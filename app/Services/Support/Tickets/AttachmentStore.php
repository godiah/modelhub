<?php

namespace App\Services\Support\Tickets;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Taking in a file someone attaches to a ticket message, safely. Nothing about the file is believed:
 *
 *  - the type comes from reading the file's content (not its name, not what the browser said); only JPEG, PNG and PDF are accepted;
 *  - a picture is decoded and written out again, which drops its metadata (location, device) and anything hidden after the image data,
 *    and a picture that claims to be enormous is refused before it is decoded;
 *  - a PDF must really be one and must not carry the active-content markers a PDF can use to run code (a best-effort check: there is no malware
 *    scanner yet, so PDFs are only ever downloaded, never opened or previewed by ModelHub);
 *  - the stored name is random and lives on a private disk; the member's file name is cleaned and kept for display only.
 *
 * `prepare` checks every file and limit first and changes nothing; `put` writes one prepared file.
 */
class AttachmentStore
{
    private const PDF_ACTIVE_CONTENT = ['/JavaScript', '/JS', '/Launch', '/OpenAction', '/EmbeddedFile', '/AcroForm', '/RichMedia', '/SubmitForm'];

    /**
     * @param  array<int, mixed>  $files
     * @return list<array{bytes: string, mime: string, ext: string, kind: string, name: string}>|string the prepared files, or why they cannot be accepted
     */
    public function prepare(SupportTicket $ticket, array $files, ?int $memberId = null): array|string
    {
        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));

        if ($files === []) {
            return [];
        }

        $max = (int) config('support.tickets.attachments.max_files');

        if (count($files) > $max) {
            return "You can attach up to {$max} files at a time.";
        }

        if ($ticket->attachments()->count() + count($files) > (int) config('support.tickets.attachments.max_per_ticket')) {
            return 'This request already has as many files as it can hold.';
        }

        if ($memberId !== null) {
            $today = SupportTicketAttachment::whereHas('message', fn ($m) => $m->where('member_id', $memberId)->where('created_at', '>=', now()->subDay()))->count();

            if ($today + count($files) > (int) config('support.tickets.attachments.max_per_member_per_day')) {
                return 'You have attached a lot of files today. Please try again tomorrow.';
            }
        }

        $prepared = [];

        foreach ($files as $file) {
            $one = $this->inspect($file);

            if (is_string($one)) {
                return $one;
            }

            $prepared[] = $one;
        }

        return $prepared;
    }

    /** @return array{bytes: string, mime: string, ext: string, kind: string, name: string}|string */
    public function inspect(UploadedFile $file): array|string
    {
        $label = $this->cleanName((string) $file->getClientOriginalName());

        if (! $file->isValid()) {
            return "\"{$label}\" could not be uploaded. Please try again.";
        }

        if ($file->getSize() > (int) config('support.tickets.attachments.max_bytes')) {
            return "\"{$label}\" is too large. Each file can be up to ".((int) config('support.tickets.attachments.max_bytes') / 1048576).' MB.';
        }

        $bytes = (string) file_get_contents($file->getRealPath());
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        return match ($mime) {
            'image/jpeg', 'image/png' => $this->image($bytes, $mime, $label),
            'application/pdf' => $this->pdf($bytes, $label),
            default => "\"{$label}\" is not a file we can take. Attach a JPEG or PNG picture (a screenshot, for example) or a PDF.",
        };
    }

    /** Write one prepared file to the private disk under a random name, and record it against the message. */
    public function put(SupportTicket $ticket, SupportTicketMessage $message, array $prepared): SupportTicketAttachment
    {
        $path = 'support-tickets/'.$ticket->id.'/'.Str::ulid().'.'.$prepared['ext'];
        Storage::disk(config('support.tickets.attachments.disk'))->put($path, $prepared['bytes']);

        return SupportTicketAttachment::create([
            'support_ticket_id' => $ticket->id, 'support_ticket_message_id' => $message->id, 'original_name' => $prepared['name'], 'path' => $path,
            'mime' => $prepared['mime'], 'kind' => $prepared['kind'], 'size' => strlen($prepared['bytes']), 'checksum' => hash('sha256', $prepared['bytes']),
        ]);
    }

    /** A file name safe to show and keep: no path, no control characters, no markup, at most 100 characters. Never used to build a path. */
    public function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N}._ \-()]+/u', '', $name) ?? '';
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '', ' .');

        return $name === '' ? 'file' : mb_substr($name, 0, 100);
    }

    private function image(string $bytes, string $mime, string $label): array|string
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false || ! in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
            return "\"{$label}\" is not a picture we can read.";
        }

        if ($info[0] < 1 || $info[1] < 1 || (int) config('support.tickets.attachments.max_pixels') < $info[0] * $info[1]) {
            return "\"{$label}\" is too large as a picture. Try a smaller screenshot.";
        }

        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return "\"{$label}\" is not a picture we can read.";
        }

        // Written out again from the decoded pixels: metadata and anything hidden after the image data do not survive
        ob_start();
        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 6);
        } else {
            imagejpeg($image, null, 90);
        }
        $clean = (string) ob_get_clean();
        imagedestroy($image);

        return ['bytes' => $clean, 'mime' => $mime, 'ext' => $mime === 'image/png' ? 'png' : 'jpg', 'kind' => SupportTicketAttachment::IMAGE, 'name' => $this->nameWithExtension($label, $mime === 'image/png' ? 'png' : 'jpg')];
    }

    private function pdf(string $bytes, string $label): array|string
    {
        if (! str_starts_with(ltrim(substr($bytes, 0, 1024), "\xEF\xBB\xBF \t\r\n\0"), '%PDF-')) {
            return "\"{$label}\" is not a PDF we can read.";
        }

        foreach (self::PDF_ACTIVE_CONTENT as $marker) {
            if (str_contains($bytes, $marker)) {
                return "\"{$label}\" contains active content, which we cannot accept. Attach a picture of the page instead.";
            }
        }

        return ['bytes' => $bytes, 'mime' => 'application/pdf', 'ext' => 'pdf', 'kind' => SupportTicketAttachment::PDF, 'name' => $this->nameWithExtension($label, 'pdf')];
    }

    /** The display name always ends with the extension of what the file really is, so a ".exe" name on a picture is not kept. */
    private function nameWithExtension(string $name, string $ext): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);

        return mb_substr(($base === '' ? 'file' : $base).'.'.$ext, 0, 100);
    }
}

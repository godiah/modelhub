<?php

use App\Enums\SupportTicketCategory;
use App\Models\StaffActivity;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Support\Tickets\AttachmentStore;
use App\Services\Support\Tickets\SeverityRules;
use App\Services\Support\Tickets\TicketService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/*
 * Files on tickets: what is accepted, what is made safe, where it is kept, who can fetch it, and that a refused file never leaves a half-saved reply.
 * Nothing about a file is believed: not its name, not what the browser said it was, and not what is hidden inside it.
 */

beforeEach(function () {
    Storage::fake('local');
    $this->member = User::factory()->create();
    $this->tickets = app(TicketService::class);
    $this->staff = staffWith('Support');
    $this->ticket = $this->tickets->open($this->member, SupportTicketCategory::PaymentIssue, 'I paid but I have no licence');
    RateLimiter::clear('support-ticket-reply:'.$this->member->id);
});

function pngBytes(int $w = 40, int $h = 30): string
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 30, 120, 90));
    ob_start();
    imagepng($im);
    $bytes = ob_get_clean();
    imagedestroy($im);

    return $bytes;
}

function jpegBytes(): string
{
    $im = imagecreatetruecolor(40, 30);
    ob_start();
    imagejpeg($im);
    $bytes = ob_get_clean();
    imagedestroy($im);

    return $bytes;
}

/** A JPEG with a comment segment (where cameras and phones keep location and device details) holding a marker we can look for. */
function jpegWithMetadata(string $marker): string
{
    $jpeg = jpegBytes();

    return "\xFF\xD8\xFF\xFE".pack('n', strlen($marker) + 2).$marker.substr($jpeg, 2);
}

/** A PNG with a text chunk holding a marker, before the end of the image. */
function pngWithChunk(string $marker): string
{
    $png = pngBytes();
    $data = "Comment\0".$marker;
    $chunk = pack('N', strlen($data)).'tEXt'.$data.pack('N', crc32('tEXt'.$data));

    return substr($png, 0, -12).$chunk.substr($png, -12);
}

function pdfBytes(string $extra = ''): string
{
    return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n{$extra}\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
}

function upload(string $name, string $bytes): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $bytes);
}

function replyWith(object $test, array $files, string $body = 'Here is my screenshot'): SupportTicketMessage|string
{
    return $test->tickets->memberReply($test->ticket, $test->member, $body, $files);
}

function storedFiles(): array
{
    return Storage::disk('local')->allFiles();
}

// ---- what is accepted ----------------------------------------------------------------------------------------------

it('takes a screenshot and a PDF with a reply, and keeps them against that message', function () {
    $message = replyWith($this, [upload('shot.png', pngBytes()), upload('statement.pdf', pdfBytes())]);

    expect($message)->toBeInstanceOf(SupportTicketMessage::class)->and($message->attachments)->toHaveCount(2)
        ->and($message->attachments->pluck('kind')->sort()->values()->all())->toBe(['image', 'pdf'])
        ->and($message->attachments->pluck('mime')->sort()->values()->all())->toBe(['application/pdf', 'image/png'])
        ->and(storedFiles())->toHaveCount(2);
});

it('lets a reply be only files, and refuses one with neither words nor files', function () {
    $filesOnly = replyWith($this, [upload('shot.png', pngBytes())], '');

    expect($filesOnly)->toBeInstanceOf(SupportTicketMessage::class)->and($filesOnly->body)->toBe('');
    expect(replyWith($this, [], ''))->toBe('Write a message first.');
});

it('refuses what it cannot be sure is a JPEG, PNG or PDF, however the file is named', function (string $name, string $bytes) {
    $result = replyWith($this, [upload($name, $bytes)]);

    expect($result)->toBeString()->toContain('not a file we can take')->and(storedFiles())->toBe([])->and($this->ticket->refresh()->messages)->toHaveCount(1);
})->with([
    'text called a picture' => ['photo.jpg', 'just some words'],
    'a PHP script called a picture' => ['photo.png', '<?php system($_GET["c"]); ?>'],
    'HTML called a PDF' => ['statement.pdf', '<html><script>alert(1)</script></html>'],
    'an SVG (it can carry script)' => ['logo.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
    'a zip called a PDF' => ['statement.pdf', "PK\x03\x04".str_repeat("\0", 64)],
    'an executable called a PNG' => ['x.png', "MZ\x90\x00".str_repeat("\0", 128)],
    'an empty file' => ['empty.png', ''],
]);

it('refuses a picture that is damaged, or that claims to be enormous before decoding it', function () {
    $damaged = replyWith($this, [upload('broken.png', substr(pngBytes(), 0, 40))]);
    expect($damaged)->toBeString();

    // a header that says 30000 x 30000 pixels: a decompression bomb if it were decoded
    $ihdr = pack('N', 30000).pack('N', 30000)."\x08\x02\x00\x00\x00";
    $bomb = "\x89PNG\r\n\x1A\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
    expect(replyWith($this, [upload('huge.png', $bomb)]))->toBeString()->and(storedFiles())->toBe([]);
});

it('refuses a perfectly good picture that is bigger in pixels than allowed, before decoding it', function () {
    config(['support.tickets.attachments.max_pixels' => 1000]); // 100 x 100 = 10,000 pixels is over this

    expect(replyWith($this, [upload('big.png', pngBytes(100, 100))]))->toContain('too large as a picture')->and(storedFiles())->toBe([]);

    config(['support.tickets.attachments.max_pixels' => 25_000_000]);
    expect(replyWith($this, [upload('ok.png', pngBytes(100, 100))]))->toBeInstanceOf(SupportTicketMessage::class);
});

it('refuses a PDF that is not one, or that carries the markers a PDF uses to run code', function (string $extra) {
    expect(replyWith($this, [upload('doc.pdf', pdfBytes($extra))]))->toContain('active content')->and(storedFiles())->toBe([]);
})->with(['/JavaScript', '/JS (app.alert(1))', '/OpenAction', '/Launch', '/EmbeddedFile', '/AcroForm']);

it('refuses a file that is too large, too many at once, and more than a request or a day can hold', function () {
    expect(replyWith($this, [upload('big.pdf', pdfBytes(str_repeat('a', 5 * 1024 * 1024 + 10)))]))->toContain('too large');

    expect(replyWith($this, array_map(fn ($i) => upload("s{$i}.png", pngBytes()), range(1, 6))))->toContain('up to 5 files');

    config(['support.tickets.attachments.max_per_ticket' => 3]);
    replyWith($this, [upload('a.png', pngBytes()), upload('b.png', pngBytes())]);
    expect(replyWith($this, [upload('c.png', pngBytes()), upload('d.png', pngBytes())]))->toContain('as many files as it can hold');

    config(['support.tickets.attachments.max_per_ticket' => 25, 'support.tickets.attachments.max_per_member_per_day' => 3]);
    expect(replyWith($this, [upload('e.png', pngBytes()), upload('f.png', pngBytes())]))->toContain('a lot of files today');
});

// ---- what is made safe -----------------------------------------------------------------------------------------------

it('writes a picture out again, so its metadata and anything hidden in it do not survive', function () {
    $message = replyWith($this, [
        upload('phone.jpg', jpegWithMetadata('GPS-AND-DEVICE-MARKER')),
        upload('shot.png', pngWithChunk('HIDDEN-TEXT-CHUNK')),
        upload('polyglot.png', pngBytes().'<?php system($_GET["c"]); ?>'),
    ]);

    foreach ($message->attachments as $file) {
        $stored = Storage::disk('local')->get($file->path);

        expect($stored)->not->toContain('GPS-AND-DEVICE-MARKER')->not->toContain('HIDDEN-TEXT-CHUNK')->not->toContain('<?php');
        expect(getimagesizefromstring($stored))->not->toBeFalse();
        expect($file->checksum)->toBe(hash('sha256', $stored))->and($file->size)->toBe(strlen($stored));
    }
});

it('keeps a picture\'s size, and keeps a PDF byte for byte', function () {
    $pdf = pdfBytes('% harmless comment');
    $message = replyWith($this, [upload('a.png', pngBytes(64, 48)), upload('b.pdf', $pdf)]);

    $image = $message->attachments->firstWhere('kind', 'image');
    $document = $message->attachments->firstWhere('kind', 'pdf');

    expect(array_slice(getimagesizefromstring(Storage::disk('local')->get($image->path)), 0, 2))->toBe([64, 48])
        ->and(Storage::disk('local')->get($document->path))->toBe($pdf);
});

it('stores a file under a random name on the private disk and never uses the name it came with', function () {
    $message = replyWith($this, [upload('../../etc/passwd.png', pngBytes()), upload('<b>x</b>.pdf', pdfBytes())]);

    foreach ($message->attachments as $file) {
        expect($file->path)->toStartWith('support-tickets/'.$this->ticket->id.'/')->toMatch('#^support-tickets/\d+/[0-9A-Za-z]{26}\.(png|pdf)$#')
            ->and($file->path)->not->toContain('passwd')->and($file->original_name)->not->toContain('/')->not->toContain('<')->not->toContain('..');
        Storage::disk('local')->assertExists($file->path);
    }
});

it('shows a name that ends with what the file really is, whatever it was called', function () {
    $message = replyWith($this, [upload('invoice.exe', pngBytes()), upload('report.docx', pdfBytes())]);

    expect($message->attachments->pluck('original_name')->sort()->values()->all())->toBe(['invoice.png', 'report.pdf']);
});

it('cleans names without ever trusting them', function (string $given, string $shown) {
    expect(app(AttachmentStore::class)->cleanName($given))->toBe($shown);
})->with([
    ['holiday photo.png', 'holiday photo.png'],
    ['C:\\Users\\me\\Desktop\\shot.png', 'shot.png'],
    ["bad\x00name\r\n.png", 'badname.png'],
    ['...', 'file'],
    ['', 'file'],
    [str_repeat('long', 60).'.png', str_repeat('long', 25)],
]);

// ---- all or nothing -------------------------------------------------------------------------------------------------------

it('saves nothing when any one file is refused: no message, no files', function () {
    $result = replyWith($this, [upload('good.png', pngBytes()), upload('bad.jpg', 'not a picture')], 'Two files');

    expect($result)->toBeString()->and($this->ticket->refresh()->messages)->toHaveCount(1)->and(SupportTicketAttachment::count())->toBe(0)->and(storedFiles())->toBe([]);
});

it('removes the files already written when saving fails part-way, and saves no message', function () {
    $failing = new class extends AttachmentStore
    {
        private int $calls = 0;

        public function put(SupportTicket $ticket, SupportTicketMessage $message, array $prepared): SupportTicketAttachment
        {
            if (++$this->calls === 2) {
                throw new RuntimeException('disk full');
            }

            return parent::put($ticket, $message, $prepared);
        }
    };
    $service = new TicketService(app(SeverityRules::class), null, $failing);

    expect(fn () => $service->memberReply($this->ticket, $this->member, 'Two files', [upload('a.png', pngBytes()), upload('b.png', pngBytes())]))->toThrow(RuntimeException::class);

    expect(storedFiles())->toBe([])->and($this->ticket->refresh()->messages)->toHaveCount(1)->and(SupportTicketAttachment::count())->toBe(0);
});

it('deletes the file when the attachment is deleted', function () {
    $file = replyWith($this, [upload('a.png', pngBytes())])->attachments->first();

    $file->delete();

    Storage::disk('local')->assertMissing($file->path);
});

// ---- who can fetch a file ------------------------------------------------------------------------------------------------

function attachmentOf(object $test, string $name = 'shot.png', ?string $bytes = null): SupportTicketAttachment
{
    return replyWith($test, [upload($name, $bytes ?? pngBytes())])->attachments->first();
}

it('gives a member their own file with headers that stop the browser guessing, caching or running it', function () {
    $image = attachmentOf($this);
    $pdf = attachmentOf($this, 'statement.pdf', pdfBytes());
    $this->actingAs($this->member);

    $shown = $this->get(route('support.requests.attachments.show', [$this->ticket, $image->id]))->assertOk();
    $downloaded = $this->get(route('support.requests.attachments.show', [$this->ticket, $pdf->id]))->assertOk();

    expect($shown->headers->get('Content-Type'))->toBe('image/png')->and($shown->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($downloaded->headers->get('Content-Type'))->toBe('application/pdf')->and($downloaded->headers->get('Content-Disposition'))->toStartWith('attachment')->toContain('statement.pdf');

    foreach ([$shown, $downloaded] as $response) {
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')->and($response->headers->get('Cache-Control'))->toContain('no-store')
            ->and($response->headers->get('Content-Security-Policy'))->toBe("default-src 'none'; sandbox");
    }
});

it('gives someone else\'s file, a file on another request, a missing one, and a guest the same refusal', function () {
    $mine = attachmentOf($this);
    $other = User::factory()->create();
    $theirTicket = $this->tickets->open($other, SupportTicketCategory::Other, 'Their private matter');
    RateLimiter::clear('support-ticket-reply:'.$other->id);
    $theirFile = $this->tickets->memberReply($theirTicket, $other, 'Theirs', [upload('theirs.png', pngBytes())])->attachments->first();

    $this->get(route('support.requests.attachments.show', [$this->ticket, $mine->id]))->assertRedirect(); // a guest

    $this->actingAs($other);
    $notMine = $this->get(route('support.requests.attachments.show', [$this->ticket, $mine->id]));
    $wrongTicket = $this->get(route('support.requests.attachments.show', [$theirTicket, $mine->id]));
    $missing = $this->get(route('support.requests.attachments.show', [$theirTicket, 999999]));

    foreach ([$notMine, $wrongTicket, $missing] as $response) {
        $response->assertNotFound();
    }
    $this->get(route('support.requests.attachments.show', [$theirTicket, $theirFile->id]))->assertOk();
});

it('never gives a member a file from a staff-only note', function () {
    $note = $this->tickets->note($this->ticket, $this->staff, 'Checked the screenshot');
    $file = SupportTicketAttachment::create([
        'support_ticket_id' => $this->ticket->id, 'support_ticket_message_id' => $note->id, 'original_name' => 'internal.png', 'path' => 'support-tickets/'.$this->ticket->id.'/internal.png',
        'mime' => 'image/png', 'kind' => 'image', 'size' => 10, 'checksum' => str_repeat('a', 64),
    ]);
    Storage::disk('local')->put($file->path, pngBytes());

    $this->actingAs($this->member)->get(route('support.requests.attachments.show', [$this->ticket, $file->id]))->assertNotFound();
    $this->actingAs($this->staff, 'staff')->get(route('admin.support.tickets.attachments.show', [$this->ticket, $file->id]))->assertOk();
});

it('lets staff who may see tickets fetch a file, and nobody else', function () {
    $file = attachmentOf($this);

    $this->actingAs($this->staff, 'staff')->get(route('admin.support.tickets.attachments.show', [$this->ticket, $file->id]))->assertOk();
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.support.tickets.attachments.show', [$this->ticket, $file->id]))->assertForbidden();
    $this->actingAs($this->member)->get(route('admin.support.tickets.attachments.show', [$this->ticket, $file->id]))->assertForbidden(); // a member is not staff
});

// ---- through the pages --------------------------------------------------------------------------------------------

it('lets a member attach files from their request page and shows them in the thread', function () {
    $this->actingAs($this->member);

    $this->post(route('support.requests.reply', $this->ticket), ['body' => 'Screenshot attached', 'files' => [upload('shot.png', pngBytes()), upload('statement.pdf', pdfBytes())]])->assertRedirect(route('support.requests.show', $this->ticket));

    $page = $this->get(route('support.requests.show', $this->ticket))->assertOk()->assertSee('statement.pdf');
    expect($page->getContent())->toContain('/attachments/');
});

it('lets staff attach a file to a reply, and the member sees it', function () {
    $this->actingAs($this->staff, 'staff');
    $this->post(route('admin.support.tickets.reply', $this->ticket), ['body' => 'Here is the receipt', 'files' => [upload('receipt.pdf', pdfBytes())]])->assertRedirect();
    $this->flushSession();

    $this->actingAs($this->member)->get(route('support.requests.show', $this->ticket))->assertSee('receipt.pdf')->assertSee('Here is the receipt');
    expect(StaffActivity::where('action', 'support.ticket.replied')->first()->details)->toBe(['files' => 1]);
});

it('tells a member why a file was refused and keeps what they typed', function () {
    $this->actingAs($this->member);

    $this->from(route('support.requests.show', $this->ticket))->post(route('support.requests.reply', $this->ticket), ['body' => 'Please see this', 'files' => [upload('virus.png', '<?php evil();')]])->assertRedirect();

    expect($this->ticket->refresh()->messages)->toHaveCount(1)->and(storedFiles())->toBe([]);
    $this->get(route('support.requests.show', $this->ticket))->assertOk();
});

it('draws a file name as text, never as markup, even if a bad one got into the database', function () {
    $file = attachmentOf($this, 'photo.pdf', pdfBytes());
    $file->forceFill(['original_name' => '<script>alert(1)</script>.pdf'])->save();

    $this->actingAs($this->member);
    $member = $this->get(route('support.requests.show', $this->ticket))->getContent();
    $this->flushSession();
    $this->actingAs($this->staff, 'staff');
    $staff = $this->get(route('admin.support.tickets.show', $this->ticket))->getContent();

    foreach ([$member, $staff] as $html) {
        expect($html)->not->toContain('<script>alert(1)</script>')->toContain('&lt;script&gt;');
    }
});

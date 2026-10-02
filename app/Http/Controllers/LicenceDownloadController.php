<?php

namespace App\Http\Controllers;

use App\Models\IssuedLicence;
use App\Models\LicenceDownload;
use App\Models\ProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Downloading the files of a model you hold a licence for. The files are private: this is the only way a buyer reaches them. */
class LicenceDownloadController extends Controller
{
    public function __invoke(Request $request, IssuedLicence $licence, ProductFile $file)
    {
        abort_unless($licence->user_id === $request->user()->id, 404);
        abort_unless($licence->product_id !== null && $file->product_id === $licence->product_id, 404);
        abort_unless($licence->isActive(), 403, 'This licence has ended, so the files can no longer be downloaded.');

        // A file whose copy has gone missing is reported plainly, not as a server error, and is not counted as a download
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404, 'This file is not available right now. Please write to us and we will sort it out.');

        LicenceDownload::create(['issued_licence_id' => $licence->id, 'product_file_id' => $file->id, 'file_name' => $file->original_name, 'ip_address' => $request->ip()]);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}

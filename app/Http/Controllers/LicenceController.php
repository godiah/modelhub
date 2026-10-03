<?php

namespace App\Http\Controllers;

use App\Models\IssuedLicence;
use App\Models\LicenceDownload;
use App\Services\Marketplace\LicenceLibrary;
use Illuminate\Http\Request;

/** The licences a member holds: the list, and the certificate of each. Only the holder can see theirs. */
class LicenceController extends Controller
{
    public function index(Request $request, LicenceLibrary $library)
    {
        $user = $request->user();
        $tab = LicenceLibrary::tab($request->query('tab'));
        $sort = LicenceLibrary::sort($request->query('sort'));
        $term = trim((string) $request->query('q'));

        return view('licences.index', [
            'licences' => $library->page($user, $tab, $term, $sort),
            'counts' => $library->counts($user),
            'stats' => $library->stats($user),
            'tab' => $tab, 'sort' => $sort, 'term' => $term,
        ]);
    }

    public function show(Request $request, IssuedLicence $licence, LicenceLibrary $library)
    {
        // Somebody else's licence is a 404, not a 403: its key is not confirmed to exist
        abort_unless($licence->user_id === $request->user()->id, 404);

        $licence->load('product.files', 'product.images');
        $library->decorate($request->user(), collect([$licence]));

        return view('licences.show', [
            'licence' => $licence,
            // How often each file was taken, and the latest downloads, for the files list and the history beside it
            'fileStats' => LicenceDownload::selectRaw('product_file_id, count(*) as total, max(created_at) as last_at')->where('issued_licence_id', $licence->id)->groupBy('product_file_id')->get()->keyBy('product_file_id'),
            'recent' => LicenceDownload::where('issued_licence_id', $licence->id)->latest('id')->limit(6)->get(),
        ]);
    }
}

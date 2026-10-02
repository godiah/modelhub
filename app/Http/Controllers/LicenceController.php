<?php

namespace App\Http\Controllers;

use App\Models\IssuedLicence;
use Illuminate\Http\Request;

/** The licences a member holds: the list, and the certificate of each. Only the holder can see theirs. */
class LicenceController extends Controller
{
    public function index(Request $request)
    {
        return view('licences.index', [
            'licences' => $request->user()->issuedLicences()->with('product:id,slug,title,deleted_at')->latest('issued_at')->latest('id')->paginate(12),
        ]);
    }

    public function show(Request $request, IssuedLicence $licence)
    {
        // Somebody else's licence is a 404, not a 403: its key is not confirmed to exist
        abort_unless($licence->user_id === $request->user()->id, 404);

        return view('licences.show', ['licence' => $licence->load('product.files')]);
    }
}

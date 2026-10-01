<?php

namespace App\Http\Controllers;

/** The public legal documents. Wording lives in resources/views/legal; business details in config/legal.php. */
class LegalController extends Controller
{
    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }
}

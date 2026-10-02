<?php

namespace App\Contracts;

use Illuminate\Http\Request;

/**
 * A gateway whose callbacks are not signed by the provider (Daraja's are not) checks something else instead: here, a secret carried
 * in the callback URL we gave the provider. The callback controllers refuse a request this says no to.
 */
interface VerifiesCallbacks
{
    public function verifiesCallback(Request $request): bool;
}

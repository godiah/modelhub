<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The read API for the support assistant. Everything here is GET, about the member named by the signed claim, and writes nothing. */
class SupportReadController extends Controller
{
    /** Proves the plumbing end to end without reading any record: the call was signed, the claim names an active member in stage. */
    public function ping(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok', 'as_of' => now()->toIso8601String()]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PolicyManagementController extends Controller
{
    public function index()
    {
        return view('jobBoard.engagements.policy');
    }
}

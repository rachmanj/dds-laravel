<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SapController extends Controller
{
    public function logs()
    {
        $logs = DB::table('sap_logs')->orderByDesc('created_at')->paginate(50);
        return view('admin.sap-logs', compact('logs'));
    }
}

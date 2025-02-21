<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with(['performedBy', 'performedOn'])->latest()->get();
        return view('logs.index', compact('logs'));
    }
}

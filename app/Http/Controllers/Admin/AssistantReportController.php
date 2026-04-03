<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantRequestLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = AssistantRequestLog::query()
            ->with(['user', 'conversation']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), [
            AssistantRequestLog::STATUS_SUCCESS,
            AssistantRequestLog::STATUS_ERROR,
        ], true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $logs = $query->orderByDesc('id')->paginate(50)->withQueryString();

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('username')
            ->limit(500)
            ->get(['id', 'name', 'username']);

        return view('admin.assistant-report.index', [
            'logs' => $logs,
            'users' => $users,
        ]);
    }
}

@extends('layouts.main')

@section('title_page', 'Domain Assistant request log')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Assistant request log</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-robot mr-2"></i> Domain Assistant — request log
                    </h3>
                </div>
                <div class="card-body">
                    <form method="get" action="{{ route('admin.assistant-report.index') }}" class="mb-4">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-2">
                                <label for="filter-user">User</label>
                                <select name="user_id" id="filter-user" class="form-control">
                                    <option value="">All users</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}"
                                            {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>
                                            {{ $u->name ?: $u->username }} ({{ $u->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="filter-status">Status</label>
                                <select name="status" id="filter-status" class="form-control">
                                    <option value="">All</option>
                                    <option value="{{ \App\Models\AssistantRequestLog::STATUS_SUCCESS }}"
                                        {{ request('status') === \App\Models\AssistantRequestLog::STATUS_SUCCESS ? 'selected' : '' }}>
                                        Success
                                    </option>
                                    <option value="{{ \App\Models\AssistantRequestLog::STATUS_ERROR }}"
                                        {{ request('status') === \App\Models\AssistantRequestLog::STATUS_ERROR ? 'selected' : '' }}>
                                        Error
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="filter-from">From</label>
                                <input type="date" name="date_from" id="filter-from" class="form-control"
                                    value="{{ request('date_from') }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="filter-to">To</label>
                                <input type="date" name="date_to" id="filter-to" class="form-control"
                                    value="{{ request('date_to') }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="filter-per-page">Per page</label>
                                <select name="per_page" id="filter-per-page" class="form-control">
                                    @foreach ($allowedPerPage as $n)
                                        <option value="{{ $n }}" {{ (int) $perPage === $n ? 'selected' : '' }}>
                                            {{ $n }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter mr-1"></i> Apply
                                </button>
                                <a href="{{ route('admin.assistant-report.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Time (UTC)</th>
                                    <th>User</th>
                                    <th>Question</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                    <th>Show all</th>
                                    <th>Msg len</th>
                                    <th>Conversation</th>
                                    <th>Tools</th>
                                    <th>Error</th>
                                    <th>IP</th>
                                    <th>TG chat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td class="text-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if ($log->user)
                                                {{ $log->user->name ?: $log->user->username }}
                                                <span class="text-muted small">({{ $log->user->username }})</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="small" style="max-width: 280px;">
                                            @if ($log->user_message)
                                                <span class="d-inline-block text-truncate"
                                                    style="max-width: 260px; vertical-align: bottom;"
                                                    title="{{ e($log->user_message) }}">{{ \Illuminate\Support\Str::limit($log->user_message, 160) }}</span>
                                            @else
                                                <span class="text-muted" title="Logged before full-text storage was enabled">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($log->status === \App\Models\AssistantRequestLog::STATUS_SUCCESS)
                                                <span class="badge badge-success">success</span>
                                            @else
                                                <span class="badge badge-danger">error</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->duration_ms }} ms</td>
                                        <td>{{ $log->show_all_records ? 'yes' : 'no' }}</td>
                                        <td>{{ $log->user_message_length }}</td>
                                        <td>
                                            @if ($log->assistant_conversation_id)
                                                #{{ $log->assistant_conversation_id }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="small" style="max-width: 220px;">
                                            @if ($log->tools_invoked)
                                                <code class="d-block text-truncate"
                                                    title="{{ json_encode($log->tools_invoked) }}">{{ json_encode($log->tools_invoked) }}</code>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="small" style="max-width: 200px;">
                                            @if ($log->error_summary)
                                                <span title="{{ $log->error_summary }}">{{ \Illuminate\Support\Str::limit($log->error_summary, 80) }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="small text-nowrap">{{ $log->ip_address }}</td>
                                        <td class="small text-nowrap">
                                            @if ($log->telegram_chat_id)
                                                {{ $log->telegram_chat_id }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-muted">No log rows match the filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                        <p class="text-muted small mb-2 mb-md-0">
                            @if ($logs->total() > 0)
                                Showing <strong>{{ $logs->firstItem() }}</strong>–<strong>{{ $logs->lastItem() }}</strong>
                                of <strong>{{ $logs->total() }}</strong>
                                {{ $logs->total() === 1 ? 'entry' : 'entries' }}
                            @else
                                No entries match the current filters.
                            @endif
                        </p>
                        <div>
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

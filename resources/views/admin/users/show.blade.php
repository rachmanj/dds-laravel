@extends('layouts.main')

@section('title_page')
    View User
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">User Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Name</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">{{ $user->name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NIK</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">{{ $user->nik ?? '—' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">{{ $user->username ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Project</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">
                                            @if ($user->projectInfo)
                                                {{ $user->projectInfo->code }} — {{ $user->projectInfo->owner }}
                                            @elseif ($user->project)
                                                {{ $user->project }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">
                                            @if ($user->department)
                                                {{ $user->department->name }}
                                                @if ($user->department->project)
                                                    — {{ $user->department->project }}
                                                @endif
                                                @if ($user->department->location_code)
                                                    ({{ $user->department->location_code }})
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <p class="mb-0">
                                            @if ($user->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email verified</label>
                                        <p class="mb-0 form-control-plaintext border rounded px-3 py-2 bg-light">
                                            @if ($user->email_verified_at)
                                                {{ $user->email_verified_at->format('Y-m-d H:i') }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-secondary mb-3">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Telegram — Domain Assistant</h4>
                                </div>
                                <div class="card-body">
                                    @if ($user->telegram_user_id)
                                        <p class="mb-0">
                                            <strong>Linked:</strong>
                                            ID {{ $user->telegram_user_id }}
                                            @if ($user->telegram_username)
                                                — {{ '@'.$user->telegram_username }}
                                            @endif
                                        </p>
                                    @else
                                        <p class="text-muted mb-0">Not linked.</p>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Roles</label>
                                <p class="mb-0">
                                    @forelse ($user->roles as $role)
                                        <span class="badge badge-primary mr-1">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </p>
                            </div>

                            <div class="form-group mb-0">
                                <label>Direct permissions</label>
                                <p class="mb-0">
                                    @forelse ($user->permissions as $permission)
                                        <span class="badge badge-info mr-1 mb-1">{{ $permission->name }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit User
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Close
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

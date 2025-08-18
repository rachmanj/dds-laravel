@extends('layouts.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/">Home</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- User Info Card -->
            @auth
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user mr-2"></i>
                                    Welcome back, {{ auth()->user()->name }}!
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                                                <p><strong>Roles:</strong>
                                                    @foreach (auth()->user()->roles as $role)
                                                        <span class="badge badge-primary">{{ $role->name }}</span>
                                                    @endforeach
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Permissions:</strong></p>
                                                <div class="d-flex flex-wrap">
                                                    @foreach (auth()->user()->getAllPermissions()->take(5) as $permission)
                                                        <span class="badge badge-info mr-1 mb-1">{{ $permission->name }}</span>
                                                    @endforeach
                                                    @if (auth()->user()->getAllPermissions()->count() > 5)
                                                        <span
                                                            class="badge badge-secondary mr-1 mb-1">+{{ auth()->user()->getAllPermissions()->count() - 5 }}
                                                            more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="user-panel">
                                            <img src="{{ asset('adminlte/dist/img/user2-160x160.jpg') }}"
                                                class="img-circle elevation-2" alt="User Image"
                                                style="width: 80px; height: 80px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endauth

            <!-- Info boxes -->
            <div class="row">
                @can('view-users')
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Users</span>
                                <span class="info-box-number">1,258</span>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 70%"></div>
                                </div>
                                <span class="progress-description">
                                    70% Increase in 30 Days
                                </span>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('view-content')
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-file-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Content</span>
                                <span class="info-box-number">847</span>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 50%"></div>
                                </div>
                                <span class="progress-description">
                                    50% Increase in 30 Days
                                </span>
                            </div>
                        </div>
                    </div>
                @endcan

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Active Sessions</span>
                            <span class="info-box-number">156</span>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 30%"></div>
                            </div>
                            <span class="progress-description">
                                30% Increase in 30 Days
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-server"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">System Status</span>
                            <span class="info-box-number text-success">Online</span>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 100%"></div>
                            </div>
                            <span class="progress-description">
                                All systems operational
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Small boxes (Stat box) -->
            <div class="row">
                @can('view-users')
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>1,258</h3>
                                <p>Total Users</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <a href="/users" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                @endcan

                @can('view-content')
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>847</h3>
                                <p>Total Content</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <a href="/content" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                @endcan

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>156</h3>
                            <p>Active Sessions</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>99.9%</h3>
                            <p>Uptime</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt mr-2"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @can('view-users')
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="/users" class="text-decoration-none">
                                            <div class="card card-outline card-primary h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                                    <h5 class="card-title">User Management</h5>
                                                    <p class="card-text text-muted">Manage system users and their roles</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endcan

                                @can('view-roles')
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="/roles" class="text-decoration-none">
                                            <div class="card card-outline card-success h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-user-shield fa-3x text-success mb-3"></i>
                                                    <h5 class="card-title">Role Management</h5>
                                                    <p class="card-text text-muted">Configure user roles and permissions</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endcan

                                @can('view-content')
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="/content" class="text-decoration-none">
                                            <div class="card card-outline card-warning h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
                                                    <h5 class="card-title">Content Management</h5>
                                                    <p class="card-text text-muted">Create and manage system content</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endcan

                                @can('view-settings')
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="/settings" class="text-decoration-none">
                                            <div class="card card-outline card-info h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-cog fa-3x text-info mb-3"></i>
                                                    <h5 class="card-title">System Settings</h5>
                                                    <p class="card-text text-muted">Configure system preferences</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role-Based Content -->
            <div class="row">
                @role('super-admin')
                    <div class="col-12">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-crown mr-2"></i>
                                    Super Admin Panel
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Super Admin Access!</h5>
                                    You have full access to all system features and can manage the entire application.
                                </div>
                                <p class="text-muted">You can manage users, roles, permissions, content, and system settings.
                                </p>
                            </div>
                        </div>
                    </div>
                @endrole

                @role('admin')
                    <div class="col-12">
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user-shield mr-2"></i>
                                    Admin Panel
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success">
                                    <h5><i class="icon fas fa-check"></i> Admin Access!</h5>
                                    You have extensive permissions to manage most system features.
                                </div>
                                <p class="text-muted">You can manage users, content, and settings, but cannot modify roles.</p>
                            </div>
                        </div>
                    </div>
                @endrole

                @role('manager')
                    <div class="col-12">
                        <div class="card card-warning card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user-tie mr-2"></i>
                                    Manager Panel
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Manager Access!</h5>
                                    You have moderate permissions for content management and user viewing.
                                </div>
                                <p class="text-muted">You can view users, manage content, and view settings.</p>
                            </div>
                        </div>
                    </div>
                @endrole

                @role('user')
                    <div class="col-12">
                        <div class="card card-info card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user mr-2"></i>
                                    User Panel
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> User Access!</h5>
                                    You have basic access to view content and use the system.
                                </div>
                                <p class="text-muted">You can view content and use basic system features.</p>
                            </div>
                        </div>
                    </div>
                @endrole
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                Recent Activity
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div>
                                    <i class="fas fa-user bg-blue"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> Just now</span>
                                        <h3 class="timeline-header">System Login</h3>
                                        <div class="timeline-body">
                                            You logged into the system successfully.
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fas fa-tachometer-alt bg-green"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> 2 minutes ago</span>
                                        <h3 class="timeline-header">Dashboard Access</h3>
                                        <div class="timeline-body">
                                            Dashboard page was accessed.
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fas fa-shield-alt bg-yellow"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> 5 minutes ago</span>
                                        <h3 class="timeline-header">Permission Check</h3>
                                        <div class="timeline-body">
                                            Permission system is working correctly.
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fas fa-clock bg-gray"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

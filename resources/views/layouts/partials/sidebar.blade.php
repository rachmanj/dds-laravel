<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/dashboard" class="brand-link">
        <img src="{{ asset('adminlte/dist/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
            class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">ARKA - <b>DDS</b></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->

                <!-- Dashboard Section -->
                <li
                    class="nav-item {{ request()->is('dashboard') || request()->is('processing-analytics') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ request()->is('dashboard') || request()->is('processing-analytics') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            Dashboard
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dashboard 1</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('processing-analytics.index') }}"
                                class="nav-link {{ request()->is('processing-analytics') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dashboard 2</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="nav-header">MAIN</li>

                <!-- Additional Documents Section -->
                @can('view-additional-documents')
                    @include('layouts.partials.menu.additional-documents')
                @endcan

                <!-- Invoices Section -->
                @can('view-invoices')
                    @include('layouts.partials.menu.invoices')
                @endcan

                <!-- Distributions Section -->
                @can('view-distributions')
                    @include('layouts.partials.menu.distributions')
                @endcan

                <!-- Reports Section -->
                @can('view-reconcile')
                    @include('layouts.partials.menu.reports')
                @endcan

                <!-- Messages Section -->
                <li class="nav-item {{ request()->is('messages*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('messages*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>
                            Messages
                            <span class="badge badge-warning right" id="sidebar-unread-count">
                                {{ Auth::user()->unread_messages_count }}
                            </span>
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('messages.index') }}"
                                class="nav-link {{ request()->is('messages') && !request()->is('messages/sent') && !request()->is('messages/create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Inbox</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('messages.sent') }}"
                                class="nav-link {{ request()->is('messages/sent') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Sent</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('messages.create') }}"
                                class="nav-link {{ request()->is('messages/create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Compose</p>
                            </a>
                        </li>
                    </ul>
                </li>

                @can('view-master')
                    @include('layouts.partials.menu.master')
                @endcan

                @can('view-admin')
                    @include('layouts.partials.menu.admin')
                @endcan


            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

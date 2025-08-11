<!-- Navbar -->
<nav class="main-header navbar navbar-expand-md navbar-light navbar-dark fixed-top py-1">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <span class="d-none d-md-inline">
                    {{ Auth::user()->name }}
                    @if (Auth::user()->department_location_code)
                        <small>({{ Auth::user()->department_location_code }})</small>
                    @endif
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <!-- User image -->
                {{-- <li class="user-header bg-primary">
                    <img src="{{ asset('adminlte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2"
                        alt="User Image">
                    <p>
                        {{ Auth::user()->name }}
                        @if (Auth::user()->department_location_code)
                            <br><small class="text-white-50">({{ Auth::user()->department_location_code }})</small>
                        @endif
                        <br><small>{{ Auth::user()->email }}</small>
                    </p>
                </li> --}}
                <!-- Menu Footer-->
                <li class="user-footer">
                    <a href="{{ route('profile.change-password') }}" class="btn btn-default btn-flat">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-default btn-flat float-right">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </li>

    </ul>
</nav>
<!-- /.navbar -->

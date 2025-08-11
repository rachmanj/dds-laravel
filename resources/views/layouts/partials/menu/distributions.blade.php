<li class="nav-item {{ request()->is('content*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->is('content*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-file-alt"></i>
        <p>
            Distribution
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="/distribution" class="nav-link {{ request()->is('distribution') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Dashboard</p>
            </a>
        </li>
        @can('create-distributions')
            <li class="nav-item">
                <a href="/distribution/create" class="nav-link {{ request()->is('distribution/create') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Distribution List</p>
                </a>
            </li>
        @endcan
    </ul>
</li>

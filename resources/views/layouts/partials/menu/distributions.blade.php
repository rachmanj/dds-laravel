<li class="nav-item {{ request()->is('distributions*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->is('distributions*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-share-alt"></i>
        <p>
            Distribution
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('distributions.index') }}"
                class="nav-link {{ request()->routeIs('distributions.index') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Distribution List</p>
            </a>
        </li>
        @can('create-distributions')
            <li class="nav-item">
                <a href="{{ route('distributions.create') }}"
                    class="nav-link {{ request()->routeIs('distributions.create') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Create Distribution</p>
                </a>
            </li>
        @endcan
        @can('view-distributions')
            <li class="nav-item">
                <a href="{{ route('distributions.numbering-stats') }}"
                    class="nav-link {{ request()->routeIs('distributions.numbering-stats') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Numbering Stats</p>
                </a>
            </li>
        @endcan
        @if (auth()->user()->department)
            <li class="nav-item">
                <a href="{{ route('distributions.department-history') }}"
                    class="nav-link {{ request()->routeIs('distributions.department-history') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Department History</p>
                </a>
            </li>
        @endif
    </ul>
</li>

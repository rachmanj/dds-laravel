<li class="nav-item {{ request()->is('settings*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-cog"></i>
        <p>
            Settings
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="/settings" class="nav-link {{ request()->is('settings') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>General Settings</p>
            </a>
        </li>
        @can('edit-settings')
            <li class="nav-item">
                <a href="/settings/permissions"
                    class="nav-link {{ request()->is('settings/permissions') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Permission Settings</p>
                </a>
            </li>
        @endcan
    </ul>
</li>

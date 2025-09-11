<li class="nav-item {{ request()->routeIs('reconcile.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('reconcile.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>
            Reports
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <!-- Reconciliation Report -->
        <li class="nav-item">
            <a href="{{ route('reconcile.index') }}"
                class="nav-link {{ request()->routeIs('reconcile.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Reconciliation</p>
            </a>
        </li>
    </ul>
</li>

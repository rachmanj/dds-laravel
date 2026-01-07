<li class="nav-item {{ request()->routeIs('reconcile.*') || request()->routeIs('accounting-fulfillment.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('reconcile.*') || request()->routeIs('accounting-fulfillment.*') ? 'active' : '' }}">
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
        <!-- Accounting Monthly Invoice Fulfillment Report -->
        @hasanyrole('superadmin|admin|accounting')
            <li class="nav-item">
                <a href="{{ route('accounting-fulfillment.index') }}"
                    class="nav-link {{ request()->routeIs('accounting-fulfillment.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Accounting Monthly Invoice Fulfillment</p>
                </a>
            </li>
        @endhasanyrole
    </ul>
</li>

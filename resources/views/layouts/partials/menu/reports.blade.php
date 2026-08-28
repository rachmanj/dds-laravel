<li class="nav-item {{ request()->routeIs('reconcile.*') || request()->routeIs('accounting-fulfillment.*') || request()->routeIs('document-report.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('reconcile.*') || request()->routeIs('accounting-fulfillment.*') || request()->routeIs('document-report.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>
            Reports
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('view-reconcile')
            <li class="nav-item">
                <a href="{{ route('reconcile.index') }}"
                    class="nav-link {{ request()->routeIs('reconcile.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Reconciliation</p>
                </a>
            </li>
        @endcan
        @hasanyrole('superadmin|admin|accounting')
            <li class="nav-item">
                <a href="{{ route('accounting-fulfillment.index') }}"
                    class="nav-link {{ request()->routeIs('accounting-fulfillment.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Accounting Monthly Invoice Fulfillment</p>
                </a>
            </li>
        @endhasanyrole
        @can('view-document-report')
            <li class="nav-item">
                <a href="{{ route('document-report.invoices') }}"
                    class="nav-link {{ request()->routeIs('document-report.invoices') || request()->routeIs('document-report.invoices-*') || request()->routeIs('document-report.invoice-detail') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>All Invoice Report</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('document-report.additional-documents') }}"
                    class="nav-link {{ request()->routeIs('document-report.additional-documents') || request()->routeIs('document-report.additional-documents-*') || request()->routeIs('document-report.additional-document-detail') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>All Additional Documents Report</p>
                </a>
            </li>
        @endcan
    </ul>
</li>

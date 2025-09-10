<li class="nav-item {{ request()->routeIs('invoices.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-file-invoice"></i>
        <p>
            Invoices
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <!-- Dashboard -->
        <li class="nav-item">
            <a href="{{ route('invoices.dashboard') }}"
                class="nav-link {{ request()->routeIs('invoices.dashboard') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Dashboard</p>
            </a>
        </li>

        <!-- List Invoices -->
        <li class="nav-item">
            <a href="{{ route('invoices.index') }}"
                class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>List Invoices</p>
            </a>
        </li>

        <!-- Create New Invoice -->
        <li class="nav-item">
            <a href="{{ route('invoices.create') }}"
                class="nav-link {{ request()->routeIs('invoices.create') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Create New Invoice</p>
            </a>
        </li>

        <!-- Invoice Attachments -->
        <li class="nav-item">
            <a href="{{ route('invoices.attachments.index') }}"
                class="nav-link {{ request()->routeIs('invoices.attachments.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Invoice Attachments</p>
            </a>
        </li>

        <!-- Invoice Payments -->
        @can('view-invoice-payment')
            <li class="nav-item">
                <a href="{{ route('invoices.payments.dashboard') }}"
                    class="nav-link {{ request()->routeIs('invoices.payments.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Invoice Payments</p>
                </a>
            </li>
        @endcan

        <!-- SAP Update -->
        @can('view-sap-update')
            <li class="nav-item">
                <a href="{{ route('invoices.sap-update.index') }}"
                    class="nav-link {{ request()->routeIs('invoices.sap-update.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>SAP Update</p>
                </a>
            </li>
        @endcan
    </ul>
</li>

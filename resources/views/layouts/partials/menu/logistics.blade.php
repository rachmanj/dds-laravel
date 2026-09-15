<!-- Logistik -->
<li class="nav-item {{ request()->routeIs('logistics.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('logistics.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-truck"></i>
        <p>
            Logistik
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('logistics.inventory.index') }}"
                class="nav-link {{ request()->routeIs('logistics.inventory.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Ringkasan Inventory</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('logistics.grpo.index') }}"
                class="nav-link {{ request()->routeIs('logistics.grpo.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>GRPO</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('logistics.usage.index') }}"
                class="nav-link {{ request()->routeIs('logistics.usage.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Pemakaian</p>
            </a>
        </li>
        @can('manage-logistics-category-map')
            <li class="nav-item">
                <a href="{{ route('logistics.categories.index') }}"
                    class="nav-link {{ request()->routeIs('logistics.categories.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Kategori Item</p>
                </a>
            </li>
        @endcan
    </ul>
</li>

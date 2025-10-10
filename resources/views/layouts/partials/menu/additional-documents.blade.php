<!-- Additional Documents -->
<li class="nav-item {{ request()->routeIs('additional-documents.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('additional-documents.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-file-alt"></i>
        <p>
            Additional Documents
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <!-- Dashboard -->
        <li class="nav-item">
            <a href="{{ route('additional-documents.dashboard') }}"
                class="nav-link {{ request()->routeIs('additional-documents.dashboard') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Dashboard</p>
            </a>
        </li>

        <!-- List Documents -->
        <li class="nav-item">
            <a href="{{ route('additional-documents.index') }}"
                class="nav-link {{ request()->routeIs('additional-documents.index') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>List Documents</p>
            </a>
        </li>

        <!-- Create New Document -->
        <li class="nav-item">
            <a href="{{ route('additional-documents.create') }}"
                class="nav-link {{ request()->routeIs('additional-documents.create') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Create New Document</p>
            </a>
        </li>

        <!-- Import Documents -->
        @if (auth()->user()->can('import-additional-documents') || auth()->user()->can('import-general-documents'))
            <li class="nav-item">
                @if (auth()->user()->can('import-additional-documents'))
                    <a href="{{ route('additional-documents.import') }}"
                        class="nav-link {{ request()->routeIs('additional-documents.import*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Import Documents</p>
                    </a>
                @else
                    <a href="{{ route('additional-documents.import-general') }}"
                        class="nav-link {{ request()->routeIs('additional-documents.import*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Import Documents</p>
                    </a>
                @endif
            </li>
        @endif
    </ul>
</li>

<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div>
            <img src="{{ asset('assets/images/logo-icon.png') }}" class="logo-icon" alt="logo icon">
        </div>
        <div>
            <h4 class="logo-text">Support Portal</h4>
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i>
        </div>
     </div>
    <!--navigation-->
    <ul class="metismenu" id="menu">
        <li>
            <a href="{{ route('dashboard.index') }}">
                <div class="parent-icon"><i class='bx bx-home-alt'></i>
                </div>
                <div class="menu-title">Dashboard</div>
            </a>
        </li>
        @if (Auth::user()->isAdmin())
        <li>
            <a href="{{ route('administrative.index') }}">
                <div class="parent-icon"><i class='bx bx-cog'></i>
                </div>
                <div class="menu-title">Administrative Tool</div>
            </a>
        </li>
        @endif
        <li>
            <a href="{{ route('reports.index') }}">
                <div class="parent-icon"><i class='bx bx-clipboard'></i>
                </div>
                <div class="menu-title">Reports</div>
            </a>
        </li>
        <li>
            <a href="{{ route('settings') }}">
                <div class="parent-icon"><i class='bx bx-list-ul'></i>
                </div>
                <div class="menu-title">Settings</div>
            </a>
        </li>
        @if (Auth::user()->isAdmin())
        <li>
            <a href="{{ route('messhall') }}">
                <div class="parent-icon"><i class='bx bx-pencil'></i>
                </div>
                <div class="menu-title">Messhall</div>
            </a>
        </li>
        <li>
            <a href="{{ route('trip-ticket') }}">
                <div class="parent-icon"><i class='bx bx-calendar-week'></i>
                </div>
                <div class="menu-title">Trip Ticket</div>
            </a>
        </li>
        @endif
        @if (Auth::user()->isMisMember())
        <li>
            <a href="{{ route('leave-monitoring.index') }}">
                <div class="parent-icon"><i class='bx bx-user-pin'></i>
                </div>
                <div class="menu-title">Leave Monitoring</div>
            </a>
        </li>
        <li>
            <a href="{{ route('network-hosts.index') }}">
                <div class="parent-icon"><i class='bx bx-wifi'></i>
                </div>
                <div class="menu-title">Server Monitoring</div>
            </a>
        </li>
        <li>
            <a href="{{ route('slot-locator.index') }}">
                <div class="parent-icon"><i class='bx bx-package'></i>
                </div>
                <div class="menu-title">Slot Locator</div>
            </a>
        </li>
        @endif
        @if (Auth::user()->canManageInventory())
        <li>
            <a href="{{ route('inventory-items.index') }}">
                <div class="parent-icon"><i class='bx bx-box'></i>
                </div>
                <div class="menu-title">MIS Item Inventory</div>
            </a>
        </li>
        @endif
        <li>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
                @csrf
            </form>
            <a href="javascript:;" onclick="document.getElementById('logout-form').submit();">
                <div class="parent-icon"><i class='bx bx-log-out'></i>
                </div>
                <div class="menu-title">Logout</div>
            </a>
        </li>
    </ul>
    <!--end navigation-->
</div>

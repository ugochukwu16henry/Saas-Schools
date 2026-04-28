{{--Manage Settings--}}
<li class="nav-item">
    <a href="{{ route('settings') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['settings',]) ? 'active' : '' }}"><i class="icon-gear"></i> <span>Settings</span></a>
</li>

{{--Student Transfers--}}
<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['transfers.outbox', 'transfers.inbox', 'transfers.create']) ? 'nav-item-expanded nav-item-open' : '' }}">
    <a href="#" class="nav-link"><i class="icon-switch2"></i> <span>Student Transfers</span></a>

    <ul class="nav nav-group-sub" data-submenu-title="Student Transfers">
        <li class="nav-item">
            <a href="{{ route('transfers.outbox') }}" class="nav-link {{ Route::is('transfers.outbox') ? 'active' : '' }}">Transferred Students</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('transfers.inbox') }}" class="nav-link {{ Route::is('transfers.inbox') ? 'active' : '' }}">Received Students</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('transfers.create') }}" class="nav-link {{ Route::is('transfers.create') ? 'active' : '' }}">New Transfer</a>
        </li>
    </ul>
</li>

{{--Pins--}}
<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['pins.create', 'pins.index']) ? 'nav-item-expanded nav-item-open' : '' }} ">
    <a href="#" class="nav-link"><i class="icon-lock2"></i> <span> Pins</span></a>

    <ul class="nav nav-group-sub" data-submenu-title="Manage Pins">
        {{--Generate Pins--}}
        <li class="nav-item">
            <a href="{{ route('pins.create') }}"
                class="nav-link {{ (Route::is('pins.create')) ? 'active' : '' }}">Generate Pins</a>
        </li>

        {{-- Valid/Invalid Pins  --}}
        <li class="nav-item">
            <a href="{{ route('pins.index') }}"
                class="nav-link {{ (Route::is('pins.index')) ? 'active' : '' }}">View Pins</a>
        </li>
    </ul>
</li>
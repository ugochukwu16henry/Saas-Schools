<div class="navbar navbar-expand-md navbar-dark" style="background-color:#273246;">
    <div class="navbar-brand mt-1 mr-4" style="min-width:0; max-width:60vw;">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center" style="gap:8px; text-decoration:none; min-width:0;">
            @php
            $schoolName = trim((string) ($currentSchool->name ?? ''));
            if ($schoolName === '') {
                $schoolName = Qs::getSystemName();
            }
            if ($schoolName === '') {
                $schoolName = config('app.name', 'School Dashboard');
            }

            $schoolLogo = !empty($currentSchool->logo ?? null) ? $currentSchool->logo : Qs::getSetting('logo');
            $schoolLogo = is_string($schoolLogo) ? trim($schoolLogo) : '';

            if ($schoolLogo !== '' && !preg_match('/^https?:\/\//i', $schoolLogo)) {
                if (strpos($schoolLogo, '/storage/') === 0 || strpos($schoolLogo, '/global_assets/') === 0) {
                    $schoolLogo = asset(ltrim($schoolLogo, '/'));
                } elseif (strpos($schoolLogo, 'storage/') === 0 || strpos($schoolLogo, 'global_assets/') === 0) {
                    $schoolLogo = asset($schoolLogo);
                } elseif (strpos($schoolLogo, 'uploads/') === 0) {
                    $schoolLogo = asset('storage/' . $schoolLogo);
                } else {
                    $schoolLogo = asset(ltrim($schoolLogo, '/'));
                }
            }

            if ($schoolLogo === '') {
                $schoolLogo = asset('global_assets/images/riseflow-logo.png');
            }
            @endphp
            <img src="{{ $schoolLogo }}" alt="{{ $schoolName ?: 'School' }}" style="height:40px; width:auto; object-fit:contain; max-width:140px;">
            <span class="font-weight-semibold" style="line-height:1.1; color:#ffffff !important; display:inline-block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:42vw;">{{ $schoolName }}</span>
        </a>
    </div>

    <div class="d-md-none">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <i class="icon-tree5"></i>
        </button>
        <button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
            <i class="icon-paragraph-justify3"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block">
                    <i class="icon-paragraph-justify3"></i>
                </a>
            </li>


        </ul>

        <span class="navbar-text ml-md-3 mr-md-auto"></span>
        <ul class="navbar-nav">
            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
                    <img style="width: 38px; height:38px;" src="{{ Auth::user()->photo }}" class="rounded-circle" alt="photo">
                    <span>{{ Auth::user()->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ Qs::userIsStudent() ? route('students.show', Qs::hash(Qs::findStudentRecord(Auth::user()->id)->id)) : route('users.show', Qs::hash(Auth::user()->id)) }}" class="dropdown-item"><i class="icon-user-plus"></i> My profile</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('my_account') }}" class="dropdown-item"><i class="icon-cog5"></i> Account settings</a>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault();
          document.getElementById('logout-form').submit();" class="dropdown-item"><i class="icon-switch2"></i> Logout</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
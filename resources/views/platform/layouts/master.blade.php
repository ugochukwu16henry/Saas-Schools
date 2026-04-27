<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta id="csrf-token" name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page_title') | Platform Admin</title>

    @include('partials.inc_top')
</head>

<body>
    <div class="navbar navbar-expand-md navbar-dark" style="background:#1e293b;">
        <div class="navbar-brand wmin-200">
            <a href="{{ route('platform.dashboard') }}" class="d-inline-flex align-items-center text-white" style="gap:8px; text-decoration:none;">
                <img src="{{ asset('global_assets/images/riseflow-logo.png') }}" alt="RiseFlow" style="height:32px; width:auto; object-fit:contain;">
                <span class="font-weight-semibold">RiseFlow Platform</span>
            </a>
        </div>

        <div class="d-md-none">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#platform-navbar">
                <i class="icon-tree5"></i>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="platform-navbar">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a href="{{ route('platform.dashboard') }}" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('platform.revenue') }}" class="nav-link">Revenue</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('platform.usage.index') }}" class="nav-link">Usage</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('platform.notifications.index') }}" class="nav-link">Notifications</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('platform.webhooks.index') }}" class="nav-link">Webhooks</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('platform.affiliates.index') }}" class="nav-link">Affiliates</a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('platform.logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-light" type="submit">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <div class="page-content">
        <div class="content-wrapper">
            <div class="content pt-4">
                @if (session('status'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    {{ session('status') }}
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    @include('partials.inc_bottom')
    @yield('script')
</body>

</html>
@extends('layouts.login_master')

@section('content')
<div class="page-content auth-stage">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center py-4">
            <div class="saas-auth-shell">

                <aside class="saas-auth-side platform-side">
                    <div class="kicker">Platform Owner Access</div>
                    <h1>Operate every school account from one control center.</h1>
                    <p>Monitor registrations, billing state, school activity, and account health from the platform dashboard.</p>

                    <ul class="value-list">
                        <li>Central visibility across all schools</li>
                        <li>Suspend, activate, and delete tenant accounts</li>
                        <li>Secure owner-only authentication flow</li>
                    </ul>

                    <div class="trust-chip">Restricted to platform operators only</div>
                </aside>

                <section class="saas-auth-card-wrap">
                    <form class="saas-auth-card" method="post" action="{{ route('platform.login.post') }}">
                        @csrf

                        <div class="text-center mb-3">
                            <i class="icon-shield-check icon-2x"></i>
                            <h2>Platform admin sign in</h2>
                            <p class="muted">Use your owner credentials to continue.</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger alert-styled-left alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                <span class="font-weight-semibold">Please check the form:</span>
                                <ul class="mt-1 mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="alert alert-success alert-styled-left alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="font-weight-semibold">Email address</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="owner@company.com" required>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Password</label>
                            <input required name="password" type="password" class="form-control" placeholder="Your password">
                        </div>

                        <div class="form-group d-flex align-items-center">
                            <div class="form-check mb-0">
                                <label class="form-check-label">
                                    <input type="checkbox" name="remember" class="form-input-styled" data-fouc>
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block saas-btn-primary">
                                Sign in <i class="icon-circle-right2 ml-2"></i>
                            </button>
                        </div>

                        <div class="auth-links text-center">
                            <div>School user? <a href="{{ route('login') }}">Back to school login</a></div>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </div>
</div>
@endsection

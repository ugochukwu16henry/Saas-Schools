@extends('layouts.login_master')

@section('content')
<div class="page-content auth-stage">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center py-4">
            <div class="saas-auth-shell">

                <aside class="saas-auth-side">
                    <div class="kicker">School Management SaaS</div>
                    <h1>Run your school with clarity, speed, and confidence.</h1>
                    <p>Manage students, teachers, fees, results, and operations from one secure platform.</p>

                    <ul class="value-list">
                        <li>Multi-school SaaS architecture</li>
                        <li>Paystack-ready billing for Nigeria</li>
                        <li>Secure and role-based access</li>
                    </ul>

                    <div class="trust-chip">Trusted by growing schools and administrators</div>
                </aside>

                <section class="saas-auth-card-wrap">
                    <form class="saas-auth-card" method="post" action="{{ route('login') }}">
                        @csrf

                        <div class="text-center mb-3">
                            <i class="icon-people icon-2x"></i>
                            <h2>Sign in</h2>
                            <p class="muted">Welcome back. Continue to your dashboard.</p>
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
                            <label class="font-weight-semibold">Email or username</label>
                            <input type="text" class="form-control" name="identity" value="{{ old('identity') }}" placeholder="name@school.com or username" required>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Your password" required>
                        </div>

                        <div class="form-group d-flex align-items-center">
                            <div class="form-check mb-0">
                                <label class="form-check-label">
                                    <input type="checkbox" name="remember" class="form-input-styled" {{ old('remember') ? 'checked' : '' }} data-fouc>
                                    Remember me
                                </label>
                            </div>
                            <a href="{{ route('password.request') }}" class="ml-auto">Forgot password?</a>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block saas-btn-primary">Sign in <i class="icon-circle-right2 ml-2"></i></button>
                        </div>

                        <div class="auth-links text-center">
                            <div>New school? <a href="{{ route('school.register') }}">Register your school</a></div>
                            <div class="mt-1">Platform owner? <a href="{{ route('platform.login') }}">Platform admin login</a></div>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </div>
</div>
@endsection

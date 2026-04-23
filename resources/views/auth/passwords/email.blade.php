@extends('layouts.login_master')

@section('content')
<div class="page-content auth-stage">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center py-4">
            <div class="saas-auth-shell">

                <aside class="saas-auth-side">
                    <div class="kicker">Account Recovery</div>
                    <h1>Securely recover access to your dashboard.</h1>
                    <p>Enter your account email and we will send a recovery link so you can set a new password quickly.</p>

                    <ul class="value-list">
                        <li>Fast reset link delivery</li>
                        <li>Protected and time-limited token</li>
                        <li>Works across all school accounts</li>
                    </ul>

                    <div class="trust-chip">Need help? Contact support@riseflow.com</div>
                </aside>

                <section class="saas-auth-card-wrap">
                    <form class="saas-auth-card" method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="text-center mb-3">
                            <i class="icon-key icon-2x"></i>
                            <h2>Password recovery</h2>
                            <p class="muted">We will send password reset instructions to your email.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success alert-styled-left alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('email'))
                            <div class="alert alert-danger alert-styled-left alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                {{ $errors->first('email') }}
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="font-weight-semibold">Your email</label>
                            <input name="email" required type="email" class="form-control" value="{{ old('email') }}" placeholder="name@school.com">
                        </div>

                        <div class="form-group mb-2">
                            <button type="submit" class="btn btn-block saas-btn-primary">
                                Send recovery link <i class="icon-circle-right2 ml-2"></i>
                            </button>
                        </div>

                        <div class="auth-links text-center mt-2">
                            <a href="{{ route('login') }}">Back to sign in</a>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </div>
</div>
@endsection

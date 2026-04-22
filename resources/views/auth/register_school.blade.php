@extends('layouts.login_master')

@section('content')
<div class="page-content auth-stage">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center py-4">
            <div class="saas-auth-shell">

                <aside class="saas-auth-side">
                    <div class="kicker">Start Free</div>
                    <h1>Launch your school portal in minutes.</h1>
                    <p>First 50 students are free. Add your school details and create your administrator account.</p>

                    <ol class="step-list">
                        <li>Create account</li>
                        <li>Set school profile</li>
                        <li>Start onboarding</li>
                    </ol>

                    <div class="trust-chip">No credit card required for trial</div>
                </aside>

                <section class="saas-auth-card-wrap">
                    <form class="saas-auth-card" method="POST" action="{{ route('school.register.store') }}">
                        @csrf

                        <div class="text-center mb-3">
                            <i class="icon-home icon-2x"></i>
                            <h2>Register your school</h2>
                            <p class="muted">Create your school workspace and admin login.</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger alert-styled-left alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                <span class="font-weight-semibold">Please fix the following:</span>
                                <ul class="mt-1 mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="font-weight-semibold">School name</label>
                            <input type="text" name="school_name" value="{{ old('school_name') }}" required class="form-control" placeholder="Greenfield Academy">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Your full name</label>
                            <input type="text" name="your_name" value="{{ old('your_name') }}" required class="form-control" placeholder="Owner or lead admin">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Email address</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="form-control" placeholder="admin@yourschool.com">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Password</label>
                            <input type="password" name="password" required class="form-control" placeholder="Minimum 8 characters">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Confirm password</label>
                            <input type="password" name="password_confirmation" required class="form-control" placeholder="Repeat password">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block saas-btn-primary">Create school account <i class="icon-circle-right2 ml-2"></i></button>
                        </div>

                        <p class="small text-muted text-center mb-2">By continuing, you agree to our terms and privacy policy.</p>
                        <div class="text-center text-muted">Already registered? <a href="{{ route('login') }}">Log in here</a></div>
                    </form>
                </section>

            </div>
        </div>
    </div>
</div>
@endsection

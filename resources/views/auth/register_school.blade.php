@extends('layouts.login_master')

@section('content')
<div class="page-content login-cover">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center">

            <form class="login-form" style="width:480px;" method="POST" action="{{ route('school.register.store') }}">
                @csrf
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="icon-home icon-2x text-success-400 border-success-400 border-3 rounded-round p-3 mb-3 mt-1"></i>
                            <h5 class="mb-0">Register Your School</h5>
                            <span class="d-block text-muted">First 50 students are free. No credit card required.</span>
                        </div>

                        @if ($errors->any())
                        <div class="alert alert-danger alert-styled-left alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            <span class="font-weight-semibold">Please fix the errors below:</span>
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
                            <label class="font-weight-semibold">School Name <span class="text-danger">*</span></label>
                            <input type="text" name="school_name" value="{{ old('school_name') }}" required
                                   class="form-control" placeholder="e.g. Greenfield Academy">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Your Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="your_name" value="{{ old('your_name') }}" required
                                   class="form-control" placeholder="School owner / administrator name">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="form-control" placeholder="admin@yourschool.com">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" required
                                   class="form-control" placeholder="Minimum 8 characters">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" required
                                   class="form-control" placeholder="Repeat password">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-block">
                                Register School &amp; Get Started <i class="icon-circle-right2 ml-2"></i>
                            </button>
                        </div>

                        <div class="text-center text-muted">
                            Already registered? <a href="{{ route('login') }}">Log in here</a>
                        </div>

                        <hr class="my-3">
                        <div class="row text-center text-muted small">
                            <div class="col-4">
                                <i class="icon-users2 d-block mb-1 text-success"></i>
                                First 50 students free
                            </div>
                            <div class="col-4">
                                <i class="icon-lock2 d-block mb-1 text-success"></i>
                                Secure &amp; private
                            </div>
                            <div class="col-4">
                                <i class="icon-clock3 d-block mb-1 text-success"></i>
                                Live in 5 minutes
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

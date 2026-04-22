@extends('layouts.login_master')

@section('content')
<div class="page-content login-cover">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center">

            <form class="login-form" method="post" action="{{ route('platform.login.post') }}">
                @csrf
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="icon-shield-check icon-2x text-primary-400 border-primary-400 border-3 rounded-round p-3 mb-3 mt-1"></i>
                            <h5 class="mb-0">Platform Admin Login</h5>
                            <span class="d-block text-muted">App owner and operations dashboard</span>
                        </div>

                        @if ($errors->any())
                        <div class="alert alert-danger alert-styled-left alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                        @endif

                        @if (session('status'))
                        <div class="alert alert-success alert-styled-left alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            {{ session('status') }}
                        </div>
                        @endif

                        <div class="form-group">
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Email" required>
                        </div>

                        <div class="form-group">
                            <input required name="password" type="password" class="form-control" placeholder="Password">
                        </div>

                        <div class="form-group d-flex align-items-center">
                            <div class="form-check mb-0">
                                <label class="form-check-label">
                                    <input type="checkbox" name="remember" class="form-input-styled" data-fouc>
                                    Remember
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">Sign in <i class="icon-circle-right2 ml-2"></i></button>
                        </div>

                        <div class="text-center text-muted">
                            School user? <a href="{{ route('login') }}">Back to school login</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

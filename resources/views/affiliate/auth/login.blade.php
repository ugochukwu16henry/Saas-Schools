@extends('layouts.login_master')

@section('content')
<div class="page-content auth-stage">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center py-4">
            <div class="saas-auth-shell" style="max-width:520px;">
                <section class="saas-auth-card-wrap w-100">
                    <form class="saas-auth-card" method="POST" action="{{ route('affiliate.login.post') }}">
                        @csrf
                        <div class="text-center mb-3">
                            <h2>Affiliate sign in</h2>
                            <p class="muted">Approved RiseFlow affiliates only.</p>
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
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <div class="form-group">
                            <label class="font-weight-semibold">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="form-control" autocomplete="username">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">Password</label>
                            <input type="password" name="password" required class="form-control" autocomplete="current-password">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block saas-btn-primary">Sign in</button>
                        </div>

                        <p class="small text-muted text-center mb-0">
                            Need an account? <a href="{{ route('affiliates.request') }}">Apply to the RiseFlow Affiliate Program</a>
                        </p>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection

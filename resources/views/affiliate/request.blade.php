@extends('layouts.login_master')

@section('content')
<div class="page-content auth-stage">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center py-4">
            <div class="saas-auth-shell">
                <aside class="saas-auth-side d-none d-md-block">
                    <div class="kicker">RiseFlow Affiliate Program</div>
                    <h1>Refer schools. Earn when they grow.</h1>
                    <p>Share your referral link with school leaders. When their school pays successfully through Paystack, you earn from billable students according to the published rates.</p>
                    <ul class="step-list">
                        <li>Submit your details (optional KYC photo)</li>
                        <li>Platform admin reviews and approves</li>
                        <li>Sign in, copy your link, and start referring</li>
                    </ul>
                    <div class="trust-chip">Commissions accrue on successful school payments only</div>
                </aside>

                <section class="saas-auth-card-wrap">
                    <form class="saas-auth-card" method="POST" action="{{ route('affiliates.request.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="text-center mb-3">
                            <h2>Request affiliate access</h2>
                            <p class="muted">We will email you at the address below once you are approved.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-styled-left alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="font-weight-semibold">Full name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Country (optional)</label>
                            <input type="text" name="country" value="{{ old('country') }}" maxlength="10" class="form-control" placeholder="NG">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Why you would like to join (optional)</label>
                            <textarea name="bio" rows="3" class="form-control" placeholder="Short note for reviewers">{{ old('bio') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">KYC / profile photo (optional)</label>
                            <input type="file" name="photo" accept="image/*" class="form-control">
                            <span class="form-text text-muted">JPEG or PNG, up to 2&nbsp;MB.</span>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Choose a password</label>
                            <input type="password" name="password" required class="form-control" minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Confirm password</label>
                            <input type="password" name="password_confirmation" required class="form-control" autocomplete="new-password">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block saas-btn-primary">Submit application</button>
                        </div>

                        <p class="small text-muted text-center mb-0">
                            Already approved? <a href="{{ route('affiliate.login') }}">Affiliate sign in</a>
                        </p>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection

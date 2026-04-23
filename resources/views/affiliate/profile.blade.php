@extends('layouts.login_master')

@section('content')
<div class="page-content">
    <div class="content-wrapper">
        <div class="content py-4">
            <div class="container" style="max-width:640px;">
                <div class="mb-3">
                    <a href="{{ route('affiliate.dashboard') }}" class="btn btn-link pl-0">&larr; Back to dashboard</a>
                </div>
                <h1 class="h3 mb-3">Payout profile</h1>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if ($affiliate->photo_path)
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Current photo</div>
                        <img src="{{ asset('storage/'.$affiliate->photo_path) }}" alt="Profile" class="img-thumbnail" style="max-height:180px;">
                    </div>
                @endif

                <form method="POST" action="{{ route('affiliate.profile.update') }}" enctype="multipart/form-data" class="card card-body">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="font-weight-semibold">Bank name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Account name</label>
                        <input type="text" name="account_name" value="{{ old('account_name', $affiliate->account_name) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Account number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $affiliate->account_number) }}" class="form-control" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Replace photo (optional)</label>
                        <input type="file" name="photo" accept="image/*" class="form-control">
                    </div>
                    <hr>
                    <p class="text-muted small">Leave blank to keep your current password.</p>
                    <div class="form-group">
                        <label class="font-weight-semibold">New password</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Confirm new password</label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn saas-btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

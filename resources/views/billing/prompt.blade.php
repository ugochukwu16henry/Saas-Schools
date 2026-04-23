@extends('layouts.login_master')

@section('content')
<div class="page-content login-cover">
    <div class="content-wrapper">
        <div class="content d-flex justify-content-center align-items-center">

            <div class="login-form" style="width:520px;">
                <div class="card mb-0 border-warning">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="icon-credit-card icon-2x text-warning-400 border-warning-400 border-3 rounded-round p-3 mb-3 mt-1"></i>
                            <h5 class="mb-0 text-warning">Subscription Required</h5>
                            <span class="d-block text-muted">First {{ number_format($school->free_student_limit) }} students are free for life. Billing applies only above that.</span>
                        </div>

                        @if ($errors->any())
                        <div class="alert alert-danger alert-styled-left alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                        @endif

                        <div class="alert alert-info alert-styled-left mb-3">
                            <strong>Billing Summary for {{ $school->name }}</strong>
                            <hr class="my-2">
                            <div class="row">
                                <div class="col-7">Total enrolled students</div>
                                <div class="col-5 text-right font-weight-semibold">{{ number_format($studentCount) }}</div>
                            </div>
                            <div class="row">
                                <div class="col-7">Free student allocation</div>
                                <div class="col-5 text-right font-weight-semibold">{{ number_format($school->free_student_limit) }}</div>
                            </div>
                            <div class="row">
                                <div class="col-7">Billable students</div>
                                <div class="col-5 text-right font-weight-semibold">{{ number_format($billableCount) }}</div>
                            </div>
                            <div class="row">
                                <div class="col-7">Rate</div>
                                <div class="col-5 text-right font-weight-semibold">₦{{ number_format($monthlyRate) }} / student / month</div>
                            </div>
                            <div class="row">
                                <div class="col-7">One-time add rate (new students above free limit)</div>
                                <div class="col-5 text-right font-weight-semibold">₦{{ number_format($oneTimeRate) }} / student</div>
                            </div>
                            <div class="row">
                                <div class="col-7">Already charged one-time for</div>
                                <div class="col-5 text-right font-weight-semibold">{{ number_format($alreadyPaidOneTime) }} student(s)</div>
                            </div>
                            <div class="row">
                                <div class="col-7">New students to charge one-time now</div>
                                <div class="col-5 text-right font-weight-semibold">{{ number_format($newlyAddedCount) }} student(s)</div>
                            </div>
                            <hr class="my-2">
                            <div class="row text-dark">
                                <div class="col-7">Monthly charge</div>
                                <div class="col-5 text-right">₦{{ number_format($monthlyAmount) }}</div>
                            </div>
                            <div class="row text-dark">
                                <div class="col-7">One-time charge</div>
                                <div class="col-5 text-right">₦{{ number_format($oneTimeAmount) }}</div>
                            </div>
                            <div class="row font-weight-bold text-dark">
                                <div class="col-7">Total due now</div>
                                <div class="col-5 text-right">₦{{ number_format($totalDue) }}</div>
                            </div>
                        </div>

                        <p class="text-muted small mb-3">
                            Payment is processed securely via <strong>Paystack</strong>. You will be redirected to complete payment and then returned here automatically.
                        </p>

                        <a href="{{ route('billing.initialize') }}" class="btn btn-warning btn-block font-weight-semibold">
                            Pay ₦{{ number_format($totalDue) }} Now &nbsp;<i class="icon-arrow-right14"></i>
                        </a>

                        <div class="text-center mt-3">
                            <a href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="text-muted small">
                                Log out
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

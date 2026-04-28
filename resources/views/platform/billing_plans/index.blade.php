@extends('platform.layouts.master')

@section('page_title', 'Billing Plans')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">Create Billing Plan</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('platform.billing_plans.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-semibold">Plan Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Monthly Rate per Billable Student (₦)</label>
                        <input type="number" name="monthly_rate_per_student" class="form-control" value="{{ old('monthly_rate_per_student', 500) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">One-time Upload Rate (₦)</label>
                        <input type="number" name="one_time_add_rate" class="form-control" value="{{ old('one_time_add_rate', 1000) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Affiliate One-time Commission per New Billable Student (₦)</label>
                        <input type="number" name="affiliate_one_time_commission_per_student" class="form-control" value="{{ old('affiliate_one_time_commission_per_student', 200) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Affiliate Monthly Commission per Billable Student (₦)</label>
                        <input type="number" name="affiliate_monthly_commission_per_student" class="form-control" value="{{ old('affiliate_monthly_commission_per_student', 100) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Default Free Student Limit</label>
                        <input type="number" name="default_free_student_limit" class="form-control" value="{{ old('default_free_student_limit', 50) }}" min="0" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Set as default plan</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Plan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">Existing Billing Plans</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th class="text-right">Monthly ₦</th>
                            <th class="text-right">One-time ₦</th>
                            <th class="text-right">Affiliate Upload ₦</th>
                            <th class="text-right">Affiliate Monthly ₦</th>
                            <th class="text-right">Free Limit</th>
                            <th class="text-center">Schools</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $plan->name }}</div>
                                @if($plan->is_default)
                                <span class="badge badge-success">Default</span>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($plan->monthly_rate_per_student) }}</td>
                            <td class="text-right">{{ number_format($plan->one_time_add_rate) }}</td>
                            <td class="text-right">{{ number_format($plan->affiliate_one_time_commission_per_student) }}</td>
                            <td class="text-right">{{ number_format($plan->affiliate_monthly_commission_per_student) }}</td>
                            <td class="text-right">{{ number_format($plan->default_free_student_limit) }}</td>
                            <td class="text-center">{{ number_format($plan->schools_count) }}</td>
                            <td>
                                @if($plan->is_active)
                                <span class="badge badge-primary">Active</span>
                                @else
                                <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="bg-light">
                                <form method="POST" action="{{ route('platform.billing_plans.update', $plan) }}" class="form-inline" style="gap:8px;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $plan->name }}" style="width:160px;" required>
                                    <input type="number" name="monthly_rate_per_student" class="form-control form-control-sm" value="{{ $plan->monthly_rate_per_student }}" min="0" style="width:120px;" required>
                                    <input type="number" name="one_time_add_rate" class="form-control form-control-sm" value="{{ $plan->one_time_add_rate }}" min="0" style="width:120px;" required>
                                    <input type="number" name="affiliate_one_time_commission_per_student" class="form-control form-control-sm" value="{{ $plan->affiliate_one_time_commission_per_student }}" min="0" style="width:120px;" required>
                                    <input type="number" name="affiliate_monthly_commission_per_student" class="form-control form-control-sm" value="{{ $plan->affiliate_monthly_commission_per_student }}" min="0" style="width:120px;" required>
                                    <input type="number" name="default_free_student_limit" class="form-control form-control-sm" value="{{ $plan->default_free_student_limit }}" min="0" style="width:120px;" required>
                                    <label class="mb-0"><input type="checkbox" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }}> Active</label>
                                    <label class="mb-0"><input type="checkbox" name="is_default" value="1" {{ $plan->is_default ? 'checked' : '' }}> Default</label>
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted p-3">No billing plans yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
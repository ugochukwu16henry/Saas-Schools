<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:platform');
    }

    public function index()
    {
        $plans = BillingPlan::query()
            ->withCount('schools')
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('platform.billing_plans.index', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:billing_plans,name'],
            'monthly_rate_per_student' => ['required', 'integer', 'min:0', 'max:1000000'],
            'one_time_add_rate' => ['required', 'integer', 'min:0', 'max:1000000'],
            'default_free_student_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                BillingPlan::query()->update(['is_default' => false]);
            }

            BillingPlan::create([
                'name' => $data['name'],
                'monthly_rate_per_student' => (int) $data['monthly_rate_per_student'],
                'one_time_add_rate' => (int) $data['one_time_add_rate'],
                'default_free_student_limit' => (int) $data['default_free_student_limit'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);
        });

        return back()->with('status', 'Billing plan created successfully.');
    }

    public function update(Request $request, BillingPlan $billingPlan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:billing_plans,name,' . $billingPlan->id],
            'monthly_rate_per_student' => ['required', 'integer', 'min:0', 'max:1000000'],
            'one_time_add_rate' => ['required', 'integer', 'min:0', 'max:1000000'],
            'default_free_student_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($billingPlan, $data) {
            if (! empty($data['is_default'])) {
                BillingPlan::query()->where('id', '!=', $billingPlan->id)->update(['is_default' => false]);
            }

            $billingPlan->update([
                'name' => $data['name'],
                'monthly_rate_per_student' => (int) $data['monthly_rate_per_student'],
                'one_time_add_rate' => (int) $data['one_time_add_rate'],
                'default_free_student_limit' => (int) $data['default_free_student_limit'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);
        });

        return back()->with('status', 'Billing plan updated successfully.');
    }
}

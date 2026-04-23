<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RequestAffiliateController extends Controller
{
    public function create()
    {
        return view('affiliate.request');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:affiliates,email',
            'phone' => 'required|string|max:30',
            'country' => 'nullable|string|max:10',
            'bio' => 'nullable|string|max:2000',
            'password' => 'required|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('affiliate-applications', 'public');
        }

        Affiliate::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'country' => $validated['country'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
            'photo_path' => $path,
        ]);

        return redirect()->route('affiliates.request')
            ->with('status', 'Thanks! Your application is pending review. You will be able to sign in once a platform admin approves your account.');
    }
}

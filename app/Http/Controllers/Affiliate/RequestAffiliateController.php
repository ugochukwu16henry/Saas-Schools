<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RequestAffiliateController extends Controller
{
    public function create()
    {
        return view('affiliate.request');
    }

    public function store(Request $request)
    {
        // Validate text fields using input() to avoid touching the files bag,
        // which crashes when a stray string arrives in the 'photo' POST key
        // (UploadedFile::createFromBase receives a string instead of a SymfonyUploadedFile).
        $textValidator = Validator::make($request->input(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:affiliates,email',
            'phone'    => 'required|string|max:30',
            'country'  => 'nullable|string|max:10',
            'bio'      => 'nullable|string|max:2000',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $textValidator->validate();
        $validated = $textValidator->validated();

        // Resolve the photo safely from the files bag only.
        $path = null;
        $photoFile = $request->files->get('photo');
        if ($photoFile instanceof UploadedFile && $photoFile->isValid()) {
            $fileValidator = Validator::make(
                ['photo' => $photoFile],
                ['photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048']
            );
            $fileValidator->validate();
            $path = $photoFile->store('affiliate-applications', 'public');
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

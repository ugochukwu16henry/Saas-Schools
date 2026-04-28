<?php

Auth::routes();

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    $pricing = [
        'freeLimit' => \App\Models\BillingPlan::DEFAULT_FREE_STUDENT_LIMIT,
        'monthlyRate' => \App\Models\BillingPlan::DEFAULT_MONTHLY_RATE_PER_STUDENT,
        'oneTimeRate' => \App\Models\BillingPlan::DEFAULT_ONE_TIME_ADD_RATE,
        'affiliateOneTimeRate' => \App\Models\BillingPlan::DEFAULT_AFFILIATE_ONE_TIME_COMMISSION_NGN,
        'affiliateMonthlyRate' => \App\Models\BillingPlan::DEFAULT_AFFILIATE_MONTHLY_COMMISSION_NGN,
    ];

    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('billing_plans')) {
            $defaultPlan = \App\Models\BillingPlan::defaultActive();

            if ($defaultPlan) {
                $pricing['freeLimit'] = (int) $defaultPlan->default_free_student_limit;
                $pricing['monthlyRate'] = (int) $defaultPlan->monthly_rate_per_student;
                $pricing['oneTimeRate'] = (int) $defaultPlan->one_time_add_rate;
                $pricing['affiliateOneTimeRate'] = (int) $defaultPlan->affiliate_one_time_commission_per_student;
                $pricing['affiliateMonthlyRate'] = (int) $defaultPlan->affiliate_monthly_commission_per_student;
            }
        }
    } catch (\Throwable $e) {
        // Keep fallbacks during early boot/migration windows.
    }

    return view('marketing.home', $pricing);
})->name('landing');

Route::get('/healthz', function () {
    $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
    $sessionDomain = (string) config('session.domain');

    $sessionDomainOk = true;
    if ($appHost && $sessionDomain !== '') {
        $normalizedSessionDomain = ltrim(trim($sessionDomain), '.');
        $sessionDomainOk = $appHost === $normalizedSessionDomain
            || \Illuminate\Support\Str::endsWith($appHost, '.' . $normalizedSessionDomain);
    }

    return response()->json([
        'status' => 'ok',
        'checks' => [
            'session_domain_ok' => $sessionDomainOk,
        ],
    ], 200);
})->name('healthz');

// Fallback for environments where public/storage symlink is missing.
Route::get('/storage/{path}', function ($path) {
    $storageRoot = realpath(storage_path('app/public'));
    $target = storage_path('app/public/' . ltrim((string) $path, '/'));
    $resolved = realpath($target);
    $isWindows = DIRECTORY_SEPARATOR === '\\';

    $normalize = static function (?string $value) use ($isWindows): string {
        $normalized = str_replace('\\', '/', (string) $value);
        return $isWindows ? strtolower($normalized) : $normalized;
    };

    $rootNorm = rtrim($normalize($storageRoot), '/');
    $resolvedNorm = $normalize($resolved);
    $insideStorageRoot = $rootNorm !== '' && $resolvedNorm !== '' && strpos($resolvedNorm, $rootNorm . '/') === 0;

    if (
        !$storageRoot ||
        !$resolved ||
        !$insideStorageRoot ||
        !is_file($resolved)
    ) {
        abort(404);
    }

    return response()->file($resolved);
})->where('path', '.*');

//Route::get('/test', 'TestController@index')->name('test');
Route::get('/privacy-policy', 'HomeController@privacy_policy')->name('privacy_policy');
Route::get('/terms-of-use', 'HomeController@terms_of_use')->name('terms_of_use');
Route::get('/verify/student/{token}', 'Public\StudentVerificationController@show')->name('students.verify.public');

// School self-registration (public)
Route::get('/register/school', 'SchoolRegistrationController@create')->name('school.register');
Route::post('/register/school', 'SchoolRegistrationController@store')->name('school.register.store');

Route::get('/r/{code}', 'Affiliate\ReferralRedirectController')->where('code', '[A-Za-z0-9]{4,32}')->name('affiliate.referral_redirect');

Route::middleware('guest:affiliate')->group(function () {
    Route::get('/affiliates/request', 'Affiliate\RequestAffiliateController@create')->name('affiliates.request');
    Route::post('/affiliates/request', 'Affiliate\RequestAffiliateController@store')->name('affiliates.request.store');
});

Route::prefix('affiliate')->group(function () {
    Route::middleware('guest:affiliate')->group(function () {
        Route::get('/login', 'Affiliate\AuthController@showLogin')->name('affiliate.login');
        Route::post('/login', 'Affiliate\AuthController@login')->name('affiliate.login.post');
    });

    Route::middleware('auth:affiliate')->group(function () {
        Route::post('/logout', 'Affiliate\AuthController@logout')->name('affiliate.logout');
        Route::get('/', 'Affiliate\DashboardController@index')->name('affiliate.dashboard');
        Route::get('/profile', 'Affiliate\DashboardController@editProfile')->name('affiliate.profile.edit');
        Route::put('/profile', 'Affiliate\DashboardController@updateProfile')->name('affiliate.profile.update');
    });
});

// Platform admin auth and dashboard
Route::group(['prefix' => 'platform'], function () {
    Route::middleware('guest:platform')->group(function () {
        Route::get('/login', 'Platform\AuthController@showLogin')->name('platform.login');
        Route::post('/login', 'Platform\AuthController@login')->name('platform.login.post');
    });

    Route::middleware('auth:platform')->group(function () {
        Route::post('/logout', 'Platform\AuthController@logout')->name('platform.logout');
        Route::get('/dashboard', 'Platform\DashboardController@index')->name('platform.dashboard');
        Route::get('/notifications', 'Platform\NotificationController@index')->name('platform.notifications.index');
        Route::patch('/notifications/{notification}/read', 'Platform\NotificationController@markRead')->name('platform.notifications.read');
        Route::get('/webhooks', 'Platform\WebhookController@index')->name('platform.webhooks.index');
        Route::post('/webhooks', 'Platform\WebhookController@store')->middleware('ability:platform.webhooks.manage')->name('platform.webhooks.store');
        Route::patch('/webhooks/{webhook}/toggle', 'Platform\WebhookController@toggle')->middleware('ability:platform.webhooks.manage')->name('platform.webhooks.toggle');
        Route::delete('/webhooks/{webhook}', 'Platform\WebhookController@destroy')->middleware('ability:platform.webhooks.manage')->name('platform.webhooks.destroy');
        Route::get('/usage', 'Platform\UsageAnalyticsController@index')->name('platform.usage.index');
        Route::get('/schools/at-risk-contact-gaps', 'Platform\DashboardController@atRiskContactGaps')->name('platform.schools.contact_gaps');
        Route::get('/schools/export', 'Platform\DashboardController@exportSchoolsCsv')->name('platform.schools.export');
        Route::get('/schools/export-at-risk-contacts', 'Platform\DashboardController@exportAtRiskContactsCsv')->name('platform.schools.export_at_risk_contacts');
        Route::get('/affiliates/export', 'Platform\AffiliateAdminController@exportCsv')->name('platform.affiliates.export');
        Route::get('/affiliates', 'Platform\AffiliateAdminController@index')->name('platform.affiliates.index');
        Route::get('/affiliates/{affiliate}', 'Platform\AffiliateAdminController@show')->name('platform.affiliates.show');
        Route::patch('/affiliates/{affiliate}/approve', 'Platform\AffiliateAdminController@approve')->middleware('ability:platform.affiliates.manage')->name('platform.affiliates.approve');
        Route::patch('/affiliates/{affiliate}/suspend', 'Platform\AffiliateAdminController@suspend')->middleware('ability:platform.affiliates.manage')->name('platform.affiliates.suspend');
        Route::post('/affiliates/{affiliate}/payouts', 'Platform\AffiliateAdminController@createPayout')->middleware('ability:platform.affiliates.manage')->name('platform.affiliates.payouts.create');
        Route::patch('/affiliates/{affiliate}/payouts/{payout}/paid', 'Platform\AffiliateAdminController@markPayoutPaid')->middleware('ability:platform.affiliates.manage')->name('platform.affiliates.payouts.paid');
        Route::delete('/affiliates/{affiliate}', 'Platform\AffiliateAdminController@destroy')->middleware('ability:platform.affiliates.manage')->name('platform.affiliates.destroy');
        Route::get('/revenue', 'Platform\RevenueController@index')->name('platform.revenue');
        Route::get('/billing-plans', 'Platform\BillingPlanController@index')->middleware('ability:platform.billing_plans.manage')->name('platform.billing_plans.index');
        Route::post('/billing-plans', 'Platform\BillingPlanController@store')->middleware('ability:platform.billing_plans.manage')->name('platform.billing_plans.store');
        Route::patch('/billing-plans/{billingPlan}', 'Platform\BillingPlanController@update')->middleware('ability:platform.billing_plans.manage')->name('platform.billing_plans.update');
        Route::patch('/schools/{school}/plan', 'Platform\\DashboardController@updatePlan')->middleware('ability:platform.schools.manage')->name('platform.schools.update_plan');
        Route::patch('/schools/{school}/billing-plan', 'Platform\\DashboardController@updateBillingPlan')->middleware('ability:platform.schools.manage')->name('platform.schools.update_billing_plan');
        Route::get('/schools/{school}', 'Platform\DashboardController@show')->name('platform.schools.show');
        Route::patch('/schools/{school}/suspend', 'Platform\DashboardController@suspend')->middleware('ability:platform.schools.manage')->name('platform.schools.suspend');
        Route::patch('/schools/{school}/activate', 'Platform\DashboardController@activate')->middleware('ability:platform.schools.manage')->name('platform.schools.activate');
        Route::delete('/schools/{school}', 'Platform\DashboardController@destroy')->middleware('ability:platform.schools.manage')->name('platform.schools.destroy');
    });
});

// Billing (Paystack)
Route::post('/paystack/webhook', 'Billing\PaystackController@webhook')->name('paystack.webhook');
Route::group(['middleware' => ['auth', 'tenant']], function () {
    Route::get('/billing/prompt',      'Billing\PaystackController@prompt')->name('billing.prompt');
    Route::get('/billing/status',      'Billing\PaystackController@status')->name('billing.status');
    Route::get('/billing/initialize',  'Billing\PaystackController@initialize')->name('billing.initialize');
    Route::get('/billing/callback',    'Billing\PaystackController@callback')->name('billing.callback');
});


Route::group(['middleware' => ['auth', 'tenant', 'subscription']], function () {

    Route::get('/home', 'HomeController@dashboard')->name('home');
    Route::get('/dashboard', 'HomeController@dashboard')->name('dashboard');
    Route::get('/onboarding', 'OnboardingController@index')->name('onboarding.index');
    Route::patch('/onboarding/complete', 'OnboardingController@complete')->name('onboarding.complete');

    Route::group(['prefix' => 'my_account'], function () {
        Route::get('/', 'MyAccountController@edit_profile')->name('my_account');
        Route::put('/', 'MyAccountController@update_profile')->name('my_account.update');
        Route::put('/change_password', 'MyAccountController@change_pass')->name('my_account.change_pass');
    });

    Route::group(['prefix' => 'ai'], function () {
        Route::get('/announcement-draft', 'Ai\AnnouncementPageController@index')->name('ai.announcement.page');
        Route::post('/announcement-draft', 'Ai\AnnouncementController@generate')->name('ai.announcement.generate');
        Route::post('/ops-summary', 'Ai\OpsCopilotController@summarize')->name('ai.ops.summary');
    });

    /*************** Support Team *****************/
    Route::group(['namespace' => 'SupportTeam',], function () {

        /*************** Students *****************/
        Route::group(['prefix' => 'students'], function () {
            Route::get('bulk/template', 'StudentBulkController@downloadTemplate')->name('students.bulk.template');
            Route::get('bulk', 'StudentBulkController@create')->name('students.bulk.create');
            Route::post('bulk', 'StudentBulkController@store')->middleware('ability:school.students.bulk_import')->name('students.bulk.store');

            Route::get('reset_pass/{st_id}', 'StudentRecordController@reset_pass')->name('st.reset_pass');
            Route::get('graduated', 'StudentRecordController@graduated')->name('students.graduated');
            Route::put('not_graduated/{id}', 'StudentRecordController@not_graduated')->name('st.not_graduated');
            Route::get('list/{class_id}', 'StudentRecordController@listByClass')->name('students.list')->middleware('teamSAT');

            /* Promotions */
            Route::post('promote_selector', 'PromotionController@selector')->name('students.promote_selector');
            Route::get('promotion/manage', 'PromotionController@manage')->name('students.promotion_manage');
            Route::delete('promotion/reset/{pid}', 'PromotionController@reset')->name('students.promotion_reset');
            Route::delete('promotion/reset_all', 'PromotionController@reset_all')->name('students.promotion_reset_all');
            Route::get('promotion/{fc?}/{fs?}/{tc?}/{ts?}', 'PromotionController@promotion')->name('students.promotion');
            Route::post('promote/{fc}/{fs}/{tc}/{ts}', 'PromotionController@promote')->name('students.promote');
        });

        /*************** Users *****************/
        Route::group(['prefix' => 'users'], function () {
            Route::get('reset_pass/{id}', 'UserController@reset_pass')->name('users.reset_pass');
        });

        /*************** Student Transfers (Super Admin) *****************/
        Route::group(['prefix' => 'transfers', 'middleware' => 'super_admin'], function () {
            Route::get('/', 'StudentTransferController@outbox')->name('transfers.outbox');
            Route::get('inbox', 'StudentTransferController@inbox')->name('transfers.inbox');
            Route::get('create', 'StudentTransferController@create')->name('transfers.create');
            Route::get('search-school', 'StudentTransferController@searchSchool')->name('transfers.search_school');
            Route::post('/', 'StudentTransferController@store')->name('transfers.store');
            Route::patch('{transfer}/accept', 'StudentTransferController@accept')->name('transfers.accept');
            Route::patch('{transfer}/reject', 'StudentTransferController@reject')->name('transfers.reject');
            Route::delete('{transfer}', 'StudentTransferController@cancel')->name('transfers.cancel');
        });

        /*************** TimeTables *****************/
        Route::group(['prefix' => 'timetables'], function () {
            Route::get('/', 'TimeTableController@index')->name('tt.index');

            Route::group(['middleware' => 'teamSA'], function () {
                Route::post('/', 'TimeTableController@store')->name('tt.store');
                Route::put('/{tt}', 'TimeTableController@update')->name('tt.update');
                Route::delete('/{tt}', 'TimeTableController@delete')->name('tt.delete');
            });

            /*************** TimeTable Records *****************/
            Route::group(['prefix' => 'records'], function () {

                Route::group(['middleware' => 'teamSA'], function () {
                    Route::get('manage/{ttr}', 'TimeTableController@manage')->name('ttr.manage');
                    Route::post('/', 'TimeTableController@store_record')->name('ttr.store');
                    Route::get('edit/{ttr}', 'TimeTableController@edit_record')->name('ttr.edit');
                    Route::put('/{ttr}', 'TimeTableController@update_record')->name('ttr.update');
                });

                Route::get('show/{ttr}', 'TimeTableController@show_record')->name('ttr.show');
                Route::get('print/{ttr}', 'TimeTableController@print_record')->name('ttr.print');
                Route::delete('/{ttr}', 'TimeTableController@delete_record')->name('ttr.destroy');
            });

            /*************** Time Slots *****************/
            Route::group(['prefix' => 'time_slots', 'middleware' => 'teamSA'], function () {
                Route::post('/', 'TimeTableController@store_time_slot')->name('ts.store');
                Route::post('/use/{ttr}', 'TimeTableController@use_time_slot')->name('ts.use');
                Route::get('edit/{ts}', 'TimeTableController@edit_time_slot')->name('ts.edit');
                Route::delete('/{ts}', 'TimeTableController@delete_time_slot')->name('ts.destroy');
                Route::put('/{ts}', 'TimeTableController@update_time_slot')->name('ts.update');
            });
        });

        /*************** Payments *****************/
        Route::group(['prefix' => 'payments'], function () {

            Route::get('manage/{class_id?}', 'PaymentController@manage')->name('payments.manage');
            Route::get('invoice/{id}/{year?}', 'PaymentController@invoice')->name('payments.invoice');
            Route::get('receipts/{id}', 'PaymentController@receipts')->name('payments.receipts');
            Route::get('pdf_receipts/{id}', 'PaymentController@pdf_receipts')->name('payments.pdf_receipts');
            Route::post('select_year', 'PaymentController@select_year')->name('payments.select_year');
            Route::post('select_class', 'PaymentController@select_class')->name('payments.select_class');
            Route::delete('reset_record/{id}', 'PaymentController@reset_record')->name('payments.reset_record');
            Route::post('pay_now/{id}', 'PaymentController@pay_now')->name('payments.pay_now');
        });

        /*************** Pins *****************/
        Route::group(['prefix' => 'pins'], function () {
            Route::get('create', 'PinController@create')->name('pins.create');
            Route::get('/', 'PinController@index')->name('pins.index');
            Route::post('/', 'PinController@store')->name('pins.store');
            Route::get('enter/{id}', 'PinController@enter_pin')->name('pins.enter');
            Route::post('verify/{id}', 'PinController@verify')->name('pins.verify');
            Route::delete('/', 'PinController@destroy')->name('pins.destroy');
        });

        /*************** Marks *****************/
        Route::group(['prefix' => 'marks'], function () {

            // FOR teamSA
            Route::group(['middleware' => 'teamSA'], function () {
                Route::get('batch_fix', 'MarkController@batch_fix')->name('marks.batch_fix');
                Route::put('batch_update', 'MarkController@batch_update')->name('marks.batch_update');
                Route::get('tabulation/{exam?}/{class?}/{sec_id?}', 'MarkController@tabulation')->name('marks.tabulation');
                Route::post('tabulation', 'MarkController@tabulation_select')->name('marks.tabulation_select');
                Route::get('tabulation/print/{exam}/{class}/{sec_id}', 'MarkController@print_tabulation')->name('marks.print_tabulation');
            });

            // FOR teamSAT
            Route::group(['middleware' => 'teamSAT'], function () {
                Route::get('/', 'MarkController@index')->name('marks.index');
                Route::get('manage/{exam}/{class}/{section}/{subject}', 'MarkController@manage')->name('marks.manage');
                Route::put('update/{exam}/{class}/{section}/{subject}', 'MarkController@update')->name('marks.update');
                Route::put('comment_update/{exr_id}', 'MarkController@comment_update')->name('marks.comment_update');
                Route::put('skills_update/{skill}/{exr_id}', 'MarkController@skills_update')->name('marks.skills_update');
                Route::post('selector', 'MarkController@selector')->name('marks.selector');
                Route::get('bulk/{class?}/{section?}', 'MarkController@bulk')->name('marks.bulk');
                Route::post('bulk', 'MarkController@bulk_select')->name('marks.bulk_select');
            });

            Route::get('select_year/{id}', 'MarkController@year_selector')->name('marks.year_selector');
            Route::post('select_year/{id}', 'MarkController@year_selected')->name('marks.year_select');
            Route::get('show/{id}/{year}', 'MarkController@show')->name('marks.show');
            Route::get('print/{id}/{exam_id}/{year}', 'MarkController@print_view')->name('marks.print');
        });

        Route::resource('students', 'StudentRecordController');
        Route::resource('users', 'UserController');
        Route::resource('classes', 'MyClassController');
        Route::resource('sections', 'SectionController');
        Route::resource('subjects', 'SubjectController');
        Route::resource('grades', 'GradeController');
        Route::resource('exams', 'ExamController');
        Route::resource('dorms', 'DormController');
        Route::resource('payments', 'PaymentController');
    });

    /************************ AJAX ****************************/
    Route::group(['prefix' => 'ajax'], function () {
        Route::get('get_lga/{state_id}', 'AjaxController@get_lga')->name('get_lga');
        Route::get('get_class_sections/{class_id}', 'AjaxController@get_class_sections')->name('get_class_sections');
        Route::get('get_class_subjects/{class_id}', 'AjaxController@get_class_subjects')->name('get_class_subjects');
    });
});

/************************ SUPER ADMIN ****************************/
Route::group(['namespace' => 'SuperAdmin', 'middleware' => 'super_admin', 'prefix' => 'super_admin'], function () {

    Route::get('/settings', 'SettingController@index')->name('settings');
    Route::put('/settings', 'SettingController@update')->middleware('ability:school.settings.manage')->name('settings.update');
});

/************************ PARENT ****************************/
Route::group(['namespace' => 'MyParent', 'middleware' => 'my_parent',], function () {

    Route::get('/my_children', 'MyController@children')->name('my_children');
});

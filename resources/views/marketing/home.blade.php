@extends('layouts.login_master')

@section('content')
<div class="page-content marketing-home">
    <div class="content-wrapper">

        <section class="container pt-3">
            <div class="mh-topbar">
                <div class="mh-brand">
                    <img src="{{ asset('global_assets/images/riseflow-logo.png') }}" alt="RiseFlow" style="height:36px; width:auto; object-fit:contain; vertical-align:middle; margin-right:6px;">
                    RiseFlow
                </div>
                <div class="mh-top-links">
                    <a href="#features">Features</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#affiliates">Affiliates</a>
                    <a href="#faq">FAQ</a>
                    <a href="mailto:support@saas-schools.com?subject=Book%20a%20Demo">Book a Demo</a>
                    <a href="{{ route('school.register') }}">Get Started</a>
                    <a href="{{ route('platform.login') }}">Platform Admin</a>
                    <button class="theme-toggle-btn" id="theme-toggle-home" title="Toggle dark/light mode" aria-label="Toggle dark/light mode">
                        <span id="theme-icon-home">🌙</span>
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-sm saas-btn-primary">Sign in</a>
                </div>
            </div>
        </section>

        <section class="mh-hero">
            <div class="container py-5">
                <span class="mh-kicker">Built for modern schools in Africa</span>
                <h1>One platform for admissions, academics, and school finance.</h1>
                <p>Give your administrators, teachers, and parents a faster workflow with secure records, role-based access, and clear accountability.</p>

                <div class="mh-cta">
                    <a href="{{ route('school.register') }}" class="btn saas-btn-primary btn-lg">Register your school</a>
                    <a href="{{ route('login') }}" class="btn saas-btn-secondary btn-lg">Sign in</a>
                </div>

                <div class="mh-trust-note">First {{ number_format($freeLimit ?? 50) }} students are free for life. Above {{ number_format($freeLimit ?? 50) }}, pay a one-time ₦{{ number_format($oneTimeRate ?? 500) }} per newly added student and ₦{{ number_format($monthlyRate ?? 100) }} per student monthly.</div>
            </div>
        </section>

        <section class="container py-3">
            <div class="mh-trust-strip">
                <span>Trusted workflow for schools:</span>
                <span>Admissions</span>
                <span>Results</span>
                <span>Fees</span>
                <span>Class Management</span>
                <span>Reports</span>
            </div>
        </section>

        <section id="features" class="container py-5">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <article class="mh-card">
                        <h3>Academic Operations</h3>
                        <p>Manage classes, exams, grading, and result publication with less manual work.</p>
                    </article>
                </div>
                <div class="col-md-4 mb-3">
                    <article class="mh-card">
                        <h3>Finance and Billing</h3>
                        <p>Enjoy lifetime free access for your first {{ number_format($freeLimit ?? 50) }} students, then transparent one-time and monthly billing as your school grows.</p>
                    </article>
                </div>
                <div class="col-md-4 mb-3">
                    <article class="mh-card">
                        <h3>Multi-tenant by Design</h3>
                        <p>Each school operates in a secure tenant context while you scale from one platform.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="container pb-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-3">
                    <div class="mh-card">
                        <h3>What you can manage in one place</h3>
                        <ul class="mh-list">
                            <li>Student and staff records</li>
                            <li>Class, subject, and section workflows</li>
                            <li>Payments, invoices, and receipts</li>
                            <li>Exam records and report sheets</li>
                            <li>School settings and communication details</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="mh-card mh-proof">
                        <h3>Built for clarity and accountability</h3>
                        <p>Role-based access, cleaner workflows, and tenant isolation help your school run with less friction and stronger oversight.</p>
                        <a href="{{ route('school.register') }}" class="btn saas-btn-primary">Start free now</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="container pb-5">
            <div class="row">
                <div class="col-12 mb-3">
                    <h2 class="mh-section-title">What school leaders are saying</h2>
                </div>
                <div class="col-md-4 mb-3">
                    <article class="mh-card mh-quote">
                        <p>"Attendance, fees, and results are now in one flow. Our admin team saves hours every week."</p>
                        <h4>Mrs. Okafor</h4>
                        <span>School Administrator, Lagos</span>
                    </article>
                </div>
                <div class="col-md-4 mb-3">
                    <article class="mh-card mh-quote">
                        <p>"The reporting is clear, and parents get updates faster. It improved trust with our school community."</p>
                        <h4>Mr. Adeyemi</h4>
                        <span>Principal, Ibadan</span>
                    </article>
                </div>
                <div class="col-md-4 mb-3">
                    <article class="mh-card mh-quote">
                        <p>"We started quickly without a card and scaled smoothly as student numbers increased."</p>
                        <h4>Mrs. Nnaji</h4>
                        <span>Proprietress, Enugu</span>
                    </article>
                </div>
            </div>
        </section>

        <section id="pricing" class="container pb-5">
            <div class="mh-pricing">
                <div class="mh-pricing-head">
                    <h2>Simple and predictable pricing</h2>
                    <p>First {{ number_format($freeLimit ?? 50) }} students are free for life. Billing starts only for students above {{ number_format($freeLimit ?? 50) }}.</p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="mh-price-card">
                            <h3>Starter</h3>
                            <p class="mh-price">Free</p>
                            <ul class="mh-list">
                                <li>First {{ number_format($freeLimit ?? 50) }} students free for life</li>
                                <li>Core academic and admin workflows</li>
                                <li>Secure tenant space</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="mh-price-card mh-price-card-featured">
                            <h3>Growth</h3>
                            <p class="mh-price">₦{{ number_format($monthlyRate ?? 100) }} <span>/student/month above {{ number_format($freeLimit ?? 50) }}</span></p>
                            <ul class="mh-list">
                                <li>₦{{ number_format($oneTimeRate ?? 500) }} one-time per newly added student above {{ number_format($freeLimit ?? 50) }}</li>
                                <li>Scale without migration</li>
                                <li>Paystack billing support</li>
                                <li>Platform-level monitoring</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="affiliates" class="container pb-5">
            <div class="mh-card" style="max-width:920px;margin:0 auto;">
                <span class="mh-kicker">RiseFlow Affiliate Program</span>
                <h2 class="mh-section-title">Refer schools. Earn when they bill.</h2>
                <p class="mb-3">Share RiseFlow with school owners using your personal referral link. When a referred school completes successful Paystack payments, you accrue commissions based on billable students (one-time and monthly components).</p>
                <ul class="mh-list mb-4">
                    <li>Request access, pass a quick review, and receive your referral code</li>
                    <li>Schools register with your link so attribution stays clear</li>
                    <li>Track referred schools and recorded earnings in your affiliate dashboard</li>
                </ul>
                <div class="d-flex flex-wrap" style="gap:10px;">
                    <a href="{{ route('affiliates.request') }}" class="btn saas-btn-primary">Apply as an affiliate</a>
                    <a href="{{ route('affiliate.login') }}" class="btn saas-btn-secondary">Affiliate sign in</a>
                </div>
                <p class="small text-muted mt-3 mb-0">Payouts and rates are communicated during onboarding. Platform administrators approve each application.</p>
            </div>
        </section>

        <section id="faq" class="container pb-5">
            <div class="mh-faq">
                <h2>Frequently asked questions</h2>

                <div class="faq-item">
                    <h3>Do I need a card before starting?</h3>
                    <p>No. You can register your school and begin onboarding immediately.</p>
                </div>

                <div class="faq-item">
                    <h3>How does billing work?</h3>
                    <p>Your first {{ number_format($freeLimit ?? 50) }} students are free for life. For each newly added student above {{ number_format($freeLimit ?? 50) }}, there is a one-time ₦{{ number_format($oneTimeRate ?? 500) }} charge, plus ₦{{ number_format($monthlyRate ?? 100) }} monthly per student above {{ number_format($freeLimit ?? 50) }}.</p>
                </div>

                <div class="faq-item">
                    <h3>Can multiple schools use this platform?</h3>
                    <p>Yes. The app is built as a multi-tenant SaaS with school-level data isolation.</p>
                </div>
            </div>
        </section>

        <section class="container pb-5">
            <div class="mh-final-cta">
                <h2>Ready to modernize your school operations?</h2>
                <p>Launch quickly and scale confidently as your school grows.</p>
                <a href="{{ route('school.register') }}" class="btn saas-btn-primary">Create your school account</a>
                <a href="{{ route('login') }}" class="btn saas-btn-secondary ml-2">I already have an account</a>
                <a href="mailto:support@saas-schools.com?subject=Demo%20Request%20for%20School%20Management%20Platform" class="btn saas-btn-secondary ml-2">Book a guided demo</a>
            </div>
        </section>

        <section class="container pb-5">
            <div class="mh-support-band">
                <div>
                    <h3>Need help before onboarding?</h3>
                    <p>Our team can guide your first setup and data migration planning.</p>
                </div>
                <div class="mh-support-links">
                    <a href="mailto:support@saas-schools.com">support@saas-schools.com</a>
                    <a href="{{ route('privacy_policy') }}">Privacy Policy</a>
                    <a href="{{ route('terms_of_use') }}">Terms of Use</a>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection
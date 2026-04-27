<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ability Matrix
    |--------------------------------------------------------------------------
    |
    | Map named abilities to actor types that are allowed to execute them.
    | Actor types resolve from current guard/context:
    | - platform_admin (auth:platform)
    | - affiliate (auth:affiliate)
    | - school user types (super_admin, admin, teacher, accountant, etc)
    |
    */
    'abilities' => [
        // Platform-side capabilities
        'platform.billing_plans.manage' => ['platform_admin'],
        'platform.schools.manage' => ['platform_admin'],
        'platform.webhooks.manage' => ['platform_admin'],
        'platform.affiliates.manage' => ['platform_admin'],

        // School-side capabilities
        'school.settings.manage' => ['super_admin', 'admin'],
        'school.users.manage' => ['super_admin', 'admin'],
        'school.students.manage' => ['super_admin', 'admin'],
        'school.students.bulk_import' => ['super_admin', 'admin'],
        'school.subjects.manage' => ['super_admin', 'admin'],
        'school.exams.manage' => ['super_admin', 'admin'],
        'school.payments.manage' => ['super_admin', 'admin', 'accountant'],
        'school.marks.manage' => ['super_admin', 'admin', 'teacher'],
        'school.timetables.manage' => ['super_admin', 'admin'],
        'school.pins.manage' => ['super_admin', 'admin'],
        'school.promotions.manage' => ['super_admin', 'admin'],
        'school.classes.manage' => ['super_admin', 'admin'],
        'school.sections.manage' => ['super_admin', 'admin'],
        'school.grades.manage' => ['super_admin', 'admin'],
        'school.dorms.manage' => ['super_admin', 'admin'],
    ],
];

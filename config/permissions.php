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

        // School-side capabilities
        'school.settings.manage' => ['super_admin', 'admin'],
        'school.students.bulk_import' => ['super_admin', 'admin'],
    ],
];

<?php

namespace App\Repositories;


use App\Models\Setting;

class SettingRepo
{
    public function update($type, $desc)
    {
        // Upsert to avoid "missing setting row" cases per tenant.
        return Setting::updateOrCreate(
            ['type' => $type],
            ['description' => $desc]
        );
    }

    public function getSetting($type)
    {
        return Setting::where('type', $type)->get();
    }

    public function all()
    {
        return Setting::all();
    }
}
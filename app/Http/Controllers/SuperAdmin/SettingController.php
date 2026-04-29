<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Models\School;
use App\Models\Setting;
use App\Http\Controllers\Controller;
use App\Http\Requests\SettingUpdate;
use App\Repositories\MyClassRepo;
use App\Repositories\SettingRepo;
use App\Services\SchoolAuditLogService;

class SettingController extends Controller
{
    protected $setting, $my_class;

    public function __construct(SettingRepo $setting, MyClassRepo $my_class)
    {
        $this->setting = $setting;
        $this->my_class = $my_class;
    }

    public function index()
    {
        $this->ensureSchoolContext();
        $s = $this->setting->all();
        $d['class_types'] = $this->my_class->getTypes();
        $d['s'] = $s->flatMap(function ($s) {
            return [$s->type => $s->description];
        });
        return view('pages.super_admin.settings', $d);
    }

    public function update(SettingUpdate $req)
    {
        $school = $this->ensureSchoolContext();

        $sets = $req->except('_token', '_method', 'logo');
        $sets['lock_exam'] = $sets['lock_exam'] == 1 ? 1 : 0;

        $before = [];
        if ($school) {
            $trackedKeys = array_keys($sets);
            if ($req->hasFile('logo')) {
                $trackedKeys[] = 'logo';
            }

            $before = Setting::query()
                ->whereIn('type', $trackedKeys)
                ->pluck('description', 'type')
                ->toArray();
        }

        $keys = array_keys($sets);
        $values = array_values($sets);
        for ($i = 0; $i < count($sets); $i++) {
            $this->setting->update($keys[$i], $values[$i]);
        }

        // Keep core school profile fields in sync with tenant settings so
        // identity/verification pages always reflect latest school updates.
        if ($school) {
            $schoolUpdate = [];

            if (array_key_exists('system_name', $sets) && trim((string) $sets['system_name']) !== '') {
                $schoolUpdate['name'] = (string) $sets['system_name'];
            }

            if (array_key_exists('system_email', $sets)) {
                $schoolUpdate['email'] = trim((string) $sets['system_email']) !== '' ? (string) $sets['system_email'] : null;
            }

            if (array_key_exists('phone', $sets)) {
                $schoolUpdate['phone'] = trim((string) $sets['phone']) !== '' ? (string) $sets['phone'] : null;
            }

            if (array_key_exists('address', $sets)) {
                $schoolUpdate['address'] = trim((string) $sets['address']) !== '' ? (string) $sets['address'] : null;
            }

            if (!empty($schoolUpdate)) {
                School::where('id', (int) $school->id)->update($schoolUpdate);
            }
        }

        if ($req->hasFile('logo')) {
            $logo = $req->file('logo');
            $f = Qs::getFileMetaData($logo);
            $f['name'] = 'logo.' . $f['ext'];
            // Store logo per-tenant to prevent cross-school overwrites.
            // Without this, different schools end up pointing to the same
            // `uploads/logo.<ext>` physical file, so updating one school
            // changes what the other school displays.
            $schoolId = optional($school)->id ?: optional(auth()->user())->school_id;
            $targetDir = $schoolId
                ? (Qs::getPublicUploadPath() . 'schools/' . (int) $schoolId . '/')
                : Qs::getPublicUploadPath(); // fallback for platform-level accounts

            $f['path'] = $logo->storeAs($targetDir, $f['name'], 'public');
            $logo_path = '/storage/' . ltrim($f['path'], '/');
            $this->setting->update('logo', $logo_path);

            // Keep school.logo aligned with settings logo used across tenant layouts.
            if ($schoolId) {
                School::where('id', $schoolId)->update(['logo' => $logo_path]);
            }
        }

        if ($school) {
            $after = Setting::query()
                ->whereIn('type', array_keys($before))
                ->pluck('description', 'type')
                ->toArray();

            app(SchoolAuditLogService::class)->logDiff(
                $school,
                'school_settings_updated',
                $before,
                $after,
                ['source' => 'super_admin.settings.update']
            );
        }

        return back()->with('flash_success', __('msg.update_ok'));
    }

    private function ensureSchoolContext(): ?School
    {
        if (app()->bound('currentSchool')) {
            return app('currentSchool');
        }

        $schoolId = (int) (optional(auth()->user())->school_id ?? 0);
        if ($schoolId <= 0) {
            return null;
        }

        $school = School::query()->find($schoolId);
        if (!$school) {
            return null;
        }

        app()->instance('currentSchool', $school);
        view()->share('currentSchool', $school);

        return $school;
    }
}

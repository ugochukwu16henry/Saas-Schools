<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Repositories\UserRepo;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentAdmissionService
{
    private const USERNAME_MAX_LENGTH = 100;

    private const ADMISSION_NUMBER_MAX_LENGTH = 100;

    /** @var UserRepo */
    protected $userRepo;

    /** @var StudentRepo */
    protected $studentRepo;

    /** @var MyClassRepo */
    protected $myClassRepo;

    public function __construct(UserRepo $userRepo, StudentRepo $studentRepo, MyClassRepo $myClassRepo)
    {
        $this->userRepo = $userRepo;
        $this->studentRepo = $studentRepo;
        $this->myClassRepo = $myClassRepo;
    }

    /**
     * Create a student User and StudentRecord (same rules as single admit form).
     *
     * @param  array  $userRecord  Keys from Qs::getUserRecord() plus name
     * @param  array  $studentRecord  Keys from Qs::getStudentData()
     * @param  string|null  $admNo  Optional segment used in generated username
     */
    public function admitStudent(array $userRecord, array $studentRecord, ?string $admNo = null, ?UploadedFile $photo = null): User
    {
        $data = $userRecord;
        $sr = $studentRecord;

        $ct = $this->myClassRepo->findTypeByClass($sr['my_class_id'])->code;

        $data['user_type'] = 'student';
        $data['name'] = ucwords($data['name']);
        $data['code'] = $this->generateUniqueCode();
        $data['password'] = Hash::make('student');
        $data['photo'] = Qs::getDefaultUserImage();

        $data['username'] = $this->generateUniqueUsername($ct, $sr['year_admitted'], $admNo);

        if ($photo && $photo->isValid()) {
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student') . $data['code'], $f['name'], 'public');
            $data['photo'] = asset('storage/' . $f['path']);
        }

        $this->ensureGeneratedIdentifiersFit($data['username']);

        $user = $this->userRepo->create($data);

        $sr['adm_no'] = $data['username'];
        $sr['user_id'] = $user->id;
        $sr['session'] = Qs::getSetting('current_session');

        $this->studentRepo->createRecord($sr);

        return $user;
    }

    /**
     * Generate a unique code for a student.
     *
     * @return string
     */

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (User::withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }
    /**
     * Generate a unique username for a student.
     *
     * @param  string  $classTypeCode
     * @param  string  $yearAdmitted
     * @param  string|null  $admNo
     * @return string
     */
    private function generateUniqueUsername(string $classTypeCode, string $yearAdmitted, ?string $admNo): string
    {
        $appCode = Qs::getAppCode();
        $base = strtoupper($appCode . '/' . $classTypeCode . '/' . $yearAdmitted . '/');

        if ($admNo) {
            $username = $base . $admNo;
            // Check if this username already exists (globally, across all schools)
            if (!User::withoutGlobalScopes()->where('username', $username)->exists()) {
                return $username;
            }
            // If it exists, append a counter
            $counter = 1;
            while (User::withoutGlobalScopes()->where('username', $base . $admNo . '-' . $counter)->exists()) {
                $counter++;
            }
            return $base . $admNo . '-' . $counter;
        }

        // No admission number provided, generate a unique one
        $maxAttempts = 100;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $username = $base . mt_rand(1000, 99999);
            if (!User::withoutGlobalScopes()->where('username', $username)->exists()) {
                return $username;
            }
        }

        // Fallback: use a timestamp-based approach if random attempts fail
        $username = $base . 'U' . time();
        while (User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $username .= '_' . mt_rand(10, 99);
        }

        return $username;
    }

    private function ensureGeneratedIdentifiersFit(string $username): void
    {
        if (mb_strlen($username) > self::USERNAME_MAX_LENGTH) {
            throw new \RuntimeException('Generated student username "' . $username . '" exceeds the current users.username limit of ' . self::USERNAME_MAX_LENGTH . ' characters. Shorten admission number input or reduce system title length.');
        }

        if (mb_strlen($username) > self::ADMISSION_NUMBER_MAX_LENGTH) {
            throw new \RuntimeException('Generated admission number "' . $username . '" exceeds the current student_records.adm_no limit of ' . self::ADMISSION_NUMBER_MAX_LENGTH . ' characters.');
        }
    }
}

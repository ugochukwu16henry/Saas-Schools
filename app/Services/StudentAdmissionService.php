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
        $data['code'] = strtoupper(Str::random(10));
        $data['password'] = Hash::make('student');
        $data['photo'] = Qs::getDefaultUserImage();

        $data['username'] = strtoupper(Qs::getAppCode().'/'.$ct.'/'.$sr['year_admitted'].'/'.($admNo ?: mt_rand(1000, 99999)));

        if ($photo && $photo->isValid()) {
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.'.$f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$data['code'], $f['name']);
            $data['photo'] = asset('storage/'.$f['path']);
        }

        $user = $this->userRepo->create($data);

        $sr['adm_no'] = $data['username'];
        $sr['user_id'] = $user->id;
        $sr['session'] = Qs::getSetting('current_session');

        $this->studentRepo->createRecord($sr);

        return $user;
    }
}

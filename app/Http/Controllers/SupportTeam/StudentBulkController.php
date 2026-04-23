<?php

namespace App\Http\Controllers\SupportTeam;

use App\Http\Controllers\Controller;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\User;
use App\Repositories\LocationRepo;
use App\Services\StudentAdmissionService;
use App\Services\StudentBulkExcelService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentBulkController extends Controller
{
    public function __construct()
    {
        $this->middleware('teamSA');
    }

    public function create(LocationRepo $loc)
    {
        $data['nationals'] = $loc->getAllNationals();
        $data['states'] = $loc->getAllStates();

        return view('pages.support_team.students.bulk', $data);
    }

    public function downloadTemplate(StudentBulkExcelService $excel)
    {
        return $excel->downloadTemplateResponse('student_import_template.xlsx');
    }

    public function store(Request $request, StudentBulkExcelService $excelService, StudentAdmissionService $admissionService)
    {
        $file = $this->resolveImportFile($request);
        if ($file === null) {
            return $this->bulkImportRedirect($request)
                ->withErrors(['import_file' => 'Please upload an Excel file (.xlsx or .xls). Do not use a saved session without choosing the file again.']);
        }
        if (! $file->isValid()) {
            return $this->bulkImportRedirect($request)
                ->withErrors(['import_file' => 'The file upload failed ('.$file->getErrorMessage().'). Check size (max 10 MB) and PHP upload_max_filesize / post_max_size.']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->bulkImportRedirect($request)
                ->withErrors(['import_file' => 'Only .xlsx or .xls files are allowed.']);
        }
        $maxBytes = 10240 * 1024;
        if ($file->getSize() > $maxBytes) {
            return $this->bulkImportRedirect($request)
                ->withErrors(['import_file' => 'The file may not be greater than 10 MB.']);
        }

        Validator::make($request->only(['nal_id', 'state_id', 'lga_id', 'default_address']), [
            'nal_id' => 'required|integer|exists:nationalities,id',
            'state_id' => 'required|integer|exists:states,id',
            'lga_id' => 'required|integer|exists:lgas,id',
            'default_address' => 'required|string|min:6|max:120',
        ])->validate();

        $parsed = $excelService->parseStudentSheet($file);
        if ($parsed['errors']) {
            return $this->bulkImportRedirect($request)
                ->with('flash_danger', implode(' ', $parsed['errors']));
        }

        $rowData = $parsed['rows'];
        if (count($rowData) === 0) {
            return $this->bulkImportRedirect($request)
                ->with('flash_danger', 'No student rows found. Add data below the header row in the Students sheet.');
        }

        $school = app('currentSchool');
        $studentCount = $school->users()->where('user_type', 'student')->count();
        $n = count($rowData);
        if ($studentCount + $n > $school->free_student_limit) {
            $sub = $school->subscription;
            if (! $sub || ! $sub->isActive()) {
                return $this->bulkImportRedirect($request)
                    ->with('flash_danger', 'Importing '.$n.' student(s) would exceed your free student limit ('.$school->free_student_limit.') without an active subscription. Please subscribe or reduce the number of rows. You can manage billing from your dashboard.')
                    ->with('billing_required', true);
            }
        }

        $defaults = [
            'nal_id' => (int) $request->nal_id,
            'state_id' => (int) $request->state_id,
            'lga_id' => (int) $request->lga_id,
            'address' => $request->default_address,
        ];

        $rowErrors = $this->validateRows($rowData, $defaults);
        if ($rowErrors !== []) {
            return $this->bulkImportRedirect($request)
                ->with('bulk_import_errors', $rowErrors);
        }

        $built = $this->buildPayloads($rowData, $defaults);

        $start = microtime(true);
        try {
            DB::transaction(function () use ($built, $admissionService) {
                foreach ($built as $payload) {
                    $admissionService->admitStudent(
                        $payload['user'],
                        $payload['student'],
                        $payload['adm_no'],
                        null
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('student_bulk_import_failed', [
                'message' => $e->getMessage(),
                'school_id' => $school->id ?? null,
            ]);

            return $this->bulkImportRedirect($request)
                ->with('flash_danger', 'Import failed: '.$e->getMessage());
        }

        Log::info('student_bulk_import_ok', [
            'school_id' => $school->id,
            'user_id' => auth()->id(),
            'rows' => $n,
            'seconds' => round(microtime(true) - $start, 2),
        ]);

        return redirect()
            ->route('students.bulk.create')
            ->with('flash_success', 'Successfully imported '.$n.' student(s). Default password is "student". Students can complete profiles via Student Information.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rowData  excel row index => assoc cells
     * @return array<int, string> row number => message
     */
    protected function validateRows(array $rowData, array $defaults): array
    {
        $errors = [];
        $seenEmails = [];
        $seenAdmKeys = [];

        foreach ($rowData as $excelRow => $cells) {
            $name = trim((string) ($cells['full_name'] ?? ''));
            if (mb_strlen($name) < 6) {
                $errors[$excelRow] = 'full_name must be at least 6 characters.';
                continue;
            }

            $gender = trim((string) ($cells['gender'] ?? ''));
            if (! in_array($gender, ['Male', 'Female'], true)) {
                $errors[$excelRow] = 'gender must be Male or Female.';
                continue;
            }

            $year = trim((string) ($cells['year_admitted'] ?? ''));
            if ($year === '') {
                $errors[$excelRow] = 'year_admitted is required.';
                continue;
            }

            $classId = $cells['class_id'] ?? null;
            if (! is_numeric($classId) || (int) $classId < 1) {
                $errors[$excelRow] = 'class_id must be a positive number.';
                continue;
            }
            $classId = (int) $classId;
            if (! MyClass::query()->whereKey($classId)->exists()) {
                $errors[$excelRow] = 'class_id is invalid for this school.';
                continue;
            }

            $sectionId = $cells['section_id'] ?? null;
            if (! is_numeric($sectionId) || (int) $sectionId < 1) {
                $errors[$excelRow] = 'section_id must be a positive number.';
                continue;
            }
            $sectionId = (int) $sectionId;
            if (! Section::query()->where('id', $sectionId)->where('my_class_id', $classId)->exists()) {
                $errors[$excelRow] = 'section_id does not belong to the given class_id.';
                continue;
            }

            $adm = isset($cells['optional_adm_no']) ? trim((string) $cells['optional_adm_no']) : '';
            if ($adm !== '' && ! ctype_alnum($adm)) {
                $errors[$excelRow] = 'optional_adm_no must be alphanumeric when provided.';
                continue;
            }
            if ($adm !== '' && (strlen($adm) < 3 || strlen($adm) > 150)) {
                $errors[$excelRow] = 'optional_adm_no must be between 3 and 150 characters when provided.';
                continue;
            }
            if ($adm !== '') {
                $admKey = $year.'|'.$classId.'|'.strtolower($adm);
                if (isset($seenAdmKeys[$admKey])) {
                    $errors[$excelRow] = 'duplicate optional_adm_no for the same class and year in this file.';
                    continue;
                }
                $seenAdmKeys[$admKey] = true;
            }

            $email = isset($cells['email']) ? trim((string) $cells['email']) : '';
            if ($email !== '') {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[$excelRow] = 'email is invalid.';
                    continue;
                }
                $el = strtolower($email);
                if (isset($seenEmails[$el])) {
                    $errors[$excelRow] = 'duplicate email in file.';
                    continue;
                }
                $seenEmails[$el] = true;
                if (User::query()->where('email', $email)->exists()) {
                    $errors[$excelRow] = 'email already exists in this school.';
                    continue;
                }
            }

            $phone = isset($cells['optional_phone']) ? trim((string) $cells['optional_phone']) : '';
            if ($phone !== '' && strlen($phone) < 6) {
                $errors[$excelRow] = 'optional_phone must be at least 6 characters when provided.';
                continue;
            }
        }

        ksort($errors, SORT_NUMERIC);

        return $errors;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rowData
     */
    protected function buildPayloads(array $rowData, array $defaults): array
    {
        $out = [];
        foreach ($rowData as $excelRow => $cells) {
            $name = trim((string) $cells['full_name']);
            $gender = trim((string) $cells['gender']);
            $year = trim((string) $cells['year_admitted']);
            $classId = (int) $cells['class_id'];
            $sectionId = (int) $cells['section_id'];
            $adm = trim((string) ($cells['optional_adm_no'] ?? ''));
            $email = trim((string) ($cells['email'] ?? ''));
            $phone = trim((string) ($cells['optional_phone'] ?? ''));

            $user = [
                'name' => $name,
                'email' => $email === '' ? null : $email,
                'phone' => $phone === '' ? null : $phone,
                'phone2' => null,
                'dob' => null,
                'gender' => $gender,
                'address' => $defaults['address'],
                'bg_id' => null,
                'nal_id' => $defaults['nal_id'],
                'state_id' => $defaults['state_id'],
                'lga_id' => $defaults['lga_id'],
            ];

            $student = [
                'my_class_id' => $classId,
                'section_id' => $sectionId,
                'my_parent_id' => null,
                'dorm_id' => null,
                'dorm_room_no' => null,
                'year_admitted' => $year,
                'house' => null,
                'age' => null,
            ];

            $out[] = [
                'user' => $user,
                'student' => $student,
                'adm_no' => $adm === '' ? null : $adm,
            ];
        }

        return $out;
    }

    /**
     * Read upload only from the files bag so a stray string "import_file" in POST/session cannot break Request::file().
     */
    protected function resolveImportFile(Request $request): ?UploadedFile
    {
        if (! $request->files->has('import_file')) {
            return null;
        }
        $file = $request->files->get('import_file');
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $file;
    }

    /**
     * Never flash "import_file" into old input (files are not re-postable; a string breaks the next request).
     * Use input() instead of except()/all() to avoid triggering allFiles() which crashes when the
     * uploaded file is malformed or partially received (createFromBase receives a string).
     */
    protected function bulkImportRedirect(Request $request)
    {
        return back()->withInput($request->input());
    }
}

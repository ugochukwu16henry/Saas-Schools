<?php

namespace App\Services;

use App\Repositories\MyClassRepo;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentBulkExcelService
{
    public const MAX_DATA_ROWS = 1000;

    public const STUDENTS_SHEET = 'Students';

    public const HEADER_KEYS = [
        'full_name',
        'gender',
        'year_admitted',
        'class_id',
        'section_id',
        'optional_adm_no',
        'email',
        'optional_phone',
    ];

    /** @var MyClassRepo */
    protected $myClassRepo;

    public function __construct(MyClassRepo $myClassRepo)
    {
        $this->myClassRepo = $myClassRepo;
    }

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instructions');
        $lines = [
            'Student bulk import template',
            '',
            '1. Fill one row per student in the "Students" sheet. Do not rename the header row.',
            '2. Default password for new students is: student (same as single admit).',
            '3. On the upload page, set Nationality, State, LGA, and default address — they apply to every row unless you add those columns later in a custom export.',
            '4. class_id and section_id must match your school (see Reference sheet). section_id must belong to class_id.',
            '5. full_name: at least 6 characters. gender: Male or Female. year_admitted: e.g. '.date('Y').'.',
            '6. optional_adm_no: optional alphanumeric segment used in the generated username.',
            '7. email: optional; must be unique in your school if provided.',
            '8. Maximum rows per file: '.self::MAX_DATA_ROWS.'.',
        ];
        $r = 1;
        foreach ($lines as $line) {
            $instructions->setCellValue('A'.$r, $line);
            $r++;
        }
        $instructions->getColumnDimension('A')->setWidth(100);

        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Reference');
        $ref->fromArray(['class_id', 'class_name', 'section_id', 'section_name'], null, 'A1');
        $row = 2;
        foreach ($this->myClassRepo->all() as $mc) {
            foreach ($mc->section as $sec) {
                $ref->fromArray([$mc->id, $mc->name, $sec->id, $sec->name], null, 'A'.$row);
                $row++;
            }
        }
        foreach (range('A', 'D') as $col) {
            $ref->getColumnDimension($col)->setAutoSize(true);
        }

        $students = $spreadsheet->createSheet();
        $students->setTitle(self::STUDENTS_SHEET);
        $students->fromArray(self::HEADER_KEYS, null, 'A1');
        foreach (range('A', 'H') as $col) {
            $students->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function downloadTemplateResponse(string $filename = 'student_import_template.xlsx')
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function parseStudentSheet(UploadedFile $file): array
    {
        $errors = [];
        $rows = [];

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName(self::STUDENTS_SHEET);
        if (! $sheet) {
            $errors[] = 'Missing sheet "'.self::STUDENTS_SHEET.'". Use the downloaded template without renaming sheets.';

            return ['rows' => [], 'errors' => $errors];
        }

        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestColumn);

        $headerMap = [];
        for ($c = 1; $c <= $highestColIndex; $c++) {
            $addr = Coordinate::stringFromColumnIndex($c).'1';
            $val = trim((string) $sheet->getCell($addr)->getValue());
            if ($val === '') {
                continue;
            }
            $key = $this->normalizeHeaderToKey($val);
            if ($key && in_array($key, self::HEADER_KEYS, true)) {
                $headerMap[$c] = $key;
            }
        }

        foreach (self::HEADER_KEYS as $required) {
            if (! in_array($required, $headerMap, true)) {
                $errors[] = 'Missing required column: '.$required;
            }
        }
        if ($errors) {
            return ['rows' => [], 'errors' => $errors];
        }

        $dataRowCount = 0;
        for ($r = 2; $r <= $highestRow; $r++) {
            $assoc = [];
            foreach ($headerMap as $colIndex => $key) {
                $addr = Coordinate::stringFromColumnIndex($colIndex).$r;
                $assoc[$key] = $sheet->getCell($addr)->getValue();
            }

            $isEmpty = true;
            foreach (['full_name', 'gender', 'year_admitted', 'class_id', 'section_id'] as $k) {
                if (isset($assoc[$k]) && trim((string) $assoc[$k]) !== '') {
                    $isEmpty = false;
                    break;
                }
            }
            if ($isEmpty) {
                continue;
            }

            $dataRowCount++;
            if ($dataRowCount > self::MAX_DATA_ROWS) {
                $errors[] = 'Too many data rows (max '.self::MAX_DATA_ROWS.').';

                return ['rows' => [], 'errors' => $errors];
            }

            $rows[$r] = $assoc;
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    protected function normalizeHeaderToKey(string $raw): ?string
    {
        $k = strtolower(trim(preg_replace('/\s+/', '_', $raw)));
        $synonyms = [
            'full_name' => 'full_name',
            'name' => 'full_name',
            'student_name' => 'full_name',
            'gender' => 'gender',
            'sex' => 'gender',
            'year_admitted' => 'year_admitted',
            'year' => 'year_admitted',
            'class_id' => 'class_id',
            'section_id' => 'section_id',
            'optional_adm_no' => 'optional_adm_no',
            'adm_no' => 'optional_adm_no',
            'admission_no' => 'optional_adm_no',
            'email' => 'email',
            'optional_phone' => 'optional_phone',
            'phone' => 'optional_phone',
        ];

        return $synonyms[$k] ?? (in_array($k, self::HEADER_KEYS, true) ? $k : null);
    }
}

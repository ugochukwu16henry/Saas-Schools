<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\DB;
use Mockery;
use App\Services\StudentBulkExcelService;
use Tests\TestCase;

class BulkStudentImportFlowTest extends TestCase
{
    public function testBulkImportPageShowsHumanFriendlyUploadLimits(): void
    {
        $this->be(new User([
            'name' => 'Bulk Import User',
            'photo' => '/images/avatar.png',
            'user_type' => 'super_admin',
        ]));

        view()->share('errors', new ViewErrorBag());

        $html = view('pages.support_team.students.bulk', [
            'nationals' => collect(),
            'states' => collect(),
            'uploadMaxDisplay' => 'Unlimited',
            'postMaxDisplay' => 'Unlimited',
            'appUploadLimitDisplay' => '10 MB',
        ])->render();

        $this->assertStringContainsString('Server upload_max_filesize = <strong>Unlimited</strong>', $html);
        $this->assertStringContainsString('post_max_size = <strong>Unlimited</strong>', $html);
        $this->assertStringContainsString('app import limit = <strong>10 MB</strong>', $html);
        $this->assertStringContainsString('Browsers do not keep a selected file after a failed submit or redirect.', $html);
    }

    public function testBulkImportMissingFileRedirectKeepsFormValuesAndShowsRetryMessage(): void
    {
        $this->withoutMiddleware();

        $response = $this->from(route('students.bulk.create'))->post(route('students.bulk.store'), [
            'nal_id' => 12,
            'state_id' => 5,
            'lga_id' => 9,
            'default_address' => 'Address to be updated from student profile',
        ]);

        $response->assertRedirect(route('students.bulk.create'));
        $response->assertSessionHasErrors([
            'import_file' => 'No file was selected. Choose an Excel file (.xlsx or .xls) before submitting. If you are retrying after an error, browsers require you to re-select the file.',
        ]);

        $this->assertEquals(12, session('_old_input.nal_id'));
        $this->assertEquals(5, session('_old_input.state_id'));
        $this->assertEquals(9, session('_old_input.lga_id'));
        $this->assertEquals('Address to be updated from student profile', session('_old_input.default_address'));
        $this->assertNull(session('_old_input.import_file'));
    }

    public function testBulkImportRejectsNonExcelFilesBeforeRowValidation(): void
    {
        $this->withoutMiddleware();

        $response = $this->from(route('students.bulk.create'))->post(route('students.bulk.store'), [
            'import_file' => UploadedFile::fake()->create('students.csv', 5, 'text/csv'),
            'nal_id' => 12,
            'state_id' => 5,
            'lga_id' => 9,
            'default_address' => 'Address to be updated from student profile',
        ]);

        $response->assertRedirect(route('students.bulk.create'));
        $response->assertSessionHasErrors([
            'import_file' => 'Only .xlsx or .xls files are allowed.',
        ]);
    }

    public function testBulkImportShowsFriendlyMessageWhenSpreadsheetParsingFails(): void
    {
        $this->withoutMiddleware();

        DB::table('nationalities')->updateOrInsert(['id' => 91001], ['name' => 'Test Nationality']);
        DB::table('states')->updateOrInsert(['id' => 91001], ['name' => 'Test State']);
        DB::table('lgas')->updateOrInsert(['id' => 91001], ['state_id' => 91001, 'name' => 'Test LGA']);

        $mock = Mockery::mock(StudentBulkExcelService::class);
        $mock->shouldReceive('parseStudentSheet')
            ->once()
            ->andThrow(new \RuntimeException('Corrupt workbook'));
        $this->app->instance(StudentBulkExcelService::class, $mock);

        $response = $this->from(route('students.bulk.create'))->post(route('students.bulk.store'), [
            'import_file' => UploadedFile::fake()->create('students.xlsx', 5, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'nal_id' => 91001,
            'state_id' => 91001,
            'lga_id' => 91001,
            'default_address' => 'Address to be updated from student profile',
        ]);

        $response->assertRedirect(route('students.bulk.create'));
        $response->assertSessionHas('flash_danger', 'The uploaded spreadsheet could not be read. Please use the downloaded template and upload a valid .xlsx or .xls file.');
    }

    public function testBulkImportShowsInlineListWhenParserReportsMissingColumns(): void
    {
        $this->withoutMiddleware();

        DB::table('nationalities')->updateOrInsert(['id' => 91001], ['name' => 'Test Nationality']);
        DB::table('states')->updateOrInsert(['id' => 91001], ['name' => 'Test State']);
        DB::table('lgas')->updateOrInsert(['id' => 91001], ['state_id' => 91001, 'name' => 'Test LGA']);

        $mock = Mockery::mock(StudentBulkExcelService::class);
        $mock->shouldReceive('parseStudentSheet')
            ->once()
            ->andReturn([
                'rows'   => [],
                'errors' => [
                    'Missing required column: gender',
                    'Missing required column: class_id',
                ],
            ]);
        $this->app->instance(StudentBulkExcelService::class, $mock);

        $response = $this->from(route('students.bulk.create'))->post(route('students.bulk.store'), [
            'import_file' => UploadedFile::fake()->create('students.xlsx', 5, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'nal_id' => 91001,
            'state_id' => 91001,
            'lga_id' => 91001,
            'default_address' => 'Address to be updated from student profile',
        ]);

        $response->assertRedirect(route('students.bulk.create'));

        $parseErrors = $response->getSession()->get('bulk_import_parse_errors');
        $this->assertIsArray($parseErrors);
        $this->assertContains('Missing required column: gender', $parseErrors);
        $this->assertContains('Missing required column: class_id', $parseErrors);
    }

    public function testBulkImportShowsInlineListWhenStudentsSheetHasNoDataRows(): void
    {
        $this->withoutMiddleware();

        DB::table('nationalities')->updateOrInsert(['id' => 91001], ['name' => 'Test Nationality']);
        DB::table('states')->updateOrInsert(['id' => 91001], ['name' => 'Test State']);
        DB::table('lgas')->updateOrInsert(['id' => 91001], ['state_id' => 91001, 'name' => 'Test LGA']);

        $mock = Mockery::mock(StudentBulkExcelService::class);
        $mock->shouldReceive('parseStudentSheet')
            ->once()
            ->andReturn(['rows' => [], 'errors' => []]);
        $this->app->instance(StudentBulkExcelService::class, $mock);

        $response = $this->from(route('students.bulk.create'))->post(route('students.bulk.store'), [
            'import_file' => UploadedFile::fake()->create('students.xlsx', 5, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'nal_id' => 91001,
            'state_id' => 91001,
            'lga_id' => 91001,
            'default_address' => 'Address to be updated from student profile',
        ]);

        $response->assertRedirect(route('students.bulk.create'));

        $parseErrors = $response->getSession()->get('bulk_import_parse_errors');
        $this->assertIsArray($parseErrors);
        $this->assertStringContainsString('No student rows found', $parseErrors[0]);
    }
}

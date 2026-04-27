<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\ViewErrorBag;
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
}

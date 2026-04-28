<?php

namespace Tests\Unit;

use App\Services\Ai\StructuredOutput;
use Tests\TestCase;

class StructuredOutputTest extends TestCase
{
    public function test_it_decodes_valid_json_object(): void
    {
        $service = new StructuredOutput();
        $decoded = $service->decodeObject('{"title":"Hello","body":"World"}');

        $this->assertIsArray($decoded);
        $this->assertSame('Hello', $decoded['title']);
        $this->assertTrue($service->hasRequiredKeys($decoded, ['title', 'body']));
    }
}

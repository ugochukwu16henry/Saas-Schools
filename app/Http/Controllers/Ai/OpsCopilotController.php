<?php

namespace App\Http\Controllers\Ai;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiRouter;
use App\Services\Ai\AiUsageLogger;
use App\Services\Ai\PromptBuilders\OpsSummaryPromptBuilder;
use App\Services\Ai\Security\ResponseGuard;
use App\Services\Ai\StructuredOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpsCopilotController extends Controller
{
    private AiRouter $router;
    private OpsSummaryPromptBuilder $promptBuilder;
    private ResponseGuard $responseGuard;
    private AiUsageLogger $usageLogger;
    private StructuredOutput $structuredOutput;

    public function __construct(
        AiRouter $router,
        OpsSummaryPromptBuilder $promptBuilder,
        ResponseGuard $responseGuard,
        AiUsageLogger $usageLogger,
        StructuredOutput $structuredOutput
    ) {
        $this->router = $router;
        $this->promptBuilder = $promptBuilder;
        $this->responseGuard = $responseGuard;
        $this->usageLogger = $usageLogger;
        $this->structuredOutput = $structuredOutput;
    }

    public function summarize(Request $request)
    {
        if (! Qs::userIsTeamSA()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:160',
            'notes' => 'required|string|max:3000',
        ]);

        $messages = $this->promptBuilder->build($data);
        $featureConfig = (array) config('ai.features.ops_summary', []);
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        $user = Auth::user();

        $reqLog = $this->usageLogger->createRequest('ops_summary', $messages, $school, $user, [
            'provider' => data_get($featureConfig, 'provider'),
            'model' => data_get($featureConfig, 'model'),
        ]);

        $start = microtime(true);
        try {
            $result = $this->router->generate('ops_summary', $messages, $featureConfig);
            $content = $this->responseGuard->guard((string) data_get($result, 'content', ''));
            $decoded = $this->structuredOutput->decodeObject($content) ?? [];
            $latency = (int) round((microtime(true) - $start) * 1000);
            $this->usageLogger->markSuccess($reqLog, $result, $latency);

            return response()->json([
                'summary' => (string) data_get($decoded, 'summary', $content),
                'risks' => (array) data_get($decoded, 'risks', []),
                'next_steps' => (array) data_get($decoded, 'next_steps', []),
                'provider' => data_get($result, 'provider'),
                'model' => data_get($result, 'model'),
                'request_id' => $reqLog->id,
            ]);
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            $this->usageLogger->markFailed($reqLog, $e, $latency);
            return response()->json(['message' => 'Could not generate ops summary right now.'], 422);
        }
    }
}

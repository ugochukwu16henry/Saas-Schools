<?php

namespace App\Http\Controllers\Ai;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiRouter;
use App\Services\Ai\AiUsageLogger;
use App\Services\Ai\PromptBuilders\AnnouncementPromptBuilder;
use App\Services\Ai\Security\ResponseGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    private AiRouter $router;
    private AnnouncementPromptBuilder $promptBuilder;
    private ResponseGuard $responseGuard;
    private AiUsageLogger $usageLogger;

    public function __construct(
        AiRouter $router,
        AnnouncementPromptBuilder $promptBuilder,
        ResponseGuard $responseGuard,
        AiUsageLogger $usageLogger
    ) {
        $this->router = $router;
        $this->promptBuilder = $promptBuilder;
        $this->responseGuard = $responseGuard;
        $this->usageLogger = $usageLogger;
    }

    public function generate(Request $request)
    {
        if (! Qs::userIsTeamSA()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'audience' => 'required|string|max:120',
            'tone' => 'required|string|max:80',
            'language' => 'required|string|max:80',
            'context' => 'nullable|string|max:1200',
            'key_points' => 'required|string|max:2000',
        ]);

        $messages = $this->promptBuilder->build($data);
        $featureConfig = (array) config('ai.features.announcement_draft', []);
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        $user = Auth::user();

        $reqLog = $this->usageLogger->createRequest('announcement_draft', $messages, $school, $user, [
            'provider' => data_get($featureConfig, 'provider'),
            'model' => data_get($featureConfig, 'model'),
        ]);

        $start = microtime(true);
        try {
            $result = $this->router->generate('announcement_draft', $messages, $featureConfig);
            $content = $this->responseGuard->guard((string) data_get($result, 'content', ''));
            $latency = (int) round((microtime(true) - $start) * 1000);

            $this->usageLogger->markSuccess($reqLog, $result, $latency);

            return response()->json([
                'draft' => $content,
                'provider' => data_get($result, 'provider'),
                'model' => data_get($result, 'model'),
                'fallback_from' => data_get($result, 'fallback_from'),
                'request_id' => $reqLog->id,
                'message' => 'Draft generated. Review before publishing.',
            ]);
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            $this->usageLogger->markFailed($reqLog, $e, $latency);

            return response()->json([
                'message' => 'Could not generate draft right now. Please try again.',
            ], 422);
        }
    }
}

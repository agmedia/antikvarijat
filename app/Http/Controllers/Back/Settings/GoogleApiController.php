<?php

namespace App\Http\Controllers\Back\Settings;

use App\Http\Controllers\Controller;
use App\Services\AdminGoogleTranslationService;
use App\Services\GoogleTranslateService;
use Illuminate\Http\Request;

class GoogleApiController extends Controller
{
    public function index(AdminGoogleTranslationService $batch, GoogleTranslateService $translate)
    {
        return view('back.settings.google-api.index', [
            'targets' => $batch->targetsForView(),
            'googleTranslate' => [
                'official_configured' => $translate->hasOfficialApiKey(),
                'public_fallback' => $translate->isPublicEndpointEnabled(),
                'source' => 'hr',
                'target' => 'en',
            ],
        ]);
    }

    public function start(Request $request, AdminGoogleTranslationService $batch, GoogleTranslateService $translate)
    {
        $data = $request->validate([
            'targets' => ['required', 'array', 'min:1'],
            'targets.*' => ['required', 'string'],
            'overwrite' => ['nullable', 'boolean'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:25'],
            'limit' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! $translate->hasOfficialApiKey() && ! $translate->isPublicEndpointEnabled()) {
            return response()->json([
                'message' => 'Google Translate nije konfiguriran. Postavite GOOGLE_TRANSLATE_API_KEY u .env.',
            ], 422);
        }

        $result = $batch->start(
            $data['targets'],
            (bool) ($data['overwrite'] ?? false),
            (int) ($data['batch_size'] ?? 5),
            (int) ($data['limit'] ?? 0)
        );

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'job' => $result['job'],
        ]);
    }

    public function status(string $job, AdminGoogleTranslationService $batch)
    {
        $status = $batch->status($job);

        if (! $status) {
            return response()->json([
                'message' => 'Prijevod nije pronađen ili je istekao.',
            ], 404);
        }

        return response()->json([
            'job' => $status,
        ]);
    }

    public function process(string $job, AdminGoogleTranslationService $batch, GoogleTranslateService $translate)
    {
        $status = $batch->process($job, $translate);

        if (! $status) {
            return response()->json([
                'message' => 'Prijevod nije pronađen ili je istekao.',
            ], 404);
        }

        return response()->json([
            'job' => $status,
        ]);
    }

    public function cancel(string $job, AdminGoogleTranslationService $batch)
    {
        $status = $batch->cancel($job);

        if (! $status) {
            return response()->json([
                'message' => 'Prijevod nije pronađen ili je istekao.',
            ], 404);
        }

        return response()->json([
            'job' => $status,
        ]);
    }
}

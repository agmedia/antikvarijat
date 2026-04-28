<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\VialibriFeedService;
use Illuminate\Http\Request;

class VialibriFeedController extends Controller
{
    /**
     * @return \Illuminate\Http\Response
     */
    public function sync(Request $request, VialibriFeedService $feedService)
    {
        $this->authorizeRequest($request);

        return response()
            ->view('front.layouts.partials.vialibri-sync', [
                'feed' => $feedService->buildSyncPayload(),
            ])
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function data(Request $request, VialibriFeedService $feedService)
    {
        $this->authorizeRequest($request);

        return response()
            ->view('front.layouts.partials.vialibri-data', [
                'feed' => $feedService->buildDataPayload(),
            ])
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * Abort when an access code is configured and not supplied.
     */
    private function authorizeRequest(Request $request): void
    {
        $expected = trim((string) config('services.vialibri.access_code', ''));

        if ($expected === '') {
            return;
        }

        $provided = trim((string) ($request->query('access_code') ?: ''));

        if ($provided === '') {
            $header = trim((string) $request->header('Authorization', ''));

            if (stripos($header, 'Bearer ') === 0) {
                $header = trim(substr($header, 7));
            }

            $provided = $header;
        }

        abort_unless(hash_equals($expected, $provided), 401);
    }
}

<?php

namespace App\Http\Controllers\Back\Settings;

use App\Http\Controllers\Controller;
use App\Services\Shipping\WoltDriveSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WoltDriveSettingsController extends Controller
{
    public function update(Request $request, WoltDriveSettingsService $settings)
    {
        $user = $request->user();

        abort_unless(
            $user
                && $user->isAdministrator()
                && (bool) optional($user->details)->status,
            403
        );

        $validator = Validator::make($request->all(), [
            'module_enabled' => ['required', 'boolean'],
            'environment' => ['required', Rule::in(['production', 'development'])],
            'api_key' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:2000'],
            'venue_id' => ['nullable', 'string', 'max:191'],
            'merchant_id' => ['nullable', 'string', 'max:191'],
            'availability_cache_seconds' => ['required', 'integer', 'min:0', 'max:900'],
            'preparation_time_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'request_timeout_seconds' => ['required', 'integer', 'min:3', 'max:30'],
            'fallback_weight_grams' => ['required', 'integer', 'min:1', 'max:25000'],
            'cod_enabled' => ['required', 'boolean'],
            'pricing_mode' => ['required', Rule::in(['fixed', 'quote'])],
            'quote_markup_percent' => ['required', 'numeric', 'min:0', 'max:200'],
            'max_quote_price' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'support_url' => ['nullable', 'url', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:191'],
            'support_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $validator->after(function ($validator) use ($request, $settings) {
            if (! $request->boolean('module_enabled')) {
                return;
            }

            $current = $settings->get();

            if (trim((string) $request->input('venue_id')) === '') {
                $validator->errors()->add('venue_id', 'Upišite Wolt Venue ID prije uključivanja modula.');
            }

            if (trim((string) $request->input('api_key')) === '' && trim((string) ($current['api_key'] ?? '')) === '') {
                $validator->errors()->add('api_key', 'Upišite Wolt API ključ prije uključivanja modula.');
            }

            if (trim((string) $request->input('webhook_secret')) === ''
                && trim((string) ($current['webhook_secret'] ?? '')) === '') {
                $validator->errors()->add(
                    'webhook_secret',
                    'Upišite Wolt webhook secret prije uključivanja modula kako bi statusi dostave radili.'
                );
            }

            if (trim((string) $request->input('support_email')) === ''
                && trim((string) $request->input('support_phone')) === '') {
                $validator->errors()->add('support_email', 'Upišite barem e-mail ili telefon korisničke podrške.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->route('shippings')
                ->withErrors($validator, 'wolt')
                ->withInput($request->except(['api_key', 'webhook_secret']));
        }

        $validated = $validator->validated();
        $validated['module_enabled'] = $request->boolean('module_enabled');
        $validated['cod_enabled'] = $request->boolean('cod_enabled');

        if ($settings->save($validated)) {
            return redirect()
                ->route('shippings')
                ->with('success', 'Wolt Drive postavke su spremljene.');
        }

        return redirect()
            ->route('shippings')
            ->withInput($request->except(['api_key', 'webhook_secret']))
            ->with('error', 'Wolt Drive postavke nije moguće spremiti.');
    }
}

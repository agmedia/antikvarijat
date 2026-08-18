<?php

namespace App\Http\Controllers\Back\Settings;

use App\Http\Controllers\Controller;
use App\Services\Shipping\BoxNowSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BoxNowSettingsController extends Controller
{
    public function update(Request $request, BoxNowSettingsService $settings)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'string', 'max:500'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            'api_partner_id' => ['nullable', 'string', 'max:191'],
            'widget_partner_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['required', 'string', 'max:191'],
            'origin_name' => ['required', 'string', 'max:191'],
            'origin_email' => ['required', 'email', 'max:191'],
            'origin_phone' => ['required', 'string', 'max:50'],
            'allow_return' => ['required', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $settings) {
            if (trim((string) $request->input('client_secret')) === ''
                && $settings->get()['client_secret'] === '') {
                $validator->errors()->add('client_secret', 'Upišite Box Now Client Secret.');
            }
        });

        $validated = $validator->validate();
        $validated['allow_return'] = $request->boolean('allow_return');

        if ($settings->save($validated)) {
            return redirect()
                ->route('shippings')
                ->with('success', 'Box Now API postavke su spremljene.');
        }

        return redirect()
            ->route('shippings')
            ->withInput()
            ->with('error', 'Box Now API postavke nije moguće spremiti.');
    }
}

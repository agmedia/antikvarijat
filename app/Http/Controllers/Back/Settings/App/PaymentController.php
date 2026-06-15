<?php

namespace App\Http\Controllers\Back\Settings\App;

use App\Http\Controllers\Controller;
use App\Models\Back\Settings\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->checkForNewFiles();

        $payments  = Settings::getList('payment', 'list.%', false)->sortBy('title');
        $geo_zones = Settings::getList('geo_zone', 'list', false);

        return view('back.settings.app.payment.payment', compact('payments', 'geo_zones'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->normalizePayload($request->input('data', []));

        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:191'],
            'title_en' => ['nullable', 'string', 'max:191'],
            'code' => ['required', 'string', 'alpha_dash', Rule::in($this->availablePaymentCodes())],
            'min' => ['nullable', 'numeric', 'min:0'],
            'geo_zone' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'data' => ['required', 'array'],
            'data.price' => ['nullable', 'numeric', 'min:0'],
            'data.short_description' => ['nullable', 'string', 'max:500'],
            'data.short_description_en' => ['nullable', 'string', 'max:500'],
            'data.description' => ['nullable', 'string'],
            'data.description_en' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $updated = Settings::setListItem('payment', 'list.' . $data['code'], $data);

        if ($updated) {
            Cache::forget('payment_list');

            return response()->json(['success' => 'Način plaćanja je uspješno snimljen.']);
        }

        return response()->json(['message' => 'Server error! Pokušajte ponovo ili kontaktirajte administratora!']);
    }

    private function normalizePayload(array $data): array
    {
        $data = $this->emptyStringsToNull($data);
        $data['data'] = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

        foreach (['title_en'] as $field) {
            $data[$field] = $data[$field] ?? null;
        }

        foreach (['price', 'short_description', 'short_description_en', 'description', 'description_en'] as $field) {
            $data['data'][$field] = $data['data'][$field] ?? null;
        }

        $data['status'] = filter_var($data['status'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function emptyStringsToNull(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->emptyStringsToNull($value);
            } elseif ($value === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function availablePaymentCodes(): array
    {
        return array_keys(config('settings.payment.providers', []));
    }


    /**
     * Check for new files in ..payment/modals directory.
     * Install payment if new files exist.
     */
    private function checkForNewFiles(): void
    {
        $files              = new \DirectoryIterator(resource_path('views/back/settings/app/payment/modals'));
        $accepted_providers = collect(config('settings.payment.providers'))->keys()->toArray();

        foreach ($files as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '.blade.php') !== false) {
                $filename = str_replace('.blade.php', '', $file->getFilename());
                $exist    = false;

                if ( ! in_array($filename, $accepted_providers)) {
                    Settings::erase('payment', 'list.' . $filename);

                } else {
                    $payment = collect(Settings::get('payment', 'list.' . $filename));

                    if ($payment) {
                        $exist = $payment->where('code', $filename)->first();
                    }

                    if ( ! $exist) {
                        $default_value = [
                            'title'      => $filename,
                            'title_en'   => null,
                            'code'       => $filename,
                            'data'       => [
                                'description' => '',
                                'description_en' => null,
                                'short_description_en' => null,
                            ],
                            'sort_order' => 0,
                            'status'     => 0
                        ];

                        Settings::set('payment', 'list.' . $filename, $default_value);
                    }
                }

            }
        }
    }
}

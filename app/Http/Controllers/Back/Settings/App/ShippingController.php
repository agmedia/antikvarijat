<?php

namespace App\Http\Controllers\Back\Settings\App;

use App\Http\Controllers\Controller;
use App\Models\Back\Settings\Faq;
use App\Models\Back\Settings\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShippingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->checkForNewFiles();

        $shippings = Settings::getList('shipping', 'list.%', false);
        $geo_zones = Settings::getList('geo_zone', 'list', false);

        //dd($geo_zones);

        return view('back.settings.app.shipping.shipping', compact('shippings', 'geo_zones'));
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
            'code' => ['required', 'string', 'alpha_dash', Rule::in($this->availableShippingCodes())],
            'geo_zone' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'data' => ['required', 'array'],
            'data.price' => ['required', 'numeric', 'min:0'],
            'data.time' => ['nullable', 'string', 'max:191'],
            'data.time_en' => ['nullable', 'string', 'max:191'],
            'data.short_description' => ['nullable', 'string', 'max:500'],
            'data.short_description_en' => ['nullable', 'string', 'max:500'],
            'data.description' => ['nullable', 'string'],
            'data.description_en' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $updated = Settings::setListItem('shipping', 'list.' . $data['code'], $data);

        if ($updated) {
            return response()->json(['success' => 'Način dostave je uspješno snimljen.']);
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

        foreach (['price', 'time', 'time_en', 'short_description', 'short_description_en', 'description', 'description_en'] as $field) {
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

    private function availableShippingCodes(): array
    {
        $codes = [];
        $files = new \DirectoryIterator(resource_path('views/back/settings/app/shipping/modals'));

        foreach ($files as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '.blade.php') !== false) {
                $codes[] = str_replace('.blade.php', '', $file->getFilename());
            }
        }

        return $codes;
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Faq $faq)
    {
        $destroyed = Faq::destroy($faq->id);

        if ($destroyed) {
            return redirect()->route('faqs')->with(['success' => 'Faq was succesfully deleted!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error deleting the faq.']);
    }


    /**
     * Check for new files in ..payment/modals directory.
     * Install payment if new files exist.
     */
    private function checkForNewFiles(): void
    {
        $files = new \DirectoryIterator(resource_path('views/back/settings/app/shipping/modals'));

        foreach ($files as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '.blade.php') !== false) {
                $filename = str_replace('.blade.php', '', $file->getFilename());
                $exist = false;

                $shipping = collect(Settings::get('shipping', 'list.' . $filename));

                if ($shipping) {
                    $exist = $shipping->where('code', $filename)->first();
                }

                if ( ! $exist) {
                    $default_value = [
                        'title' => $filename,
                        'title_en' => null,
                        'code' => $filename,
                        'data' => [
                            'price' => 0,
                            'time_en' => null,
                            'short_description_en' => null,
                            'description_en' => null,
                        ],
                        'geo_zone' => '0',
                        'sort_order' => 0,
                        'status' => false
                    ];

                    Settings::set('shipping', 'list.' . $filename, $default_value);
                }

            }
        }
    }
}

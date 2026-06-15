<?php

namespace App\Http\Controllers\Back\Settings\App;

use App\Http\Controllers\Controller;
use App\Models\Back\Settings\Faq;
use App\Models\Back\Settings\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $statuses = Settings::get('order', 'statuses')->sortBy('sort_order');

        return view('back.settings.app.order_status', compact('statuses'));
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
            'id' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:191'],
            'title_en' => ['nullable', 'string', 'max:191'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', Rule::in($this->availableColors())],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $setting = Settings::where('code', 'order')->where('key', 'statuses')->first();

        $values = collect();

        if ($setting) {
            $values = collect(json_decode($setting->value));
        }

        if ( ! $data['id']) {
            $data['id'] = $values->count() + 1;
            $values->push($data);
        }
        else {
            $values->where('id', $data['id'])->map(function ($item) use ($data) {
                $item->title = $data['title'];
                $item->title_en = $data['title_en'] ?? null;
                $item->sort_order = $data['sort_order'];
                $item->color = isset($data['color']) && $data['color'] ? $data['color'] : 'primary';

                return $item;
            });
        }

        if ( ! $setting) {
            $stored = Settings::insert('order', 'statuses', $values->toJson(), true);
        } else {
            $stored = Settings::edit($setting->id, 'order', 'statuses', $values->toJson(), true);
        }

        if ($stored) {
            return response()->json(['success' => 'Status narudžbe je uspješno snimljen.']);
        }

        return response()->json(['message' => 'Server error! Pokušajte ponovo ili kontaktirajte administratora!']);
    }

    private function normalizePayload(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        $data['id'] = $data['id'] ?? null;
        $data['title_en'] = $data['title_en'] ?? null;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['color'] = $data['color'] ?? 'primary';

        return $data;
    }

    private function availableColors(): array
    {
        return ['primary', 'secondary', 'success', 'info', 'light', 'danger', 'warning', 'dark'];
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $data = $request->data;

        if ($data['id']) {
            $setting = Settings::where('code', 'order')->where('key', 'statuses')->first();

            $values = collect(json_decode($setting->value));

            $new_values = $values->reject(function ($item) use ($data) {
                return $item->id == $data['id'];
            });

            $stored = Settings::edit($setting->id, 'order', 'statuses', $new_values->toJson(), true);
        }

        if ($stored) {
            return response()->json(['success' => 'Status narudžbe je uspješno obrisan.']);
        }

        return response()->json(['message' => 'Server error! Pokušajte ponovo ili kontaktirajte administratora!']);
    }
}

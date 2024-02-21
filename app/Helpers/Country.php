<?php


namespace App\Helpers;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Country
{

    /**
     * @return Collection
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function list(): Collection
    {
        $countries = Storage::disk('assets')->get('country.json');
        $countries = json_decode($countries, true);
        $response = [];

        foreach ($countries as $key => $country) {
            if ($country['iso_code_2'] == 'HR') {
                array_push($response, $country);
                unset($countries[$key]);
            }
        }

        $response = $response + $countries;

        return collect($response);
    }


    /**
     * @param null $id
     *
     * @return Collection
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function zones($id = null): Collection
    {
        $zones = Storage::disk('assets')->get('zone.json');

        if ($id) {
            return collect(json_decode($zones))->where('country_id', $id);
        }

        return collect(json_decode($zones));
    }
}

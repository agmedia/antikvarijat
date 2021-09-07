<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Publisher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Import
{

    /**
     * @param array $data
     *
     * @return array|null[]
     */
    public function setAttributes(array $data): array
    {
        $list_attributes = ['Autor', 'Izdavač', 'Dimenzije', 'Broj stranica', 'Godina izdavanja', 'Mjesto izdavanja', 'Uvez', 'Pismo', 'Stanje'];
        $attributes = [];

        foreach ($list_attributes as $key) {
            $attributes[$key] = null;
        }

        if (in_array($data['AN'], $list_attributes)) {
            $attributes[$data['AN']] = $data['AO'];
        }
        if (in_array($data['AR'], $list_attributes)) {
            $attributes[$data['AR']] = $data['AS'];
        }
        if (in_array($data['AV'], $list_attributes)) {
            $attributes[$data['AV']] = $data['AW'];
        }
        if (in_array($data['AZ'], $list_attributes)) {
            $attributes[$data['AZ']] = $data['BA'];
        }
        if (in_array($data['BD'], $list_attributes)) {
            $attributes[$data['BD']] = $data['BE'];
        }
        if (in_array($data['BH'], $list_attributes)) {
            $attributes[$data['BH']] = $data['BI'];
        }
        if (in_array($data['BL'], $list_attributes)) {
            $attributes[$data['BL']] = $data['BM'];
        }
        if (in_array($data['BP'], $list_attributes)) {
            $attributes[$data['BP']] = $data['BQ'];
        }
        if (in_array($data['CK'], $list_attributes)) {
            $attributes[$data['CK']] = $data['CL'];
        }

        return [
            'author'     => $attributes['Autor'],
            'publisher'  => $attributes['Izdavač'],
            'pages'      => $attributes['Broj stranica'],
            'dimensions' => $attributes['Dimenzije'],
            'origin'     => $attributes['Mjesto izdavanja'],
            'letter'     => $attributes['Pismo'],
            'condition'  => $attributes['Stanje'],
            'binding'    => $attributes['Uvez'],
            'year'       => $attributes['Godina izdavanja'],
        ];
    }


    /**
     * @param array $images
     *
     * @return array
     */
    public function resolveImages(array $images, string $name): array
    {
        $response = [];

        foreach ($images as $image) {
            $img = Image::make($image);
            $data = str_replace('jpeg', 'jpg', $img->exif('MimeType'));
            $type = str_replace('image/', '', $data);

            $path = config('filesystems.disks.products.url') . $name . '.' . $type;

            $img->save($path);

            $response[] = $path;
        }

        return $response;
    }


    /**
     * @param array $categories
     *
     * @return int|mixed
     */
    public function resolveCategories(array $categories)
    {
        $response = [];
        $data = [];

        foreach ($categories as $category) {
            $category = $this->replaceNames($category);
            $data = array_merge($data, explode(' > ', $category));
        }

        $data = array_unique($data);
        $parent = 0;

        for ($i = 0; $i < count($data); $i++) {
            if (strpos($data[$i], '?') == false && $data[$i] != 'Knjige') {
                $exist = Category::where('title', $data[$i])->first();

                if ( ! $exist) {
                    $id = Category::insertGetId([
                        'parent_id'        => $parent,
                        'title'            => $data[$i],
                        'description'      => '',
                        'meta_title'       => $data[$i],
                        'meta_description' => $data[$i],
                        'group'            => $data[0],
                        'lang'             => 'hr',
                        'status'           => 1,
                        'slug'             => Str::slug($data[$i]),
                        'created_at'       => Carbon::now(),
                        'updated_at'       => Carbon::now()
                    ]);

                    $parent = $id;

                    $response[] = $id;
                } else {
                    $response[] = $exist->id;
                }
            }
        }

        return collect($response)->last();
    }


    /**
     * @param string $author
     *
     * @return int
     */
    public function resolveAuthor(string $author = null): int
    {
        if ($author) {
            $exist = Author::where('title', $author)->first();

            if ( ! $exist) {
                return Author::insertGetId([
                    'title'            => $author,
                    'description'      => '',
                    'meta_title'       => $author,
                    'meta_description' => '',
                    'lang'             => 'hr',
                    'sort_order'       => 0,
                    'status'           => 1,
                    'slug'             => Str::slug($author),
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now()
                ]);
            }

            return $exist->id;
        }

        return 0;
    }


    /**
     * @param string $publisher
     *
     * @return int
     */
    public function resolvePublisher(string $publisher = null): int
    {
        if ($publisher) {
            $exist = Publisher::where('title', $publisher)->first();

            if ( ! $exist) {
                return Author::insertGetId([
                    'title'            => $publisher,
                    'description'      => '',
                    'meta_title'       => $publisher,
                    'meta_description' => '',
                    'lang'             => 'hr',
                    'sort_order'       => 0,
                    'status'           => 1,
                    'slug'             => Str::slug($publisher),
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now()
                ]);
            }

            return $exist->id;
        }

        return 0;
    }


    /**
     * @param string $text
     *
     * @return string
     */
    private function replaceNames(string $text): string
    {
        $text = str_replace('Knji?evnost', 'Književnost', $text);

        return $text;
    }
}
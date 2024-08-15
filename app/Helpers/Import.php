<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Publisher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Import
{


    /**
     * @param array $images
     *
     * @return array
     */
    public function resolveImages(array $images, string $name, int $id): array
    {
        $response = [];
        // Log::info($images);

        foreach ($images as $image) {
            if ($image) {
                $time = time();

                try {
                    $img = Image::make($image);
                } catch (\Exception $e) {
                    //not throwing  error when exception occurs
                }

                $str = $id . '/' . Str::limit(Str::slug($name)) . '-' . $time . '.';

                $path = $str . 'jpg';
                Storage::disk('products')->put($path, $img->encode('jpg'));

                $path_webp = $str . 'webp';
                Storage::disk('products')->put($path_webp, $img->encode('webp'));

                // Thumb creation
                $str_thumb = $id . '/' . Str::limit(Str::slug($name)) . '-' . $time . '-thumb.';

                $img = $img->resize(null, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->resizeCanvas(250, null);

                $path_webp_thumb = $str_thumb . 'webp';
                Storage::disk('products')->put($path_webp_thumb, $img->encode('webp'));

                $response[] = config('filesystems.disks.products.url') . $path;
            }
        }

        return $response;
    }


    /**
     * @param array  $images
     * @param string $name
     * @param int    $id
     *
     * @return array
     */
    public function resolveImagesImport(array $images, string $name, int $id): array
    {
        $response = [];

        foreach ($images as $key => $image) {
            if ($image) {
                $time = time() . Str::random(9);

                $image_saved = Storage::disk('local')->put('temp/' . $key . '.jpg', file_get_contents($image));

                if ($image_saved) {
                    try {
                        $image = Storage::disk('local')->get('temp/' . $key . '.jpg');
                        $img = Image::make($image);
                    } catch (\Exception $e) {
                        Log::info('Error downloading image: ' . $image);
                        Log::info($e->getMessage());
                    }

                    $str = $id . '/' . Str::limit(Str::slug($name)) . '-' . $time . '.';

                    $path = $str . 'jpg';
                    Storage::disk('products')->put($path, $img->encode('jpg'));

                    $path_webp = $str . 'webp';
                    Storage::disk('products')->put($path_webp, $img->encode('webp'));

                    // Thumb creation
                    $str_thumb = $id . '/' . Str::limit(Str::slug($name)) . '-' . $time . '-thumb.';

                    $img = $img->resize(null, 300, function ($constraint) {
                        $constraint->aspectRatio();
                    })->fit(250, 300);

                    $path_webp_thumb = $str_thumb . 'webp';
                    Storage::disk('products')->put($path_webp_thumb, $img->encode('webp'));

                    $response[] = config('filesystems.disks.products.url') . $path;

                    Storage::disk('local')->delete('temp/' . $key . '.jpg');
                }
            }
        }

        return $response;
    }


    /**
     * @param array $categories
     *
     * @return int|mixed
     */
    public function resolveStringCategories(string $categories)
    {
        $default = config('settings.default_category');
        $response[] = $default;

        $categories = explode(', ', $categories);

        if ( ! isset($categories[1])) {
            $response[] = $this->saveCategory($categories[0]);
        } else {
            $response[] = $this->saveCategory($categories[0]);
            $response[] = $this->saveCategory($categories[1]);
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
            if (isset($data[$i])) {

                if (strpos($data[$i], '?') == false && ! in_array($data[$i], ['Knjige', 'Zemljovidi i vedute','Vedute'])) {
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
                        $parent = $exist->id;
                        $response[] = $exist->id;
                    }
                }
            }
        }

        if (empty($response)) {
            $response[] = 1;
        }

        return $response;
    }

    /**
     * @param string $name
     * @param int    $parent
     *
     * @return mixed
     */
    private function saveCategory(string $name, int $parent = 0)
    {
        $exist = Category::where('title', $name)->first();

        if ( ! $exist) {
            return Category::insertGetId([
                'parent_id'        => $parent,
                'title'            => $name,
                'description'      => '',
                'meta_title'       => $name,
                'meta_description' => $name,
                'group'            => Helper::categoryGroupPath(true),
                'lang'             => 'hr',
                'status'           => 1,
                'slug'             => Str::slug($name),
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
            ]);
        }

        return $exist->id;
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
                    'url'              => config('settings.author_path') . '/' . Str::slug($author),
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
                return Publisher::insertGetId([
                    'title'            => $publisher,
                    'description'      => '',
                    'meta_title'       => $publisher,
                    'meta_description' => '',
                    'lang'             => 'hr',
                    'sort_order'       => 0,
                    'status'           => 1,
                    'slug'             => Str::slug($publisher),
                    'url'              => config('settings.publisher_path') . '/' . Str::slug($publisher),
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
        $text = str_replace('Kazali?te', 'Kazalište', $text);
        $text = str_replace('knji?evnosti', 'književnosti', $text);

        return $text;
    }
}

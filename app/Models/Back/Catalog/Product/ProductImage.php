<?php

namespace App\Models\Back\Catalog\Product;

use App\Helpers\ProductHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Intervention\Image\Facades\Image;

class ProductImage extends Model
{
    public const MAX_NEW_IMAGES_PER_REQUEST = 10;
    public const MAX_UPLOAD_BYTES = 6291456;
    public const MAX_UPLOAD_WIDTH = 2000;
    public const MAX_UPLOAD_HEIGHT = 2000;
    public const MAX_UPLOAD_PIXELS = 4000000;
    public const STORED_MAX_WIDTH = 1600;
    public const STORED_MAX_HEIGHT = 2000;

    /**
     * @var string
     */
    protected $table = 'product_images';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Model
     */
    protected $resource;

    /**
     * @param $resource
     * @param $request
     *
     * @return mixed
     */
    public function store($resource, $request)
    {
        $this->resource = $resource;

        // Uvijek normaliziraj u asocijativne arraye da izbjegnemo mix objekt/array pristup
        $existing = $request->input('slim', null);
        $new      = $request->input('files', null);

        if (is_object($existing)) { $existing = json_decode(json_encode($existing), true); }
        if (is_object($new))      { $new      = json_decode(json_encode($new), true);   }

        // Ako ima novih slika
        if (!empty($new)) {
            foreach ($new as $key => $new_image) {
                if (isset($new_image['image']) && $new_image['image']) {
                    $data = json_decode($new_image['image']); // stdClass
                    if ($data && isset($data->output)) {
                        $saved = $this->saveNew($data->output, $new_image['sort_order'] ?? 0);

                        // Ako je default označen na novouploadanoj fotki
                        $isDefaultByIndex = isset($new['default']) && (string) $new['default'] === (string) $key;
                        $isDefaultByLegacyName = isset($new['default'])
                            && strpos((string) $new['default'], 'image/') !== false
                            && isset($data->output->name)
                            && $data->output->name == str_replace('image/', '', $new['default']);

                        if ($isDefaultByIndex || $isDefaultByLegacyName) {
                            $this->switchDefault($saved);
                        }
                    }
                }
            }
        }

        if (!empty($existing)) {
            // Ako se mijenja default i nismo ga već promijenili...
            if (isset($existing['default']) && $existing['default'] != 'on') {
                $newDefault = $this->where('id', $existing['default'])->first();
                if ($newDefault) {
                    $this->switchDefault($newDefault);
                }
            }

            foreach ($existing as $key => $image) {
                // preskoči specijalni ključ 'default'
                if ($key === 'default') {
                    continue;
                }

                // Ako je poslan novi crop za postojeću sliku
                if (is_array($image) && isset($image['image']) && $image['image']) {
                    $data = json_decode($image['image']); // stdClass
                    if ($data && isset($data->output)) {
                        $this->replace($key, $data->output, $image['title'] ?? '');
                    }
                }

                // Naslov glavne (key 0 ili falsy) – koristi title iz forme ili input('title')
                if (!$key) {
                    $mainTitle = (string)($image['title'] ?? $request->input('title') ?? '');
                    $this->saveMainTitle($mainTitle);
                }

                // Update metapodataka za svaku postojeću (osim 'default')
                if ($key && $key !== 'default' && is_array($image)) {
                    $published = (!empty($image['published']) && $image['published'] === 'on') ? 1 : 0;

                    $this->where('id', $key)->update([
                        'alt'        => $image['alt']        ?? null,
                        'sort_order' => $image['sort_order'] ?? 0,
                        'published'  => $published
                    ]);

                    $this->saveTitle($key, (string)($image['title'] ?? ''));
                }
            }
        }

        return $this->where('product_id', $this->resource->id)->get();
    }

    /**
     * @param $id
     * @param $new
     * @param string $title
     *
     * @return mixed
     */
    public function replace($id, $new, $title)
    {
        // Nađi staru sliku i izdvoji path
        $old  = $id ? $this->where('id', $id)->first() : $this->resource;
        $path = str_replace('media/images/gallery/products/', '', $old['image']);
        // Obriši staru sliku
        Storage::disk('products')->delete($path);

        $path = $this->saveImage($new->image, $title);

        // Ako nije glavna slika updejtaj path na product_images DB
        if ($id) {
            return $this->where('id', $id)->update([
                'image' => config('filesystems.disks.products.url') . $path
            ]);
        }

        return Product::where('id', $this->resource->id)->update([
            'image' => config('filesystems.disks.products.url') . $path
        ]);
    }

    /**
     * @param $new
     *
     * @return mixed
     */
    public function switchDefault($new)
    {
        if (isset($new->id)) {
            if ($this->resource->image) {
                $this->where('id', $new->id)->update([
                    'image' => $this->resource->image
                ]);
            } else {
                $this->where('id', $new->id)->delete();
            }

            Product::where('id', $this->resource->id)->update([
                'image' => $new->image
            ]);
        }

        return $new;
    }

    /**
     * @param $new
     *
     * @return mixed
     */
    public function saveNew($new, $sort_order = 0)
    {
        $path = $this->saveImage($new->image);

        // Store image in product_images DB (query builder je ok ovdje)
        $id = $this->insertGetId([
            'product_id' => $this->resource->id,
            'image'      => config('filesystems.disks.products.url') . $path,
            'alt'        => $this->resource->name,
            'published'  => 1,
            'sort_order' => $sort_order,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        return $this->find($id);
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param string $title
     */
    private function saveMainTitle(string $title/*, string $alt*/)
    {
        $existing_clean = ProductHelper::getCleanImageTitle($this->resource->image);

        if ($existing_clean != $title) {
            $path          = $this->resource->id . '/';
            $existing_full = ProductHelper::getFullImageTitle($this->resource->image);
            $new_full      = ProductHelper::setFullImageTitle($title);

            Storage::disk('products')->move($path . $existing_full . '.jpg', $path . $new_full . '.jpg');
            Storage::disk('products')->move($path . $existing_full . '.webp', $path . $new_full . '.webp');
            Storage::disk('products')->move($path . $existing_full . '-thumb.webp', $path . $new_full . '-thumb.webp');

            Product::where('id', $this->resource->id)->update([
                'image' => config('filesystems.disks.products.url') . $path . $new_full . '.jpg'
            ]);
        }

        /*Product::where('id', $this->resource->id)->update([
            'image_alt' => $alt
        ]);*/
    }

    /**
     * @param int    $id
     * @param string $title
     */
    private function saveTitle(int $id, string $title)
    {
        $resource = $this->where('id', $id)->first();

        if ($resource && isset($resource->image)) {
            $existing_clean = ProductHelper::getCleanImageTitle($resource->image);

            if ($existing_clean != $title) {
                $path          = $this->resource->id . '/';
                $existing_full = ProductHelper::getFullImageTitle($resource->image);
                $new_full      = ProductHelper::setFullImageTitle($title);

                Storage::disk('products')->move($path . $existing_full . '.jpg', $path . $new_full . '.jpg');
                Storage::disk('products')->move($path . $existing_full . '.webp', $path . $new_full . '.webp');
                Storage::disk('products')->move($path . $existing_full . '-thumb.webp', $path . $new_full . '-thumb.webp');

                $this->where('id', $id)->update([
                    'image' => config('filesystems.disks.products.url') . $path . $new_full . '.jpg'
                ]);
            }
        }
    }

    /**
     * @param $image
     * @param string|null $title
     *
     * @return string
     */
    private function saveImage($image, $title = null)
    {
        if (!$title) {
            $title = $this->resource->name;
        }

        $time = Str::random(4);
        $img  = Image::make($this->makeImageFromBase($image));

        // A phone photo can be small on disk but require hundreds of MB once
        // decoded. Keep enough detail for zoom without encoding that full canvas
        // three times in this synchronous request.
        $img->resize(self::STORED_MAX_WIDTH, self::STORED_MAX_HEIGHT, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $path = $this->resource->id . '/' . Str::slug($this->resource->name) . '-' . $time . '.';

        $path_jpg = $path . 'jpg';
        Storage::disk('products')->put($path_jpg, (string) $img->encode('jpg', 85));

        $path_webp = $path . 'webp';
        Storage::disk('products')->put($path_webp, (string) $img->encode('webp', 82));

        // Thumb creation
        $path_thumb = $this->resource->id . '/' . Str::slug($this->resource->name) . '-' . $time . '-thumb.';

        $img = $img->resize(null, 300, function ($constraint) {
            $constraint->aspectRatio();
        })->resizeCanvas(250, null);

        $path_webp_thumb = $path_thumb . 'webp';
        Storage::disk('products')->put($path_webp_thumb, (string) $img->encode('webp', 82));

        return $path_jpg;
    }

    /**
     * Validate Slim payloads before the product or its relations are saved.
     *
     * @throws ValidationException
     */
    public static function validateRequestImages(Request $request): void
    {
        $errors = [];
        $newImages = $request->input('files', []);
        $newImageCount = 0;

        if (is_array($newImages)) {
            foreach ($newImages as $image) {
                if (is_array($image) && ! empty($image['image'])) {
                    $newImageCount++;
                }
            }
        }

        if ($newImageCount > self::MAX_NEW_IMAGES_PER_REQUEST) {
            $errors['files'][] = 'Odjednom možete dodati najviše ' . self::MAX_NEW_IMAGES_PER_REQUEST . ' fotografija.';
        }

        foreach (['files', 'slim'] as $group) {
            $images = $request->input($group, []);

            if (! is_array($images)) {
                continue;
            }

            foreach ($images as $key => $image) {
                if (! is_array($image) || empty($image['image'])) {
                    continue;
                }

                try {
                    self::decodeSlimPayload((string) $image['image']);
                } catch (InvalidArgumentException $exception) {
                    $errors[$group . '.' . $key . '.image'][] = $exception->getMessage();
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Decode and inspect the JSON value generated by Slim.
     */
    private static function decodeSlimPayload(string $payload): string
    {
        // 6 MiB of binary data expands to roughly 8 MiB in Base64.
        if (strlen($payload) > 8500000) {
            throw new InvalidArgumentException('Fotografija je prevelika za obradu. Smanjite je i pokušajte ponovno.');
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ! isset($data['output']['image']) || ! is_string($data['output']['image'])) {
            throw new InvalidArgumentException('Fotografija nije ispravno pripremljena. Učitajte je ponovno.');
        }

        return self::decodeImageDataUri($data['output']['image']);
    }

    /**
     * Strictly decode a supported data URI before GD allocates its canvas.
     */
    private static function decodeImageDataUri(string $dataUri): string
    {
        $separator = strpos($dataUri, ',');
        $header = $separator === false ? '' : substr($dataUri, 0, $separator);

        if (! preg_match('/\Adata:(image\/(?:jpeg|jpg|png|webp|gif));base64\z/i', $header, $matches)) {
            throw new InvalidArgumentException('Format fotografije nije podržan. Koristite JPG, PNG ili WebP.');
        }

        $encoded = preg_replace('/\s+/', '', substr($dataUri, $separator + 1));
        $maxEncodedLength = (int) ceil(self::MAX_UPLOAD_BYTES / 3) * 4;

        if (! is_string($encoded) || strlen($encoded) > $maxEncodedLength) {
            throw new InvalidArgumentException('Fotografija je prevelika za obradu. Smanjite je i pokušajte ponovno.');
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false || strlen($decoded) > self::MAX_UPLOAD_BYTES) {
            throw new InvalidArgumentException('Fotografija sadrži neispravne podatke. Učitajte je ponovno.');
        }

        $info = @getimagesizefromstring($decoded);
        $claimedMime = strtolower($matches[1]) === 'image/jpg' ? 'image/jpeg' : strtolower($matches[1]);
        $actualMime = is_array($info) ? strtolower((string) ($info['mime'] ?? '')) : '';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (! is_array($info) || ! in_array($actualMime, $allowedMimes, true) || $actualMime !== $claimedMime) {
            throw new InvalidArgumentException('Sadržaj fotografije ne odgovara odabranom formatu.');
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);

        if (
            $width < 1 ||
            $height < 1 ||
            $width > self::MAX_UPLOAD_WIDTH ||
            $height > self::MAX_UPLOAD_HEIGHT ||
            ($width * $height) > self::MAX_UPLOAD_PIXELS
        ) {
            throw new InvalidArgumentException(
                'Fotografija ima preveliku rezoluciju. Ponovno je odaberite kako bi se automatski smanjila.'
            );
        }

        return $decoded;
    }

    /**
     * @param string $base_64_string
     *
     * @return false|string
     */
    private function makeImageFromBase(string $base_64_string)
    {
        return self::decodeImageDataUri($base_64_string);
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param int $product_id
     *
     * @return Collection
     */
    public static function getAdminList(int $product_id = null): Collection
    {
        $response = [];

        if ($product_id) {
            $images = self::where('product_id', $product_id)->orderBy('sort_order')->get();

            foreach ($images as $image) {
                $response[] = [
                    'id'         => $image->id,
                    'product_id' => $image->product_id,
                    'image'      => $image->image,
                    'title'      => ProductHelper::getCleanImageTitle($image->image),
                    'alt'        => $image->alt,
                    'published'  => $image->published,
                    'sort_order' => $image->sort_order,
                ];
            }
        }

        return collect($response);
    }

    /**
     * Save stack of images to the product_images database.
     *
     * @param array $paths
     * @param       $product_id
     *
     * @return array|bool
     */
    public static function saveStack(array $paths, $product_id)
    {
        $images = [];

        foreach ($paths as $key => $path) {
            $images[] = self::create([
                'product_id' => $product_id,
                'image'      => $path,
                'sort_order' => $key,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }

        return !empty($images) ? $images : false;
    }

    /**
     * Save temporary stored images to newly saved product folder.
     * The folder is based on product ID.
     *
     * @param array $paths
     * @param       $product_id
     *
     * @return array|bool
     */
    public static function transferTemporaryImages(array $paths, $product_id)
    {
        $targets = [];

        foreach ($paths as $key => $path) {
            $target    = str_replace('temp', $product_id, $path);
            $targets[] = $target;

            if ($key == 0) {
                self::setDefault($target, $product_id);
            }

            $_path   = str_replace(config('filesystems.disks.products.url'), '', $path);
            $_target = str_replace(config('filesystems.disks.products.url'), '', $target);

            Storage::disk('products')->move($_path, $_target);
            Storage::disk('products')->delete($_path);
        }

        return self::saveStack($targets, $product_id);
    }

    /**
     * Set default product image.
     *
     * @param string $path
     * @param        $id
     *
     * @return mixed
     */
    public static function setDefault(string $path, $id)
    {
        return Product::where('id', $id)->update([
            'image' => $path
        ]);
    }
}

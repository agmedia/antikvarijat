<?php

namespace App\Models\Back\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Settings extends Model
{

    use HasFactory;

    /**
     * Request-scoped cache for repeated setting lookups.
     */
    protected static array $resolved = [];

    /**
     * Request-scoped cache for expanded settings lists.
     */
    protected static array $resolvedLists = [];

    /**
     * @var string
     */
    protected $table = 'settings';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param string $code
     * @param string $key
     *
     * @return false|Collection
     */
    public static function get(string $code, string $key)
    {
        $cacheKey = $code . '|' . $key;

        if (array_key_exists($cacheKey, static::$resolved)) {
            return static::$resolved[$cacheKey];
        }

        $styles = Settings::where('code', $code)->where('key', $key)->first();

        if ($styles) {
            if ($styles->json) {
                return static::$resolved[$cacheKey] = collect(json_decode($styles->value));
            }

            return static::$resolved[$cacheKey] = $styles->value;
        }

        return static::$resolved[$cacheKey] = collect();
    }


    /**
     * @param string $code
     * @param string $key
     *
     * @return false|Collection
     */
    public static function getList(string $code, string $key = 'list.%', bool $only_active = true)
    {
        $cacheKey = implode('|', [$code, $key, (int) $only_active]);

        if (array_key_exists($cacheKey, static::$resolvedLists)) {
            return static::$resolvedLists[$cacheKey];
        }

        $styles = Settings::where('code', $code)->where('key', 'like', $key)->get();

        if ($styles->count()) {
            $return_styles = collect();

            foreach ($styles as $style) {
                if ($style->json) {
                    $temp_style = collect(json_decode($style->value))->all();

                    foreach ($temp_style as $item) {
                        $return_styles->put($item->title, $item);
                    }
                }
            }

            if ($only_active) {
                return static::$resolvedLists[$cacheKey] = $return_styles->where('status')->sortBy('sort_order');
            }

            return static::$resolvedLists[$cacheKey] = $return_styles->sortBy('sort_order');
        }

        return static::$resolvedLists[$cacheKey] = [];
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param string $code
     * @param string $key
     * @param        $value
     * @param bool   $json
     *
     * @return bool|mixed
     */
    public static function set(string $code, string $key, $value, bool $json = true)
    {
        static::clearResolved();

        $setting = Settings::where('code', $code)->where('key', $key)->first();

        if ($setting) {
            if ($json) {
                $values = collect(json_decode($setting->value));

                if ( ! $values->contains($value)) {
                    $values->push($value);
                }

                $value = json_encode($values);
            }

            return self::edit($setting->id, $code, $key, $value, $json);
        }

        if ($json) {
            $values = [$value];

            $value = json_encode($values);
        }

        return self::insert($code, $key, $value, $json);
    }


    /**
     * @param string $code
     * @param string $key
     * @param        $value
     * @param bool   $json
     *
     * @return bool|mixed
     */
    public static function setListItem(string $code, string $key, $value)
    {
        static::clearResolved();

        $updated = false;
        $setting = Settings::where('code', $code)->where('key', $key)->first();

        if ($setting) {
            $updated = $setting->update([
                'value' => json_encode([$value])
            ]);
        }

        return $updated ?: false;
    }


    /**
     * @param string $key
     * @param mixed  $value
     * @param bool   $json
     *
     * @return mixed
     */
    public static function setProduct(string $key, $value, bool $json = true)
    {
        static::clearResolved();

        $styles = Settings::where('code', 'product')->where('key', $key)->first();

        if ($styles) {
            if ($json) {
                $values = collect(json_decode($styles->value));

                if ( ! $values->contains($value)) {
                    $values->push($value);
                }

                $value = json_encode($values);
            }

            return self::edit($styles->id, 'product', $key, $value, $json);
        }

        if ($json) {
            $values = [$value];

            $value = json_encode($values);
        }

        return self::insert('product', $key, $value, $json);
    }


    /**
     * @param string $code
     * @param string $key
     * @param        $value
     * @param bool   $json
     *
     * @return mixed
     */
    public static function insert(string $code, string $key, $value, bool $json)
    {
        static::clearResolved();

        return self::insertGetId([
            'code'       => $code,
            'key'        => $key,
            'value'      => $value,
            'json'       => $json,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }


    /**
     * @param int    $id
     * @param string $code
     * @param string $key
     * @param        $value
     * @param bool   $json
     *
     * @return bool
     */
    public static function edit(int $id, string $code, string $key, $value, bool $json)
    {
        static::clearResolved();

        return self::where('id', $id)->update([
            'code'       => $code,
            'key'        => $key,
            'value'      => $value,
            'json'       => $json,
            'updated_at' => Carbon::now()
        ]);
    }


    /**
     * @param string $code
     * @param string $key
     *
     * @return mixed
     */
    public static function erase(string $code, string $key)
    {
        static::clearResolved();

        return self::where('code', $code)->where('key', $key)->delete();
    }

    protected static function clearResolved(): void
    {
        static::$resolved = [];
        static::$resolvedLists = [];
    }
}

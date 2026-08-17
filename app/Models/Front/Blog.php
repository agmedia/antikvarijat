<?php

namespace App\Models\Front;

use App\Helpers\LocaleHelper;
use App\Models\Concerns\CachesRouteBinding;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Blog extends Model
{
    use CachesRouteBinding;

    /**
     * @var string
     */
    protected $table = 'pages';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $casts = [
        'hide_from_home_widget' => 'boolean',
        'recommendation_product_ids' => 'array',
    ];


    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getTitleAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'title', true);
    }

    public function getShortDescriptionAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'short_description', true);
    }

    public function getMetaTitleAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'meta_title', true);
    }

    public function getMetaDescriptionAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'meta_description', true);
    }

    public function getSlugAttribute($value)
    {
        return LocaleHelper::isEnglish() ? LocaleHelper::routeKey($this, LocaleHelper::ENGLISH_LOCALE) : $value;
    }


    /**
     * @param $value
     *
     * @return array|string|string[]
     */
    public function getImageAttribute($value)
    {
        $path = Helper::resolveOptimizedPublicImagePath($value);

        if (! $path) {
            return null;
        }

        return config('settings.images_domain') . ltrim($path, '/');
    }


    /**
     * @return string|null
     */
    public function getThumbAttribute($value)
    {
        $path = Helper::resolveOptimizedPublicImagePath($this->getRawOriginal('image'));

        if (! $path) {
            return null;
        }

        return Helper::imageThumbnailUrl($path, '600');
    }


    /**
     * @return string|null
     */
    public function getHeroAttribute($value)
    {
        $path = Helper::resolveOptimizedPublicImagePath($this->getRawOriginal('image'));

        if (! $path) {
            return null;
        }

        return Helper::imageThumbnailUrl($path, '1200x1200');
    }


    /**
     * @param $value
     *
     * @return array|string|string[]
     */
    public function getDescriptionAttribute($value)
    {
        $value = LocaleHelper::localizedField($this, 'description', true);

        return Helper::optimizeRichContentMedia(
            Helper::resolveYouTubeFrame((string) $value)
        );
    }


    /**
     *
     */
    protected static function booted()
    {
        static::addGlobalScope('blogs', function (Builder $builder) {
            $builder->where('group', 'blog');
        });
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1)->orderBy('created_at', 'desc');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeLast(Builder $query, $count = 9): Builder
    {
        return $query->orderBy('updated_at', 'desc')->limit($count);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePopular(Builder $query, $count = 9): Builder
    {
        return $query->orderBy('viewed', 'desc')->limit($count);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeFeatured(Builder $query, $count = 9): Builder
    {
        return $query->where('featured', 1)->orderBy('updated_at', 'desc')->limit($count);
    }
}

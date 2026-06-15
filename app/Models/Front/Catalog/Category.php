<?php

namespace App\Models\Front\Catalog;

use App\Helpers\LocaleHelper;
use App\Models\Concerns\CachesRouteBinding;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Category extends Model
{
    use CachesRouteBinding;
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'categories';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


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

    public function getDescriptionAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'description', true);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent()
    {
        return $this->hasOne(Category::class, 'id', 'parent_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function products()
    {
        return $this->hasManyThrough(Product::class, CategoryProducts::class, 'category_id', 'id', 'id', 'product_id')->where('status', 1)->orderBy('sort_order');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subcategories()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1)->where('title', '!=', '');
    }


    /**
     * @param Builder $query
     * @param string  $group
     *
     * @return Builder
     */
    public function scopeTopList(Builder $query, string $group = ''): Builder
    {
        if ( ! empty($group)) {
            return $query->where('group', $group)->where('parent_id', '==', 0);
        }

        return $query->where('parent_id', '==', 0);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeGroups(Builder $query): Builder
    {
        return $query->groupBy('group');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeSortByName(Builder $query): Builder
    {
        return $query->orderBy('title');
    }


    /**
     * @param Category|null $subcategory
     *
     * @return string
     */
    public function url(Category $subcategory = null)
    {
        return LocaleHelper::categoryUrl($this, $subcategory);
    }


    /**
     * @param bool $full
     *
     * @return Collection
     */
    public function getList(bool $full = true): Collection
    {
        $categories = collect();

        $groups = $this->groups()->pluck('group');

        foreach ($groups as $group) {
            if ($full) {
                $cats = $this->topList($group)->with('subcategories')->get();
            } else {
                $cats = [];
                $fill = $this->topList($group)->with('subcategories')->get();

                foreach ($fill as $cat) {
                    $cats[$cat->id] = ['title' => $cat->title];

                    if ($cat->subcategories) {
                        $subcats = [];

                        foreach ($cat->subcategories as $subcategory) {
                            $subcats[$subcategory->id] = ['title' => $subcategory->title];
                        }
                    }

                    $cats[$cat->id]['subs'] = $subcats;
                }
            }

            $categories->put($group, $cats);
        }

        return $categories;
    }

}

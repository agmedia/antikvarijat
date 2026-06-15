<?php

namespace App\Models\Front;

use App\Helpers\LocaleHelper;
use App\Models\Concerns\CachesRouteBinding;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Page extends Model
{

    use CachesRouteBinding;
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'pages';

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

    public function getShortDescriptionAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'short_description', true);
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
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeSubgroups(Builder $query): Builder
    {
        return $query->groupBy('subgroup')->whereNotNull('subgroup');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeGroups(Builder $query): Builder
    {
        return $query->groupBy('group')->whereNotNull('group');
    }
}

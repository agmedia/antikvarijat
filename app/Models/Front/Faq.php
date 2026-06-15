<?php

namespace App\Models\Front;

use App\Helpers\LocaleHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Faq extends Model
{

    /**
     * @var string
     */
    protected $table = 'faq';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getTitleAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'title', true);
    }

    public function getDescriptionAttribute($value)
    {
        return LocaleHelper::localizedField($this, 'description', true);
    }

}

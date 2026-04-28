<?php

namespace App\Models\Back\Marketing;

use App\Mail\WishlistArrived;
use App\Models\Front\Catalog\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Recaptcha;

class Wishlist extends Model
{

    /**
     * @var string
     */
    protected $table = 'wishlist';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('sent', 1);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->where('sent', 0);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeBasic(Builder $query): Builder
    {
        return $query->select('id', 'product_id', 'email');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Front\Catalog\Product::class, 'product_id');
    }



    /**
     * Validate new category Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->merge([
            'email' => static::normalizeEmail($request->input('email')),
        ]);

        $request->validate([
            'email'      => 'required|email|max:190',
            'product_id' => 'required|integer|exists:products,id',
            'recaptcha'  => 'required'
        ], [
            'email.required' => 'Polje za e-mail adresu je obavezno.',
            'email.email' => 'Unesite ispravnu e-mail adresu.',
            'product_id.required' => 'Proizvod nije odabran.',
            'product_id.integer' => 'Odabrani proizvod nije ispravan.',
            'product_id.exists' => 'Odabrani proizvod nije pronađen.',
            'recaptcha.required' => 'ReCaptcha provjera je obavezna.',
        ]);

        $this->request = $request;

        return $this;
    }


    /**
     * Store new category.
     *
     * @return false
     */
    public function create()
    {
        $email = static::normalizeEmail($this->request->email);

        // Provjeri postoji li već zapis za ovaj email i product_id
        $exists = static::where('email', $email)
            ->where('product_id', $this->request->product_id)
            ->exists();

        if ($exists) {
            // po želji vrati poruku da je korisnik već prijavljen na obavijest za taj artikl
            return false;
        }

        $id = $this->insertGetId([
            'user_id'    => auth()->guest() ? 0 : auth()->user()->id,
            'email'      => $email,
            'product_id' => $this->request->product_id,
            'sent'       => 0,
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id ? $this->find($id) : false;
    }


    /**
     * @return int
     */
    public static function check_CRON()
    {
        $log_start = microtime(true);

        $list = Wishlist::active()->unsent()->basic()->get();
        $invalidEntries = $list->reject(function ($entry) {
            return static::isValidEmail($entry->email);
        });

        foreach ($invalidEntries as $entry) {
            Log::warning('Skipping wishlist notification with invalid email address.', [
                'wishlist_id' => $entry->id,
                'product_id' => $entry->product_id,
                'email' => $entry->email,
            ]);

            static::query()->whereKey($entry->id)->delete();
        }

        $list = $list->filter(function ($entry) {
            return static::isValidEmail($entry->email);
        });
        $ids = $list->unique('product_id')->pluck('product_id');
        $products = Product::query()->whereIn('id', $ids)->available()->basicData()->get();

        foreach ($products as $product) {
            $emails = $list
                ->where('product_id', $product->id)
                ->groupBy(function ($entry) {
                    return static::normalizeEmail($entry->email);
                });

            foreach ($emails as $email => $entries) {
                dispatch(function () use ($email, $product) {
                    Mail::to($email)->send(new WishlistArrived($product));
                });

                static::query()->whereIn('id', $entries->pluck('id'))->delete();
            }
        }

        $log_end = microtime(true);
        Log::info('__Check Wishlist - Total Execution Time: ' . number_format(($log_end - $log_start), 2, ',', '.') . ' sec.');

        return 1;
    }


    protected static function normalizeEmail(?string $email): string
    {
        return trim((string) $email);
    }


    protected static function isValidEmail(?string $email): bool
    {
        $normalizedEmail = static::normalizeEmail($email);

        return $normalizedEmail !== '' && filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) !== false;
    }

}

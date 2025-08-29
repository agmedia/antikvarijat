<?php

namespace App\Models\Back\Orders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OrderTotal extends Model
{

    /**
     * @var string
     */
    protected $table = 'order_total';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    /**
     * @param $totals
     * @param $order_id
     *
     * @return bool
     */
    public static function store($totals, $order_id)
    {
        self::where('order_id', $order_id)->delete();

        // Ako je došao JSON objekata, prebacimo u uniformni pristup
        $i = 0;
        foreach ($totals as $t) {
            $code  = is_array($t) ? ($t['code']  ?? null) : ($t->code  ?? null);
            $title = is_array($t) ? ($t['title'] ?? $t['name'] ?? ($code ? ucfirst($code) : null))
                : ($t->title ?? $t->name ?? ($code ? ucfirst($code) : null));
            $value = is_array($t) ? ($t['value'] ?? 0) : ($t->value ?? 0);

            if ($code === null) {
                $i++;
                continue;
            }

            self::insertGetId([
                'order_id'   => $order_id,
                'code'       => $code,
                'title'      => $title,
                'value'      => $value,    // DECIMAL(15,4) prima i 55 i 55.0000
                'sort_order' => $i,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            if ($code === 'total') {
                Order::where('id', $order_id)->update(['total' => $value]);
            }



            $i++;
        }

        return true;
    }



    /**
     * @param $request
     * @param $order_id
     *
     * @return bool
     */
    public function make($request, $order_id)
    {
        $totals     = collect(config('settings.totals'))->where('status', 1)->sortBy('sort_order');
        $order_data = json_decode($request->order_data);

        foreach ($totals as $code => $total) {
            $value = $this->resolveTotalValue($order_data, $code);

            $this->insertGetId([
                'order_id'   => $order_id,
                'code'       => $code,
                'title'      => $total['title'],
                'value'      => $value,
                'sort_order' => $total['sort_order'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }

        Order::where('id', $order_id)->update([
            'total' => $order_data->total
        ]);

        return true;
    }


    /**
     * @param        $request
     * @param string $code
     *
     * @return false|float|int
     */
    public function resolveTotalValue($obj, string $code)
    {
        if ($code == 'subtotal') {
            return intval($obj->subtotal);
        }
        /*if ($code == 'nett') {
            return intval($obj->tax[0]->value);
        }*/
        if ($code == 'tax') {
            return intval($obj->tax[0]->value);
        }
        if ($code == 'total') {
            return intval($obj->total);
        }

        return false;
    }


    /**
     * @param $total
     * @param $action
     *
     * @return int
     */
    public static function resolveSortOrder($total, $action)
    {
        if ($total->code == 'subtotal') {
            return 0;
        }
        if ($action and $total->code == 'action') {
            return 1;
        }
        if ($total->code == 'shipping') {
            return $action ? 2 : 1;
        }
        if ($total->code == 'total') {
            return $action ? 3 : 2;
        }
    }


    /**
     * @param $totals
     *
     * @return bool
     */
    public static function hasAction($totals)
    {
        foreach ($totals as $total) {
            if ($total->code == 'action') {
                return true;
            }
        }

        return false;
    }

}

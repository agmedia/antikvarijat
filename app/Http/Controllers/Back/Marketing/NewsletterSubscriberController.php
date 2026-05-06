<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query()
            ->with(['user.details'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', '%' . $search . '%')
                    ->orWhere('order_id', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhereHas('details', function ($dq) use ($search) {
                                $dq->where('fname', 'like', '%' . $search . '%')
                                    ->orWhere('lname', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $subscribers = $query->paginate(30);

        return view('back.marketing.newsletter.index', compact('subscribers'));
    }

    public function clearCaches()
    {
        @set_time_limit(0);

        Artisan::call('optimize:clear');
        Cache::store('file')->flush();

        return back()->with('status', trim(Artisan::output()) ?: 'Laravel cache je očišćen.');
    }
}

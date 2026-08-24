<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Roles\Role;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = (new User())->newQuery();
        $search = trim((string) $request->input('search'));

        $roleLabels = [
            'admin' => 'Administrator',
            'editor' => 'Editor',
            'customer' => 'Kupac',
        ];

        $availableRoles = UserDetail::query()
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->pluck('role')
            ->filter()
            ->unique()
            ->sortBy(function ($role) {
                $position = array_search($role, ['admin', 'editor', 'customer'], true);

                return $position !== false ? $position : 100;
            });

        $roleOptions = $availableRoles->mapWithKeys(function ($role) use ($roleLabels) {
            return [$role => $roleLabels[$role] ?? ucfirst($role)];
        });

        if ($search !== '') {
            $query->where(function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role') && $roleOptions->has($request->input('role'))) {
            $query->whereHas('details', function ($detailsQuery) use ($request) {
                $detailsQuery->where('role', $request->input('role'));
            });
        }

        $users = $query
            ->with(['details', 'roles'])
            ->paginate(config('settings.pagination.back'))
            ->appends($request->query());

        return view('back.user.index', compact('users', 'roleOptions', 'roleLabels'));
    }
    
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::selectList();

        return view('back.user.edit', compact('roles'));
    }
    
    
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = new User();


    }
    
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles = Role::selectList();

        return view('back.user.edit', compact('user', 'roles'));
    }
    
    
    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param User                     $user
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $updated = $user->validateRequest($request)->edit();

        if ($updated) {
            return redirect()->route('users.edit', ['user' => $updated])->with(['success' => 'Korisnik je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Greška prilikom snimanja.']);
    }
    
    
    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {}
}

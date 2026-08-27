<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $perPage = min(max((int) $request->integer('per_page', 15), 5), 100);
        $search = trim((string) $request->query('search', ''));

        $query = User::query()->with('roles');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => Role::pluck('name')->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create(
            $request->only(['name', 'email']) + [
                'password' => Hash::make($request->password),
                'registration_source' => 'admin',
            ]
        );

        $roles = (array) $request->roles;
        if (in_array('Admin', $roles) || in_array('Super Admin', $roles)) {
            $roles = Role::pluck('name')->all();
        }
        $user->syncRoles($roles);

        return to_route('users.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('view', $user);

        return Inertia::render('Users/Show', [
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        return Inertia::render('Users/Update', [
            'user' => $user,
            'userRoles' => $user->roles()->pluck('name')->all(),
            'roles' => Role::pluck('name')->all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $roles = (array) $request->roles;
        if (in_array('Admin', $roles) || in_array('Super Admin', $roles)) {
            $roles = Role::pluck('name')->all();
        }
        $user->syncRoles($roles);

        return to_route('users.index');
    }

    /**
     * Display user category assignments.
     */
    public function categories(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $perPage = min(max((int) $request->integer('per_page', 10), 5), 100);
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->with('roles')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'categories' => $user->categoryPermissions()
                        ->withPivot('permission')
                        ->get()
                        ->map(fn ($cat) => [
                            'id' => $cat->id,
                            'title' => $cat->title,
                            'permission' => $cat->pivot->permission,
                        ]),
                ];
            });

        return Inertia::render('Users/UserCategories', [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    /**
     * Display user category assignment matrix.
     */
    public function categoryAssignments()
    {
        $this->authorize('assignCategories', User::class);
        $users = User::query()
            ->where('id', '!=', request()->user()->id)
            ->with('roles')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $categories = Category::query()->orderBy('title')->get(['id', 'title']);

        $userCategoryMap = [];
        foreach ($users as $user) {
            $userCategoryMap[$user->id] = $user->categoryPermissions()
                ->get(['categories.id', 'category_user.permission'])
                ->mapWithKeys(fn ($cat) => [
                    $cat->id => $cat->pivot->permission,
                ])
                ->all();
        }

        return Inertia::render('Users/UserCategoryAssignment', [
            'users' => $users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ]),
            'categories' => $categories,
            'userCategoryMap' => $userCategoryMap,
            'permissions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ]);
    }

    /**
     * Bulk store category assignments for users.
     */
    public function storeCategoryAssignments(Request $request)
    {
        $this->authorize('assignCategories', User::class);
        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'assignments.*.category_id' => ['required', 'integer', 'exists:categories,id'],
            'assignments.*.permissions' => ['required', 'array'],
            'assignments.*.permissions.*' => ['required', 'string', 'in:view,create,edit,delete,manage'],
        ]);

        foreach ($validated['assignments'] as $assignment) {
            $user = User::findOrFail($assignment['user_id']);
            $category = Category::findOrFail($assignment['category_id']);
            $permission = implode(',', $assignment['permissions']);
            $category->users()->syncWithoutDetaching([$user->id => ['permission' => $permission]]);
        }

        return back()->with('success', 'Category assignments updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete', [User::class, $id]);
        User::destroy($id);

        return to_route('users.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // === LISTADO DE USUARIOS ===
    public function index()
    {
        $users = User::with('staff')->orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    // === FORMULARIO CREAR ===
    public function create()
    {
        $staff = Staff::active()->orderBy('name')->get();
        return view('admin.users.create', compact('staff'));
    }

    // === GUARDAR NUEVO USUARIO ===
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    // === FORMULARIO EDITAR ===
    public function edit(User $user)
    {
        $staff = Staff::active()->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'staff'));
    }

    // === ACTUALIZAR USUARIO ===
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        // Solo actualizar password si se proporciona
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    // === ELIMINAR USUARIO ===
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}

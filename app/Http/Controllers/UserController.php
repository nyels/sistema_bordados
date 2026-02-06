<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::with('staff')->orderBy('name')->get();
        $staff = Staff::active()->doesntHave('user')->orderBy('name')->get();
        return view('admin.users.index', compact('users', 'staff'));
    }

    public function create()
    {
        $staff = Staff::active()->orderBy('name')->get();
        return view('admin.users.create', compact('staff'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'staff_id' => 'nullable|exists:staff,id',
            'is_active' => 'nullable|boolean'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato de email no es válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        try {
            $validated['password'] = Hash::make($validated['password']);
            $validated['is_active'] = $request->has('is_active') || $request->input('is_active');

            $user = User::create($validated);
            $user->load('staff');

            Log::info('[User@store] Usuario creado', ['id' => $user->id, 'user_id' => Auth::id()]);

            $msg = 'Usuario creado exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $user]);
            }
            return redirect()->route('admin.users.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[User@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al crear el usuario';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.users.index')->with('error', $msg);
        }
    }

    public function edit(User $user)
    {
        $staff = Staff::active()->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'staff'));
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'staff_id' => 'nullable|exists:staff,id',
            'is_active' => 'boolean'
        ]);

        try {
            if (empty($validated['password'])) {
                unset($validated['password']);
            } else {
                $validated['password'] = Hash::make($validated['password']);
            }

            $validated['is_active'] = $request->has('is_active') || $request->input('is_active');

            $user->fill($validated);

            if (!$user->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.users.index')->with('info', $msg);
            }

            $user->save();
            $user->load('staff');

            Log::info('[User@update] Usuario actualizado', ['id' => $user->id, 'user_id' => Auth::id()]);

            $msg = 'Usuario actualizado exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $user]);
            }
            return redirect()->route('admin.users.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[User@update] Error', ['id' => $user->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el usuario';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.users.index')->with('error', $msg);
        }
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        try {
            if ($user->id === Auth::id()) {
                $msg = 'No puedes eliminar tu propio usuario.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.users.index')->with('error', $msg);
            }

            $userId = $user->id;
            $user->delete();

            Log::info('[User@destroy] Usuario eliminado', ['id' => $userId, 'user_id' => Auth::id()]);

            $msg = 'Usuario eliminado exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.users.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[User@destroy] Error', ['id' => $user->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el usuario';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.users.index')->with('error', $msg);
        }
    }
}

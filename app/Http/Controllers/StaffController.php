<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $staff = Staff::with('user')->orderBy('name')->get();
        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function store(StoreStaffRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $staff = Staff::create($request->validated());

            Log::info('[Staff@store] Personal creado', ['id' => $staff->id, 'user_id' => Auth::id()]);

            $msg = 'Personal creado exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $staff]);
            }
            return redirect()->route('admin.staff.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Staff@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al crear el personal';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.staff.index')->with('error', $msg);
        }
    }

    public function edit(Staff $staff)
    {
        return view('admin.staff.edit', compact('staff'));
    }

    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse|RedirectResponse
    {
        try {
            $staff->fill($request->validated());

            if (!$staff->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.staff.index')->with('info', $msg);
            }

            $staff->save();

            Log::info('[Staff@update] Personal actualizado', ['id' => $staff->id, 'user_id' => Auth::id()]);

            $msg = 'Personal actualizado exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $staff]);
            }
            return redirect()->route('admin.staff.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Staff@update] Error', ['id' => $staff->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el personal';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.staff.index')->with('error', $msg);
        }
    }

    public function destroy(Request $request, Staff $staff): JsonResponse|RedirectResponse
    {
        try {
            if ($staff->user) {
                $msg = 'No se puede eliminar: tiene un usuario vinculado.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.staff.index')->with('error', $msg);
            }

            $staffId = $staff->id;
            $staff->delete();

            Log::info('[Staff@destroy] Personal eliminado', ['id' => $staffId, 'user_id' => Auth::id()]);

            $msg = 'Personal eliminado exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.staff.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Staff@destroy] Error', ['id' => $staff->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el personal';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.staff.index')->with('error', $msg);
        }
    }
}

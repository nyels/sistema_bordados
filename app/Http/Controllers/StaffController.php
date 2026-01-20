<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // === LISTADO DE PERSONAL ===
    public function index()
    {
        $staff = Staff::with('user')->orderBy('name')->get();
        return view('admin.staff.index', compact('staff'));
    }

    // === FORMULARIO CREAR ===
    public function create()
    {
        return view('admin.staff.create');
    }

    // === GUARDAR NUEVO PERSONAL ===
    public function store(StoreStaffRequest $request)
    {
        Staff::create($request->validated());

        return redirect()->route('admin.staff.index')
            ->with('success', 'Personal creado exitosamente.');
    }

    // === FORMULARIO EDITAR ===
    public function edit(Staff $staff)
    {
        return view('admin.staff.edit', compact('staff'));
    }

    // === ACTUALIZAR PERSONAL ===
    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        $staff->update($request->validated());

        return redirect()->route('admin.staff.index')
            ->with('success', 'Personal actualizado exitosamente.');
    }

    // === ELIMINAR PERSONAL ===
    public function destroy(Staff $staff)
    {
        if ($staff->user) {
            return redirect()->route('admin.staff.index')
                ->with('error', 'No se puede eliminar: tiene un usuario vinculado.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')
            ->with('success', 'Personal eliminado exitosamente.');
    }
}

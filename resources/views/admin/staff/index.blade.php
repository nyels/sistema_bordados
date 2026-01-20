@extends('adminlte::page')

@section('title', 'Personal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-id-badge mr-2"></i> Personal</h1>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Personal
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Puesto</th>
                        <th class="text-center">Usuario</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $person)
                        <tr>
                            <td>{{ $person->id }}</td>
                            <td class="font-weight-bold">{{ $person->name }}</td>
                            <td>{{ $person->position ?? '-' }}</td>
                            <td class="text-center">
                                @if($person->user)
                                    <span class="badge badge-info">{{ $person->user->email }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($person->is_active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.staff.edit', $person) }}" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.staff.destroy', $person) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este personal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No hay personal registrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

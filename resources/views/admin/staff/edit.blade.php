@extends('adminlte::page')

@section('title', 'Editar Personal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-id-badge mr-2"></i> Editar Personal</h1>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <form action="{{ route('admin.staff.update', $staff) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="name" class="font-weight-bold">Nombre *</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $staff->name) }}" required maxlength="150">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="position" class="font-weight-bold">Puesto</label>
                            <input type="text" name="position" id="position"
                                   class="form-control @error('position') is-invalid @enderror"
                                   value="{{ old('position', $staff->position) }}" maxlength="100">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Activo</label>
                            </div>
                        </div>

                        @if($staff->user)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i>
                                Usuario vinculado: <strong>{{ $staff->user->email }}</strong>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Actualizar
                        </button>
                        <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

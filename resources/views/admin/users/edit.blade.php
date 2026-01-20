@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user-edit mr-2"></i> Editar Usuario</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <form action="{{ route('admin.users.update', $user) }}" method="POST">
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
                                   value="{{ old('name', $user->name) }}" required maxlength="255">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email" class="font-weight-bold">Email *</label>
                            <input type="email" name="email" id="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password" class="font-weight-bold">Password</label>
                                    <input type="password" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Dejar vacio para no cambiar.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation" class="font-weight-bold">Confirmar Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="staff_id" class="font-weight-bold">Vincular a Personal</label>
                            <select name="staff_id" id="staff_id" class="form-control @error('staff_id') is-invalid @enderror">
                                <option value="">-- Sin vincular --</option>
                                @foreach($staff as $person)
                                    <option value="{{ $person->id }}" {{ old('staff_id', $user->staff_id) == $person->id ? 'selected' : '' }}>
                                        {{ $person->name }} {{ $person->position ? '('.$person->position.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('staff_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Actualizar
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@extends('adminlte::page')

@section('title', 'Configuración del Sistema')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-cogs"></i> CONFIGURACIÓN DEL SISTEMA
            </h3>
        </div>

        <div class="card-body">
            {{-- TABS DE GRUPOS --}}
            <ul class="nav nav-tabs mb-4">
                @foreach ($groups as $group)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeGroup === $group ? 'active' : '' }}"
                            href="{{ route('settings.index', ['group' => $group]) }}">
                            @switch($group)
                                @case('general')
                                    <i class="fas fa-home"></i> General
                                @break

                                @case('inventario')
                                    <i class="fas fa-boxes"></i> Inventario
                                @break

                                @case('facturacion')
                                    <i class="fas fa-file-invoice-dollar"></i> Facturación
                                @break

                                @case('produccion')
                                    <i class="fas fa-industry"></i> Producción
                                @break

                                @default
                                    <i class="fas fa-cog"></i> {{ ucfirst($group) }}
                            @endswitch
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- FORMULARIO DE CONFIGURACIONES --}}
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('put')
                <input type="hidden" name="group" value="{{ $activeGroup }}">

                <div class="row">
                    @forelse ($settings as $setting)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="setting_{{ $setting->key }}">
                                    {{ $setting->label }}
                                </label>

                                @switch($setting->type)
                                    @case('boolean')
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                            {{ old('settings.' . $setting->key, $setting->value) }}
                                            <input type="checkbox" class="custom-control-input" id="setting_{{ $setting->key }}"
                                                name="settings[{{ $setting->key }}]" value="1"
                                                {{ old('settings.' . $setting->key, $setting->value) == '1' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="setting_{{ $setting->key }}">
                                                Activado
                                            </label>
                                        </div>
                                    @break

                                    @case('select')
                                        <select class="form-control" id="setting_{{ $setting->key }}"
                                            name="settings[{{ $setting->key }}]">
                                            @foreach ($setting->options ?? [] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}"
                                                    {{ old('settings.' . $setting->key, $setting->value) === $optionValue ? 'selected' : '' }}>
                                                    {{ $optionLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @break

                                    @case('integer')
                                        <input type="number" class="form-control" id="setting_{{ $setting->key }}"
                                            name="settings[{{ $setting->key }}]"
                                            value="{{ old('settings.' . $setting->key, $setting->value) }}" min="0">
                                    @break

                                    @default
                                        <input type="text" class="form-control" id="setting_{{ $setting->key }}"
                                            name="settings[{{ $setting->key }}]"
                                            value="{{ old('settings.' . $setting->key, $setting->value) }}" maxlength="255">
                                @endswitch

                                @if ($setting->description)
                                    <small class="form-text text-muted">{{ $setting->description }}</small>
                                @endif
                            </div>
                        </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No hay configuraciones en este grupo.
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if ($settings->count() > 0)
                        <hr>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @stop

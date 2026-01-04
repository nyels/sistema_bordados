<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class ActivityLogController extends Controller
{
    private const ALLOWED_ACTIONS = ['created', 'updated', 'deleted', 'restored', 'login', 'logout'];
    private const MAX_PER_PAGE = 50;

    public function __construct(
        private readonly ActivityLog $activityLog
    ) {}

    public function index(Request $request): View
    {
        try {
            $validated = $request->validate([
                'user_id'   => ['nullable', 'integer', 'exists:users,id'],
                'action'    => ['nullable', 'string', 'in:' . implode(',', self::ALLOWED_ACTIONS)],
                'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
                'date_to'   => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
                'search'    => ['nullable', 'string', 'max:100'],
            ]);

            $query = $this->activityLog->newQuery()
                ->with('user')
                ->latest();

            $query->when($request->filled('user_id'), fn($q) => $q->where('user_id', $validated['user_id']))
                ->when($request->filled('action'), fn($q) => $q->where('action', $validated['action']))
                ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $validated['date_from']))
                ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $validated['date_to']));

            if ($request->filled('search')) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('model_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            }

            return view('admin.activity-logs.index', [
                'logs'    => $query->paginate(self::MAX_PER_PAGE)->withQueryString(),
                'users'   => User::orderBy('name')->pluck('name', 'id'),
                'actions' => self::ALLOWED_ACTIONS
            ]);
        } catch (Exception $e) {
            Log::error("Error en ActivityLogController@index: " . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace'   => $e->getTraceAsString()
            ]);

            return view('admin.activity-logs.index', [
                'logs'    => $this->activityLog->newQuery()->paginate(self::MAX_PER_PAGE),
                'users'   => collect(),
                'actions' => self::ALLOWED_ACTIONS
            ])->with('error', 'Error interno al cargar la auditoría.');
        }
    }

    /**
     * Muestra el detalle de un log específico por UUID.
     */
    public function show(string $uuid): View|RedirectResponse
    {
        try {
            // Buscamos el registro asegurando limpieza del input
            $log = $this->activityLog->where('uuid', trim($uuid))->first();

            if (!$log) {
                Log::warning("Actividad no encontrada: UUID '{$uuid}'", [
                    'ip'      => request()->ip(),
                    'user_id' => Auth::id()
                ]);

                return redirect()->route('activity-logs.index')
                    ->with('error', 'El registro de actividad solicitado no existe.');
            }

            return view('admin.activity-logs.show', compact('log'));
        } catch (Exception $e) {
            Log::error("Fallo crítico en ActivityLogController@show: " . $e->getMessage(), [
                'uuid'    => $uuid,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('activity-logs.index')
                ->with('error', 'Ocurrió un error al procesar la solicitud.');
        }
    }
}

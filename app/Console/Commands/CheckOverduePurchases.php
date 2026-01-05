<?php

namespace App\Console\Commands;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\PurchaseOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckOverduePurchases extends Command
{
    protected $signature = 'purchases:check-overdue';

    protected $description = 'Verificar compras con fecha de entrega vencida y notificar';

    public function handle(): int
    {
        $this->info('Verificando compras vencidas...');

        $overduePurchases = Purchase::where('activo', true)
            ->whereIn('status', [
                PurchaseStatus::PENDING->value,
                PurchaseStatus::PARTIAL->value,
            ])
            ->whereNotNull('expected_at')
            ->whereDate('expected_at', '<', now()->toDateString())
            ->with(['proveedor', 'creator'])
            ->get();

        if ($overduePurchases->isEmpty()) {
            $this->info('No hay compras vencidas.');
            return Command::SUCCESS;
        }

        $this->warn("Se encontraron {$overduePurchases->count()} compras vencidas.");

        // Obtener usuarios administradores para notificar
        $admins = User::role(['admin', 'super-admin'])->get();

        foreach ($overduePurchases as $purchase) {
            $daysOverdue = now()->diffInDays($purchase->expected_at);

            $this->line("- {$purchase->purchase_number}: Vencida hace {$daysOverdue} días");

            // Notificar a administradores
            Notification::send($admins, new PurchaseOverdueNotification($purchase, $daysOverdue));

            Log::channel('purchases')->warning('Compra vencida detectada', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'expected_at' => $purchase->expected_at->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'proveedor' => $purchase->proveedor->nombre_proveedor ?? 'N/A',
            ]);
        }

        $this->info('Proceso completado.');

        return Command::SUCCESS;
    }
}

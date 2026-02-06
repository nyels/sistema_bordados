<?php

namespace App\Console\Commands;

use App\Models\ClientMeasurementHistory;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMeasurementsToHistory extends Command
{
    protected $signature = 'measurements:sync-to-history';

    protected $description = 'Sincroniza las medidas existentes en order_items al historial de medidas del cliente';

    public function handle(): int
    {
        $this->info('Buscando order_items con medidas sin historial vinculado...');

        // Buscar items que tienen medidas pero no tienen measurement_history_id
        $items = OrderItem::whereNotNull('measurements')
            ->whereNull('measurement_history_id')
            ->whereHas('order', fn($q) => $q->whereNotNull('cliente_id'))
            ->with(['order', 'product'])
            ->get();

        $this->info("Encontrados: {$items->count()} items");

        if ($items->isEmpty()) {
            $this->info('No hay items pendientes de sincronizar.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $synced = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $measurements = $item->measurements;

            // Filtrar valores vacíos y save_to_client
            $filtered = collect($measurements)
                ->except(['save_to_client'])
                ->filter(fn($v) => !empty($v) && $v !== '0')
                ->toArray();

            if (empty($filtered)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                DB::transaction(function () use ($item, $filtered, &$synced) {
                    // Crear registro en historial
                    $history = ClientMeasurementHistory::create([
                        'cliente_id' => $item->order->cliente_id,
                        'order_id' => $item->order_id,
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'measurements' => $filtered,
                        'source' => 'order',
                        'notes' => "Migrado de pedido {$item->order->order_number}",
                        'created_by' => null, // Sistema
                        'captured_at' => $item->created_at,
                    ]);

                    // Vincular al item
                    $item->update(['measurement_history_id' => $history->id]);

                    $synced++;
                });
            } catch (\Exception $e) {
                $this->error("\nError en item {$item->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sincronizados: {$synced}");
        $this->info("Omitidos (sin medidas válidas): {$skipped}");

        return Command::SUCCESS;
    }
}

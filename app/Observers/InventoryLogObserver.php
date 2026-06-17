<?php

namespace App\Observers;

use App\Models\InventoryLog;

class InventoryLogObserver
{
    /**
     * Handle the InventoryLog "created" event.
     */
    public function created(InventoryLog $log): void
    {
        $inventory = $log->inventory;

        if ($log->is_incoming) {
            $inventory->increment('amount', $log->amount);
        } else {
            $inventory->decrement('amount', $log->amount);
        }
    }

    /**
     * Handle the InventoryLog "updated" event.
     */
    public function updated(InventoryLog $inventoryLog): void
    {
        //
    }

    /**
     * Handle the InventoryLog "deleted" event.
     */
    public function deleted(InventoryLog $inventoryLog): void
    {
        $inventory = $inventoryLog->inventory()->withTrashed()->first();

        if (! $inventory) return;

        // ЛОГИКА ОБРАТНАЯ МЕТОДУ CREATED
        if ($inventoryLog->is_incoming) {
            // Удаляем запись о приходе -> значит товара стало меньше
            $inventory->decrement('amount', $inventoryLog->amount);
        } else {
            // Удаляем запись о расходе -> значит товар вернулся на склад
            $inventory->increment('amount', $inventoryLog->amount);
        }
    }

    /**
     * Handle the InventoryLog "restored" event.
     */
    public function restored(InventoryLog $inventoryLog): void
    {
        //
    }

    /**
     * Handle the InventoryLog "force deleted" event.
     */
    public function forceDeleted(InventoryLog $inventoryLog): void
    {
        //
    }
}

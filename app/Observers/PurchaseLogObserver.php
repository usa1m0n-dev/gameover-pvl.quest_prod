<?php

namespace App\Observers;

use App\Models\InventoryLog;
use App\Models\PurchaseLog;

class PurchaseLogObserver
{
    /**
     * Handle the InventoryLog "created" event.
     */
    public function created(PurchaseLog $log): void
    {
        $inventory = $log->purchase;

        if ($log->is_expense) {
            $inventory->decrement('left_in_stock', $log->quantity);
        } else {
            $inventory->increment('left_in_stock', $log->quantity);
        }
    }

    /**
     * Handle the InventoryLog "updated" event.
     */
    public function updated(PurchaseLog $inventoryLog): void
    {
        //
    }

    /**
     * Handle the InventoryLog "deleted" event.
     */
    public function deleted(PurchaseLog $inventoryLog): void
    {
        $inventory = $inventoryLog->purchase()->first();

        if (! $inventory) return;

        // ЛОГИКА ОБРАТНАЯ МЕТОДУ CREATED
        if ($inventoryLog->is_incoming) {
            // Удаляем запись о приходе -> значит товара стало меньше
            $inventory->decrement('left_in_stock', $inventoryLog->quantity);
        } else {
            // Удаляем запись о расходе -> значит товар вернулся на склад
            $inventory->increment('left_in_stock', $inventoryLog->quantity);
        }
    }

    /**
     * Handle the InventoryLog "restored" event.
     */
    public function restored(PurchaseLog $inventoryLog): void
    {
        //
    }

    /**
     * Handle the InventoryLog "force deleted" event.
     */
    public function forceDeleted(PurchaseLog $inventoryLog): void
    {
        //
    }
}

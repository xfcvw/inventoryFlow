<?php

namespace App\Notifications;

use App\Models\ProductWarehouseStock;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public ProductWarehouseStock $stock) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'low_stock',
            'title' => 'Low stock',
            'message' => $this->stock->product->name . ' has ' . $this->stock->quantity . ' units in ' . $this->stock->warehouse->name . '.',
            'product_id' => $this->stock->product_id,
            'warehouse_id' => $this->stock->warehouse_id,
        ];
    }
}

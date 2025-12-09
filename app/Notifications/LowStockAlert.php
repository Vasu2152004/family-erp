<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public InventoryItem $inventoryItem
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $categoryName = $this->inventoryItem->category?->name ?? 'Uncategorized';
        $familyName = $this->inventoryItem->family->name;

        return (new MailMessage)
            ->subject('Low Stock Alert: ' . $this->inventoryItem->name)
            ->line("⚠️ Low Stock Alert for {$familyName}")
            ->line("Item: {$this->inventoryItem->name}")
            ->line("Category: {$categoryName}")
            ->line("Current Quantity: {$this->inventoryItem->qty} {$this->inventoryItem->unit}")
            ->line("Minimum Quantity: {$this->inventoryItem->min_qty} {$this->inventoryItem->unit}")
            ->action('View Inventory', route('inventory.items.index', ['family_id' => $this->inventoryItem->family_id]))
            ->line('Please restock this item soon.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $categoryName = $this->inventoryItem->category?->name ?? 'Uncategorized';

        return [
            'type' => 'low_stock_alert',
            'title' => 'Low Stock Alert',
            'message' => "⚠️ {$this->inventoryItem->name} ({$categoryName}) is running low. Current: {$this->inventoryItem->qty} {$this->inventoryItem->unit}, Minimum: {$this->inventoryItem->min_qty} {$this->inventoryItem->unit}",
            'data' => [
                'family_id' => $this->inventoryItem->family_id,
                'inventory_item_id' => $this->inventoryItem->id,
                'category_id' => $this->inventoryItem->category_id,
                'current_qty' => $this->inventoryItem->qty,
                'min_qty' => $this->inventoryItem->min_qty,
                'unit' => $this->inventoryItem->unit,
            ],
        ];
    }
}

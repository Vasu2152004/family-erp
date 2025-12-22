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
        $shortage = $this->inventoryItem->min_qty - $this->inventoryItem->qty;
        $percentage = ($this->inventoryItem->qty / $this->inventoryItem->min_qty) * 100;
        
        // Format numbers - show integers when appropriate
        $formatNumber = function($value) {
            return $value == (int)$value ? (int)$value : number_format((float)$value, 2, '.', '');
        };
        
        $currentQty = $formatNumber($this->inventoryItem->qty);
        $minQty = $formatNumber($this->inventoryItem->min_qty);
        $shortageFormatted = $formatNumber($shortage);
        $percentageFormatted = number_format($percentage, 1);

        return (new MailMessage)
            ->subject('⚠️ Low Stock Alert: ' . $this->inventoryItem->name)
            ->view('emails.layout', [
                'subject' => 'Low Stock Alert',
                'headerIcon' => '⚠️',
                'headerTitle' => 'Low Stock Alert',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    "**{$this->inventoryItem->name}** is running low in your inventory.",
                    "Current stock is below the minimum required quantity.",
                ],
                'details' => [
                    'Item Name' => $this->inventoryItem->name,
                    'Category' => $categoryName,
                    'Family' => $familyName,
                    'Current Quantity' => "{$currentQty} {$this->inventoryItem->unit}",
                    'Minimum Required' => "{$minQty} {$this->inventoryItem->unit}",
                    'Shortage' => "{$shortageFormatted} {$this->inventoryItem->unit}",
                    'Stock Level' => "{$percentageFormatted}%",
                ],
                'actionUrl' => route('inventory.items.index', ['family_id' => $this->inventoryItem->family_id]),
                'actionText' => 'View Inventory',
                'outroLines' => [
                    'Please restock this item soon to avoid running out.',
                ],
                'salutation' => 'Best regards,<br>Family ERP Team',
            ]);
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

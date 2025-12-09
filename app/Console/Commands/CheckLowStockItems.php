<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\Family;
use App\Services\InventoryService;
use App\Notifications\LowStockAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckLowStockItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-low-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock inventory items and send notifications';

    public function __construct(
        private InventoryService $inventoryService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for low stock items...');

        // Get all active families
        $families = Family::all();
        $totalAlerts = 0;

        foreach ($families as $family) {
            try {
                $lowStockItems = $this->inventoryService->checkLowStock($family->id);

                if ($lowStockItems->isEmpty()) {
                    continue;
                }

                // Get users to notify (OWNER/ADMIN)
                $usersToNotify = collect();
                $adminsAndOwners = $family->roles()
                    ->whereIn('role', ['OWNER', 'ADMIN'])
                    ->with('user')
                    ->get();

                foreach ($adminsAndOwners as $role) {
                    if ($role->user) {
                        $usersToNotify->push($role->user);
                    }
                }

                // Send notifications for each low stock item
                foreach ($lowStockItems as $item) {
                    // Check if notification already exists today for this item
                    $existingNotification = \App\Models\Notification::whereIn('user_id', $usersToNotify->pluck('id'))
                        ->where('type', 'low_stock_alert')
                        ->where('data->inventory_item_id', $item->id)
                        ->whereDate('created_at', today())
                        ->exists();

                    if (!$existingNotification) {
                        foreach ($usersToNotify->unique('id') as $user) {
                            // Create database notification
                            \App\Models\Notification::create([
                                'tenant_id' => $family->tenant_id,
                                'user_id' => $user->id,
                                'type' => 'low_stock_alert',
                                'title' => 'Low Stock Alert',
                                'message' => "⚠️ {$item->name} is running low. Current: {$item->qty} {$item->unit}, Minimum: {$item->min_qty} {$item->unit}",
                                'data' => [
                                    'family_id' => $family->id,
                                    'inventory_item_id' => $item->id,
                                    'category_id' => $item->category_id,
                                    'current_qty' => $item->qty,
                                    'min_qty' => $item->min_qty,
                                    'unit' => $item->unit,
                                ],
                            ]);

                            // Send email notification
                            try {
                                $user->notify(new LowStockAlert($item));
                            } catch (\Exception $e) {
                                Log::error("Failed to send low stock email to user {$user->id}: " . $e->getMessage());
                            }
                        }

                        $totalAlerts++;
                    }
                }

                $this->info("Family {$family->name}: Found {$lowStockItems->count()} low stock items");
            } catch (\Exception $e) {
                Log::error("Low stock check failed for family {$family->id}: " . $e->getMessage());
                $this->error("Error checking family {$family->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed. Sent {$totalAlerts} low stock alerts.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\Atk;
use App\Models\Inventory;
use App\Models\StockAlert;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StockAlertService
{
    public const LOW_STOCK_THRESHOLD = 5;
    private const STATUS_EMPTY = 'empty';
    private const STATUS_LOW = 'low';
    private const STATUS_NORMAL = 'normal';

    public function checkAtk(Atk $atk): void
    {
        if (!$this->isReady()) {
            return;
        }

        if (!$this->stockAlertEnabled($atk)) {
            $this->resolve($atk);
            $this->markUnreadStockNotificationsAsRead($atk);
            return;
        }

        if ((int) ($atk->active ?? 1) !== 1) {
            $this->resolve($atk);
            return;
        }

        $this->checkItem(
            $atk,
            'atk',
            'ATK',
            $atk->nama_atk,
            $atk->kode_atk,
            (float) ($atk->stok ?? 0),
            $atk->satuan ?: 'Pcs',
            '/atk/' . $atk->id . '/detail'
        );
    }

    public function checkInventory(Inventory $inventory): void
    {
        if (!$this->isReady()) {
            return;
        }

        if (!$this->stockAlertEnabled($inventory)) {
            $this->resolve($inventory);
            $this->markUnreadStockNotificationsAsRead($inventory);
            return;
        }

        $this->checkItem(
            $inventory,
            'inventory',
            'Aset Kantor',
            $inventory->nama_barang,
            $inventory->kode_barang,
            $inventory->stock_quantity,
            $inventory->display_uom,
            '/inventory/' . $inventory->id . '/detail'
        );
    }

    public function checkAll(): void
    {
        if (!$this->isReady()) {
            return;
        }

        if (Schema::hasTable('atks')) {
            Atk::where('active', 1)
                ->when(Schema::hasColumn('atks', 'stock_alert_enabled'), function ($query) {
                    $query->where('stock_alert_enabled', true);
                })
                ->where('stok', '<=', self::LOW_STOCK_THRESHOLD)
                ->chunkById(100, function ($items) {
                    foreach ($items as $item) {
                        $this->checkAtk($item);
                    }
                });
        }

        if (Schema::hasTable('inventories')) {
            Inventory::where('stok', '<=', self::LOW_STOCK_THRESHOLD)
                ->when(Schema::hasColumn('inventories', 'stock_alert_enabled'), function ($query) {
                    $query->where('stock_alert_enabled', true);
                })
                ->chunkById(100, function ($items) {
                    foreach ($items as $item) {
                        $this->checkInventory($item);
                    }
                });
        }
    }

    public function resolve(Model $item): void
    {
        if (!$this->isReady()) {
            return;
        }

        $alert = StockAlert::where('alertable_type', get_class($item))
            ->where('alertable_id', $item->getKey())
            ->first();

        if (!$alert || $alert->status === self::STATUS_NORMAL) {
            return;
        }

        $alert->update([
            'status' => self::STATUS_NORMAL,
            'stock' => max(0, (float) ($item->stok ?? 0)),
            'resolved_at' => now(),
        ]);
    }

    private function checkItem(Model $item, string $source, string $label, ?string $name, ?string $code, float $stock, string $unit, string $action): void
    {
        $status = $this->statusForStock($stock);

        if ($status === self::STATUS_NORMAL) {
            $this->resolve($item);
            return;
        }

        $alert = StockAlert::firstOrCreate(
            [
                'alertable_type' => get_class($item),
                'alertable_id' => $item->getKey(),
            ],
            [
                'company_id' => $item->company_id ?? current_company_id(),
                'source' => $source,
                'status' => self::STATUS_NORMAL,
                'stock' => $stock,
                'threshold' => self::LOW_STOCK_THRESHOLD,
            ]
        );

        $shouldNotify = $alert->status !== $status
            || $alert->resolved_at !== null
            || $alert->last_notified_at === null;

        $alert->forceFill([
            'company_id' => $item->company_id ?? $alert->company_id,
            'source' => $source,
            'status' => $status,
            'stock' => $stock,
            'threshold' => self::LOW_STOCK_THRESHOLD,
            'resolved_at' => null,
        ])->save();

        if (!$shouldNotify) {
            return;
        }

        $message = $this->message($status, $label, $name, $code, $stock, $unit);
        $this->notifyAdmins($message, $action, $status, $source, get_class($item) . ':' . $item->getKey(), $item->company_id ?? null);

        $alert->forceFill([
            'last_notified_at' => now(),
        ])->save();
    }

    private function statusForStock(float $stock): string
    {
        if ($stock <= 0) {
            return self::STATUS_EMPTY;
        }

        if ($stock <= self::LOW_STOCK_THRESHOLD) {
            return self::STATUS_LOW;
        }

        return self::STATUS_NORMAL;
    }

    private function stockAlertEnabled(Model $item): bool
    {
        if (!array_key_exists('stock_alert_enabled', $item->getAttributes())) {
            return true;
        }

        return (bool) $item->getAttribute('stock_alert_enabled');
    }

    private function markUnreadStockNotificationsAsRead(Model $item): void
    {
        $alertKey = get_class($item) . ':' . $item->getKey();

        User::where('is_admin', 'admin')
            ->forCompany($item->company_id ?? null)
            ->get()
            ->each(function (User $admin) use ($alertKey) {
            $admin->unreadNotifications()
                ->get()
                ->filter(function ($notification) use ($alertKey) {
                    return (bool) ($notification->data['stock_alert'] ?? false)
                        && ($notification->data['stock_alert_key'] ?? null) === $alertKey;
                })
                ->each
                ->markAsRead();
        });
    }

    private function message(string $status, string $label, ?string $name, ?string $code, float $stock, string $unit): string
    {
        $itemName = trim((string) $name) ?: '-';
        $itemCode = trim((string) $code) ?: '-';
        $stockText = $this->formatStock($stock) . ' ' . $unit;

        if ($status === self::STATUS_EMPTY) {
            return 'Stok ' . $label . ' ' . $itemName . ' (' . $itemCode . ') habis. Stok saat ini ' . $stockText . '.';
        }

        return 'Stok ' . $label . ' ' . $itemName . ' (' . $itemCode . ') menipis. Stok saat ini ' . $stockText . ' (batas ' . self::LOW_STOCK_THRESHOLD . ').';
    }

    private function notifyAdmins(string $message, string $action, string $status, string $source, string $alertKey, ?int $companyId): void
    {
        User::where('is_admin', 'admin')
            ->forCompany($companyId)
            ->get()
            ->each(function (User $admin) use ($message, $action, $status, $source, $alertKey) {
            $admin->messages = [
                'user_id' => $admin->id,
                'from' => 'Sistem Stok',
                'message' => $message,
                'action' => $action,
                'stock_alert' => true,
                'stock_alert_status' => $status,
                'stock_alert_source' => $source,
                'stock_alert_key' => $alertKey,
            ];

            $admin->notify(new UserNotification);
        });
    }

    private function formatStock(float $stock): string
    {
        $formatted = number_format($stock, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function isReady(): bool
    {
        return Schema::hasTable('stock_alerts')
            && Schema::hasTable('notifications')
            && Schema::hasTable('users');
    }
}

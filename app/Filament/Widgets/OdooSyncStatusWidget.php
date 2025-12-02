<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\OdooSyncLog;
use App\Services\OdooService;
use App\Services\OdooSyncService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

class OdooSyncStatusWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $syncedProducts = Product::whereNotNull('odoo_product_id')->count();
        $unsyncedProducts = $totalProducts - $syncedProducts;
        $lastSync = Product::whereNotNull('odoo_synced_at')
            ->orderBy('odoo_synced_at', 'desc')
            ->value('odoo_synced_at');

        $syncLogsToday = OdooSyncLog::whereDate('synced_at', today())->count();
        $errorsToday = OdooSyncLog::whereDate('synced_at', today())
            ->where('status', 'error')
            ->count();

        $syncPercentage = $totalProducts > 0
            ? round(($syncedProducts / $totalProducts) * 100)
            : 0;

        return [
            Stat::make('Odoo Synced Products', $syncedProducts . ' / ' . $totalProducts)
                ->description($syncPercentage . '% synced with Odoo')
                ->descriptionIcon($syncPercentage === 100 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-path')
                ->color($syncPercentage === 100 ? 'success' : ($syncPercentage > 50 ? 'warning' : 'danger'))
                ->chart($this->getSyncTrend()),

            Stat::make('Unsynced Products', $unsyncedProducts)
                ->description('Products not linked to Odoo')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unsyncedProducts === 0 ? 'success' : 'warning'),

            Stat::make('Sync Activity Today', $syncLogsToday)
                ->description($errorsToday > 0 ? $errorsToday . ' errors' : 'No errors')
                ->descriptionIcon($errorsToday > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                ->color($errorsToday > 0 ? 'danger' : 'success'),

            Stat::make('Last Sync', $lastSync ? $lastSync->diffForHumans() : 'Never')
                ->description($lastSync ? $lastSync->format('M j, Y g:i A') : 'No sync recorded')
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getLastSyncColor($lastSync)),
        ];
    }

    /**
     * Get sync trend data for the chart
     */
    protected function getSyncTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = OdooSyncLog::whereDate('synced_at', $date)
                ->where('status', 'success')
                ->count();
            $trend[] = $count;
        }
        return $trend;
    }

    /**
     * Determine color based on last sync time
     */
    protected function getLastSyncColor($lastSync): string
    {
        if (!$lastSync) {
            return 'danger';
        }

        $hoursSinceSync = $lastSync->diffInHours(now());

        if ($hoursSinceSync < 1) {
            return 'success';
        } elseif ($hoursSinceSync < 24) {
            return 'warning';
        }

        return 'danger';
    }
}

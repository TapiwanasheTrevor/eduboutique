<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Customer;
use App\Models\OdooSyncLog;
use App\Services\OdooService;
use App\Services\OdooSyncService;
use App\Services\CustomerSyncService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OdooSyncPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static string $view = 'filament.pages.odoo-sync-page';

    protected static ?string $navigationLabel = 'Odoo Sync';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    public array $syncStatus = [];

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $totalProducts = Product::count();
        $syncedProducts = Product::whereNotNull('odoo_product_id')->count();

        $totalCustomers = Customer::count();
        $syncedCustomers = Customer::whereNotNull('odoo_partner_id')->count();

        $this->syncStatus = [
            'total_products' => $totalProducts,
            'synced_products' => $syncedProducts,
            'unsynced_products' => $totalProducts - $syncedProducts,
            'sync_percentage' => $totalProducts > 0
                ? round(($syncedProducts / $totalProducts) * 100, 1)
                : 0,
            'total_customers' => $totalCustomers,
            'synced_customers' => $syncedCustomers,
            'unsynced_customers' => $totalCustomers - $syncedCustomers,
            'customer_sync_percentage' => $totalCustomers > 0
                ? round(($syncedCustomers / $totalCustomers) * 100, 1)
                : 0,
            'last_sync' => Product::whereNotNull('odoo_synced_at')
                ->orderBy('odoo_synced_at', 'desc')
                ->value('odoo_synced_at'),
            'sync_logs_today' => OdooSyncLog::whereDate('synced_at', today())->count(),
            'errors_today' => OdooSyncLog::whereDate('synced_at', today())
                ->where('status', 'error')
                ->count(),
            'recent_logs' => OdooSyncLog::orderBy('synced_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('pullProductsFromOdoo')
                    ->label('Pull Products')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn () => $this->runSync('pull_products')),

                Action::make('pushProductsToOdoo')
                    ->label('Push Products')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->action(fn () => $this->runSync('push_products')),

                Action::make('syncStock')
                    ->label('Sync Stock Only')
                    ->icon('heroicon-o-cube')
                    ->action(fn () => $this->runSync('stock')),
            ])
                ->label('Products')
                ->icon('heroicon-o-book-open')
                ->color('primary'),

            ActionGroup::make([
                Action::make('pullCustomersFromOdoo')
                    ->label('Pull Customers')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn () => $this->runSync('pull_customers')),

                Action::make('pushCustomersToOdoo')
                    ->label('Push Customers')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->action(fn () => $this->runSync('push_customers')),
            ])
                ->label('Customers')
                ->icon('heroicon-o-users')
                ->color('success'),

            Action::make('fullSync')
                ->label('Full Sync')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Full Bidirectional Sync')
                ->modalDescription('This will sync products and customers in both directions. Conflicts will be resolved using the "newest wins" strategy.')
                ->action(fn () => $this->runSync('all')),
        ];
    }

    protected function runSync(string $type): void
    {
        try {
            $odoo = app(OdooService::class);
            $productSyncService = new OdooSyncService($odoo);
            $customerSyncService = new CustomerSyncService($odoo);
            $productSyncService->setConflictStrategy('newest_wins');
            $customerSyncService->setConflictStrategy('newest_wins');

            $message = match ($type) {
                'pull_products' => $this->pullProducts($productSyncService),
                'push_products' => $this->pushProducts($productSyncService),
                'stock' => $this->syncStock($productSyncService),
                'pull_customers' => $this->pullCustomers($customerSyncService),
                'push_customers' => $this->pushCustomers($customerSyncService),
                'all' => $this->fullSync($productSyncService, $customerSyncService),
                default => 'Unknown sync type',
            };

            $this->refreshStatus();

            Notification::make()
                ->title('Sync Completed')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Sync failed: ' . $e->getMessage());

            Notification::make()
                ->title('Sync Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function pullProducts(OdooSyncService $service): string
    {
        $stats = $service->pullProductsFromOdoo();
        return "Pulled {$stats['synced']} products from Odoo";
    }

    protected function pushProducts(OdooSyncService $service): string
    {
        $stats = $service->pushProductsToOdoo();
        return "Pushed {$stats['synced']} products to Odoo";
    }

    protected function syncStock(OdooSyncService $service): string
    {
        $stats = $service->syncStockLevels();
        return "Updated {$stats['updated']} stock levels";
    }

    protected function pullCustomers(CustomerSyncService $service): string
    {
        $stats = $service->pullCustomersFromOdoo();
        return "Pulled {$stats['synced']} customers from Odoo";
    }

    protected function pushCustomers(CustomerSyncService $service): string
    {
        $stats = $service->pushCustomersToOdoo();
        return "Pushed {$stats['synced']} customers to Odoo";
    }

    protected function fullSync(OdooSyncService $productService, CustomerSyncService $customerService): string
    {
        $productStats = $productService->syncProducts();
        $customerStats = $customerService->syncCustomers();

        return "Products: Pulled {$productStats['pulled']}, Pushed {$productStats['pushed']}. " .
               "Customers: Pulled {$customerStats['pulled']}, Pushed {$customerStats['pushed']}";
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\OdooService;
use Illuminate\Console\Command;

class TestOdooConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odoo:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to Odoo and display basic information';

    /**
     * Execute the console command.
     */
    public function handle(OdooService $odoo)
    {
        $this->info('Testing Odoo connection...');
        $this->newLine();

        try {
            // Display configuration
            $this->info('Configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['URL', config('odoo.url')],
                    ['Database', config('odoo.database')],
                    ['Username', config('odoo.username')],
                ]
            );
            $this->newLine();

            // Test connection
            $this->info('Testing connection...');
            $connected = $odoo->testConnection();

            if (!$connected) {
                $this->error('❌ Connection test failed!');
                return 1;
            }

            $this->info('✅ Connection successful!');
            $this->newLine();

            // Get product count
            $this->info('Fetching statistics...');

            $products = $odoo->search('product.product', [['sale_ok', '=', true]], ['id'], 5);
            $this->info('Sample products available: ' . count($products));

            if (!empty($products)) {
                $this->table(
                    ['Odoo Product ID', 'Name'],
                    collect($products)->map(fn($p) => [$p['id'], $p['name'] ?? 'N/A'])->toArray()
                );
            }

            $this->newLine();
            $this->info('✅ Odoo connection test completed successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Connection test failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->line('Trace:');
            $this->line($e->getTraceAsString());

            return 1;
        }
    }
}

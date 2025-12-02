<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Middleware\VerifyCpanelSecret;

/**
 * cPanel Management Routes
 *
 * These routes provide web-based access to common artisan commands
 * for environments without terminal access (like cPanel shared hosting).
 *
 * IMPORTANT: Protect these routes with a secret key in production!
 */

Route::middleware(VerifyCpanelSecret::class)->prefix('cpanel')->group(function () {

    // Run migrations
    Route::get('/migrate', function () {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return response()->json([
                'success' => true,
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Run fresh migrations (DANGEROUS - drops all tables)
    Route::get('/migrate-fresh', function () {
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            return response()->json([
                'success' => true,
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Run seeders
    Route::get('/seed', function (Request $request) {
        try {
            $class = $request->query('class');
            $params = ['--force' => true];

            if ($class) {
                $params['--class'] = $class;
            }

            Artisan::call('db:seed', $params);
            return response()->json([
                'success' => true,
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Clear all caches
    Route::get('/clear-cache', function () {
        try {
            Artisan::call('config:clear');
            $output = Artisan::output();

            Artisan::call('cache:clear');
            $output .= Artisan::output();

            Artisan::call('view:clear');
            $output .= Artisan::output();

            Artisan::call('route:clear');
            $output .= Artisan::output();

            return response()->json([
                'success' => true,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Optimize for production
    Route::get('/optimize', function () {
        try {
            Artisan::call('config:cache');
            $output = Artisan::output();

            Artisan::call('route:cache');
            $output .= Artisan::output();

            Artisan::call('view:cache');
            $output .= Artisan::output();

            return response()->json([
                'success' => true,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Storage link (create directories if needed)
    Route::get('/storage-link', function () {
        try {
            // Ensure storage directories exist
            $directories = [
                storage_path('app/public'),
                storage_path('app/public/products'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                storage_path('logs'),
            ];

            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Storage directories created. Use storage.php for file serving.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Odoo sync
    Route::get('/odoo-sync', function (Request $request) {
        try {
            $type = $request->query('type', 'status');

            Artisan::call('odoo:sync', ['--type' => $type]);

            return response()->json([
                'success' => true,
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Create Filament user
    Route::get('/create-admin', function (Request $request) {
        try {
            $name = $request->query('name', 'Admin');
            $email = $request->query('email');
            $password = $request->query('password');

            if (!$email || !$password) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email and password are required'
                ], 400);
            }

            $user = \App\Models\User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Admin user {$email} created/updated"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Queue work (process pending jobs)
    Route::get('/queue-work', function () {
        try {
            // Process up to 10 jobs
            Artisan::call('queue:work', [
                '--once' => true,
                '--tries' => 3,
            ]);

            return response()->json([
                'success' => true,
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Health check / status
    Route::get('/status', function () {
        return response()->json([
            'success' => true,
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => [
                'connected' => \DB::connection()->getPdo() ? true : false,
            ],
        ]);
    });
});

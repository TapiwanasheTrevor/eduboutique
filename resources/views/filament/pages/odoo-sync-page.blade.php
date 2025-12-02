<x-filament-panels::page>
    {{-- Products Stats --}}
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Products</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <x-heroicon-o-book-open class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Products</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['total_products'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Synced Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Synced with Odoo</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['synced_products'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400">{{ $syncStatus['sync_percentage'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>

            {{-- Unsynced Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full {{ ($syncStatus['unsynced_products'] ?? 0) > 0 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-green-100 dark:bg-green-900' }}">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 {{ ($syncStatus['unsynced_products'] ?? 0) > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400' }}" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Not Synced</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['unsynced_products'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Sync Activity --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full {{ ($syncStatus['errors_today'] ?? 0) > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' }}">
                        <x-heroicon-o-arrow-path class="w-6 h-6 {{ ($syncStatus['errors_today'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sync Activity Today</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['sync_logs_today'] ?? 0 }}</p>
                        @if(($syncStatus['errors_today'] ?? 0) > 0)
                            <p class="text-xs text-red-500">{{ $syncStatus['errors_today'] }} errors</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customers Stats --}}
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Customers</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Total Customers --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                        <x-heroicon-o-users class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['total_customers'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Synced Customers --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Synced with Odoo</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['synced_customers'] ?? 0 }}</p>
                        <p class="text-xs text-gray-400">{{ $syncStatus['customer_sync_percentage'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>

            {{-- Unsynced Customers --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full {{ ($syncStatus['unsynced_customers'] ?? 0) > 0 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-green-100 dark:bg-green-900' }}">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 {{ ($syncStatus['unsynced_customers'] ?? 0) > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400' }}" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Not Synced</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $syncStatus['unsynced_customers'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Last Sync Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Last Sync</h3>
        @if($syncStatus['last_sync'] ?? null)
            <p class="text-gray-600 dark:text-gray-300">
                {{ $syncStatus['last_sync']->diffForHumans() }}
                <span class="text-sm text-gray-400">({{ $syncStatus['last_sync']->format('M j, Y g:i A') }})</span>
            </p>
        @else
            <p class="text-gray-400">No sync recorded yet</p>
        @endif
    </div>

    {{-- Recent Sync Logs --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sync Activity</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Operation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Direction</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($syncStatus['recent_logs'] ?? [] as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $log->model }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ ucfirst($log->operation) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->direction === 'to_odoo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        <x-heroicon-s-arrow-up class="w-3 h-3 mr-1" /> To Odoo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        <x-heroicon-s-arrow-down class="w-3 h-3 mr-1" /> From Odoo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->status === 'success')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Success
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Error
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $log->synced_at?->diffForHumans() ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No sync activity recorded
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

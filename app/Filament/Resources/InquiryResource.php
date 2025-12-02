<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Filament\Resources\InquiryResource\RelationManagers;
use App\Models\Inquiry;
use App\Models\Customer;
use App\Jobs\SyncCustomerToOdoo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Orders & Inquiries';

    protected static ?string $pluralModelLabel = 'Inquiries';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inquiry Details')
                    ->schema([
                        Forms\Components\TextInput::make('inquiry_number')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'contacted' => 'Contacted',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Unassigned'),
                    ])->columns(3),

                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Linked Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('email')->email()->required(),
                                Forms\Components\TextInput::make('phone'),
                            ]),

                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->required()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->required()
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Delivery Details')
                    ->schema([
                        Forms\Components\Select::make('delivery_method')
                            ->options([
                                'store_pickup' => 'Store Pickup',
                                'agent_delivery' => 'Agent Delivery',
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('delivery_city')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('delivery_address')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('message')
                            ->label('Customer Message')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\View::make('filament.infolists.order-items')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Totals')
                    ->schema([
                        Forms\Components\TextInput::make('total_zwl')
                            ->label('Total (ZWL)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('total_usd')
                            ->label('Total (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Internal Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(4)
                            ->placeholder('Add internal notes about this inquiry...'),
                    ]),

                Forms\Components\Section::make('Odoo Integration')
                    ->schema([
                        Forms\Components\TextInput::make('odoo_order_id')
                            ->label('Odoo Order ID')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('odoo_synced_at')
                            ->label('Last Synced')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->hidden(fn ($record) => !$record?->odoo_order_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inquiry_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Inquiry number copied'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->url(fn ($record) => $record->customer_id
                        ? route('filament.admin.resources.customers.edit', $record->customer_id)
                        : null)
                    ->color('primary')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('customer_email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->limit(30),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone copied'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'contacted' => 'info',
                        'confirmed' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('total_usd')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_method')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->default('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('odoo_order_id')
                    ->label('Synced')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('M d, Y g:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'contacted' => 'Contacted',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                    ]),

                Tables\Filters\SelectFilter::make('delivery_method')
                    ->options([
                        'store_pickup' => 'Store Pickup',
                        'agent_delivery' => 'Agent Delivery',
                    ]),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name'),

                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_to')),

                Tables\Filters\Filter::make('synced')
                    ->label('Synced to Odoo')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('odoo_order_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('sync_to_odoo')
                    ->label('Sync to Odoo')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Inquiry $record) {
                        try {
                            $odoo = app(\App\Services\OdooService::class);

                            // Build description
                            $lines = [];
                            $lines[] = '=== Customer Inquiry Details ===' . PHP_EOL;
                            $lines[] = 'Inquiry Number: ' . $record->inquiry_number;
                            $lines[] = 'Customer: ' . $record->customer_name;
                            $lines[] = 'Email: ' . $record->customer_email;
                            $lines[] = 'Phone: ' . $record->customer_phone;
                            $lines[] = '';
                            $lines[] = 'Delivery Method: ' . ucfirst(str_replace('_', ' ', $record->delivery_method));
                            if ($record->delivery_address) {
                                $lines[] = 'Delivery Address: ' . $record->delivery_address;
                            }
                            if ($record->delivery_city) {
                                $lines[] = 'City: ' . $record->delivery_city;
                            }
                            $lines[] = PHP_EOL . '=== Cart Items ===' . PHP_EOL;
                            foreach ($record->cart_items as $index => $item) {
                                $productTitle = $item['title'] ?? 'Product';
                                $quantity = $item['quantity'] ?? 1;
                                $priceUsd = $item['price_usd'] ?? 0;
                                $odooProductId = $item['odoo_product_id'] ?? null;
                                $isbn = $item['isbn'] ?? '';
                                $sku = $item['sku'] ?? '';

                                // Look up Odoo product ID from database if not in cart data
                                if (!$odooProductId && isset($item['product_id'])) {
                                    $product = \App\Models\Product::find($item['product_id']);
                                    $odooProductId = $product?->odoo_product_id;
                                    $isbn = $isbn ?: ($product?->isbn ?? '');
                                    $sku = $sku ?: ($product?->sku ?? '');
                                }

                                $identifiers = [];
                                if ($odooProductId) $identifiers[] = "Odoo ID: {$odooProductId}";
                                if ($isbn) $identifiers[] = "ISBN: {$isbn}";
                                if ($sku) $identifiers[] = "SKU: {$sku}";
                                $idString = $identifiers ? ' [' . implode(', ', $identifiers) . ']' : '';

                                $lines[] = sprintf('%d. %s%s (Qty: %d) - $%.2f USD', $index + 1, $productTitle, $idString, $quantity, $priceUsd * $quantity);
                            }
                            $lines[] = PHP_EOL . '=== Order Totals ===' . PHP_EOL;
                            $lines[] = sprintf('Total (USD): $%.2f', $record->total_usd ?? 0);
                            $lines[] = sprintf('Total (ZWL): $%.2f', $record->total_zwl ?? 0);
                            $description = implode(PHP_EOL, $lines);

                            $leadData = [
                                'name' => 'Website Inquiry: ' . $record->inquiry_number,
                                'contact_name' => $record->customer_name,
                                'email_from' => $record->customer_email,
                                'phone' => $record->customer_phone,
                                'description' => $description,
                                'type' => 'lead',
                                'street' => $record->delivery_address,
                                'city' => $record->delivery_city,
                                'expected_revenue' => $record->total_usd,
                                'referred' => 'EduBoutique Website',
                            ];

                            $leadId = $odoo->create('crm.lead', $leadData);
                            $record->update([
                                'odoo_lead_id' => $leadId,
                                'odoo_synced_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Synced to Odoo')
                                ->body("Lead created with ID: {$leadId}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Inquiry $record) => !$record->odoo_lead_id)
                    ->color('warning'),

                Tables\Actions\Action::make('convert_to_customer')
                    ->label('Create Customer')
                    ->icon('heroicon-o-user-plus')
                    ->action(function (Inquiry $record) {
                        // Create customer from inquiry
                        $customer = Customer::firstOrCreate(
                            ['email' => $record->customer_email],
                            [
                                'name' => $record->customer_name,
                                'phone' => $record->customer_phone,
                                'street' => $record->delivery_address,
                                'city' => $record->delivery_city,
                                'source' => 'inquiry',
                            ]
                        );

                        // Link inquiry to customer
                        $record->update(['customer_id' => $customer->id]);

                        // Sync to Odoo
                        SyncCustomerToOdoo::dispatch($customer);

                        Notification::make()
                            ->title('Customer created')
                            ->body("Customer '{$customer->name}' has been created and linked to this inquiry.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Inquiry $record) => !$record->customer_id)
                    ->color('success'),

                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-envelope')
                    ->action(function (Inquiry $record) {
                        // dispatch(new SendInquiryEmailToCustomer($record));
                        // Placeholder for email job
                    })
                    ->requiresConfirmation()
                    ->color('info'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'view' => Pages\ViewInquiry::route('/{record}'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

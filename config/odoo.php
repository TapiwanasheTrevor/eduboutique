<?php

return [
    'url' => env('ODOO_URL', 'https://eduboutique.odoo.com'),
    'database' => env('ODOO_DATABASE', 'eduboutique'),
    'username' => env('ODOO_USERNAME'),
    'password' => env('ODOO_PASSWORD'),
    'api_key' => env('ODOO_API_KEY'),

    'sync' => [
        'products_interval' => env('ODOO_SYNC_PRODUCTS_INTERVAL', 30),
        'orders_interval' => env('ODOO_SYNC_ORDERS_INTERVAL', 5),
        'stock_interval' => env('ODOO_SYNC_STOCK_INTERVAL', 15),
    ],

    'models' => [
        'product' => 'product.product',
        'category' => 'product.category',
        'order' => 'sale.order',
        'partner' => 'res.partner',
    ],
];

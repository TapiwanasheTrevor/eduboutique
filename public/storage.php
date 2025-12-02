<?php

/**
 * Storage File Server for cPanel (No Symlinks)
 *
 * This file serves files from storage/app/public without needing symlinks.
 * Upload to public_html/storage.php
 */

// Get the requested file path
$requestUri = $_SERVER['REQUEST_URI'];

// Remove /storage/ prefix
$path = preg_replace('/^\/storage\//', '', parse_url($requestUri, PHP_URL_PATH));

if (empty($path) || $path === '/') {
    http_response_code(404);
    exit('File not found');
}

// Sanitize the path to prevent directory traversal
$path = str_replace(['..', "\0"], '', $path);

// Build the full file path - adjust this path based on your cPanel structure
// If Laravel is in /home/user/laravel and public_html points to /home/user/public_html
// Then storage path would be: /home/user/laravel/storage/app/public/
$storagePath = __DIR__ . '/../storage/app/public/' . $path;

// Check if file exists
if (!file_exists($storagePath) || !is_file($storagePath)) {
    http_response_code(404);
    exit('File not found');
}

// Get the file's mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $storagePath);
finfo_close($finfo);

// Set appropriate headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($storagePath));
header('Cache-Control: public, max-age=31536000');

// Output the file
readfile($storagePath);
exit;

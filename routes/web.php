<?php

use Illuminate\Support\Facades\Route;

function runLegacy(string $relativePath): void
{
    $file = base_path('legacy/' . $relativePath);

    if (!file_exists($file)) {
        abort(404);
    }

    // Capture variables from the current scope if needed, 
    // but the bootstrap.php should handle its own globals.
    
    chdir(dirname($file));
    require $file;
    exit;
}

function runLegacyFolder(string $folder, string $page): void
{
    if (!preg_match('/^[a-z0-9\-]+$/', $page)) {
        abort(404);
    }
    runLegacy("{$folder}/{$page}.php");
}

Route::get('/', function () { runLegacy('index.php'); });
Route::get('/index.php', function () { runLegacy('index.php'); });

Route::match(['get', 'post'], '/login.php', function () { runLegacy('login.php'); });
Route::match(['get', 'post'], '/register.php', function () { runLegacy('register.php'); });
Route::get('/logout.php', function () { runLegacy('logout.php'); });
Route::match(['get', 'post'], '/process-booking.php', function () { runLegacy('process-booking.php'); });
Route::match(['get', 'post'], '/send-notification.php', function () { runLegacy('send-notification.php'); });

Route::any('/admin/{page}', function ($page) {
    $page = str_replace('.php', '', $page);
    runLegacyFolder('admin', $page);
});
Route::any('/admin/{page}.php', function ($page) {
    runLegacyFolder('admin', $page);
});

Route::any('/customer/{page}', function ($page) {
    $page = str_replace('.php', '', $page);
    runLegacyFolder('customer', $page);
});
Route::any('/customer/{page}.php', function ($page) {
    runLegacyFolder('customer', $page);
});

Route::any('/mechanic/{page}', function ($page) {
    $page = str_replace('.php', '', $page);
    runLegacyFolder('mechanic', $page);
});
Route::any('/mechanic/{page}.php', function ($page) {
    runLegacyFolder('mechanic', $page);
});

Route::any('/{path?}', function ($path = null) {
    if ($path && str_ends_with($path, '.php')) {
        runLegacy($path);
    }
    abort(404);
})->where('path', '.*');

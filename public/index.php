<?php
require_once __DIR__ . '/../src/bootstrap.php';

$page = $_GET['page'] ?? 'index';

switch ($page) {
    case 'index':
        require __DIR__ . '/../pages/index.php';
        break;
    case 'series':
        require __DIR__ . '/../pages/series.php';
        break;
    case 'favorites':
        require __DIR__ . '/../pages/favorites.php';
        break;
    case 'config_page':
    case 'config':
        require __DIR__ . '/../pages/config_page.php';
        break;
    case 'reorder':
        require __DIR__ . '/../pages/reorder.php';
        break;
    default:
        http_response_code(404);
        echo 'Page not found';
}

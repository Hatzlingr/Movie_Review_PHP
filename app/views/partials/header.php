<?php
// app/views/partials/header.php
declare(strict_types=1);
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/auth.php';
ensure_session();
$_current_user = current_user();
$_q = e($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'ELITISRIPIW') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        <?php
        // File urutan penting: base -> navbar -> hero -> home -> dark-bs -> responsive
        // Halaman tertentu bisa tambah file ekstra via $extraCss = ['admin']
        $_cssDir   = __DIR__ . '/../../../public/assets/css/';
        $_cssFiles = array_merge(
            ['base', 'navbar', 'hero', 'home', 'dark-bs', 'responsive'],
            $extraCss ?? []
        );
        foreach ($_cssFiles as $_f) {
            $fp = $_cssDir . $_f . '.css';
            if (file_exists($fp)) echo file_get_contents($fp);
        }
        ?>
    </style>
</head>

<body>
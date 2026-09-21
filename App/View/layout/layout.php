<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $escape($csrfToken) ?>">
    <title><?= $escape($title) ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light"
      data-page="<?= $escape(trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: 'home') ?>">
<?php require __DIR__ . '/header.php'; ?>
<?php require __DIR__ . '/flash.php'; ?>
<?= $content ?>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
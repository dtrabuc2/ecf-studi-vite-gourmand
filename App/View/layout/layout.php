<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $escape($csrfToken) ?>">
    <title><?= $escape($title) ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components/navbar.css">
    <link rel="stylesheet" href="/assets/css/components/buttons.css">
    <link rel="stylesheet" href="/assets/css/components/cards.css">
    <link rel="stylesheet" href="/assets/css/components/forms.css">
    <link rel="stylesheet" href="/assets/css/components/footer.css">
    <link rel="stylesheet" href="/assets/css/components/hero.css">
    <link rel="stylesheet" href="/assets/css/components/feedback.css">
    <link rel="stylesheet" href="/assets/css/pages/home.css">
    <link rel="stylesheet" href="/assets/css/pages/menus.css">
    <link rel="stylesheet" href="/assets/css/pages/orders.css">
    <link rel="stylesheet" href="/assets/css/pages/admin.css">
    <link rel="stylesheet" href="/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/assets/css/pages/contact.css">
    <link rel="stylesheet" href="/assets/css/pages/quote.css">
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
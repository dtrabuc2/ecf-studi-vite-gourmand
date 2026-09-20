<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAuthenticated = !empty($user);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="/">Vite &amp; Gourmand</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNavigation"
                aria-controls="mainNavigation" aria-expanded="false"
                aria-label="Afficher la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPath === '/' ? 'active' : '' ?>" href="/">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_starts_with($currentPath, '/menus') ? 'active' : '' ?>" href="/menus">Menus</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPath === '/contact' ? 'active' : '' ?>" href="/contact">Contact</a>
                </li>

                <?php if ($isAuthenticated): ?>
                    <li class="nav-item"><a class="nav-link" href="/orders/new">Commander</a></li>
                    <li class="nav-item"><a class="nav-link" href="/orders">Mes commandes</a></li>
                    <li class="nav-item"><a class="nav-link" href="/profile">Mon profil</a></li>
                    <?php if ($role === 'user'): ?>
                        <li class="nav-item"><a class="nav-link" href="/notifications">Notifications</a></li>
                    <?php endif; ?>

                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/dashboard">Administration</a></li>
                    <?php elseif ($role === 'employee'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/orders">Commandes</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/quotes">Devis</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/dishes">Plats</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/hours">Horaires</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/comments/pending">Avis</a></li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <form method="post" action="/logout" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <button class="btn btn-outline-primary btn-sm" type="submit">Déconnexion</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login">Connexion</a></li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm" href="/register">Créer un compte</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
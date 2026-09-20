<main>
    <section class="hero bg-primary text-white py-5">
        <div class="container py-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge text-bg-light text-primary mb-3">Traiteur à Bordeaux</span>
                    <h1 class="display-5 fw-bold">Des menus gourmands pour vos événements</h1>
                    <p class="lead">Une cuisine de saison, des formules lisibles et un parcours de commande simple.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-light btn-lg" href="/menus">Découvrir les menus</a>
                        <?php if (!empty($user)): ?>
                            <a class="btn btn-outline-light btn-lg" href="/orders/new">Commander</a>
                        <?php else: ?>
                            <a class="btn btn-outline-light btn-lg" href="/login">Se connecter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h2 class="h2 text-primary mb-1">Menus à la une</h2><p class="text-muted mb-0">Des formules disponibles à la commande.</p></div>
                <a href="/menus" class="btn btn-outline-primary">Tout voir</a>
            </div>
            <div class="row g-4">
                <?php foreach ($menus as $menu): ?>
                    <?php $details = $menuDetails[$menu->getId()] ?? ['images' => [], 'dishes' => [], 'allergens' => []]; ?>
                    <?php $cover = $details['images'][0] ?? null; ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100 shadow-sm border-0 overflow-hidden">
                            <?php if ($cover): ?>
                                <img src="/uploads/<?= $escape($cover['path']) ?>" class="card-img-top" alt="<?= $escape($cover['alt_text']) ?>" style="height:220px;object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height:220px;">Aucune image disponible</div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <span class="badge text-bg-light align-self-start mb-2"><?= $escape($menu->getTheme()) ?></span>
                                <h3 class="h4 text-primary"><?= $escape($menu->getTitle()) ?></h3>
                                <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>
                                <p class="mb-3"><strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong> pour <?= $menu->getMinPeople() ?> personnes minimum</p>
                                <div class="d-grid gap-2">
                                    <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                                    <?php if (!empty($user)): ?><a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander</a><?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if ($menus === []): ?><p class="text-muted">Les menus seront disponibles prochainement.</p><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="h2 text-primary mb-4">Avis clients</h2>
            <div class="row g-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-4"><article class="card h-100 border-0 shadow-sm"><div class="card-body"><p class="text-warning">★★★★★</p><p class="fst-italic">« <?= $escape($review['comment'] ?? '') ?> »</p><p class="mb-0 text-muted small"><?= $escape($review['user_name'] ?? '') ?></p></div></article></div>
                <?php endforeach; ?>
                <?php if ($reviews === []): ?><p class="text-muted">Les avis validés seront affichés ici.</p><?php endif; ?>
            </div>
        </div>
    </section>
</main>
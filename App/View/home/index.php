<main>
    <section class="hero home-hero text-white py-5">
        <div class="container py-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-light text-primary rounded-pill px-3 py-2 mb-3">Traiteur évènementiel à Bordeaux</span>
                    <h1 class="display-4 fw-bold mb-4">Des réceptions soignées, un choix clair, une commande simple.</h1>
                    <p class="lead mb-4">Mariages, cocktails, repas d'entreprise, anniversaires ou déjeuners d'équipe : des formules adaptées, du service sur place à la livraison.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="/menus" class="btn btn-light btn-lg text-primary fw-bold">Découvrir les menus</a>
                        <?php if (!empty($user)): ?>
                            <a href="/orders/new" class="btn btn-primary btn-lg fw-bold">Démarrer une commande</a>
                        <?php else: ?>
                            <a href="/login" class="btn btn-outline-light btn-lg fw-bold">Se connecter</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-4 p-lg-5">
                            <h2 class="h4 text-primary mb-3">Pourquoi nous choisir ?</h2>
                            <div class="d-grid gap-3">
                                <div class="summary-box p-3">
                                    <strong class="d-block mb-1">Formules lisibles</strong>
                                    <span class="small text-muted">Menus complets, options végétales, cocktails et plats à la carte.</span>
                                </div>
                                <div class="summary-box p-3">
                                    <strong class="d-block mb-1">Service adapté</strong>
                                    <span class="small text-muted">Retrait, livraison ou prestation sur place selon votre évènement.</span>
                                </div>
                                <div class="summary-box p-3">
                                    <strong class="d-block mb-1">Zone d'intervention</strong>
                                    <span class="small text-muted">Bordeaux et sa métropole, avec extension sur demande en Gironde.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h2 text-primary mb-1">Menus à la une</h2>
                    <p class="text-muted mb-0">Les formules disponibles actuellement.</p>
                </div>
                <a href="/menus" class="btn btn-outline-primary">Tout voir</a>
            </div>
            <div class="row g-4">
                <?php foreach ($menus as $menu): ?>
                    <?php $mongoImages = $menuImages[$menu->getId()] ?? []; ?>
                    <?php $cover = $mongoImages[0] ?? null; ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">
                            <?php if ($cover && !empty($cover['url'])): ?>
                                <img src="<?= $escape($cover['url']) ?>" class="card-img-top" alt="<?= $escape($cover['alt_text'] ?? $menu->getTitle()) ?>" style="height:250px;object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height:250px;">Aucune image disponible</div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-primary-subtle text-primary align-self-start mb-2"><?= $escape($menu->getTheme()) ?></span>
                                <h3 class="h4 text-primary"><?= $escape($menu->getTitle()) ?></h3>
                                <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>
                                <p class="mb-3"><strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong> · minimum <?= $menu->getMinPeople() ?> personnes</p>
                                <div class="d-grid gap-2">
                                    <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                                    <?php if (!empty($user)): ?>
                                        <a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander</a>
                                    <?php else: ?>
                                        <a class="btn btn-primary" href="/login">Se connecter pour commander</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if ($menus === []): ?>
                    <div class="col-12"><p class="text-muted mb-0">Les menus seront disponibles prochainement.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="h2 text-primary fw-bold mb-1">Avis clients</h2>
                    <p class="text-muted mb-0">Quelques retours après des prestations.</p>
                </div>
                <?php if (!empty($user)): ?>
                    <a class="btn btn-outline-primary" href="/orders">Mes commandes</a>
                <?php endif; ?>
            </div>
            <div class="row g-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-4">
                        <article class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <p class="text-warning mb-2"><?= str_repeat('★', max(0, min(5, (int) ($review['rating'] ?? 5)))) ?></p>
                                <p class="fst-italic">« <?= $escape($review['comment'] ?? '') ?> »</p>
                                <p class="mb-0 text-muted small"><?= $escape($review['user_name'] ?? '') ?></p>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if ($reviews === []): ?>
                    <div class="col-12"><p class="text-muted mb-0">Les avis validés seront affichés ici.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
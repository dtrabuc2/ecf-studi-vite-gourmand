<main class="py-5">
    <section class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary-subtle text-primary">Nos formules</span>
            <h1 class="display-6 text-primary fw-bold mt-3">Menus et prestations</h1>
            <p class="text-muted">Choisissez une formule, consultez son aperçu et lancez directement votre commande.</p>
        </div>

        <form id="menuFilters" class="card border-0 shadow-sm p-3 mb-4" data-filter-endpoint="/menus/filter">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="filterTheme">Thème</label>
                    <select id="filterTheme" name="theme" class="form-select">
                        <option value="">Tous les thèmes</option>
                        <?php foreach (array_unique(array_map(static fn ($menu) => $menu->getTheme(), $menus)) as $theme): ?>
                            <option value="<?= $escape($theme) ?>"><?= $escape($theme) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filterRegime">Régime</label>
                    <select id="filterRegime" name="dietary_regime" class="form-select">
                        <option value="">Tous les régimes</option>
                        <option value="classic">Classique</option>
                        <option value="vegetarian">Végétarien</option>
                        <option value="vegan">Végétalien</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterMinPrice">Prix min.</label>
                    <input id="filterMinPrice" name="min_price" class="form-control" type="number" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterMaxPrice">Prix max.</label>
                    <input id="filterMaxPrice" name="max_price" class="form-control" type="number" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterPeople">Personnes min.</label>
                    <input id="filterPeople" name="min_people" class="form-control" type="number" min="1">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-primary" type="submit">Filtrer</button>
                    <button class="btn btn-outline-secondary" type="reset">Réinitialiser</button>
                </div>
            </div>
        </form>

        <div id="menuFilterStatus" class="visually-hidden" aria-live="polite"></div>

        <div id="menuGrid" class="row g-4">
            <?php foreach ($menus as $menu): ?>
                <?php $details = $menuDetails[$menu->getId()] ?? ['images' => [], 'dishes' => [], 'allergens' => []]; ?>
                <?php $mongoImages = $menuImages[$menu->getId()] ?? []; ?>
                <?php $cover = $mongoImages[0] ?? null; ?>
                <div class="col-md-6 col-lg-4" data-menu-card>
                    <article class="card h-100 border-0 shadow-sm overflow-hidden">
                        <?php if ($cover && !empty($cover['url'])): ?>
                            <img src="<?= $escape($cover['url']) ?>" class="card-img-top" alt="<?= $escape($cover['alt_text']) ?>" style="height:220px;object-fit:cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height:220px;">
                                <span>Aucune image disponible</span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="badge bg-primary-subtle text-primary"><?= $escape($menu->getTheme()) ?></span>
                                <strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong>
                            </div>
                            <h2 class="h4 text-primary mt-3"><?= $escape($menu->getTitle()) ?></h2>
                            <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>

                            <?php $dishesByCategory = ['starter' => [], 'main' => [], 'dessert' => []]; ?>
                            <?php foreach ($details['dishes'] as $dish): ?>
                                <?php if (isset($dishesByCategory[$dish['category']])): ?>
                                    <?php $dishesByCategory[$dish['category']][] = $dish['name']; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <div class="bg-light p-3 rounded mb-3 small text-muted">
                                <p class="mb-1"><strong>Entrées :</strong> <?= $escape(implode(', ', array_slice($dishesByCategory['starter'], 0, 2)) ?: 'Non renseignées') ?></p>
                                <p class="mb-1"><strong>Plats :</strong> <?= $escape(implode(', ', array_slice($dishesByCategory['main'], 0, 2)) ?: 'Non renseignés') ?></p>
                                <p class="mb-0"><strong>Desserts :</strong> <?= $escape(implode(', ', array_slice($dishesByCategory['dessert'], 0, 2)) ?: 'Non renseignés') ?></p>
                            </div>

                            <p class="small mb-3">Minimum : <?= $menu->getMinPeople() ?> personnes · Stock : <?= $menu->getAvailableStock() ?></p>
                            <div class="d-grid gap-2 mt-auto">
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
                <div class="col-12"><div class="alert alert-warning">Aucun menu disponible pour le moment.</div></div>
            <?php endif; ?>
        </div>
    </section>
</main>
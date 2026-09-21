<?php
$errors = $_SESSION['order_errors'] ?? [];
$oldInput = $_SESSION['order_old_input'] ?? [];
unset($_SESSION['order_errors'], $_SESSION['order_old_input']);
$old = static fn(string $key, string $default = ''): string => htmlspecialchars((string) ($oldInput[$key] ?? $default), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$selectedId = (int) ($oldInput['menu_id'] ?? $selectedMenuId);
$selectedServiceType = (string) ($oldInput['service_type'] ?? 'delivery');
if (!in_array($selectedServiceType, ['delivery', 'pickup', 'on_site'], true)) {
    $selectedServiceType = 'delivery';
}
?>
<main class="py-5">
    <div class="container">
        <div id="orderNotification"></div>
        <section class="mb-5">
            <div class="row align-items-end g-4">
                <div class="col-lg-8">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">Étape 1</span>
                    <h1 class="h2 text-primary fw-bold mt-3 mb-3">Choisissez votre type de prestation</h1>
                    <p class="text-muted mb-0">Sélectionnez un menu complet ou une formule à la carte. Le formulaire final reste relié au vrai backend PHP.</p>
                </div>
            </div>
            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <button class="card service-selector h-100 p-4 shadow-sm border-0 text-start w-100 bg-white" type="button" data-type="menu">
                        <span class="display-6 text-primary mb-3"><i class="fas fa-utensils"></i></span>
                        <span class="h3 fw-bold d-block mb-3 text-dark">Menu complet</span>
                        <span class="text-muted d-block mb-4">Une formule avec entrées, plats, desserts et boissons.</span>
                        <span class="btn btn-outline-primary w-100">Choisir cette formule</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="card service-selector h-100 p-4 shadow-sm border-0 text-start w-100 bg-white" type="button" data-type="plat">
                        <span class="display-6 text-primary mb-3"><i class="fas fa-bowl-food"></i></span>
                        <span class="h3 fw-bold d-block mb-3 text-dark">À la carte</span>
                        <span class="text-muted d-block mb-4">Des plats unitaires pour une demande plus libre.</span>
                        <span class="btn btn-outline-primary w-100">Choisir cette formule</span>
                    </button>
                </div>
            </div>
        </section>

        <section id="orderBuilder" class="d-none">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4 p-lg-5">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">Étape 2</span>
                            <h2 id="orderSelectionTitle" class="h3 text-primary mb-3">Votre menu</h2>
                            <label class="form-label fw-bold" for="selectMenu">Sélection</label>
                            <select id="selectMenu" class="form-select form-select-lg mb-4">
                                <option value="">-- Sélectionnez une formule --</option>
                            </select>
                            <div id="aLaCarteDishField" class="d-none mb-4"><label class="form-label fw-bold" for="selectDish">Plat à la carte</label><select id="selectDish" class="form-select"><option value="">-- Sélectionnez un plat --</option></select></div>
                            <div id="detailMenuSelectionne" class="d-none">
                                <img id="imageMenu" src="" alt="Sélection en cours" class="rounded mb-3 w-100 shadow-sm menu-preview-image">
                                <h3 id="nomMenuAffiche" class="h4 text-primary fw-bold"></h3>
                                <p id="descriptionMenu" class="text-muted small mb-3"></p>
                                <p id="prixMenuAffiche" class="h4 text-primary fw-bold mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div id="sectionPersonnalisation" class="card shadow-sm border-0 d-none">
                        <div class="card-body p-4 p-lg-5">
                            <span class="badge bg-dark-subtle text-dark rounded-pill px-3 py-2 mb-3">Étape 3</span>
                            <h2 class="h3 text-primary mb-4">Personnalisez votre prestation</h2>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="summary-box p-3 h-100"><h3 class="h5 mb-3">Entrées</h3><div id="listEntrees"></div></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-box p-3 h-100"><h3 class="h5 mb-3">Plats</h3><div id="listPlats"></div></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-box p-3 h-100"><h3 class="h5 mb-3">Desserts</h3><div id="listDesserts"></div></div>
                                </div>
                            </div>
                            <div class="mt-4"><h3 class="h5 mb-2">Ingrédients non désirés</h3><p class="small text-muted">Cochez les ingrédients que vous ne souhaitez pas retrouver dans votre prestation.</p><div id="listIngredients" class="row g-2"></div></div>
                            <hr class="my-4">
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-6">
                                    <h3 class="h5 mb-3">Boissons</h3>
                                    <div class="d-flex flex-wrap gap-3 mb-3">
                                        <div class="form-check"><input class="form-check-input" type="radio" name="typeBoisson" id="boissonSans" value="sans" checked><label class="form-check-label" for="boissonSans">Sans alcool</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="typeBoisson" id="boissonAvec" value="avec"><label class="form-check-label" for="boissonAvec">Avec alcool</label></div>
                                    </div>
                                    <div id="listBoissons"></div>
                                </div>
                                <div class="col-lg-6">
                                    <h3 class="h5 mb-3">Digestif</h3>
                                    <select id="selectDigestif" class="form-select"><option value="">Aucun</option></select>
                                    <p id="digestifHelp" class="text-muted small mt-2 mb-0">Disponible uniquement avec une boisson alcoolisée.</p>
                                </div>
                            </div>
                            <div class="d-grid d-lg-flex justify-content-lg-end mt-4">
                                <button id="btnVersRecap" class="btn btn-primary btn-lg px-5" type="button">Continuer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="orderFinalForm" class="d-none mt-5">
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>La commande n’a pas pu être enregistrée.</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <?php if (is_string($error) && trim($error) !== ''): ?>
                                <li><?= $escape($error) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" action="/orders" class="card border-0 shadow-sm p-4 p-lg-5">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" id="selected_options" name="selected_options" value="">
                <input type="hidden" id="selected_dish_id" name="selected_dish_id" value="">
                <div class="row g-3">
                    <div class="col-12"><h2 class="h3 text-primary">Récapitulatif de la commande</h2><p class="text-muted">Le montant final est recalculé et validé par le serveur.</p></div>
                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label fw-bold">Mode de prestation</legend>
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-check h-100 border rounded p-3 service-type-option">
                                        <input class="form-check-input me-2" type="radio" name="service_type" value="delivery" <?= $selectedServiceType === 'delivery' ? 'checked' : '' ?>>
                                        <strong>Livraison</strong>
                                        <span class="d-block small text-muted mt-1">Adresse et téléphone requis. Les instructions de livraison sont facultatives.</span>
                                    </label>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-check h-100 border rounded p-3 service-type-option">
                                        <input class="form-check-input me-2" type="radio" name="service_type" value="pickup" <?= $selectedServiceType === 'pickup' ? 'checked' : '' ?>>
                                        <strong>À emporter</strong>
                                        <span class="d-block small text-muted mt-1">Téléphone et heure de retrait requis.</span>
                                    </label>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-check h-100 border rounded p-3 service-type-option">
                                        <input class="form-check-input me-2" type="radio" name="service_type" value="on_site" <?= $selectedServiceType === 'on_site' ? 'checked' : '' ?>>
                                        <strong>Sur place</strong>
                                        <span class="d-block small text-muted mt-1">Téléphone et heure d’arrivée. Adresse du restaurant affichée.</span>
                                    </label>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6"><label class="form-label">Nom</label><input class="form-control" value="<?= $escape($orderUser?->getLastName() ?? '') ?>" readonly></div>
                    <div class="col-md-6"><label class="form-label">Prénom</label><input class="form-control" value="<?= $escape($orderUser?->getFirstName() ?? '') ?>" readonly></div>
                    <div class="col-md-6"><label class="form-label" for="contact_phone">Téléphone de contact</label><input class="form-control" id="contact_phone" name="contact_phone" type="tel" inputmode="tel" autocomplete="tel" value="<?= $old('contact_phone', $orderUser?->getGsm() ?: $orderUser?->getPhone() ?? '') ?>" placeholder="+33 6 12 34 56 78" required><div class="form-text">France : +33 6/7… ou 06/07… — Espagne : +34 6/7/8/9…</div></div>
                    <div class="col-md-6"><label class="form-label">E-mail</label><input class="form-control" value="<?= $escape($orderUser?->getEmail() ?? '') ?>" readonly></div>
                    <div class="col-12"><hr></div>
                    <div class="col-md-4"><label class="form-label" for="menu_id">Menu</label><select class="form-select" id="menu_id" name="menu_id" required><option value="">Sélectionnez un menu</option><?php foreach ($menus as $menu): ?><option value="<?= $menu->getId() ?>" data-base-price="<?= $menu->getBasePrice() ?>" data-min-people="<?= $menu->getMinPeople() ?>" <?= $selectedId === $menu->getId() ? 'selected' : '' ?>><?= $escape($menu->getTitle()) ?> — <?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="number_of_people">Nombre de personnes</label><input class="form-control" id="number_of_people" name="number_of_people" type="number" min="1" value="<?= $old('number_of_people') ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="delivery_date">Date</label><input class="form-control" id="delivery_date" name="delivery_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= $old('delivery_date') ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="delivery_time" id="serviceTimeLabel">Heure de livraison</label><input class="form-control" id="delivery_time" name="delivery_time" type="time" value="<?= $old('delivery_time') ?>" required><div class="form-text" id="serviceTimeHelp">Heure prévue de remise au client.</div></div>
                    <div class="col-12 mb-3">
                        <div id="serviceContactHelp" class="alert alert-secondary mb-0">
                            <strong>Téléphone de contact :</strong> requis pour la livraison, le retrait et l’arrivée sur place.
                        </div>
                    </div>
                    <div id="deliveryFields" class="row g-3">
                        <div class="col-12 d-none" id="pickupLocationNotice">
                            <div class="alert alert-info mb-0">
                                <strong>Retrait / sur place :</strong> Vite &amp; Gourmand, Bordeaux. L’adresse exacte du restaurant est reprise dans le récapitulatif de commande.
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="address-autocomplete-wrap">
                                <label class="form-label" for="delivery_address">Adresse de livraison</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2" placeholder="Commencez à saisir une adresse..."><?= $old('delivery_address', $orderUser?->getAddress() ?? '') ?></textarea>
                                <div id="addressSuggestions" class="list-group address-suggestions d-none" role="listbox" aria-label="Suggestions d’adresse"></div>
                                <div id="addressHint" class="form-text">L’adresse peut être proposée automatiquement. Ajoutez résidence, bâtiment, étage, appartement et toute instruction utile au livreur.</div>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label" for="delivery_postal_code">Code postal</label><input class="form-control" id="delivery_postal_code" name="delivery_postal_code" maxlength="10" value="<?= $old('delivery_postal_code') ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="delivery_city">Ville</label><input class="form-control" id="delivery_city" name="delivery_city" value="<?= $old('delivery_city') ?>" placeholder="Bordeaux" required></div>
                        <div class="col-md-6"><label class="form-label" for="delivery_distance_km">Distance depuis Bordeaux (km)</label><input class="form-control" id="delivery_distance_km" name="delivery_distance_km" type="number" min="0" step="0.01" value="<?= $old('delivery_distance_km') ?>"><div class="form-text">Requise hors Bordeaux. 5 € + 0,59 €/km.</div></div>
                    </div>
                    <div class="col-12" id="deliveryInstructionsField">
                        <label class="form-label" for="delivery_instructions">Instructions pour le livreur <span class="text-muted">(facultatif)</span></label>
                        <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="2" placeholder="Interphone, étage, appel du client..."><?= $old('delivery_instructions') ?></textarea>
                    </div>
                    <div class="col-12 d-none" id="onSiteAddressNotice">
                        <div class="alert alert-secondary mb-0">
                            <strong>Adresse du restaurant :</strong> Vite &amp; Gourmand, Bordeaux. Veuillez prévoir votre arrivée à l’heure indiquée.
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <strong>Paiement :</strong> règlement obligatoire en espèces sur place lors de la remise.
                            <input type="hidden" name="payment_method" value="cash_on_site">
                        </div>
                    </div>
                    <div class="col-12">
                        <section class="card bg-light border-0">
                            <div class="card-body">
                                <h3 class="h5 text-primary">Prix indicatif</h3>
                                <dl class="row mb-0">
                                    <dt class="col-8">Prix menu</dt><dd class="col-4 text-end" id="orderMenuPrice">0,00 €</dd>
                                    <dt class="col-8" id="orderDeliveryLabel">Livraison</dt><dd class="col-4 text-end" id="orderDeliveryPrice">0,00 €</dd>
                                    <dt class="col-8 fw-bold">Total</dt><dd class="col-4 text-end fw-bold" id="orderTotalPrice">0,00 €</dd>
                                </dl>
                            </div>
                        </section>
                    </div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Valider la commande</button><a class="btn btn-outline-secondary ms-2" href="/menus">Retour aux menus</a></div>
                </div>
            </form>
        </section>
    </div>
</main>
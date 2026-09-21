<?php
$errors = is_array($errors ?? null) ? $errors : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$old = static function (string $key, mixed $default = '') use ($oldInput): string {
    return htmlspecialchars((string) ($oldInput[$key] ?? $default), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$selectedId = isset($selectedId) ? (int) $selectedId : (int) ($selectedMenuId ?? 0);
$selectedServiceType = (string) ($selectedServiceType ?? $oldInput['service_type'] ?? 'delivery');
$orderUser = $orderUser ?? null;
$menus = is_array($menus ?? null) ? $menus : [];
?>
        <section id="orderFinalForm" class="mt-5">
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
                    <div class="col-md-4">
                        <label class="form-label" for="delivery_date">Date</label>
                        <input class="form-control" id="delivery_date" name="delivery_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= $old('delivery_date') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="delivery_time" id="serviceTimeLabel">Créneau horaire</label>
                        <select class="form-select" id="delivery_time" name="delivery_time" required>
                            <option value="">Choisir un créneau</option>
                        </select>
                        <div class="form-text" id="serviceTimeHelp">Créneaux de 15 minutes. Les horaires passés sont masqués pour aujourd’hui.</div>
                    </div>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dateField = document.getElementById('delivery_date');
    const timeField = document.getElementById('delivery_time');
    if (!dateField || !timeField) return;

    const oldTime = <?= json_encode((string) ($oldInput['delivery_time'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const timezoneFormatter = new Intl.DateTimeFormat('fr-FR', {
        timeZone: 'Europe/Paris',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });

    function parisToday() {
        const parts = timezoneFormatter.formatToParts(new Date());
        const values = Object.fromEntries(parts.filter(({type}) => type !== 'literal').map(({type, value}) => [type, value]));
        return values.year + '-' + values.month + '-' + values.day;
    }

    function populateSlots() {
        const selectedDate = dateField.value;
        timeField.innerHTML = '<option value="">Choisir un créneau</option>';
        if (!selectedDate) return;

        const current = new Date();
        const today = parisToday();
        let startMinute = 0;

        if (selectedDate === today) {
            const parisParts = new Intl.DateTimeFormat('fr-FR', {
                timeZone: 'Europe/Paris',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23'
            }).formatToParts(current);
            const hour = Number(parisParts.find(p => p.type === 'hour')?.value ?? 0);
            const minute = Number(parisParts.find(p => p.type === 'minute')?.value ?? 0);
            startMinute = hour * 60 + minute + 1;
        }

        for (let total = startMinute; total < 24 * 60; total += 15) {
            const hour = String(Math.floor(total / 60)).padStart(2, '0');
            const minute = String(total % 60).padStart(2, '0');
            const value = hour + ':' + minute;
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            if (value === oldTime) option.selected = true;
            timeField.appendChild(option);
        }
    }

    dateField.addEventListener('change', populateSlots);
    dateField.min = parisToday();
    populateSlots();
});
</script>
</main>
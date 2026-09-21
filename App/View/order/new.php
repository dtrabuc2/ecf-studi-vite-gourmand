<?php
$errors = is_array($errors ?? null) ? $errors : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$old = static function (string $key, mixed $default = '') use ($oldInput): string {
    return htmlspecialchars((string) ($oldInput[$key] ?? $default), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$selectedId = isset($selectedId) ? (int) $selectedId : (int) ($selectedMenuId ?? 0);
$selectedServiceType = (string) ($selectedServiceType ?? $oldInput['service_type'] ?? 'delivery');
$orderUser = $orderUser ?? null;
?>
<main class="py-5">
    <div class="container">
        <h1 class="h2 text-primary mb-4">Nouvelle commande</h1>

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

        <form id="orderFinalForm" method="post" action="/orders" class="card border-0 shadow-sm p-4 p-lg-5">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" id="confirmedGuestCount" name="confirmed_guest_count" value="<?= $old('number_of_people') ?>">

            <div class="row g-3">
                <div class="col-12">
                    <h2 class="h3 text-primary">1. Préparer la commande</h2>
                    <p class="text-muted mb-0">Le nombre de convives est confirmé avant l’affichage des menus disponibles.</p>
                </div>

                <div class="col-12">
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>Nombre de convives :</strong>
                            <span id="guestCountSummary" class="text-muted">Non renseigné</span>
                            <button type="button" id="openGuestsModal" class="btn btn-outline-primary btn-sm">
                                Indiquer / modifier
                            </button>
                        </div>
                    </div>
                </div>

                <div id="guestModal" class="modal fade" tabindex="-1" aria-labelledby="guestModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="guestModalLabel" class="modal-title h5">Nombre de convives</h3>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label" for="number_of_people">Combien de personnes sont attendues ?</label>
                                <input
                                    class="form-control"
                                    id="number_of_people"
                                    name="number_of_people"
                                    type="number"
                                    min="1"
                                    max="999"
                                    value="<?= $old('number_of_people') ?>"
                                    required
                                >
                                <div class="form-text">
                                    De 1 à 14 convives, les menus disponibles peuvent être commandés directement. De 15 à 30 convives, choisissez votre menu traiteur. Au-delà de 30 convives, une demande de devis est obligatoire.
                                </div>
                                <div id="guestValidationMessage" class="alert alert-danger d-none mt-3"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" id="confirmGuestCount">
                                    Confirmer le nombre
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="orderConfiguration" class="d-none">
                    <div class="row g-3">
                        <div class="col-12">
                            <h2 class="h4 text-primary">2. Menu</h2>
                            <p class="text-muted">
                                Le serveur vérifie le stock et le parcours correspondant au nombre de convives.
                                Vert = disponible, rouge = indisponible.
                            </p>
                            <div id="menuAvailabilityMessage" class="alert alert-secondary">
                                Vérification des menus en cours…
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="menu_id">Menu</label>
                            <select class="form-select" id="menu_id" name="menu_id" required disabled>
                                <option value="">Sélectionnez un menu</option>
                            </select>
                            <div id="menuAvailabilityLegend" class="small mt-2"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="contact_phone">Téléphone de contact</label>
                            <input
                                class="form-control"
                                id="contact_phone"
                                name="contact_phone"
                                type="tel"
                                inputmode="tel"
                                autocomplete="tel"
                                value="<?= $old('contact_phone', $orderUser?->getGsm() ?: $orderUser?->getPhone() ?? '') ?>"
                                placeholder="+33 6 12 34 56 78"
                                required
                            >
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-12">
                            <fieldset>
                                <legend class="form-label fw-bold">3. Mode de prestation</legend>
                                <div class="row g-2">
                                    <?php foreach ([
                                        'delivery' => ['Livraison', 'Adresse, ville, code postal et téléphone requis.'],
                                        'pickup' => ['À emporter', 'Téléphone et heure de retrait requis.'],
                                        'on_site' => ['Sur place', 'Téléphone et heure d’arrivée requis.'],
                                    ] as $type => [$label, $description]): ?>
                                        <div class="col-md-4">
                                            <label class="form-check h-100 border rounded p-3 service-type-option">
                                                <input
                                                    class="form-check-input me-2"
                                                    type="radio"
                                                    name="service_type"
                                                    value="<?= $type ?>"
                                                    <?= $selectedServiceType === $type ? 'checked' : '' ?>
                                                >
                                                <strong><?= $escape($label) ?></strong>
                                                <span class="d-block small text-muted mt-1"><?= $escape($description) ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="delivery_date">Date de prestation</label>
                            <input
                                class="form-control"
                                id="delivery_date"
                                name="delivery_date"
                                type="date"
                                min="<?= date('Y-m-d') ?>"
                                value="<?= $old('delivery_date') ?>"
                                required
                            >
                            <div id="dateAvailabilityMessage" class="form-text">
                                Le serveur vérifie le jour et les plages ouvertes.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="delivery_time" id="serviceTimeLabel">Créneau horaire</label>
                            <select class="form-select" id="delivery_time" name="delivery_time" required disabled>
                                <option value="">Choisir une date</option>
                            </select>
                            <div class="form-text">
                                Créneaux de 15 minutes. Mardi/mercredi/vendredi/samedi : 11h30–15h30 puis 18h00–23h00.
                                Jeudi/dimanche : 12h00–20h00. Lundi : fermé.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tarification serveur</label>
                            <div class="border rounded bg-light p-3 small">
                                <div class="d-flex justify-content-between">
                                    <span>Prix menu</span>
                                    <strong id="orderMenuPrice">0,00 €</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Remise</span>
                                    <strong id="orderDiscount">0 %</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Livraison</span>
                                    <strong id="orderDeliveryPrice">À renseigner</strong>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <span id="orderPreviewTotal">À calculer</span>
                                </div>
                            </div>
                            <div id="orderPriceMessage" class="small text-muted mt-2">
                                Le montant définitif est recalculé et validé par PHP au moment de la commande.
                            </div>
                        </div>

                        <div id="deliveryFields" class="row g-3 mt-1">
                            <div class="col-12 d-none" id="pickupLocationNotice">
                                <div class="alert alert-info mb-0">
                                    <strong>Retrait / sur place :</strong> Vite &amp; Gourmand, Bordeaux.
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="address-autocomplete-wrap">
                                    <label class="form-label" for="delivery_address">Adresse de livraison</label>
                                    <textarea
                                        class="form-control"
                                        id="delivery_address"
                                        name="delivery_address"
                                        rows="2"
                                        placeholder="Commencez à saisir une adresse..."
                                    ><?= $old('delivery_address', $orderUser?->getAddress() ?? '') ?></textarea>
                                    <div id="addressSuggestions" class="list-group address-suggestions d-none" role="listbox" aria-label="Suggestions d’adresse"></div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="delivery_postal_code">Code postal</label>
                                <input class="form-control" id="delivery_postal_code" name="delivery_postal_code" maxlength="10" value="<?= $old('delivery_postal_code') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="delivery_city">Ville</label>
                                <input class="form-control" id="delivery_city" name="delivery_city" value="<?= $old('delivery_city') ?>" placeholder="Bordeaux">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="delivery_distance_km">Distance depuis Bordeaux (km)</label>
                                <input class="form-control" id="delivery_distance_km" name="delivery_distance_km" type="number" min="0" step="0.01" value="<?= $old('delivery_distance_km') ?>">
                                <div class="form-text">Requise hors Bordeaux. 5 € + 0,59 €/km.</div>
                            </div>

                            <div class="col-12" id="deliveryInstructionsField">
                                <label class="form-label" for="delivery_instructions">Instructions pour le livreur <span class="text-muted">(facultatif)</span></label>
                                <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="2"><?= $old('delivery_instructions') ?></textarea>
                            </div>
                        </div>

                        <div class="col-12 d-none" id="onSiteAddressNotice">
                            <div class="alert alert-secondary mb-0">
                                <strong>Adresse du restaurant :</strong> Vite &amp; Gourmand, Bordeaux.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <strong>Paiement :</strong> règlement obligatoire en espèces sur place lors de la remise.
                            </div>
                        </div>

                        <input type="hidden" name="payment_method" value="cash_on_site">

                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Valider la commande</button>
                            <a class="btn btn-outline-secondary ms-2" href="/menus">Retour aux menus</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

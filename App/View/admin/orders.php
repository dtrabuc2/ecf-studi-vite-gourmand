<?php $labels=['pending'=>'En attente','accepted'=>'Acceptée','preparing'=>'En préparation','delivering'=>'En livraison','delivered'=>'Livrée','awaiting_return'=>'En attente de retour','completed'=>'Terminée','cancelled'=>'Annulée']; ?>
<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary">Espace équipe</span>
            <h1 class="h2 text-primary mt-2 mb-1">Gestion des commandes</h1>
            <p class="text-muted mb-0">Suivi des commandes clients et des transitions de prestation.</p>
        </div>

        <form method="get" action="/admin/orders" class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label" for="adminCustomer">Client</label>
                        <input class="form-control" id="adminCustomer" name="customer"
                               value="<?= $escape($customer ?? '') ?>" placeholder="Nom ou email">
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label" for="adminStatus">Statut</label>
                        <select class="form-select" id="adminStatus" name="status">
                            <option value="">Tous</option>
                            <?php foreach ($labels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>" <?= ($selectedStatus ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-xl-4 d-grid d-xl-flex justify-content-xl-end">
                        <button class="btn btn-primary" type="submit">Filtrer</button>
                    </div>
                </div>
            </div>
        </form>

        <?php foreach ($orders as $order): ?><section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Commande <?= $escape($order->getOrderNumber()) ?></h2>
                <p class="small text-muted mb-0">Identifiant interne : #<?= $order->getId() ?></p>
            </div>
            <span class="badge text-bg-secondary align-self-start">
                <?= $escape($labels[$order->getStatus()] ?? $order->getStatus()) ?>
            </span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Client</span>
                    <strong><?= $escape($order->getUserId()) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Prestation</span>
                    <strong><?= $escape($order->getDeliveryDate()) ?></strong>
                    <span class="small d-block"><?= $escape($order->getDeliveryTime()) ?></span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Type</span>
                    <strong><?= $escape([
                        'delivery' => 'Livraison',
                        'on_site' => 'Sur place',
                        'pickup' => 'À emporter',
                    ][$order->getServiceType()] ?? $order->getServiceType()) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Total</span>
                    <strong><?= number_format($order->getTotalPrice(), 2, ',', ' ') ?> €</strong>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <p class="small mb-1"><strong>Client ID :</strong> <?= $order->getUserId() ?></p>
                <p class="small mb-1"><strong>Téléphone :</strong> <?= $escape($order->getContactPhone()) ?></p>
                <p class="small mb-0"><strong>Lieu :</strong> <?= $escape($order->getDeliveryAddress()) ?>
                    <?= $order->getDeliveryCity() !== '' ? ' — ' . $escape($order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()) : '' ?>
                </p>
            </div>
            <div class="col-12 col-lg-6">
                <p class="small mb-1"><strong>Personnes :</strong> <?= $order->getNumberOfPeople() ?></p>
                <p class="small mb-1"><strong>Menu :</strong> #<?= $order->getMenuId() ?></p>
                <p class="small mb-0"><strong>Total :</strong> <?= number_format($order->getTotalPrice(), 2, ',', ' ') ?> €</p>
            </div>
        </div>

        <p class="small text-muted mb-3">
            Matériel prêté : <?= $order->isEquipmentLoaned() ? 'Oui' : 'Non' ?>
        </p>
<?php
$next = match ($order->getStatus()) {
    'pending' => ['accepted', 'cancelled'],
    'accepted' => ['preparing', 'cancelled'],
    'preparing' => ['delivering', 'cancelled'],
    'delivering' => ['delivered', 'cancelled'],
    'delivered' => $order->isEquipmentLoaned() ? ['awaiting_return'] : ['completed'],
    'awaiting_return' => ['completed'],
    default => [],
};
if($next): ?>
<form method="post" action="/admin/orders/<?= $order->getId() ?>/status" class="row g-2"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="status_<?= $order->getId() ?>">Statut</label>
<select class="form-select" id="status_<?= $order->getId() ?>" name="status"><?php foreach($next as $s): ?><option value="<?= $s ?>"><?= $escape($labels[$s]) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="contact_<?= $order->getId() ?>">Mode de contact</label>
<input class="form-control" id="contact_<?= $order->getId() ?>" name="contact_mode" placeholder="Téléphone, email..." required>
</div>
<div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="reason_<?= $order->getId() ?>">Motif d’annulation</label>
<input class="form-control" id="reason_<?= $order->getId() ?>" name="cancellation_reason" placeholder="Obligatoire si annulation">
</div>
<div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="notes_<?= $order->getId() ?>">Note interne</label>
<input class="form-control" id="notes_<?= $order->getId() ?>" name="notes" placeholder="Note complémentaire">
</div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="equipment_loaned" value="1" id="equipment_<?= $order->getId() ?>" <?= $order->isEquipmentLoaned() ? "checked" : "" ?>><label class="form-check-label" for="equipment_<?= $order->getId() ?>">Matériel prêté</label></div></div><div class="col-12"><button class="btn btn-outline-primary" type="submit">Mettre à jour</button></div></form>
<?php endif; ?></div></section><?php endforeach; ?>
<?php if($orders===[]): ?><p class="text-muted">Aucune commande trouvée.</p><?php endif; ?></div></main>

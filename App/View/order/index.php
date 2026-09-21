<?php
$statusLabels = [
    'pending' => 'En attente',
    'accepted' => 'Acceptée',
    'preparing' => 'En préparation',
    'delivering' => 'En livraison',
    'delivered' => 'Livrée',
    'awaiting_return' => 'En attente de retour',
    'completed' => 'Terminée',
    'cancelled' => 'Annulée',
];
$serviceLabels = [
    'delivery' => 'Livraison',
    'pickup' => 'À emporter',
    'on_site' => 'Sur place',
];
?>
<main class="py-5"><div class="container">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 text-primary mb-1">Mes commandes</h1><p class="text-muted mb-0">Suivez vos prestations, leurs statuts et leurs dates.</p></div><a class="btn btn-primary" href="/orders/new">Nouvelle commande</a></div>
<?php foreach ($orders as $order): ?>
<section class="card border-0 shadow-sm mb-4"><div class="card-body">
<div class="d-flex justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="h5 mb-1">Commande <?= $escape($order->getOrderNumber()) ?></h2>
        <p class="small text-muted mb-0">Identifiant interne : #<?= $order->getId() ?></p>
        <?php if ($order->getCreatedAt() instanceof \DateTimeInterface): ?>
            <p class="small text-muted mb-0">Reçue le <?= $escape($order->getCreatedAt()->format('d/m/Y H:i')) ?></p>
        <?php endif; ?>
    </div>
    <span class="badge text-bg-secondary"><?= $escape($statusLabels[$order->getStatus()] ?? $order->getStatus()) ?></span>
</div>
<p class="mb-1 mt-3"><strong>Prestation :</strong> <?= $escape($serviceLabels[$order->getServiceType()] ?? $order->getServiceType()) ?> — <?= $escape($order->getDeliveryDate()) ?> à <?= $escape($order->getDeliveryTime()) ?></p>
<p class="small text-muted mb-1"><strong>Téléphone :</strong> <?= $escape($order->getContactPhone()) ?></p>
<p class="small text-muted mb-3"><strong>Lieu :</strong> <?= $escape($order->getDeliveryAddress()) ?><?= $order->getDeliveryCity() !== '' ? ' — ' . $escape($order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()) : '' ?></p>
<p class="mb-1"><strong>Personnes :</strong> <?= $order->getNumberOfPeople() ?></p>
<p class="mb-1"><strong>Sous-total menu :</strong> <?= number_format($order->getMenuPrice(),2,',',' ') ?> €</p>
<?php if ($order->getDiscountRate() > 0): ?><p class="small text-success mb-1">Remise appliquée : <?= number_format($order->getDiscountRate(),0) ?> %</p><?php endif; ?>
<p class="mb-1"><strong>Frais de livraison :</strong> <?= number_format($order->getDeliveryCost(),2,',',' ') ?> €</p>
<p class="mb-1"><strong>Total :</strong> <?= number_format($order->getTotalPrice(),2,',',' ') ?> €</p>
<h3 class="h6 mt-4">Suivi</h3><ol class="small ps-3">
<?php foreach (($history[$order->getId()] ?? []) as $entry): ?><li><?= $escape($statusLabels[$entry['status']] ?? $entry['status']) ?> — <?= $entry['changed_at'] instanceof \DateTimeInterface ? $escape($entry['changed_at']->format('d/m/Y H:i')) : '' ?></li><?php endforeach; ?>
</ol>
<?php if ($order->getStatus() === 'pending' && $order->getServiceType() === 'delivery'): ?>
<hr><details><summary class="fw-semibold">Modifier la commande</summary>
<form method="post" action="/orders/<?= $order->getId() ?>/edit" class="row g-2 mt-2">
<input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
<div class="col-md-3"><label class="form-label">Personnes</label><input class="form-control" type="number" min="1" name="number_of_people" value="<?= $order->getNumberOfPeople() ?>" required></div>
<div class="col-md-3"><label class="form-label">Date</label><input class="form-control order-edit-date" type="date" name="delivery_date" value="<?= $escape($order->getDeliveryDate()) ?>" min="<?= date('Y-m-d') ?>" required></div>
<div class="col-md-3"><label class="form-label">Créneau</label><select class="form-select order-edit-time" name="delivery_time" data-current-time="<?= $escape($order->getDeliveryTime()) ?>" required><option value="">Choisir</option></select></div>
<div class="col-md-3"><label class="form-label">Code postal</label><input class="form-control" name="delivery_postal_code" value="<?= $escape($order->getDeliveryPostalCode()) ?>"></div>
<div class="col-md-6"><label class="form-label">Adresse</label><input class="form-control" name="delivery_address" value="<?= $escape($order->getDeliveryAddress()) ?>" required></div>
<div class="col-md-3"><label class="form-label">Ville</label><input class="form-control" name="delivery_city" value="<?= $escape($order->getDeliveryCity()) ?>" required></div>
<div class="col-md-3"><label class="form-label">Distance (km)</label><input class="form-control" type="number" step="0.01" min="0" name="delivery_distance_km" value="<?= $order->getDeliveryDistanceKm() ?? '' ?>"></div>
<div class="col-12"><button class="btn btn-outline-primary btn-sm">Enregistrer les modifications</button></div>
</form></details>
<?php elseif ($order->getStatus() === 'pending'): ?>
<hr>
<div class="alert alert-info mb-0">
    Cette commande est en <strong><?= $escape(strtolower($serviceLabels[$order->getServiceType()] ?? $order->getServiceType())) ?></strong>.
    Les modifications ne sont pas disponibles depuis cet espace pour ce type de prestation.
</div>
<?php endif; ?>
<?php if ($order->getStatus() === 'pending'): ?>
<form method="post" action="/orders/<?= $order->getId() ?>/status" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="status" value="cancelled"><input type="hidden" name="cancellation_reason" value="Annulation demandée par le client"><button class="btn btn-outline-danger btn-sm" data-confirm="Annuler cette commande ?">Annuler la commande</button></form>
<?php endif; ?>
<?php if ($order->getStatus() === 'completed' && empty($reviews[$order->getId()])): ?>
<hr><h3 class="h6">Votre avis</h3><form method="post" action="/orders/<?= $order->getId() ?>/review" class="row g-2">
<input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><div class="col-md-3"><label class="form-label" for="rating_<?= $order->getId() ?>">Note</label><select class="form-select" id="rating_<?= $order->getId() ?>" name="rating" required><option value="">Choisir</option><?php for($rating=1;$rating<=5;$rating++): ?><option value="<?= $rating ?>"><?= $rating ?>/5</option><?php endfor; ?></select></div>
<div class="col-md-9"><label class="form-label" for="comment_<?= $order->getId() ?>">Commentaire</label><textarea class="form-control" id="comment_<?= $order->getId() ?>" name="comment" required></textarea></div><div class="col-12"><button class="btn btn-primary btn-sm">Envoyer l'avis</button></div></form>
<?php elseif (!empty($reviews[$order->getId()])): ?><p class="small text-muted">Avis déjà transmis.</p><?php endif; ?>
</div></section>
<?php endforeach; ?>
<?php if ($orders === []): ?><div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-4">Vous n'avez pas encore de commande.</div></div><?php endif; ?>
</div><script>
document.addEventListener('DOMContentLoaded', () => {
    const todayParis = () => new Intl.DateTimeFormat('fr-CA', {timeZone: 'Europe/Paris'}).format(new Date());

    document.querySelectorAll('.order-edit-date').forEach((dateField) => {
        const form = dateField.closest('form');
        const timeField = form?.querySelector('.order-edit-time');
        if (!timeField) return;
        const currentTime = timeField.dataset.currentTime || '';

        const populate = () => {
            const selectedDate = dateField.value;
            timeField.innerHTML = '<option value="">Choisir</option>';
            if (!selectedDate) return;

            const today = todayParis();
            let startMinute = 0;

            if (selectedDate === today) {
                const parts = new Intl.DateTimeFormat('fr-FR', {
                    timeZone: 'Europe/Paris',
                    hour: '2-digit',
                    minute: '2-digit',
                    hourCycle: 'h23'
                }).formatToParts(new Date());
                const hour = Number(parts.find(p => p.type === 'hour')?.value ?? 0);
                const minute = Number(parts.find(p => p.type === 'minute')?.value ?? 0);
                startMinute = hour * 60 + minute + 1;
            }

            for (let total = startMinute; total < 24 * 60; total += 15) {
                const value = String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (value === currentTime && selectedDate !== today) option.selected = true;
                timeField.appendChild(option);
            }
        };

        dateField.min = todayParis();
        dateField.addEventListener('change', populate);
        populate();
    });
});
</script>
</main>

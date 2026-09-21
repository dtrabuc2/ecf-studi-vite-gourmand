<?php
$days = [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi',7=>'Dimanche'];
$byDay = [];
foreach ($openingHours as $hour) {
    $byDay[(int) $hour['day_of_week']] = $hour;
}
?>
<main class="py-5">
  <div class="container">
    <h1 class="h2 text-primary mb-4">Horaires d'ouverture</h1>
    <p class="text-muted">Les horaires enregistrés ici sont utilisés directement par le serveur pour les créneaux de commande.</p>
    <form method="post" action="/admin/hours" class="card border-0 shadow-sm">
      <div class="card-body">
        <?php foreach ($days as $day => $label): ?>
          <?php
          $h = $byDay[$day] ?? [
              'is_open' => 0,
              'opening_time' => '',
              'closing_time' => '',
              'opening_time_2' => '',
              'closing_time_2' => '',
          ];
          ?>
          <div class="border rounded p-3 mb-3">
            <div class="row g-2 align-items-end">
              <div class="col-md-2"><strong><?= $escape($label) ?></strong></div>
              <div class="col-md-2">
                <label class="form-label">Ouvert</label>
                <input class="form-check-input d-block" type="checkbox" name="is_open[<?= $day ?>]" <?= (int) $h['is_open'] === 1 ? 'checked' : '' ?>>
              </div>
              <div class="col-md-4">
                <label class="form-label">Plage 1</label>
                <div class="input-group">
                  <input class="form-control" type="time" name="windows[<?= $day ?>][0][opening]" value="<?= $escape(substr((string) $h['opening_time'], 0, 5)) ?>">
                  <input class="form-control" type="time" name="windows[<?= $day ?>][0][closing]" value="<?= $escape(substr((string) $h['closing_time'], 0, 5)) ?>">
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Plage 2</label>
                <div class="input-group">
                  <input class="form-control" type="time" name="windows[<?= $day ?>][1][opening]" value="<?= $escape(substr((string) $h['opening_time_2'], 0, 5)) ?>">
                  <input class="form-control" type="time" name="windows[<?= $day ?>][1][closing]" value="<?= $escape(substr((string) $h['closing_time_2'], 0, 5)) ?>">
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
        <button class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</main>

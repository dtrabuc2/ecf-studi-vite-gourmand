<?php
$errors = \App\Core\Session::pullFlash('profile_errors') ?? [];
$oldInput = \App\Core\Session::pullFlash('profile_old_input') ?? [];
$value = static fn (string $key): string => (string) ($oldInput[$key] ?? $user[$key] ?? '');
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Mon profil</h1>

                        <form method="post" action="/profile" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <?php foreach ([
                                'first_name' => 'Prénom',
                                'last_name' => 'Nom',
                                'email' => 'Email',
                            ] as $field => $label): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $escape($field) ?>"><?= $escape($label) ?></label>
                                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                           id="<?= $escape($field) ?>" name="<?= $escape($field) ?>"
                                           type="<?= $field === 'email' ? 'email' : 'text' ?>"
                                           value="<?= $escape($value($field)) ?>" required>
                                    <?php if (isset($errors[$field])): ?>
                                        <div class="invalid-feedback"><?= $escape($errors[$field]) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach (['phone' => 'Téléphone', 'gsm' => 'GSM'] as $field => $label): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $escape($field) ?>"><?= $escape($label) ?></label>
                                    <div class="input-group">
                                        <select class="form-select flex-grow-0 phone-region" name="<?= $escape($field) ?>_region" aria-label="Pays du <?= $escape(strtolower($label)) ?>" style="max-width: 170px;">
                                            <option value="FR">France +33</option>
                                            <option value="ES">Espagne +34</option>
                                            <option value="BE">Belgique +32</option>
                                            <option value="GB">Royaume-Uni +44</option>
                                            <option value="IT">Italie +39</option>
                                        </select>
                                        <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                               id="<?= $escape($field) ?>" name="<?= $escape($field) ?>" type="tel"
                                               value="<?= $escape($value($field)) ?>" required>
                                    </div>
                                    <?php if (isset($errors[$field])): ?>
                                        <div class="invalid-feedback d-block"><?= $escape($errors[$field]) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <div class="col-12">
                                <label class="form-label" for="address">Adresse</label>
                                <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                          id="address" name="address" required><?= $escape($value('address')) ?></textarea>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= $escape($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if (isset($errors['general'])): ?>
                                <div class="col-12">
                                    <div class="alert alert-danger"><?= $escape($errors['general']) ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
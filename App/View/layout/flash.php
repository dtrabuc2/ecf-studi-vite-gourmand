<?php
$flashKeys = [
    'login_error' => 'danger',
    'register_success' => 'success',
    'register_error' => 'danger',
    'profile_success' => 'success',
    'profile_errors' => 'danger',
    'password_success' => 'success',
    'password_errors' => 'danger',
    'order_success' => 'success',
    'order_error' => 'danger',
    'admin_success' => 'success',
    'admin_error' => 'danger',
    'forgot_success' => 'success',
    'forgot_error' => 'danger',
    'reset_success' => 'success',
    'reset_error' => 'danger',
    'reset_errors' => 'danger',
    'contact_success' => 'success',
    'contact_errors' => 'danger',
];

?>
<div class="container pt-3" aria-live="polite">
    <?php foreach ($flashKeys as $key => $type): ?>
        <?php $message = AppCoreSession::pullFlash($key); ?>
        <?php if (is_array($message)): ?>
            <?php $message = implode(' ', array_map('strval', $message)); ?>
        <?php endif; ?>

        <?php if ($message !== null && $message !== ''): ?>
            <div class="alert alert-<?= $escape($type) ?> alert-dismissible fade show" role="alert">
                <?= $escape($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
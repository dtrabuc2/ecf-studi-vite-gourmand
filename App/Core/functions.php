<?php
declare(strict_types=1);

use App\Core\Config;

function config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    $config ??= new Config(require dirname(__DIR__, 2) . '/config/app.php');

    if ($key === null) {
        return $config->all();
    }

    return $config->get($key, $default);
}
/**
 * Petite icône « i » qui affiche une aide au survol, au toucher et au clavier
 * (infobulle Bootstrap, voir public/assets/js/accessibility.js).
 * Le même texte est en texte caché : un lecteur d'écran le lit sans avoir à survoler.
 * À placer juste APRÈS un <label> (jamais dedans : un bouton dans un label est invalide).
 */
function info_tip(string $text): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    return '<button type="button" class="info-tip" data-bs-toggle="tooltip" data-bs-title="' . $safe . '">'
        . '<span aria-hidden="true">i</span>'
        . '<span class="visually-hidden">Aide : ' . $safe . '</span>'
        . '</button>';
}

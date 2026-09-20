<?php
declare(strict_types=1);

/*
 * La configuration de l'application est désormais centralisée dans config/app.php
 * et lue via App\Core\Config et la fonction globale config().
 */
return require dirname(__DIR__, 2) . '/config/app.php';

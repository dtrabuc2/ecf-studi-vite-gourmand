<?php
declare(strict_types=1);

return [
    'mongodb' => [
        'database' => 'viteetgourmand',
        'collections' => [
            'menu_images' => [
                [
                    'menuId' => 1,
                    'position' => 1,
                    'url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&auto=format&fit=crop',
                    'altText' => 'Présentation gastronomique du Menu de Noël',
                ],
                [
                    'menuId' => 2,
                    'position' => 1,
                    'url' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1200&auto=format&fit=crop',
                    'altText' => 'Présentation gourmande du Menu de Pâques',
                ],
                [
                    'menuId' => 3,
                    'position' => 1,
                    'url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2e8c0?w=1200&auto=format&fit=crop',
                    'altText' => 'Présentation du Menu végétarien',
                ],
            ],
        ],
    ],
];
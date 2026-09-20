-- ================================================================
-- Données de départ du catalogue
-- Les menus, plats, régimes, thèmes et relations restent relationnels.
-- Les images de galerie sont gérées dans MongoDB.
-- ================================================================

INSERT INTO `menus`
    (`id`, `title`, `description`, `theme`, `dietary_regime`, `min_people`, `base_price`, `conditions`, `available_stock`)
VALUES
    (1, 'Menu Prestige', 'Une formule gastronomique pour les mariages, réceptions élégantes et événements professionnels.', 'Évènement', 'classic', 10, 49.50, 'Réservation conseillée au moins 7 jours à l’avance. Service sur place selon disponibilité.', 20),
    (2, 'Menu Végétarien Délice', 'Une formule gourmande à base de légumes et produits de saison.', 'Classique', 'vegetarian', 8, 35.00, 'Commande conseillée 5 jours avant la prestation. Options sans lactose sur demande.', 20),
    (3, 'Menu Cocktail Chic', 'Une sélection pensée pour les cocktails dînatoires et événements professionnels.', 'Évènement', 'classic', 15, 42.00, 'Commande recommandée 10 jours avant la prestation. Service et vaisselle premium selon disponibilité.', 20),
    (4, 'Menu Enfant Gourmand', 'Une formule familiale adaptée aux anniversaires et réceptions avec enfants.', 'Classique', 'classic', 5, 18.00, 'Réservation conseillée 3 jours avant. Couverts et serviettes inclus.', 30),
    (5, 'Menu Méditerranéen Végétal', 'Une formule entièrement végétale inspirée des saveurs méditerranéennes.', 'Classique', 'vegan', 6, 34.00, 'Commande conseillée 4 jours avant. Préparation 100 % végétale.', 15),
    (6, 'Menu Saveurs de la Mer', 'Une formule autour des poissons et fruits de mer pour les repas festifs.', 'Classique', 'classic', 8, 39.50, 'Commande conseillée 5 jours avant. Selon arrivage.', 12)
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `theme` = VALUES(`theme`),
    `dietary_regime` = VALUES(`dietary_regime`),
    `min_people` = VALUES(`min_people`),
    `base_price` = VALUES(`base_price`),
    `conditions` = VALUES(`conditions`),
    `available_stock` = VALUES(`available_stock`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `dishes` (`id`, `name`, `description`) VALUES
    (10, 'Foie gras maison et chutney de figues', 'Foie gras de canard accompagné d’un chutney de figues.'),
    (11, 'Saint-Jacques snackées aux agrumes', 'Noix de Saint-Jacques juste snackées, sauce légère aux agrumes.'),
    (12, 'Filet de bœuf sauce truffée', 'Filet de bœuf rôti avec jus réduit à la truffe.'),
    (13, 'Homard grillé et légumes de saison', 'Homard grillé accompagné de légumes frais.'),
    (14, 'Sphère chocolat grand cru', 'Dessert individuel au chocolat noir et cœur fondant.'),
    (15, 'Paris-Brest pistache', 'Pâte à choux et crème légère à la pistache.'),
    (16, 'Millefeuille vanille', 'Feuilletage croustillant et crème vanille.'),
    (17, 'Gaspacho de tomates anciennes', 'Gaspacho frais de tomates anciennes et herbes.'),
    (18, 'Tartare avocat mangue et herbes', 'Avocat, mangue, citron vert et herbes aromatiques.'),
    (19, 'Risotto crémeux aux cèpes', 'Risotto aux cèpes, parmesan et persil.'),
    (20, 'Tarte fine aux légumes du soleil', 'Courgette, poivron et tomate sur pâte fine.'),
    (21, 'Pavlova aux fruits exotiques', 'Meringue légère, crème fouettée et fruits exotiques.'),
    (22, 'Moelleux chocolat et fruits rouges', 'Gâteau chocolat fondant et fruits rouges.'),
    (23, 'Mini burgers foie gras et figue', 'Mini burger brioché au foie gras et à la figue.'),
    (24, 'Verrines saumon gravlax et aneth', 'Saumon mariné, crème citronnée et aneth.'),
    (25, 'Brochettes de poulet façon yakitori', 'Poulet mariné et légèrement caramélisé.'),
    (26, 'Gambas tempura sauce aigre-douce', 'Gambas croustillantes et sauce douce-acidulée.'),
    (27, 'Assortiment de macarons', 'Sélection de macarons aux parfums variés.'),
    (28, 'Mini éclairs chocolat, café et vanille', 'Petits éclairs garnis de crèmes classiques.'),
    (29, 'Roulé jambon fromage', 'Roulé moelleux au jambon et fromage.'),
    (30, 'Velouté doux de potimarron', 'Velouté de potimarron à la texture crémeuse.'),
    (31, 'Taboulé coloré', 'Semoule, tomates, concombre et herbes fraîches.'),
    (32, 'Nuggets de poulet maison et frites', 'Nuggets de poulet maison et pommes de terre rôties.'),
    (33, 'Pâtes bolognaise', 'Pâtes et sauce tomate à la viande.'),
    (34, 'Filet de poisson pané et purée', 'Poisson pané croustillant et purée.'),
    (35, 'Brownie chocolat', 'Brownie moelleux au chocolat.'),
    (36, 'Salade de fruits frais', 'Assortiment de fruits de saison.'),
    (37, 'Couscous de légumes rôtis', 'Semoule, pois chiches et légumes rôtis aux épices douces.'),
    (38, 'Falafels aux herbes et tahini', 'Falafels maison, herbes fraîches et sauce tahini.'),
    (39, 'Tofu grillé au gingembre', 'Tofu mariné puis grillé au gingembre et citron vert.'),
    (40, 'Ratatouille provençale', 'Courgette, aubergine, tomate et poivron mijotés.'),
    (41, 'Salade niçoise végétale', 'Tomates, haricots verts, pommes de terre, olives et pois chiches.'),
    (42, 'Tartelette citron', 'Tartelette au citron avec meringue légère.'),
    (43, 'Compote pomme-poire maison', 'Compote de fruits sans sucre ajouté.'),
    (44, 'Pommes rôties à la cannelle', 'Pommes rôties parfumées à la cannelle.'),
    (45, 'Moules marinières et frites', 'Moules cuisinées au vin blanc et accompagnées de frites.'),
    (46, 'Paella valencienne', 'Riz safrané, poulet, fruits de mer et légumes.'),
    (47, 'Côte de bœuf, frites et salade', 'Côte de bœuf grillée, frites maison et salade verte.'),
    (48, 'Tofu grillé, légumes croquants et quinoa', 'Tofu grillé, légumes de saison et quinoa.'),
    (50, 'Gratin de légumes au lait de coco', 'Légumes de saison rôtis et sauce au lait de coco.')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `menu_dishes` (`menu_id`, `dish_id`, `category`, `position`) VALUES
    (1, 10, 'starter', 1), (1, 11, 'starter', 2),
    (1, 12, 'main', 1), (1, 13, 'main', 2),
    (1, 14, 'dessert', 1), (1, 15, 'dessert', 2), (1, 16, 'dessert', 3),
    (2, 17, 'starter', 1), (2, 18, 'starter', 2),
    (2, 19, 'main', 1), (2, 20, 'main', 2),
    (2, 21, 'dessert', 1), (2, 22, 'dessert', 2),
    (3, 23, 'starter', 1), (3, 24, 'starter', 2),
    (3, 25, 'main', 1), (3, 26, 'main', 2),
    (3, 27, 'dessert', 1), (3, 28, 'dessert', 2),
    (4, 29, 'starter', 1), (4, 30, 'starter', 2), (4, 31, 'starter', 3),
    (4, 32, 'main', 1), (4, 33, 'main', 2), (4, 34, 'main', 3),
    (4, 35, 'dessert', 1), (4, 36, 'dessert', 2),
    (5, 37, 'starter', 1), (5, 38, 'starter', 2),
    (5, 39, 'main', 1), (5, 40, 'main', 2), (5, 41, 'main', 3),
    (5, 42, 'dessert', 1), (5, 43, 'dessert', 2), (5, 44, 'dessert', 3),
    (6, 24, 'starter', 1), (6, 45, 'main', 1), (6, 46, 'main', 2),
    (6, 22, 'dessert', 1), (6, 36, 'dessert', 2)
ON DUPLICATE KEY UPDATE
    `category` = VALUES(`category`),
    `position` = VALUES(`position`);

INSERT INTO `allergens` (`id`, `name`) VALUES
    (5, 'Fruits à coque'),
    (6, 'Soja'),
    (7, 'Sésame'),
    (8, 'Moutarde')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `dish_allergens` (`dish_id`, `allergen_id`) VALUES
    (10,1), (10,2), (11,4), (12,2), (13,2),
    (14,1), (14,2), (14,3), (15,1), (15,2), (15,3),
    (16,1), (16,2), (16,3), (18,7), (19,2), (22,2), (22,3),
    (23,1), (23,2), (24,4), (25,6), (26,4), (27,3), (28,1), (28,2), (28,3),
    (29,1), (29,2), (30,2), (31,1), (32,1), (32,3), (33,1), (33,2),
    (34,1), (34,3), (38,7), (39,6), (42,3), (45,4), (46,4)
ON DUPLICATE KEY UPDATE
    `allergen_id` = VALUES(`allergen_id`);

INSERT INTO `opening_hours` (`day_of_week`, `is_open`, `opening_time`, `closing_time`) VALUES
    (1, 1, '09:00:00', '18:00:00'),
    (2, 1, '09:00:00', '18:00:00'),
    (3, 1, '09:00:00', '18:00:00'),
    (4, 1, '09:00:00', '18:00:00'),
    (5, 1, '09:00:00', '18:00:00'),
    (6, 1, '10:00:00', '16:00:00'),
    (7, 1, '10:00:00', '16:00:00')
ON DUPLICATE KEY UPDATE
    `is_open` = VALUES(`is_open`),
    `opening_time` = VALUES(`opening_time`),
    `closing_time` = VALUES(`closing_time`);

-- Compte administrateur de démonstration local.
INSERT INTO `users`
    (`email`, `password`, `role`, `first_name`, `last_name`, `phone`, `gsm`, `address`, `is_active`)
VALUES
    ('admin@viteetgourmand.local',
     '$2y$12$reyyfX4tbPWQ5mCzO6XAk.Esqx7U5wkuUIYQek5TpKxdzPLBk5jru',
     'admin',
     'Admin',
     'Vite & Gourmand',
     '0500000000',
     '0600000000',
     'Adresse administrative',
     1)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `role` = 'admin',
    `is_active` = 1,
    `first_name` = VALUES(`first_name`),
    `last_name` = VALUES(`last_name`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Les employés sont créés et gérés depuis l’espace administrateur.
-- Les clients peuvent créer leur compte via /register.

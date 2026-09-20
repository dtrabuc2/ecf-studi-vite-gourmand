-- ================================================================
-- Données de départ du catalogue
-- Les menus, plats, régimes, thèmes et relations restent relationnels.
-- Les images de galerie sont gérées dans MongoDB.
-- ================================================================

INSERT INTO `menus`
    (`id`, `title`, `description`, `theme`, `dietary_regime`, `min_people`, `base_price`, `conditions`, `available_stock`)
VALUES
    (1, 'Menu Prestige Signature', 'Formule traiteur haut de gamme pour mariages, réceptions privées et événements professionnels.', 'Gastronomique', 'classic', 12, 74.00, 'Réservation recommandée 7 jours à l’avance. Livraison, dressage et service selon option.', 20),
    (2, 'Menu Végétal Élégance', 'Menu gastronomique végétarien inspiré de la cuisine végétale contemporaine.', 'Végétal', 'vegetarian', 8, 46.00, 'Réservation recommandée 5 jours à l’avance. Adaptation vegan possible selon composition.', 20),
    (3, 'Menu Cocktail Signature', 'Sélection de bouchées et pièces cocktail pour réceptions et événements professionnels.', 'Cocktail', 'classic', 15, 52.00, 'Réservation recommandée 7 jours à l’avance. Service et verrerie selon option.', 20),
    (4, 'Menu Enfant Gourmand', 'Formule enfant avec plat, accompagnement, boisson et dessert.', 'Famille', 'classic', 5, 10.90, 'Réservé aux enfants. Composition adaptée aux goûts des plus jeunes.', 30),
    (5, 'Menu Méditerranéen Vegan', 'Formule 100 % végétale inspirée des saveurs méditerranéennes.', 'Méditerranéen', 'vegan', 6, 42.00, 'Réservation recommandée 4 jours à l’avance. Préparation entièrement végétale.', 15),
    (6, 'Menu Terre & Mer', 'Formule festive associant produits de la mer et pièce de viande.', 'Tradition', 'classic', 8, 55.00, 'Réservation recommandée 5 jours à l’avance. Selon arrivage et disponibilité.', 12)
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
    (10, 'Foie gras maison, chutney de figues', 'Foie gras de canard et chutney de figues.'),
    (11, 'Saint-Jacques rôties, agrumes et herbes', 'Noix de Saint-Jacques snackées, agrumes et herbes fraîches.'),
    (12, 'Filet de bœuf sauce truffée', 'Filet de bœuf rôti, jus réduit et truffe.'),
    (13, 'Homard rôti, légumes de saison', 'Homard rôti accompagné de légumes frais.'),
    (14, 'Sphère chocolat noir, cœur praliné', 'Dessert au chocolat noir et praliné.'),
    (15, 'Paris-Brest pistache', 'Pâte à choux et crème légère à la pistache.'),
    (16, 'Millefeuille vanille bourbon', 'Feuilletage croustillant et crème vanille.'),
    (17, 'Gaspacho de tomates anciennes', 'Tomates anciennes, huile d’olive et herbes.'),
    (18, 'Tartare avocat, mangue et citron vert', 'Avocat, mangue, citron vert et herbes fraîches.'),
    (19, 'Risotto crémeux aux cèpes', 'Risotto aux cèpes, parmesan et persil.'),
    (20, 'Tarte fine aux légumes du soleil', 'Courgette, tomate, poivron et herbes.'),
    (21, 'Pavlova aux fruits exotiques', 'Meringue, crème végétale et fruits exotiques.'),
    (22, 'Moelleux chocolat et fruits rouges', 'Gâteau chocolat fondant accompagné de fruits rouges.'),
    (23, 'Mini burgers gourmets au foie gras', 'Mini burgers briochés, foie gras et condiment fruité.'),
    (24, 'Verrine de saumon gravlax et aneth', 'Saumon mariné, crème citronnée et aneth.'),
    (25, 'Brochette de poulet yakitori', 'Poulet mariné et grillé, sauce soja douce.'),
    (26, 'Gambas croustillantes, sauce aigre-douce', 'Gambas en tempura et sauce aigre-douce.'),
    (27, 'Assortiment de macarons', 'Sélection de macarons aux parfums variés.'),
    (28, 'Mini éclairs chocolat, café et vanille', 'Petits éclairs garnis de crèmes parfumées.'),
    (29, 'Roulé jambon fromage', 'Roulé moelleux au jambon et fromage.'),
    (30, 'Velouté doux de potimarron', 'Potimarron et crème pour une soupe douce.'),
    (31, 'Taboulé coloré aux herbes', 'Semoule, légumes croquants et herbes fraîches.'),
    (32, 'Nuggets de poulet maison', 'Bouchées de poulet panées maison.'),
    (33, 'Pâtes bolognaise', 'Pâtes avec sauce tomate et viande mijotée.'),
    (34, 'Poisson pané et purée maison', 'Poisson croustillant et purée de pommes de terre.'),
    (35, 'Brownie chocolat', 'Brownie moelleux au chocolat noir.'),
    (36, 'Salade de fruits frais', 'Assortiment de fruits de saison.'),
    (37, 'Couscous de légumes rôtis', 'Semoule, pois chiches et légumes rôtis.'),
    (38, 'Falafels aux herbes, sauce tahini', 'Falafels maison et sauce au sésame.'),
    (39, 'Tofu grillé au gingembre', 'Tofu mariné, gingembre et citron vert.'),
    (40, 'Ratatouille provençale', 'Légumes du soleil mijotés à l’huile d’olive.'),
    (41, 'Salade niçoise végétale', 'Tomates, haricots verts, pommes de terre, olives et pois chiches.'),
    (42, 'Tartelette citron meringuée', 'Crème de citron et meringue légère.'),
    (43, 'Compote pomme-poire maison', 'Compote de fruits de saison.'),
    (44, 'Pommes rôties à la cannelle', 'Pommes rôties et parfumées à la cannelle.'),
    (45, 'Moules marinières', 'Moules cuisinées dans une sauce marinière.'),
    (46, 'Paella aux fruits de mer', 'Riz safrané, fruits de mer et légumes.'),
    (47, 'Côte de bœuf grillée', 'Pièce de bœuf grillée, jus réduit et garniture.'),
    (48, 'Quinoa, tofu fumé et légumes croquants', 'Quinoa, tofu fumé et légumes frais.'),
    (50, 'Gratin de légumes au lait de coco', 'Légumes rôtis et sauce douce au lait de coco.'),
    (51, 'Penne crémeuses aux champignons', 'Penne, champignons et crème végétale.'),
    (52, 'Buddha bowl quinoa et légumes rôtis', 'Quinoa, légumes rôtis, pois chiches et sauce citronnée.'),
    (53, 'Burger végétal aux champignons', 'Pain brioché, galette végétale, champignons et salade.'),
    (54, 'Tiramisu revisité au café', 'Dessert au café et crème mascarpone.'),
    (55, 'Crème chocolat et noisette', 'Crème chocolatée avec éclats de noisette.'),
    (56, 'Panna cotta fruits rouges', 'Panna cotta vanille et coulis de fruits rouges.'),
    (57, 'Crêpe fine au sucre', 'Crêpe légère servie avec sucre.'),
    (58, 'Salade de fruits exotiques', 'Mangue, ananas, kiwi et fruits de saison.'),
    (59, 'Jus de pomme artisanal', 'Jus de pomme servi frais.'),
    (60, 'Citronnade maison', 'Citronnade fraîchement préparée.'),
    (61, 'Thé glacé pêche', 'Thé glacé parfum pêche.'),
    (62, 'Café expresso', 'Café expresso.'),
    (63, 'Armagnac XO', 'Armagnac de caractère servi en digestif.'),
    (64, 'Limoncello', 'Liqueur italienne de citron servie fraîche.')
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `menu_dishes` (`menu_id`, `dish_id`, `category`, `position`) VALUES
    (1,10,'starter',1),(1,11,'starter',2),(1,12,'main',1),(1,13,'main',2),(1,14,'dessert',1),(1,15,'dessert',2),(1,16,'dessert',3),
    (2,17,'starter',1),(2,18,'starter',2),(2,19,'main',1),(2,20,'main',2),(2,21,'dessert',1),(2,22,'dessert',2),
    (3,23,'starter',1),(3,24,'starter',2),(3,25,'main',1),(3,26,'main',2),(3,27,'dessert',1),(3,28,'dessert',2),
    (4,29,'starter',1),(4,30,'starter',2),(4,31,'starter',3),(4,32,'main',1),(4,33,'main',2),(4,34,'main',3),(4,35,'dessert',1),(4,36,'dessert',2),
    (5,37,'starter',1),(5,38,'starter',2),(5,39,'main',1),(5,40,'main',2),(5,41,'main',3),(5,42,'dessert',1),(5,43,'dessert',2),(5,44,'dessert',3),
    (6,24,'starter',1),(6,45,'main',1),(6,46,'main',2),(6,47,'main',3),(6,54,'dessert',1),(6,56,'dessert',2)
ON DUPLICATE KEY UPDATE
    `category` = VALUES(`category`),
    `position` = VALUES(`position`);

INSERT INTO `allergens` (`id`, `name`) VALUES
    (5,'Fruits à coque'),(6,'Soja'),(7,'Sésame'),(8,'Moutarde')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `dish_allergens` (`dish_id`, `allergen_id`) VALUES
    (10,1),(10,2),(11,4),(12,2),(13,2),(14,1),(14,2),(14,3),(15,1),(15,2),(15,3),
    (16,1),(16,2),(16,3),(18,7),(19,2),(22,2),(22,3),(23,1),(23,2),(24,4),
    (25,6),(26,4),(27,3),(28,1),(28,2),(28,3),(29,1),(29,2),(30,2),(31,1),
    (32,1),(32,3),(33,1),(33,2),(34,1),(34,3),(38,7),(39,6),(42,3),(45,4),(46,4),
    (47,2),(51,2),(52,7),(53,1),(54,2),(54,3),(55,2),(55,5),(56,2)
ON DUPLICATE KEY UPDATE `allergen_id` = VALUES(`allergen_id`);

ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `role` = 'admin',
    `is_active` = 1,
    `first_name` = VALUES(`first_name`),
    `last_name` = VALUES(`last_name`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Les employés sont créés et gérés depuis l’espace administrateur.
-- Les clients peuvent créer leur compte via /register.

# Bugs identifies

## MongoDB : authentification impossible dans la moderation des avis

- **Symptome :** la route `/admin/comments/pending` affichait une erreur `MongoDB\\Driver\\Exception\\AuthenticationException`.
- **Cause :** le compte MongoDB `dylan` est defini dans la base d'authentification `admin`, mais `MONGO_CONNECTION_STRING` ne precisait pas `authSource=admin`.
- **Correction :** ajout de `?authSource=admin` dans `.env`.
- **Verification :** connexion admin reussie, `/admin/comments/pending` repond `200` sans `Fatal error` ni `AuthenticationException`.

## Etat final

## MongoDB : champs de commentaires incompatibles

- **Symptome :** `/admin/comments/pending` affichait des warnings `Undefined array key "rating"` et `Undefined array key "comment"`.
- **Cause :** les anciens documents MongoDB utilisent le champ `content` et ne contiennent pas toujours `rating`.
- **Correction :** `CommentRepository` utilise maintenant `comment` ou `content`, avec une note par defaut a `0` si elle est absente.
- **Verification :** la page repond `200` sans warning ni erreur fatale.

## Etat final

## Commande : parcours bloque apres les options

- **Symptome :** le bouton `Continuer` ne faisait rien apres la selection du menu et des options.
- **Cause :** aucun gestionnaire JavaScript n'etait attache a `btnVersRecap`.
- **Correction :** affichage du formulaire final, selection du menu reportee et options enregistrees dans la commande via `customization`.
- **Verification :** parcours teste dans le navigateur avec le menu 1.

## Catalogue : noms et descriptions mal encodes

- **Symptome :** certains caracteres accentues apparaissaient sous la forme `??` dans les noms de plats.
- **Cause :** les noms existants n'etaient pas mis a jour par le seed et provenaient d'une importation mal encodee.
- **Correction :** restauration UTF-8 des noms et descriptions dans MariaDB; le seed met maintenant aussi a jour `name`.

## Footer : horaires absents

- **Symptome :** le footer ne pouvait afficher aucune plage horaire.
- **Cause :** la table `opening_hours` etait vide apres la migration SQL.
- **Correction :** ajout des horaires de reference dans la base et dans le seed catalogue.

## Authentification admin : mot de passe non hache

- **Symptome :** le compte admin refusait le mot de passe pourtant configure.
- **Cause :** le mot de passe avait ete importe en clair dans MariaDB.
- **Correction :** hachage du mot de passe existant, sans le regenerer ni le modifier.
- **Verification :** connexion navigateur reussie vers `/admin/dashboard`.

## Etat final

Aucun bug reproductible restant n'a ete observe sur les routes et parcours testes.

## Corrections recentes

- La boîte email utilise maintenant une valeur par défaut `inbox` quand aucun dossier n'est fourni.
- Les employés et administrateurs reçoivent une notification interne pour chaque nouvelle commande et chaque devis.
- Les commandes proposent livraison, sur place ou à emporter, avec paiement en espèces sur place.
- Les ingrédients non désirés, les instructions livreur et la carte des plats sont pris en compte.
- Les numéros sont contrôlés sur les préfixes internationaux `+33`, `+34`, `+32`, `+44` et `+39`.
- Une clé Google Maps peut être ajoutée via `GOOGLE_MAPS_API_KEY`; sans clé, le formulaire conserve un fallback local sans inventer de validation distante.

## Centre email

- La boîte `/admin/emails` lit les messages du formulaire stockés dans MariaDB et permet d'y répondre.
- La boîte `mail.ai` est volontairement locale : elle permet de simuler des entrants et de tester les réponses sans DNS ni serveur email réel.
- Le dépôt `template-manager/email-template-manager` ne reçoit pas d'emails; son principe de placeholders est repris par le renderer local `EmailTemplateRenderer`.
- La réception distante IMAP reste optionnelle : l'extension PHP `imap` n'est pas activée dans l'environnement courant.
- Les réponses sont tracées dans `email_outbox`; si SMTP est indisponible, elles passent en `failed` et l'interface l'indique.

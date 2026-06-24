> 2026-06-24
  + Fix sécurité


> 2026-04-28
  + V4.0.0
  + **Correctif majeur "Forcer le téléchargement"** : sur les comptes avec plusieurs caméras très actives, certaines caméras (ex. peu actives entre deux pics) ne récupéraient plus les vidéos les plus récentes — la pagination s'arrêtait après quelques pages "sans cette caméra" alors que d'autres pages plus loin contenaient les vidéos de la nuit/journée. Refonte de la boucle : parcours direct de l'API `/media/changed` Blink (ordre décroissant côté serveur), filtrage par caméra côté plugin, arrêt dès que `nb_max_video` vidéos sont collectées POUR la caméra. La fin réelle de pagination est détectée via la réponse brute (toutes caméras), plus via un compteur arbitraire de pages vides. Conséquence : les `N` vidéos les plus récentes sont toujours conservées, quel que soit le rythme des autres caméras.
  + Correctifs annexes dans `forceCleanup` : tri du cache de téléchargement par date (clé `<id>-<YYYY-MM-DD_HHMMSS>.mp4`) au lieu de l'URL Blink, comparaisons `array_search() === false` strictes (l'index 0 ne déclenche plus de faux négatifs sur `last.mp4`).
  + **Refonte interne** : extraction du flow OAuth dans un trait `BlinkOAuthTrait` (`core/class/BlinkOAuthTrait.php`) pour découper le fichier monolithique de la classe.
  + **Wrapper `apiCall()` centralisé** pour les appels REST authentifiés Blink : gestion automatique des codes 401 (refresh `refresh_token` + retry), 429 (respect du `Retry-After`) et 5xx (backoff + retry). `queryGet` et `queryPost` passent désormais par ce wrapper.
  + **Refresh `refresh_token` à la demande** (sur 401), en plus du cron horaire existant : plus aucun trou de service entre l'expiration du jeton et le prochain cron.
  + **Réception de notifications de détection (webhook)** : nouveau endpoint `/plugins/blink_camera/core/php/notification.php` protégé par jeton. Un service externe peut POST un payload JSON (`network_id`, `camera_id`, `source`, `timestamp`) pour déclencher immédiatement le rafraîchissement de la caméra concernée. URL et jeton visibles dans la configuration du plugin (régénérables).
  + Nouvelle commande info masquée par défaut `last_motion_event` mise à jour à chaque réception webhook (utilisable comme déclencheur de scénario sans attendre le polling).
> 2026-04-27
  + V3.2.0
  + **Migration vers le nouveau flow d'authentification OAuth 2.0 (PKCE)** suite au changement des API Blink fin 2025.
  + La connexion s'appuie désormais sur les endpoints `api.oauth.blink.com` (authorize / signin / 2fa / token) avec génération d'un `code_verifier` / `code_challenge` côté plugin.
  + Gestion d'un `refresh_token` : le plugin renouvelle automatiquement le jeton d'accès toutes les heures (cron horaire) sans redemander le code PIN.
  + Le code PIN reste demandé lors de la première connexion ou lorsqu'un nouveau device n'est pas encore validé par Blink.
  + Conservation des cookies de session (jar sérialisé) pour rester compatible avec le parcours web Next.js de Blink.
  + Récupération automatique du `tier_info` (région) après authentification.
  + **ATTENTION** : après mise à jour, il est conseillé de ressaisir mot de passe + code PIN pour chaque compte Blink afin de réinitialiser les jetons OAuth.

> 2025-09-06
  + Intégration des corrections pour PHP 8.3 (Merci Romain-Grosos)
> 2025-07-26
  + Passage en stable de la v3.0
  + **ATTENTION : CHANGEMENTS IMPORTANTS DANS CETTE VERSION NECESSITANTS UNE RECONFIGURATION DU PLUGIN ET DE VOS CAMERAS**
  + Voir ci-dessous
  
> 2024-08-15
  + V3.0.1
  + Ajout d'une config permettant de limiter la fréquence d'interrogation des serveurs Blink (toutes les minutes,toutes les 5 min., etc).
  
> 2024-04-07
  + V3.0.0
  + Version compatible Jeedom 4.4
  + Support de plusieurs comptes Blink

> 2024-01-09
  + V2.8.2
  + Correction d'un problème de connexion avec des erreurs "406 Not Acceptable"

> 2023-11-03
  + V2.8.1
  + Ajout des actions "Armer caméra" et "Désarmer caméra" sur les caméras de types "Mini" et "Doorbell"
  + Pour l'info "dernier évenement" et les dates dans la vue "historique" : Les dates sont affichées au format français si Jeedom est configuré en français (sinon le format des dates Jeedom habituel est affiché)
  + Vue historique : Ajout d'une option permettant de ne charger la vidéo que si on clique dessus.
  + [**&rarr; Voir la documentation**](https://d9-197.github.io/blink_camera/beta/fr_FR/?theme=dark)

> 2023-08-12
  + V2.8.0
  + Ajout de l'option "Largeur du widget" dans le choix de la taille de la vignette (config du plugin)
  + **Ajout du téléchargement des vidéos stockées localement.**
  + Ajout d'une page "santé"
  + **Ajout des noms des modèles de caméra dans la liste des équipements,**
  + Ajout d'un "template" pour l'info "battery" : affiche une icone "prise" lorsque le plugin détecte que la caméra est sur secteur
  + Ajout d'une loupe dans la vue "Historique" si la vignette a été réduite.
  + **L'ouverture de la vue historique est plus rapide (pas de retéléchargement des vidéos/images)**
  + **Ajout d'un bouton permettant d'ajouter toutes les caméras automatiquement**

> 2023-07-04
  + V2.7.1
  + Fix sécurité

> 2023-04-14
  + V2.7.0
  + **Ajout d'un widget spécial pour les commandes "Statut","Armer" et "Désarmer" des caméras ou systèmes.** Voir la documentation plus de détail.

> 2023-03-23
  + V2.6.0
  + Ajout d'un mode "hors ligne" : quand les serveurs Blink sont inaccessibles ou quand le mot de passe a expiré, le plugin utilise les vidéos, photos et infos déjà présentes dans Jeedom.
  <br><br>
  + Les vignettes de la caméra ("Prendre une photo") sont maintenant sauvegardées :
  + Elles sont accessibles dans la vue historique.
  + Blink ne permettant de récupérer que la dernière photo, les photos sont sauvegardées dans Jeedom au fur et à mesure qu'elles sont trouvées.
  + Exemple 1 : Si vous effacez une photo depuis la vue historique, elle ne pourra pas être retrouvée (sauf s'il s'agissait de la dernière photo de la caméra).
  + Exemple 2 : si votre Jeedom ne peut pas se connecter aux serveurs Blink pendant un certains temps, il est possible que des photos prises pendant ce temps ne soient jamais téléchargées.
  

> 2023-02-14
  + V2.5.1
  + Correction du problème d'affichage des vignettes dans la vue historique

> 2023-02-03
  + V2.5.0
  + Ajout du choix entre vignettes ou vidéos dans la vue Historique : nouveau bouton en haut de la vue historique (le dernier choix est mémorisé individuellement pour chaque caméra)

> 2023-01-17
  + V2.4.0
  + Prise en compte (partielle) des Blink Doorbell

> 2022-06-28
  + V2.3.2
  + Modification de la récupération des valeurs de température

> 2022-06-02
  + V2.3.1
  + Correctif de sécurité
  
> 2022-05-03
  + V2.3.0
  + Ajout de l'action "Prendre une photo"
  + **Sécurité** - Ajout dans la configuration du plugin de _"Bloquer l'accès aux URLs des vidéos et images sans être authentifié dans Jeedom ?"_
    - Si la case est cochée, les URLs des vidéos (et images) ne pourront être ouvertes que si l'utilisateur est déjà connecté à Jeedom
    - Si la case n'est pas cochée, les urls sont librement accessibles


> 2022-04-06
  + V2.2.0
  + Correction de l'affichage des "vignettes de la caméra"
  + Patch de sécurité

> 2022-02-17
  + V2.1.0
  + Modification de l'affichage des vignettes : possibilité d'afficher de la vignette de la caméra s'il n'y a pas de vidéo (pas disponible pour les caméra Blink Mini).
  + Correction de l'affichage des messages dans la partie configuration suite à l'arrivée de la version 4.2 de Jeedom

> 2021-02-10
  + V2.0.1
  + Corrections suite aux modifications des API de connexion Blink
  + L'authentification avec code pin envoyé par SMS n'a pas encore été testée. Donc elle peut ne pas fonctionner

> 2020-08-26
  + V2.0.0
  + Modification du plugin pour permettre l'authentification en 2 étapes (mot de passe + code envoyé par Blink par email)

> 2020-02-28
  + Correction du tri des vidéos dans la vue historique
  + Modification du nom des fichiers téléchargés depuis la vue historique

> 2020-02-26
  + Correction des URL de la dernière vidéo et de la vignette
  + Ajout de l'information sur le pourcentage de la batterie (gestion des seuils d'alerte)

> 2020-02-01 
  + Ajout des URL de la dernière vidéo et de la vignette

> 2019-10-06
  + Correction de la liste des caméras (configuration d'un équipement)

> 2019-09-19
  + Modification de la gestion de la configuration

> 2019-08-08
  + Passage en version stable

> 2019-07-21
  + Première version Beta du plugin

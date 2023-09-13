> 2023-08-12
  + V2.8.0 Beta
  + **Ajout de l'option "Largeur du widget" dans le choix de la taille de la vignette (config du plugin)
  + **Ajout du téléchargement des vidéos stockées localement.
  + **Ajout d'une page "santé"
  + **Ajout des noms des modèles de caméra dans la liste des équipements,
  + **Ajout d'un "template" pour l'info "battery" : affiche une icone "prise" lorsque le plugin détecte que la caméra est sur secteur
  + **Ajout d'une loupe dans la vue "Historique" si la vignette a été réduite.
  + **L'ouverture de la vue historique est plus rapide (pas de retéléchargement des vidéos/images)

> 2023-07-04
  + V2.7.1
  + **Fix sécurité

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

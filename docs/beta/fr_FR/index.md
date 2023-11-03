# Description

Plugin dédié aux caméras Blink.

Il permet d'afficher les vidéos des différentes caméras.

[**&rarr; Lien vers GITHUB de ce plugin gratuit**](https://github.com/d9-197/blink_camera)

Les actions suivantes sont disponibles (en fonction du modèle de caméra):
- d'armer/désarmer la détection de mouvement d'un système
- d'armer/désarmer la détection de mouvement d'une caméra.
- Prendre une vidéo
- Prendre une photo (mise à jour de la vignette de la caméra)
- Afficher l'historique des dernières vidéos d'une caméra (avec possibilité de télécharger les vidéos ou de les supprimer)

Les informations liées à la caméra sont également disponibles :
* Date du dernier évenement,
* URL et chemin de la dernière vidéo (et de sa vignette),
* URL et chemin de la vignette de la caméra (issue de "Prendre une photo")
* Puissance du wifi,
* Température,
* Etat des piles,
* [Doorbell uniquement] Source du dernier évenement ("pir" pour la détection, "button_press" pour la sonette)

>**Dans le plugin, les différents types de caméras n'ont pas tous les mêmes capacités. Certaines fonctions ou informations ne sont donc pas disponibles dans le plugin pour certaines caméras.**

**Cas du stockage local**\
Il existe 3 modes de stockage pour les vidéos/images des caméras
- "Cloud" Le stockage dans le Cloud Blink
- "Local" Le stockage en local sur clé USB : nécessite un module de synchronisation v2
- "Pas de stockage" (ni Cloud, ni clé USB sur un module de synchronisation v2.

Les modes "Cloud" et "Local" permettent de récupérer les évenements (toutes les minutes : ce n'est pas instantané).
Le mode "pas de stockage" ne permet pas de récupérer le dernier évènement, ni les vidéos/images. Ce plugin n'a donc que peu d'interêt si vous êtes en mode "pas de stockage"   


# Configuration du plugin

Dans l'écran de configuration du plugin les options suivantes sont disponibles :

* Compte Blink
  + Zone permettant de saisir l'email, le mot de passe et le code pin associés à votre compte Blink. (Le champs de saisie du code pin ne s'affiche que lorsque la connexion n'est pas encore validée)

>**Point important sur le code PIN envoyé par Blink :**\
>Blink redemande régulièrement de resaisir le code PIN (la durée entre 2 demandes est variable - et indépendante du plugin). Quand cela se produit, vous recevez alors un code PIN de la part de Blink mais le plugin n'a pas l'information qu'un nouveau code doit être renseigné. Dans ce cas, il est probable que le champs du code PIN ne soit pas affiché dans le plugin.\
**Il vous faut alors forcer une demande de code PIN depuis le plugin** (et donc le réaffichage du champ). Pour cela renseignez un __mauvais__ email ou mot de passe puis sauvegardez (à ce stade vous aurez une erreur : ce qui normal puisque l'email ou le mot de passe ne sont pas corrects), puis remettez le bon email et mot de passe et sauvegardez.\
**Un nouveau code PIN vous est alors envoyé par Blink.**
Le champs de saisi du code PIN apparaitra dans la config du plugin.\
[**&rarr; Vidéo guide redemander un code pin**](https://youtu.be/mDud775DjYQ)

* Sécurité
  + _"Bloquer l'accès aux URLs des vidéos et images sans être authentifié dans Jeedom ?"_
    - Si la case est cochée, les URLs des vidéos (et images) ne pourront être ouvertes que si l'utilisateur est déjà connecté à Jeedom
    - Si la case n'est pas cochée, les urls sont librement accessibles

  + Adresse de Jeedom à utiliser pour les URLs : permet de choisir qu'elle URL de Jeedom sera utilisée dans les infos "URL dernière vidéo" et "URL vignette".
    *Ces URL correspondent à celles que vous avez définies dans la configuration de votre système Jeedom : Menu "Réglages / Système / Configuration" puis onglet "Réseaux" : accès interne ou accès externe.*

>**Il est conseillé de ne choisir "Accès externe" que si vous avez coché "Bloquer l'accès aux URLs..."**

* Widget
  + Contenu de la vignette : permet de choisir ce qui est affiché dans le widget
    + Si vous choisissez "Dernière vidéo", une case à cocher s'affiche pour choisir d'afficher (ou non) la vignette de la caméra s'il n'y a pas de vidéos.

  + Taille de la vignette : permet de définir la réduction à appliquer (en pourcentage de la taille initiale).


* Vue historique
  + Cette vue est accessible depuis le widget
  + Elle affiche les dernières vidéos/vignettes disponibles
  + Le nombre maximum de vidéos téléchargées permet de limiter la quantité de données à télécharger à l'ouverture de la vue historique
  + La taille des aperçus des vidéos peut également être confirgurée.

    *Attention : Si vous configurez une taille importante et un nombre important de vidéos, cela peut entrainer des lenteurs dans la vue historique*


>**N'oubliez pas de cliquer sur le bouton "Sauvegarder"**


![Configuration du plugin](..\assets\images\cfg_plugin.png "Configuraion du plugin")

# Création et configuration d'un équipements


## Ajout d'un équipement


L'ajout des équipements se fait manuellement.

Pour cela, vous devez cliquer sur le bouton "Ajouter" ayant l'icône "+".

![Ajout d'un équipement](..\assets\images\cfg_plugin_general.png "Ajout d'un équipement")

Vous pouvez alors renseigner un nom pour l'équipement.

La fenêtre de configuration de l'équipement s'ouvre ensuite.

## Ajout automatique de toutes les caméras associées à votre compte Blink
Lorsque vous cliquez sur ce bouton, le plugin va tenter d'ajouter les caméras associées à votre compte qui ne seraient pas déjà présentes dans Jeedom.


## Configuration d'un équipement

### Onglet Equipement
![Onglet équipement](..\assets\images\cfg_equipment.png "Equipement")

Les options standard des équipements Jeedom sont en haut de l'écran.

Si vous avez correctement configuré votre compte Blink (voir configuration du plugin), 2 autres options sont disponibles dans la partie basse de l'écran :
- Système : Correspond au système créé dans votre application Blink.
- Caméra : vous permet de sélectionner la caméra à utiliser avec cet équipement

>**N'oubliez pas de cliquer sur le bouton "Sauvegarder"**


### Onglet Commandes

Les commandes sont automatiquement créées lors de la création de l'équipement.

Des icônes sont associées aux actions, vous pouvez les modifier de manière classique dans Jeedom.

Les cases à cocher "Afficher" vous permettent de choisir les informations ou commandes qui seront visibles sur le widget.

![Onglet commandes](..\assets\images\cfg_commands.png "Commandes")


>**Widget particulier pour les commandes "Caméra armée ?" et "Système armé ?"**
Par défaut, ces commandes affichent l'état de détection de la caméra/système.<br> 
 Il est possible de modifier l'affichage de ces commandes afin de n'avoir qu'un seul bouton pour les 3 commandes "Caméra armée ?", "Armer la caméra", "Désarmer la caméra" (et les 3 commandes pour le Système).<br>Dans ce cas, l'icone du bouton qui indique l'état (cadenas ouvert : pas de détection, cadenas fermé : détection) et en cliquant dessus vous pouvez changer l'état.<br>
 Pour activer ce mode de fonctionnement, il faut utiliser le widget "Blink_camera/Camera or System status" dans la configuration avancée des commandes "Caméra armée ?" et/ou "Système armé ?" :<br>
>![Ouvrir la configuration avancée des commandes](..\assets\images\cfg_command_switch_1.png "Config commandes")<br>
>![Choisir le widget](..\assets\images\cfg_command_switch_2.png "Choix widget")



Vue historique
===
La vue historique est accessible depuis le widget de la caméra. (commande Historique)
Cette vue vous donne accès aux dernières vidéos de la caméra ou aux dernières vignettes. Le choix se fait directement dans la fenêtre "historique" et ce choix est mémorisé pour chaque caméra.<br>
Le nombre de vidéos affichées ainsi que la taille des vignettes est configurable sur le plugin : voir [Configuration du plugin](#-Configuration-du-plugin)

Utilisation dans des scénarios
===
Vous pouvez déclencher un scénario en mettant directement comme déclencheur l'information "Dernier événement"

> Voir champ "Evénement" dans l'image ci-dessous

![Utilisation dans des scénarios](..\assets\images\scenario.png "Utilisation dans des scénarios")
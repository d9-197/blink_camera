# Description

Plugin dédié aux caméras Blink.

Il permet d'afficher les vidéos des différentes caméras.

[&rarr; pour soutenir les développements de ce plugin gratuit](https://fr.tipeee.com/duke-9)

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

>**Dans le plugin, les caméras Blink Mini et les Doorbell n'ont pas les mêmes capacités que les autres caméras Blink XT, Outdoor, etc.<br> Par exemple, les vignettes de caméra ne sont pas implémentées pour les Mini, il n'est pas possible d'activer/désactiver la détection d'un Doorbell**


# Configuration du plugin

Dans l'écran de configuration du plugin les options suivantes sont disponibles :

* Compte Blink
  + Zone permettant de saisir l'email, le mot de passe et le code pin associés à votre compte Blink. (Le champs de saisi du code pin ne s'affiche que lorsque la connexion n'est pas encore validée)

* Sécurité
  + _"Bloquer l'accès aux URLs des vidéos et images sans être authentifié dans Jeedom ?"_
    - Si la case est cochée, les URLs des vidéos (et images) ne pourront être ouvertes que si l'utilisateur est déjà connecté à Jeedom
    - Si la case n'est pas cochée, les urls sont librement accessibles

  + Adresse de Jeedom à utiliser pour les URLs : permet de choisir qu'elle URL de Jeedom sera utilisée dans les infos "URL dernière vidéo" et "URL vignette".
    *Ces URL correspondent à celles que vous avez définies dans la configuration de votre système Jeedom : Menu "Réglages / Système / Configuration" puis onglet "Réseaux" : accès interne ou accès externe.*

>**Il est conseillé de ne choisir "Accès externe" que si vous avez coché "Bloquer l'accès aux URLs..."**

* Widget
  + Contenu de la vignette : permet de choisir ce qui est affiché dans le widget
  + Taille de la vignette : permet de définir la réduction à appliquer (en pourcentage de la taille initiale).


* Vue historique
  + Cette vue est accessible depuis le widget
  + Elle affiche les dernières vidéos disponibles
  + Le nombre maximum de vidéos téléchargées permet de limiter la quantité de données à télécharger à l'ouverture de la vue historique
  + La taille des aperçus des vidéos peut également être confirgurée.

    *Attention : Si vous configurez une taille importante et un nombre important de vidéos, cela peut entrainer des lenteurs dans la vue historique*


>**N'oubliez pas de cliquer sur le bouton "Sauvegarder"**


![Configuration du plugin](..\assets\images\cfg_plugin.png "Configuraion du plugin")

# Création et configuration d'un équipements


## Ajout d'un équipement


L'ajout des équipements se fait manuellement.

Pour cela, vous devez cliquer sur le bouton "Ajouter" ayant l'icône "+".

![Ajout d'un équipment](..\assets\images\cfg_plugin_general.png "Ajout d'un équipment")

Vous pouvez alors renseigner un nom pour l'équipement.

La fenêtre de configuration de l'équipement s'ouvre ensuite.

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


Vue historique
===
La vue historique est accessible depuis le widget de la caméra. (commande Historique)
Cette vue vous donne accès aux dernières vidéos de la caméra.
Le nombre de vidéos affichées ainsi que la taille des vignettes est configurable sur le plugin : voir [Configuration du plugin](#-Configuration-du-plugin)

Utilisation dans des scénarios
===
Vous pouvez déclencher un scénario en mettant directement comme déclencheur l'information "Dernier événement"

> Voir champ "Evénement" dans l'image ci-dessous

![Utilisation dans des scénarios](..\assets\images\scenario.png "Utilisation dans des scénarios")
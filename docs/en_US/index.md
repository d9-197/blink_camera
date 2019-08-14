# Description

Plugin dédié aux caméras Blink.
Il permet d'afficher les vidéos des différentes caméras, d'armer/désarmer la détection de mouvement d'un système ou d'une caméra.
Les informations liées à la caméra sont également disponibles :

* Température,
* Date du dernier évenement,
* Puissance du wifi,
* Voltage des piles.

# Configuration du plugin

Dans l'écran de configuration du plugin les options suivantes sont disponibles :

* Compte Blink
  + Zone permettant de saisir l'email et le mot de passe associés à votre compte Blink

* Widget
  + Contenu de la vignette : permet de choisir ce qui est affiché dans le widget
  + Taille de la vignette : permet de définir la réduction à appliquer (en pourcentage de la taille initiale).

* Vue historique
  + Cette vue est accessible depuis le widget
  + Elle affiche les dernières vidéos disponibles
  + Le nombre maximum de vidéos télécharger permet de limiter la quantité de données à télécharger à l'ouverture de la vue historique
  + La taille des aperçus des vidéos peut également être confirgurée.

*Attention : Si vous configurez une taille importante et un nombre important de vidéos, cela peut entrainer des lenteurs dans la vue historique*

**N'oubliez pas de cliquer sur le bouton "Sauvegarder"**

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

Si vous avez correctement configuré votre compte Blink (voir configuration du plugin), 3 autres options sont diposnibles dans la partie basse de l'écran :
- Système : Correspond au "Système" créé dans votre application Blink.
- Caméra : vous permet de sélectionner la caméra à utiliser avec cet équipement

**N'oubliez pas de cliquer sur le bouton "Sauvegarder"**

### Onglet Commandes

Les commandes sont automatiquement créées lors de la création de l'équipement.

Des icônes sont associées aux actions, vous pouvez les modifier de manière classique dans Jeedom.

![Onglet commandes](..\assets\images\cfg_commands.png "Commandes")
 





Vue historique
===
La vue historique est accessible depuis le widget de la caméra. (commande Historique)
Cette vue vous donne accès aux dernières vidéos de la caméra.
Le nombre de vidéos affichées ainsi que la taille des vignettes est configurable sur le plugin : voir [Configuration du plugin](#-Configuration-du-plugin)

Vue "panel"
===
Un panel est activable depuis la configuration du plugin. voir [Configuration du plugin](#-Configuration-du-plugin)

Si celui ci est activé, il est accessible dans le menu "Accueil"/"Blink Camera".

Ce panel affiche l'ensemble des caméras Blink configurées.

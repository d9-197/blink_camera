# Description

Plugin dedicated to Blink cameras.

It allows you to display videos from different cameras.

[&rarr; Link to support developpements for this free plugin](https://fr.tipeee.com/duke-9)

The following actions are available (depending on the camera model):
- to arm/disarm the motion detection of a system
- to arm/disarm the motion detection of a camera.
- Take a video
- Take photo (updated camera thumbnail)
- View a camera's latest video history (with option to download videos or delete them)

Camera information is also available:
* Date of the last event,
* URL and path of the last video (and its thumbnail),
* Camera thumbnail URL and path (from "Take a photo")
* wifi power,
* Temperature,
* Battery voltage.
>**In the plugin, Blink Mini cameras do not have the same capabilities as other Blink XT, Outdoor cameras, etc.<br> For example, camera thumbnails are not implemented for Mini**

# Configuration of the plugin

In the plugin configuration screen the following options are available:

* Blink account
  + Area to enter the email, password and pin code associated with your Blink account. (The pin code entry field is only displayed when the connection is not yet validated)

* Security
  + _"Block access to video and image URLs without being authenticated in Jeedom?"_
    - If the box is checked, the URLs of the videos (and images) can only be opened if the user is already connected to Jeedom
    - If the box is not checked, the urls are freely accessible

  + Jeedom address to use for URLs: allows you to choose which Jeedom URL will be used in the "Last video URL" and "Thumbnail URL" info.
    *These URLs correspond to those you have defined in the configuration of your Jeedom system: "Settings / System / Configuration" menu then "Networks" tab: internal access or external access.*

>**It is advisable to choose "External access" only if you have checked "Block access to URLs..."**


* Widget
  + Thumbnail content: allows you to choose what is displayed in the widget
  + Thumbnail size: allows you to define the reduction to be applied (as a percentage of the initial size).
    
* Historical view
  + This view is accessible from the widget
  + It displays the latest videos available
  + The maximum number of downloaded videos allows you to limit the amount of data to download when opening the historical view
  + The size of the video previews can also be configured.

    *Warning: If you configure a large size and a large number of videos, this can cause slowness in the historical view*


>**Don't forget to click on the "Save" button**

![Configuration du plugin](..\assets\images\cfg_plugin.png "Configuraion du plugin")

# Creation and configuration of equipment


## Adding equipment


Adding equipment is done manually.

To do this, you must click on the "Add" button with the "+" icon.

![Ajout d'un équipment](..\assets\images\cfg_plugin_general.png "Ajout d'un équipment")

You can then enter a name for the equipment.

The equipment configuration window then opens.

## Equipment configuration

### Equipment tab
![Onglet équipement](..\assets\images\cfg_equipment.png "Equipement")

The standard options for Jeedom equipment are at the top of the screen.

If you have correctly configured your Blink account (see configuration of the plugin), 2 other options are available in the lower part of the screen:
- System: Corresponds to the system created in your Blink application.
- Camera: allows you to select the camera to use with this equipment


>**Don't forget to click on the "Save" button**


### Commands tab

Commands are automatically created when the equipment is created.

Icons are associated with actions, you can modify them in the classic way in Jeedom.

The "Display" checkboxes allow you to choose the information or commands that will be visible on the widget.

![Onglet commandes](..\assets\images\cfg_commands.png "Commandes")


Historical view
===
The history view is accessible from the camera widget. (History command)

This view gives you access to the latest videos from the camera.

The number of videos displayed as well as the size of the thumbnails is configurable on the plugin: see [Configuration du plugin](#-Configuration-du-plugin)

Use in scenarios
===
You can trigger a scenario by directly activating the information "Last event"

> See "Event" field in the image below

![Utilisation dans des scénarios](..\assets\images\scenario.png "Utilisation dans des scénarios")
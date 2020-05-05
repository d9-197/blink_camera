# Description

<span style='color:red'>**WARNING :**</span>

<span style='color:red'>**Blink announces for May 11, 2020 the end of support for connections by tools other than IFTTT or their official application.**</span>

<span style='color:red'>**From May 11, the plugin may therefore no longer be usable in Jeedom.**</span>

Plugin dedicated to Blink cameras.
It is used to display the videos of the various cameras, to arm / disarm the motion detection of a system or a camera.

Camera related information is also available:

* Temperature,
* Date of last event,
* URL and path of the last video (and its thumbnail),
* Wifi power,
* Battery voltage.


# Configuration of the plugin

In the plugin configuration screen the following options are available:

* Blink account
  + Area for entering the email and password associated with your Blink account

* Widget
  + Thumbnail content: allows you to choose what is displayed in the widget
  + Thumbnail size: allows you to define the reduction to be applied (as a percentage of the initial size).
  + Jeedom address to use for URLs: allows you to choose which Jeedom URL will be used in the "Last video URL" and "Thumbnail URL" infos.
    *These URLs correspond to those you defined in the configuration of your Jeedom system: "Settings / System / Configuration" menu then "Networks" tab: internal access or external access.*
    
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

If you have correctly configured your Blink account (see configuration of the plugin), 3 other options are available in the lower part of the screen:
- System: Corresponds to the system created in your Blink application.
- Camera: allows you to select the camera to use with this equipment


>**Don't forget to click on the "Save" button**


### Commands tab

Commands are automatically created when the equipment is created.

Icons are associated with actions, you can modify them in the classic way in Jeedom.

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
# Description

Plugin dedicated to Blink cameras.

It allows you to display videos from different cameras.

[**&rarr; Link to GITHUB for this free plugin**](https://github.com/d9-197/blink_camera)

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
* Battery status,
* [Doorbell only] Source of the last evebt ("pir" for IR detection, "button_press" pour the button)

>**In the plugin, some types of Blink camera do not have the same capabilities as other. Then some functions or information can not be available for these types of camera.**

**Case of the local storage**\
There are 3 storage configurations for camera videos/images.
- "Cloud": Storage on the Blink Cloud
- "Local": Storage on a USB key : a synchronisation module v2 is required
- "No storage": no Cloud, no USB key.

The "Cloud" and "Local" configurations allow the plugin to obtain the last event (every minutes : it is not instantaneous).
The "No storage" configuration does not allow obtaining the last event and does not allow obtaining videos/images. In this case, this plugin is not very useful.   

# Configuration of the plugin

In the plugin configuration screen the following options are available:

* Blink account
  + Area to enter the email, password and pin code associated with your Blink account. (The pin code entry field is only displayed when the connection is not yet validated)

>**Important point about the PIN code sent by Blink:**\
>Blink regularly asks you to re-enter the PIN code (the duration between 2 requests is variable - and independent of the plugin). When this happens, you then receive a PIN code from Blink but the plugin does not have the information that a new code must be entered. In this case, it is likely that the PIN code field is not displayed in the plugin.\
**You must then force a PIN code request from the plugin** (and therefore the redisplay of the field). To do this, enter a __bad__ email or password then save (at this stage you will have an error: which is normal since the email or password are not correct), then enter the correct email and password and save .\
**A new PIN code is then sent to you by Blink.**
The PIN code entry field will appear in the plugin config.\
[**&rarr; Video guide request a pin code**](https://youtu.be/mDud775DjYQ)

* Security
  + _"Block access to video and image URLs without being authenticated in Jeedom?"_
    - If the box is checked, the URLs of the videos (and images) can only be opened if the user is already connected to Jeedom
    - If the box is not checked, the urls are freely accessible

  + Jeedom address to use for URLs: allows you to choose which Jeedom URL will be used in the "Last video URL" and "Thumbnail URL" info.
    *These URLs correspond to those you have defined in the configuration of your Jeedom system: "Settings / System / Configuration" menu then "Networks" tab: internal access or external access.*

>**It is advisable to choose "External access" only if you have checked "Block access to URLs..."**


* Widget
  + Thumbnail content: allows you to choose what is displayed in the widget
    + If you choose "Last video", a checkbox is displayed to choose whether to display (or not) the thumbnail of the camera if there are no videos.

  + Thumbnail size: allows you to define the reduction to be applied (as a percentage of the initial size).
    
* Historical view
  > This view is accessible from the widget. It displays the latest videos/thumbnails available
  + The maximum number of downloaded videos allows you to limit the amount of data to download when opening the historical view.
  + The size of the video previews can also be configured.

    *Warning: If you configure a large size and a large number of videos, this can cause slowness in the historical view*

  + Eco mode: This mode limits the quantity of data exchanged:
    + between Jeedom and your browser: a video is downloaded to your browser only when you click on it.
    + between the Blink and Jeedom servers: only data already present on Jeedom is displayed.


* Backup
  + This option allows you to include videos and images in Jeedom backups.
  
  **CAUTION: this option will cause a significant increase in the size of the Jeedom backup**

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

>**Special widget for "Camera armed?" and "System armed?" commands.**<br>
By default, these commands show the detection status of the camera/system.<br>
 It is possible to modify the display of these commands in order to have only one button for the 3 commands "Camera armed?", "Arm the camera", "Disarm the camera" (and for the 3 System commands).<br>In this case, the icon of the button which indicates the state (open padlock: no detection, closed padlock: detection) and by clicking on it you can change the state.<br>
 To activate this operating mode, you must use the "Blink_camera/Camera or System status" widget in the advanced configuration of the "Camera armed?" commands and/or "System armed?":<br>
>![Open advanced configuration of commands](..\assets\images\cfg_command_switch_1.png "Commands config")<br>
>![select the widget](..\assets\images\cfg_command_switch_2.png "Select widget")

Historical view
===
The history view is accessible from the camera widget. (History command)

This view gives you access to the latest videos or lastest thumbnails from the camera.

The number of videos displayed as well as the size of the thumbnails is configurable on the plugin: see [Configuration du plugin](#-Configuration-du-plugin)

Use in scenarios
===
You can trigger a scenario by directly activating the information "Last event"

> See "Event" field in the image below

![Utilisation dans des scénarios](..\assets\images\scenario.png "Utilisation dans des scénarios")
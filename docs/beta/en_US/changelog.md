[**&rarr; Link to support developpements for this free plugin**](https://fr.tipeee.com/duke-9)

> 2023-07-04
  + V2.7.1 Beta
  + **Security fix.

> 2023-04-14
  + V2.7.0 Beta
  + **Addition of special widget for "arm status","arm" and "desarm" commands of cameras or systems.** See documentation for more details.

> 2023-03-23
  + V2.6.0
  + Addition of an "offline" mode: when the Blink servers are inaccessible or when the password has expired, the plugin uses the videos, photos and info already present in Jeedom.
  <br><br>
  + Camera thumbnails ("Take photo") are now saved:
  + They are accessible in the historical view.
  + Blink only allows you to retrieve the last photo, the photos are saved in Jeedom as they are found.
  + Example 1: If you delete a photo from the historical view, it can no longer be found (unless it was the last photo from the camera).
  + Example 2: if your Jeedom cannot connect to Blink servers for a certain period of time, it is possible that photos taken during this time will never be downloaded.
  
> 2023-02-14
  + V2.5.1
  + Fix the issue with thumbnails display in historical view

> 2023-02-03
  + V2.5.0
  + Add option between display thumbnails or videos in the Historical view : new switch on the top of the view (the last choise is save in configuration for each camera)

> 2023-01-17
  + V2.4.0
  + Take into account (partially) of the Blink Doorbell

> 2022-06-28
  + V2.3.2
  + Modification of the retrieval of temperature values

> 2022-06-02
  + V2.3.1
  + Security fix
  
> 2022-05-03
  + V2.3.0
  + Add action "Take a picture"
  + Sécurité - Addition in the configuration of the plugin of _"Block access to URLs of videos and images without being authenticated in Jeedom?"_
    - If the box is checked, the URLs of the videos (and images) can only be opened if the user is already connected to Jeedom
    - If the box is not checked, the urls are freely accessible

> 2022-04-06
  + V2.2.0
  + Fix camera thumbnail display
  + security patch

> 2022-02-17
  + V2.1.0
  + Change thumbnail display : thumbnail of the camera can be display if no video is available (not for Blink Mini camera).
  + Fix the issue of displaying messages in the configuration view (since Jeedom v4.2)
  
  > 2021-02-10
  + V2.0.1
  + Fix issue with new login API
  + Authentication with pin code send by SMS has not yet been tested. So it may not work

> 2020-08-26
  + V2.0.0
  + Allow two factors authentication (password + pin code send by Blink)

> 2020-02-28
  + Fix sort of videos in historical view
  + Update the file names of downloads from historical view

> 2020-02-26
  + Fix URL to last video and last thumbnail
  + Add battery pourcentage information 

> 2020-02-01 
  + Add URL to last video and last thumbnail

> 2019-10-06
  + Fix camera list (new equipment)

> 2019-09-19
  + Update the configuration management

> 2019-08-08
  + Stable version

> 2019-07-21
  + First beta version 

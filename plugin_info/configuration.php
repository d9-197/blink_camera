<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <h4 class="icon_blue">{{Compte Blink}}</h4>
        <div class="form-group">
             <label class="col-lg-6 control-label">{{Email}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" data-l1key="param1" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Mot de passe}}</label>
            <div class="col-lg-3">
                <input type="password" class="configKey form-control" data-l1key="param2"/>
            </div>
        </div>
        <h4 class="icon_blue">{{Vue historique}}</h4>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Nombre maximum de vidéos téléchargées}}</label>
            <div class="col-lg-2">
                <input type="number" class="configKey form-control" data-l1key="nb_max_video" min="0" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Taille des aperçus des vidéos}}</label>
            <div class="col-lg-2">
                <select  class="configKey form-control" data-l1key="blink_size_videos">
                    <option value="0.1">10%</option>
                    <option value="0.2">20%</option>
                    <option value="0.3">30%</option>
                    <option value="0.4">40%</option>
                    <option value="0.5">50%</option>
                    <!--option value="0.6">60%</option>
                    <option value="0.7">70%</option>
                    <option value="0.8">80%</option>
                    <option value="0.9">90%</option>
                    <option value="1">100%</option-->
                </select>
            </div>
        </div>
  </fieldset>
</form>


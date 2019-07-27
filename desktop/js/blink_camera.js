
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


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '';
    if ( false && (_cmd.logicalId != null)  ) {
        if (init(_cmd.type)=='info') {
            tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" style="background:#F5F6CE">';
        }
        else {
            tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" style="background:#ECF8E0">';
        }
        tr += '<td>';
        tr += '<div style="width:100%;">';
        tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icône</a>';
        tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 8px;display: inline-block;"></span>';
        tr += '<input class="cmdAttr form-control input-sm" style="width:250px;float:right;" data-l1key="name">';
        tr += '</div>';
        tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 0px;" title="La valeur de la commande vaut par défaut la commande">';
        tr += '<option value="">Aucune</option>';
        tr += '</select>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;" readonly>';
        tr += '<span><center>{{' + init(_cmd.type) + '}}</center></span>';
        tr += '<span><center style="font-size:x-small;">({{' + init(_cmd.subType) + '}})</center></span>';
        tr += '</td>';
        tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="logicalId" value="0" style="width : 98%; display : inline-block;" title="{{Commande par défaut du plugin}}" readonly><br/>';
        tr += '</td>';
        tr += '<td>';
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
        if (init(_cmd.type)=='info') {
            tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized"/>{{Historiser}}</label></span> ';
        }
        else {
            tr += '<span style="display:none;><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized"/>{{Historiser}}</label></span> ';
        }
        tr += '</td>';
        tr += '<td>';
        tr += '<span></span>';
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> Tester</a>';
        }
 } else {
         if (!is_numeric(_cmd.id)) {
             tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" style="background:#F5F6CE">';
         }
         else {
             tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" style="background:#ECF8E0">';
         }
         tr += '<td>';
         tr += '<div style="width:100%;">';
         tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icône</a>';
         tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 8px;display: inline-block;"></span>';
         tr += '<input class="cmdAttr form-control input-sm" style="width:200px;float:right;" data-l1key="name">';
         tr += '</div>';
         tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="La valeur de la commande vaut par défaut la commande">';
         tr += '<option value="">Aucune</option>';
         tr += '</select>';
         tr += '</td>';
         tr += '<td>';
         tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
         tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
         tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
         tr += '</td>';
         tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="logicalId" value="" style="width : 98%; display : inline-block;" placeholder="{{ex : cmd=tts|value=Mon texte TTS (voir documentation pour plus d\'info)}}" maxlength="128" title="{{Limité à 128 caractères}}"><br/>';
         tr += '<textarea class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="listValue" title="<id>|<texte> séparé par ;" rows="4" style="resize:vertical;margin-top : 5px;width : 98%" placeholder="{{Liste de valeurs au format \'<id>|<texte>;<id>|<texte>;...\'\n Si la commande a pour nom \'cmdlist_<XXX>\', <id> => <commandes> (avec ^ pour séparateur), sinon utiliser \'#listValue#\' pour récuperer <id> dans la liste de commandes.}}"></textarea>';
         tr += '</td>';
         tr += '<td>';
         tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
         tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized"/>{{Historiser}}</label></span> ';
         tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
         tr += '<br/><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateValue" title="{{Valeur retour d\'état}}" placeholder="{{Valeur retour d\'état}}" style="width : 67%; display : inline-block;margin-top : 5px;margin-right : 4px;">';
         tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateTime" title="{{Durée avant retour d\'état (min)}}" placeholder="{{Durée avant retour d\'état (min)}}" style="width : 25%; display : inline-block;margin-top : 5px;margin-right : 2px;">';
         tr += '</td>';
         tr += '<td>';
         tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdId" style="display : none;margin-top : 0px;" title="Commande d\'information à mettre à jour">';
         tr += '<option value="">Aucune</option>';
         tr += '</select>';
         tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="Valeur de l\'information" title="Valeur de l\'information" style="display : none;margin-top : 5px;">';
         tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite"  style="width : 120px;" placeholder="Unité" title="Unité">';
         tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="Min" title="Min" style="margin-top=5px;width : 45%;display : inline-block;"> ';
         tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="Max" title="Max" style="margin-top=5px;width : 45%;display : inline-block;">';
         tr += '</td>';
         tr += '<td>';
         if (is_numeric(_cmd.id)) {
             tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
             tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> Tester</a>';
        }

    }
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
    $('.cmdAttr[data-l1key=logicalId]').prop('readonly', true);
    $('.cmdAttr[data-l1key=type]').prop('disabled', true);
    $('.cmdAttr[data-l1key=subType]').prop('disabled', true);
 }
 
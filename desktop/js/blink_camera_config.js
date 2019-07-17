
/**
* Fonction de rÃ©cupÃ©ration des donnÃ©es correspondant au critÃ¨re de recherche
* @param   {String} condition - Chaine indiquant la condition Ã  remplir
* @param   {Array}  table - Tableau contenant les donnÃ©es Ã  extraire
* @returns {Array}  result - Tableau contenant les donnÃ©es extraites
*/
function getDataFromTable( condition, table) {
    // rÃ©cupÃ©ration de la clÃ© et de la valeur
    var cde = condition.replace(/\s/g, '').split('='),
        key = cde[0],
        value = cde[1],
        result = [];
    
    // retour direct si *
    if (condition === '*') {
      return table.slice();
    }
    // retourne les Ã©lÃ©ments rÃ©pondant Ã  la condition
    result = table.filter( function(obj){
         return obj[key] === value;
      });
    return result;
  }
  /**
  * Affichage du nombre d'<option> prÃ©sentes dans le <select>
  * @param {Object} obj - <select> parent
  * @param {Number} nb - nombre Ã  afficher
  */
  function setNombre( obj, nb){
    var oElem = obj.parentNode.querySelector('.nombre');
    if( oElem){
      oElem.innerHTML = nb ? '(' +nb +')' :'';
    }
  }
  /**
  * Fonction d'ajout des <option> Ã  un <select>
  * @param   {String} id_select - ID du <select> Ã  mettre Ã  jour
  * @param   {Array}  liste - Tableau contenant les donnÃ©es Ã  ajouter
  * @param   {String} valeur - Champ pris en compte pour la value de l'<option>
  * @param   {String} texte - Champ pris en compte pour le texte affichÃ© de l'<option>
  * @returns {String} Valeur sÃ©lectionnÃ©e du <select> pour chainage
  */
  function updateSelect( id_select, liste, valeur, texte){
    var oOption,
        oSelect = document.getElementById( id_select),
        i, nb = liste.length;
    // vide le select
    oSelect.options.length = 0;
    // dÃ©sactive si aucune option disponible
    oSelect.disabled = nb ? false : true;
    // affiche info nombre options, facultatif
    setNombre( oSelect, nb);
    // ajoute 1st option
    if( nb){
      oSelect.add( new Option( '{{Choisir}}', ''));
      // focus sur le select
      oSelect.focus();
    }
    // crÃ©ation des options d'aprÃ¨s la liste
    for (i = 0; i < nb; i += 1) {
      // crÃ©ation option
      oOption = new Option( liste[i][texte], liste[i][valeur]);
      // ajout de l'option en fin
      oSelect.add( oOption);
    }
    // si une seule option on la sÃ©lectionne
    oSelect.selectedIndex = nb === 1 ? 1 : 0;
    // on retourne la valeur pour le select suivant
    return oSelect.value;
  }
  /**
  * fonction de chainage des <select>
  * @param {String|Object} ID du <select> Ã  traiter ou le <select> lui-mÃªme
  */
  function chainSelect( param){
    // affectation par dÃ©faut
    param = param || 'init';
    var liste,
        id     = param.id || param,
        valeur = param.value || '';
        
    // test Ã  faire pour rÃ©cupÃ©ration de la value
    if( typeof id === 'string'){
       param = document.getElementById( id);
       valeur = param ? param.value : '';
     }
  
    switch (id){
      case 'init':
        liste = getDataFromTable( '*', tbl_reseau);
        // mise Ã  jour du select
        valeur = updateSelect( 'select_reseau', liste, 'network_id', 'network_name');
        // chainage sur le select liÃ©
        chainSelect('select_reseau');
        break;
      case 'select_reseau':
        // rÃ©cup. des donnÃ©es
        liste = getDataFromTable( 'network_id=' +valeur, tbl_camera);
        
        // mise Ã  jour du select
        valeur = updateSelect( 'select_camera', liste, 'device_id', 'device_name');
        // chainage sur le select liÃ©
        chainSelect('select_camera');
        break;
      case 'select_camera':
        document.getElementById('select_camera').value = valeur;
        break;
    }
  }

    var oSelects = document.querySelectorAll('#liste select'),
        i, nb = oSelects.length;
    // affectation de la fonction sur le onchange
    for( i = 0; i < nb; i += 1) {
      oSelects[i].onchange = function() {
          chainSelect(this);
        };
    }
    // init du 1st select
    if( nb){
      chainSelect('init');
    }


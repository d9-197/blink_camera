function openScan(event) {
  jeeDialog.dialog({
    id: 'md_blinkScan',
    title: "{{Ajout des caméras}}",
    buttons: {
      confirm: {
        label: '<i class="icon securite-exit7"></i> {{Fermer}}',
        className: 'success',
        callback: {
          click: function(event) {
            reloadParentPage();
          }
        }
      },
      cancel: {
        className: 'hidden'
      }
    },
    contentUrl: 'index.php?v=d&plugin=blink_camera&modal=blink_camera.scan'
  });
}
document.querySelector(".eqLogicAction[data-action=scan]").addEventListener('click', openScan);

function openAccount(event) {
  jeeDialog.dialog({
    id: 'md_blinkAccounts',
    title: "{{Comptes Blink}}",
    buttons: {
      confirm: {
        label: '<i class="icon securite-exit7"></i> {{Fermer}}',
        className: 'success',
        callback: {
          click: function(event) {
            event.target.closest('#md_blinkAccounts')._jeeDialog.destroy();
          }
        }
      },
      cancel: {
        className: 'hidden'
      }
    },
    contentUrl: 'index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id=' + document.getElementById('ideq').value });
}
document.querySelector(".eqLogicAction[data-action=account]").addEventListener('click', openAccount);
function getEmails(forceValue) {
  domUtils.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getEmails",
      ideq: document.getElementById("ideq").value,
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, document.getElementById('div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //document.getElementById('div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        document.getElementById('div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
        return;
      } else {
        dataParsed = JSON.parse(data.result);
        if (dataParsed != null && dataParsed.message) {
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          document.getElementById('div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
          document.getElementById('select_email').disabled = false;
          document.getElementById('select_email').innerHTML = "";
          dataParsed.forEach(function (key, value) {
            mySelect = document.getElementById('select_email');
            mySelect.options[mySelect.options.length] = new Option(key, key);
          });
          if (forceValue) {
            setEmail();
          } else {
            getEmail();
          }
          getNetworks();
        }
        return;
      }
    }
  });

}

function getNetworks(forceValue) {
  //alert('getNetworks');
  if (document.getElementById('select_email').value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "getNetworks",
        email: document.getElementById('select_email').value,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          //document.getElementById('div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        } else {
          dataParsed = JSON.parse(data.result);
          if (dataParsed == null) {
          } else {
            document.getElementById('select_reseau').disabled = false;
            document.getElementById('select_reseau').innerHTML = "";
            dataParsed.forEach(function (key, value) {
              mySelect = document.getElementById('select_reseau');
              mySelect.options[mySelect.options.length] = new Option(key['network_name'], key['network_id']);
            });
            if (forceValue) {
              setNetwork();
            } else {
              getNetwork();
            }
            getCameras();
          }
          return;
        }
      }
    });
  }

}

function getCameras(forceValue) {
  // alert('getCameras');
  //if (document.getElementById('select_reseau').options[document.getElementById('select_reseau').selectedIndex].value!="") {
  domUtils.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getCameras",
      netid: document.getElementById('select_reseau').value,
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, document.getElementById('div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //document.getElementById('div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        document.getElementById('div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
        return;
      } else {
        dataParsed = JSON.parse(data.result);
        if (dataParsed != null && dataParsed.message) {
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          document.getElementById('div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
          document.getElementById('select_camera').disabled = false;
          document.getElementById('select_camera').innerHTML = "";
          if (dataParsed!=null) {
            dataParsed.forEach(function (key, value) {
              mySelect = document.getElementById('select_camera');
              mySelect.options[mySelect.options.length] = new Option(key['device_name'], key['device_id']);
            });
          }
          if (forceValue) {
            setCamera();
          } else {
            getCamera();
          }
        }
        return;
      }
    }
  });
  //}
}

function getEmail() {
  if (document.getElementById("ideq").value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "getEmail",
        ideq: document.getElementById("ideq").value,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          //document.getElementById('div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        } else {
          dataParsed = JSON.parse(data.result);
          if (dataParsed.message) {
            if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
            document.getElementById('div_alert').showAlert({
              message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
              level: 'warning'
            });
          } else {
            //document.getElementById('select_email').val(dataParsed);
            var optionExists = checkOptionValueExist("select_email", dataParsed);
            if (!optionExists) {
              mySelect = document.getElementById('select_email');
              mySelect.options[mySelect.options.length] = new Option(dataParsed, dataParsed);
            }
            document.getElementById('select_email').value = dataParsed;
          }
          return;
        }
      }
    });
  }
}
function checkOptionValueExist(mySelect, myOption) {
  for (i = 0; i < document.getElementById(mySelect).length; ++i) {
    if (document.getElementById(mySelect).options[i].value == myOption) {
      return true;
    }
  }
  return false;
}
function setEmail() {
  if (document.getElementById("ideq").value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "setEmail",
        ideq: document.getElementById("ideq").value,
        email: document.getElementById('select_email').value,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        }
      }
    });
  }
}
function getNetwork() {
  if (document.getElementById("ideq").value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "getNetwork",
        ideq: document.getElementById("ideq").value,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          //document.getElementById('div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        } else {
          dataParsed = JSON.parse(data.result);
          if (dataParsed.message) {
            if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
            document.getElementById('div_alert').showAlert({
              message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
              level: 'warning'
            });
          } else {
            var optionExists = checkOptionValueExist('select_reseau', dataParsed['network_id']);
            if (!optionExists) {
              mySelect = document.getElementById('select_reseau');
              mySelect.options[mySelect.options.length] = new Option(dataParsed['network_name'], dataParsed['network_id']);
            }
            document.getElementById('select_reseau').value = dataParsed['network_id'];
          }
          return;
        }
      }
    });
  }
}
function setNetwork() {
  if (document.getElementById("ideq").value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "setNetwork",
        ideq: document.getElementById("ideq").value,
        netid: document.getElementById('select_reseau').options[document.getElementById('select_reseau').selectedIndex].value,
        netname: document.getElementById('select_reseau').options[document.getElementById('select_reseau').selectedIndex].text,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        }
      }
    });
  }
}
function getCamera() {
  if (document.getElementById("ideq").value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "getCamera",
        ideq: document.getElementById("ideq").value,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          //document.getElementById('div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        } else {
          dataParsed = JSON.parse(data.result);
          if (dataParsed.message) {
            if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
            document.getElementById('div_alert').showAlert({
              message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
              level: 'warning'
            });
          } else {
            var optionExists = checkOptionValueExist('select_camera', dataParsed['device_id']);
            if (!optionExists) {
              mySelect = document.getElementById('select_camera');
              mySelect.options[mySelect.options.length] = new Option(dataParsed['device_name'], dataParsed['device_id']);
            }
            document.getElementById('select_camera').value = dataParsed['device_id'];

          }
          return;
        }
      }
    });
  }
};
function setCamera() {
  if (document.getElementById("ideq").value != "") {
    domUtils.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "setCamera",
        ideq: document.getElementById("ideq").value,
        devid: document.getElementById('select_camera').options[document.getElementById('select_camera').selectedIndex].value,
        devname: document.getElementById('select_camera').options[document.getElementById('select_camera').selectedIndex].text,
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, document.getElementById('div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          document.getElementById('div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
          return;
        }
      }
    });
  }
}
function initSelect() {
  getEmails();
  getEmail();
  //setTimeout( getEmail(), 500);
  getNetworks();
  getNetwork();
  getCameras();
  getCamera();
}
function updateConfigAccount(email, key, value) {
  domUtils.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "update_cfg_account",
      email: email,
      key: key,
      value: '' + value,
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, document.getElementById('div_alert'));
    },
    success: function (data) {
      $res = JSON.parse(JSON.parse(data.result));
      if ($res.status != "true") {
        jeedomUtils.showAlert({ message: "{{Erreur lors de la mise à jour de la configuration du compte}}", level: 'warning' });
      }
      return;
    }
  });
}
function reinitAccounts() {
  domUtils.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "reinit_cfg_account"
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, document.getElementById('div_alert'));
    },
    success: function (data) {
      $res = JSON.parse(JSON.parse(data.result));
      if ($res.status != "true") {
        jeedomUtils.showAlert({ message: "{{Erreur lors de la réinitialisation de la configuration des comptes}}", level: 'warning' });
      }
      return;
    }
  });
  $('#md_modal').dialog({
    title: "{{Comptes Blink}}"
  }).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id=' + document.getElementById('ideq').value).dialog('open');
}
function removeConfigAccount(email) {
  domUtils.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "remove_cfg_account",
      email: email
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, document.getElementById('div_alert'));
    },
    success: function (data) {
      $res = JSON.parse(JSON.parse(data.result));
      if ($res.status != "true") {
        jeedomUtils.showAlert({ message: "{{Erreur lors de la mise à jour de la configuration du compte}}", level: 'warning' });
      }
      return;
    }
  });
  $('#md_modal').dialog({
    title: "{{Comptes Blink}}"
  }).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id=' + document.getElementById('ideq').value).dialog('open');
}
function savePwd(accountNumber) {
  updateConfigAccount(document.getElementById("email_" + accountNumber).value, 'pwd', document.getElementById("pwd_" + accountNumber).value);
};
function addAccount(email) {
  updateConfigAccount(email, 'pwd', 'xxx');
  $('#md_modal').dialog({
    title: "{{Comptes Blink}}"
  }).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id=' + document.getElementById('ideq').value).dialog('open');
};
function checkConnexionBlink(accountNumber) {
  updateConfigAccount(document.getElementById("email_" + accountNumber).value, 'pwd', document.getElementById("pwd_" + accountNumber).value);
  domUtils.ajax({
    type: "POST",
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "test_blink",
      email: document.getElementById("email_" + accountNumber).value
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, document.getElementById('div_alert'));
    },
    success: function (data) {
      $res = JSON.parse(JSON.parse(data.result));
      if ($res.token == "true") {
        document.getElementById('verifdiv_' + accountNumber).unseen();
        if (document.getElementById('blink_cfg')!=null) { document.getElementById('blink_cfg').seen();}
        jeedomUtils.showAlert({ title:document.getElementById("email_" + accountNumber).value,message: "{{Connexion à votre compte Blink OK}}" , level: 'info' });
        //checkBlinkCameraConfig();
        //sessionStorage.setItem("blink_camera_refresh", "REFRESH");
      } else if ($res.token == "verif") {
        jeedomUtils.showAlert({ message: "{{Connexion à votre compte Blink OK mais un code de vérification est nécessaire}} (" + document.getElementById("email_" + accountNumber).value + ")", level: 'warning' });
        //jeedomUtils.showAlert({message: "{{Connexion à votre compte Blink OK - Email de vérification nécessaire}}", level: 'info'});
        document.getElementById('verifdiv_' + accountNumber).seen();
      } else if ($res.token == "limit") {
        jeedomUtils.showAlert({ message: "{{limite connexion}}", level: 'danger' });
        document.getElementById('verifdiv_' + accountNumber).unseen();
        //jeedomUtils.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
        if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
      } else {
        jeedomUtils.showAlert({ message: "{{Erreur de connexion à votre compte Blink}} (" + document.getElementById("email_" + accountNumber).value + ")", level: 'danger' });
        document.getElementById('verifdiv_' + accountNumber).unseen();
        //jeedomUtils.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
        if (document.getElementById('blink_cfg')!=null) {document.getElementById('blink_cfg').unseen();}
      }
      return;
    }
  });
};
function verifyPin(accountNumber) {
  domUtils.ajax({
    type: 'POST',
    url: 'plugins/blink_camera/core/ajax/blink_camera.ajax.php',
    data: {
      action: 'verifyPinCode',
      pin: document.getElementById("pincode_" + accountNumber).value,
      email: document.getElementById("email_" + accountNumber).value
    },
    dataType: 'json',
    global: false,
    error: function (request, status, error) {
      bootbox.alert("ERREUR 'verifyPinCode' !<br>Erreur lors l'envoi du code de vérification à Blink.");
    },
    success: function (json_res) {
      checkConnexionBlink(accountNumber);
    }
  });
};
function reloadParentPage(e) {
  document.querySelector('.btClose').removeEventListener('click',reloadParentPage,{ once: true });
  var vars = getUrlVars()
  var url = 'index.php?'
  for (var i in vars) {
      if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
          url += i + '=' + vars[i].replace('#', '') + '&'
      }
  }   
  url=url.substring(0, url.length - 1);
  jeedomUtils.loadPage(url)
};
function loadCameraPage(idcam) {
  var vars = getUrlVars()
  var url = 'index.php?'
  for (var i in vars) {
      if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
          url += i + '=' + vars[i].replace('#', '') + '&'
      }
  }   
  url += 'id='+idcam
  jeedomUtils.loadPage(url)
};
document.querySelectorAll('.bt_return_cfg').forEach(function (key, value) {key.addEventListener('click',reloadParentPage);})

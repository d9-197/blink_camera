$('.eqLogicAction[data-action=scan]').on('click', function() {
  $('#md_modal').dialog({
    title: "{{Ajout des caméras}}"
  }).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.scan').dialog('open')
  //index.php?v=d&plugin=blink_camera&modal=

})
$('.eqLogicAction[data-action=account]').on('click', function() {
  $('#md_modal').dialog({
    title: "{{Comptes Blink}}"
  }).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id='+$("#ideq").find(":selected").text()).dialog('open')
  //index.php?v=d&plugin=blink_camera&modal=

})
function getEmails(forceValue) {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getEmails",
      ideq: $("#ideq").find(":selected").text(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //$('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      } else {
        dataParsed = $.parseJSON(data.result);
        if (dataParsed!=null && dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
            $("#select_email").attr('disabled', false);
            $('#select_email').find('option').remove();
            $.each(dataParsed,function(key, value)
            {
              $("#select_email").append('<option value=' + value + '>' + value + '</option>');
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
  if ($("#select_email").find(":selected").val()!="") {
    $.ajax({
      type: "POST",
      async: false,
      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
      data: {
        action: "getNetworks",
        email: $("#select_email").find(":selected").val(),
      },

      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error, $('#div_alert'));
      },
      success: function (data) {
        if (data.state != 'ok') {
          //$('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
          $('#div_alert').showAlert({
            message: "'" + data.result + "'",
            level: 'danger'
          });
          $('.blink_cfg').hide();
          return;
        } else {
          dataParsed = $.parseJSON(data.result);
          if (dataParsed==null) {
          } else {
              $("#select_reseau").attr('disabled', false);
              $('#select_reseau').find('option').remove();
              $.each(dataParsed,function(key, value)
              {
                  $("#select_reseau").append('<option value=' + value['network_id'] + '>' + value['network_name'] + '</option>');
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
 //if ($("#select_reseau").find(":selected").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getCameras",
      netid: $("#select_reseau").find(":selected").val(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //$('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      } else {
        dataParsed = $.parseJSON(data.result);
        if (dataParsed!=null && dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
            $("#select_camera").attr('disabled', false);
            $('#select_camera').find('option').remove();
            $.each(dataParsed,function(key, value)
            {
                $("#select_camera").append('<option value=' + value['device_id'] + '>' + value['device_name'] + '</option>');
            });
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
  if ($("#ideq").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getEmail",
      ideq: $("#ideq").val(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //$('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      } else {
        dataParsed = $.parseJSON(data.result);
        if (dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
          //$("#select_email").val(dataParsed);
          var optionExists = ($('#select_email option[value="' + dataParsed + '"]').length > 0);
          if(!optionExists)
          {
            $('#select_email').append("<option value='"+dataParsed+"'>"+dataParsed+"</option>");
          }
          $("#select_email").val(dataParsed);
        }
        return;
      }
    }
  });
  }
}
function setEmail() {
  if ($("#ideq").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "setEmail",
      ideq: $("#ideq").val(),
      email: $("#select_email").val(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      }
    }
  });
  }
}
function getNetwork() {
  if ($("#ideq").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getNetwork",
      ideq: $("#ideq").val(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //$('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      } else {
        dataParsed = $.parseJSON(data.result);
        if (dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
          var optionExists = ($('#select_reseau option[value=' + dataParsed['network_id'] + ']').length > 0);
          if(!optionExists)
          {
            $('#select_reseau').append("<option value='"+dataParsed['network_id']+"'>"+dataParsed['network_name']+"</option>");
          }
          $("#select_reseau").val(dataParsed['network_id']);
        }
        return;
      }
    }
  });
}
}
function setNetwork() {
  if ($("#ideq").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "setNetwork",
      ideq: $("#ideq").val(),
      netid: $("#select_reseau").find(":selected").val(),
      netname: $("#select_reseau").find(":selected").text(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      }
    }
  });
  }
}
function getCamera() {
  if ($("#ideq").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getCamera",
      ideq: $("#ideq").val(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        //$('#div_alert').showAlert({message: data.result.replace('\{\{','').replace('\}\}',''), level: 'danger'});
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
        return;
      } else {
        dataParsed = $.parseJSON(data.result);
        if (dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
          var optionExists = ($('#select_camera option[value=' + dataParsed['device_id'] + ']').length > 0);
          if(!optionExists)
          {
            $('#select_camera').append("<option value='"+dataParsed['device_id']+"'>"+dataParsed['device_name']+"</option>");
          } 
          $("#select_camera").val(dataParsed['device_id']);
          
        }
        return;
      }
    }
  });
}
};
function setCamera() {
  if ($("#ideq").val()!="") {
  $.ajax({
    type: "POST",
    async: false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "setCamera",
      ideq: $("#ideq").val(),
      devid: $("#select_camera").find(":selected").val(),
      devname: $("#select_camera").find(":selected").text(),
    },

    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: "'" + data.result + "'",
          level: 'danger'
        });
        $('.blink_cfg').hide();
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
function updateConfigAccount(email,key,value) {
  $.ajax({
    type: "POST",
    async:false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
        action: "update_cfg_account",
        email : email,
        key : key,
        value : ''+value,
    },
    dataType: 'json',
    error: function(request, status, error) {
        handleAjaxError(request, status, error,$('#div_alert'));
    },
    success: function(data) {
        $res = JSON.parse(JSON.parse(data.result));
        if ($res.status != "true") {
            $.fn.showAlert({message: "{{Erreur lors de la mise à jour de la configuration du compte}}", level: 'warning'});
        }
        return;
    }
});
}
function removeConfigAccount(email) {
  $.ajax({
    type: "POST",
    async:false,
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
        action: "remove_cfg_account",
        email : email
    },
    dataType: 'json',
    error: function(request, status, error) {
        handleAjaxError(request, status, error,$('#div_alert'));
    },
    success: function(data) {
        $res = JSON.parse(JSON.parse(data.result));
        if ($res.status != "true") {
            $.fn.showAlert({message: "{{Erreur lors de la mise à jour de la configuration du compte}}", level: 'warning'});
        }
        return;
    }
});
$('#md_modal').dialog({
  title: "{{Comptes Blink}}"
}).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id='+$("#ideq").find(":selected").text()).dialog('open');
}
function savePwd(accountNumber) {
  updateConfigAccount(document.getElementById("email_"+accountNumber).value,'pwd',document.getElementById("pwd_"+accountNumber).value);
};
function addAccount(email) {
  updateConfigAccount(email,'pwd','xxx');
  $('#md_modal').dialog({
    title: "{{Comptes Blink}}"
  }).load('index.php?v=d&plugin=blink_camera&modal=blink_camera.account&id='+$("#ideq").find(":selected").text()).dialog('open');
};
function checkConnexionBlink(accountNumber) {
          $.ajax({
                      type: "POST",
                      url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
                      data: {
                          action: "test_blink",
                          email : document.getElementById("email_"+accountNumber).value
                      },
                      dataType: 'json',
                      error: function(request, status, error) {
                          handleAjaxError(request, status, error,$('#div_alert'));
                      },
                      success: function(data) {
                          $res = JSON.parse(JSON.parse(data.result));
                          if ($res.token == "true") {
                              $.fn.showAlert({message: "{{Connexion à votre compte Blink OK}}", level: 'info'});
                              $('#verifdiv_'+accountNumber).hide();
                              $('.blink_cfg').show();
                              //checkBlinkCameraConfig();
                              sessionStorage.setItem("blink_camera_refresh","REFRESH");
                          } else if ($res.token == "verif") {
                              $.fn.showAlert({message: "{{Connexion à votre compte Blink OK mais un code de vérification est nécessaire}}", level: 'warning'});
                              //$.fn.showAlert({message: "{{Connexion à votre compte Blink OK - Email de vérification nécessaire}}", level: 'info'});
                              $('#verifdiv_'+accountNumber).show();
                          } else if ($res.token == "limit") {
                              $.fn.showAlert({message: "{{limite connexion}}", level: 'danger'});
                              $('#verifdiv_'+accountNumber).hide();
                              //$.fn.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
                              $('.blink_cfg').hide();
                          } else {
                              $.fn.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
                              $('#verifdiv_'+accountNumber).hide();
                              //$.fn.showAlert({message: "{{Erreur de connexion à votre compte Blink}}", level: 'danger'});
                              $('.blink_cfg').hide();
                          }
                          return;
                      }
                  });
      }; 
function verifyPin(accountNumber) {
        $.ajax({
            type: 'POST',
            url: 'plugins/blink_camera/core/ajax/blink_camera.ajax.php',
            data: {
                action: 'verifyPinCode',
                pin: document.getElementById("pincode_"+accountNumber).value,
                email: document.getElementById("email_"+accountNumber).value
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
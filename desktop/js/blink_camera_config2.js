$('#bt_refresh_blink_cfg').on('click', function (e) {
  getEmails();
  getNetworks();
  getCameras();
});

if (document.querySelector('#select_email')) {
  document.querySelector('#select_email').onchange = function () {
  getNetworks();
};
}
if (document.querySelector('#select_reseau')) {
  document.querySelector('#select_reseau').onchange = function () {
  getCameras();
};
}


function getEmails() {
  $.ajax({
    type: "POST",
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
        if (dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
            $("#select_email").attr('disabled', false);
            $.each(dataParsed,function(key, value)
            {
                $("#select_email").append('<option value=' + key + '>' + value + '</option>');
            });
        }
        return;
      }
    }
  });
  
}

function getNetworks() {
  $.ajax({
    type: "POST",
    url: "plugins/blink_camera/core/ajax/blink_camera.ajax.php",
    data: {
      action: "getNetworks",
      email: $("#select_email").find(":selected").text(),
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
            $.each(dataParsed,function(key, value)
            {
                $("#select_reseau").append('<option value=' + value['network_id'] + '>' + value['network_name'] + '</option>');
            });
        }
        return;
      }
    }
  });
  
}

function getCameras() {
  $.ajax({
    type: "POST",
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
        if (dataParsed.message) {
          $('.blink_cfg').hide();
          $('#div_alert').showAlert({
            message: dataParsed.message.replace('\{\{', '{{').replace('\}\}', '}}'),
            level: 'warning'
          });
        } else {
            $("#select_camera").attr('disabled', false);
            $.each(dataParsed,function(key, value)
            {
                $("#select_camera").append('<option value=' + value['network_id'] + '>' + value['network_name'] + '</option>');
            });
        }
        return;
      }
    }
  });
  
}

function getEmail() {
  $.ajax({
    type: "POST",
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
          $("#select_email").val(dataParsed);
          //$.each(dataParsed, function (k, item) {
              //alert(dataParsed);
          //});
        }
        return;
      }
    }
  });
}

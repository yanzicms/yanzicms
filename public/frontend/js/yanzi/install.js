/*** Created by A.J on 2020/5/14.*/$(document).ready(function(){$.post("testing",function(data){if(data == 'ok'){$('#continue').removeClass('d-none').siblings().addClass('d-none');$('#installModal').modal('show');}else{$('#details').html(data);$('#failed').removeClass('d-none').siblings().addClass('d-none');}});$("#submit").click(function(){var button = $(this);var submit = $("#dbinfo").serialize();button.children('div').removeClass('d-none');$.post("chkdb", submit,function(data){button.children('div').addClass('d-none');if(data == 'ok'){$('#installModal').modal('hide');$('#progress').removeClass('d-none').siblings().addClass('d-none');$('#prompt').text(button.data('prompt'));$.post("creating", submit,function(data){$.post(button.data('url'), submit,function(data){if(data == 'ok'){$('#complete').removeClass('d-none').siblings().addClass('d-none');}else{$.alert({title: button.data('notice'),content: data,buttons: {ok: {text: button.data('understood'),action: function () {$('#continue').removeClass('d-none').siblings().addClass('d-none');$('#installModal').modal('show');}}}});}});});}else{$.alert({title: button.data('notice'),content: data,buttons: {ok: {text: button.data('understood')}}});}});});});
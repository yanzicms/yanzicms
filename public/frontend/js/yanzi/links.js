/*** Created by A.J on 2020/10/28.*/$(document).ready(function(){$(".yanzisort").change(function(){var obj = $(this);$.post("linksort", {id: $(this).data("id"), val: $(this).val()},function(data){if(data == "ok"){obj.data("val", obj.val());}else{obj.val(obj.data("val"));$.alert({title: $('#error').text(),content: data,buttons: {confirm: {text: $('#ok').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});$(".yanzihome").click(function(){var obj = $(this);var ischk = 0;if($(this).prop("checked") == true){ischk = 1;}$.post("linkhome", {id: $(this).data("id"), val: ischk},function(data){if(data != "ok"){if(ischk == 1){obj.prop("checked", false);}else{obj.prop("checked", true);}$.alert({title: $('#error').text(),content: data,buttons: {confirm: {text: $('#ok').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});$(".yanzistatus").click(function(){var obj = $(this);var ischk = 0;if($(this).prop("checked") == true){ischk = 1;}$.post("linkstatus", {id: $(this).data("id"), val: ischk},function(data){if(data != "ok"){if(ischk == 1){obj.prop("checked", false);}else{obj.prop("checked", true);}$.alert({title: $('#error').text(),content: data,buttons: {confirm: {text: $('#ok').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});$(".yanzidel").click(function(){var obj = $(this);$(this).next("div").removeClass("d-none");$.post("linkdel", {id: $(this).data("id")},function(data){obj.next("div").addClass("d-none");if(data == "ok"){obj.parent().parent().remove();}else{$.alert({title: $('#error').text(),content: data,buttons: {confirm: {text: $('#ok').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});});
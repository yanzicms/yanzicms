/*** Created by A.J on 2020/5/18.*/$(document).ready(function(){var maxh = $("#yanzistart").offset().top - $(window).scrollTop();$("#yanzimenu").css("max-height", maxh);$("form").find("button.submit").on("click", function(){var subobj = $(this);subobj.children("div").removeClass("d-none");$.post("", subobj.parents("form").serialize(),function(data){subobj.children("div").addClass("d-none");if(data == "ok"){if(!subobj.hasClass("notrefreshing")){if($("#jumpto").length > 0 && $("#jumpto").text() != ""){window.location.href = $("#jumpto").text();}else{window.location.reload();}}}else{$.alert({title: $('#error').text(),content: data,buttons: {confirm: {text: $('#ok').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});$('[data-toggle="popover"]').popover();});
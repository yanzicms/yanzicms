/*** Created by A.J on 2020/11/3.*/$(document).ready(function(){$(".yanzidel").click(function(){var obj = $(this);$(this).next("div").removeClass("d-none");$.post("mymessagedel", {id: $(this).data("id")},function(data){obj.next("div").addClass("d-none");if(data == "ok"){obj.parent().parent().remove();}else{$.alert({title: $('#error').text(),content: data,buttons: {confirm: {text: $('#ok').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});});
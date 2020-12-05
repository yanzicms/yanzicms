/**
 * Created by A.J on 2020/10/24.
 */
$(document).ready(function(){
    $(".yanzistatus").click(function(){
        var obj = $(this);
        var ischk = 0;
        if($(this).prop("checked") == true){
            ischk = 1;
        }
        $.post("contentstatus", {id: $(this).data("id"), val: ischk},
            function(data){
                if(data != "ok"){
                    if(ischk == 1){
                        obj.prop("checked", false);
                    }
                    else{
                        obj.prop("checked", true);
                    }
                    $.alert({
                        title: $('#error').text(),
                        content: data,
                        buttons: {
                            confirm: {
                                text: $('#ok').text(),
                                btnClass: 'btn-info',
                                keys: ['enter']
                            }
                        }
                    });
                }
            });
    });
    $(".yanzitop").click(function(){
        var obj = $(this);
        var ischk = 0;
        if($(this).prop("checked") == true){
            ischk = 1;
        }
        $.post("contenttop", {id: $(this).data("id"), val: ischk},
            function(data){
                if(data != "ok"){
                    if(ischk == 1){
                        obj.prop("checked", false);
                    }
                    else{
                        obj.prop("checked", true);
                    }
                    $.alert({
                        title: $('#error').text(),
                        content: data,
                        buttons: {
                            confirm: {
                                text: $('#ok').text(),
                                btnClass: 'btn-info',
                                keys: ['enter']
                            }
                        }
                    });
                }
            });
    });
    $(".yanzirecommend").click(function(){
        var obj = $(this);
        var ischk = 0;
        if($(this).prop("checked") == true){
            ischk = 1;
        }
        $.post("contentrecommend", {id: $(this).data("id"), val: ischk},
            function(data){
                if(data != "ok"){
                    if(ischk == 1){
                        obj.prop("checked", false);
                    }
                    else{
                        obj.prop("checked", true);
                    }
                    $.alert({
                        title: $('#error').text(),
                        content: data,
                        buttons: {
                            confirm: {
                                text: $('#ok').text(),
                                btnClass: 'btn-info',
                                keys: ['enter']
                            }
                        }
                    });
                }
            });
    });
    $(".yanzidel").click(function(){
        var obj = $(this);
        $(this).next("div").removeClass("d-none");
        $.post("contentdel", {id: $(this).data("id")},
            function(data){
                obj.next("div").addClass("d-none");
                if(data == "ok"){
                    obj.parent().parent().remove();
                }
                else{
                    $.alert({
                        title: $('#error').text(),
                        content: data,
                        buttons: {
                            confirm: {
                                text: $('#ok').text(),
                                btnClass: 'btn-info',
                                keys: ['enter']
                            }
                        }
                    });
                }
            });
    });
    $(".viewcontribution").on("click", function(){
        var tobj = $(this);
        $("#contributionModalLabel").text(tobj.data("title"));
        $("#contributionModalBody").html($("#loading").html());
        $.post("getcontribution", { id: tobj.data("cid")}, function(data){
            $("#contributionModalBody").html(data);
        });
    });
});
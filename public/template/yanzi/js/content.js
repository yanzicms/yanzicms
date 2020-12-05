/**
 * Created by A.J on 2020/10/31.
 */
$(document).ready(function(){
    $("form button.comment").click(function(){
        var form = $(this).parents("form");
        var id = form.find("input[name='id']").val();
        var reply = form.find("input[name='reply']").val();
        var comment = form.find("textarea[name='comment']").val();
        $.post($("#submitcomment").text(), {id: id, reply: reply, comment: comment},
            function(data){
                if(data == "ok"){
                    window.location.reload();
                }
                else{
                    alert(data);
                }
            });
    });
    var ismessage = false;
    $("form button.message").click(function(){
        if(ismessage){
            alert("已经留言");
        }
        else{
            var form = $(this).parents("form");
            var name = form.find("input[name='name']").val();
            var phone = form.find("input[name='phone']").val();
            var email = form.find("input[name='email']").val();
            var other = form.find("input[name='other']").val();
            var message = form.find("textarea[name='message']").val();
            $.post($("#submitmessage").text(), {name: name, phone: phone, email: email, other: other, message: message},
                function(data){
                    if(data == "ok"){
                        ismessage = true;
                        alert("完成留言");
                    }
                    else{
                        alert(data);
                    }
                });
        }
    });
    $("#favorites").click(function(){
        var id = $("#id").text();
        $.post($("#submitfavorites").text(), {id: id},
            function(data){
                alert(data);
            });
    });
    $("#likes").click(function(){
        var id = $("#id").text();
        $.post($("#submitlikes").text(), {id: id},
            function(data){
                alert(data);
            });
    });
    $(".reply").click(function(){
        var reply = $(this).parent().find(".replydiv");
        if(reply.hasClass("d-none")){
            reply.removeClass("d-none");
        }
        else{
            reply.addClass("d-none");
        }
    });
});
/*** Created by A.J on 2020/10/24.*/$(document).ready(function(){var e=HE.getEditor("content",{height:"500px",autoHeight:!0,autoFloat:!0,topOffset:49,uploadPhoto:!0,uploadPhotoHandler:$("#upload_handyeditor_url").text(),uploadPhotoSize:2048,uploadPhotoType:"gif,png,jpg,jpeg,webp",uploadPhotoSizeError:$("#sizeError").text(),uploadPhotoTypeError:$("#typeError").text(),uploadParam:{allow:"gif,png,jpg,jpeg,webp",fullpath:"1"},skin:"yanzicms"});$("#imagefile").on("change",function(){var e=$(this).val();$(this).next(".custom-file-label").html(e),$("#imagefile").upload("uploadfile",{allow:"jpg,gif,png,jpeg,webp"},function(e){""!=e?($("#image").val(e),$("#imageimg").attr("src",$("#domain").text()+e),$("#imagediv").removeClass("d-none")):($("#imagefile").next(".custom-file-label").html($("#choosefile").text()),$.alert({title:$("#error").text(),content:$("#uploadfailed").text(),buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}}))})}),$("#imagedel").on("click",function(){var l=$(this);$(this).children("div:last").removeClass("d-none"),$.post("uploadfiledel",{filename:$("#image").val()},function(e){l.children("div:last").addClass("d-none"),"ok"==e?($("#image").val(""),$("#imageimg").attr("src",""),$("#imagediv").addClass("d-none"),$("#imagefile").next(".custom-file-label").html($("#choosefile").text())):$.alert({title:$("#error").text(),content:e,buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}})})}),""!=$("#image").val()&&($("#imageimg").attr("src",$("#domain").text()+$("#image").val()),$("#imagediv").removeClass("d-none"));var p,l=JSON.parse($("#parts").text()),s=[],c=[],u=[],v=[],f=[];0<$("#yanzicms").length&&($.each(l,function(e,a){var l,t,i,o,d,m,r,n;"video"==a.parttype?(p=$("#modelvideodiv").html().replace('id="modelvideodiv_"','id="modelvideodiv_'+a.partalias+'"').replace('id="modelvideodel_"','id="modelvideodel_'+a.partalias+'"').replace('id="modelvideoup_"','id="modelvideoup_'+a.partalias+'"').replace('id="modelvideo_"','id="modelvideo_'+a.partalias+'"').replace('for="modelvideo_"','for="modelvideo_'+a.partalias+'"').replace('value=""','value="'+$("#modelc_"+a.partalias).html()+'"'),u.push(a.partalias)):"annex"==a.parttype?(p=$("#modelfilediv").html().replace('id="modelfilediv_"','id="modelfilediv_'+a.partalias+'"').replace('id="modelfiledel_"','id="modelfiledel_'+a.partalias+'"').replace('id="modelfileup_"','id="modelfileup_'+a.partalias+'"').replace('id="modelfile_"','id="modelfile_'+a.partalias+'"').replace('for="modelfile_"','for="modelfile_'+a.partalias+'"').replace('value=""','value="'+$("#modelc_"+a.partalias).html()+'"'),v.push(a.partalias)):"multiplepictures"==a.parttype?(p=$("#multiplepicturesdiv").html().replace('id="multiimagediv_"','id="multiimagediv_'+a.partalias+'"').replace('id="multiimage_"','id="multiimage_'+a.partalias+'"').replace('id="multiimagefile_"','id="multiimagefile_'+a.partalias+'"').replace('name=""','name="'+a.partalias+'"').replace('value=""','value="'+$("#modelc_"+a.partalias).html()+'"'),f.push(a.partalias)):"date"==a.parttype?("1000-01-01"==(l=$("#modelc_"+a.partalias).html())&&(l=""),p=$("#modeldatediv").html().replace('id=""','id="datepicker_'+a.partalias+'"').replace('value=""','value="'+l+'"'),s.push(a.partalias)):"datetime"==a.parttype?("1000-01-01 00:00:00"==(t=$("#modelc_"+a.partalias).html())&&(t=""),p=$("#modeldatediv").html().replace('id=""','id="datetimepicker_'+a.partalias+'"').replace('value=""','value="'+t+'"'),c.push(a.partalias)):p="singlechoice"==a.parttype?$("#modelsinglechoicediv").html():"multiplechoice"==a.parttype?(i=a.defaults.replace(/，/g,",").split(","),o="",$.each(i,function(e,l){l=$.trim(l),o+=$("#multiplechoicediv").html().replace('id="modelmultiplechoicediv_"','id="modelmultiplechoicediv_'+a.partalias+"_"+e+'"').replace('for="modelmultiplechoicediv_"></label>','for="modelmultiplechoicediv_">'+l+"</label>").replace('for="modelmultiplechoicediv_"','for="modelmultiplechoicediv_'+a.partalias+"_"+e+'"').replace('value="" name=""','value="'+l+'" name="'+a.partalias+'[]"')}),$("#modelmultiplechoicediv").html().replace('<div class="multiplechoicediv"></div>','<div class="multiplechoicediv">'+o+"</div>")):"text"==a.parttype?$("#modeltextdiv").html().replace("></textarea>",">"+$("#modelc_"+a.partalias).html()+"</textarea>"):"int"==a.parttype?$("#modelintdiv").html().replace('value=""','value="'+$("#modelc_"+a.partalias).html()+'"'):"decimal"==a.parttype?$("#modeldecimaldiv").html().replace('value=""','value="'+$("#modelc_"+a.partalias).html()+'"'):$("#modeldiv").html().replace('value=""','value="'+$("#modelc_"+a.partalias).html()+'"'),$("#generativemodel").append(p),$("#generativemodel").children(":last").find("label:first").text(a.partname),"text"==a.parttype?$("#generativemodel").children(":last").find("textarea:first").attr("name",a.partalias):"singlechoice"==a.parttype?(d=a.defaults.replace(/，/g,",").split(","),(m=$("#generativemodel").children(":last").find("select:first")).attr("name",a.partalias),$.each(d,function(e,l){l=$.trim(l),m.append('<option value="'+l+'">'+l+"</option>")}),m.val($("#modelc_"+a.partalias).html())):"multiplechoice"==a.parttype?(r=$("#modelc_"+a.partalias).html().split(","),$("input[type='checkbox'][name='"+a.partalias+"[]']").each(function(e,l){-1<$.inArray($(this).val(),r)&&$(this).prop("checked",!0)})):"multiplepictures"==a.parttype?(n=$("#multiimage_"+a.partalias).val().split(","),$.each(n,function(e,l){var t=$("#multiimagediv").html().replace('src=""','src="'+$("#domain").text()+l+'"').replace('data-img=""','data-img="'+l+'"').replace('class="multiimagedel multiimagedel_"','class="multiimagedel multiimagedel_'+a.partalias+'"');$("#multiimagediv_"+a.partalias).append(t)})):$("#generativemodel").children(":last").find("input:first").attr("name",a.partalias)}),$.each(s,function(e,l){$("#datepicker_"+l).datepicker({locale:"zh-cn",format:"yyyy-mm-dd",uiLibrary:"bootstrap4"})}),$.each(c,function(e,l){$("#datetimepicker_"+l).datetimepicker({locale:"zh-cn",format:"yyyy-mm-dd HH:MM:ss",uiLibrary:"bootstrap4",footer:!0,modal:!0})}),$.each(u,function(e,t){""!=$("#modelvideoup_"+t).val()&&$("#modelvideodiv_"+t).removeClass("d-none"),$("#modelvideo_"+t).on("change",function(){var e=$(this).val(),l=$(this);$(this).next(".custom-file-label").html(e),$(this).parent().next("small").removeClass("d-none"),$("#modelvideo_"+t).upload("uploadfile",{allow:"mp4,avi,mov,flv,rmvb,wmv,swf,mp3,wav,wma,mid,mpg,asf,rm,webm,ogg"},function(e){l.parent().next("small").addClass("d-none"),""!=e?($("#modelvideoup_"+t).val(e),$("#modelvideodiv_"+t).removeClass("d-none")):(l.next(".custom-file-label").html($("#choosefile").text()),$.alert({title:$("#error").text(),content:$("#uploadfailed").text(),buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}}))})}),$("#modelvideodel_"+t).on("click",function(){var l=$(this);$(this).children("div:last").removeClass("d-none"),$.post("uploadfiledel",{filename:$("#modelvideoup_"+t).val()},function(e){l.children("div:last").addClass("d-none"),"ok"==e?($("#modelvideoup_"+t).val(""),$("#modelvideodiv_"+t).addClass("d-none"),$("#modelvideo_"+t).next(".custom-file-label").html($("#choosefile").text())):$.alert({title:$("#error").text(),content:e,buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}})})})}),$.each(v,function(e,t){""!=$("#modelfileup_"+t).val()&&$("#modelfilediv_"+t).removeClass("d-none"),$("#modelfile_"+t).on("change",function(){var e=$(this).val(),l=$(this);$(this).next(".custom-file-label").html(e),$(this).parent().next("small").removeClass("d-none"),$("#modelfile_"+t).upload("uploadfile",{allow:"doc,docx,xls,xlsx,ppt,htm,html,txt,zip,rar,gz,bz2,pdf"},function(e){l.parent().next("small").addClass("d-none"),""!=e?($("#modelfileup_"+t).val(e),$("#modelfilediv_"+t).removeClass("d-none")):(l.next(".custom-file-label").html($("#choosefile").text()),$.alert({title:$("#error").text(),content:$("#uploadfailed").text(),buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}}))})}),$("#modelfiledel_"+t).on("click",function(){var l=$(this);$(this).children("div:last").removeClass("d-none"),$.post("uploadfiledel",{filename:$("#modelfileup_"+t).val()},function(e){l.children("div:last").addClass("d-none"),"ok"==e?($("#modelfileup_"+t).val(""),$("#modelfilediv_"+t).addClass("d-none"),$("#modelfile_"+t).next(".custom-file-label").html($("#choosefile").text())):$.alert({title:$("#error").text(),content:e,buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}})})})}),$.each(f,function(e,o){var d="";$("#multiimagefile_"+o).on("change",function(){""==d&&(d=$(this).next(".custom-file-label").html());var e=$(this).val(),a=$(this);$(this).next(".custom-file-label").html(e),$("#multiimagefile_"+o).upload("uploadfile",{allow:"jpg,gif,png,jpeg,webp"},function(e){var l,t;a.next(".custom-file-label").html(d),""!=e?(""==(l=$("#multiimage_"+o).val())?l=e:l+=","+e,$("#multiimage_"+o).val(l),t=$("#multiimagediv").html().replace('src=""','src="'+$("#domain").text()+e+'"').replace('data-img=""','data-img="'+e+'"').replace('class="multiimagedel multiimagedel_"','class="multiimagedel multiimagedel_'+o+'"'),$("#multiimagediv_"+o).append(t),$('[data-toggle="popover"]').popover("dispose").popover()):$.alert({title:$("#error").text(),content:$("#uploadfailed").text(),buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}})})}),$("#multiimagediv_"+o).on("click",".multiimagedel_"+o,function(){var a=$(this),i=$(this).data("img");$(this).children("div:last").removeClass("d-none"),$.post("uploadfiledel",{filename:i},function(e){var l,t;a.children("div:last").addClass("d-none"),"ok"==e?(l=$("#multiimage_"+o).val().split(","),t="",$.each(l,function(e,l){l!=i&&(""==t?t=l:t+=","+l)}),$("#multiimage_"+o).val(t),a.parent().remove(),$("#multiimagefile_"+o).next(".custom-file-label").html(d)):$.alert({title:$("#error").text(),content:e,buttons:{confirm:{text:$("#ok").text(),btnClass:"btn-info",keys:["enter"]}}})})})})),$("#submit").on("click tap",function(){e.sync(),""==$("#summary").val()&&(500<e.getText().length?$("#summary").val(e.getText().replace(/\n/g," ").substr(0,500)+"..."):$("#summary").val(e.getText().replace(/\n/g," ")))}),$('[data-toggle="popover"]').popover()});
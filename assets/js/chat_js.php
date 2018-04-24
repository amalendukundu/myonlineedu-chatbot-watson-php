<script>
var rootDirectory = "/demo/chatbot/";
var localDirectory = "/demo/chatbot/app/ajax";

//var rootDirectory = "/";
//var localDirectory = "/app/ajax";

$(document).ajaxStop(function(){
   //console.debug("ajaxStop");
   $("#ajax_loader").hide();
});
$(document).ajaxStart(function(){
    //console.debug("ajaxStart");
    $("#ajax_loader").show();
});

$(document).ready(function() {  
   
   //Click on the chat send button
   $('#ebsBtnSend').click(function(){
      var v_chat_text = $('#chatInput').val();
      
      if(v_chat_text.length > 0)
         sendChatText(v_chat_text, true);
		
      $('#chatInput').val("");
	});
   
   //When 'enter' is pressed while typing in chat text box
   $('#chatInput').on('keypress', function (e) {
      if(e.which === 13)
      {
         var v_chat_text = $('#chatInput').val();
      
         if(v_chat_text.length > 0)
            sendChatText(v_chat_text, true);
		
         $('#chatInput').val("");
      }
   });
   
   //Click on special menu button created by bot
   $("div.chat_area").on("click", "button.bot-special-btn", function(){
      var button_text = $(this).val();
      //alert(button_text);
      
      //Remove the special button area
      //$(this).closest('li.special-btn-area').remove();
      
      sendChatText(button_text, true);
   });

   startChat();
   
});

function startChat()
{
	<?php if(isset($start_chat_automatic) && $start_chat_automatic == true):?>
      sendChatText('Hi', false);
   <?php endif; ?>
   
   setInterval(function(){ makeImageResponsive(); }, 4000);
}

function makeImageResponsive()
{
   //Make all the image responsive
   $('img').filter(function(){
      return !$(this).hasClass('img-responsive');
   }).addClass('img-responsive');
}

function printChatText(p_user_type, p_user_name, p_chat_text_obj)
{
	
   //console.log(p_chat_text_obj);
   
   var html = "";
   
   var jsonData = JSON.parse(p_chat_text_obj);
   
   //To print the messages in chat area
   if (typeof jsonData.result_texts == 'object') 
   {
      var jsonLength = jsonData.result_texts.length;
      //console.log('Result text jsonLength = ' + jsonLength);
      
      for (var i = 0; i < jsonLength; i++) 
      {
         var result = jsonData.result_texts[i];
         var v_li_class = "admin_chat";
         var v_pull_type = "pull-right";
         var v_user_name_style = "color:rgb(107,203,239); display:block;";
         var v_avatar_image_src = rootDirectory + "assets/images/user_avatar.jpg";;
         
         if(p_user_type == 'ROBOT')
         {
            v_li_class = "partner_chat";
            v_pull_type = "pull-left";
            v_user_name_style = "color:rgb(207,103,239); display:block;";
            v_avatar_image_src = rootDirectory + "assets/images/watson_avatar.jpg";
         }
         
         var d = new Date();
         //var time = d.getDate() + '-' + d.getMonth() + '-' + d.getFullYear() + ' ' + d.toLocaleTimeString();
         var time = d.toLocaleTimeString();
    
         html += '<li class="left clearfix ' + v_li_class +'">' + 
                     '<span class="chat-img1 ' + v_pull_type +'">' +
                        '<img src="'+ v_avatar_image_src +'" alt="User Avatar" class="img-circle">' +
                     '</span>' +
                     '<div class="chat-body1 clearfix">' +
                        '<div class="chat-message1">' +
                           '<span style="'+ v_user_name_style +'">' + p_user_name +'</span>' +
                           '<div style="display:block; padding:5px 0px 5px 0px; color:#'+ result.color +';">' + result.text + '</div>' +
                           '<span style="font-size:0.85em; color:grey; display:block; float:right;">'+ time +'</span>' +
                        '</div>' +
                     '</div>' +
                  '</li>'
                  ;
      }
   }
   
   //To print the special buttons in chat area
   if (typeof jsonData.bot_special_action_btn == 'object') 
   {
      var jsonActionBtnLength = jsonData.bot_special_action_btn.length;
      //console.log('Action Button jsonActionBtnLength = ' + jsonActionBtnLength);
      
      if(jsonActionBtnLength > 0)
      {
         html += '<li class="left clearfix special-btn-area">' + 
                        '<div class="chat-body1 clearfix">' +
                           '<div class="">'
                     ;
                     
         for (var i = 0; i < jsonActionBtnLength; i++) 
         {
            var button_text = jsonData.bot_special_action_btn[i];
            html += '<button type="button" class="btn btn-info bot-special-btn" value="' + button_text +'">' + button_text + '</button>';
            //alert(jsonData.bot_special_action_btn[i]);
         }
         
         html +=           '</div>' +
                        '</div>' +
                     '</li>'
                     ;
      }
   }
   
   $('ul.list-unstyled').append(html);
   
   
   //To print document chunks from COG3 in the right side bar
   if (typeof jsonData.cog_documents == 'object')
   {
      var jsonCogDocumentLength = jsonData.cog_documents.length;
      //console.log('Cog Document jsonCogDocumentLength = ' + jsonCogDocumentLength);
      
      var v_html = "<h3>Relevent documents</h3>";
      
      if(jsonCogDocumentLength > 0)
      {
         for (var i = 0; i < jsonCogDocumentLength; i++)
         {
            var doc_result = jsonData.cog_documents[i];
            
            v_html += '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">' +
                      '<div class="panel panel-default">' +
                        '<div class="panel-heading" role="tab" id="headingOne">' +
                           '<h4 class="panel-title">' +
                              '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse' + i +'" aria-expanded="true" aria-controls="collapse' + i + '">' + doc_result.file_name + '</a>' +
                           '</h4>' +
                        '</div>' +
                        '<div id="collapse' + i +'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading' + i + '">' +
                           '<div class="panel-body">' + doc_result.file_hifi_text + '</div>' +
                        '</div>' +
                      '</div>' +
                    '</div>'
                     ;
         }
         
         $('div#cog_documents').html(v_html);
      }
      else
      {
         $('div#cog_documents').html('');
      }
   }
   else
   {
      $('div#cog_documents').html('');
   }
   
   //Scroll down the div for chat area
   var div = $("div.chat_area");
   div.scrollTop(div.prop('scrollHeight'));
}

function sendChatText(p_chat_text, p_display_text)
{
	//Remove the special button area
   $('li.special-btn-area').remove();
      
   var chatInput = p_chat_text;
	//alert ('Inside sendChatText chatInput= ' + chatInput);
   
   if(p_display_text == true)
   {
      //First call the printChatText to print user's input
      var v_user_name = '<?php echo $_SESSION['firstName']; ?>';
      var v_user_input_obj = '{"result_texts":[{"text":"' + chatInput +'","color":"000000"}]}';
      
      printChatText('USER', v_user_name, v_user_input_obj);
   }
   
   if(chatInput != ""){
		$.ajax({
			type: "GET",
			url: localDirectory + "/submit.php?chattext=" + encodeURIComponent( chatInput ),
         success: function( data )
         {
            //console.log(data);
            printChatText('ROBOT', 'DemoBot', data);
         }
		});
	}
}

</script>
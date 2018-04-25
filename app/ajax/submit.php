<?php
	session_start();
	require_once("class/chatClass.php");
	
   if(isset($_GET['chattext']))
   {
      $chat_text = strip_tags($_GET['chattext'] );
      
      $chatObj = new chatClass();
      $chatObj->setWatsonConfig();
      
      (isset($_SESSION['watson_api_context'])) ? $watson_api_context = $_SESSION['watson_api_context'] : $watson_api_context = null;
      //error_log('2.' . print_r($watson_api_context, TRUE));
      $watson_return_array = $chatObj->processChatInput($chat_text, $watson_api_context);
      
      $_SESSION['watson_api_context'] = $watson_return_array['watson_api_context'];
      unset($watson_return_array['watson_api_context']);
      
      $watson_return_json = json_encode($watson_return_array);
      
      print $watson_return_json;
   }
?>
<?php
if (!function_exists('getallheaders')) {
    function getallheaders() {
       $headers = [];
       foreach ($_SERVER as $name => $value) {
           if (substr($name, 0, 5) == 'HTTP_') {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
       }
       return $headers;
    }
}

require_once("../ajax/class/chatClass.php");

$v_log_file = 'C:/wamp/www/bluemix/oraclechatbot/log/app.log';
error_log("\nMS Teams Oracle eBS Bot service start\n", 3, $v_log_file);

$msteam_app_name = "OracleEBSBot";
$msteam_app_id = "58c3b2e1-a2f7-462d-b9c2-40e5d4a45b72";
$msteam_app_password = "nNKMRSW1[~=rqzscdA1367(";

 
//Get data from request
//error_log("MS Teams Input:\n", 3, $v_log_file);
$input_strting = file_get_contents('php://input');
$input = json_decode($input_strting, true);
//error_log("JSON Format:" . $input_strting . "\n", 3, $v_log_file);
//error_log(print_r($input, TRUE), 3, $v_log_file);

if($input['type'] == 'message')
{
   error_log("Found input type message...\n", 3, $v_log_file);
   
   $conversation_id = $input['conversation']['id'];
   $activity_id = $input['id'];
   $callback_url = $input['serviceUrl'];
   $sender_id = 'MSTEAMS-' . $input['from']['id'];
      
   $chatObj = new chatClass(true);
   $chatObj->setWatsonConfig("8ec62dfd-1e1a-4d06-93bf-b9bc93c6f520");
   $session_array = $chatObj->getDBSessions($sender_id);
   //error_log("Session Array after getDBSessions for sender id:" . $sender_id . "\n", 3, $v_log_file);
   //error_log(print_r($session_array, TRUE), 3, $v_log_file);
   
   (!empty($session_array['msteams_session_expiary'])) ? $session_expiary = $session_array['msteams_session_expiary'] : $session_expiary = time();
   //error_log(print_r($session_array, TRUE));
   
   //Get the access token
   if($session_expiary > time())
   {
      $msteam_token = $session_array['msteams_bot_access_tokens'];
      error_log("No need to call MS bot TOKEN API. Got the token from session.\n", 3, $v_log_file);
   }
   else
   {
      $api_url = "https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token";
      $api_post_data = array(
                               'grant_type' => 'client_credentials'
                              ,'client_id'  => $msteam_app_id
                              ,'client_secret' => $msteam_app_password
                              ,'scope' => 'https://api.botframework.com/.default'
                            );
                            
      $http_header = array();
      $http_header[] = 'Content-Type: application/x-www-form-urlencoded';
      
      $api_response = call_ms_bot_api($api_url, http_build_query($api_post_data), $http_header);
      $api_response = json_decode($api_response, true);
      error_log("After call of MS Bot TOKEN API:\n", 3, $v_log_file);
      //error_log(print_r($api_response, TRUE), 3, $v_log_file);
      
      if(isset($api_response['access_token']))
      {
         $msteam_token = $api_response['access_token'];
         
         $session_array = array();
         $session_array['msteams_bot_access_tokens'] = $api_response['access_token'];
         $session_array['msteams_session_expiary'] = time() + $api_response['expires_in'];
         $session_array['watson_api_context'] = null;
      }
   }
   
   //Send the response back to MS Bot
   if(!empty($msteam_token))
   {
      $input_text = $input['text'];
      $input_conversation = $input['conversation'];
      $input_from = $input['from'];
      $input_recipient = $input['recipient'];
      
      //Call watson
      $watson_return_array = $chatObj->getMessengerGenericEBSReply($sender_id, $input_text, $session_array, @$session_array['msteams_session_expiary']);
      //error_log("After Watson API call:\n", 3, $v_log_file);
      //error_log(print_r($watson_return_array, TRUE), 3, $v_log_file);
      
      //set the output array
      $output['type'] = 'message';
      $output['replyToId'] = $activity_id;
      $output['text'] = '';
      if(isset($watson_return_array['result_texts']))
      {
         foreach($watson_return_array['result_texts'] as $watson_result)
         {
            $output['text'] .= $watson_result['text'] . "\n\n  \n\n";
         }
      }
      $output['conversation'] = $input_conversation;
      $output['from'] = $input_recipient;
      $output['recipient'] = $input_from;
      
      //Attachment card to display the special action buttons
      if(isset($watson_return_array['bot_special_action_btn']) && count($watson_return_array['bot_special_action_btn']) > 0)
      {
         $output['attachmentLayout'] = 'list';
         $output['attachments'] = array();
         $output['attachments'][0]['contentType'] = 'application/vnd.microsoft.card.thumbnail';
         $output['attachments'][0]['content']['buttons'] = array();
         foreach($watson_return_array['bot_special_action_btn'] as $special_button)
            $output['attachments'][0]['content']['buttons'][] = array('type' => 'imBack', 'title'=> $special_button, 'value' => $special_button);
      }
      //error_log("Before call of the RESPONSE API:\n", 3, $v_log_file);
      //error_log("JSON Format:" . json_encode($output) . "\n", 3, $v_log_file);


      //Call API to send the data back to the MS BOT service
      $api_url = $callback_url . "v3/conversations/$conversation_id/activities/$activity_id";
      $http_header = array();
      $http_header[] = 'Content-Type: application/json';
      $http_header[] = 'Authorization: Bearer ' . $msteam_token;
      $api_response = call_ms_bot_api($api_url, json_encode($output), $http_header);
      //error_log("After call of MS Bot SEND RESPONSE API: $api_url \n", 3, $v_log_file);
      //error_log(print_r($api_response, TRUE), 3, $v_log_file);
   }
   error_log("--------------------------------------------------------------------\n", 3, $v_log_file);
}

// Function to call Microsoft Bot REST API
function call_ms_bot_api($url, $post_fields, $http_header = array('Content-Type: application/json'))
{
   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
   curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   // receive server response ...
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

   
   //error_log("Calling CURL to call MS Bot API:\n", 3, $GLOBALS['v_log_file']);
   $api_result = curl_exec($ch);
   
   if($errno = curl_errno($ch))
      error_log("CURL error: " . $errno . " - " . curl_strerror($errno), 3, $GLOBALS['v_log_file']);
   else
      error_log("CURL call success\n", 3, $GLOBALS['v_log_file']);

   curl_close($ch);
   
   return $api_result;
}
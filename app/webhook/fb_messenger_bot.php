<?php
require_once("../ajax/class/config.php");
require_once("../ajax/class/chatClass.php");

$v_log_file = 'C:/wamp/www/demo/chatbot/log/app.log';
error_log("\nV2- Facebook MyOnlineedu Chat Hook start\n", 3, $v_log_file);
//error_log(print_r($_REQUEST, TRUE), 3, $v_log_file);
//die();

// parameters
$hubVerifyToken = 'your_app_token';
$accessToken = "your_app_access_token";

// check token at setup
if (isset($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $hubVerifyToken) 
{
  error_log("HUB Challenge" . $_REQUEST['hub_challenge'] . "\n", 3, $v_log_file);
  echo $_REQUEST['hub_challenge'];
  exit;
}

// handle FB bot's anwser
$input = json_decode(file_get_contents('php://input'), true);
//error_log("Facebook Input:\n", 3, $v_log_file);
//error_log(print_r($input, TRUE), 3, $v_log_file);

$receipientId = isset($input['entry'][0]['messaging'][0]['recipient']['id']) ? $input['entry'][0]['messaging'][0]['recipient']['id'] : -1;
$senderId = isset($input['entry'][0]['messaging'][0]['sender']['id']) ? $input['entry'][0]['messaging'][0]['sender']['id'] : -1;
$messageInputText = isset($input['entry'][0]['messaging'][0]['message']['text']) ? $input['entry'][0]['messaging'][0]['message']['text'] : "Hi";

//Send sender action to Facebook Messenger - typing on
$response = array();
$response['recipient']['id'] = $senderId;
$response['sender_action'] = 'typing_on';
$api_url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken;
$http_header = array();
$http_header[] = 'Content-Type: application/json';
$api_response = call_facebook_messenger_api($api_url, json_encode($response), $http_header);

//get the answer from Watson
$v_db_session_id = 'FB-' . $senderId . '-' . $receipientId;

$chatObj = new chatClass(true);
$chatObj->setWatsonConfig(CONFIG_WATSON_WORKSPACE_ID);
$session_array = $chatObj->getDBSessions($v_db_session_id);
$watson_return_array = $chatObj->getWebHookReply($v_db_session_id, $messageInputText, $session_array);
error_log("After Watson API call:\n", 3, $v_log_file);
error_log(print_r($watson_return_array, TRUE), 3, $v_log_file);

$answer = '';

if(isset($watson_return_array['result_texts']))
{
   foreach($watson_return_array['result_texts'] as $watson_result)
   {
      $answer .= substr(strip_tags($watson_result['text']), 0, 1700) . "\r\n \r\n";
   }
}

//send Watson response message to Facebook bot
$response = array();
$response['recipient']['id'] = $senderId;
$response['message']['text'] = substr($answer, 0, 1999);

//Attachment quick reply buttons for the special action buttons
if(isset($watson_return_array['bot_special_action_btn']) && count($watson_return_array['bot_special_action_btn']) > 0)
{
   $response['message']['quick_replies'] = array();
   foreach($watson_return_array['bot_special_action_btn'] as $special_button)
      $response['message']['quick_replies'][] = array('content_type' => 'text', 'title'=> strip_tags($special_button), 'payload' => 'EBS_BOT_CUSTOM_PAYLOAD');
}
      
error_log("Before call of the Facebook Message RESPONSE API:\n", 3, $v_log_file);
error_log("JSON Format: " . json_encode($response) . "\n", 3, $v_log_file);

//Call API to send the data back to the Facebook Messenger service
$api_url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken;
$http_header = array();
$http_header[] = 'Content-Type: application/json';
$api_response = call_facebook_messenger_api($api_url, json_encode($response), $http_header);
//error_log("After call of Facebook Bot SEND RESPONSE API: $api_url \n", 3, $v_log_file);
//error_log(print_r($api_response, TRUE), 3, $v_log_file);

error_log("--------------------------------------------------------------------\n", 3, $v_log_file);

// Function to call Facebook Messenger REST API
function call_facebook_messenger_api($url, $post_fields, $http_header = array('Content-Type: application/json'))
{
   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
   curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   // receive server response ...
   //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

   
   //error_log("Calling CURL to call MS Bot API:\n", 3, $GLOBALS['v_log_file']);
   $api_result = curl_exec($ch);
   
   if($errno = curl_errno($ch))
      error_log("CURL error: " . $errno . " - " . curl_strerror($errno), 3, $GLOBALS['v_log_file']);
   else
      error_log("CURL call success\n", 3, $GLOBALS['v_log_file']);

   curl_close($ch);
   
   return $api_result;
}
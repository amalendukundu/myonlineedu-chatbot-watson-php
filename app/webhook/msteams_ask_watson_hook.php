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

require_once("../ajax/class/cogAssist.php");

$v_log_file = 'C:/wamp/www/bluemix/oraclechatbot/log/app.log';
error_log("\nMS Teams Watson COG3 service start\n", 3, $v_log_file);
//error_log(print_r($_SERVER, TRUE), 3, $v_log_file);
//die();

$msteam_app_name = "AskWatson";
$msteam_token = "zBT0rkqB1K71BCdmLJ3RHDFnlGpS3pWWaxMnN30DcHI=";

$response_array = array('type' => 'message', 'text' => 'Sorry! Could not authorize the request.');

//Authorize the header to check if the incoming request is from MS Teams only
//get headers
$request_headers = getallheaders();
//error_log("Request Headers:\n", 3, $v_log_file);
//error_log(print_r($request_headers, TRUE), 3, $v_log_file);

if(isset($request_headers['Authorization']))
   $provided_hmac = $request_headers['Authorization'];
else
   $provided_hmac = "";

//error_log("provided_hmac = " . $provided_hmac . "\n", 3, $v_log_file);
   
//Get data from request
$input_strting = file_get_contents('php://input');
$input = json_decode($input_strting, true);
error_log("MS Teams Input:\n", 3, $v_log_file);
error_log(print_r($input, TRUE), 3, $v_log_file);

//hashing
$hash = hash_hmac("sha256", $input_strting, base64_decode($msteam_token), true);
$calculated_hmac = "HMAC " . base64_encode($hash);

//error_log("calculated_hmac = " . $calculated_hmac . "\n", 3, $v_log_file);

if(hash_equals($provided_hmac,$calculated_hmac))
{
   // handle MS Teams bot's anwser
   $question_for_cog = trim(str_replace($msteam_app_name, "", strip_tags($input['text'])));
   error_log("Retrieved question: " . $question_for_cog . "\n", 3, $v_log_file);

   $cogObj = new cogAssist();
   $solution_msg = $cogObj->get_cognitive_answer($question_for_cog, false);
   error_log("Retrieved solution: " . $solution_msg . "\n", 3, $v_log_file);

   $response_array = array('type' => 'message', 'text' => $solution_msg);
}
else
   error_log("Inbound HMAC code did not match.\n", 3, $v_log_file);

echo json_encode($response_array);
exit;
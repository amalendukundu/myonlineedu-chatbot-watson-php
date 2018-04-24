<?php

$v_log_file = 'prod_app.log';
error_log("\nMS Teams Watson COG3 service start\n", 3, $v_log_file);
//error_log(print_r($_SERVER, TRUE), 3, $v_log_file);
//die();



$response_array = array('type' => 'message', 'text' => 'Sorry! Could not authorize the request.');

echo json_encode($response_array);
exit;
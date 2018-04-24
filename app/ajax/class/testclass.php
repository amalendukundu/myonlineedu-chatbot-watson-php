<?php
  session_start();
  
  include_once('reservationClass.php');
  
  $reservationObj = new Reservation();
  $return_array = $reservationObj->createReservation('UPDATE', array('id'=>3, 'r_date' => '2018-04-28', 'r_time' => '21:00:00', 'r_status'=>'CENCELLED'));
  print_r ($return_array);
  die();
  
  
  include_once('chatClass.php');
  
  $chatObj = new chatClass();
  $return_str = $chatObj->processOracleEBSChatInput('Hi');
  
  echo '<pre>';
  var_dump($return_str);
  echo '</pre>';
  
  //echo '<pre>';
  //var_dump($_SESSION);
  //echo '</pre>';
?>
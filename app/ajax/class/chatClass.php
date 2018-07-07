<?php
  include_once('config.php');
  include_once('reservationClass.php');
  
  class chatClass
  {
    const C_COLOR_NORMAL = '000000';
    const C_COLOR_GREEN = '356825';
    const C_COLOR_RED = 'f50808';
    
    private $_db;
    private $_session_id;
    
    private $_watson_username;
    private $_watson_password;
    private $_watson_waorkspace_id;
    
    public function __construct($conect_to_db = true)
    {
      $this->_session_id = session_id();
      
      $this->setWatsonConfig();
      
      if($conect_to_db || 1==1)
      {
         $this->_db = new mysqli( mysqlServer, mysqlUser, mysqlPass, mysqlDB);
         if ($this->_db->connect_errno) 
         {
             echo "Failed to connect to MySQL: (" . $this->_db->connect_errno . ") " . $this->_db->connect_error;
             die();
         }
      }
    }
    
    /* */
    public function setWatsonConfig($p_watson_waorkspace_id = null)
    {
      (empty($p_watson_waorkspace_id)) ? $this->_watson_waorkspace_id = CONFIG_WATSON_WORKSPACE_ID : $this->_watson_waorkspace_id = $p_watson_waorkspace_id;
      $this->_watson_username = CONFIG_WATSON_USERNAME;
      $this->_watson_password = CONFIG_WATSON_PASSWORD;
    }
    
    /*  */
    public function __destruct()
    {
      $this->_db->close();
    }
    
    
    /* 
      This function is called from AJAX submit.php. This function process the user input and act accordingly
    */
    public function processChatInput($p_chat_text, $p_watson_api_context)
    {
      if(!empty($p_watson_api_context))
         $watson_current_context = $p_watson_api_context;
      else
         $watson_current_context = null;
      

      //error_log('1.' . print_r($watson_current_context, TRUE));
      
      //Send the user input to watson for a response
      $watson_reply_msg = $this->getWatsonResponse($p_chat_text, $watson_current_context);
      
      //echo '<pre>';
      //var_dump($watson_reply_msg);
      //echo '</pre>';
      
      foreach($watson_reply_msg['watson_reply_text'] as $key => $watson_msg)
      {
         $return_msg['result_texts'][] = array('text'=>$watson_msg, 'color'=>chatClass::C_COLOR_NORMAL);
      }
      
      //Check if there any context set to take some action
      if(isset($watson_reply_msg['watson_api_context']))
      {
         $watson_current_context = $watson_reply_msg['watson_api_context'];
         
         if(isset($watson_current_context->special_menu))
         {
            $return_msg['bot_special_action_btn'] = $watson_current_context->special_menu;
            $watson_current_context->special_menu = array();
         }
         
         //Action to MAKE reservation
         if(isset($watson_current_context->action) && strtoupper($watson_current_context->action) == 'ACTION_MAKE_RESERVATION' && isset($watson_current_context->i_date))
         {
            $i_date = empty($watson_current_context->i_date) ? null : trim($watson_current_context->i_date);
            $i_time = empty($watson_current_context->i_time) ? null : trim($watson_current_context->i_time);
            $i_num_of_ppl = empty($watson_current_context->i_num_of_ppl) ? null : trim($watson_current_context->i_num_of_ppl);
            $i_city = empty($watson_current_context->i_city) ? null : trim($watson_current_context->i_city);
            $i_name = empty($watson_current_context->i_name) ? null : trim($watson_current_context->i_name);
            
            $reservationObj = new Reservation();
            $return_array = $reservationObj->createReservation('CREATE', array('r_date' => $i_date, 'r_time' => $i_time, 'r_num_of_people'=> $i_num_of_ppl, 'r_city'=>$i_city, 'r_person_name'=>$i_name, 'r_status'=>'CONFIRMED'));
            
            //Remove the input parameters from context
            if(isset($watson_current_context->i_date)) 
               unset($watson_current_context->i_date);
            if(isset($watson_current_context->i_time)) 
               unset($watson_current_context->i_time);
            if(isset($watson_current_context->i_num_of_ppl)) 
               unset($watson_current_context->i_num_of_ppl);
            if(isset($watson_current_context->i_city)) 
               unset($watson_current_context->i_city);
            if(isset($watson_current_context->i_name)) 
               unset($watson_current_context->i_name);
         }
         
         //Action to CANCEL reservation
         if(isset($watson_current_context->action) && strtoupper($watson_current_context->action) == 'ACTION_CANCEL_RESERVATION' && isset($watson_current_context->i_reservation_number))
         {
            $i_reservation_number = empty($watson_current_context->i_reservation_number) ? null : trim($watson_current_context->i_reservation_number);
            
            $reservationObj = new Reservation();
            $reservation_data = $reservationObj->getReservation($i_reservation_number);
            
            if(isset($reservation_data['r_status']) && $reservation_data['r_status'] != 'CANCELLED')
            {
               $return_array = $reservationObj->createReservation('UPDATE', array('id'=>$reservation_data['id'],'r_date' => $reservation_data['r_date'], 'r_time' => $reservation_data['r_time'], 'r_status'=>'CANCELLED'));
               
               if(isset($return_array['status']) && $return_array['status'] == 'S')
               {
                  $return_array['status'] = 'S';
                  $return_array['message'] = 'Your request to cancel reservation number <b>' . $reservation_data['id'] . '</b> is taken care.';
               }
               else
               {
                  $return_array['status'] = 'E';
                  $return_array['message'] = 'Sorry. Some system error happened while cancelling reservation number <b>' . $reservation_data['id'] . '</b>.';
               }
            }
            else
            {
               $return_array['status'] = 'E';
               $return_array['message'] = 'Sorry, either the entered reservation number is not found or it is already cancelled.';
            }
            
            //Remove the input parameters from context
            if(isset($watson_current_context->i_reservation_number)) 
               unset($watson_current_context->i_reservation_number);
         }
         
         //Action to RESCHEDULE reservation
         if(isset($watson_current_context->action) && strtoupper($watson_current_context->action) == 'ACTION_RESCHEDULE_RESERVATION' && isset($watson_current_context->i_reservation_number))
         {
            $i_reservation_number = empty($watson_current_context->i_reservation_number) ? null : trim($watson_current_context->i_reservation_number);
            $i_date = empty($watson_current_context->i_date) ? null : trim($watson_current_context->i_date);
            $i_time = empty($watson_current_context->i_time) ? null : trim($watson_current_context->i_time);
            
            $reservationObj = new Reservation();
            $reservation_data = $reservationObj->getReservation($i_reservation_number);
            
            if(isset($reservation_data['r_status']) && $reservation_data['r_status'] != 'CANCELLED')
            {
               $return_array = $reservationObj->createReservation('UPDATE', array('id'=>$reservation_data['id'],'r_date' => $i_date, 'r_time' => $i_time, 'r_status'=>'RESCHEDULED'));
               
               if(isset($return_array['status']) && $return_array['status'] == 'S')
               {
                  $return_array['status'] = 'S';
                  $return_array['message'] = 'Your reservation number <b>' . $reservation_data['id'] . '</b> is updated with date: ' . $i_date . ' time: ' . $i_time;
               }
               else
               {
                  $return_array['status'] = 'E';
                  $return_array['message'] = 'Sorry. Some system error happened while rescheduling reservation number <b>' . $reservation_data['id'] . '</b>.';
               }
            }
            else
            {
               $return_array['status'] = 'E';
               $return_array['message'] = 'Sorry, either the entered reservation number is not found or it is already cancelled.';
            }
            
            //Remove the input parameters from context
            if(isset($watson_current_context->i_reservation_number)) 
               unset($watson_current_context->i_reservation_number);
            if(isset($watson_current_context->i_date)) 
               unset($watson_current_context->i_date);
            if(isset($watson_current_context->i_time)) 
               unset($watson_current_context->i_time);
         }
         
         if(isset($return_array) && isset($return_array['status']) && isset($return_array['message']))
         {
            if($return_array['status'] == 'S')
               $text_color = chatClass::C_COLOR_GREEN;
            else
               $text_color = chatClass::C_COLOR_RED;
            
            $return_msg['result_texts'][] = array('text'=> $return_array['message'], 'color'=> $text_color);
         }
      }
      
      $return_msg['watson_api_context'] = $watson_current_context;
      
      return $return_msg;
    }
    
    
    /* 
      This function is called from FaceBook messenger webhook for Oracle eBS context. This function process the user input and act accordingly
    */
    public function getWebHookReply($p_sender_id, $p_chat_text, $session_array, $p_session_expiary = null)
    {
      //Send the user input to watson for a response and save the response to DB
      (isset($session_array['watson_api_context'])) ? $watson_api_context = $session_array['watson_api_context'] : $watson_api_context = null;
      $watson_return_array = $this->processChatInput($p_chat_text, $watson_api_context);
      
      //$session_array->watson_api_context = $watson_return_array['watson_api_context'];
      $session_array['watson_api_context'] = $watson_return_array['watson_api_context'];
      unset($watson_return_array['watson_api_context']);
      
      $this->processDBSessions($p_sender_id, $session_array, $p_session_expiary);
      
      return $watson_return_array;
    }
    
    
    /* */
    private function getWatsonResponse($p_input_text, $p_watson_context)
    {
      $watson_username = $this->_watson_username;
      $watson_password = $this->_watson_password;
      $watson_waorkspace_id = $this->_watson_waorkspace_id;
      
      $api_url = "https://gateway.watsonplatform.net/conversation/api/v1/workspaces/" . $watson_waorkspace_id . "/message?version=2017-05-26";
      
      if(!empty($p_input_text))
         $api_request_array['input']['text'] = $p_input_text;
      else
         $api_request_array['input'] = null;
      
      if(!empty($p_watson_context))
         $api_request_array['context'] = $p_watson_context;
      
      $json_api_request = json_encode($api_request_array);
      
      $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_USERPWD, "$watson_username:$watson_password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_api_request);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
      
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      
      $response = curl_exec($ch);
      
		if($errno = curl_errno($ch))
		{
			//echo 'Error: CURL error: ' . curl_error($ch);	//HTTP ERROR
         $return_msg = 'Error connecting with Watson API. Error:(' . $errno . ') - ' . curl_strerror($errno);
		}
		else
		{
         $api_response = json_decode($response);
         
         if(isset($api_response->output->text[0]))
            $watson_output_text = $api_response->output->text;
         else
            $watson_output_text[] = "Sorry! Watson service could not reply";
         
         //store the context in session which will be used before calling the api
         if(isset($api_response->context))
         {
            $return_msg['watson_api_context'] = $api_response->context;
         }
         
         $return_msg['watson_reply_text'] = $watson_output_text;
      }
      
      return $return_msg;
    }
    
    /* */
    private function oracleEbsApi($p_api_url, $p_parameters = array())
    {
      $api_url = $p_api_url . http_build_query($p_parameters);
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $api_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
   
      $response = curl_exec($ch);
      
      if($errno = curl_errno($ch))
      {
         $return_msg = 'Error connecting with OracleEbs API. Error:(' . $errno . ') - ' . curl_strerror($errno);
      }
      else
      {
         /*echo 'Curl Success';
         echo '<br>';
         echo '<pre>'; var_dump($response); echo '</pre>';
         echo '<br>';
         */
         
         $return_msg = json_decode($response);
      }
      
      return $return_msg;
    }
    
    /* */
    private function processDBSessions($p_session_id, $p_session_array, $p_session_expiary = null) 
    {
      //error_log('In processDBSessions function with input sessionID=' . $p_session_id);
      
      if(empty($p_session_expiary))
         $p_session_expiary = time() + 3600;
      
      $session_data = $this->getDBSessions($p_session_id);
      
      $p_json_session_data = json_encode($p_session_array);
      
      if(empty($session_data))
      {
         //error_log('Inserting into DB session id=' . $p_session_id);
         $statement = $this->_db->prepare("INSERT INTO db_sessions(session_id, session_json_data, session_expiary) VALUES(?, ?, ?)");
         $statement->bind_param('ssi', $p_session_id, $p_json_session_data, $p_session_expiary);
         $statement->execute();
         $statement->close();
         //error_log('Insert successful');
      }
      else
      {
         //error_log('Updating DB session id=' . $p_session_id);
         $statement = $this->_db->prepare("UPDATE db_sessions SET session_json_data = '" . $p_json_session_data . "', session_expiary = " . $p_session_expiary . " WHERE session_id = ?" );
         $statement->bind_param('s', $p_session_id);
         $statement->execute();
         $statement->close();
         //error_log('Update successful');
      }
      
      return true;
    }
    
    /* */
    public function getDBSessions($p_session_id) 
    {
      //error_log('In getDBSessions function with input session id=' . $p_session_id);
      
      $statement = $this->_db->prepare("SELECT session_id, session_json_data, session_expiary FROM db_sessions WHERE session_id = ?");
      $statement->bind_param('s', $p_session_id);
      $statement->execute();
      $statement->bind_result($session_id, $session_json_data, $session_expiary);
      $statement->fetch();
      $statement->close();
      
      //error_log('Got from DB session_id=' . $session_id . ' and session_json_data=' . $session_json_data);
      
      $return_array = array();
      
      if(!empty($session_id))
      {
         if($session_expiary > time())
         {
            //error_log('DB session is not expired yet. Sending the session data from DB:');
            $return_array = json_decode($session_json_data, true);
            //error_log(print_r($return_array, TRUE));
         }
         else
         {
            //error_log('DB session is expired. Deleting it');
            $statement = $this->_db->prepare("DELETE FROM db_sessions WHERE session_id = ?");
            $statement->bind_param('s', $p_session_id);
            $statement->execute();
            $statement->close();
            //error_log('DB session deleted successfully');
         }
      }
      //else
         //error_log('Could not find any session');
      
      return $return_array;
    }
  }
?>
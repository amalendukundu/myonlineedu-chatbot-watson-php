<?php    
   $relative_path = '../';
   include_once ($relative_path . 'global.php');
   
   if(isset($_SESSION['watson_api_context']))
   {
      unset($_SESSION['watson_api_context']);
   }
   
   if(isset($_SESSION['search_solution_bank']))
   {
      unset($_SESSION['search_solution_bank']);
   }
   
   header("Location: ". base_url('app/ebschat.php'));
   exit();
   
   //echo '<pre>';var_dump($_SESSION);echo'</pre>';
?>
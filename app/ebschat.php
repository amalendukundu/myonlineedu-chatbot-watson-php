<?php
   $relative_path = '../';
   $page_title = 'Restaurant Reservation Chatbot';
   include_once('global.php');
   include_once ($relative_path . 'html_templates/html_header.php');
   include_once ($relative_path . 'html_templates/nav_bar.php');
?>
      <div class="main_section">
		  <div class="container">
			<div class="chat_container">
				<div class="col-sm-3 hidden-xs chat_sidebar">
					<h4>Hello <?php print @$_SESSION['firstName'];?>, <br/>Welcome to Restaurant Reservation Demo.</h4>
               <p>Presented by Myonlineedu.com. Have fun!</p>
				</div>

				<div class="col-sm-6 message_section">
					<div class="row" style="background-color: #fff;">
						<div class="chat_area">
							<ul class="list-unstyled">
							</ul>
						</div>
						<!--chat_area-->

						<div class="message_write">
							<input type="text" id="chatInput" style="width: 80%;" placeholder="Enter your message here..." />
                     <input type="button" value="Send" id="ebsBtnSend" style="width: 10%; height: 40px;" class="btn-primary" />
						</div>
					</div>
				</div>
				<!--message_section-->

				<div class="col-sm-3 hidden-xs chat_sidebar">
               <br>
               <a class="btn btn-success" href="<?php echo base_url('app/ajax/conversation_start.php');?>">Restart Conversation</a>
               
               <div id="cog_documents">
                  
               </div>
            </div>
			</div>
         <div id='ajax_loader' style="position: fixed; left: 50%; top: 50%; display: none;">
             <img src="<?php echo base_url('assets/images/ajax-loader4.gif');?>" style="width: 75px; height: 75px;"></img>
         </div>
		</div>
	</div>
      
      
<?php
   $start_chat_automatic = true;
   $php_js_pages = array('chat_js.php');
   include_once ($relative_path . 'html_templates/footer.php');
?>
      <script src="<?php echo $relative_path;?>assets/js/jquery-2.2.4.min.js"></script>
      <script src="<?php echo $relative_path;?>assets/js/bootstrap.min.js"></script>
      
      <?php
         if(isset($js_pages) && is_array($js_pages))
         {
            foreach($js_pages as $js_page)
               echo '<script src="' . $relative_path . 'assets/js/' . $js_page . '"></script>';
         }
      ?>
      
      <?php
         if(isset($php_js_pages) && is_array($php_js_pages))
         {
            foreach($php_js_pages as $php_js_page)
               include_once ($relative_path . 'assets/js/' . $php_js_page);
         }
      ?>
      
   </body>
</html>
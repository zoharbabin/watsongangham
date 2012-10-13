<?php

   if(!isset($_REQUEST['filename']))
   {
     exit('No file');
   }

   $upload_path = dirname(__FILE__). '/';
   
   $filename = $_REQUEST['filename'];
   
   $fp = fopen($upload_path."/".$filename.".wav", "wb");
   
   fwrite($fp, file_get_contents('php://input'));
   
   fclose($fp);
   
   $commandString = 'ffmpeg -i watson.wav -y -ar 8000 -ab 16 -ac 1 watson-audio.wav >> log_file.log 2>&1 &';
   $command = exec($commandString);
   
   exit('done');
   


?>

<!--Licensed by AT&T under 'Software Development Kit Tools Agreement.'2012
TERMS AND CONDITIONS FOR USE, REPRODUCTION, AND DISTRIBUTION: http://developer.att.com/sdk_agreement/
Copyright 2012 AT&T Intellectual Property. All rights reserved. http://developer.att.com
For more information contact developer.support@att.com
-->

<?php
header("Content-Type: text/html; charset=ISO-8859-1");
include ("config.php");
//include ($oauth_file);
require_once('php5/KalturaClient.php');
require_once('php5/TestCode/KalturaTestConfiguration.php');

//error_reporting(0);
session_start();

function RefreshToken($FQDN,$api_key,$secret_key,$scope,$fullToken){

  $refreshToken=$fullToken["refreshToken"];
  $accessTok_Url = $FQDN."/oauth/token";

  //http header values
  $accessTok_headers = array(
			     'Content-Type: application/x-www-form-urlencoded'
			     );

  //Invoke the URL
  $post_data="client_id=".$api_key."&client_secret=".$secret_key."&refresh_token=".$refreshToken."&grant_type=refresh_token";

  $accessTok = curl_init();
  curl_setopt($accessTok, CURLOPT_URL, $accessTok_Url);
  curl_setopt($accessTok, CURLOPT_HTTPGET, 1);
  curl_setopt($accessTok, CURLOPT_HEADER, 0);
  curl_setopt($accessTok, CURLINFO_HEADER_OUT, 0);
  curl_setopt($accessTok, CURLOPT_HTTPHEADER, $accessTok_headers);
  curl_setopt($accessTok, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($accessTok, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($accessTok, CURLOPT_POST, 1);
  curl_setopt($accessTok, CURLOPT_POSTFIELDS,$post_data);
  $accessTok_response = curl_exec($accessTok);
  $currentTime=time();

  $responseCode=curl_getinfo($accessTok,CURLINFO_HTTP_CODE);
  if($responseCode==200){
    $jsonObj = json_decode($accessTok_response);
    $accessToken = $jsonObj->{'access_token'};//fetch the access token from the response.
    $refreshToken = $jsonObj->{'refresh_token'};
    $expiresIn = $jsonObj->{'expires_in'};

      if($expiresIn == 0) {
          $expiresIn = 24*60*60;
          }

	      
    $refreshTime=$currentTime+(int)($expiresIn); // Time for token refresh
    $updateTime=$currentTime + ( 24*60*60); // Time to get for a new token update, current time + 24h 
	      
    $fullToken["accessToken"]=$accessToken;
    $fullToken["refreshToken"]=$refreshToken;
    $fullToken["refreshTime"]=$refreshTime;
    $fullToken["updateTime"]=$updateTime;
                        
  }
  else{
    $fullToken["accessToken"]=null;
    $fullToken["errorMessage"]=curl_error($accessTok).$accessTok_response;

			
  }
  curl_close ($accessTok);
  return $fullToken;
}
function GetAccessToken($FQDN,$api_key,$secret_key,$scope){

  $accessTok_Url = $FQDN."/oauth/token";
	    
  //http header values
  $accessTok_headers = array(
			     'Content-Type: application/x-www-form-urlencoded'
			     );

  //Invoke the URL
  $post_data = "client_id=".$api_key."&client_secret=".$secret_key."&scope=".$scope."&grant_type=client_credentials";

  $accessTok = curl_init();
  curl_setopt($accessTok, CURLOPT_URL, $accessTok_Url);
  curl_setopt($accessTok, CURLOPT_HTTPGET, 1);
  curl_setopt($accessTok, CURLOPT_HEADER, 0);
  curl_setopt($accessTok, CURLINFO_HEADER_OUT, 0);
  curl_setopt($accessTok, CURLOPT_HTTPHEADER, $accessTok_headers);
  curl_setopt($accessTok, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($accessTok, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($accessTok, CURLOPT_POST, 1);
  curl_setopt($accessTok, CURLOPT_POSTFIELDS,$post_data);
  $accessTok_response = curl_exec($accessTok);
  
  $responseCode=curl_getinfo($accessTok,CURLINFO_HTTP_CODE);
  $currentTime=time();
  /*
   If URL invocation is successful fetch the access token and store it in session,
   else display the error.
  */
  if($responseCode==200)
    {
      $jsonObj = json_decode($accessTok_response);
      $accessToken = $jsonObj->{'access_token'};//fetch the access token from the response.
      $refreshToken = $jsonObj->{'refresh_token'};
      $expiresIn = $jsonObj->{'expires_in'};

       if($expiresIn == 0) {
          $expiresIn = 24*60*60*365*100;
          }


      $refreshTime=$currentTime+(int)($expiresIn); // Time for token refresh
      $updateTime=$currentTime + ( 24*60*60); // Time to get for a new token update, current time + 24h

      $fullToken["accessToken"]=$accessToken;
      $fullToken["refreshToken"]=$refreshToken;
      $fullToken["refreshTime"]=$refreshTime;
      $fullToken["updateTime"]=$updateTime;
      
    }else{
 
    $fullToken["accessToken"]=null;
    $fullToken["errorMessage"]=curl_error($accessTok).$accessTok_response;

  }
  curl_close ($accessTok);
  return $fullToken;
}
function SaveToken( $fullToken,$oauth_file ){

  $accessToken=$fullToken["accessToken"];
  $refreshToken=$fullToken["refreshToken"];
  $refreshTime=$fullToken["refreshTime"];
  $updateTime=$fullToken["updateTime"];
      

  $tokenfile = $oauth_file;
  $fh = fopen($tokenfile, 'w');
  $tokenfile="<?php \$accessToken=\"".$accessToken."\"; \$refreshToken=\"".$refreshToken."\";?>";
  fwrite($fh,$tokenfile);
  fclose($fh);
}

function check_token( $FQDN,$api_key,$secret_key,$scope, $fullToken,$oauth_file){

  $currentTime=time();

  if ( ($fullToken["updateTime"] == null) || ($fullToken["updateTime"] <= $currentTime)){
    $fullToken=GetAccessToken($FQDN,$api_key,$secret_key,$scope);
    if(  $fullToken["accessToken"] == null ){
      //      echo $fullToken["errorMessage"];
    }else{
      //      echo $fullToken["accessToken"];
      SaveToken( $fullToken,$oauth_file );
    }
  }
  elseif ($fullToken["refreshTime"]<= $currentTime){
    $fullToken=RefreshToken($FQDN,$api_key,$secret_key,$scope, $fullToken);
    if(  $fullToken["accessToken"] == null ){
      //      echo $fullToken["errorMessage"];
    }else{
      //      echo $fullToken["accessToken"];
      SaveToken( $fullToken,$oauth_file );
    }
  }
  
  return $fullToken;
  
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <title>AT&amp;T Sample Speech Application - Speech to Text (Generic) Application</title>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
    <link rel="stylesheet" type="text/css" href="style/common.css"/>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="jRecorder.js"> </script>
    
</head>
    <body>
        <div id="container">
            <!-- open HEADER -->
            <div id="header">
                <div>
                    <div class="hcRight">
                       <?php echo date("D M j G:i:s T Y");?>
                    </div>
                    <div class="hcLeft">
                        Server Time:</div>
                </div>
                <div>
                    <div class="hcRight">
                        <script language="JavaScript" type="text/javascript">
                            var myDate = new Date();
                            document.write(myDate);
                        </script>
                    </div>
                    <div class="hcLeft">
                        Client Time:</div>
                </div>
                <div>
                    <div class="hcRight">
                        <script language="JavaScript" type="text/javascript">
                            document.write("" + navigator.userAgent);
                        </script>
                    </div>
                    <div class="hcLeft">
                        User Agent:</div>
                </div>
                <br clear="all" />
            </div>
            <!-- close HEADER -->
            <div>
                <div class="content">
                    <h1>
                        AT&amp;T Sample Speech Application - Speech to Text (Generic) Application</h1>
                    <h2>
                        Feature 1: Speech to Text (Generic)</h2>
                </div>
            </div>
            <br />
            <br />
			
			  <form enctype="multipart/form-data" name="SpeechToText" action="" method="post">
                <div class="navigation">
                    <table border="0" width="100%">
                        <tbody>
                            <tr>
                                <td width="20%" valign="top" class="label">Audio File:</td>
                                <!--td class="cell"><input name="f1" type="file"></td-->
				<td class="cell">
					<div id="locationforrecorder" style="border:1px solid grey">
					</div>
					<script>
   
   $.jRecorder(
     
     { 
        host : 'http://54.242.49.145/watsongangham/acceptfile.php?filename=watson' ,
//        host : 'acceptfile.php?filename=watsonaudio' ,
        callback_started_recording:     function(){callback_started(); },
        callback_stopped_recording:     function(){callback_stopped(); },
        callback_activityLevel:          function(level){callback_activityLevel(level); },
        callback_activityTime:     function(time){callback_activityTime(time); },
        
        callback_finished_sending:     function(time){ callback_finished_sending() },
        
        
        swf_path : 'jRecorder.swf',
     
     }
     
    , $('#locationforrecorder')
        
   
   );
   
    
   
   </script>
<div style="background-color: #eeeeee;border:1px solid #cccccc">

  Time: <span id="time">00:00</span>

</div>


<div>
  Level: <span id="level"></span>
</div>

<div id="levelbase" style="width:200px;height:20px;background-color:#ffff00">

  <div id="levelbar" style="height:19px; width:2px;background-color:red"></div>

</div>

<div>
  Status: <span id="status"></status>
</div>
<input type="button" id="record" value="Record" style="color:red">
<input type="button" id="stop" value="Stop">
<script type="text/javascript">
                  $('#record').click(function(){
                      $.jRecorder.record(30);
                  })
                  
                  $('#stop').click(function(){
                     $.jRecorder.stop();
		     $.jRecorder.sendData();
                  })
                  
                  function callback_finished()
                  {
                      $('#status').html('Recording is finished');
                  }
                  
                  function callback_started()
                  {
                      $('#status').html('Recording is started');
                  }
                  
                  function callback_error(code)
                  {
                      $('#status').html('Error, code:' + code);
                  }
                  
                  function callback_stopped()
                  {
                      $('#status').html('Stop request is accepted');
                  }

                  function callback_finished_recording()
                  {
                      $('#status').html('Recording event is finished');
                  }
                  
                  function callback_finished_sending()
		  {
                      $('#status').html('File has been sent to server mentioned as host parameter');
                  }
                  
                  function callback_activityLevel(level)
                  {
                    $('#level').html(level);
                    if(level == -1)
                    {
                      $('#levelbar').css("width",  "2px");
                    }
                    else
                    {
                      $('#levelbar').css("width", (level * 2)+ "px");
                    }
                  }
                  
                  function callback_activityTime(time)
                  {
                   //$('.flrecorder').css("width", "1px"); 
                   //$('.flrecorder').css("height", "1px"); 
                    $('#time').html(time);
                  }
        </script>
				</td>
                            </tr>
			    <tr>
			<td />
			<td>
			<div id="extraleft">
                        <div class="warning">
                            <strong>Note:</strong><br />
                            After recording your gangham, wait untill the playback finishes and then click submit and wait till watson magically print the song!<br />
                            <strong>Speech file format constraints:</strong> <br />
                                .	16 bit PCM WAV, single channel, 8 kHz sampling<br />
                                .	AMR (narrowband), 12.2 kbit/s, 8 kHz sampling<br />
                        </div>
                        </div>
			        </td>
			    </tr>
                        </tbody>
                    </table>
                </div>
                <div id="extra">
                    <table>
                        <tbody>
                            <tr>
                                <td><button type="submit" name="SpeechToText" value="SpeechToText">Submit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </form>
			
			
			
			
			
				<br clear="all" />
				<div align="center">
			<?php
			if ($_REQUEST["SpeechToText"]) {
			
			
			
			
			
			$fullToken["accessToken"]=$accessToken;
      $fullToken["refreshToken"]=$refreshToken;
      $fullToken["refreshTime"]=$refreshTime;
      $fullToken["updateTime"]=$updateTime;
      
      $fullToken=check_token($FQDN,$api_key,$secret_key,$scope,$fullToken,$oauth_file);
      $accessToken=$fullToken["accessToken"];
	  
	  $filename = null; //$_FILES['f1']['name'];
	  
	  
	  if($filename == null) {
		$commandString = 'ffmpeg -i watson.wav -y -ar 8000 -ab 16 -ac 1 watson-audio.wav >> log_file.log 2>&1 &';
   		$command = exec($commandString);	
	 	$filename = dirname(__FILE__).'/watson-audio.wav';
	  	$file_binary = fread(fopen($filename, 'rb'), filesize($filename));
	  
	 } else{
	  
      		$temp_file = $_FILES['f1']["tmp_name"];
       		$dir = dirname($temp_file);
	  	$file_binary = fread(fopen($temp_file, "r"), filesize($temp_file));
     	       }	  
	  $ext = end(explode('.', $filename));
	  $type = 'audio/'.$ext;
	  
	  
	 if($type == 'audio/wav' || $type == 'audio/amr') {	  

	 $speech_info_url = $FQDN."/rest/1/SpeechToText";
	 
		$authorization = "Authorization: BEARER ".$accessToken; 
		$accept = 'Accept: application/json';
		$content = "Content-Type:".$type;
		$transfer_encoding = 'Content-Transfer-Encoding: chunked';
		$context = 'X-SpeechContext: Generic';
		
	
		

  $speech_info_request = curl_init();
  curl_setopt($speech_info_request, CURLOPT_URL, $speech_info_url);
  curl_setopt($speech_info_request, CURLOPT_HTTPGET, 1);
  curl_setopt($speech_info_request, CURLOPT_HEADER, 0);
  curl_setopt($speech_info_request, CURLINFO_HEADER_OUT, 1);
  curl_setopt($speech_info_request, CURLOPT_HTTPHEADER, array($authorization, $context, $content, $transfer_encoding , $accept));
  curl_setopt($speech_info_request, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($speech_info_request, CURLOPT_POSTFIELDS, $file_binary);
  curl_setopt($speech_info_request, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($speech_info_request, CURLOPT_SSL_VERIFYHOST, false);
  
  



  
  $speech_info_response = curl_exec($speech_info_request);
  $responseCode=curl_getinfo($speech_info_request,CURLINFO_HTTP_CODE);                    
  
 
  if($responseCode==200)
	{
	$jsonObj2 = json_decode($speech_info_response);
	?><div class="successWide" align="left">
						<strong>SUCCESS:</strong>
						<br />
						Response parameters listed below.
					</div>
					<table width="500" cellpadding="1" cellspacing="1" border="0">
						<thead>
							<tr>
								<th width="50%" class="label">Parameter</th>
								<th width="50%" class="label">Value</th>
							</tr>
						</thead>
						<tbody>
							    <td class="cell" align="center"><em>ResponseId</em></td>
								<td class="cell" align="center"><em><?php echo $jsonObj2->Recognition->ResponseId ?></em></td>
							</tr>
							<tr> 
							    <td class="cell" align="center"><em>Hypothesis</em></td>
								<td class="cell" align="center"><em> <?php echo $jsonObj2->Recognition->NBest[0]->Hypothesis ?></em></td>
							</tr>
							<tr> 
							    <td class="cell" align="center"><em>LanguageId</em></td>
								<td class="cell" align="center"><em><?php echo $jsonObj2->Recognition->NBest[0]->LanguageId?></em></td>
							</tr>
							<tr> 
							    <td class="cell" align="center"><em>Confidence</em></td>
								<td class="cell" align="center"><em><?php echo $jsonObj2->Recognition->NBest[0]->Confidence ?></em></td>
							</tr>
							<tr> 
							    <td class="cell" align="center"><em>Grade</em></td>
								<td class="cell" align="center"><em><?php echo $jsonObj2->Recognition->NBest[0]->Grade?></em></td>
							</tr>
							<tr> 
							    <td class="cell" align="center"><em>ResultText</em></td>
								<td class="cell" align="center"><em><?php echo $jsonObj2->Recognition->NBest[0]->ResultText?></em></td>
							</tr>
							<tr>
							    <td class="cell" align="center"><em>Words</em></td> 
								<td class="cell" align="center"><em><?php for ($i=0; $i<=100; $i++) { echo $jsonObj2->Recognition->NBest[0]->Words[$i]; echo ' '; }?></em></td>
							</tr>
							<tr> 
							    <td class="cell" align="center"><em>WordScores</em></td>
								<td class="cell" align="center"><em><?php for ($i=0; $i<=100; $i++) { echo $jsonObj2->Recognition->NBest[0]->WordScores[$i]; echo ' '; }?></em></td>
							</tr>
						</tbody>
					</table><?php
					
					
					
			$kConfig = new KalturaConfiguration(KalturaTestConfiguration::PARTNER_ID);
        	        $kConfig->serviceUrl = KalturaTestConfiguration::SERVICE_URL;
	                $client = new KalturaClient($kConfig);	
			$userId = "watsongangham";
                	$sessionType = KalturaSessionType::ADMIN;
                        $ks = $client->generateSession(KalturaTestConfiguration::ADMIN_SECRET, $userId, $sessionType, KalturaTestConfiguration::PARTNER_ID);
                        $client->setKs($ks);	
//	$client = $this->getKalturaClient(KalturaTestConfiguration::PARTNER_ID, KalturaTestConfiguration::ADMIN_SECRET, false);
                        $filePath = dirname(__FILE__).'/watson-audio.wav'; //'watson-audio.wav';
                        //$token = $client->media->upload($filePath);
                        $token = $client->uploadToken->add();
			$client->uploadToken->upload($token->id, $filePath);
			$mediaResource = new KalturaUploadedFileTokenResource();
			$mediaResource->token = $token->id;
			$entry = new KalturaMediaEntry();
                        $entry->name = "watson gangham - ".@$jsonObj2->Recognition->ResponseId;
			$entry->description = @$jsonObj2->Recognition->NBest[0]->ResultText;
                        $entry->mediaType = KalturaMediaType::AUDIO;
			$entry = $client->media->add($entry);
			$newEntry = $client->media->addContent($entry->id, $mediaResource);
                        //$newEntry = $client->media->addFromUploadedFile($entry, $token);
                        echo "<p>Uploaded to Kaltura: " . $newEntry->id . '<br />' . $filePath . '</p>';

        }else{
		
    		$msghead="Error";
		$msgdata=curl_error($speech_info_request); 
		$errormsg=$msgdata.$speech_info_response;
               ?>
		<div class="errorWide">
                <strong>ERROR:</strong><br />
                <?php  echo $errormsg?>
                </div>
       <?php }
	curl_close ($speech_info_request);
	}else{
	         ?>
		<div class="errorWide">
                <strong>ERROR:</strong><br />
                <?php echo "Invalid file specified. Valid file formats are .wav and .amr"?>
                </div>
       <?php }}
	
?>
            <br clear="all" />
            <div id="footer">
                <div style="float: right; width: 20%; font-size: 9px; text-align: right">
                    Powered by AT&amp;T Cloud Architecture</div>
                <p>
                    &#169; 2012 AT&amp;T Intellectual Property. All rights reserved. <a href="http://developer.att.com/"
                        target="_blank">http://developer.att.com</a>
                    <br />
                    The Application hosted on this site are working examples intended to be used for
                    reference in creating products to consume AT&amp;T Services and not meant to be
                    used as part of your product. The data in these pages is for test purposes only
                    and intended only for use as a reference in how the services perform.
                    <br />
                    For download of tools and documentation, please go to <a href="https://devconnect-api.att.com/"
                        target="_blank">https://devconnect-api.att.com</a>
                    <br />
                    For more information contact <a href="mailto:developer.support@att.com">developer.support@att.com</a></p>
            </div>
        </div>
        <p>
            </p>
    </body>
</html>



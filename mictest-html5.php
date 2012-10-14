<!DOCTYPE HTML>
<html>
<head>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	<script>
		var localMediaStream;
		var recorder;
		var video;
		var audio;
		
		function hasGetUserMedia() {
			// Note: Opera is unprefixed.
			return !!(navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
		}

		function onFailSoHard (e) {
			console.log('Reeeejected!', e);
		};

		function initializeMediaElements () {
			console.log('initializing...');
			window.URL = window.URL || window.webkitURL;
			navigator.getUserMedia  = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
			video = document.querySelector('video');
			audio = document.querySelector('audio');
			navigator.getUserMedia({video: true, audio: true, toString: function() {return "video,audio";}}, function (stream) {
					audio.src = window.URL.createObjectURL(stream);
					audio.play();
					video.src = window.URL.createObjectURL(stream);
					video.play();
					localMediaStream = stream;
					console.log('camera and mic are setup');
				}, onFailSoHard);
		}

		function startRecording (){
			console.log('recording video and audio to local blob...');
			console.log(localMediaStream);
			recorder = localMediaStream.record();
		}

		function stopRecording (){
			localMediaStream.stop();
			/*audio.src = window.URL.createObjectURL(localMediaStream);
			video.src = window.URL.createObjectURL(localMediaStream);
			audio.play();
                        video.play();
                        console.log('playing from the local blob');
			*/
			recorder.getRecordedData(function(blob) {
				// Upload blob using XHR2.
				audio.src = window.URL.createObjectURL(blob);
				video.src = window.URL.createObjectURL(blob);
				document.getElementById('audiodata').innerHTML = blob;
				audio.play();
        	                video.play();
				console.log('playing from the local blob');
				console.log(blob);
			});
		}

		$(document).ready(function() {
			// Handler for .ready() called.
			if (hasGetUserMedia()) {
				console.log('Good to go! we have support for getUserMedia API!');
				initializeMediaElements();
			} else {
				alert('getUserMedia() is not supported in your browser');
			}
		});

	</script>
</head>
<body>
	<video style="width:320px;height:240px;"></video>
	<audio></audio>
	<div id="audiodata"></div>
	<input onclick="startRecording()" type="button" value="start recording" />
	<input onclick="stopRecording()" type="button" value="stop recording and play" />
	<!--
<form enctype="multipart/form-data">
<input name="file" type="file" />
<input type="button" value="Upload" />
</form>
-->
</body>
</html>

//navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;

$(function(){
	/*
	if (navigator.mediaDevices || navigator.getUserMedia) {
		$("#variableButton").show();
		$("#variableButton button").text("音読を開始する");
	} else {
		$("#inputForm").show();
	}
	*/
	$("#inputForm").show();
});

var inputText = "";

var audioStream = null;
var websocket = null;
var recorder = null;
var analyser = null;
var canvas = null;
var canvasContext = null; 
var graphAllowed = true;
var jsonFinal = true;
var wsStopIntervalID = null;

var timerIntervalID = null;
var variableButtonID = 0;
var currentMin = 0;
var currentSec = 0;

function wsOpen() {
	websocket = new WebSocket(wsURI);
	websocket.onopen = function(evt) { onOpen(evt) };
	websocket.onclose = function(evt) { onClose(evt) };
	websocket.onmessage = function(evt) { onMessage(evt) };
	websocket.onerror = function(evt) { onError(evt) };
}

function wsStop() {
	if (jsonFinal) {
		recorder.stop();
		websocket.send(JSON.stringify({"action": "stop"}));
		websocket.close();
		clearInterval(wsStopIntervalID);
	}
}

function onOpen(evt) {
	var message = {
		"continuous": true,
		"interim_results": true,
		"smart_formatting": false,
		"content-type": "audio/wav",
		"action": "start"
	};
	websocket.send(JSON.stringify(message));
}

function onClose(evt) {
	inputText = inputText.substr(0, inputText.length - 1);
}

function onMessage(evt) {
	var jsonData = evt.data;
	var msg = JSON.parse(jsonData);

	if (msg["state"] == "listening") {
		recorder.record(websocket);

		$(function() {
			$("#variableButton button").text("音読を終了する");
			$("#variableButton button").prop("disabled", false);
			$("#connecting").hide();
			$("#canvas").show();
		});
		canvasDrawing();

		currentMin = minute;
		currentSec = second;
		timerManager();
		timerIntervalID = setInterval(timerManager, 1000);

	} else if (msg["results"][0]["final"]) {
		inputText += msg["results"][0]["alternatives"][0]["transcript"];
		jsonFinal = true;
	} else if (!msg["results"][0]["final"]) {
		jsonFinal = false;
	}
}

function onError(evt) {
	alert("エラーが発生しました。(3)");
	location.href = "index.php";
}

function recognitionStart() {
	if (navigator.mediaDevices.getUserMedia) {
		navigator.mediaDevices.getUserMedia({audio: true, video: false}).then(function (stream) {
			audioStream = stream;
			recognition();
		}).catch (function (e) {
			alert("エラーが発生しました。(4)");
			location.href = "index.php";
		});
	} else {
		navigator.getUserMedia(
			{video: false, audio: true},

			function (stream) {
				audioStream = stream;
				recognition();
			},

			function (e) {
				alert("エラーが発生しました。(4)");
				location.href = "index.php";
			}
		);
	}
}

function recognition() {
	$(function() {
		$("#variableButton button").prop("disabled", true);
		$("#model").hide();
		$("#connecting").show();
	});

	var AudioContext = window.AudioContext || window.webkitAudioContext;
	var audioContext = new AudioContext();
	var mediaStreamSource = audioContext.createMediaStreamSource(audioStream);
	recorder = new Recorder(mediaStreamSource);

	analyser = audioContext.createAnalyser();
	analyser.fftSize = 256;
	mediaStreamSource.connect(analyser);
	
	canvas = document.getElementById("visualizedSound");
	canvasContext = canvas.getContext("2d");
	canvasContext.lineWidth = canvas.height;

	window.requestAnimationFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.msRequestAnimationFrame;
	window.cancelAnimationFrame = window.cancelAnimationFrame || window.mozCancelAnimationFrame;
	
	wsOpen();
}

function recognitionEnd() {
	if (audioStream.getAudioTracks()[0].stop) {
		audioStream.getAudioTracks()[0].stop();
	} else if (audioStream.stop) {
		audioStream.stop();
	}

	clearInterval(timerIntervalID);
	graphAllowed = false;
	$(function() {
		$("#variableButton button").text("結果を確認する");
		$("#redoButton").show();
		$("#model").show();
		$("#canvas").hide();
	});

	wsStopIntervalID = setInterval(wsStop, 500);
}

function postInputText() {
	var form = document.createElement("form");
	document.body.appendChild(form);
	var input = document.createElement("input");
	input.setAttribute("type", "hidden");
	input.setAttribute("name", "inputText");
	input.setAttribute("value", inputText);
	form.appendChild(input);
	form.setAttribute("action", "result.php?text=" + title);
	form.setAttribute("method", "post");
	form.submit();
}

function variableButtonPressed() {
	if (variableButtonID == 0) {
		variableButtonID = 1;
		recognitionStart();
	} else if (variableButtonID == 1) {
		variableButtonID = 2;
		recognitionEnd();
	} else if (variableButtonID == 2) {
		postInputText();
	}
}

function redoButtonPressed() {
	$(function() {
		$("#redoButton").hide();
		$('#timer').text(timerTxt);
		$("#variableButton button").text("音読を開始する");
		variableButtonID = 0;
	});
}

function timerManager() {
	var zero = "";
	if (currentSec < 10) {
		zero = "0";
	}
	$(function() {
		$("#timer").text(currentMin + ":" + zero + currentSec);

		if (currentMin < 1 && currentSec < 1) {
			variableButtonID = 2;
			recognitionEnd();
		} else if (currentSec < 1) {
			currentMin--;
			currentSec = 59;
		} else {
			currentSec--;
		}
	});
}

function canvasDrawing() {
	var requestID = window.requestAnimationFrame(canvasDrawing);

	var dataArray = new Uint8Array(analyser.fftSize);
	analyser.getByteTimeDomainData(dataArray);

	var averageDifference = 0;
	for (var i = 0; i < dataArray.length; i++) {
		averageDifference += Math.abs(dataArray[i] - 128);
	}
	averageDifference = averageDifference / dataArray.length;

	canvasContext.clearRect(0, 0, canvas.width, canvas.height);
	canvasContext.beginPath();
	canvasContext.moveTo((canvas.width / 2) - ((averageDifference / 2) * 10), 0);
	canvasContext.lineTo((canvas.width / 2) + ((averageDifference / 2) * 10), 0);
	canvasContext.stroke();

	if (!graphAllowed) {
		window.cancelAnimationFrame(requestID);
		graphAllowed = true;
	}
}

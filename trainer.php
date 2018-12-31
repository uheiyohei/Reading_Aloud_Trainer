<html lang = "ja">
<head>
	<meta charset = "UTF-8">
	<meta name = "viewport" content = "initial-scale = 1.0">
	<link rel = "stylesheet" type = "text/css" href = "css/style.css">
	<script type = "text/javascript" src = "http://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script type = "text/javascript">
	var title = <?php $title = $_GET["text"]; echo '"' . $title . '"'; ?>;
	var minute = 0;
	var second = 0;
	var timerTxt = "";
	</script>
	<script type = "text/javascript" src = "script/recorder.js"></script>
	<script type = "text/javascript" src = "script/speech.js"></script>
	<title><?php echo $title; ?></title>
</head>

<body>
	<div class = "title">Reading Aloud Trainer</div>

	<div class = "textTitle"><?php echo $title; ?></div>

	<div id = "text">
		<?php
		$errorMsg1 = '<script type = "text/javascript">alert("エラーが発生しました。';
		$errorMsg2 = '"); location.href = "index.php";</script>';

		if ($isError) {
			echo $errorMsg1 . "(1)" . $errorMsg2;
		}

		$text = file("text/" . $title . ".txt");
		if (!$text) {
			echo $errorMsg1 . "(2)" . $errorMsg2;
		}

		for ($i = 0; $i < count($text); $i++) {
			echo str_replace("’", "'", $text[$i]);
			if ($i != count($text) - 1) {
				echo "<br>";
			}
		}

		$time = 0;

		$textListArray = file("TextList.txt");
		$listError = false;
		for ($i = 0; $i < count($textListArray); $i++) {
			if (strpos($textListArray[$i], $title) !== false) {
				$time = explode(":", $textListArray[$i])[1];
				if (!$time) {
					$listError = true;
				}
				break;
			} else if ($i == count($textListArray) - 1) {
				$listError = true;
				break;
			}
		}

		if ($listError) {
			echo $errorMsg1 . "(2)" . $errorMsg2;
		}
		?>
	</div>

	<div class = "controller">
		<div id = "model">
			<div id = "modelStr">モデル音声</div>
			<audio id = "audio" preload = "metadata" controls>
				<source src = <?php echo '"audio/' . $title . '.mp3"'?> type = "audio/mp3">
			</audio>
		</div>

		<div id = "connecting">Please wait a moment...</div>

		<div id = "canvas">
			<canvas id = "visualizedSound"></canvas>
		</div>

		<div id = "timer">
			<script type = "text/javascript">
			var time = <?php echo $time; ?>;

			timerTxt += "目標タイム: ";

			minute = parseInt(time / 60);
			second = time - (60 * minute);
			if (minute != 0) {
				timerTxt += String(minute) + "分";
			}
			if (second != 0) {
				timerTxt += String(second) + "秒";
			}
			document.write(timerTxt);
			</script>
		</div>

		<form id = "inputForm" action = <?php echo '"result.php?text=' . $title . '"'?> method = "post">
			<div id = "textarea">
				<textarea name = "inputText" cols = "50" rows = "10" placeholder = "音声入力を用いて英文を入力してください(ピリオドやコンマなどの記号は不要)"></textarea>
			</div>
			<div id = "checkButton">
				<button type = "submit">CHECK</button>
			</div>
		</form>

		<div id = "button">
			<div id = "variableButton">
				<button onClick = "variableButtonPressed()"></button>
			</div>
			<div id = "redoButton">
				<button onClick = "redoButtonPressed()">音読をやり直す</button>
			</div>
		</div>
	
		<div id = "toTop">
			<div id = "headerToTop">
				<a href = "index.php">TOPに戻る</a>
			</div>

			<div id = "footerToTop">
				<a href = "index.php">TOPに戻る</a>
			</div>
		</div>
	</div>

	<script type = "text/javascript">
		$(window).on("load resize", function() {
			if ($(window).width() < 1060) {
				$("#headerToTop").hide();
				$("#footerToTop").show();
			} else {
				$("#headerToTop").show();
				$("#footerToTop").hide();
			}

			$('#text').css({
				'height': window.innerHeight - ($('.title').outerHeight(true) + $('.textTitle').outerHeight(true) + $('.controller').outerHeight(true))
			});

			if ($('#text').height() < 200) {
				$('#text').css({
					'height': '200px'
				});
			}

			if ($('#text').get(0).scrollHeight > $('#text').get(0).clientHeight) {
				$('#text').css({
					'border': 'thin solid Silver'
				});
			} else {
				$('#text').css({
					'height': 'auto',
					'border': 'none'
				});
			}

			if (variableButtonID != 2) {
				$('#redoButton').hide();
			}			
		});
	</script>

</body>
</html>
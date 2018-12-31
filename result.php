<?php
session_start();
?>

<html lang = "ja">
<head>
	<meta charset = "UTF-8">
	<meta name = "viewport" content = "initial-scale = 1.0">
	<link rel = "stylesheet" type = "text/css" href = "css/style.css">
	<script type = "text/javascript" src = "http://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<title>Result - <?php $title = $_GET["text"]; echo $title; ?></title>
</head>

<body>
	<div class = "title">Reading Aloud Trainer</div>

	<div id = "resultTitle">
		<div class = "textTitle"><?php echo $title; ?></div>
		<div id = "resultStr">-- RESULT --</div>
	</div>

	<div id = "result">
		<?php
		$inputWordsArray = explode(" ", htmlspecialchars($_POST["inputText"]));
		$sampleWordsArray = array();
		$swaIndex = 0;
		$matchingSWAIndex = array();
		$symbolOnlyArray = array();
		$allowableGap = 0;

		$errorStr = '<script type = "text/javascript">alert("エラーが発生しました"); location.href = "trainer.php?text=' . $title . '";</script>';

		$text = file("text/" . $title . ".txt");
		if (!$text) {
			echo $errorStr;
		}

		for ($i = 0; $i < count($text); $i++) {
			$sampleWordsArray = array_merge($sampleWordsArray, explode(" ", str_replace("’", "'", $text[$i])));
			if ($i != count($text) - 1) {
				array_push($sampleWordsArray, "###");
			}
		}

		$textListArray = file("TextList.txt");
		$listError = false;
		for ($i = 0; $i < count($textListArray); $i++) {
			if (strpos($textListArray[$i], $title) !== false) {
				$allowableGap = explode(":", $textListArray[$i])[2];
				if (!$allowableGap) {
					$listError = true;
				}
				break;
			} else if ($i == count($textListArray) - 1) {
				$listError = true;
				break;
			}
		}

		if ($listError) {
			echo $errorStr;
		}

		for ($i = 0; $i < count($sampleWordsArray); $i++) {
			if (!preg_match("/[\'A-Za-z0-9]/", $sampleWordsArray[$i])) {
				$symbolOnlyArray += array($i => $sampleWordsArray[$i]);
				array_splice($sampleWordsArray, $i , 1);
			}
		}

		for ($i = 0; $i < count($inputWordsArray); $i++) {
			$inputWordsArray[$i] = mb_strtolower(preg_replace("/[^\'A-Za-z0-9]/", "", $inputWordsArray[$i]));

			$j = 0;
			do {
				if ($sampleWordsArray[$swaIndex + $j]) {
					$judgingWord = mb_strtolower(preg_replace("/[^\'A-Za-z0-9]/", "", $sampleWordsArray[$swaIndex + $j]));

					for ($k = 0; $k < 3; $k++) {
						if ($inputWordsArray[$i] === $judgingWord) {
							array_push($matchingSWAIndex, $swaIndex + $j);
							$swaIndex += $j;
							break 2;
						}

						if ($k < 2) {
							if (strpos($judgingWord, "'s") !== false) {
								$judgingWord = str_replace("'s", "s", $judgingWord);
							} else if (substr($judgingWord, -1) == "s") {
								$judgingWord .= "'";
							} else if (strpos($judgingWord, "s'") !== false) {
								$judgingWord = str_replace("s'", "'s", $judgingWord);
							} else {
								break;
							}
						}
					}
				}

				if ($j == 0) {
					$j++;
				} else if ($j > 0) {
					$j *= -1;
				} else {
					$j = (-1 * $j) + 1;
				}

			} while ($j <= $allowableGap);

			$swaIndex++;
		}

		$_SESSION["sampleWordsArray"] = $sampleWordsArray;
		$_SESSION["matchingSWAIndex"] = $matchingSWAIndex;
		$_SESSION["symbolOnlyArray"] = $symbolOnlyArray;

		for ($i = 0; $i < count($sampleWordsArray); $i++) {
			if ($symbolOnlyArray[$i]) {
				if ($symbolOnlyArray[$i] == "###") {
					echo '<br>';
				} else {
					echo '<span>' . $symbolOnlyArray[$i] . ' </span>';
				}
			}

			if (in_array($i, $matchingSWAIndex)) {
				echo '<span>' . $sampleWordsArray[$i] . '</span>';
			} else {
				if (preg_match("/[^\'A-Za-z0-9]/", $sampleWordsArray[$i])) {
					for ($j = 0; $j < strlen($sampleWordsArray[$i]); $j++) {
						if (preg_match("/[^\'A-Za-z0-9]/", $sampleWordsArray[$i][$j])) {
							echo '<span>' . $sampleWordsArray[$i][$j] . '</span>';
						} else {
							echo '<span class = "miss">' . $sampleWordsArray[$i][$j] . '</span>';
						}
					}
				} else {
					echo '<span class = "miss">' . $sampleWordsArray[$i] . '</span>';
				}
			}

			if ($i != count($sampleWordsArray) - 1) {
				echo '<span> </span>';
			}
		}

		?>
	</div>

	<div id = "resultInfo">
		<div id = "explanationForMistakes">(赤字 = ミスした単語)</div>

		<div id = "accuracyRate">
			正確率: 
			<?php
			echo round((count($matchingSWAIndex) / count($sampleWordsArray)) * 100, 1);
			?>
			%
		</div>
	</div>

	<div class = "controller">
		<div id = "pdfButton">
			<button onClick = <?php echo '\'window.open("resultpdf.php?text=' . $title . '");\''?>>PDF出力</button>
		</div>

		<div id = "linkOnResult">
			<a href = <?php echo '"trainer.php?text=' . $title . '"'?>>もう一度</a>
			<a href = "index.php">TOPに戻る</a>
		</div>
	</div>

	<script type = "text/javascript">
		$(window).on("load resize", function() {
			$('#result').css({
				'height': window.innerHeight - ($('.title').outerHeight(true) + $('#resultTitle').outerHeight(true) + $('#resultInfo').outerHeight(true) + $('.controller').outerHeight(true))
			});

			if ($('#result').height() < 200) {
				$('#result').css({
					'height': '200px'
				});
			}

			if ($('#result').get(0).scrollHeight > $('#result').get(0).clientHeight) {
				$('#result').css({
					'border': 'thin solid Silver'
				});
			} else {
				$('#result').css({
					'height': 'auto',
					'border': 'none'
				});
			}
		});
	</script>
</body>
</html>
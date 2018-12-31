<html lang = "ja">
<head>
	<meta charset = "UTF-8">
	<meta name = "viewport" content = "initial-scale = 1.0">
	<link rel = "stylesheet" type = "text/css" href = "css/style.css">
	<title>Reading Aloud Trainer</title>
</head>

<body>
	<div class = "title">Reading Aloud Trainer</div>
	<div id = "explanationForTextSelection">テキストを選択してください</div>

	<div id = "textList">
		<?php
		$textListArray = file("TextList.txt");
		for ($i = 0; $i < count($textListArray); $i++) {
			$title = explode(":", $textListArray[$i])[0];
			echo('<a href = "trainer.php?text=' . $title . '">' . $title . '</a>');
		}
		?>
	</div>
</body>
</html>
<?php
session_start();

require("fpdf181/fpdf.php");

$pdf = new FPDF("P", "mm", "A4");
$pdf -> AddPage();

$title = $_GET["text"];
$sampleWordsArray = $_SESSION["sampleWordsArray"];
$matchingSWAIndex = $_SESSION["matchingSWAIndex"];
$symbolOnlyArray = $_SESSION["symbolOnlyArray"];

$pdf -> SetFont("Arial", "", 20);
$pdf -> Cell(0, 30, "Reading Aloud Trainer", 0, 1, "C");

$pdf -> Cell(0, 5, "", 0, 1, "C");
$pdf -> SetFont("Arial", "B", 18);
$pdf -> Cell(0, 10, $title, 0, 1, "C");

$pdf -> SetFont("Arial", "", 17);
$pdf -> Cell(0, 10, "-- RESULT --", 0, 1, "C");

$pdf -> Cell(0, 5, "", 0, 1, "C");
$pdf -> SetFont("Arial", "", 14);
$pdf -> SetX(20.0);

for ($i = 0; $i < count($sampleWordsArray); $i++) {
	if ($symbolOnlyArray[$i]) {
		if ($symbolOnlyArray[$i] == "###") {
			$pdf -> Ln();
			$pdf -> SetX(20.0);
		} else {
			writeOnPDF($pdf, $symbolOnlyArray[$i] . " ", 0, true);
		}
	}

	if (in_array($i, $matchingSWAIndex)) {
		writeOnPDF($pdf, $sampleWordsArray[$i], 0, true);
	} else {
		if (preg_match("/[^\'A-Za-z0-9]/", $sampleWordsArray[$i])) {
			for ($j = 0; $j < strlen($sampleWordsArray[$i]); $j++) {
				if (preg_match("/[^\'A-Za-z0-9]/", $sampleWordsArray[$i][$j])) {
					writeOnPDF($pdf, $sampleWordsArray[$i][$j], 0, false);
				} else {
					writeOnPDF($pdf, $sampleWordsArray[$i][$j], 255, false);
				}
			}
		} else {
			writeOnPDF($pdf, $sampleWordsArray[$i], 255, true);
		}
	}

	if ($i != count($sampleWordsArray) - 1) {
		$pdf -> Write(6, " ");
	}
}

$pdf -> Cell(0, 6, "", 0, 1, "C");
$pdf -> SetFont("Arial", "", 17);
$pdf -> Cell(0, 30, "Accuracy Rate: " . round((count($matchingSWAIndex) / count($sampleWordsArray)) * 100, 1) . "%", 0, 1, "C");

$pdf -> SetFont("Arial", "", 14);
$pdf -> SetX(20.0);
$pdf -> Cell(100, 8, "Name: ", 0, 1, "L");
$pdf -> SetX(20.0);
$pdf -> Cell(100, 8, "Date: " . date("Y/n/j"), 0, 1, "L");

$pdf -> Output("Result.pdf", "I");

function writeOnPDF($pdf, $text, $red, $checkX) {
	if ($checkX && $pdf -> GetX() > 175.0) {
		$pdf -> Ln();
		$pdf -> SetX(20.0);
	}
	$pdf -> SetTextColor($red, 0, 0);
	$pdf -> Write(6, $text);
}

?>
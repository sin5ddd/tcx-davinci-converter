<?php
	
	
	use app\Model\TCX;
	use app\Model\GPX;
	
	include_once __DIR__ . "/../vendor/autoload.php";
	
	// $filename = __DIR__ . "/../sample/activity_15000490472.tcx";
	$filename = __DIR__ . "/../sample/LSD_2h.gpx";
	
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	
	if (!file_exists($filename)) {
		echo "File not found";
		exit;
	}
	if ($ext == 'tcx') {
		$data = new TCX();
	} else if ($ext == 'gpx') {
		$data = new GPX();
	} else {
		die ('invalid file extension');
	}
	$data->loadXml(file_get_contents($filename));
?>
	<!doctype html>
	<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>Document</title>
	</head>
	<body>


	<div class='svg-container' style='max-width: 50%;'><?= $data->makeMapSVG()
	                                                            ->toXMLString() ?></div>
	<div class='svg-container' style='max-width: 50%;'><?= $data->makeGradeSVG()
	                                                            ->toXMLString() ?></div>
	</body>
	</html>

<?php
	// file_put_contents('davinci.json',$tcx->makeJson());
	$data->makeSPL();
<?php
	
	
	use app\Model\TCX;
	
	include_once __DIR__ . "/../vendor/autoload.php";
	
	$filename = __DIR__ . "/../sample/activity_15000490472.tcx";
	
	if (!file_exists($filename)) {
		echo "File not found";
		exit;
	}
	$tcx = new TCX(file_get_contents($filename));
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

<table>
	<tr>
		<th>alt</th>
		<th>dist</th>
		<th>hr</th>
		<th>cad</th>
		<th>grade</th>
	</tr>
	<?php
		for ($i = 0; $i < sizeof($tcx->latitude); $i++) { ?>
		<tr>
			<td><?= number_format($tcx->altitudes[$i],1) ?>m</td>
			<td><?= number_format($tcx->distance[$i]/1000 ,1) ?>km</td>
			<td><?= $tcx->hr[$i] ?> BPM</td>
			<td><?= $tcx->cadence[$i] ?> RPM</td>
			<td><?= $tcx->grade[$i] ?>%</td>
		</tr>
		<?php
		} ?>
	<div class="svg-container" style="max-width: 50%;"><?= $tcx->makeMapSVG()->toXMLString() ?></div>
</table>
</body>
</html>
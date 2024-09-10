<?php
	
	
	use app\Model\TCX;
	use app\Model\GPX;
	
	include_once __DIR__ . "/../vendor/autoload.php";
	$is_loaded = false;
	if (sizeof($_FILES) > 0) {
		$dir_name   = basename($_FILES["file_upload"]["name"]);
		$upload_dir = __DIR__ . '/../dist/' . pathinfo($dir_name)['filename'] . '/';
		if (!file_exists($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$upload_file = $upload_dir . $_FILES["file_upload"]["name"];
		move_uploaded_file($_FILES['file_upload']['tmp_name'], $upload_dir . $_FILES['file_upload']['name']);
		$ext = pathinfo($upload_file, PATHINFO_EXTENSION);
		
		if (!file_exists($upload_dir)) {
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
		$data->basedir = $upload_dir;
		$data->loadXml(file_get_contents($upload_file));
		$is_loaded = true;
	}
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
	<form enctype='multipart/form-data' action="index.php" method="POST">
		<input type="file" name="file_upload" id="file_upload" accept=".gpx,.tcx">
		<input type="submit">
	</form>
	<?php
		if ($is_loaded) { ?>
			<div class='svg-container' style='max-width: 50%;'><?php
					$map = $data->makeMapSVG()
					            ->toXMLString()
					;
					echo $map ?></div>
			<div class='svg-container' style='max-width: 50%;'><?php
					$grade = $data->makeGradeSVG()
					              ->toXMLString()
					;
					echo $grade ?></div>
			<a href='download.php?path=<?= pathinfo($dir_name)['filename']?>'>Download Files for Davinci</a>
			<?php
		} ?>
	</body>
	</html>

<?php
	if ($is_loaded) {
		$data->makeSPL();
		file_put_contents(__DIR__ . '/../dist/'.pathinfo($dir_name)['filename'].'/map.svg', $map);
		file_put_contents(__DIR__ . '/../dist/'.pathinfo($dir_name)['filename'].'/grade.svg', $grade);
	}
	
	
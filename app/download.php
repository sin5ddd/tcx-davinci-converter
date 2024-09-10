<?php
/*
 * Copyright (c) 2024. Shingo Kitayama
 */
	$path = $_GET['path'];
	$zip      = new ZipArchive();
	$zip_path = __DIR__ . '/../dist/'.$path.'/'.$path.'.zip';
	$zip->open($zip_path, ZipArchive::CREATE);
	$zip->addFile(__DIR__ . '/../dist/'.$path.'/map.svg', 'map.svg');
	$zip->addFile(__DIR__ . '/../dist/'.$path.'/grade.svg', 'grade.svg');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Altitude.spl', 'Altitude.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Cadence.spl', 'Cadence.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Distance.spl', 'Distance.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Grade.spl', 'Grade.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/HR.spl', 'HR.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Power.spl', 'Power.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Progress.spl', 'Progress.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Speed.spl', 'Speed.spl');
	$zip->addfile(__DIR__ . '/../dist/'.$path.'/Temperature.spl', 'Temperature.spl');
	$zip->close();
	
	$zip_dest = __DIR__ . '/../dist/' . $path . '/' . $path . '.zip';
	
	$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($zip_path);
	
	header('Content-Type: ' . $mimeType);
	header('Content-Length: ' . filesize($zip_dest));
	header('Content-Disposition: attachment; filename=' . basename($zip_dest));
	header('Connection: close');
	
	while (ob_get_level()) {
		ob_end_clean();
	}
	
	readfile($zip_path);
	exit;

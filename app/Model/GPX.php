<?php
	/*
	 * Copyright (c) 2024. Shingo Kitayama
	 */
	
	namespace app\Model;
	
	use DateTime;
	use DateTimeZone;
	
	class GPX extends GPSData {
		
		public function loadXml(string $xml) {
			$normalize_count = $this->getNormalizeCount();
			$xml             = simplexml_load_string($xml);
			// $this->creator =
			$track_arr = $xml->trk->trkseg->trkpt;
			
			$start_dist = null;
			foreach ($track_arr as $t) {
				$time = new DateTime($t->time);
				$time->setTimezone(new DateTimeZone('Asia/Tokyo'));
				$this->time[]      = $time;
				$this->latitude[]  = floatval($t->attributes()['lat']);
				$this->longitude[] = floatval($t->attributes()['lon']);
				
				if (sizeof($this->latitude) > 1) {
					$size             = sizeof($this->latitude);
					$this->distance[] = $this->distance[$size - 2]
					                    + $this->cal_distance($this->latitude[$size - 1],
					                                          $this->longitude[$size - 1],
					                                          $this->latitude[$size - 2],
					                                          $this->longitude[$size - 2]);
				} else {
					$this->distance[] = 0;
				}
				$g_tpx = $t->extensions->children('gpxtpx',true)->TrackPointExtension;
				// var_dump($g_tpx);die;
				$this->hr[]      = intval($g_tpx->hr);
				$this->cadence[] = intval($g_tpx->cad);
				$this->temp[]    = intval($g_tpx->atemp);
				$this->power[]   = floatval($g_tpx->power);
				$altitudes_raw[] = floatval($t->ele);
			}
			$total_dist = $this->distance[sizeof($this->distance) - 1];
			
			for ($i = 0; $i < sizeof($this->distance); $i++) {
				$this->progress[] = $this->distance[$i] / $total_dist;
			}
			
			$a_tmp = [];
			for ($j = 0; $j < $normalize_count; $j++) {
				$a_tmp[] = $altitudes_raw[0];
			}
			foreach ($altitudes_raw as $a) {
				array_pop($a_tmp);
				array_unshift($a_tmp, $a);
				$this->altitudes[] = array_sum($a_tmp) / $normalize_count;
			}
			
			$g_tmp = [];
			for ($l = 0; $l < $normalize_count; $l++) {
				$g_tmp[] = 0;
			}
			for ($k = 0; $k < sizeof($this->distance); $k++) {
				if ($k == 0) {
					$a_delta = 0;
					$d_delta = 0;
				} else {
					$a_delta = $this->altitudes[$k] - $this->altitudes[$k - 1];
					$d_delta = $this->distance[$k] - $this->distance[$k - 1];
				}
				
				$value = abs($d_delta) < 0.001
					? 0
					: $a_delta / $d_delta * 100;
				if ($value == 0 and $k > 0) {
					$value = $this->grade[$k - 1];
				}
				// if ($k > 0) {
				// 	if (abs($value) - abs($g_tmp[$normalize_count - 1]) > 10) {
				// 		$value = $g_tmp[$normalize_count - 1];
				// 	}
				// }
				// array_pop($g_tmp);
				array_unshift($g_tmp, $value);
				array_pop($g_tmp);
				$this->grade[] = round(array_sum($g_tmp)* 10 / $normalize_count)/10.0;
				// $this->grade[] = $value;
				$this->speed[] = round($d_delta * 3600 / 1000);
			}
			
		}
		
		function cal_distance(float $x1, float $y1, float $x2, float $y2): float {
			$x1 = deg2rad($x1);
			$y1 = deg2rad($y1);
			$x2 = deg2rad($x2);
			$y2 = deg2rad($y2);
			return 6378.137 * acos(sin($y1) * sin($y2) + cos($y1) * cos($y2) * cos($x2 - $x1))*1000;
		}
	}
<?php
	
	namespace app\Model;
	
	use DateTime;
	
	/**
	 * Generating Overlay Data from Garmin TCX For Davinci Resolve
	 * Using Reactive plugin Kartaverse/Vonk vJSONGet on Davinci Fusion nodes
	 *
	 */
	class TCX extends GPSData {
		public function __construct(){
			parent::__construct();
		}
		public function loadXml(string $xml){
			$normalize_count = $this->getNormalizeCount();
			$xml             = simplexml_load_string($xml);
			$this->creator   = $xml->Activities->Activity->Creator->Name;
			$lap             = $xml->Activities->Activity->Lap;
			$altitudes_raw   = [];
			$start_dist      = null;
			foreach ($lap as $l) {
				foreach ($l->Track->Trackpoint as $t) {
					if ($start_dist == null) {
						$start_dist = floatval($t->DistanceMeters);
					}
					$time = new DateTime(strval($t->Time));
					$time->setTimezone(new \DateTimeZone('Asia/Tokyo'));
					$this->time[]      = $time;
					$this->latitude[]  = floatval($t->Position->LatitudeDegrees);
					$this->longitude[] = floatval($t->Position->LongitudeDegrees);
					$this->distance[]  = (floatval($t->DistanceMeters) - $start_dist) /1000;
					$this->hr[]        = intval($t->HeartRateBpm->Value);
					$this->cadence[]   = intval($t->Cadence);
					$this->power[]     = floatval($t->Power);
					$altitudes_raw[]   = floatval($t->AltitudeMeters);
				}
			}
			$total_dist = $this->distance[sizeof($this->distance) -1];
			for ($m = 0; $m < sizeof($this->distance); $m++) {
				$this->progress[$m] = $this->distance[$m] / $total_dist;
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
			for ($i = 0; $i < $normalize_count; $i++) {
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
				// $this->grade[] = round(array_sum($g_tmp) / $normalize_count);
				$this->grade[] = $value;
				$this->speed[] = round($d_delta * 3600);
			}
			// var_dump($this->time[1]->format("H"));die;
		}
		
		
		
		
	}
	
	
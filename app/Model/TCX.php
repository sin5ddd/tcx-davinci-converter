<?php
	
	namespace app\Model;
	
	use DateTime;
	
	/**
	 * Generating Overlay Data from Garmin TCX For Davinci Resolve
	 *
	 */
	class TCX extends GPSData {
		public function __construct() {
			parent::__construct();
		}
		
		public function loadXml(string $xml): void {
			$normalize_count = $this->getNormalizeCount();
			$xml             = simplexml_load_string($xml);
			$ns              = $xml->getNamespaces(true);
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
					$this->time[]     = $time;
					// var_dump($t->Position->LatitudeDegrees);die;
					$this->latitude[] = $t->Position->LatitudeDegrees == 0
						? $this->latitude[sizeof($this->latitude) - 1]
						: floatval($t->Position->LatitudeDegrees);
					
					$this->longitude[] = $t->Position->LongitudeDegrees == 0
						? $this->longitude[sizeof($this->longitude) - 1]
						: floatval($t->Position->LongitudeDegrees);
					
					$this->distance[]  = floatval($t->DistanceMeters) / 1000; // convert meter to kilo
					$this->hr[]        = intval($t->HeartRateBpm->Value);
					$this->cadence[]   = intval($t->Cadence);
					$this->power[]     = floatval($t->Power);
					$this->speed[]     = floatval($t->Extensions->children($ns['ns3'])->TPX->Speed) * 1.60934; // convert mph to kpm
					$altitudes_raw[]   = floatval($t->AltitudeMeters);
				}
			}
			$total_dist = $this->distance[sizeof($this->distance) - 1];
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
				
				array_unshift($g_tmp, $value);

				$this->grade[] = $value;
				$this->temp[] = 0; // TCX data doesn't have temperature data
			}
		}
		
		
	}
	
	
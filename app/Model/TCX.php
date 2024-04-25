<?php
	
	namespace app\Model;
	
	use SimpleXMLElement;
	
	class TCX {
		
		public SimpleXMLElement $xml;
		
		public string $creator;
		public string $start_time;
		public array  $pos;
		public array  $time;
		public array  $altitudes; // in Meter\
		public array  $distance;  // in Meter
		public array  $hr;        // in BPM
		public array  $cadence;   // in RPM
		public array  $grade_raw=[]; // in percent
		public array  $grade=[];     // in percent
		
		public function __construct(string $xml) {
			$xml           = simplexml_load_string($xml);
			$this->creator = $xml->Activities->Activity->Creator->Name;
			$lap           = $xml->Activities->Activity->Lap;
			foreach ($lap as $l) {
				foreach ($l->Track->Trackpoint as $t) {
					// var_dump($t);die;
					$this->pos[]       = [
						floatval($t->Position->LatitudeDegrees),
						floatval($t->Position->LongitudeDegrees),
					];
					$this->time[]      = strval($t->Time);
					$this->altitudes[] = floatval($t->AltitudeMeters);
					$this->distance[]  = floatval($t->DistanceMeters);
					$this->hr[]        = intval($t->HeartRateBpm->Value);
					$this->cadence[]   = intval($t->Cadence);
					
					$c = sizeof($this->distance) - 1;
					if ($c > 1) {
						$dist = $this->distance[$c] - $this->distance[$c - 1];
						if ($dist == 0) {
							$this->grade_raw[] = 0.0;
						} else {
							$this->grade_raw[] = ($this->altitudes[$c] - $this->altitudes[$c - 1]) / $dist * 100;
						}
					} else {
						$this->grade_raw[] = 0.0;
					}
				}
				$g_tmp = [];
				for ($i = 0; $i < 10; $i++) {
					$g_tmp[] = 0;
				}
				foreach ($this->grade_raw as $r) {
					array_pop($g_tmp);
					array_unshift($g_tmp, $r);
					$this->grade[] = round(array_sum($g_tmp)/10,1);
				}
			}
		}
		
	}
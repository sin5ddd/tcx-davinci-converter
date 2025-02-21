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
			$xml             = simplexml_load_string($xml);
			$ns              = $xml->getNamespaces(true);
			$this->creator   = $xml->Activities->Activity->Creator->Name;
			$lap             = $xml->Activities->Activity->Lap;
			$start_dist      = null;
			foreach ($lap as $l) {
				foreach ($l->Track->Trackpoint as $t) {
					if ($start_dist == null) {
						$start_dist = floatval($t->DistanceMeters);
					}
					$time = new DateTime(strval($t->Time));
					$time->setTimezone(new \DateTimeZone('Asia/Tokyo'));
					$this->time[] = $time;
					// var_dump($t->Position->LatitudeDegrees);die;
					$this->latitude[] = $t->Position->LatitudeDegrees == 0
						? $this->latitude[sizeof($this->latitude) - 1]
						: floatval($t->Position->LatitudeDegrees);
					
					$this->longitude[] = $t->Position->LongitudeDegrees == 0
						? $this->longitude[sizeof($this->longitude) - 1]
						: floatval($t->Position->LongitudeDegrees);
					
					$this->distance[] = floatval($t->DistanceMeters) / 1000; // convert meter to kilo
					$this->hr[]       = intval($t->HeartRateBpm->Value);
					$this->cadence[]  = intval($t->Cadence);
					$this->power[]    = floatval($t->Power);
					$this->speed[]    = floatval($t->Extensions->children($ns['ns3'])->TPX->Speed) * 1.60934; // convert mph to kpm
					$this->altitudes_raw[]  = floatval($t->AltitudeMeters);
				}
			}
			$total_dist = $this->distance[sizeof($this->distance) - 1];
			for ($m = 0; $m < sizeof($this->distance); $m++) {
				$this->progress[$m] = $this->distance[$m] / $total_dist;
			}
			
			$this->lerpAltitude();
			$this->lerpGradeAndSpeed();
		}
	}
	
	
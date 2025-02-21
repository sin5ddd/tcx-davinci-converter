<?php
	/*
	 * Copyright (c) 2024. Shingo Kitayama
	 */
	
	namespace app\Model;
	
	use DateTime;
	use DateTimeZone;
	
	class GPX extends GPSData {
		
		public function loadXml(string $xml): void {
			$xml       = simplexml_load_string($xml);
			$ns        = $xml->getNamespaces(true);
			$track_arr = $xml->trk->trkseg->children();
			foreach ($track_arr as $t) { // $t : TrackPoint
				$time = new DateTime($t->time);
				$time->setTimezone(new DateTimeZone('Asia/Tokyo'));
				$this->time[]      = $time;
				$this->latitude[]  = floatval($t->attributes()['lat']);
				$this->longitude[] = floatval($t->attributes()['lon']);
				if (sizeof($this->latitude) > 1) {
					$this->distance[] = $this->distance[sizeof($this->distance) - 1]
					                    + $this->calc_distance();
				} else {
					$this->distance[] = 0;
				}
				$gpx_ext               = $t->extensions->children($ns['ns3'])->TrackPointExtension;
				$this->hr[]            = intval($gpx_ext->hr);
				$this->cadence[]       = intval($gpx_ext->cad);
				$this->temp[]          = intval($gpx_ext->atemp);
				$this->power[]         = floatval($gpx_ext->power);
				$this->altitudes_raw[] = floatval($t->ele);
			}
			$total_dist = max($this->distance);
			
			for ($i = 0; $i < sizeof($this->distance); $i++) {
				$this->progress[] = $this->distance[$i] / $total_dist;
			}
			
			// 標高の平滑化
			$this->lerpAltitude();
			// 勾配・速度の平滑化
			$this->lerpGradeAndSpeed();
		}
		
		function calc_distance(): float {
			$lng1     = deg2rad($this->longitude[sizeof($this->longitude) - 1]);
			$lat1     = deg2rad($this->latitude[sizeof($this->latitude) - 1]);
			$lng2     = deg2rad($this->longitude[sizeof($this->longitude) - 2]);
			$lat2     = deg2rad($this->latitude[sizeof($this->latitude) - 2]);
			$distance =
				6378.137 * acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lng2 - $lng1)) * 1000;
			if(is_nan($distance)) {
				$distance = 0;
			}
			return $distance;
		}
	}
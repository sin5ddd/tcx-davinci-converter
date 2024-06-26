<?php
	
	namespace app\Model;
	
	use SVG\SVG;
	use DateTime;
	use SimpleXMLElement;
	use DateTimeImmutable;
	use SVG\Nodes\Shapes\SVGRect;
	use SVG\Nodes\Shapes\SVGCircle;
	use SVG\Nodes\Shapes\SVGPolygon;
	use SVG\Nodes\Shapes\SVGPolyline;
	
	/**
	 * Generating Overlay Data from Garmin TCX For Davinci Resolve
	 * Using Reactive plugin Kartaverse/Vonk vJSONGet on Davinci Fusion nodes
	 *
	 */
	class TCX {
		
		public SimpleXMLElement $xml;
		
		public string $creator;
		public array  $latitude  = [];
		public array  $longitude = [];
		
		public array $time;
		public array $speed     = [];                    // in KPH
		public array $altitudes = [];                    // in Meter
		public array $distance  = [];                    // in Meter
		public array $hr;                                // in BPM
		public array $cadence;                           // in RPM
		public array $grade_raw = [];                    // in percent
		public array $grade     = [];                    // in percent
		public array $power     = [];                    // in W
		public array $progress  = [];                    // in percent
		
		public function __construct(string $xml) {
			$normalize_count = 10;
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
				$this->speed[] = round($d_delta * 3.6);
			}
			// var_dump($this->time[1]->format("H"));die;
		}
		
		public function makeMapSVG(
			$mainColor = '#33ac00',
			$mainWidth = 5,
			$shadowColor = '#33333355',
			$shadowWidth = 6,
			$shadowOffset = 5,
		) {
			
			// マップSVG用の定数を算出
			$maxLng   = max($this->longitude);
			$minLng   = min($this->longitude);
			$maxLat   = max($this->latitude);
			$minLat   = min($this->latitude);
			$aspect   = ($maxLng - $minLng) / ($maxLat - $minLat);
			$latRange = $maxLat - $minLat;
			$lngRange = $maxLng - $minLng;
			
			$svgWidth  = 300;
			$svgHeight = $svgWidth / $aspect;
			
			if ($svgHeight > 1080) {
				$svgHeight = 1080;
				$svgWidth  = $svgHeight * $aspect;
			}
			
			
			$img      = new SVG($svgWidth, $svgHeight);
			$doc      = $img->getDocument();
			$mainPath = new SVGPolyline();
			$mainPath->setAttribute('stroke', $mainColor)
			         ->setAttribute('stroke-width', $mainWidth)
			         ->setAttribute('stroke-linejoin', 'round')
			         ->setAttribute('fill', 'none')
			         ->setAttribute('id', 'MainPath')
			;
			$shadowPath = new SVGPolyline();
			$shadowPath->setAttribute('stroke', $shadowColor)
			           ->setAttribute('stroke-width', $shadowWidth)
			           ->setAttribute('stroke-linejoin', 'round')
			           ->setAttribute('fill', 'none')
			           ->setAttribute('id', 'ShadowPath')
			;
			
			$circle = new SVGCircle();
			$circle->setAttribute('cx', $svgWidth / 2)
			       ->setAttribute('cy', $svgHeight / 2)
			       ->setAttribute('r', 12)
			       ->setAttribute('fill', $mainColor)
			       ->setAttribute('id', 'MainDot')
			;
			
			$circleOutline = new SVGCircle();
			$circleOutline->setAttribute('cx', $svgWidth / 2)
			              ->setAttribute('cy', $svgHeight / 2)
			              ->setAttribute('r', 12)
			              ->setAttribute('fill', $shadowColor)
			              ->setAttribute('id', 'OutlineDot')
			;
			$bg_rect = new SVGRect(0, 0, $svgWidth, $svgHeight);
			$bg_rect->setStyle('fill', '#33333355')
			        ->setStyle('stroke', 'none')
			;
			
			// パス作成
			$conv_x = $svgWidth / $lngRange;
			$conv_y = $svgHeight / $latRange;
			for ($i = 0; $i < sizeof($this->longitude); $i++) {
				$x = ($this->longitude[$i] - $minLng) * $conv_x;
				$y = ($this->latitude[$i] - $minLat) * $conv_y;
				$mainPath->addPoint($x, $y);
				$shadowPath->addPoint($x + $shadowOffset, $y + $shadowOffset);
			}
			
			$doc->addChild($bg_rect)
			    ->addChild($shadowPath)
			    ->addChild($mainPath)
			    ->addChild($circleOutline)
			    ->addChild($circle)
			;
			
			return $img;
			
		}
		
		
		public function makeGradeSVG(
			$mainColor = '#33ac00',
			$subColor = '#228800',
			$mainWidth = 1,
			$shadowColor = '#33333355',
			$shadowOffset = 5,
		) {
			$minAlt   = min($this->altitudes);
			$maxAlt   = max($this->altitudes);
			$altRange = $maxAlt - $minAlt;
			
			
			$svgWidth  = 300;
			$svgHeight = 100;
			$margin    = 10;
			
			$img = new SVG($svgWidth, $svgHeight);
			$doc = $img->getDocument();
			
			$mainPath = new SVGPolyline();
			$mainPath->setAttribute('stroke', $mainColor)
			         ->setAttribute('stroke-width', $mainWidth)
			         ->setAttribute('stroke-linejoin', 'round')
			         ->setAttribute('fill', 'none')
			         ->setAttribute('id', 'MainPath')
			;
			$mainPoly   = new SVGPolyline();
			$shadowPoly = new SVGPolyline();
			
			// パス作成
			$conv_x = ($svgWidth - $margin * 2) / sizeof($this->altitudes);
			$conv_y = ($svgHeight - $margin * 2) / $altRange;
			for ($i = 0; $i < sizeof($this->altitudes); $i++) {
				$x = $i * $conv_x + $margin;
				$y = $svgHeight - ($this->altitudes[$i] - $minAlt) * $conv_y - $margin;
				$mainPath->addPoint($x, $y);
				$mainPoly->addPoint($x, $y);
				$shadowPoly->addPoint($x, $y);
			}
			$mainPoly->addPoint($svgWidth - $margin, $svgHeight - $margin / 2);
			$mainPoly->addPoint($margin, $svgHeight - $margin / 2);
			$shadowPoly->addPoint($svgWidth - $margin, $svgHeight - $margin / 2);
			$shadowPoly->addPoint($margin, $svgHeight - $margin / 2);
			$mainPoly
				->setAttribute('stroke', 'none')
				->setAttribute('fill', $subColor)
				->setAttribute('id', 'MainFill')
				->setAttribute('fill-opacity', '50%')
			;
			$shadowPoly
				->setAttribute('stroke', 'none')
				->setAttribute('fill', $shadowColor)
				->setAttribute('id', 'ShadowFill')
				->setAttribute('fill-opacity', '50%')
			;
			
			
			$bg_rect = new SVGRect(0, 0, $svgWidth, $svgHeight);
			$bg_rect->setStyle('fill', $shadowColor);
			
			$doc->addChild($bg_rect)
			    ->addChild($shadowPoly)
			    ->addChild($mainPoly)
			    ->addChild($mainPath)
			;
			
			return $img;
		}
		
		/**
		 *
		 * @param int $framerate
		 *
		 * @return false|string
		 */
		public function makeJson(int $framerate = 30) {
			/**
			 * Export format:
			 *
			 * {
			 *     "00:00:00:00": {
			 *         'HOUR': 7,
			 *         'MIN' : 30,
			 *         'SEC' : 12,
			 *         'HR':75,
			 *         'SPEED': 25.2,
			 *         'GRADE': -1.2
			 *     }
			 * }
			 *
			 * todo: calc easing data per frame from second
			 * todo: easing data :
			 */
			$data       = [];
			$start_time = $this->time[0];
			$start_dist = $this->distance[0];
			$total_dist = $this->distance[sizeof($this->distance) - 1] - $start_dist;
			$p_point    = null;
			
			for ($i = 0; $i < sizeof($this->latitude); $i++) {
				
				/** @var \DateInterval $tc */
				$tc     = $this->time[$i]->diff($start_time);
				$tc_str = $tc->format("%H:%I:%S:00");
				$c_dist = $this->distance[$i] - $start_dist;
				$point  = new JSONPoint();
				
				$point
					->setTC($tc_str)
					->setHOUR($this->time[$i]->format('H'))
					->setMIN($this->time[$i]->format('m'))
					->setSEC($this->time[$i]->format('s'))
					->setFRAME('00')
					->formatTime()
					->setHR($this->hr[$i])
					->setALT($this->altitudes[$i])
					->setCAD($this->cadence[$i])
					->setSPEED($this->speed[$i])
					->setDIST($c_dist)
					->setGRADE($this->grade[$i])
					->setPROGRESS($c_dist / $total_dist)
				;
				if ($p_point) {
					$arr = $point->ease($p_point, $framerate);
					foreach ($arr as $p) {
						$data[$p->TC] = $p;
					}
				} else {
					$data[$point->TC] = $point;
				}
				$p_point = $point;
			}
			
			return json_encode($data);
		}
		
		public function makeSPL(int $framerate = 30) {
			$data       = [];
			$start_time = $this->time[0];
			$start_dist = $this->distance[0];
			$total_dist = $this->distance[sizeof($this->distance) - 1] - $start_dist;
			$p_point    = null;
			$spls       = [
				new DavinciSPL('HR'),
				new DavinciSPL('Altitude'),
				new DavinciSPL('Cadence'),
				new DavinciSPL('Speed'),
				new DavinciSPL('Distance'),
				new DavinciSPL('Grade'),
				new DavinciSPL('Power'),
				new DavinciSPL('Progress'),
			];
			for ($i = 0; $i < sizeof($this->latitude); $i++) {
				$spls[0]->addValue($this->hr[$i]);
				$spls[1]->addValue($this->altitudes[$i]);
				$spls[2]->addValue($this->cadence[$i]);
				$spls[3]->addValue($this->speed[$i]);
				$spls[4]->addValue($this->distance[$i]);
				$spls[5]->addValue($this->grade[$i]);
				$spls[6]->addValue($this->power[$i]);
				$spls[7]->addValue($this->distance[$i] / $total_dist);
			}
			
			foreach ($spls as $s) {
				$s->generate();
			}
		}
	}
	
	
<?php
	
	namespace app\Model;
	
	use SVG\SVG;
	use SimpleXMLElement;
	use SVG\Nodes\Shapes\SVGCircle;
	use SVG\Nodes\Shapes\SVGPolyline;
	
	class TCX {
		
		public SimpleXMLElement $xml;
		
		public string $creator;
		public string $start_time;
		public array  $latitude  = [];
		public array  $longitude = [];
		public float  $minLat;
		public float  $maxLat;
		public float  $minLng;
		public float  $maxLng;
		
		public array $time;
		public array $altitudes;          // in Meter\
		public array $distance;           // in Meter
		public array $hr;                 // in BPM
		public array $cadence;            // in RPM
		public array $grade_raw = [];     // in percent
		public array $grade     = [];     // in percent
		
		public function __construct(string $xml) {
			$xml           = simplexml_load_string($xml);
			$this->creator = $xml->Activities->Activity->Creator->Name;
			$lap           = $xml->Activities->Activity->Lap;
			foreach ($lap as $l) {
				foreach ($l->Track->Trackpoint as $t) {
					$this->latitude[]  = floatval($t->Position->LatitudeDegrees);
					$this->longitude[] = floatval($t->Position->LongitudeDegrees);
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
					$this->grade[] = round(array_sum($g_tmp) / 10, 1);
				}
			}
			$this->maxLng = max($this->longitude);
			$this->minLng = min($this->longitude);
			$this->maxLat = max($this->latitude);
			$this->minLat = min($this->latitude);
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
			
			$svgWidth  = 1920;
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
			
			// パス作成
			$conv_x = $svgWidth / $lngRange;
			$conv_y = $svgHeight / $latRange;
			for ($i = 0; $i < sizeof($this->longitude); $i++) {
				$x = ($this->longitude[$i] - $minLng) * $conv_x;
				$y = ($this->latitude[$i] - $minLat) * $conv_y;
				$mainPath->addPoint($x, $y);
				$shadowPath->addPoint($x + $shadowOffset, $y + $shadowOffset);
			}
			
			$doc->addChild($shadowPath)
			    ->addChild($mainPath)
			    ->addChild($circleOutline)
			    ->addChild($circle)
			;
			
			return $img;
			
		}
		
	}
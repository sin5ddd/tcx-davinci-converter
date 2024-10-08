<?php
	/*
	 * Copyright (c) 2024. Shingo Kitayama
	 */
	
	namespace app\Model;
	
	use SVG\SVG;
	use SimpleXMLElement;
	use SVG\Nodes\Shapes\SVGRect;
	use SVG\Nodes\Shapes\SVGCircle;
	use SVG\Nodes\Shapes\SVGPolyline;
	
	class GPSData {
		
		public SimpleXMLElement $xml;
		
		public string $creator;
		public array  $latitude  = [];
		public array  $longitude = [];
		
		public array  $time;
		public array  $speed     = [];                    // in KPH
		public array  $altitudes = [];                    // in Meter
		public array  $distance  = [];                    // in Meter
		public array  $hr;                                // in BPM
		public array  $cadence;                           // in RPM
		public array  $grade_raw = [];                    // in percent
		public array  $grade     = [];                    // in percent
		public array  $power     = [];                    // in W
		public array  $progress  = [];                    // in percent
		public array  $temp      = [];
		public string $basedir   = "";
		
		private $normalize_count = 10;
		
		public function setNormalizeCount(int $normalize_count): GPSData {
			$this->normalize_count = $normalize_count;
			return $this;
		}
		
		public function setMainColor(string $mainColor): GPSData {
			$this->mainColor = $mainColor;
			return $this;
		}
		
		public function setMainWidth(int $mainWidth): GPSData {
			$this->mainWidth = $mainWidth;
			return $this;
		}
		
		public function setShadowColor(string $shadowColor): GPSData {
			$this->shadowColor = $shadowColor;
			return $this;
		}
		
		public function setShadowWidth(int $shadowWidth): GPSData {
			$this->shadowWidth = $shadowWidth;
			return $this;
		}
		
		public function setShadowOffset(int $shadowOffset): GPSData {
			$this->shadowOffset = $shadowOffset;
			return $this;
		}
		
		public function setSubColor(string $subColor): GPSData {
			$this->subColor = $subColor;
			return $this;
		}
		
		private $mainColor    = '#33ac00';
		private $subColor     = '#228800';
		private $mainWidth    = 5;
		private $shadowColor  = '#33333355';
		private $shadowWidth  = 6;
		private $shadowOffset = 5;
		
		public function __construct() { }
		
		public function makeMapSVG() {
			
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
			$mainPath->setAttribute('stroke', $this->mainColor)
			         ->setAttribute('stroke-width', $this->mainWidth)
			         ->setAttribute('stroke-linejoin', 'round')
			         ->setAttribute('fill', 'none')
			         ->setAttribute('id', 'MainPath')
			;
			$shadowPath = new SVGPolyline();
			$shadowPath->setAttribute('stroke', $this->shadowColor)
			           ->setAttribute('stroke-width', $this->shadowWidth)
			           ->setAttribute('stroke-linejoin', 'round')
			           ->setAttribute('fill', 'none')
			           ->setAttribute('id', 'ShadowPath')
			;
			
			$circle = new SVGCircle();
			$circle->setAttribute('cx', $svgWidth / 2)
			       ->setAttribute('cy', $svgHeight / 2)
			       ->setAttribute('r', 12)
			       ->setAttribute('fill', $this->mainColor)
			       ->setAttribute('id', 'MainDot')
			;
			
			$circleOutline = new SVGCircle();
			$circleOutline->setAttribute('cx', $svgWidth / 2)
			              ->setAttribute('cy', $svgHeight / 2)
			              ->setAttribute('r', 12)
			              ->setAttribute('fill', $this->shadowColor)
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
				$shadowPath->addPoint($x + $this->shadowOffset, $y + $this->shadowOffset);
			}
			
			$doc->addChild($bg_rect)
			    ->addChild($shadowPath)
			    ->addChild($mainPath)
			    ->addChild($circleOutline)
			    ->addChild($circle)
			;
			
			return $img;
			
		}
		
		public function getNormalizeCount(): int {
			return $this->normalize_count;
		}
		
		protected function makeJson(int $framerate = 30) {
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
				$tc_str = $tc->format('%H:%I:%S:00');
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
		
		/**
		 *
		 * @param int $framerate
		 *
		 * @return false|string
		 */
		
		
		public function makeSPL(int $framerate = 60): void {
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
				new DavinciSPL('Temperature'),
			];
			for ($i = 0; $i < sizeof($this->latitude); $i++) {
				$spls[0]->addValue($this->hr[$i]);
				$spls[1]->addValue(number_format($this->altitudes[$i], 1));
				$spls[2]->addValue($this->cadence[$i]);
				$spls[3]->addValue($this->speed[$i]);
				$spls[4]->addValue(round($this->distance[$i] * 100) / 100.0);
				$spls[5]->addValue(number_format($this->grade[$i], 2));
				$spls[6]->addValue($this->power[$i]);
				$spls[7]->addValue(number_format($this->distance[$i] / $total_dist, 3));
				$spls[8]->addValue(number_format($this->temp[$i], 2));
			}
			
			foreach ($spls as $s) {
				$s->generate($this->basedir);
			}
		}
		
		public function makeGradeSVG() {
			$minAlt   = min($this->altitudes);
			$maxAlt   = max($this->altitudes);
			$altRange = $maxAlt - $minAlt;
			
			
			$svgWidth  = 300;
			$svgHeight = 100;
			$margin    = 10;
			
			$img = new SVG($svgWidth, $svgHeight);
			$doc = $img->getDocument();
			
			$mainPath = new SVGPolyline();
			$mainPath->setAttribute('stroke', $this->mainColor)
			         ->setAttribute('stroke-width', $this->mainWidth)
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
				->setAttribute('fill', $this->subColor)
				->setAttribute('id', 'MainFill')
				->setAttribute('fill-opacity', '50%')
			;
			$shadowPoly
				->setAttribute('stroke', 'none')
				->setAttribute('fill', $this->shadowColor)
				->setAttribute('id', 'ShadowFill')
				->setAttribute('fill-opacity', '50%')
			;
			
			
			$bg_rect = new SVGRect(0, 0, $svgWidth, $svgHeight);
			$bg_rect->setStyle('fill', $this->shadowColor);
			
			$doc->addChild($bg_rect)
			    ->addChild($shadowPoly)
			    ->addChild($mainPoly)
			    ->addChild($mainPath)
			;
			
			return $img;
		}
		
	}
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
		public array  $altitudes_raw = [];
		public array  $distance  = [];                    // in Meter
		public array  $hr;                                // in BPM
		public array  $cadence;                           // in RPM
		public array  $grade_raw = [];                    // in percent
		public array  $grade     = [];                    // in percent
		public array  $power     = [];                    // in W
		public array  $progress  = [];                    // in percent
		public array  $temp      = [];
		public string $basedir   = "";
		
		private int $normalize_count = 10;
		
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
		
		protected function lerpAltitude(): void {
			// 高度は誤差が大きいため平滑化する
			$a_tmp = [];
			for ($j = 0; $j < $this->normalize_count; $j++) {
				$a_tmp[] = $this->altitudes_raw[0];
			}
			foreach ($this->altitudes_raw as $a) {
				array_pop($a_tmp);
				array_unshift($a_tmp, $a);
				$this->altitudes[] = array_sum($a_tmp) / $this->normalize_count;
			}
		}
		
		protected function lerpGradeAndSpeed(): void {
			$g_tmp = [];
			for ($i = 0; $i < $this->normalize_count; $i++) {
				$g_tmp[] = 0;
			}
			for ($j = 0; $j < sizeof($this->distance); $j++) {
				if ($j == 0) {
					$a_delta = 0;
					$d_delta = 0;
				} else {
					$a_delta = $this->altitudes[$j] - $this->altitudes[$j - 1];
					$d_delta = $this->distance[$j] - $this->distance[$j - 1];
				}
				
				$value = abs($d_delta) < 0.001
					? 0
					: $a_delta / $d_delta * 100;
				if ($value == 0 and $j > 0) {
					$value = $this->grade[$j - 1];
				}
				array_unshift($g_tmp, $value);
				array_pop($g_tmp);
				$this->grade[] = round(array_sum($g_tmp) / $this->normalize_count ,1);
				$this->speed[] = round($d_delta * 3600 / 1000);
			}
		}
		
		private string $mainColor = '#33ac00';
		private string $subColor  = '#228800';
		private int    $mainWidth = 5;
		private string $shadowColor = '#33333355';
		private int    $shadowWidth = 6;
		private int $shadowOffset = 5;
		
		public function __construct() { }
		
		public function makeMapSVG(): SVG {
			
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
		
		
		/**
		 *
		 * @param int $framerate
		 *
		 * @return void
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
				$spls[1]->addValue(round($this->altitudes[$i], 1));
				$spls[2]->addValue($this->cadence[$i]);
				$spls[3]->addValue($this->speed[$i]);
				$spls[4]->addValue(round($this->distance[$i],2));
				$spls[5]->addValue(round($this->grade[$i], 2));
				$spls[6]->addValue($this->power[$i]);
				$spls[7]->addValue(round($this->distance[$i] / $total_dist, 3));
				$spls[8]->addValue(round($this->temp[$i], 2));
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
<?php
	/*
	 * Copyright (c) 2024. Shingo Kitayama
	 */
	
	namespace app\Model;
	
	class JSONPoint {
		public string $TC;
		public int    $HOUR;
		public int    $MIN;
		public int    $SEC;
		public int    $FRAME;
		public int    $HR;
		public int    $SPEED;
		public string $GRADE;
		public int    $ALT;
		public int    $CAD;
		public string $DIST;
		public float  $PROGRESS;
		public string $TIME;
		
		public function getTime(): string {
			return sprintf('%02d', $this->HOUR) . ':'
			       . sprintf('%02d', $this->MIN) . ':'
			       . sprintf('%02d', $this->SEC) . ':'
			       . sprintf('%02d', $this->FRAME);
		}
		
		public function formatTime(): JSONPoint {
			$this->TIME = $this->getTime();
			return $this;
		}
		
		public function setHOUR(int $HOUR): JSONPoint {
			$this->HOUR = $HOUR;
			return $this;
		}
		
		public function setMIN(int $MIN): JSONPoint {
			$this->MIN = $MIN;
			return $this;
		}
		
		public function setSEC(int $SEC): JSONPoint {
			$this->SEC = $SEC;
			return $this;
		}
		
		public function setHR(int $HR): JSONPoint {
			$this->HR = $HR;
			return $this;
		}
		
		public function setSPEED(int $SPEED): JSONPoint {
			$this->SPEED = $SPEED;
			return $this;
		}
		
		public function setGRADE(string $GRADE): JSONPoint {
			$this->GRADE = $GRADE;
			return $this;
		}
		
		public function setALT(int $ALT): JSONPoint {
			$this->ALT = $ALT;
			return $this;
		}
		
		public function setCAD(int $CAD): JSONPoint {
			$this->CAD = $CAD;
			return $this;
		}
		
		public function setDIST(string $DIST): JSONPoint {
			$this->DIST = $DIST;
			return $this;
		}
		
		public function setPROGRESS(float $PROGRESS): JSONPoint {
			$this->PROGRESS = $PROGRESS;
			return $this;
		}
		
		public function setFRAME(int $FRAME): JSONPoint {
			$this->FRAME = $FRAME;
			return $this;
		}
		
		public function setTC(string $TC): JSONPoint {
			$this->TC = $TC;
			return $this;
		}
		
		public function ease(JSONPoint $prev, int $frame, int $precision = 5): array {
			$ret        = [];
			$fps        = intval($frame / $precision);
			$d_hr       = ($this->HR - $prev->HR) / $fps;
			$d_alt      = ($this->ALT - $prev->ALT) / $fps;
			$d_cad      = ($this->CAD - $prev->CAD) / $fps;
			$d_speed    = ($this->SPEED - $prev->SPEED) / $fps;
			$d_dist     = ($this->DIST - $prev->DIST) / $fps;
			$d_grade    = ($this->GRADE - $prev->GRADE) / $fps;
			$d_progress = ($this->PROGRESS - $prev->PROGRESS) / $fps;
			
			for ($i = 0; $i * $fps < $frame; $i++) {
				$p = new JSONPoint();
				$p
					->setHOUR($prev->HOUR)
					->setMIN($prev->MIN)
					->setSEC($prev->SEC)
					->setFrame($i * $fps)
					->formatTime()
				;
				$p->TC = substr($prev->TC, 0, 8) . sprintf(':%02d', $i * $fps);
				
				$p
					->setHR(intval($prev->HR + $d_hr * $i))
					->setSPEED(round(floatval($prev->SPEED) + $d_speed * $i))
					->setDIST(sprintf('%.1f', floatval($prev->DIST) + $d_dist * $i))
					->setGRADE(sprintf('%.1f', floatval($prev->GRADE) + $d_grade * $i))
					->setALT(intval($prev->ALT + $d_alt * $i))
					->setCAD(intval(floatval($prev->CAD) + $d_cad * $i))
					->setPROGRESS(floatval($prev->PROGRESS + $d_progress * $i))
				;
				$ret[] = $p;
			}
			return $ret;
		}
	}
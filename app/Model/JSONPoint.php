<?php
/*
 * Copyright (c) 2024. Shingo Kitayama
 */

namespace app\Model;
	
	class JSONPoint {
		public string $TC;
		public int $HOUR;
		public int $MIN;
		public int $SEC;
		public int $HR;
		public string $SPEED;
		public string $GRADE;
		public string $ALT;
		public string $CAD;
		public string $DIST;
		public float $PROGRESS;
		
		public function getTC(): string {
			return $this->TC;
		}
		
		public function getHOUR(): int {
			return $this->HOUR;
		}
		
		public function getMIN(): int {
			return $this->MIN;
		}
		
		public function getSEC(): int {
			return $this->SEC;
		}
		
		public function getHR(): int {
			return $this->HR;
		}
		
		public function getSPEED(): string {
			return $this->SPEED;
		}
		
		public function getGRADE(): string {
			return $this->GRADE;
		}
		
		public function getALT(): string {
			return $this->ALT;
		}
		
		public function getCAD(): string {
			return $this->CAD;
		}
		
		public function getDIST(): string {
			return $this->DIST;
		}
		
		public function getPROGRESS(): float {
			return $this->PROGRESS;
		}
		
		public function setTC(string $TC): JSONPoint {
			$this->TC = $TC;
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
		
		public function setSPEED(string $SPEED): JSONPoint {
			$this->SPEED = $SPEED;
			return $this;
		}
		
		public function setGRADE(string $GRADE): JSONPoint {
			$this->GRADE = $GRADE;
			return $this;
		}
		
		public function setALT(string $ALT): JSONPoint {
			$this->ALT = $ALT;
			return $this;
		}
		
		public function setCAD(string $CAD): JSONPoint {
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
	}
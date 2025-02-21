<?php
	/*
	 * Copyright (c) 2024. Shingo Kitayama
	 */
	
	namespace app\Model;
	
	class DavinciSPL {
		public string $filename;
		private int   $framerate;
		private array $values = [];
		
		public function __construct(string $name, int $framerate = 30) {
			$this->filename  = $name;
			$this->framerate = $framerate;
		}
		
		public function addValue($v): void {
			$this->values[] = $v;
		}
		
		public function generate(string $dest = __DIR__ . "/../../dist"): void {
			$str = "DFSP";
			for ($i = 0; $i < sizeof($this->values); $i++) {
				$frame = $this->framerate * $i;
				$str   .= "\n$frame " . $this->values[$i];
			}
			if (!file_exists($dest)) {
				mkdir($dest);
			}
			file_put_contents($dest . "/" . $this->filename . ".spl", $str);
		}
	}
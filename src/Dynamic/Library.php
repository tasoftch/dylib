<?php
namespace TASoft\DyLib\Dynamic;

use TASoft\DyLib\Library as ABS_LIB;

class Library extends ABS_LIB {
	private $name, $path, $meta;
	
	
	private function __construct($name, $data) {
		$this->name = $name;
		$this->path = $data[1];
		$this->meta = $data[2];
	}
	
	
	public static function registerNewLibrary($name) {
		$lib = new static($name, func_get_args());
		static::$allLibraries[$name] = $lib;
		return true;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getPath() {
		return $this->path;
	}
}
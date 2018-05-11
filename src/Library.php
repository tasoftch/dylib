<?php
namespace TASoft\DyLib;

abstract class Library {
	protected static $allLibraries = [];
	
	private static $extMap = [
		'dylib' => Dynamic\Library::class,
		'slib' => Stat\Library::class
	];
	
	public static function linkLibrary(string $filename, &$info = NULL): bool {
		if(preg_match("/\.([^\.]+)$/", $filename, $ms)) {
			$ext = strtolower($ms[1]);
			$class = self::$extMap[$ext] ?? NULL;
			
			if($class) {
				call_user_func("\\TASoft\\DyLib\\DynamicLibraryLinker::willLinkForClass", $class);
				if($info)
					$info = unserialize( file_get_contents("dll://$filename") );
				else
					file_get_contents("dll://$filename");
				return true;
			} else {
				trigger_error("Libraries with extension '$ext' are not supported", E_USER_WARNING);
			}
		}
		return false;
	}

	public static function canLinkLibrary(string $filename):bool {
        if(preg_match("/\.([^\.]+)$/", $filename, $ms)) {
            $ext = strtolower($ms[1]);
            if(isset(self::$extMap[$ext]))
                return true;
        }
        return false;
    }
	
	public static function libraryWithName($name) {
		return self::$allLibraries[$name] ?? NULL;
	}
	
	public static function getLinkedLibraries() {
		return array_keys(self::$allLibraries);
	}
	
	abstract public function getName();
	abstract public function getPath();
}
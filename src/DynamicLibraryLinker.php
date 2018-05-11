<?php
namespace TASoft\DyLib {

/*
*	The dynamic library linker class enables the linking against libraries. You should call it before any attempt to load libraries.
*	After that, you can simply use the symbols defined in a library. You need to know, which symbols are included in a library. Only frameworks can declare their symbols.
*/

use Composer\Autoload\ClassLoader;

class DynamicLibraryLinker {
	protected static $autoloader;
	
	private static $className;
	private $metaData;
	private $pos = 0;
	
	
	public static function getAutoloader() {
		if(!static::$autoloader) {
			static::$autoloader = new ClassLoader();
			static::$autoloader->register();
		}
		return static::$autoloader;
	}
	
	
	protected function _linkDynamicLibrary($name, $linkerInfo) {
		$namespace = $linkerInfo['namespace'] ?? NULL;

		if(!$namespace) {
			throw new LinkerException("Can not link dynamic library $name because it does not support a psr-4 namespace declaration.", 14);
		}
		
		$loader = static::getAutoloader();
		$loader->setPsr4($namespace, ["phar://$name.phar"]);
	
		return true;
	}
	
	protected function _linkStaticLibrary($name, $linkerInfo) {
		$public = $linkerInfo['public'];
		foreach($public as $file)
			require("phar://$name.phar/$file");
		
		return true;
	}
	
	
	protected function linkAgainstLibrary($libPath) {
		$kind = NULL;
		if(preg_match("/^(.*?)\.(dylib|slib)$/i", $libPath, $ms)) {
			$libName = basename($ms[1]);
			$kind = $ms[2];
		} else {
			return false;
		}
		
		global $__tasoft_ref_path;
		$__tasoft_ref_path = "$libName.$kind";
		$data = $this->metaData = require($libPath);
		unset($__tasoft_ref_path);
		
		$meta = unserialize( base64_decode($data) );
		if(!$meta['name']) {
			throw new LinkerException("Library has no name declared with it", 12);
			return false;
		}
		
		
		
		if(md5($meta['name']) != $meta['hash']) {
			throw new LinkerException("Library signature is broken", 11);
		}
		
		$machOHashes = [
			md5('dylib') => 'dylib',
			md5('slib') => 'slib'
		];
		
		$machO = $machOHashes[ $meta['mach-o'] ] ?? NULL;
		
		if(!$machO) {
			throw new LinkerException("Library has no valid mach-o type declared with it", 17);
			return false;
		}
		
		
		if(self::$className) {
			$cn = self::$className;
			$lib = call_user_func("$cn::registerNewLibrary", $libName, $libPath, $meta);
			if(!$lib)
				trigger_error("Could not register library $libName", E_USER_WARNING);
			self::$className = false;
		}
		
		if($machO == 'dylib')
			return $this->_linkDynamicLibrary($libName, $meta);
		
		if($machO == 'slib')
			return $this->_linkStaticLibrary($libName, $meta);
		
		return true;
	}
	
	
	
	function stream_open($path, $mode, $options, &$opened_path)
    {
	    $scheme = 0;
       	if(preg_match("/(dll|dylib):\/\/(.+)$/i", $path, $ms)) {
	       	$scheme = $ms[1];
	       	$path = $ms[2];
       	}
       	
       	if(strtolower($scheme) == 'dll')
       		return $this->linkAgainstLibrary($path);
       	
       	
       	
        return true;
    }
    
    function stream_stat() {
	    return array();
    }
    
    function url_stat() {
	    return array();
    }
    
    function stream_read($count)
    {
        $ret = substr($this->metaData, $this->pos, $count);
        $this->pos += strlen($ret);
        return $ret;
    }
    
    function stream_tell()
    {
        return $this->pos;
    }
    
     function stream_eof()
    {
        return $this->pos >= strlen($this->metaData);
    }
    
    static function willLinkForClass($class) {
	    self::$className = $class;
    }
}

stream_wrapper_register('dll', DynamicLibraryLinker::class);
stream_wrapper_register('dylib', DynamicLibraryLinker::class);
}


namespace {
	function tasoft_validate_ctx($hash) {
		global $__tasoft_ref_path;
		
		if(md5("$__tasoft_ref_path") == $hash) {
			return true;
		}
		
		return false;
	}
}
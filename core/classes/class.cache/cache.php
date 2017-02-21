<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
final class cache
{
	private static $_runStep	=	0;
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $dir			=	"";
	private static $timeout		=	180;

	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
	}

	public static function _init()
	{
		if(self::$_runStep)return;
		self::$_runStep++;
		if(strpos(self::$class,"\\")!==false)
		{
			$cl=explode("\\",self::$class);
			self::$class=$cl[count($cl)-1];
		}
		self::$c=_a::core();
		self::$dir=FLEX_APP_DIR_DAT."/_".self::$class;
		if(!@file_exists(self::$dir))@mkdir(self::$dir,0755,true);
	}

	public static function _sleep(){}

	public static function check($class,$entity,$ttl=false,$ext="")
	{
		if(!$class && !$entity)return false;
		$valDir=md5($class.$entity);
		$dir=self::$dir."/".$class."/".$valDir;
		if($ttl===false)
		{
			$files=@glob($dir."/index".($ext?"":(".html")).".*.cache".($ext?(".".$ext):""),GLOB_NOSORT);
			if(!count($files))return false;
			$file=$files[0];
			$ttl=str_replace($dir."/index".($ext?"":(".html")).".","",$files[0]);
			$ttl=0+str_replace(".cache".($ext?(".".$ext):""),"",$ttl);
		}
		else
		{
			$ttl=0+$ttl;
			if($ttl<0)$ttl=self::$timeout;
		}
		$file=$dir."/index".($ext?"":(".html")).".".$ttl.".cache".($ext?(".".$ext):"");
		if(!@file_exists($file))return false;
		if($ttl>0)
		{
			if((time()-filemtime($file))>$ttl)return false;
		}
		return $file;
	}

	public static function get($class,$entity,$ttl=false,$echo=false,$ext="")
	{
		if(!$class && !$entity)return false;
		$valDir=md5($class.$entity);
		$dir=self::$dir."/".$class."/".$valDir;
		if($ttl===false)
		{
			$files=@glob($dir."/index".($ext?"":(".html")).".*.cache".($ext?(".".$ext):""),GLOB_NOSORT);
			if(!count($files))return false;
			$file=$files[0];
			$ttl=str_replace($dir."/index".($ext?"":(".html")).".","",$files[0]);
			$ttl=0+str_replace(".cache".($ext?(".".$ext):""),"",$ttl);
		}
		else
		{
			$ttl=0+$ttl;
			if($ttl<0)$ttl=self::$timeout;
		}
		$file=$dir."/index".($ext?"":(".html")).".".$ttl.".cache".($ext?(".".$ext):"");
		if(!@file_exists($file))return false;
		if($ttl>0)
		{
			if((time()-filemtime($file))>$ttl)
			{
				@unlink($file);
				return false;
			}
		}
		if($echo)
		{
			@readfile($file);
			return true;
		}
		else return @file_get_contents($file);
	}

	public static function getTimeout()
	{
		return self::$timeout;
	}

	public static function set($class,$entity,$ttl,$value,$echo=false,$ext="")
	{
		if(!$class && !$entity)return false;
		$valDir=md5($class.$entity);
		$ttl+=0;
		if($ttl<0)$ttl=self::$timeout;
		$dir=self::$dir."/".$class."/".$valDir;
		if(!@file_exists($dir))@mkdir($dir,0755,true);
		$file=$dir."/index".($ext?"":(".html")).".".$ttl.".cache".($ext?(".".$ext):"");
		$hash="".time()."-"."-".mt_rand(111,999);
		@file_put_contents($file.".".$hash,$value);
		@chmod($file,0755);
		@rename($file.".".$hash,$file);
		@chmod($file,0755);
		@unlink($file.".".$hash);
		if($echo)echo $value;
		return $file;
	}
}
?>
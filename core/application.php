<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
include FLEX_APP_DIR."/const.php";
/* ---->>> development mode helper ---- */
if(FLEX_APP_DIR_SRC && isset($_GET["feh-rsc-get"]))
{
	$nf="HTTP/1.0 404 Not Found";
	$type=$_GET["feh-rsc-get"];
	$path=isset($_GET["path"])?$_GET["path"]:"";
	if($path)
	{
		$query=parse_url($path);
		$query=$query["query"];
		$path=pathinfo($path);
	}
	else
	{
		header($nf);
		die();
	}
	$ct="";
	$ina=false;
	if($type=="auto")
	{
		switch($path["extension"])
		{
			case "gif":
			case "png":
			case "jpg":
			case "jpeg":
				$type="img";
				$ina=true;
				break;
			case "js":
			case "json":
				$type="js";
				$ina=true;
				break;
			case "css":
				$type="css";
				$ina=true;
				break;
		}
	}
	switch($type)
	{
		case "img":
			//if(!in_array($path["extension"],array("php","js","html","htm","css")))
			if($ina || in_array($path["extension"],array("gif","png","jpg","jpeg")))$ct="image/".($path["extension"]=="jpg"?"jpeg":$path["extension"]);
			break;
		case "js":
			if($ina || in_array($path["extension"],array("js","json")))$ct="application/".($path["extension"]=="json"?"json; ":"javascript; ")."charset=utf-8";
			break;
		case "css":
			if($ina || in_array($path["extension"],array("css")))$ct="text/css";
			break;
	}
	if($ct)
	{
		$path["dirname"]=trim($path["dirname"],"/");
		$parts=explode("/",$path["dirname"]);
		//helpers' and tools' (if needed) directories must be configurated in apache host definition!!!
		if($parts[0]==FLEX_APP_DIR)$filepath=FLEX_APP_DIR_SRC.".core/";
		else $filepath=FLEX_APP_DIR_SRC.".classes/.".($parts[0]==FLEX_APP_DIR_TPL?$parts[2]:$parts[1])."/";
		$filepath.=$path["dirname"]."/".$path["basename"].($query?("?".$query):"");
		if(@file_exists($filepath))
		{
			header("Content-Type: ".$ct);
			echo @file_get_contents($filepath);
		}
		else header($nf);
	}
	else header($nf);
	die();
}
/* ----<<< development mode helper ---- */
session_start();
if(isset($_GET["fe-app-restart"])) // && ($_GET["fe-app-restart"]==session_id())
{
	session_unset();
	session_destroy();
	session_start();
}
if(!isset($_SESSION["FLEX_APP_STARTED"]))$_SESSION["FLEX_APP_STARTED"]=microtime();
$_SESSION["FLEX_APP_RENEWED"]=microtime(true);
ini_set("magic_quotes_runtime",0);
ini_set("magic_quotes_gpc",0);
ini_set("display_errors","On");
error_reporting(E_ALL);
date_default_timezone_set("Europe/Moscow");
final class _a
{
	private static $c	= NULL;

	public static function core()
	{
		return self::$c;
	}

	public static function _render()
	{
		if(isset($_SESSION["core"]))
		{
			self::$c=unserialize($_SESSION["core"]);
			if(!is_object(self::$c))self::$c=new core();
		}
		else self::$c=new core();
		self::$c->_init();
		self::$c->_exec();
		self::$c->_render();
		self::$c->_sleep();
		$_SESSION["core"]=serialize(self::$c);
	}
}
_a::_render();
?>
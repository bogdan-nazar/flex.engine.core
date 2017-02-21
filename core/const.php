<?
namespace FlexEngine
{
defined("FLEX_APP") or die("Forbidden.");
if(!defined("PHP_VERSION_ID"))
{
    $version=explode(".",PHP_VERSION);
    define("PHP_VERSION_ID",($version[0]*10000+$version[1]*100+$version[2]));
}
if(PHP_VERSION_ID<50207)
{
    define("PHP_MAJOR_VERSION",$version[0]);
    define("PHP_MINOR_VERSION",$version[1]);
    define("PHP_RELEASE_VERSION",$version[2]);
}
//application directories
$path=str_replace("\\","/",dirname(__FILE__));
if(strpos($path,"/flex.engine/.sources/")!==false)
{
	$path=explode("/.sources/",$path);
	$path=$path[0]."/.sources/";
}
else $path="";
define("FLEX_APP_DIR_ROOT",str_replace("index.php","",str_replace($_SERVER["DOCUMENT_ROOT"],"",$_SERVER["SCRIPT_FILENAME"])),false);
define("FLEX_APP_DIR_DAT","data",false);
define("FLEX_APP_DIR_HLP","helpers",false);
define("FLEX_APP_DIR_MOD","classes",false);
define("FLEX_APP_DIR_SRC",$path,false);
define("FLEX_APP_DIR_TPL","templates",false);
//custom directories
define("FLEX_APP_DIR_HLP_ACEED",FLEX_APP_DIR_HLP."/ace",false);
define("FLEX_APP_DIR_HLP_CKED",FLEX_APP_DIR_HLP."/ckeditor",false);
define("FLEX_APP_DIR_HLP_CKED4",FLEX_APP_DIR_HLP."/ckeditor4",false);
define("FLEX_APP_DIR_TOOL","tools",false);
define("FLEX_APP_OUT_STR","flex-engine-",false);
//session_destroy();

function __autoload($classname)
{
	//normalizing names
	$cname=str_replace(__NAMESPACE__."\\","",$classname);
	$classname=__NAMESPACE__."\\".$cname;
	//trying various pathes to include
	@include_once(FLEX_APP_DIR."/".FLEX_APP_DIR_MOD."/class.".$cname."/".$cname.".php");
	if(@class_exists($classname,false))return true;
	if(defined("ADMIN_MODE"))@include_once(FLEX_APP_DIR_MOD."/class.".$cname."/".((($cname!=ADMIN_MODE)?"admin/":"").$cname).".php");
	else
	{
		if(FLEX_APP_DIR_SRC)
		{
			@include_once(FLEX_APP_DIR_SRC.".classes/.".$cname."/".FLEX_APP_DIR_MOD."/class.".$cname."/".$cname.".php");
			if(@class_exists($classname,false))return true;
			@include_once(FLEX_APP_DIR_SRC.".classes/.".$cname."/".FLEX_APP_DIR_MOD."/class.".$cname.".php");
			if(@class_exists($classname,false))return true;
		}
		@include_once(FLEX_APP_DIR_MOD."/class.".$cname."/".$cname.".php");
		if(@class_exists($classname,false))return true;
		@include_once(FLEX_APP_DIR_MOD."/class.".$cname.".php");
		if(@class_exists($classname,false))return true;
	}
	return false;
}
\spl_autoload_register("FlexEngine\\__autoload");
}
?>
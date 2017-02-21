<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
final class lang
{
	private static $_runStep	=	0;
	private static $class		=	__CLASS__;
	private static $items		=	false;
	private static $lang		=	"ru-Ru";
	private static $langDef		=	"ru-Ru";
	private static $langs		=	array("ru-Ru");
	private static $nameData	=	"data";
	private static $nameSes		=	"";
	private static $nameVar		=	"code";

	private static function _set($l)
	{
		if(in_array($l,self::$langs))
		{
			setcookie(self::$nameSes,$l,time()+3600*24*365*2,"/");
			$_SESSION[self::$nameSes]=$l;
			self::$lang=$l;
		}
	}

	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
	}

	public static function _init($abnormal=false)
	{
		if(self::$_runStep)return;
		self::$_runStep++;
		if(strpos(self::$class,"\\")!==false)
		{
			$cl=explode("\\",self::$class);
			self::$class=$cl[count($cl)-1];
		}
		self::$nameVar=self::$class."-".self::$nameVar;
		self::$nameSes=FLEX_APP_NAME."-".self::$nameVar;
		$data=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$nameData.".php";
		if(@file_exists($data))
		{
			include $data;
			if(isset($items) && is_array($items))
			{
				$langs=array_keys($items);
				$l=count($langs);
				for($c=0;$c<$l;$c++)
				{
					self::$langs[]=$langs[$c];
					self::$items[$langs[$c]]=$items[$langs[$c]];
				}
			}
		}
		if(!$abnormal)
		{
			if(isset($_POST[self::$nameVar]))
			{
				if(self::_set($_POST[self::$nameVar]))
				{
					header("Location: ".$_SERVER["HOST_NAME"].$_SERVER["REQUEST_URI"]);
					die("Redirecting...");
				}
			}
		}
		if(isset($_SESSION[self::$nameSes]))self::_set($_SESSION[self::$nameSes]);
		else
		{
			if(isset($_COOKIE[self::$nameSes]))self::_set($_COOKIE[self::$nameSes]);
		}
	}

	public static function _sleep(){}

	public static function _($s,$args)
	{
		if(is_string($s))return $s;
		$msg="[Unknown message]";
		$msgs=false;
		if(isset(self::$items[self::$lang]))
		{
			$msgs=&self::$items[self::$lang];
			if(isset($msgs[$s]))$msg=$msgs[$s];
		}
		else
		{
			if(isset(self::$items[self::$langDef]))
			{
				$msgs=&self::$items[self::$langDef];
				if(isset($msgs[$s]))$msg=$msgs[$s];
			}
		}
		if(count($args))$msg=vsprintf($msg,$args);
		return $msg;
	}

	public static function cur()
	{
		return self::$lang;
	}

	public static function def()
	{
		return self::$langDef;
	}

	public static function extend($items)
	{
		if(isset($items) && is_array($items))
		{
			$langs=array_keys($items);
			$l=count($langs);
			for($c=0;$c<$l;$c++)
			{
				$lang=$langs[$c];
				if(!is_array($items[$lang]))continue;
				if(isset(self::$items[$lang]))self::$items[$lang]=array_merge(self::$items[$lang],$items[$lang]);
				else self::$items[$lang]=$items[$lang];
			}
		}
	}

	public static function index()
	{
		return count(self::$items["ru-Ru"]);
	}

	public static function itemsDef($items)
	{
		if(self::$items)return;
		self::$items=&$items;
	}
}
//идентификаторы строк
$i=0;
define("LANG_CONTENT_DELETED",$i++);
define("LANG_CONTENT_P404",$i++);
define("LANG_CORE_CRIT_CFG",$i++);
define("LANG_CORE_CRIT_CFG_DATA",$i++);

define("LANG_MSGR_BTN_CAP_CANCEL",$i++);
define("LANG_MSGR_BTN_CAP_CLOSE",$i++);
define("LANG_MSGR_BTN_CAP_CONFIRM",$i++);
define("LANG_MSGR_DALERT_TITLE_INF",$i++);
define("LANG_MSGR_DALERT_TITLE_ERR",$i++);
define("LANG_MSGR_DALERT_TITLE_WRN",$i++);
define("LANG_MSGR_DCONFIRM_TITLE",$i++);
define("LANG_MSGR_MSG_TITLE",$i++);
//строки по умолчанию
lang::itemsDef(array(
	"ru-Ru"	=> array(
		LANG_CONTENT_DELETED				=>	"Страница удалена или перемещена",
		LANG_CONTENT_P404					=>	"Страница не найдена",
		LANG_CORE_CRIT_CFG					=>	"Критическая ошибка: файл конфигурации не найден.",
		LANG_CORE_CRIT_CFG_DATA				=>	"Критическая ошибка: конфигурационные данные не найдены.",

		LANG_MSGR_BTN_CAP_CANCEL			=>	"Отмена",
		LANG_MSGR_BTN_CAP_CLOSE				=>	"Закрыть",
		LANG_MSGR_BTN_CAP_CONFIRM			=>	"Подтвердить",
		LANG_MSGR_DALERT_TITLE_INF			=>	"Информация",
		LANG_MSGR_DALERT_TITLE_ERR			=>	"Ошибка",
		LANG_MSGR_DALERT_TITLE_WRN			=>	"Внимание",
		LANG_MSGR_DCONFIRM_TITLE			=>	"Подтвердите действие",
		LANG_MSGR_MSG_TITLE					=>	"Сообщение системы"
	),
));
//ярлык для быстрого доступа к функции перевода
function _t($s,$args=array()){return lang::_($s,$args);}
?>

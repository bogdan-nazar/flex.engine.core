<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
final class lang
{
	private static $class	=	__CLASS__;
	private static $items	=	array(
		"en-Us"		=>	array (
		)
	);
	private static $lang	=	"en-Us";
	private static $langs	=	array("ru-Ru","en-Us");

	private static function _getLang($l)
	{
		self::$lang=$l;
	}

	private static function _setLang($l)
	{
		setcookie("langLang",$l,time()+3600*24*365*2,"/");
		$_SESSION["langLang"]=$l;
		self::$lang=$l;
	}

	public static function _reder()
	{

	}

	public static function _exec()
	{
	}

	public static function _init()
	{
		if(strpos(self::$class,"\\")!==false)
		{
			$cl=explode("\\",self::$class);
			self::$class=$cl[count($cl)-1];
		}
		if(isset($_POST["langLang"]))
		{
			if(in_array($_POST["langLang"],self::$langs))
			{
				self::_setLang($_POST["langLang"]);
				header("Location: ".$_SERVER["HOST_NAME"].$_SERVER["REQUEST_URI"]);
				die("Redirecting...");
			}
		}
		if(isset($_SESSION["langLang"]))
			self::_getLang($_SESSION["langLang"]);
		else
		{
			if(isset($_COOKIE["langLang"]))
			{
				if(in_array($_COOKIE["langLang"],self::$langs))
					self::_getLang($_COOKIE["langLang"]);
			}
		}
	}

	public static function _sleep()
	{
	}

	public static function _($s)
	{
		if(isset(self::$items[self::$lang][$s]))
			return self::$items[self::$lang][$s];
		else
			return $s;
	}

	public static function _renderBtn()
	{
		if(self::$lang=="ru-Ru")
			$img="en-Us";
		else
			$img="ru-Ru";
?>
	<script type="text/javascript">
		function langChange(l) {
			var d = document.getElementById("langLang");
			var s = document.getElementById("langSmt");
			d.value = l;
			s.click();
		}
	</script>
	<div id="langBtn" style="float:left;margin-right:10px;width:24px;height:18px;background:url('img/flag-<?=$img?>.jpg') center no-repeat;cursor:pointer;" onclick="langChange('<?=$img?>')">
		<form action="" method="post">
			<input type="hidden" id="langLang" name="filemanLang" value="" />
			<input type="submit" id="langSmt" value="_" style="display:none;" />
		</form>
	</div>
<?
	}

	public static function cur()
	{
		return self::$lang;
	}

	public static function def()
	{
		return"ru-Ru";
	}
}
function _t($s){return lang::_($s);}
?>

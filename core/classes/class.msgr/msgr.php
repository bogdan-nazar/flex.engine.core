<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");

define("MSGR_EMAIL_ADMIN","mail-admin",false);
define("MSGR_EMAIL_DEVEL","mail-developer",false);
define("MSGR_EMAIL_SUPRT","mail-support",false);
define("MSGR_MAILER_DOMAIN","mailer-domain",false);

define("MSGR_SHOW_DIALOG",0,false);
define("MSGR_SHOW_CONSOLE",1,false);

define("MSGR_TYPE_INF",0,false);
define("MSGR_TYPE_WRN",1,false);
define("MSGR_TYPE_ERR",2,false);

final class msgr
{
	private static $_runStep	=	0;
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $config		=	array(
		MSGR_EMAIL_ADMIN		=>	"adm@flexengine.ru",
		MSGR_EMAIL_DEVEL		=>	"dev@flexengine.ru",
		MSGR_EMAIL_SUPRT		=>	"sup@flexengine.ru",
		MSGR_MAILER_DOMAIN		=>	"flexengine.ru"
	);
	private static $displayed	=	1;
	private static $errorMsg	=	array(
	 		"class"	=> "unknown class",
	 		"func"	=> "unknown function",
	 		"line"	=> 0,
	 		"msg"	=>	"Unknown error",
	 		"ext"	=>	"Probably wrong arguments was passed?"
	);
	private static $errors		=	array();
	private static $errorsQueue	=	array();
	private static $items		=	array();
	private static $session		=	array();

	/**
	* Чтение данных из сессии
	*/
	private static function _sessionRead()
	{
		//распаковываем сессию
		$sesName=FLEX_APP_NAME."-".self::$class."-data";
		if(isset($_SESSION[$sesName]))self::$session=unserialize($_SESSION[$sesName]);
		//убеждаемся, что сессия была корректно сохранена
		if(!is_array(self::$session))self::$session=array();
		//читаем данные
		if(isset(self::$session["items"]) && is_array(self::$session["items"]))self::$items=&self::$session["items"];
	}

	/**
	* Запись данных в сессию
	*/
	private static function _sessionWrite()
	{
		//убеждаемся, что сессия не была повреждена во время выполнения
		if(!is_array(self::$session))self::$session=array();
		//записываем данные
		if(count(self::$items))self::$session["items"]=&$items;
		//пакуем сессию
		$_SESSION[FLEX_APP_NAME."-".self::$class."-data"]=serialize(self::$session);
	}

	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
		if(!self::$c->silent())
		{
			$data=array("msgs"=>array(
				"btnCapCancel"		=>	_t(LANG_MSGR_BTN_CAP_CANCEL),
				"btnCapClose"		=>	_t(LANG_MSGR_BTN_CAP_CLOSE),
				"btnCapConfirm"		=>	_t(LANG_MSGR_BTN_CAP_CONFIRM),
				"dlgAlertTilleInf"	=>	_t(LANG_MSGR_DALERT_TITLE_INF),
				"dlgAlertTitleErr"	=>	_t(LANG_MSGR_DALERT_TITLE_ERR),
				"dlgAlertTitleWarn"	=>	_t(LANG_MSGR_DALERT_TITLE_WRN),
				"dlgConfirmTitle"	=>	_t(LANG_MSGR_DCONFIRM_TITLE)
			));
			self::$c->addConfig(self::$class,$data,true);
			render::addStyle(self::$class,"",true);
			render::addScript(self::$class,"",true);
		}
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
		self::$config=self::$c->config(self::$class);
		if(!isset(self::$config[MSGR_MAILER_DOMAIN]) || !self::$config[MSGR_MAILER_DOMAIN])
		{
			$d=explode(".",self::$c->domainCurrent());
			if(count($d) && ($d[0]=="www"))$d=array_shift($d);
			self::$config[MSGR_MAILER_DOMAIN]=implode(".",$d);
		}
		//читаем сессию
		self::_sessionRead();
	}

	public static function _render()
	{
?>
<div id="<?=self::$class?>-items" style="display:none;">
<?
		$len=count(self::$items);
		if(!$len)
		{
			echo"</div>\n";
			return;
		}
		for($cnt=0;$cnt<$len;$cnt++)
		{
			$msg=self::$items[$cnt]["cont"];
			$tp=self::$items[$cnt]["type"];
			switch($tp)
			{
				case MSGR_TYPE_WRN:
					$tp="wrn";
					break;
				case MSGR_TYPE_ERR:
					$tp="err";
					break;
				default:
					$tp="inf";
			}
			$show=self::$items[$cnt]["show"];
			switch($show)
			{
				case MSGR_SHOW_DIALOG:
					$show="dialog";
					break;
				default:
					$show="console";
			}

?>
		<div id="<?=self::$class?>-item-<?=($cnt+1)?>" data-title="<?=_t(LANG_MSGR_MSG_TITLE)?>" data-msgtype="<?=$tp?>" data-showtype="<?=$show?>"><?=$msg?></div>
<?
		}
?></div><?
		self::$items=array();
	}

	public static function _sleep()
	{
		//сохраняем сессию
		self::_sessionWrite();
	}

	public static function _template()
	{
		return self::$config["template"];
	}

	/**
	* Регистрация сообщения для последующего отображения пользователю.
	* Сообщения сохраняются в сессии, до тех пор, пока не будут показаны методом msgr::show().
	* Варианты $mtype: MSGR_TYPE_INF (по умолчанию), MSGR_TYPE_WRN, MSGR_TYPE_ERR).
	*
	* @param string $msg
	* @param int $mtype
	*/
	public static function add($msg,$mtype=MSGR_TYPE_INF,$mshow=MSGR_SHOW_DIALOG)
	{
		if(self::$c->silent())return false;
		$len=count(self::$items);
		self::$items[$len]["cont"]=$msg;
		self::$items[$len]["type"]=$mtype;
		self::$items[$len]["show"]=$mshow;
		return true;
	}

	/**
	* Возвращает параметр конфига
	*
	* @param string $par
	*
	* @return mixed $value
	*/
	public static function config($par)
	{
		if(isset(self::$config[$par]))return self::$config[$par];
		else return "";
	}

	/**
	* Возвращает данные по последней зарегистрированной ошибке ($count==1)
	* или массив данных по ошибкам начиная со $start, количеством $count ошибок
	* опционально возвращаются ошибки только для указанного модуля ($class)
	* Формат данных об ошибке: array(
	* 		"class"	=> "myclassname",
	* 		"func"	=> "mfunction",
	* 		"line"	=> 1,
	* 		"msg"	=>	"error msg"
	* );
	*
	* @param int $count
	* @param int $start
	* @param string $class
	*
	* @returns array of array/false
	*/
	public static function errorGet($count=1,$start=-1,$class="")
	{
		$qlen=count(self::$errorsQueue);
		if(!$qlen)return self::$errorMsg;
		if(!$count)$count=1;
		if(!$class)
		{
			if($count>$qlen)$count=$qlen;
			if($start==-1)$start=$qlen-1;
			else
			{
				if($start<0 || ($start>($qlen-1)))$start=$qlen-1;
			}
			$rarr=array();
			$fetched=0;
			for($cnt=$start;$cnt<$qlen;$cnt++)
			{
				$rarr[]=array_merge(array(),self::$errorsQueue[$cnt]);
				$fetched++;
				if($fetched>=$count)break;
			}
			if(!$fetched)return self::$errorMsg;
			else return $rarr;
		}
		else
		{
			if(!isset(self::$errors[$class]))return self::$errorMsg;
			$clen=count(self::$errors[$class]);
			if(!$clen)return self::$errorMsg;
			if($count>$clen)$count=$clen;
			if($start==-1)$start=$clen-1;
			if($start<0 || ($start>($clen-1)))$start=$clen-1;
			$rarr=array();
			$fetched=0;
			for($cnt=$start;$cnt<$clen;$cnt++)
			{
				$rarr[]=array_merge(array(),self::$errors[$class][$cnt]);
				$fetched++;
				if($fetched>=$count)break;
			}
			if(!$fetched)return self::$errorMsg;
			else return $rarr;
		}
	}

	/**
	* Возвращает индекс последней ошибки
	*/
	public static function errorLast()
	{
		return count(self::$errorsQueue)-1;
	}

	/**
	* Регистрация системной ошибки.
	* При отсутствии данных по вызывавшему модулю,
	* функция пытается получить их самостоятельно.
	* Ошибки сохраняются в классе только на время выполнения запроса.
	*
	* @param string $msg
	* @param string $class
	* @param string $func
	* @param mixed $line
	*
	* @returns int $errorIndex/false
	*/
	public static function errorLog($msg,$show=false,$class="",$func="",$line="",$ext="")
	{
		if(!$msg)return false;
		if($show)self::add($msg,MSGR_TYPE_ERR,MSGR_SHOW_CONSOLE);
		$dd=false;
		if(!$class || !$func || !$line)
		{
			if(function_exists("debug_backtrace"))
			{
				$dd=@debug_backtrace();
				if(is_array($dd) && isset($dd[0]))
				{
					if(!$class)
					{
						if(isset($dd[0]["class"]))$class=$dd[0]["class"];
						else
						{
							$file=$dd[0]["file"];
							$pi=pathinfo($file);
							$class=$pi["basename"];
						}
					}
					if(!$func)
					{
						if(isset($dd[0]["function"]))$func=$dd[0]["function"];
					}
					if(!$line)
					{
						if(isset($dd[0]["line"]))$line="".$dd[0]["line"];
					}
				}
			}
			if(!$class)$class="unknown class";
			if(!$func)$class="unknown function";
			if(!$line)$class="no line";
		}
		$error=array();
		$error["class"]=$class;
		$error["func"]=$func;
		$error["line"]=$line;
		$error["msg"]=$msg;
		$error["ext"]=$ext;
		if(!isset(self::$errors[$class]))self::$errors[$class]=array();
		$l=count(self::$errors[$class]);
		self::$errors[$class][]=&$error;
		self::$errorsQueue[]=&$error;
		return (count(self::$errorsQueue)-1);
	}

	public static function lastMsg()
	{
		$len=count(self::$items);
		if(!$len)return "";
		return self::$items[$len-1]["cont"];
	}

	public static function mailSend($mt,$ms,$mb,$mf=false,$fname="")
	{
		if($mt=="")$mt=self::$config[MSGR_EMAIL_SUPRT];
		$mse="=?utf-8?b?".base64_encode($ms)."?=";
		$headers="From:\"Robot\" <robot@".self::$config[MSGR_MAILER_DOMAIN].">\nReply-To:\"Support\" <".self::$config[MSGR_EMAIL_SUPRT].">\n";
		if($mf && file_exists($mf))
		{
			$boundary="---";
			$headers.="Content-Type: multipart/mixed; boundary=\"$boundary\"";
			$body=$mb;
			$mb="--$boundary\n";
			$mb.="Content-type: text/html; charset='utf-8'\n";
			$mb.="Content-Transfer-Encoding: quoted-printablenn";
			$mb.="Content-Disposition: attachment; filename==?utf-8?B?".base64_encode("message.html")."?=\n\n";
			$mb.=$body."\n";
			$mb.="--$boundary\n";
			$file=fopen($mf,"r"); //Открываем файл
			$text=fread($file,filesize($mf)); //Считываем весь файл
			fclose($file); //Закрываем файл
			$mb.="Content-Type: application/octet-stream; name==?utf-8?B?".base64_encode($fname)."?=\n";
			$mb.="Content-Transfer-Encoding: base64\n";
			$mb.="Content-Disposition: attachment; filename==?utf-8?B?".base64_encode($fname)."?=\n\n";
			$mb.=chunk_split(base64_encode($text))."\n";
			$mb.="--".$boundary."--\n";
		}
		else
		{
			$headers="MIME-Version: 1.0\n";
			$headers.="Content-Type: text/html; charset=utf8\n";
		}
		$headers.="X-Mailer: PHP ".phpversion();
		$res=@mail($mt,$mse,$mb,$headers);
		if(!$res)
		{
			$msg="PHP mail() error: can't send the mail [".$ms."] to &lt;".$mt."&gt!";
			if(self::$c->silent())self::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			else self::add($msg,MSGR_TYPE_ERR);
		}
		return $res;
	}
}
?>
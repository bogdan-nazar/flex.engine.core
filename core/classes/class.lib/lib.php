<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
$c=0;
define("LIB_STR_TYPE_ADDR",$c,false);$c++;
define("LIB_STR_TYPE_DECI",$c,false);$c++;
define("LIB_STR_TYPE_FILE",$c,false);$c++;
define("LIB_STR_TYPE_HASH",$c,false);$c++;
define("LIB_STR_TYPE_MODN",$c,false);$c++;
define("LIB_STR_TYPE_NAME",$c,false);$c++;
define("LIB_STR_TYPE_PASS",$c,false);$c++;
define("LIB_STR_TYPE_PHON",$c,false);$c++;
define("LIB_STR_TYPE_SENT",$c,false);$c++;
define("LIB_STR_TYPE_USER",$c,false);$c++;
define("LIB_STR_TYPES",$c,false);

final class lib
{
	private static $c 					=	null;
	private static $class				=	__CLASS__;
	private static $lastMsg				=	"";
	private static $mquotes_gpc			=	-1;
	private static $mquotes_runtime		=	-1;

	public static function _exec()
	{
		render::addScript(self::$class,self::$class,true);
	}

	public static function _init()
	{
		if(strpos(self::$class,"\\")!==false)
		{
			$cl=explode("\\",self::$class);
			self::$class=$cl[count($cl)-1];
		}
		self::$c=_a::core();
	}

	public static function addZeros($digs,$num)
	{
		if(!is_int($digs))return $num;
		if(is_int($num))$num="".$num;
		$len=strlen($num);
		if($len>$digs)return $num;
		for($cnt=0;$cnt<($digs-$len);$cnt++)$num="0".$num;
		return $num;
	}

	public static function bomTrim($str)
	{
		if(substr($str,0,3)==pack("CCC",0xef,0xbb,0xbf))$str=substr($str,3);
   		return $str;
	}

	public static function dt($d="",$full=false,$sect="-")
	{
		if(!$d)return date("Y{$sect}m{$sect}d".($full?" H:i:s":""));
		else
		{
			if(self::validDtRus($d))return substr($d,6,4).$sect.substr($d,3,2).$sect.substr($d,0,2).($full?" ".substr($d,11,8):"");
			else return date("Y{$sect}m{$sect}d".($full?" H:i:s":""));
		}
	}

	/**
	* Дата в русском формате
	*
	* @param string $d [дата YYYY-MM-DD HH:MM:SS]
	* @param boolean $full [включить время]
	* @param string $sect [использовать разделитель]
	* @return string
	*/
	public static function dtR($d="",$full=false,$sect=".")
	{
		if(!$d)return date("d{$sect}m{$sect}Y".($full?" H:i:s":""));
		else
		{
			if(self::validDt($d))return substr($d,8,2).$sect.substr($d,5,2).$sect.substr($d,0,4).($full?" ".substr($d,11,8):"");
			else return date("d{$sect}m{$sect}Y".($full?" H:i:s":""));
		}
	}

	/**
	* Дата в русском вербальном формате
	*
	* @param string $d [дата YYYY-MM-DD HH:MM:SS]
	* @return string
	*/
	public static function dtRW($d="")
	{
		$mon=array("января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря");
		if(!$d || !self::validDt($d))	return date("d")." ".$mon[0+date("m")-1]." ".date("Y");
		else return substr($d,8,2)." ".$mon[0+substr($d,5,2)]." ".substr($d,0,4);
	}

	/**
	* Конвертация массива в JSON-объект
	*
	* @param array $i
	* @param bool $forceObject
	*
	* @return string $json
	*/
	public static function jsonMake($a,$forceObj=false)
	{
		if(!is_array($a))return"[]";
		$res=array();
		if(!$forceObj)
		{
			$keys=array_keys($a);
			foreach($keys as $key)
				if(!is_int($key))
				{
					$forceObj=true;
					break;
				}
		}
		if($forceObj)
		{
			$sq1="{";
			$sq2="}";
		}
		else
		{
			$sq1="[";
			$sq2="]";
		}
		foreach($a as $key=>$val)
		{
			$tp=gettype($val);
			switch($tp)
			{
				case "boolean":
					$val=$val?"true":"false";
					break;
				case "string":
					$val="\"".self::jsonPrepare($val)."\"";
					break;
				case "array":
					$val=self::jsonMake($val,$forceObj);
					break;
				default:
			}
			$res[]=($forceObj?("\"".$key."\":"):"").$val;
		}
		return $sq1.implode(",",$res).$sq2;
	}

	/**
	* Подготовка строки для использования в JSON
	*
	* @param string $s
	*
	* @return string $jsonString
	*/
	public static function jsonPrepare($s)
	{
		$s=preg_replace("/[\r\n\t]/im"," ",$s);
		$s=preg_replace("/[ ]+/"," ",$s);
		$s=str_replace("\\","\\\\",$s);
		$s=str_replace("\"","\\\"",$s);
		return $s;
	}

	public static function lastMsg()
	{
		$msg=self::$lastMsg;
		self::$lastMsg="";
		return $msg;
	}

	//http://php.net/manual/ru/function.mime-content-type.php
	public static function MIMEType($filename)
	{
		@preg_match("|\.([a-z0-9]{2,4})$|i",$filename,$fileSuffix);
		switch(strtolower($fileSuffix[1]))
		{
			case "js" :
				return "application/x-javascript";
			case "json" :
				return "application/json";
			case "jpg" :
			case "jpeg" :
			case "jpe" :
				return "image/jpg";
			case "png" :
			case "gif" :
			case "bmp" :
			case "tiff" :
				return "image/".strtolower($fileSuffix[1]);
			case "css" :
				return "text/css";
			case "xml" :
				return "application/xml";
			case "doc" :
			case "docx" :
				return "application/msword";
			case "xls" :
			case "xlt" :
			case "xlm" :
			case "xld" :
			case "xla" :
			case "xlc" :
			case "xlw" :
			case "xll" :
				return "application/vnd.ms-excel";
			case "ppt" :
			case "pps" :
				return "application/vnd.ms-powerpoint";
			case "rtf" :
				return "application/rtf";
			case "pdf" :
				return "application/pdf";
			case "html" :
			case "htm" :
			case "php" :
				return "text/html";
			case "txt" :
				return "text/plain";
			case "mpeg" :
			case "mpg" :
			case "mpe" :
				return "video/mpeg";
			case "mp3" :
				return "audio/mpeg3";
			case "wav" :
				return "audio/wav";
			case "aiff" :
			case "aif" :
				return "audio/aiff";
			case "avi" :
				return "video/msvideo";
			case "wmv" :
				return "video/x-ms-wmv";
			case "mov" :
				return "video/quicktime";
			case "zip" :
				return "application/zip";
			case "tar" :
				return "application/x-tar";
			case "swf" :
				return "application/x-shockwave-flash";
			default :
				if(function_exists("mime_content_type"))
				{
					$fileSuffix=mime_content_type($filename);
				}
				return "unknown/".trim($fileSuffix[0],".");
		}
	}

	public static function dtMonByNum($dt,$var=1)
	{
		$t=strtotime($dt);
		if($t===false)return "unknown";
		$mon=0+substr($dt,5,2);
		$months=array(
			0=>array("01","январь","января"),
			1=>array("02","февраль","февраля"),
			2=>array("03","март","марта"),
			3=>array("04","апрель","апреля"),
			4=>array("05","май","мая"),
			5=>array("06","июнь","июня"),
			6=>array("07","июль","июля"),
			7=>array("08","август","августа"),
			8=>array("09","сентябрь","сентября"),
			9=>array("10","октябрь","октября"),
			10=>array("11","ноябрь","ноября"),
			11=>array("12","декабрь","декабря"),
		);
		return $months[$mon-1][$var];
	}

	public static function mquotes_gpc()
	{
		if(self::$mquotes_gpc==-1)
		{
			self::$mquotes_gpc=@function_exists("get_magic_quotes_gpc");
			if(self::$mquotes_gpc)self::$mquotes_gpc=@get_magic_quotes_gpc();
		}
		return self::$mquotes_gpc;
	}

	public static function mquotes_runtime()
	{
		if(self::$mquotes_runtime==-1)
		{
			self::$mquotes_runtime=@function_exists("get_magic_quotes_runtime");
			if(self::$mquotes_runtime)self::$mquotes_runtime=@get_magic_quotes_runtime();
		}
		return self::$mquotes_runtime;
	}

	public static function rnd($min=1000000, $max=9999999)
	{
		list($usec,$sec)=explode(" ",microtime());
		mt_srand((float)$sec+((float)$usec*100000));
		return mt_rand($min,$max);
	}

	/**
	* Рекурсивное удаление папки
	*
	* @param string $dir
	*/
	public static function rrmdir($dir)
	{
		if(is_dir($dir))
		{
			$objects=scandir($dir);
			foreach($objects as $object)
			{
				if($object!="." && $object!="..")
				{
					if(filetype($dir."/".$object)=="dir")self::rrmdir($dir."/".$object);
					else unlink($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	/**
	* Массив часовых поясов
	*
	* @param string $lng
	* @return array
	*/
	public static function timeZonesList($lng="ru-Ru")
	{
		$tz=array();
		$tz[]=array(
				"val"=>"-12",
				"title"=>array(
					"ru-Ru"=>"Меридиан смены дат (запад)"
					)
				);
		$tz[]=array(
				"val"=>"-11",
				"title"=>array(
					"ru-Ru"=>"Самоа"
					)
				);
		$tz[]=array(
				"val"=>"-10",
				"title"=>array(
					"ru-Ru"=>"Гавайи"
					)
				);
		$tz[]=array(
				"val"=>"-9",
				"title"=>array(
					"ru-Ru"=>"Аляска"
					)
				);
		$tz[]=array(
				"val"=>"-8",
				"title"=>array(
					"ru-Ru"=>"Лос-Анжелес"
					)
				);
		$tz[]=array(
				"val"=>"-7",
				"title"=>array(
					"ru-Ru"=>"Денвер"
					)
				);
		$tz[]=array(
				"val"=>"-6",
				"title"=>array(
					"ru-Ru"=>"Чикаго"
					)
				);
		$tz[]=array(
				"val"=>"-5",
				"title"=>array(
					"ru-Ru"=>"Нью-Йорк"
					)
				);
		$tz[]=array(
				"val"=>"-4",
				"title"=>array(
					"ru-Ru"=>"Каракас"
					)
				);
		$tz[]=array(
				"val"=>"-3",
				"title"=>array(
					"ru-Ru"=>"Буэнос-Айрес"
					)
				);
		$tz[]=array(
				"val"=>"-2",
				"title"=>array(
					"ru-Ru"=>"Среднеатлантическое время"
					)
				);
		$tz[]=array(
				"val"=>"-1",
				"title"=>array(
					"ru-Ru"=>"Азорские острова"
					)
				);
		$tz[]=array(
				"val"=>"0",
				"title"=>array(
					"ru-Ru"=>"Лондон"
					)
				);
		$tz[]=array(
				"val"=>"+1",
				"title"=>array(
					"ru-Ru"=>"Берлин, Мадрид, Париж"
					)
				);
		$tz[]=array(
				"val"=>"+2",
				"title"=>array(
					"ru-Ru"=>"Киев, Минск, Калининград"
					)
				);
		$tz[]=array(
				"val"=>"+3",
				"title"=>array(
					"ru-Ru"=>"Москва, Санкт-Петербург, Волгоград"
					)
				);
		$tz[]=array(
				"val"=>"+4",
				"title"=>array(
					"ru-Ru"=>"Самара, Баку, Ереван"
					)
				);
		$tz[]=array(
				"val"=>"+5",
				"title"=>array(
					"ru-Ru"=>"Екатеринбург, Ташкент"
					)
				);
		$tz[]=array(
				"val"=>"+6",
				"title"=>array(
					"ru-Ru"=>"Новосибирск, Омск"
					)
				);
		$tz[]=array(
				"val"=>"+7",
				"title"=>array(
					"ru-Ru"=>"Красноярск, Бангкок"
					)
				);
		$tz[]=array(
				"val"=>"+8",
				"title"=>array(
					"ru-Ru"=>"Иркутск, Пекин"
					)
				);
		$tz[]=array(
				"val"=>"+9",
				"title"=>array(
					"ru-Ru"=>"Чита, Якутск, Токио"
					)
				);
		$tz[]=array(
				"val"=>"+10",
				"title"=>array(
					"ru-Ru"=>"Владивосток, Сидней"
					)
				);
		$tz[]=array(
				"val"=>"+11",
				"title"=>array(
					"ru-Ru"=>"Магадан, Сахалин"
					)
				);
		$tz[]=array(
				"val"=>"+12",
				"title"=>array(
					"ru-Ru"=>"Камчатка"
					)
				);
		$tz[]=array(
				"val"=>"+13",
				"title"=>array(
					"ru-Ru"=>"Тонга"
					)
				);
		$tz[]=array(
				"val"=>"+14",
				"title"=>array(
					"ru-Ru"=>"Остров Лайн"
					)
				);
		$len=count($tz);
		$res=array();
		for($cnt=0;$cnt<$len;$cnt++)
		{
			$res[$cnt]["val"]=$tz[$cnt]["val"];
			if(isset($tz[$cnt]["title"][$lng]))
				$res[$cnt]["title"]=$tz[$cnt]["title"][$lng];
			else
			{
				$keys=array_keys($tz[$cnt]["title"]);
				$res[$cnt]["title"]=$tz[$cnt]["title"][$keys[0]];
			}
		}
		return $res;
	}

	/**
	* Проверка даты на валидность
	* если дата задана как 0000-00-00 12:55:16,
	* то происходит автозаполнение года, месяца и дня
	*
	* @param mixed $dt
	*/
	public static function validDt(&$dt)
	{
		if(is_string($dt))
		{
			if(strpos($dt,"0000-00-00")===0)
			{
				$dp=explode(" ",$dt);
				$dtm="";
				if(count($dp)==2)$dtm=$dp[1];
				$dt=@date("Y-m-d",time()).($dtm?" ":"").$dtm;
			}
			$d=@strtotime($dt);
			if(($d===false) || ($d===-1))return false;
		}
		else $d=$dt;
		$d=@date("Y-m-d H:i:s",$d);
		if($d===false)return false;
		else $dt=$d;
		return true;
	}

	public static function validDtRus(&$dt)
	{
		$d=trim($dt);
		if(!$dt)return false;
		if(strlen($dt)<6)return false;
		$a=explode(" ",$d);
		if(!isset($a[1]))$a[1]="";
		$d=str_replace(" ","",$a[0]);
		$d=str_replace(".","-",$d);
		$d=str_replace("/","-",$d);
		$d=explode("-",$d);
		if(count($d)!=3)return false;
		$d=strtotime(($d[2])."-".$d[1]."-".$d[0]." ".$a[1]);
		if($d===-1)return false;
		$dt=date("d.m.Y H:i:s",$d);
		return true;
	}

	public static function validEmail($email="",$allow_empty=false)
	{
		$msg="Введенный e-mail некорректен!";
		if(!$email)
		{
			if($allow_empty)return true;
			else{
				self::$lastMsg="E-mail не может быть пустым!";
				return false;
			}
		}
		if(function_exists("filter_var"))
		{
			if(!filter_var($email, FILTER_VALIDATE_EMAIL))//php 5.0.2
			{
				self::$lastMsg=$msg;
				return false;
			}
		}
		else
		{
			$p=explode("@",$email);
			if(count($p)!=2)
			{
				self::$lastMsg=$msg;
				return false;
			}
			$p[0]=trim($p[0]);
			$p[1]=trim($p[1]);
			if(!$p[0] || !$p[1])
			{
				self::$lastMsg=$msg;
				return false;
			}
			$pt=$p[0];
			if(!preg_match("/^[-0-9a-z_\.]+$/i",$pt))
			{
				self::$lastMsg="Первая часть E-mail содержит неразрешенные символы!";
				return false;
			}
			$pt=$p[1];
			if(!preg_match("/^[-0-9a-z_\.]+$/i",$pt))
			{
				self::$lastMsg="Вторая часть E-mail (домен) содержит неразрешенные символы!";
				return false;
			}
			if(strlen($p[1])<4)
			{
				self::$lastMsg="Вторая часть E-mail (домен) имеет недопустимую длину!";
				return false;
			}
			$pt=explode(".",$p[1]);
			$l=count($pt);
			if($l>4)
			{
				self::$lastMsg=$msg;
				return false;
			}
			for($c=0;$c<$l;$c++)
			{
				$pt[$c]=trim($pt[$c]);
				if(!$pt[$c])
				{
					self::$lastMsg=$msg;
					return false;
				}
			}
			if(strlen($pt[$l-1])<2)
			{
				self::$lastMsg=$msg;
				return false;
			}
		}
		return true;
	}

	public static function validIP($ip,$ignore_empty=false,$strong_check=false)
	{
		if($ip==""){if($ignore_empty)return true;else return false;}
		if(!is_string($ip))return false;
		$chars="0123456789.";
		$len=strlen($ip);
		if($len<7 || $len>15)return false;
		$dots=0;
		for($cnt=0;$cnt<$len;$cnt++)
		{
			$ch=substr($ip,$cnt,1);
			if(strpos($chars,$ch)===false)return false;
			if($ch==".")$dots++;
		}
		if($dots!=3)return false;
		if($strong_check)
		{
			$grps=explode(".",$ip);
			$len=count($grps);
			if($len!=4)return false;
			for($cnt=0;$cnt<$len;$cnt++)
			{
				if(!ctype_digit($grps[$cnt]))return false;
				$n=0+$grps[$cnt];
				if($n>255)return false;
			}
		}
		return true;
	}

	/**
	* Проверка строки
	*
	* @param string $str - проверяемая строка
	* @param int $type (i.e. LIB_STR_TYPE_HASH)
	* @param bool $ignoreEmpty - вернуть true если строка пустая (def. false)
	* @param int $minLen - мин. длина строки (def. -1, игнорировать)
	* @param int $maxLen - макс. длина строки (def. -1, игнорировать)
	* @param bool $needMsg - вернуть сообщение через стек класса (def. false)
	* @param string $fldName - название поля/проверяемого значения
	*
	* @return bool $valid
	*/
	public static function validStr($str="",$type,$ignoreEmpty=false,$minLen=-1,$maxLen=-1,$needMsg=false,$fldName="[?]")
	{
		if(!is_int($type))
		{
			if($needMsg)self::$lastMsg="Невозможно проверить строку «{$fldName}» некорректный тип строки!";
			return false;
		}
		if(!in_array($type,range(0,LIB_STR_TYPES)))
		{
			if($needMsg)self::$lastMsg="Невозможно проверить строку «{$fldName}» - неизвестный тип строки!";
			return false;
		}
		$str.="";
		if(!$str)
		{
			if($ignoreEmpty)return true;
			else
			{
				if($needMsg)self::$lastMsg="Строка «{$fldName}» не может быть пустой!";
				return false;
			}
		}
		if(!is_int($minLen) || !is_int($maxLen))
		{
			if($needMsg)self::$lastMsg="Невозможно проверить строку «{$fldName}» - некорректные ограничения длины строки!";
			return false;
		}
		if($minLen!=-1)
		{
			$len=strlen($str);
			if($len<$minLen)
			{
				if($needMsg)self::$lastMsg="Строка «{$fldName}» содержит слишком короткую строку (мин. {$minLen} символа(ов))!";
				return false;
			}
		}
		if($minLen!=-1)
		{
			if($len>$maxLen)
			{
				if($needMsg)self::$lastMsg="Строка «{$fldName}» содержит слишком длинную строку (макс. {$maxLen} символа(ов))!";
				return false;
			}
		}
		if($type==LIB_STR_TYPE_ADDR)$chars="QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789 ,.-'"."ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮЁйцукенгшщзхъфывапролджэячсмитьбю";
		if($type==LIB_STR_TYPE_DECI)$chars="0123456789.";
		if($type==LIB_STR_TYPE_FILE)
		{
			$res=preg_match("/^[a-zA-Z0-9-_]+\$/",$str);
			if(!$res)
			{
				$chars="[a-z][A-Z][0-9][-_]";
				if($needMsg)self::$lastMsg="Строка «{$fldName}» содержит недопустимые символы! Ожидаются: ".$chars;
			}
			return $res;
		}
		if($type==LIB_STR_TYPE_HASH)$chars="ABCDEFabcdef1234567890";
		if($type==LIB_STR_TYPE_MODN)$chars="QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890_";
		if($type==LIB_STR_TYPE_NAME)$chars="QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm .-'"."ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮЁйцукенгшщзхъфывапролджэячсмитьбю";
		if($type==LIB_STR_TYPE_PASS)$chars="QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890@#%^*-_";
		if($type==LIB_STR_TYPE_PHON)$chars="0123456789 #*+-()";
		if($type==LIB_STR_TYPE_SENT)$chars="QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890 ,.!?:-«»\"'"."ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮЁйцукенгшщзхъфывапролджэячсмитьбю";
		if($type==LIB_STR_TYPE_USER)$chars="QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890_-.";
		$allowedStr[LIB_STR_TYPE_ADDR]="[A-Z][a-z][А-Я][а-я][0-9][ ][,.-']";
		$allowedStr[LIB_STR_TYPE_DECI]="[0-9][.]";
		$allowedStr[LIB_STR_TYPE_HASH]="[A-F][a-f][0-9]";
		$allowedStr[LIB_STR_TYPE_MODN]="[A-Z][a-z][0-9][_]";
		$allowedStr[LIB_STR_TYPE_NAME]="[A-Z][a-z][А-Я][а-я][ ][.-']";
		$allowedStr[LIB_STR_TYPE_PASS]="[A-Z][a-z][0-9][@#%^*-_]";
		$allowedStr[LIB_STR_TYPE_PHON]="[0-9][ ][#*+-()]";
		$allowedStr[LIB_STR_TYPE_SENT]="[A-Z][a-z][А-Я][а-я][0-9][ ][,.!?:-«»\"']";
		$allowedStr[LIB_STR_TYPE_USER]="[A-Z][a-z][0-9][_-.]";
		for($cnt=0;$cnt<$len;$cnt++)
		{
			$ch=substr($str,$cnt,1);
			if(strpos($chars,$ch)===false)
			{
				if($needMsg)self::$lastMsg="Строка «{$fldname}» содержит неразрешенные символы! Допускаются: ".$allowedStr[$type];
				return false;
			}
		}
		if($type==LIB_STR_TYPE_MODN)
		{
			$ch=substr($str,0,1);
			if(ctype_digit($ch))
			{
				if($needMsg)self::$lastMsg="Название модуля должно начинаться с латинской буквы.";
				return false;
			}
		}
		return true;
	}
}
?>
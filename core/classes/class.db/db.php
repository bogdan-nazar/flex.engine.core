<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
final class db
{
	private static $_runStep	=	0;
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $config		=	array(
		"con_host"				=>	"unknown.mysql",
		"con_name"				=>	"unknown_db",
		"con_user"				=>	"unknown_mysql",
		"con_pass"				=>	"nopass",
		"dev_host"				=>	"localhost",
		"dev_name"				=>	"unknown_db",
		"dev_user"				=>	"unknown_user",
		"dev_pass"				=>	"nopass",
		"loc_host"				=>	"localhost",
		"loc_name"				=>	"unknown_db",
		"loc_user"				=>	"unknown_user",
		"loc_pass"				=>	"nopass",
		"table_pref"			=>	"fa_"
	);
	private static $counts		=	array(0,0);
	private static $db			=	array(
		"host"					=>	"",
		"name"					=>	"",
		"user"					=>	"",
		"pass"					=>	"",
		"link"					=>	NULL
	);
	private static $lastError	=	"";
	private static $lastErrorId	=	-1;
	private static $tables		=	array();
	private	static $qfOps		=	array(">=","<=","!=","NOT","!","=",">","<","LIKE","ISNULL");
	private	static $qfJoin		=	array(",","AND","&&","OR","||");

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
		self::$config=array_merge(self::$config, self::$c->config(self::$class));
		if(self::$c->domainType()==CORE_DOMAIN_TYPE_MAIN)
		{
			self::$db["host"]=self::$config["con_host"];
			self::$db["name"]=self::$config["con_name"];
			self::$db["user"]=self::$config["con_user"];
			self::$db["pass"]=self::$config["con_pass"];
		}
		elseif(self::$c->domainType()==CORE_DOMAIN_TYPE_DEV)
		{
			self::$db["host"]=self::$config["dev_host"];
			self::$db["name"]=self::$config["dev_name"];
			self::$db["user"]=self::$config["dev_user"];
			self::$db["pass"]=self::$config["dev_pass"];
		}
		else
		{
			self::$db["host"]=self::$config["loc_host"];
			self::$db["name"]=self::$config["loc_name"];
			self::$db["user"]=self::$config["loc_user"];
			self::$db["pass"]=self::$config["loc_pass"];
		}
		self::$db["link"]=@mysql_connect(self::$db["host"],self::$db["user"],self::$db["pass"]) or die("Could not connect: ".mysql_error());
		mysql_query("SET CHARACTER_SET_CLIENT='utf8'");
		mysql_query("SET CHARACTER_SET_RESULTS='utf8'");
		mysql_query("SET COLLATION_CONNECTION='utf8_general_ci'");
		mysql_select_db(self::$db["name"],self::$db["link"]) or die ("Can't use database: ".mysql_error());
	}

	public static function _sleep(){}

	/**
	* Возвращает конфигурационный параметр
	*
	* @param string $par
	*
	* @return mixed $value
	*/
	public static function config($par)
	{
		if(strpos($par,"_pass")!==false)return "***hidden***";
		if(isset(self::$config[$par]))return self::$config[$par];
		else return "(?)";
	}

	/**
	* Возвращает значение параметра соединения
	*
	* @param string $par
	*
	* @return mixed $value
	*/
	public static function coninfo($par)
	{
		if($par=="pass")return "***hidden***";
		if(isset(self::$db[$par]))return self::$db[$par];
		else return "(?)";
	}

	/**
	* String escape
	*
	* @param string $s
	*
	* @return string $escapedString
	*/
	public static function esc($s)
	{
		return @mysql_real_escape_string("".$s);
	}

	/**
	* Returns id of just inserted record
	*
	* @return int $id
	*/
	public static function iid()
	{
		return @mysql_insert_id();
	}

	/**
	* Fetches next row from result
	*
	* @param resource $r
	* @param string[1] $a //"a" - associated, "r" - row, "b" - both
	*
	* @return array $row
	*/
	public static function fetch($r,$a="a")
	{
		switch($a)
		{
			case "a":
				return @mysql_fetch_assoc($r);
			case "r":
				return @mysql_fetch_row($r);
			default:
				return @mysql_fetch_array($r);
		}
	}

	/**
	* Возвращает текст последней ошибки, возникшей
	* при выполнении запроса
	*
	* @returns string $errorMsg
	*/
	public static function lastError()
	{
		$err=self::$lastError;
		self::$lastError="";
		return $err;
	}

	/**
	* Компонует из массива $filters строку
	* для использвания ее в SQL-запросах после WHERE
	* Каждый элемент $filters, также является массивом:
	* 0 - название поля
	* 1 - тип поля ("string/other")
	* 2 - операция ("LIKE","ISNULL","!","=","!=",">","<",">=","<=")
	* 3 - логика ("AND","OR")
	* Если $makeString == false -  результат возвращается в виде массива подстрок
	* Если $firstJoin == false - первое OR/AND будет опускаться
	*
	* @param array $filters
	* @param bool $makeString
	* @param bool $allowString
	* @param bool $firstJoin
	* @param bool $t - прифекс таблицы
	*
	* @returns string/array $res
	*/
	public static function filtersMake($filters=array(),$makeString=true,$allowString=false,$firstJoin=false,$t="")
	{
		if(!count($filters))return($makeString?"":false);
		$parced=array();
		$vars=array(
			"field-type"=>array("type","field-type","field_type",0),//string, subset, other
			"field-value"=>array("val","value","field-value","field_value","field-val","field_val",1),
			"field-join"=>array("join",2),
			"field-name"=>array("name","field-name","field_name",3),
			"field-oper"=>array("logic","operation","oper",4)
		);
		$cnt=-1;
		foreach($filters as $key=>$filter)
		{
			//проверяем на строку
			if(!is_array($filter))
			{
				if($allowString)
				{
					$filter="".$filter;
					if($filter)
					{
						$cnt++;
						$parced[]=$filter;
					}
				}
				continue;
			}
			else $cnt++;
			$ftype="string";
			foreach($vars["field-type"] as $field_type)
			{
				if(isset($filter[$field_type]))
				{
					$ftype=$filter[$field_type];
					if(!in_array($ftype,array("string","subset")))$ftype="other";
					break;
				}
			}
			$fvalue=false;
			foreach($vars["field-value"] as $field_val)
				if(isset($filter[$field_val]))
				{
					$fvalue=$filter[$field_val];
					break;
				}
			$fjoin="AND";
			foreach($vars["field-join"] as $field_join)
				if(isset($filter[$field_join]))
				{
					$fjoin=$filter[$field_join];
					$fjoin=strtoupper(trim($fjoin));
					if(!in_array($fjoin,self::$qfJoin))$fjoin="";
					break;
				}
			if(!$fjoin)continue;
			if($ftype=="subset" && is_array($fvalue) && $makeString)
			{
				$fvalue=self::filtersMake($value,true,$allowString,false);
				if(!$fvalue)continue;
				$parced[]=((!$cnt && !$firstJoin)?"":($fjoin." "))."(".$fvalue.")";
			}
			else
			{
				$fname="";
				foreach($vars["field-name"] as $field_name)
					if(isset($filter[$field_name]))
					{
						$fname=trim($filter[$field_name],"`");
						break;
					}
				if(!$fname)continue;
				$foper="=";
				foreach($vars["field-oper"] as $field_oper)
					if(isset($filter[$field_oper]))
					{
						$foper=$filter[$field_oper];
						$foper=strtoupper(trim($foper));
						if(!in_array($foper,self::$qfOps))$foper="";
						break;
					}
				if($foper=="LIKE")
				{
					if($ftype!="string")continue;
				}
				if($fvalue===false)
				{
					if(($foper!="!") && ($foper!="NOT") && ($foper!="ISNULL"))continue;
					if($foper=="ISNULL")$poper="ISNULL(".($t?("`".$t."`."):"")."`".$fname."`)";
					else $poper=($t?("`".$t."`."):"")."`".$fname."`=(".$foper.(($foper=="NOT")?" ":"").($t?("`".$t."`."):"")."`".$fname."`)";//только для update-запросов
				}
				else
				{
					$poper=($t?("`".$t."`."):"")."`".$fname."`".($foper=="LIKE"?" ":"").$foper.($foper=="LIKE"?" ":"");
				}
				if($fvalue!==false)
				{
					if($ftype=="string")$fvalue=mysql_real_escape_string($fvalue);
					$sect=($ftype=="string"?"'":"");
					$lsect="";
					if($foper=="LIKE")$lsect="%";
					$poper.=($sect.$lsect.$fvalue.$lsect.$sect);
				}
			}
			$parced[]=((!$cnt && !$firstJoin)?"":(" ".$fjoin." "))." ".$poper;
		}
		if($makeString)return implode(" ",$parced);
		else return $parced;
	}

	/**
	* Выполняет запрос к БД
	*
	* @param string $q - строка запроса
	* @param boolean $die - прервать работу приложения
	* @param array $debug - перезапись параметров стека отладки
	* @return resource
	*/
	public static function q($q,$die=false,$debug=array("msg"=>"Ошибка выполнения запроса к БД."))
	{
		$r=@mysql_query($q);
		if($r===false)
		{
			$msgQuery="SQL string: [\n".$q."\n].";
			$msgErr="MySQL message: [\n".mysql_error(self::$db["link"])."\n].";
			if(@function_exists("error_log") || $die)
			{
				//сохраняем
				if(!isset($debug["msg"]))$debug["msg"]="Ошибка выполнения запроса к БД.";
				if(!isset($debug["class"]))$debug["class"]="";
				if(!isset($debug["func"]))$debug["func"]="";
				if(!isset($debug["line"]))$debug["line"]="";
				if(!$debug["class"] || !$debug["func"] || !$debug["line"])
				{
					if(function_exists("debug_backtrace"))
					{
						$dbg=debug_backtrace();
						if(!$debug["line"])$debug["line"]=$dbg[0]["line"];
						array_shift($dbg);
						if(!$debug["class"])$debug["class"]=isset($dbg[0]["class"])?$dbg[0]["class"]:"";
						if(!$debug["func"])$debug["func"]=isset($dbg[0]["function"])?$dbg[0]["function"]:"";
					}
				}
				self::$lastError=$debug["msg"];
				self::$lastErrorId=msgr::errorLog($debug["msg"],false,$debug["class"],$debug["func"],$debug["line"],$msgQuery."\n\n".$msgErr);
				$ep="EP: [".($debug["class"]?($debug["class"]."::"):"").($debug["func"]?($debug["func"].">"):"").$debug["line"]."]";
				if(function_exists("error_log"))
				{
					$amail=self::$c->config("","adminEmail");
					if(lib::validEmail($amail,false))@error_log($debug["msg"]."\n\n".$msgQuery."\n\n".$msgErr."\n\n".$ep,1,$amail);
				}
				if($die)die($debug["msg"]."<br /><br />".$ep);
			}
			self::$counts[1]++;
		}
		else self::$counts[0]++;
		return $r;
	}

	/**
	* Получить счетчик запросов
	*
	* @param int $c - 0/1 успешные, неуспешные
	* @return int $count
	*/
	public static function qc($c)
	{
		if(isset(self::$counts[$c]))return self::$counts[$c];
		else return 0;
	}

	/**
	* Gets row count in the result
	*
	* @param resource $r
	* @return int $count
	*/
	public static function rows($r)
	{
		return @mysql_num_rows($r);
	}

	/**
	* Возвращает название полей таблицы
	*
	* @param string $tname
	* @param string $class
	* @param boolean $force
	*
	* @return array/false $tableFields
	*/
	public static function tFields($tname,$class="",$force=false)
	{
		$fields=array();
		if($class)$table="mod_".$class."_".$tname;
		else $table=$tname;
		$r=self::q("SHOW COLUMNS FROM `".self::$config["table_pref"].$table."`",true);
		while($rec=@mysql_fetch_assoc($r))$fields[]=$rec["Field"];
		return $fields;
	}

	/**
	* Возвращает описание таблицы
	*
	* @param string $class
	* @param string $tname
	* @param boolean $force
	*
	* @return array/false $tableFields
	*/
	public static function tMeta($class,$tname="",$force=false)
	{
		$table=self::$config["table_pref"]."mod_".$class.($tname?("_".$tname):"");
		if($force || !isset(self::$tables[$table]))
		{
			//Array([Field]=>id,[Type]=>int(7),[Null]=>,[Key]=>PRI,[Default]=>,[Extra]=>auto_increment)
			$r=db::q("SHOW COLUMNS FROM `".$table."`",false);
			if($r===false)return false;
			while($rec=@mysql_fetch_assoc($r))
			{
				$field=array();
				$field["raw"]=$rec;
				$field["name"]=$rec["Field"];
				$typeRaw=strtolower($rec["Type"]);
				$typeInt="other";
				if(preg_match("/(^[a-z]+).*$/",$typeRaw,$prsd))$type=$prsd[1];
				else $type="other";
				$maxLen=0;
				$unsigned=false;
				$isnumber=false;
				switch($type)
				{
					case "bigint":
					case "int":
					case "mediumint":
					case "smallint":
					case "tinyint":
					case "serial":
						$isnumber=true;
						$typeInt="integer";
						if(preg_match("/\(([\d]+)\)/",$typeRaw,$len))$maxLen=0+$len[1];
						if(($type=="serial") || strpos($type,"unsigned")!==0)$unsigned=true;
						break;
					case "decimal":
					case "float":
					case "double":
					case "real":
						$isnumber=true;
						$typeInt="float";
						if(($type=="decimal") && (preg_match("/\(([\d]+),([\d]+)\)/",$typeRaw,$len)))$maxLen=0+$len[1]+$len[2]+1;
						if(strpos($type,"unsigned")!==0)$unsigned=true;
						break;
					case "bit":
					case "bool":
						$typeInt="bit";
						$maxLen=1;
						break;
					case "datetime":
					case "timestamp":
					case "date":
					case "time":
					case "year":
						$typeInt="date";
						if($type=="year")$maxLen=4;
						break;
					case "char":
					case "varchar":
						$typeInt="string";
						if(preg_match("/\(([\d]+)\)/",$typeRaw,$len))$maxLen=0+$len[1];
						break;
					case "text":
					case "tinytext":
					case "mediumtext":
					case "longtext":
						$typeInt="text";
						break;
					case "blob":
					case "tinyblob":
					case "mediumblob":
					case "longblob":
						$typeInt="blob";
						break;
					default:
				}
				$field["isnumber"]=$isnumber;
				$field["type"]=$typeInt;
				$field["typeDB"]=$type;
				$field["maxLen"]=$maxLen;
				if($isnumber)$field["unsigned"]=$unsigned;
				self::$tables[$table][$field["name"]]=$field;
			}

		}
		return array_merge(array(),self::$tables[$table]);
	}

	/**
	* Имя таблицы с учетом текущего префикса
	* существование таблицы не проверяется
	*
	* @param string $name
	*
	* @return string $tableName
	*/
	public static function tn($name)
	{
		return"`".self::$config["table_pref"]."{$name}`";
	}

	/**
	* Имя таблицы модуля с учетом текущего префикса
	* существование таблицы не проверяется
	*
	* @param string $name
	*
	* @return string $tableName
	*/
	public static function tnm($name)
	{
		return"`".self::$config["table_pref"]."mod_{$name}`";
	}
}
?>
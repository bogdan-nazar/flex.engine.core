<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
final class content
{
	private static $_runStep	=	0;
	private static $adminRunner	=	array(
		"admin"					=>	"",
	);
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $config		=	array(
			"index"				=>	"index",
			"pathDefId"			=>	0,
			"pathDefAlias"		=>	"index",
			"pathMaxSects"		=>	10,
			"spotsDefault"		=>	array(1,2,3,4,5,6,10,11,12,13,16,21,22)
	);
	private static $fieldsSys	=	array("id","uid","uuid","created","updated","force_spots");
	private static $item		=	array(
			"alias"				=>	"notfound",
			"id"				=>	0,
			"images"			=>	array(),
			"meta_desc"			=>	"",
			"meta_kw"			=>	"",
			"nolayout"			=>	false,
			"spots"				=>	array(),
			"title"				=>	"Page not found",
			"title_add"			=>	"",
			"title_new"			=>	"",
			"show_title"		=>	false,
			"title_incontent"	=>	false,
			"updated"			=>	"0000-00-00 00:00:00"
	);
	private static $path		=	array();
	private static $session		=	array();
	private static $styles		=	array();

	private static function _data($id,$exposeError,$raw=false)
	{
		$path=FLEX_APP_DIR_DAT."/_".self::$class."/".$id."/";
		$file=$path.self::$config["index"].".php";
		$fe=@file_exists($file);
		$cont="";
		if($raw)
		{
			if($fe)
			{
				$cont=@file_get_contents($file);
				if($cont)
				{
					if(lib::mquotes_runtime())$cont=stripslashes($cont);
					$cont=lib::bomTrim($cont);
					$cont=trim($cont);
				}
			}
			else $raw=false;
		}
		if(!$raw)
		{
			@ob_start();
			@include $file;
			$cont=@ob_get_contents();
			@ob_end_clean();
		}
		if($cont)
		{
			$cont=str_replace("{\$contentDir}",FLEX_APP_DIR_ROOT.$path,$cont);
		}
		else
		{
			if(is_string($exposeError) && !$fe)$cont=$exposeError;
		}
		return $cont;
	}

	private static function _itemGet()
	{
		$p=self::$c->path();
		$path=array();
		$sects=array();
		if($p["pid"]>0)$sects[]=" `id`=".$p["pid"];
		else
		{
			$sects=explode("/",$p["sections"]);
			if(!$sects[0])$sects[0]=self::$config["pathDefAlias"];
			else
			{
				$nsects=array();
				foreach($sects as $sect)
				{
					//для алиасов страниц разрешены дефис(-), нижний подчерк(_), латинские буквы,
					//и цифры, если они используются вместе с латинскими буквами

					//отсекаем секции, состоящие только из цифр
					if(preg_match("/[0-9]+/",$sect))continue;
					//отсекаем разные символы
					//все спецсимволы регулярок \+*?[^]$(){}=!<>|:-
					//спец символы регулярок *?<>|: браузер не пропускает и так
					$qu="+[^]\$(){}=!";
					$pregq=preg_quote($qu);
					//в именах секций браузер не пропускает также .#%&
					if(preg_match("/[',;@\s".$pregq."]+/",$sect))continue;
					//на всякий случай эскейпим
					$nsects[]=db::esc($sect);
				}
				$sects=$nsects;
			}
		}
		if(count($sects))
		{
			$r=db::q("SELECT DISTINCT * FROM ".db::tnm(self::$class)." WHERE `alias` IN ('".implode("','",$sects)."') AND `act`=1",true);
			$found=array();
			while($rec=db::fetch($r))$found[$rec["alias"]]=$rec;
			if(!count($found))return;
			$len=count($sects);
			if(self::$c->config("","uriParseType",false)==CORE_URI_PARSE_FIRSTSECT)
			{
				$start=0;
				$end=$len;
			}
			else
			{
				$start=-($len-1);
				$end=1;
			}
			$fnd=false;
			for($cnt=$start;$cnt<$end;$cnt++)
			{
				$ind=$cnt;
				if($ind<0)$ind=$ind*(-1);
				if(isset($found[$sects[$ind]]))
				{
					$rec=$found[$sects[$ind]];
					$item=array("id"=>(0+$rec["id"]),"alias"=>$rec["alias"]);
					if($start<0)array_unshift($path,$item);
					else $path[]=$item;
					if(!$fnd)
					{
						$fnd=true;
						self::$item["id"]=0+$rec["id"];
						self::$item["nolayout"]=true && $rec["nolayout"];
						self::$item["show_title"]=true && $rec["show_title"];
						self::$item["title_incontent"]=true && $rec["title_incontent"];
						self::$item["created"]=$rec["created"];
						self::$item["updated"]=$rec["updated"];
						self::$item["spots"]=($rec["force_spots"]?explode(",",$rec["force_spots"]):array());
						if(count(self::$item["spots"]))foreach(self::$item["spots"] as $key=>$val)self::$item["spots"][$key]=0+$val;
						self::$item["alias"]=$rec["alias"];
						self::$item["title"]=$rec["title"];
						self::$item["meta_desc"]=$rec["meta_description"];
						self::$item["meta_kw"]=$rec["meta_keywords"];
					}
				}
			}
			//default page
			if(self::$config["pathDefId"])
			{
				$ind=array("id"=>self::$config["pathDefId"],"alias"=>self::$config["pathDefAlias"]);
				if($start<0)array_unshift($path,$ind);
				else array_push($path,$ind);
			}
		}
		self::$path=$path;
	}

	private static function _renderNotFound($msg)
	{
?>
		<div class="content content-404"><?=$msg?></div>
<?
	}

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
	}

	/**
	* Запись данных в сессию
	*/
	private static function _sessionWrite()
	{
		//убеждаемся, что сессия не была повреждена во время выполнения
		if(!is_array(self::$session))self::$session=array();
		//пакуем сессию
		$_SESSION[FLEX_APP_NAME."-".self::$class."-data"]=serialize(self::$session);
	}

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
		self::_sessionRead();
		self::_itemGet();
	}

	public static function _render($sid,$ads=true)
	{
		if(!self::$item["id"])
		{
?>
	<div id="<?=self::$class?>" class="<?=self::$class?> <?=self::$class?>-404"><?=_t(LANG_CONTENT_P404)?></div>
<?
			return;
		}
		$content="";
		$mods=count(render::modsInSpot(2))+count(render::modsInSpot($sid))+count(render::modsInSpot(3));
		if(!self::$item["nolayout"])
		{
			$content=self::_data(self::$item["id"],_t(LANG_CONTENT_DELETED),false);
		}
		if($content || (!$content && !$mods))
		{
?>
	<div id="<?=self::$class?>" class="<?=self::$class?>">
<?
			if(self::$item["title_incontent"])
			{
?>
		<h1 class="<?=self::$class?>-title"><?=(self::$item["title_new"]?self::$item["title_new"]:self::$item["title"])?><?=($ads?self::$item["title_add"]:"")?></h1>
<?
			}
?>
		<div id="<?=self::$class?>-item-<?=self::$item["id"]?>" class="<?=self::$class?>-item __<?=self::$item["alias"]?>"><?=$content?></div>
	</div>
<?
		}
	}

	/**
	* Завершение и сохранение
	*
	*/
	public static function _sleep()
	{
		self::_sessionWrite();
	}

	public static function pageByModMethod($mod,$method="")
	{
		$mod=db::esc($mod);
		$method=db::esc($method);
		$q="SELECT `c`.`alias` FROM ".db::tn("mods")." `m`
		INNER JOIN ".db::tnm("render_binds")." `b` ON `b`.`mid`=`m`.`id`
		INNER JOIN ".db::tnm("render_bind_adds")." `pb` ON `pb`.`bid`=`b`.`id`
		INNER JOIN ".db::tnm(self::$class)." `c` ON `c`.`id`=`pb`.`cid`
		WHERE `m`.`class`='{$mod}' AND `b`.`pages`='none' AND `b`.`method`='{$method}'";
		$r=db::q($q,true);
		$rec=db::fetch($r,"r");
		if($rec)return $rec[0];
		else
		{
			if($method==self::$c->modHookName("render"))
			{
				$q="SELECT `c`.`alias` FROM ".db::tn("mods")." `m`
				INNER JOIN ".db::tnm("render_binds")." `b` ON `b`.`mid`=`m`.`id`
				INNER JOIN ".db::tnm("render_bind_adds")." `pb` ON `pb`.`bid`=`b`.`id`
				INNER JOIN ".db::tnm(self::$class)." `c` ON `c`.`id`=`pb`.`cid`
				WHERE `m`.`class`='{$mod}' AND `b`.`pages`='none' AND (`b`.`method`='' OR `b`.`method` LIKE 'tpl:%')";
				$r=db::q($q,true);
				$rec=db::fetch($r,"r");
				if($rec)return $rec[0];
			}
		}
		return"";
	}

	public static function pageIndex()
	{
		return self::$config["index"];
	}

	public static function check($field="",$value="",$cid=0,$uniq=true)
	{
		$sl=self::$c->silent();
		$msg="";
		$ext="";
		$t=db::tMeta("content");
		$fs=array_keys($t);
		if(!in_array($field,$fs))
		{
			$msg="Указанное поле не распознано, обратитесь к администратору системы.";
			msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Field [".$field."] was not found in meta data.");
			return false;
		}
		switch($field)
		{
			case "alias":
				$a=0+$value;
				if($a>0)
				{
					$s="".$a;
					$l1=@mb_strlen($value,"UTF-8");
					$l2=@mb_strlen($s,"UTF-8");
					if(($l1==$l2) && (strpos($value,".")==false))
					{
						$msg="Алиас не должен состоять только из цифр.";
						msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Field [".$field."] is total numeric [".$value."].");
						return false;
					}
				}
				if($msg)break;
				$nal=array("/","\\","?","&","="," ","<",">","\"");
				foreach($nal as $c)
				{
					$f=strpos($value,$c);
					if($f!==false)
					{
						$msg="Поле \"Алиас\" содержит один из недопустимых символов [/,\\,\",?,&,=,пробел].";
						$ext="Field [".$field."] contains not allowed character - ".$f.".";
						break;
					}
				}
				if($msg)break;
				$l=@mb_strlen($value,"UTF-8");
				if(!$l)
				{
					$msg="Поле \"Алиас\" не может быть пустым.";
					$ext="Field [".$field."] is empty.";
					break;
				}
				if($t[$field]["maxLen"]>0)
				{
					if($l>$t[$field]["maxLen"])
					{
						$msg="Поле \"Алиас\" имеет слишком большую длину - ".$l." сим. [макс.: ".$t[$field]["maxLen"]." сим.].";
						$ext="Field [".$field."] is too long: ".$l." chars.";
						break;
					}
				}
				if($uniq)
				{
					$r=db::q("SELECT `id` FROM ".db::tnm(self::$class)." WHERE `alias`='".db::esc($value)."'".($cid?(" AND `id`!=".$cid):""),!$sl);
					if($r===false)return false;
					$rec=db::fetch($r,"r");
					if($rec!==false)
					{
						$msg="Указанный алиас [".$value."] уже используется.";
						$ext="Field [".$field."] is already in use: '".$value."'.";
						break;
					}
				}
				break;
			default:

		}
		if($msg)
		{
			msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,$ext);
			return false;
		}
		return true;
	}

	public static function count_($filters=array(),$order=array(),$range=array())
	{
		$t=db::tMeta("content");
		$fs=array_keys($t);
		$fts=array();
		foreach($filters as $filter)
		{
			if(is_string($filter))
			{
				$fts[]=$filter;
				continue;
			}
			if(is_array($filter))
			{
				if(isset($filter[0]) && (in_array($filter[0],$fs)) && isset($filter[1]) && isset($filter[2]))
				{
					if(($t[$filter[0]]["type"]=="string") || ($t[$filter[0]]["type"]=="text"))
					{
						$filter[2]="".$filter[2];
						$type="string";
					}
					else $type="other";
					$fts[]=array($type,$filter[2],"AND",$filter[0],$filter[1]);
				}
			}
		}
		$fts=db::filtersMake($fts,true,true,false);
		$q="SELECT COUNT(`id`) AS `cnt` FROM ".db::tnm(self::$class).($fts?(" WHERE ".$fts):"");
		$r=db::q($q,!self::$c->silent());
		if($r===false)return 0;
		else
		{
			$rec=db::fetch($r);
			return(0+$rec["cnt"]);
		}
	}

	public static function create($values=array(),$data=false)
	{
		$sl=self::$c->silent();
		$uid=auth::user("id");
		if(!is_string($data))$data=false;
		$t=db::tMeta(self::$class);
		$fs=array_keys($t);
		//проверяем данные
		$dts=array();
		$work=array();
		foreach($fs as $fld)
			if(!in_array($fld,self::$fieldsSys))$work[]=$fld;
		$ind=0;
		foreach($values as $field=>$value)
		{
			if(is_numeric($field))
			{
				if(!isset($work[$ind]))continue;
				$field=$work[$ind];
				$ind++;
			}
			else
			{
				if(!in_array($field,$fs))continue;
			}
			if(in_array($field,self::$fieldsSys))
			{
				switch($field)
				{
					case "id":
						$t["id"]["data"]="NULL";
						break;
					case "uid":
					case "uuid":
						$t[$field]["data"]="".$uid;
						break;
					case "created":
					case "updated":
						$dt=$value;
						if(!lib::validDt($dt))$t[$field]["data"]="NOW()";
						else $t[$field]["data"]="'".$dt."'";
						break;
					case "force_spots"://string only accepted
						if(is_string($value) && $value!="")
						{
							$sp=explode(",",$value);
							if(count($sp))
							{
								$spn=array();
								foreach($sp as $i=>$spot)
								{
									$sp[$i]=0+(trim($spot));
									if($sp[$i])$spn[]=$sp[$i];
								}
								if(count($spn))$value=implode(",",$spn);
								else $value;
							}
							else $value=false;
						}
						else $value=false;
						if(!$value)$t["force_spots"]["data"]="''";
						break;
				}
			}
			if(($field=="title") && !$value)$value="Новая страница";
			if(($t[$field]["type"]=="string") || ($t[$field]["type"]=="text"))
			{
				$value="".$value;
				$t[$field]["data"]="'".db::esc($value)."'";
			}
			else $t[$field]["data"]="".$value;
		}
		$d=array();
		foreach($t as $field=>$m)
		{
			if(!in_array($field,$work))
			{
				switch($field)
				{
					case "id":
						$d[]="NULL";
						break;
					case "uid":
					case "uuid":
						$d[]="".$uid;
						break;
					case "created":
					case "updated":
						$d[]="NOW()";
						break;
					case "force_spots":
						$d[]="''";
						break;
				}
				continue;
			}
			if(!isset($m["data"]))
			{
				$msg="Невозможно выполнить операцию: данные не распознаны или заданы с ошибкой.";
				msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Field is not set: ".$field."=>".serialize($m));
				return false;
			}
			if(!$m["isnumber"])
			{
				if($m["maxLen"]>0)
				{
					$len=@mb_strlen($m["data"],"UTF-8");
					if($len>$m["maxLen"])
					{
						$msg="Данные для поля [".$field."] превышают максимальную длину (".$m["maxLen"]." симв., текущая: ".$len.")";
						msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"String for ".db::tnm(self::$class)."[".$field."] is too long: ".$len.">".$m["maxLen"]);
						return false;
					}
				}
			}
			$d[]=$m["data"];
		}
		//проверяем на существование папку модуля,
		//создаем ее, если нужно
		if($data!==false)
		{
			$mdir=FLEX_APP_DIR_DAT."/_".self::$class;
			if(!@file_exists($mdir))
			{
				if(@mkdir($mdir,0755)===false || (!@is_writeable($mdir)))
				{
					$msg="Невозможно создать страницу: доступ к файловой системе ограничен!";
					msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Directory creation failed: ".$mdir);
					return false;
				}
			}
		}
		$r=db::q("INSERT INTO ".db::tnm(self::$class)." VALUES(".implode(",",$d).")",!$sl);
		if($r===false)return false;
		$id=0+db::iid();
		if($data)
		{
			//проверяем доступ на запись файла страницы
			$fn=$mdir."/".$id."/".self::$config["index"].".php";
			if(@file_exists($fn) && !@is_writable($fn))
			{
				db::q("DELETE FROM ".db::tnm(self::$class)." WHERE `id`=".$id);
				$msg="Невозможно создать страницу: доступ к файловой системе ограничен!";
				msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"File creation failed: ".$fn);
				return false;
			}
			@file_put_contents($fn,"<?defined(\"FLEX_APP\") or die(\"Forbidden.\");?>\n".$data);
			@chmod($fn,0755);
		}
		return $id;
	}

	public static function data($id,$render=false,$raw=false)
	{
		return self::_data($id,$render,$raw);
	}

	public static function delete($ids=array())
	{
		$sl=self::$c->silent();
		$ids=self::$c->post(self::$class."-admin-list-id");
		$aliases=self::$c->post(self::$class."-admin-list-aliases");
		if(!is_array($ids) || !count($ids))
		{
			msgr::add("Некорректные данные запроса!");
			return;
		}
		$files=array();
		foreach($aliases as $val)
		{
			$val=explode(":",$val);
			if(in_array($val[0],$ids))$files[]=$val[1];
		}
		$cnt=count($ids);
		if($cnt!=count($files))
		{
			msgr::add("Некорректные данные запроса!");
			return;
		}
		if($cnt>self::$config["batchMaxItemsCount"])
		{
			msgr::add("Невозможно выполнить операцию: выбрано слишком много страниц [>".self::$config["batchMaxItemsCount"]."]!");
			return;
		}
		$sucids=array();
		for($c=0;$c<$cnt;$c++)
		{
			$res=media::delete(self::$class,$ids[$c],array());
			if($res)$sucids[]=$ids[$c];
			else msgr::add(media::lastMsg());
		}
		$cnt=count($sucids);
		if(!$cnt)return false;
		if($cnt==1)
		{
			$sql="=".$sucids[0];
			$msg="Страница удалена!";
		}
		else
		{
			$sql=" IN (".implode(",",$sucids).")";
			$msg="Страницы удалены!";
		}
		db::q("DELETE FROM ".db::tnm(self::$class)." WHERE `id`{$sql}",true);
		db::q("DELETE FROM ".db::tn("runspots_pages")." WHERE `cid`{$sql}",true);
		db::q("DELETE FROM ".db::tn("runspots_styles")." WHERE `cid`{$sql}",true);
		msgr::add($msg);
	}

	public static function fetch($fields=array(),$filters=array(),$order=array(),$range=array())
	{
		$sl=self::$c->silent();
		$t=db::tMeta(self::$class);
		$fs=array_keys($t);
		$sel=array();
		if(is_string($fields) && $fields!="")$fields=explode(",",$fields);
		if(!is_array($fields))$fields=array();
		foreach($fields as $field)
		{
			if(!is_string($field))continue;
			if(!in_array($field,$fs))continue;
			$sel[]="`".$field."`";
		}
		if(!count($sel))$sel=$fs;
		$fts=array();
		foreach($filters as $filter)
		{
			if(is_string($filter))
			{
				$fts[]=$filter;
				continue;
			}
			if(is_array($filter))
			{
				if(isset($filter[0]) && (in_array($filter[0],$fs)) && isset($filter[1]) && isset($filter[2]))
				{
					if(($t[$filter[0]]["type"]=="string") || ($t[$filter[0]]["type"]=="text"))
					{
						$filter[2]="".$filter[2];
						$type="string";
					}
					else $type="other";
					$fts[]=array($type,$filter[2],"AND",$filter[0],$filter[1]);
				}
			}
		}
		$fts=db::filtersMake($fts,true,true,false);
		$q="SELECT ".implode(",",$sel)." FROM ".db::tnm(self::$class).($fts?(" WHERE ".$fts):"");
		if(!is_array($order))$c=0;
		else $c=count($order);
		if($c)$q.=" ORDER BY `".$order[0]."`".(isset($order[1])?(" ".$order[1]):"");
		if(!is_array($range))$c=0;
		else $c=count($range);
		if($c>0)$q.=" LIMIT ".($c==1?("0,".$range[0]):($range[0].",".$range[1]));
		$r=db::q($q,!self::$c->silent());
		if($r===false)return false;
		$recs=array();
		$mqrt=lib::mquotes_runtime();
		while($rec=db::fetch($r))
		{
			if(isset($rec["id"]))$rec["id"]=0+$rec["id"];
			if(isset($rec["act"]))$rec["act"]=0+$rec["act"];
			if(isset($rec["nolayout"]))$rec["nolayout"]=0+$rec["nolayout"];
			if(isset($rec["show_title"]))$rec["show_title"]=0+$rec["show_title"];
			if(isset($rec["alias"]) && $mqrt)$rec["alias"]=stripslashes($rec["alias"]);
			if(isset($rec["title"]) && $mqrt)$rec["title"]=stripslashes($rec["title"]);
			if(isset($rec["meta_description"]) && $mqrt)$rec["meta_description"]=stripslashes($rec["meta_description"]);
			if(isset($rec["meta_keywords"]) && $mqrt)$rec["meta_keywords"]=stripslashes($rec["meta_keywords"]);
			$recs[]=$rec;
		}
		return $recs;
	}

	public static function item($prop="")
	{
		if(isset(self::$item[$prop]))
			return self::$item[$prop];
		else
			return "";
	}

	public static function path()
	{
		return self::$path;
	}

	public static function spots()
	{
		return self::$item["spots"];
	}

	public static function title($render=true,$ads=true)
	{
		if($render && !self::$item["title_incontent"] && self::$item["show_title"])
		{
?>
		<div class="<?=self::$class?>">
			<h1 class="title"><?=(self::$item["title_new"]?self::$item["title_new"]:self::$item["title"])?><?=($ads?self::$item["title_add"]:"")?></h1>
		</div>
<?
		}
		else
		{
			if(!$render)return (self::$item["title_new"]?self::$item["title_new"]:self::$item["title"]).($ads?self::$item["title_add"]:"");
		}
	}

	public static function titleAdd($title)
	{
		self::$item["title_add"]=$title;
	}

	public static function titleReplace($title,$emptyAds=true)
	{
		if(!$title)return false;
		self::$item["title_new"]=$title;
		if($emptyAds)self::$item["title_add"]="";
		return true;
	}

	public static function update($filters=array(),$values=array(),$data=false)
	{
		$sl=self::$c->silent();
		$uid=auth::user("id");
		if(!is_string($data) || !$data)$data=false;
		$t=db::tMeta("content");
		$fs=array_keys($t);
		//проверяем фильтры
		$fts=array();
		$filterId=-1;
		foreach($filters as $filter)
		{
			if(is_string($filter))
			{
				$t=str_replace("`","",$filter);
				$t=preg_replace("/\s+/","",$t);
				if(strpos($filter,"id=")!==false)
				{
					$id=str_replace("id=","",$t);
					if(($id && ((0+$id)>0)))$filterId=$id;
					else continue;
				}
				$fts[]=$filter;
				continue;
			}
			if(is_array($filter))
			{
				if(isset($filter[0]) && (in_array($filter[0],$fs)) && isset($filter[1]) && isset($filter[2]))
				{
					if(($t[$filter[0]]["type"]=="string") || ($t[$filter[0]]["type"]=="text"))
					{
						$filter[2]="".$filter[2];
						$type="string";
					}
					else $type="other";
					$fts[]=array($type,$filter[2],"AND",$filter[0],$filter[1]);
					if(($filter[0]=="id") && ($filter[1]=="=") && ($filter[2]>0))$filterId=$filter[2];
				}
			}
		}
		//проверяем данные
		$dts=array();
		$actInProc=false;
		$aliasInProc=false;
		foreach($values as $field=>$value)
		{
			if(!is_string($field))continue;
			if(!in_array($field,$fs))continue;
			if(in_array($field,self::$fieldsSys))continue;
			if($field=="act")$actInProc=true;
			if($field=="alias")$aliasInProc=true;
			if(($t[$field]["type"]=="string") || ($t[$field]["type"]=="text"))
			{
				$value="".$value;
				$type="string";
			}
			else $type="other";
			$dts[]=array($type,$value,",",$field,($value===false?"NOT":"="));
		}
		if(!count($dts))
		{
			$msg="Невозможно выполнить операцию: данные не распознаны или заданы с ошибкой.";
			msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input values: ".serialize($values).", parced data: ".serialize($dts));
			return false;
		}
		$fc=count($fts);
		if(count($filters)!=$fc)
		{
			$msg="Невозможно выполнить операцию: один или несколько критериев поиска не распознаны или заданы с ошибкой.";
			msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", parced filters: ".serialize($fts));
			return false;
		}
		if($data && (($filterId==-1) || ($fc!=1)))
		{
			$data=false;
			if(!$sl)msgr::add("Содержимое страницы проигнорировано: фильтр должен содержать только указатель на конкретную страницу.",MSGR_TYPE_WRN);
		}
		$dts=db::filtersMake($dts,true,true,false);
		$fts=db::filtersMake($fts,true,true,false);
		//кол-во затрагиваемых страниц
		$r=db::q("SELECT COUNT(`id`) AS `cnt` FROM ".db::tnm(self::$class).($fts?(" WHERE ".$fts):""),!$sl);
		if($r===false)return false;
		$rec=db::fetch($r,"r");
		$affected=0+$rec[0];
		if(!$affected)
		{
			$msg="Выборка набор страниц по заданному фильтру не принесла результата.";
			msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", joined filters: [".$fts."]");
			return false;
		}
		if($aliasInProc)
		{
			if(!self::check("alias",$values["alias"],0,false))return false;
			//проверка установки алиаса для более чем 1 страницы
			if($affected>1)
			{
				$msg="Невозможно выполнить операцию: попытка задать одинаковый алиас для множества страниц.";
				msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", joined filters: [".$fts."]");
				return false;
			}
			if($values["alias"]=="")
			{
				//проверка установки пустого алиаса для активных страниц
				if(!$actInProc)
				{
					$r=db::q("SELECT COUNT(`id`) AS `cnt` FROM ".db::tnm(self::$class)." WHERE ".($fts?($fts." AND "):"")."`act`=1",!$sl);
					if($r===false)return false;
					$rec=db::fetch($r,"r");
					if((0+$rec[0])>0)
					{
						if($affected==1)$msg="Невозможно выполнить операцию: невозможно очистить алиас для активной страницы.";
						else $msg="Невозможно выполнить операцию: попытка очистить алиас для множества страниц, содержащего активные страницы";
						msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", joined filters: [".$fts."]");
						return false;
					}
				}
			}
			else
			{
				//если страница одна, проверяем алиас на уникальность
				$r=db::q("SELECT COUNT(`id`) AS `cnt` FROM ".db::tnm(self::$class)." WHERE ".($fts?(" NOT (".$fts.") AND "):"")."`alias`='".db::esc($values["alias"])."'",!$sl);
				if($r===false)return false;
				$rec=db::fetch($r,"r");
				if((0+$rec[0])>1)
				{
					$msg="Невозможно выполнить операцию: указанный алиас уже используется.";
					msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", joined filters: [".$fts."]");
					return false;
				}
			}
		}
		if($actInProc)
		{
 			if(!$aliasInProc)
 			{
 				if(($values["act"]=="1") || ($values["act"]==1))
				{
					$r=db::q("SELECT COUNT(`id`) AS `cnt` FROM ".db::tnm(self::$class)." WHERE ".($fts?($fts." AND "):"")."`alias`=''",!$sl);
					if($r===false)return false;
					$rec=db::fetch($r,"r");
					if((0+$rec[0])>0)
					{
						if($affected==1)$msg="Невозможно выполнить операцию: попытка активировать страницу с отсутствующим алиасом.";
						else $msg="Невозможно выполнить операцию: попытка активировать набор страниц с отсутствующим алиасом.";
						msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", joined filters: [".$fts."]");
						return false;
					}
				}
			}
			else
			{
				//$affected>1 выйдет с ошибкой в проверках выше
				if(($values["alias"]=="") && (($values["act"]=="0") || ($values["act"]==0)))
				{
					$msg="Невозможно выполнить операцию: попытка активировать страницу с очисткой ее алиаса.";
					msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Input filters: ".serialize($filters).", joined filters: [".$fts."]");
					return false;
				}
			}
		}
		//проверяем на существование папку модуля,
		//создаем ее, если нужно
		if($data)
		{
			$mdir=FLEX_APP_DIR_DAT."/_".self::$class;
			if(!@file_exists($mdir))
			{
				if(@mkdir($mdir,0755)===false || (!@is_writeable($mdir)))
				{
					$msg="Невозможно обновить страницу: доступ к файловой системе ограничен!";
					msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"Directory creation failed: ".$mdir);
					return false;
				}
			}
		}
		$r=db::q("UPDATE ".db::tnm(self::$class)." SET `uuid`=".$uid.", `updated`=NOW()".($dts?(", ".$dts):"").($fts?(" WHERE ".$fts):""),!$sl);
		if($r===false)return false;
		$c=0+db::affected();
		if($data)
		{
			$fn=$mdir."/".$filterId;
			if(!@file_exists($fn))
			{
				@mkdir($fn,0755,true);
				if(!@file_exists($fn))
				{
					$msg="Невозможно обновить содержимое страницы: доступ к файловой системе ограничен!";
					msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"File creation failed: ".$fn);
					return false;
				}
			}
			//проверяем доступ на запись файла страницы
			$fn=$mdir."/".$filterId."/".self::$config["index"].".php";
			if(@file_exists($fn) && !@is_writable($fn))
			{
				$msg="Невозможно обновить содержимое страницы: доступ к файловой системе ограничен!";
				msgr::errorLog($msg,true,self::$class,__FUNCTION__,__LINE__,"File creation failed: ".$fn);
			}
			@file_put_contents($fn,"<?defined(\"FLEX_APP\") or die(\"Forbidden.\");?>\n".$data);
			@chmod($fn,0755);
		}
		return $c;
	}

	public static function wrap($id, $alias)
	{
		$res=array();
		@ob_start();
?>
	<div id="<?=self::$class?>" class="<?=self::$class?>">
		<div id="<?=self::$class?>-item-<?=$id?>" class="content-item _-<?=$alias?>">
<?
		$res[]=@ob_get_contents();
		@ob_end_clean();
		@ob_start();
?>
		</div>
	</div>
<?
		$res[]=@ob_get_contents();
		@ob_end_clean();
		return $res;
	}
}
?>
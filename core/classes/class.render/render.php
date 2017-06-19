<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
//типы http-ответа
define("RENDER_TYPE_NORMAL",0,false);
define("RENDER_TYPE_REDIR",1,false);
define("RENDER_TYPE_SILENT",2,false);
define("RENDER_TYPE_STATUS",3,false);
final class render
{
	private static $_runStep	=	0;
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $clMetas		=	array();
	private static $clScripts	=	array();
	private static $clStyles	=	array();
	private static $config		=	array();
	private static $mods		=	array();
	private static $session		=	array();
	private static $silentSent	=	false;
	private static $spots		=	array();
	private static $spotsCur	=	array();
	private static $styles		=	array();
	private static $type		=	-1;

	private static function _bindingsLoad()
	{
		foreach(self::$spots as $key=>$value)self::$mods[$key]=array();
		$cid=content::item("id");
		if(content::item("nolayout"))
		{
			$spots=self::_config("spotsNolayout");
			if(!@in_array(self::_config("contentSpot"),$spots))$spots[]=self::_config("contentSpot");
			$spots=implode(",",$spots);
		}
		else $spots=implode(",",self::$spotsCur);
		if($cid)$where=" || `ba`.`cid`!=$cid";
		else $where="";
		//ищем модули по правилу "загружать на всех страницах кроме..."
		$q="SELECT `m`.`id`,`m`.`class`,`m`.`core`,`m`.`srv`,`m`.`title`,`b`.`sid` AS `spot`,
		(`b`.`ord`-1) AS `ord`,`b`.`method`,`b`.`args`, `ba`.`cid`
		FROM ".db::tn("mods")." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `b` ON `b`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class."_bind_adds")." `ba` ON `ba`.`bid`=`b`.`id`
		WHERE `m`.`act`=1 AND `b`.`sid` in ({$spots}) AND `b`.`pages`='all' AND (`ba`.`cid` IS NULL{$where})
		ORDER BY `b`.`sid`,`b`.`ord`";
		$r=db::q($q,true);
		while($row=db::fetch($r))
		{
			$spot=0+$row["spot"];
			$ord=0+$row["ord"];
			self::$mods[$spot][$ord]=array();
			self::$mods[$spot][$ord]["id"]=0+$row["id"];
			self::$mods[$spot][$ord]["core"]=0+$row["core"];
			self::$mods[$spot][$ord]["srv"]=0+$row["srv"];
			self::$mods[$spot][$ord]["class"]=$row["class"];
			self::$mods[$spot][$ord]["title"]=$row["title"];
			self::$mods[$spot][$ord]["method"]=$row["method"];
			self::$mods[$spot][$ord]["args"]=$row["args"];
		}
		if($cid)
		{
			$q="SELECT `m`.`id`,`m`.`class`,`m`.`core`,`m`.`srv`,`m`.`title`,`b`.`sid` AS `spot`,
			(`b`.`ord`-1) AS `ord`,`b`.`method`,`b`.`args`, `ba`.`cid`
			FROM ".db::tn("mods")." `m`
			INNER JOIN ".db::tnm(self::$class."_binds")." `b` ON `b`.`mid`=`m`.`id`
			LEFT JOIN ".db::tnm(self::$class."_bind_adds")." `ba` ON `ba`.`bid`=`b`.`id`
			WHERE `m`.`act`=1 AND `b`.`sid` in ({$spots}) AND `b`.`pages`='none' AND `ba`.`cid`={$cid}
			ORDER BY `b`.`sid`,`b`.`ord`";
			$r=db::q($q,true);
			while($row=db::fetch($r))
			{
				$spot=0+$row["spot"];
				$ord=0+$row["ord"];
				if(!isset(self::$mods[$spot][$ord]))
				{
					self::$mods[$spot][$ord]=array();
					self::$mods[$spot][$ord]["id"]=0+$row["id"];
					self::$mods[$spot][$ord]["core"]=0+$row["core"];
					self::$mods[$spot][$ord]["srv"]=0+$row["srv"];
					self::$mods[$spot][$ord]["class"]=$row["class"];
					self::$mods[$spot][$ord]["title"]=$row["title"];
					self::$mods[$spot][$ord]["method"]=$row["method"];
					self::$mods[$spot][$ord]["args"]=$row["args"];
				}
			}
		}
	}

	private static function _buildStyleSheet($styles)
	{
		if(!is_array($styles) || !count($styles))return $empty;
		$cssData="";
		foreach($styles as $item=>$pars)
		{
			$class=$pars["class"];
			$ext=$pars["ext"];
			$name=$pars["name"];
			$file=$pars["file"];
			$found=$pars["found"];
			$tplDir=$pars["tplDir"];
			$fcont=false;
			if($found)$fcont=@file_get_contents($file);
			else $fcont=false;
			$cssData.="\n/* ------------- START> SECTION: ".($class?($class."[".$name."]"):"http://***")." ------------- */\n";
			if($fcont!==false)
			{
				if($ext)
				{
					$d=preg_replace("/http:\/\/(.*)/","$1",$file);
					$d=explode("/",$d,2);
					$d=$d[0];
					$fcont=str_replace("url('/","url('http://{$d}/",$fcont);
					$fcont=str_replace("url(/","url(http://{$d}/",$fcont);
				}
				else $fcont=str_replace("{\$modTplDir}",$tplDir,$fcont);
				$cssData.=str_replace("{\$modDataDir}",FLEX_APP_DIR_ROOT.FLEX_APP_DIR_DAT."/_".$class."/",$fcont);
			}
			else
			{
				$cssData.="/* File not found".(self::$c->debug()?(" [".$file."]."):".");
			}
			$cssData.="\n/* ------------- END> SECTION: ".($class?($class."[".$name."]"):"http://***")." ------------- */\n\n";
		}
		if($cssData)return $cssData;
		else return"/*Empty Style Sheet*/";
	}

	private static function _client()
	{
		//выводим скрипты ядра и расширений
		if(!count(self::$clScripts))return;
		foreach(self::$clScripts as $class=>$scripts)
		{
			$len=count($scripts);
			for($cnt=0;$cnt<$len;$cnt++)
			{
				$script=&$scripts[$cnt];
				if(!isset($script["core"]) || !$script["core"])continue;
				if(!isset($script["tpl"]) && (strpos($script["name"],"http")===0))$file=$script["name"];
				else
				{
					$modFl="client/".$class.$script["admin"]."/".$script["name"].".js";
					$file=FLEX_APP_DIR."/".$modFl;
					$found=@file_exists($file);
					if(FLEX_APP_DIR_SRC && !$found)
						$file=FLEX_APP_DIR_ROOT."?feh-rsc-get=js&path=/".$file;
					else
						$file=FLEX_APP_DIR_ROOT.$file;
				}
?>
	<script type="text/javascript" src="<?=$file?>" charset="utf-8"></script>
<?
			}
		}
		//выводим скрипт построения клиента
?>
	<script type="text/javascript">
		//поскольку лучшего варианта защитить
		//глобальный указатель на клиент не нашлось, делаем так:
		(function() {
			"use strict";
			var client = window.FlexClient;
			delete window.FlexClient;
			Object.defineProperty(window, "FlexClient", {
				configurable: false,
				enumerable: false,
  				value: new client({
					cfg: {
						<?=("_exts: ".self::$c->clientConfig("core").",\r\n")?>
						<?=("_mods: ".self::$c->clientConfig("mods").",\r\n")?>
						<?=("appName: \"".self::$c->config("","siteName")."\",\r\n")?>
						<?=("initPoint: \""."core-client-init"."\",\r\n")/*!!!вынести в конфиг*/?>
						dirs: {
							root: (""<?=(" + \"".FLEX_APP_DIR_ROOT."\"")?>),
						},
						<?=("lang: \"".self::$c->lang()."\"")?>
					}
				}),
				writable: false
			});
			//загружаем системные расширения
			var api = FlexClient.extensionsLoad();
			Object.defineProperty(FlexClient, "module", {
				configurable: false,
				enumerable: false,
  				value: api[0],
				writable: false
			});
		})();
	</script>
<?
		//выводим скрипты модулей
		$len=count(self::$clScripts);
		if(!$len)return;
		foreach(self::$clScripts as $class=>$scripts)
		{
			$len=count($scripts);
			for($cnt=0;$cnt<$len;$cnt++)
			{
				$script=&$scripts[$cnt];
				if(isset($script["core"]) && $script["core"])continue;
				if(!isset($scripts[$cnt]["tpl"]) && (strpos($scripts[$cnt]["name"],"http")===0))$file=$scripts[$cnt]["name"];
				else
				{
					$modFl="client/".$class.$scripts[$cnt]["admin"]."/".$scripts[$cnt]["name"].".js";
					$file=(isset($scripts[$cnt]["core"])?(FLEX_APP_DIR."/"):"").$modFl;
					$found=@file_exists($file);
					if(FLEX_APP_DIR_SRC && !$found)
						$file=FLEX_APP_DIR_ROOT."?feh-rsc-get=js&path=/".$file;
					else
						$file=FLEX_APP_DIR_ROOT.$file;
				}
?>
	<script type="text/javascript" src="<?=$file?>" charset="utf-8"></script>
<?
			}
		}
	}

	private static function _clientStyleAdd($class,$name="",$core=false,$admin=false,$ret=false)
	{
		if(!is_string($class))
		{
			if(!is_object($class))return false;
			$cl=explode("\\",get_class($class));
			$clname=array_pop($cl);
		}
		if(!$name)$name=$class;
		$style=array();
		$style["http"]=(strpos($name,"http")===0);
		$style["class"]=$class;
		$style["name"]=$name;
		if($name && (strpos($name,"/")!==false))
		{
			$style["admin"]="";
			$style["core"]=false;
			$style["tpl"]="";
			$style["link"]=true;
		}
		else
		{
			if(method_exists(__NAMESPACE__."\\".$class,self::$c->modHookName("template")))
				$tpl=call_user_func_array(array(__NAMESPACE__."\\".$class,self::$c->modHookName("template")),array($class));
			else $tpl=self::_config("template");
			$style["admin"]=(@defined("ADMIN_MODE") && $admin)?"/admin":"";
			$style["core"]=$core;
			$style["tpl"]=$tpl;
			$style["link"]=false;
		}
		if($ret)return $style;
		else
		{
			self::$clStyles[]=$style;
			return true;
		}
	}

	private static function _clientStyles()
	{
		//добавляем свои стили, основной (render.css) - вначало, ender.css - вконец
		array_unshift(self::$clStyles,self::_clientStyleAdd(self::$class,self::$class,true,false,true));
		self::_clientStyleAdd(self::$class,"ender",true);
		$styles=array();
		$len=count(self::$clStyles);
		for($cnt=0;$cnt<$len;$cnt++)
		{
			//пропускаем внешний стиль
			if(self::$clStyles[$cnt]["http"])continue;
			$item=array();
			//обрабатываем...
			$style=&self::$clStyles[$cnt];
			$item["class"]=$style["class"];
			if($style["link"])
			{
				$path=explode("/",$style["name"]);
				if(count($path))$name=array_pop($path);
				else $name=$style["name"];
				$item["ext"]=false;
				$item["file"]=$style["name"];
				$item["found"]=@file_exists($item["file"]);
				$item["name"]=$name;
				$item["tplDir"]=FLEX_APP_DIR_ROOT.implode("/",$path)."/";
			}
			else
			{
				$tpl=(isset($style["tpl"])?$style["tpl"]:self::_config("template"));
				$core=(isset($style["core"]) && $style["core"]);
				$modRoot=$core?(FLEX_APP_DIR."/"):"";
				$modTpl=FLEX_APP_DIR_TPL."/".($core?"default/":$tpl)."/";//для ядра всегда используется шаблон default
				$modPath=$style["class"].$style["admin"]."/styles/";
				$file=$modRoot.$modTpl.$modPath.$style["name"].".css";
				$found=@file_exists($file);
				if(FLEX_APP_DIR_SRC && !$found)
				{
					$modRoot=FLEX_APP_DIR_SRC.($core?(".core/".FLEX_APP_DIR):(".classes/.".$style["class"]))."/";
					$modTpl=FLEX_APP_DIR_TPL."/default/";
					$file=$modRoot.$modTpl.$modPath.$style["name"].".css";
					$found=@file_exists($file);
					$tplDir=FLEX_APP_DIR_ROOT."?feh-rsc-get=auto&path=/".($core?(FLEX_APP_DIR."/"):"").$modTpl.str_replace("styles/","",$modPath);
				}
				else
				{
					$tplDir=FLEX_APP_DIR_ROOT.($core?(FLEX_APP_DIR."/"):"").$modTpl.str_replace("styles/","",$modPath);
				}
				$item["ext"]=false;
				$item["file"]=$file;
				$item["found"]=$found;
				$item["name"]=$style["name"];
				$item["tplDir"]=$tplDir;
			}
			$styles[]=$item;
		}
		//сохраняем список для css.php
		$md=md5(serialize($styles));
		$url=cache::check(self::$class,$md,self::_config("cacheEvalCss"),"css");
		if(!$url)
		{
			$cont=self::_buildStyleSheet($styles);
			if(!$cont)die("CSS render error.");
			$url=cache::set(self::$class,$md,self::_config("cacheEvalCss"),$cont,false,"css");
			if(!$url)die("CSS render error.");
		}
?>
	<link type="text/css" href="<?=(FLEX_APP_DIR_ROOT.$url)?>" media="all" rel="stylesheet" />
<?
		for($cnt=0;$cnt<$len;$cnt++)
		{
			if(self::$clStyles[$cnt]["http"])
			{
?>
	<link type="text/css" href="<?=self::$clStyles[$cnt]["name"]?>" media="all" rel="stylesheet" />
<?
			}
		}
	}

	private static function _config($name,$params=false)
	{
		//var @params is set for future purposes
		if(isset(self::$config[$name]))return self::$config[$name];
		else return "";
	}

	private static function _configLoad()
	{
		self::$config=array(
			"cacheEvalCss"		=>	120,
			"cacheEvalJs"		=>	120,
			"contentSpot"		=>	13,
			"elMainStyle"		=>	"width:1000px;",
			"elHeaderStyle"		=>	"",
			"elCenterStyle"		=>	"",
			"elFooterStyle"		=>	"",
			"elFooterHeight"	=>	"225px",
			"elFooterDynamic"	=>	false,
			"elColHLWidth"		=>	"220px",
			"elColHRWidth"		=>	"220px",
			"elColCLWidth"		=>	"220px",
			"elColCRWidth"		=>	"220px",
			"elColFLWidth"		=>	"220px",
			"elColFRWidth"		=>	"220px",
			"jquery"			=>	"jquery-1.10.2.min.js",
			"spotsDefault"		=>	array(1,2,3,4,5,6,10,11,13,16,21,22),
			"spotsNolayout"		=>	array(1,4,22),
			"template"			=>	"default"
		);
		$cfg=self::$c->config(self::$class,false,true);
		foreach($cfg as $name=>$vals)
		{
			switch($name)
			{
				case "cacheEvalCss":
				case "cacheEvalJs":
				case "contentSpot":
					self::$config[$name]=0+$cfg[$name]["value"];
					break;
				case "elFooterDynamic":
					self::$config[$name]=(($cfg[$name]["value"]=="1") || $cfg[$name]["value"]=="true")?true:false;
					break;
				case "spotsDefault":
				case "spotsNolayout":
					self::$config[$name]=explode(",",$cfg[$name]["value"]);
					foreach(self::$config[$name] as $key=>$spot)self::$config[$name][$key]=0+(trim($spot));
					break;
				default:
					if($cfg[$name]["value"])self::$config[$name]=$cfg[$name]["value"];
			}
		}
		if(self::$config["elFooterDynamic"])self::$config["elFooterHeight"]="";
	}

	private static function _hasSpot($sid)
	{
		return in_array($sid,self::$spotsCur);
	}

	private static function _hasSpotMods($spots)
	{
		if(is_int($spots))
			return true && count(self::$mods[$spots]);
		foreach($spots as $spot)
			if(count(self::$mods[$spot]))return true;
		return false;
	}

	private static function _html1Header()
	{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?=self::$c->config("","siteName")?> - <?=content::title(false,false)?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="Bogdan Nazar (info@itechserv.ru)" />
	<meta name="generator" content="FlexEngine <?=self::$c->version(true)?> (http://flexengine.ru)" />
	<meta name="description" content="<?=content::item("meta_desc")?>" />
	<meta name="keywords" content="<?=content::item("meta_kw")?>" /><?
		self::_metas();
		if(file_exists("favicon.ico")):?>
	<link rel="shortcut icon" type="image/ico" href="/favicon.ico" /><?endif;
		self::_clientStyles();
		self::_client();?>
</head>
<body leftmargin="0" topmargin="0" rightmargin="0" bottommargin="0" marginwidth="0"><?
		self::_spot(4,true,false,false,true);
	}

	private static function _html2Footer()
	{
		self::_spot(22,true,false,array("height"=>0,"overflow"=>"hidden"),true);
?>
<!-- session started <?=$_SESSION["FLEX_APP_STARTED"]?> (<?=date("Y-m-d H:i:s",$_SESSION["FLEX_APP_STARTED"])?>); executed in <?=((microtime(true)-$_SESSION["FLEX_APP_RENEWED"])*1000)?> msec -->
<!-- mysql server queried: successfull[<?=db::qc(0)?>], erroneous[<?=db::qc(1)?>] -->
</body>
</html>
<?
 	}

	private static function _metas()
	{
		foreach(self::$clMetas as $id=>$val)echo $val;
	}

	private static function _page1Header()
	{
		self::_html1Header();
		$nameForm=self::$class."-".self::$c->config("","elNameForm");
		$nameAction=self::$class."-".self::$c->config("","elNameAction");
		$ms=self::_config("elMainStyle");
		$hs=self::_config("elHeaderStyle");
		$colLW=self::_config("elColHLWidth");
		$colRW=self::_config("elColHRWidth");
		$clear=false;
		if(!$hs7=self::_hasSpot(7))$colLW="0";
		else $clear="left";
		if(!$hs9=self::_hasSpot(9))$colRW="0";
		else $clear=($clear?"both":"right");
?>
<form id="<?=$nameForm?>" action="" method="post" enctype="multipart/form-data" onsubmit="<?=(self::$class.".")?>onSubmit()">
	<input type="hidden" id="<?=$nameAction?>" name="<?=$nameAction?>" value="" />
	<div id="core-client-init"><!-- (!!!) прописать id в конфиг --></div>
	<?self::_spot(5,false);?>
	<div id="<?=self::$class?>-main" class="<?=self::$class?>" style="<?=$ms?>">
		<div id="<?=self::$class?>-header" style="<?=$hs?>"><?
		self::_spot(6,true,"header-top",false,true);
		self::_spot(7,true,"header-left",array("width"=>$colLW),true);
		self::_spot(9,true,"header-right",array("width"=>$colRW),true);
		self::_spot(8,true,"header-mid",array("margin"=>("0 ".$colRW." 0 ".$colLW)),true);
		echo($clear?"<div class=\"".self::$class."-clear-".$clear."\"></div>":"");
		self::_spot(10,true,"header-bottom",false,true);?>
		</div>
<?
	}

	private static function _page2Center()
	{
		$cs=self::_config("elCenterStyle");
		if(!self::_hasSpotMods(array(16,17,18,19,20)))$fh="0";
		else $fh=self::_config("elFooterHeight");
		$colLW=self::_config("elColCLWidth");
		$colRW=self::_config("elColCRWidth");
		$clear=false;
		if(!$hs12=self::_hasSpot(12))$colLW="0";
		else $clear="left";
		if(!$hs14=self::_hasSpot(14))$colRW="0";
		else $clear=($clear?"both":"right");
?>
		<div id="<?=self::$class?>-center" style="<?=$cs?>"><?
		self::_spot(11,true,"center-top",false,true);
		self::_spot(12,true,"center-left",array("width"=>$colLW),false);
		self::_spot(14,true,"center-right",array("width"=>$colRW),false);
		self::_spot(13,true,"center-mid",array("margin"=>"0 {$colRW} 0 {$colLW}"),false);
		echo($clear?"<div class=\"".self::$class."-clear-".$clear."\"></div>":"");
		self::_spot(15,true,"center-bottom",false,true);?>
			<div id="<?=self::$class?>-footer-margin" style="<?=("height:".$fh.";")?>"></div>
		</div>
	</div>
<?
	}

	private static function _page3Footer()
	{
		$fs=self::_config("elFooterStyle");
		if(!self::_hasSpotMods(array(16,17,18,19,20)))$fh="0";
		else $fh=self::_config("elFooterHeight");
		$fd=self::_config("elFooterDynamic");
		$colLW=self::_config("elColFLWidth");
		$colRW=self::_config("elColFRWidth");
		$clear=false;
		if(!$hs17=self::_hasSpot(17))$colLW="0";
		else $clear="left";
		if(!$hs19=self::_hasSpot(19))$colRW="0";
		else $clear=($clear?"both":"right");?>
	<div class="<?=self::$class?>" id="<?=self::$class?>-footer" style="<?=($fh?("height:".$fh.";margin-top:-".$fh.";".$fs):"")?>"><?
		self::_spot(16,true,"footer-top",false,true);
		self::_spot(17,true,"footer-left",array("width"=>$colLW),true);
		self::_spot(19,true,"footer-right",array("width"=>$colRW),true);
		self::_spot(18,true,"footer-mid",array("margin"=>"0 {$colRW} 0 {$colLW};"),true);
		echo($clear?"<div class=\"".self::$class."-clear-".$clear."\"></div>":"");
		self::_spot(20,true,"footer-bottom",false,true);?>
	</div><?
		self::_spot(21,true,false,array("height"=>0,"overflow"=>"hidden"),true);
		if($fd){?>
	<script type="text/javascript"><?=(self::$class.".")?>footerUpdate();</script><?}?>
</form>
<?
		self::_html2Footer();
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

	/**
	* Функция создает и заполняет спот, вызывая методы
	* рендеринга у модулей, привязанных к этому споту
	*
	* @param integer $sid - номер спота
	* @param mixed $htmlTag - формирующий тег, string или boolen
	* @param mixed $cssClass - дополнительный css-класс, string или boolen
	* @param mixed $styles - дополнительные стили, string или boolen
	* @param boolean $ignoreEmpty - игнорировать пустой спот
	*/
	private static function _spot($sid,$htmlTag=false,$cssClass=false,$styles=false,$ignoreEmpty=false)
	{
		if($htmlTag && (!is_string($htmlTag)))$htmlTag="div";
		$mods=self::modsInSpot($sid);
		$empty=(count($mods)===0);
		$methodDef=self::$c->modHookName("render");
		$contSpot=self::_config("contentSpot");
		if($sid==$contSpot)$ignoreEmpty=false;//верстка контент-spot'а не может быть пропущена
		if($htmlTag && (!$empty || !$ignoreEmpty))
		{
			if(is_array($styles))$styles=self::_spotStyles($sid,$styles);
			else $styles=self::_spotStyles($sid);
?>
	<<?=$htmlTag?> id="<?=self::$class?>-spot-<?=$sid?>" <?=(is_string($cssClass)?" class=\"{$cssClass}\"":"")?><?=($styles?$styles:"")?>>
<?
		}
		//выводим контент, если данный спот является контентным
		if($sid==$contSpot)
		{
			content::title(true,true);
			self::_spot(2,true,false,false,true);
			content::_render($sid);
		}
		//перебираем все модул в споте и вызываем метод рендеринга
		foreach($mods as $mod)
		{
			$class=__NAMESPACE__."\\".$mod["class"];
			$args=explode(",",$mod["args"]);
			if(!$args[0])$args=array();
			//дополнительные переменные $instance, $sid & $method
			//для пользовательских модулей
			if(!$mod["core"])array_unshift($args,$class,$sid,$mod["method"]);
			//проверяем метод "по умолчанию"
			//(этот метод сам вызовет пользовательский метод)
			$method=($mod["core"]?"_render":$methodDef);
			if(!@method_exists($class,$method))
			{
				//если метод "по умолчанию" не обнаружен,
				//то проверяем пользовательский метод
				$method=$mod["method"];
				if(!$method || !@method_exists($class,$method))$method="";
			}
			//если метод найден, то выполняем
			if($method)
			{
				if(is_array($args))@call_user_func_array(array($class,$method),$args);
				else $class::$method($class);
			}
		}
		if($sid==$contSpot)self::_spot(3,true,false,false,true);
		if($htmlTag && (!$empty || !$ignoreEmpty))
		{
?>
		</<?=$htmlTag?>>
<?
		}
	}

	private static function _spotStyles($spot,$defstyles=array())
	{
		if(!isset(self::$styles[$spot]) || !is_array(self::$styles[$spot]) || !count(self::$styles[$spot]))$styles="";
		else $styles=implode(";",self::$styles[$spot]).";";
		if(is_array($defstyles) && count($defstyles))
		{
			$dstyles="";
			foreach($defstyles as $name=>$value)$dstyles.=$name.":".$value.";";
		} else $dstyles="";
		if($dstyles || $styles)return" style=\"{$dstyles}{$styles}\"";
		else return"";
	}

	private static function _spotStylesLoad()
	{
		$cid=content::item("id");
		$q="SELECT `sid`,`styles` FROM ".db::tnm(self::$class."_spot_styles")." WHERE `cid`=".$cid." AND `sid` IN (".implode(",",self::$spotsCur).")";
		$r=db::q($q,true);
		while($row=db::fetch($r,"r"))
		{
			$sid=0+$row[0];
			$style=rtrim($row[1],";");
			if(!isset(self::$styles[$sid]))self::$styles[$sid]=array();
			self::$styles[$sid][]=$style;
		}
	}

	private static function _spotsLoad()
	{
		self::$spots[0]="system";
		$r=db::q("SELECT `id`,`alias` FROM ".db::tnm(self::$class."_spots")." ORDER BY `id`",true);
		while($row=db::fetch($r,"r"))self::$spots[0+$row[0]]=$row[1];
	}

	private static function _typeDetermine()
	{
		self::$type=RENDER_TYPE_NORMAL;
		if(isset($_REQUEST["silent"]))self::$type=RENDER_TYPE_SILENT;
		if(isset($_REQUEST["status"]))self::$type=RENDER_TYPE_STATUS;
		if((self::$type==RENDER_TYPE_SILENT) || (self::$type==RENDER_TYPE_STATUS))return;
		if(count($_POST) || count($_FILES))self::$type=RENDER_TYPE_REDIR;
	}

	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
		$spots=content::spots();
		if(is_array($spots) && count($spots))self::$spotsCur=$spots;
		else self::$spotsCur=self::_config("spotsDefault");
		self::_bindingsLoad();
		self::_spotStylesLoad();
		//подключаем собственные ресурсы
		if(!self::silent())
		{
			$data=array(
				"elems"	=> array(
					"Action"	=>	self::$c->config("","elNameAction"),
					"Form" 		=>	self::$c->config("","elNameForm")
				),
				"page"	=>	array(
					"alias"		=>	content::item("alias"),
					"template"	=>	self::$c->template(),
					"title"		=>	content::title(false,false)
				)
			);
			self::$c->addConfig(self::$class,$data,true);
			self::addScript(self::$class,self::$class,true);
		}
	}

	public static function _init()
	{
		if(self::$_runStep)return;
		self::$_runStep++;
		if(@strpos(self::$class,"\\")!==false)
		{
			$cl=@explode("\\",self::$class);
			self::$class=$cl[count($cl)-1];
		}
		self::$c=_a::core();
		self::_configLoad();
		self::_sessionRead();
		self::_spotsLoad();
	}

	public static function _()
	{
		if(render::$type==RENDER_TYPE_SILENT)
		{
			//not implemented yet: common silent answers
			return;
		}
		if(render::$type!=RENDER_TYPE_NORMAL)return;
		if(content::item("nolayout"))
		{
			self::_html1Header();
			$nameForm=self::$class."-".self::$c->config("","elNameForm");
			$nameAction=self::$class."-".self::$c->config("","elNameAction");
?>
<form id="<?=$nameForm?>" action="" method="post" enctype="multipart/form-data" onsubmit="<?=(self::$class.".")?>onSubmit()">
	<input type="hidden" id="<?=$nameAction?>" name="<?=$nameAction?>" value="" />
	<script type="text/javascript"><?=(self::$class.".")?>_init();</script>
<?
		self::_spot(5,false);
		self::_spot(self::_config("contentSpot"));
		self::_spot(21,false);
?>
</form>
<?
			self::_html2Footer();
		}
		else
		{
			self::_page1Header();
			self::_page2Center();
			self::_page3Footer();
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

	public static function addScript($class,$name="",$core=false,$admin=false)
	{
		if(is_string($class))$clname=$class;
		else
		{
			if(!is_object($class))return false;
			$cl=explode("\\",get_class($class));
			$clname=array_pop($cl);
		}
		if($name && (@strpos($name,"http")===0))
		{
			if(@array_key_exists($clname,self::$clScripts))
			{
				$len=@count(self::$clScripts[$clname]);
				for($cnt=0;$cnt<$len;$cnt++)
					if(self::$clScripts[$clname][$cnt]["name"]==$name)return true;
				$new=$len;
			}
			else $new=0;
			self::$clScripts[$clname][$new]["name"]=$name;
			return true;
		}
		if(!$name)$name=$clname;
		$new=0;
		if(@array_key_exists($clname,self::$clScripts))
		{
			$len=@count(self::$clScripts[$clname]);
			for($cnt=0;$cnt<$len;$cnt++)
			{
				if(self::$clScripts[$clname][$cnt]["name"]==$name)return true;
			}
			$new=$len;
		}
		self::$clScripts[$clname][$new]["name"]=$name;
		if($core)self::$clScripts[$clname][$new]["core"]=$core;
		self::$clScripts[$clname][$new]["admin"]=(@defined("ADMIN_MODE") && $admin)?"/admin":"";
		return true;
	}

	public static function addStyle($class,$name="",$core=false,$admin=false)
	{
		return self::_clientStyleAdd($class,$name,$core,$admin);
	}

	public static function html1Header()
	{
		$this->_html1Header();
	}

	public static function html2Footer()
	{
		$this->_html2Footer();
	}

	public static function metaAdd($meta="")
	{
		if($meta)self::$clMetas[]=$meta;
	}

	public static function modBindsList($mod="",$method="",$cid=0)
	{
		$binds=array();
		if(!$mod)return $binds;
		$hookRender=self::$c->modHookName(self::$class);
		if(!$method || ($method==$hookRender))$methodSql="((`b`.`method`='') OR (`b`.`method`='{$hookRender}'))";
		else $methodSql="`b`.`method`='{$method}'";
		$q="SELECT `c`.`id`,`c`.`alias`,`b`.`sid`,`b`.`ord`,`b`.`pages`,`b`.`args` FROM ".db::tnm(self::$class."_binds")." `b`
		INNER JOIN ".db::tn("mods")." `m` ON `m`.`id`=`b`.`mid`
		LEFT JOIN ".db::tnm(self::$class."_bind_adds")." `ba` ON `ba`.`bid`=`b`.`id`
		LEFT JOIN ".db::tnm("content")." `c` ON `c`.`id`=`ba`.`cid`
		WHERE `m`.`class`='{$mod}' AND {$methodSql}".($cid?(" AND (ISNULL(`c`.`id`) OR (`c`.`id`={$cid}))"):"");
		$r=db::q($q,true);
		while($rec=db::fetch($r))
		{
			$rec["id"]=0+$rec["id"];
			$rec["alias"]="".$rec["alias"];
			$rec["sid"]=0+$rec["sid"];
			$rec["ord"]=0+$rec["ord"];
			$rec["pages"]="".$rec["pages"];
			$rec["args"]="".$rec["args"];
			if($rec["pages"]=="none" && (!$rec["id"]))continue;
			$binds[]=@array_merge(array(),$rec);
		}
		return $binds;
	}

	public static function modsBound()
	{
		$fmods=array();
		$found=array();
		foreach(self::$mods as $spot=>$mods)
		{
			foreach($mods as $ord=>$mod)
			{
				if(!@in_array($mod["id"],$found))
				{
					$found[]=$mod["id"];
					$fmods[]=$mod;
				}
			}
		}
		return $fmods;
	}

	public static function modsInSpot($sid)
	{
		if(!isset(self::$mods[$sid]))return array();
		return self::$mods[$sid];
	}

	public static function silent()
	{
		return (self::$type==RENDER_TYPE_SILENT);
	}

	public static function silentResponseSend($data,$isJson=true,$callback=false)
	{
		//проверки
		if(self::$silentSent)return false;
		if(!is_string($callback) || !$callback)$callback=false;
		//если $data - это json, то делаем дополнительные проверки
		if($isJson)
		{
			if(is_array($data))$data=lib::jsonMake($data,NULL,($callback?true:false),true);
			else
			{
				//предполагается, что $data - валидно заэкранированная строка,
				//содержащая json, т.е. переводы строки заменены \r\n, и в значениях
				//заэкранированы символы [\] и ["]
				if($callback)$data=lib::jsonEscape($data);
			}
		}
		//задаем Content-Type и если нужно
		//обворачиваем ответ в $callback (JSONP)
		$ctype="text/html";
		if($callback)
		{
			$data=$callback."(\"".$data."\");";
			$ctype="application/javascript";
		}
		else
		{
			if($isJson)$ctype="application/json";
		}
		//отсылаем ответ
		header("Content-Type: ".$ctype."; charset=utf-8");
		echo $data;
		self::$silentSent=true;
	}

	public static function silentXResponseSend()
	{
		$gkey=self::$class."-xkey";
		$ckey=self::$class."-xcb";
		$skey=FLEX_APP_NAME."-".self::$class."-xrequest-";
		header("Content-Type: application/javascript; charset=utf-8");
		if(!isset($_GET[$gkey]))
		{
			echo"console.log(\"Ошибка определения статуса операции: ключ [".$gkey."] не определен.\");";
			return;
		}
		$key=$_GET[$gkey];
		$seskey=$skey.$key;
		if(!isset($_SESSION[$seskey]))
		{
			echo"console.log(\"Ошибка определения статуса операции: неизвестная операция \$_SESSION[".$seskey."] не определен.\");";
			return;
		}
		if(!is_array($_SESSION[$seskey]))
		{
			$_SESSION[$seskey]=array();
			$_SESSION[$seskey]["data"]="";
		}
		$rkey=FLEX_APP_NAME."-".self::$class."-xrequests";
		if(!isset($_GET[$ckey]))
		{
			echo"console.log(\"Ошибка отправки статуса операции: callback-функция \$_GET[".$ckey."] не определена. Статус: \");";
			echo"console.log(\"".$_SESSION[$seskey]["data"]."\");";
			unset($_SESSION[$seskey]);
			if(isset($_SESSION[$rkey]))unset($_SESSION[$rkey]);
			return;
		}
		echo"".$_GET[$ckey]."(\"".$_SESSION[$seskey]["data"]."\",\"{$key}\");";
		unset($_SESSION[$seskey]);
		if(isset($_SESSION[$rkey]))unset($_SESSION[$rkey]);
	}

	public static function silentXResponseSet($data,$isJson=true)
	{
		$resp=str_replace("\"","\\\"",$data);
		if(!isset($_REQUEST["render-action-key"]))
		{
			echo"
			<script type=\"text/javascript\">\n
				console.log(\"Can't save action result, action-key is not set. Response data:\n\");\n
				console.log(\"".$resp."\");\n
			</script>";
			return false;
		}
		$key=$_REQUEST["render-action-key"];
		if(!$key)
		{
			echo"
			<script type=\"text/javascript\">\n
			console.log(\"Can't save action result, action-key is empty. Response data:\n\");\n
			console.log(\"".$resp."\");\n
			</script>";
			return false;
		}
		$skey=FLEX_APP_NAME."-".self::$class."-xrequest-".$key;
		$rkey=FLEX_APP_NAME."-".self::$class."-xrequests";
		$sresp=array(
			"data"	=> $data,
			"key"	=> $key,
			"time"	=> time()
		);
		$_SESSION[$skey]=&sresp();
		//запоминааем также ключ, чтоб если возникнет ошибка на клиенте,
		//можно было удалить "зависший" ответ из сессии
		if(!isset($_SESSION[$rkey]))$_SESSION[$rkey]=array();
		$_SESSION[$rkey][$key]=true;
		echo"<script type=\"text/javascript\">console.log(\"Action [".$key."] was processed.\");</script>";
		return true;
	}

	public static function template()
	{
		return self::_config("template");
	}

	public static function type()
	{
		if(self::$type==-1)self::_typeDetermine();
		return self::$type;
	}

	public static function unbind($class,$oid)
	{
		return true;
	}
}
?>
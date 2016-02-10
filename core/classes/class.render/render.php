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
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $clMetas		=	array();
	private static $clScripts	=	array();
	private static $clStyles	=	array();
	private static $config		=	array();
	private static $mods		=	array();
	private static $session		=	array();
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
			$spots=self::$config["spotsNolayout"];
			if(!@in_array(self::$config["contentSpot"],$spots))$spots[]=self::$config["contentSpot"];
			$spots=implode(",",$spots);
		}
		else $spots=implode(",",self::$spotsCur);
		if($cid)$where=" || `ba`.`cid`!=$cid";
		else $where="";
		//ищем модули по правилу "загружать на всех страницах кроме..."
		$q="SELECT `m`.`id`,`m`.`class`,`m`.`core`,`m`.`title`,`b`.`sid` AS `spot`,
		(`b`.`ord`-1) AS `ord`,`b`.`method`,`b`.`args`, `ba`.`cid`
		FROM ".db::tn("mods")." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `b` ON `b`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class."_bind_adds")." `ba` ON `ba`.`bid`=`b`.`id`
		WHERE `m`.`act`=1 AND `b`.`sid` in ({$spots}) AND `b`.`pages`='all' AND (`ba`.`cid` IS NULL{$where})
		ORDER BY `b`.`sid`,`b`.`ord`";
		$r=db::q($q,true);
		while($row=@mysql_fetch_assoc($r))
		{
			$spot=0+$row["spot"];
			$ord=0+$row["ord"];
			self::$mods[$spot][$ord]=array();
			self::$mods[$spot][$ord]["id"]=0+$row["id"];
			self::$mods[$spot][$ord]["core"]=0+$row["core"];
			self::$mods[$spot][$ord]["class"]=$row["class"];
			self::$mods[$spot][$ord]["title"]=$row["title"];
			self::$mods[$spot][$ord]["method"]=$row["method"];
			self::$mods[$spot][$ord]["args"]=$row["args"];
		}
		if($cid)
		{
			$q="SELECT `m`.`id`,`m`.`class`,`m`.`core`,`m`.`title`,`b`.`sid` AS `spot`,
			(`b`.`ord`-1) AS `ord`,`b`.`method`,`b`.`args`, `ba`.`cid`
			FROM ".db::tn("mods")." `m`
			INNER JOIN ".db::tnm(self::$class."_binds")." `b` ON `b`.`mid`=`m`.`id`
			LEFT JOIN ".db::tnm(self::$class."_bind_adds")." `ba` ON `ba`.`bid`=`b`.`id`
			WHERE `m`.`act`=1 AND `b`.`sid` in ({$spots}) AND `b`.`pages`='none' AND `ba`.`cid`={$cid}
			ORDER BY `b`.`sid`,`b`.`ord`";
			$r=db::q($q,true);
			while($row=@mysql_fetch_assoc($r))
			{
				$spot=0+$row["spot"];
				$ord=0+$row["ord"];
				if(!isset(self::$mods[$spot][$ord]))
				{
					self::$mods[$spot][$ord]=array();
					self::$mods[$spot][$ord]["id"]=0+$row["id"];
					self::$mods[$spot][$ord]["core"]=0+$row["core"];
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
		    		$cssData.=str_replace("url(/","url(http://{$d}/",$fcont);
			    }
			    else $cssData.=str_replace("{\$modTplDir}",$tplDir,$fcont);
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
		$dir=FLEX_APP_DIR."/client/".self::$class."/";
		$file=$dir.self::$config["jquery"];
		$found=@file_exists($file);
		if(FLEX_APP_DIR_SRC && !$found)
			$file=FLEX_APP_DIR_ROOT."?feh-rsc-get=js&path=/".$file;
		else
			$file=FLEX_APP_DIR_ROOT.$file;
?>
	<script type="text/javascript" src="<?=$file?>"></script>
<?
		$file=$dir.self::$class.".js";
		$found=@file_exists($file);
		if(FLEX_APP_DIR_SRC && !$found)
			$file=FLEX_APP_DIR_ROOT."?feh-rsc-get=js&path=/".$file;
		else
			$file=FLEX_APP_DIR_ROOT.$file;
?>
	<script type="text/javascript" src="<?=$file?>"></script>
<?
		$len=count(self::$clScripts);
		if(!$len)return;
		foreach(self::$clScripts as $class=>$scripts)
		{
			$len=count($scripts);
			for($cnt=0;$cnt<$len;$cnt++)
			{
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

	private static function _clientStyles()
	{
		$styles=array();
		$dir=FLEX_APP_DIR_TPL."/".self::$config["template"]."/".self::$class;
		$file=FLEX_APP_DIR."/".$dir."/styles/".self::$class.".css";
		$found=@file_exists($file);
		if(FLEX_APP_DIR_SRC && !$found)
		{
			$file=FLEX_APP_DIR_SRC.".core/".$file;
			$tplDir=FLEX_APP_DIR_ROOT."?feh-rsc-get=auto&path=/".FLEX_APP_DIR."/".$dir."/";
			$found=@file_exists($file);
		}
		else
		{
			$tplDir=FLEX_APP_DIR_ROOT.FLEX_APP_DIR."/".$dir."/";
		}
		$styles[]=array("class"=>self::$class,"ext"=>false,"file"=>$file,"found"=>$found,"name"=>self::$class,"tplDir"=>$tplDir);//ext - external script
		$len=count(self::$clStyles);
		for($cnt=0;$cnt<$len;$cnt++)
		{
			$item=array();
			if(self::$clStyles[$cnt]["http"])continue;
			$item["class"]=self::$clStyles[$cnt]["class"];
			if(self::$clStyles[$cnt]["link"])
			{
				$path=explode("/",self::$clStyles[$cnt]["name"]);
				if(count($path))$name=array_pop($path);
				else $name=self::$clStyles[$cnt]["name"];
				$item["ext"]=false;
				$item["file"]=self::$clStyles[$cnt]["name"];
				$item["found"]=@file_exists($item["file"]);
				$item["name"]=$name;
				$item["tplDir"]=FLEX_APP_DIR_ROOT.implode("/",$path)."/";
			}
			else
			{
				$tpl=(isset(self::$clStyles[$cnt]["tpl"])?self::$clStyles[$cnt]["tpl"]:self::$c->template());
				$core=(isset(self::$clStyles[$cnt]["core"]) && self::$clStyles[$cnt]["core"]);
				$corePath=$core?(FLEX_APP_DIR."/"):"";
				$dir=FLEX_APP_DIR_TPL."/".$tpl."/".self::$clStyles[$cnt]["class"].self::$clStyles[$cnt]["admin"];
				$file=$dir."/styles/".self::$clStyles[$cnt]["name"].".css";
				$found=@file_exists($corePath.$file);
				if(FLEX_APP_DIR_SRC && !$found)
				{
					$file=FLEX_APP_DIR_SRC.($core?(".core/".FLEX_APP_DIR):(".classes/.".self::$clStyles[$cnt]["class"]))."/".$file;
					$tplDir=FLEX_APP_DIR_ROOT."?feh-rsc-get=auto&path=/".$corePath.$dir."/";
					$found=@file_exists($file);
				}
				else
				{
					$tplDir=FLEX_APP_DIR_ROOT.$corePath.$dir."/";
				}
				$item["ext"]=false;
				$item["file"]=$file;
				$item["found"]=$found;
				$item["name"]=self::$clStyles[$cnt]["name"];
				$item["tplDir"]=$tplDir;
			}
			$styles[]=$item;
		}
		$dir=FLEX_APP_DIR_TPL."/".self::$config["template"]."/".self::$class;
		$file=FLEX_APP_DIR."/".$dir."/styles/ender.css";
		$found=@file_exists($file);
		if(FLEX_APP_DIR_SRC && !$found)
		{
			$file=FLEX_APP_DIR_SRC.".core/".$file;
			$tplDir=FLEX_APP_DIR_ROOT."?feh-rsc-get=auto&path=/".FLEX_APP_DIR."/".$dir."/";
			$found=@file_exists($file);
		}
		else
		{
			$tplDir=FLEX_APP_DIR_ROOT.FLEX_APP_DIR."/".$dir."/";
		}
		$styles[]=array("class"=>self::$class,"ext"=>false,"file"=>$file,"found"=>$found,"name"=>"ender","tplDir"=>$tplDir);//ext - external script
		//сохраняем список для css.php
		$md=md5(serialize($styles));
		$url=cache::check(self::$class,$md,self::$config["cacheEvalCss"],"css");
		if(!$url)
		{
			$cont=self::_buildStyleSheet($styles);
			if(!$cont)die("CSS render error.");
			$url=cache::set(self::$class,$md,self::$config["cacheEvalCss"],$cont,false,"css");
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
			"spotsNolayout"		=>	array(1,4,22)
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
		$ent="".lib::rnd(1000000000, 2147483647);
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
		self::_clientStyles();?>
	<script type="text/javascript">
		var flex_engine_render_pointer<?=$ent?> = {cfg: {
			appName: (""<?=(" + \"".self::$c->config("","siteName")."\"")?>),
			appRoot: (""<?=(" + \"".FLEX_APP_DIR_ROOT."\"")?>),
			elNameAction: (""<?=(" + \"".self::$c->config("","elNameAction")."\"")?>),
			elNameForm: (""<?=(" + \"".self::$c->config("","elNameForm")."\"")?>),
			entity: (""<?=(" + \"".$ent."\"")?>),
			lang: (""<?=(" + \"".self::$c->lang()."\"")?>),
			pageAlias: (""<?=(" + \"".content::item("alias")."\"")?>),
			pageTemplate: (""<?=(" + \"".self::$c->template()."\"")?>),
			pageTitle: (""<?=(" + \"".content::title(false,false)."\"")?>),
			plugins: []
		}};
	</script><?self::_client();?>
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
		$ms=self::config("elMainStyle");
		$hs=self::config("elHeaderStyle");
		$colLW=self::config("elColHLWidth");
		$colRW=self::config("elColHRWidth");
		$clear=false;
		if(!$hs7=self::_hasSpot(7))$colLW="0";
		else $clear="left";
		if(!$hs9=self::_hasSpot(9))$colRW="0";
		else $clear=($clear?"both":"right");
?>
<form id="<?=$nameForm?>" action="" method="post" enctype="multipart/form-data" onsubmit="<?=(self::$class.".")?>onSubmit()">
	<input type="hidden" id="<?=$nameAction?>" name="<?=$nameAction?>" value="" />
	<script type="text/javascript"><?=(self::$class.".")?>_init();</script>
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
		$cs=self::config("elCenterStyle");
		if(!self::_hasSpotMods(array(16,17,18,19,20)))$fh="0";
		else $fh=self::config("elFooterHeight");
		$colLW=self::config("elColCLWidth");
		$colRW=self::config("elColCRWidth");
		$clear=false;
		if(!$hs12=self::_hasSpot(12))$colLW="0";
		else $clear="left";
		if(!$hs14=self::_hasSpot(14))$colRW="0";
		else $clear=($clear?"both":"right");
?>
		<div id="<?=self::$class?>-center" style="<?=$cs?>"><?
		self::_spot(11,true,"center-top",false,true);
		self::_spot(12,true,"center-left",array("width"=>$colLW),true);
		self::_spot(14,true,"center-right",array("width"=>$colRW),true);
		self::_spot(13,true,"center-mid",array("margin"=>"0 {$colRW} 0 {$colLW}"),true);
		echo($clear?"<div class=\"".self::$class."-clear-".$clear."\"></div>":"");
		self::_spot(15,true,"center-bottom",false,true);?>
			<div id="<?=self::$class?>-footer-margin" style="<?=("height:".$fh.";")?>"></div>
		</div>
	</div>
<?
	}

	private static function _page3Footer()
	{
		$fs=self::config("elFooterStyle");
		if(!self::_hasSpotMods(array(16,17,18,19,20)))$fh="0";
		else $fh=self::config("elFooterHeight");
		$fd=self::config("elFooterDynamic");
		$colLW=self::config("elColFLWidth");
		$colRW=self::config("elColFRWidth");
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

	private static function _sessionRead()
	{
		if(isset($_SESSION[self::$class."-data"]))
			self::$session=unserialize($_SESSION[self::$class."-data"]);
	}

	/**
	* Чтение данных из сессии
	*
	*/
	private static function _sessionWrite()
	{
		if(count(self::$session))
			$_SESSION[self::$class."-data"]=serialize(self::$session);
	}

	private static function _spot($sid,$tag=false,$class=false,$styles=false,$ignoreEmpty=false)
	{
		if($tag && (!is_string($tag)))$tag="div";
		$mods=self::modsInSpot($sid);
		$empty=(count($mods)===0);
		$methodDef=self::$c->modHookName("render");
		$methodDefDeep=self::$c->modHookName("renderDeep");
		if($sid==self::$config["contentSpot"])
		{
			content::title(true,true);
			self::_spot(2,true,false,false,true);
			@call_user_func(array(__NAMESPACE__."\\"."content",$methodDef));
		}
		else
		{
			if($tag && (!$empty || !$ignoreEmpty))
			{
				if(is_array($styles))$styles=self::_spotStyles($sid,$styles);
				else $styles=self::_spotStyles($sid);
?>
		<<?=$tag?> id="<?=self::$class?>-spot-<?=$sid?>" <?=(is_string($class)?" class=\"{$class}\"":"")?><?=($styles?$styles:"")?>>
<?
			}
		}
		foreach($mods as $mod)
		{
			$args="";
			$method="";
			if($mod["args"]!=="")$args=explode(",",$mod["args"]);
			if(@class_exists(__NAMESPACE__."\\".$mod["class"]))
			{
				if(@method_exists(__NAMESPACE__."\\".$mod["class"],$methodDefDeep))
				{
					$method=$methodDefDeep;
					if(!is_array($args))$args=array($mod["class"],$mod["method"]);
					else
					{
						array_unshift($args,$mod["method"]);
						array_unshift($args,$mod["class"]);
					}
				}
				else
				{
					$method=$mod["method"];
					if(!$method)$method=$methodDef;
					if(!@method_exists(__NAMESPACE__."\\".$mod["class"],$method))$method="";
				}
			}
			if($method)
			{
				if(is_array($args))
					@call_user_func_array(array(__NAMESPACE__."\\".$mod["class"],$method),$args);
				else
					@call_user_func(array(__NAMESPACE__."\\".$mod["class"],$method));
			}
		}
		if($sid==self::$config["contentSpot"])self::_spot(3,true,false,false,true);
		else
		{
			if($tag && (!$empty || !$ignoreEmpty))
			{
?>
		</<?=$tag?>>
<?
			}
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
		while($row=mysql_fetch_row($r))
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
		while($row=mysql_fetch_row($r))self::$spots[0+$row[0]]=$row[1];
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
		$spots=content::spots();
		if(is_array($spots) && count($spots))self::$spotsCur=$spots;
		else self::$spotsCur=self::$config["spotsDefault"];
		self::_bindingsLoad();
		self::_spotStylesLoad();

	}

	public static function _init()
	{
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
		self::_spot(self::$config["contentSpot"]);
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
			if(!is_object($class))return;
			$cl=@explode("\\",@get_class($class));
			$clname=@array_pop($cl);
		}
		if($name && (@strpos($name,"http")===0))
		{
			if(@array_key_exists($clname,self::$clScripts))
			{
				$len=@count(self::$clScripts[$clname]);
				for($cnt=0;$cnt<$len;$cnt++)
					if(self::$clScripts[$clname][$cnt]["name"]==$name)return;
				$new=$len;
			}
			else $new=0;
			self::$clScripts[$clname][$new]["name"]=$name;
			return;
		}
		if(!$name)$name=$clname;
		$new=0;
		if(@array_key_exists($clname,self::$clScripts))
		{
			$len=@count(self::$clScripts[$clname]);
			for($cnt=0;$cnt<$len;$cnt++)
			{
				if(self::$clScripts[$clname][$cnt]["name"]==$name)return;
			}
			$new=$len;
		}
		self::$clScripts[$clname][$new]["name"]=$name;
		if($core)self::$clScripts[$clname][$new]["core"]=$core;
		self::$clScripts[$clname][$new]["admin"]=(@defined("ADMIN_MODE") && $admin)?"/admin":"";
	}

	public static function addStyle($class,$name="",$core=false,$admin=false)
	{
		if(!is_string($class))
		{
			if(!is_object($class))return;
			$cl=@explode("\\",@get_class($class));
			$clname=@array_pop($cl);
		}
		if(!$name)$name=$class;
		$len=@count(self::$clStyles);
		self::$clStyles[$len]["http"]=(@strpos($name,"http")===0);
		self::$clStyles[$len]["class"]=$class;
		self::$clStyles[$len]["name"]=$name;
		if($name && (@strpos($name,"/")!==false))
		{
			self::$clStyles[$len]["admin"]="";
			self::$clStyles[$len]["core"]=false;
			self::$clStyles[$len]["tpl"]="";
			self::$clStyles[$len]["link"]=true;
		}
		else
		{
			if(@method_exists(__NAMESPACE__."\\".$class,self::$c->modHookName("template")))
				$tpl=@call_user_func_array(array(__NAMESPACE__."\\".$class,self::$c->modHookName("template")),array($class));
			else $tpl=self::$c->template();
			self::$clStyles[$len]["admin"]=(@defined("ADMIN_MODE") && $admin)?"/admin":"";
			self::$clStyles[$len]["core"]=$core;
			self::$clStyles[$len]["tpl"]=$tpl;
			self::$clStyles[$len]["link"]=false;
		}
	}

	public static function config($name,$params=false)
	{
		//var @params is set for future purposes
		if(isset(self::$config[$name]))return self::$config[$name];
		else return "";
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
		while($rec=@mysql_fetch_assoc($r))
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
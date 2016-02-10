<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
define("CORE_DOMAIN_TYPE_MAIN",0,false);
define("CORE_DOMAIN_TYPE_DEV",1,false);
define("CORE_DOMAIN_TYPE_LOC",2,false);
//
define("CORE_URI_PARSE_FIRSTSECT",0,false);
define("CORE_URI_PARSE_LASTSECT",1,false);
define("CORE_MOD_TYPE_SYS",0,false);
define("CORE_MOD_TYPE_USR",1,false);
final class core
{
	private $actions		=	array();
	private $appRoot		=	"/";
	private $config			=	array();
	private $configTime		=	0;
	private $class			=	__CLASS__;
	private $debug			=	false;
	private $domainType		=	CORE_DOMAIN_TYPE_MAIN;
	private $domainCurrent	=	"";
	private $metas			=	array();
	private $modules		=	array();
	private $modHookNames	=	array(
		"adminSuf"			=>	"Admin",
		"init"				=>	"_init",
		"initDeep"			=>	"__init",
		"exec"				=>	"_exec",
		"execDeep"			=>	"__exec",
		"homeDir"			=>	"_homeDir",
		"render"			=>	"_render",
		"renderDeep"		=>	"__render",
		"service"			=>	"__service",
		"sleep"				=>	"_sleep",
		"sleepDeep"			=>	"__sleep",
		"template"			=>	"_template"
	);
	private $path			=	array();
	private $post			=	array();
	private $serverPath		=	"";
	private $service		=	array();
	private $silentXKey		=	"";
	private $spots			=	array();
	private $version		=	array(3,1,2);

	private function __configClear()
	{
		$db=$this->config["db"];
		$this->config=array();
		$this->config["db"]=$db;
	}

	private function __configLoad($class="",$init=false)
	{
		if(!$class)$class=$this->class;
		if($class==$this->class)
		{
			//очищаем конфиг, если нужно
			if($init && isset($this->config[$this->class]) && isset($this->config[$this->class]["config-reload"]["value"]))
			{
				$t=time();
				if(($t-$this->configTime)>(0+$this->config[$this->class]["config-reload"]["value"]))
				{
					$this->configTime=$t;
					$this->config=array();
				}
			}
			//загружаем с нуля
			if(!isset($this->config[$this->class]))
			{
				$this->config[$this->class]=array(
					"config-reload"		=>	array("params"=>"","value"=>"300"),
					"domainName"		=>	array("params"=>"","value"=>"mydomain.ru"),
					"domainNameDev"		=>	array("params"=>"","value"=>"dev.mydomain.ru"),
					"domainNameLoc"		=>	array("params"=>"","value"=>"mydomain.loc"),
					"elNameAction"		=>	array("params"=>"","value"=>"action"),
					"elNameForm"		=>	array("params"=>"","value"=>"form-main"),
					"siteName"			=>	array("params"=>"","value"=>"Web-site"),
					"template"			=>	array("params"=>"","value"=>"default"),
					"uriParseType"		=>	array("params"=>"CORE_URI_PARSE_FIRSTSECT","value"=>CORE_URI_PARSE_FIRSTSECT),
				);
				//перезаписывам значения по-умолчанию значенияи из пользовательского конфига
				$cincl=FLEX_APP_DIR_DAT."/_".$this->class."/config.php";
				if(@file_exists($cincl))include $cincl;
				else {
					$cincl=FLEX_APP_DIR."/config.php";
					if(@file_exists($cincl))include $cincl;
					else die("Critical: config file was not found.");
				}
				if(!isset($global_cfg) || !count($global_cfg))die("Critical: config data was not found.");
				foreach($global_cfg as $mod=>$cfg)
				{
					if(!is_array($cfg))continue;
					if(!isset($this->config[$mod]))$this->config[$mod]=array();
					foreach($cfg as $name=>$val)
					{
						if(is_string($val))$this->config[$mod][$name]=array("params"=>"","value"=>$val);
						else
						{
							if(is_array($val))
							{
								if(isset($va["params"]) && isset($val["value"]))$this->config[$mod][$name]=$val;
							}
						}
					}
				}
			}
			if($init)
			{
				//инициализация базы данных
				$dl=$this->config[$this->class]["domainNameLoc"]["value"];
				$dd=$this->config[$this->class]["domainNameDev"]["value"];
				if($dl && (strpos($_SERVER["HTTP_HOST"],$dl)!==false))$this->domainType=CORE_DOMAIN_TYPE_LOC;
				elseif($dd && strpos($_SERVER["HTTP_HOST"],$dd)!==false)$this->domainType=CORE_DOMAIN_TYPE_DEV;
				else $this->domainType=CORE_DOMAIN_TYPE_MAIN;
				@call_user_func(array(__NAMESPACE__."\\db",$this->modHookNames["init"]));
			}
			$mid=0;
		}
		else
		{
			$mid=$this->modId($class);
			if(!$mid)return;
		}
		$r=db::q("SELECT * FROM ".db::tn("config")." WHERE `mid`=".$mid,true);
		while($row=db::fetch($r))$this->config[$class][$row["name"]]=array("params"=>$row["params"],"value"=>$row["value"]);
	}

	private function __modsExec()
	{
		foreach($this->modules as $i=>$mod)
		{
			if($mod["core"])continue;
			if(@method_exists(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["execDeep"]))
				@call_user_func_array(array(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["execDeep"]),array($mod["class"]));
			elseif(@method_exists(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["exec"]))
				@call_user_func(array(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["exec"]));
		}
	}

	private function __modsInit()
	{
		foreach($this->modules as $i=>$mod)
		{
			if($mod["core"])continue;
			if(@method_exists(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["initDeep"]))
				@call_user_func_array(array(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["initDeep"]),array($mod["class"]));
			elseif(@method_exists(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["init"]))
				@call_user_func(array(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["init"]));
		}
	}

	private function __modsLoad()
	{
		$this->modules=render::modsBound();
	}

	private function __modsSleep()
	{
		$l=count($this->modules);
		for($c=($l-1);$c>=0;$c--)
		{
			if($this->modules[$c]["core"])continue;
			if(@method_exists(__NAMESPACE__."\\".$this->modules[$c]["class"],$this->modHookNames["sleepDeep"]))
				@call_user_func_array(array(__NAMESPACE__."\\".$this->modules[$c]["class"],$this->modHookNames["sleepDeep"]),array($this->modules[$c]["class"]));
			if(@method_exists(__NAMESPACE__."\\".$this->modules[$c]["class"],$this->modHookNames["sleep"]))
				@call_user_func(array(__NAMESPACE__."\\".$this->modules[$c]["class"],$this->modHookNames["sleep"]));
		}
	}

	private function __sysExec()
	{
		foreach($this->system as $mod)
			if(@method_exists(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["exec"]))
				@call_user_func(array(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["exec"]));
	}

	private function __sysInit()
	{
		foreach($this->system as $mod)
			if(($mod["class"]!="db") && @method_exists(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["init"]))
				@call_user_func(array(__NAMESPACE__."\\".$mod["class"],$this->modHookNames["init"]));
	}

	private function __sysLoad()
	{
		$r=db::q("SELECT `id`,`core`,`srv`,`ord`,`class`,`title` FROM ".db::tn("mods")." WHERE `act`=1 AND (`core`=1 OR `srv`=1) ORDER BY `ord`",true);
		while($rec=@mysql_fetch_assoc($r))
		{
			$core=0+$rec["core"];
			$srv=0+$rec["srv"];
			if($srv && !$core)
			{
				$this->service[$rec["class"]]=array(
					"api"=>array(),
					"id"=>0+$rec["id"],
					"title"=>$rec["title"]
				);
			}
			else
			{
				$ord=0+$rec["ord"]-1;
				$this->system[$ord]=array();
				$this->system[$ord]["id"]=0+$rec["id"];
				$this->system[$ord]["core"]=true;
				$this->system[$ord]["class"]=$rec["class"];
				$this->system[$ord]["title"]=$rec["title"];
			}
		}
	}

	private function __sysService()
	{
		foreach($this->service as $class=>$v)
		{
			if(@method_exists(__NAMESPACE__."\\".$class,$this->modHookNames["initDeep"]))
				@call_user_func_array(array(__NAMESPACE__."\\".$class,$this->modHookNames["initDeep"]),array($class));
			$srv=@call_user_func_array(array(__NAMESPACE__."\\".$class,$this->modHookNames["service"]),array($class,true));
			if(is_array($srv))$this->service[$class]["api"]=$srv;
		}
	}

	private function __sysSleep()
	{
		$max=-1;
		foreach($this->system as $c=>$mod)if($c>$max)$max=$c;
		if($max==-1)return;
		for($c=$max;$c>=0;$c--)
		{
			if(!isset($this->system[$c]))continue;
			if(@method_exists(__NAMESPACE__."\\".$this->system[$c]["class"],$this->modHookNames["sleep"]))
				@call_user_func(array(__NAMESPACE__."\\".$this->system[$c]["class"],$this->modHookNames["sleep"]));
		}
	}

	private function _getData()
	{
		if(count($_POST))
		{
			$this->post=$_POST;
			$acts=explode(",",$this->post("render-".$this->config("","elNameAction")));
			foreach($acts as $key=>$value)if($value)$this->actions[]=trim($value);
		}
		media::check();
	}

	private function _pathParse()
	{
		$this->path=array();
		if(isset($_GET["path"]) || !isset($_GET["pid"]))
		{
			$this->path["pid"]=0;
			if(!isset($_GET["path"]))$this->path["sections"]="";
			else $this->path["sections"]=trim($_GET["path"],"/");
			$this->path["simbolic"]=true;
		}
		else
		{
			$this->path["pid"]=0+$_GET["pid"];
			$this->path["sections"]="";
			$this->path["simbolic"]=false;
		}
	}

	private function _silentXStatusSend()
	{
		$gkey=FLEX_APP_OUT_STR."render-xkey";
		$skey=FLEX_APP_OUT_STR."render-xrequest-";
		$ckey=FLEX_APP_OUT_STR."render-xcb";
		header("Content-Type: application/javascript");
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
		if(!isset($_SESSION[$seskey]["data"]))$_SESSION[$seskey]["data"]="";
		if(!isset($_GET[$ckey]))
		{
			echo"console.log(\"Ошибка отправки статуса операции: callback-функция \$_GET[".$ckey."] не определена. Статус: \");";
			echo"console.log(\"".$_SESSION[$seskey]["data"]."\");";
			return;
		}
		echo"".$_GET[$ckey]."(\"".$_SESSION[$seskey]["data"]."\",\"{$key}\");";
		unset($_SESSION[$seskey]["data"]);
		unset($_SESSION[$seskey]);
	}

	public function _exec()
	{
		if((render::type()==RENDER_TYPE_REDIR) || (render::type()==RENDER_TYPE_STATUS))return;
		$this->__sysExec();
		//загрузка и инициализация модулей страницы
		$this->__modsLoad();
		$this->__modsInit();
		$this->__modsExec();
	}

	public function _init()
	{
		if(strpos($this->class,"\\")!==false)
		{
			$cl=explode("\\",$this->class);
			$this->class=$cl[count($cl)-1];
		}
		//загрузка конфига ядра
		$this->__configLoad("",true);
		//POST и actions
		$this->_getData();
		//тип рендера
		$rtype=render::type();
		switch($rtype)
		{
			case RENDER_TYPE_REDIR:
				header("Location: http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
				return;
			case RENDER_TYPE_STATUS:
				$this->_silentXStatusSend();
				return;
			default:
				//переменные окружения
				$this->serverPath=getcwd();
				$this->domainCurrent=$_SERVER["HTTP_HOST"];
				$this->appRoot=FLEX_APP_DIR_ROOT;
				//парсинг идентификатора страницы
				$this->_pathParse();
				//загрузка и инициализация модулей ядра
				$this->__sysLoad();
				$this->__sysInit();
				$this->__sysService();
		}
	}

	public function _render()
	{
		render::_();
	}

	public function _sleep()
	{
		if((render::type()==RENDER_TYPE_REDIR) || (render::type()==RENDER_TYPE_STATUS))return;
		$this->actions=array();
		$this->post=array();
		$this->__modsSleep();
		$this->__sysSleep();
		$this->modules=array();
		$this->system=array();
		$this->metas=array();
	}

	public function action($actName="")
	{
		if(!$actName)return count($this->actions);
		return in_array($actName,$this->actions);
	}

	public function addScript($class,$name="",$core=false)
	{
		render::addScript($class,$name,$core);
	}

	public function addStyle($class,$name="",$core=false)
	{
		render::addStyle($class,$name,$core);
	}

	public function appRoot()
	{
		return $this->appRoot;
	}

	public function config($class,$name=false,$params=false,$load=true)
	{
		if(!$class)$class=$this->class;
		if($class=="db")$load=false;
		if($name===false)
		{
			if(!isset($this->config[$class]))
			{
				if(!$load)return array();
				$this->__configLoad($class);
				return $this->config($class,false,$params,false);
			}
			else
			{
				if($params)return array_merge(array(),$this->config[$class]);
				else
				{
					$cfg=array();
					foreach($this->config[$class] as $name=>$val)
					{
						if(isset($val["value"]))$cfg[$name]=$val["value"];
						else $cfg[$name]="";
					}
					return $cfg;
				}
			}
		}
		$par="";
		$val="";
		if(isset($this->config[$class][$name]))
		{
			$par=$this->config[$class][$name]["params"];
			$val=$this->config[$class][$name]["value"];
		}
		else
		{
			if($class!=$this->class && $load)
			{
				$this->__configLoad($class);
				return $this->config($class,$name,$params,false);
			}
		}
		if($params)
			return array($val,$par);
		else
			return $val;
	}

	public function content($prop)
	{
		return content::item($prop);
	}

	public function debug()
	{
		return $this->debug;
	}

	public function defaultSpots()
	{
		return $this->config("content","spotsDefault");
	}

	public function domainCurrent()
	{
		return $this->domainCurrent;
	}

	public function domainType()
	{
		return $this->domainType;
	}

	private function hasPostData()
	{
		if(count($this->post))return true;
		return false;
	}

	public function lang() {
		return lang::cur();
	}

	public function modHookName($hookName)
	{
		if(isset($this->modHookNames[$hookName]))
			return $this->modHookNames[$hookName];
		else
			return"unknownHook";
	}

	public function modId($class,$forceDb=false)
	{
		if($class==$this->class)return 0;
		$id=0;
		if(!$forceDb)
		foreach($this->modules as $mod)
			if ($mod["class"]==$class)
			{
				$id=$mod["id"];
				break;
			}
		if(!$id || $forceDb)
		{
			$r=db::q("SELECT `id` FROM ".db::tn("mods")." WHERE `class`='".$class."'",true);
			$id=@mysql_fetch_row($r);
			if(!$id)$id=0;
			else $id=0+$id[0];
		}
		return $id;
	}

	public function modsListAll($fields=array(),$filters=array())
	{
		if(is_string($fields))$fields=array($fields);
		$len=count($fields);
		if($len)
		{
			$known=db::tFields("mods");
			for($c=($len-1);$c>0;$c--)
			{
				$name=trim($fields[$c],"`");
				if(!in_array($name,$known))unset($fields[$c]);
			}
		}
		if(!count($fields))$fields=array("id","class");
		if($filters)$filtersSQL=db::filtersMake($filters,true);
		else $filtersSQL="";
		$r=db::q("SELECT `".implode("`,`",$fields)."` FROM ".db::tn("mods").($filtersSQL?(" WHERE".$filtersSQL):""),true);
		while($rec=@mysql_fetch_assoc($r))$recs[]=$rec;
		return $recs;
	}

	public function path($par="")
	{
		if(!$par)return $this->path;
		else
		{
			if(isset($this->path[$par]))return $this->path[$par];
			return false;
		}
	}

	public function post($key)
	{
		if(isset($this->post[$key]))
			return $this->post[$key];
		else
			return "";
	}

	public function posted($key)
	{
		return isset($this->post[$key]);
	}

	public function services($mod,$fname)
	{
		$srvs=array();
		foreach($this->service as $smod=>$sdata)
		{
			$sdata=$sdata["api"];
			foreach($sdata as $tmod=>$fnames)
			{
				if($tmod==$mod)
				{
					if(is_array($fname))
					foreach($fnames as $name=>$value)
					{
						if($name==$fname)$srvs[]=array($smod,$value);
					}
				}
			}
		}
		return $srvs;
	}

	public function silent()
	{
		return render::silent();
	}

	public function silentXResponseSet($resp)
	{
		$resp=str_replace("\"","\\\"",$resp);
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
		$_SESSION["render-xrequest-".$key]["data"]=$resp;
		echo"<script type=\"text/javascript\">console.log(\"Action [".$key."] was processed.\");</script>";
		return true;
	}

	public function template()
	{
		return $this->config[$this->class]["template"]["value"];
	}

	public function version($str=false)
	{
		if($str)return implode(".",$this->version);
		else return $this->version;
	}
}
?>
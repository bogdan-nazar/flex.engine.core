<?php
/* Native FlexEngine Extension Module */
namespace FlexEngine;
class module
{
	private static $__c				=	null;
	private static $__ic			=	false;
	private static $__is			=	array();
	private static $__inited		=	false;
	private static $__isadmin		=	false;
	private static $__silent		=	false;

	final private static function _iClass($class)
	{
		if(strpos($class,"\\")!==false)
		{
			$cl=explode("\\",$class);
			$class=$cl[count($cl)-1];
		}
		return $class;
	}

	final private static function _iGet($class,$set=true,$clChk=true)
	{
		if($clChk)$class=self::_iClass($class);
		if(!$class)return($set?self::$__ic=false:false);
		foreach(self::$__is as $i)
        {
			if($i->__instance===$class)return($set?self::$__ic=$i:$i);
        }
		return($set?self::$__ic=false:false);
	}

	final private static function _iSet($class,$clChk=true)
	{
		if($clChk)$class=self::_iClass($class);
		if(self::_iGet($class,false,false))return false;
		$i					=	new \StdClass();
		$i->__config		=	array("data"=>array(),"key"=>"","time"=>0);
		$i->__instance		=	$class;
		$i->__runstage		=	-1;//0-init,1-exec,2-sleep
		$i->__version		=	array(1,0,0);
		$i->__session		=	array();
		$i->__sessionAdmin	=	array();
		self::$__is[]=self::$__ic=$i;
		return $i;
	}

	final private static function _isAdmn()
	{
		if(self::$__ic && @defined("ADMIN_MODE") && (self::$__ic->__instance===ADMIN_MODE))return false;
		return self::$__isadmin;
	}

	final private static function _session($par="",$do="get",$data)
	{
		if(!self::$__ic)return;
		if($par==="")
		{
			if(!self::_isAdmn())return self::$__ic->__session;
			else return self::$__ic->__sessionAdmin;
		}
		if(self::_isAdmn())
		{
			if($par===false)
			{
				self::$__ic->__sessionAdmin=array();
				return;
			}
			if($do=="get")
			{
				if(isset(self::$__ic->__sessionAdmin[$par]))return self::$__ic->__sessionAdmin[$par];
				else return"";
			}
			else
			{
				if(!isset($data))return;
				self::$__ic->__sessionAdmin[$par]=$data;
			}
		}
		else
		{
			if($par===false)
			{
				self::$__ic->__session=array();
				return;
			}
			if($do=="get")
			{
				if(isset(self::$__ic->__session[$par]))return self::$__session[$par];
				else return"";
			}
			else
			{
				if(!isset($data))return;
				self::$__ic->__session[$par]=$data;
			}
		}
	}

	final private static function _sessionRead()
	{
		if(!self::$__ic)return;
		$sesName=FLEX_APP_NAME."-".self::$__ic->__instance."-data";
		if(self::_isAdmn())
		{
			$sesName=$sesName."-admin";
			if(isset($_SESSION[$sesName]))
				self::$__ic->__sessionAdmin=@unserialize(base64_decode($_SESSION[$sesName]));
		}
		else
		{
			if(isset($_SESSION[$sesName]))
				self::$__ic->__session=@unserialize($_SESSION[$sesName]);
		}
	}

	final private static function _sessionWrite()
	{
		if(!self::$__ic)return;
		if(self::_isAdmn())
		{
			if(@count(self::$__ic->__sessionAdmin))
				$_SESSION[FLEX_APP_NAME."-".self::$__ic->__instance."-data-admin"]=@base64_encode(@serialize(self::$__ic->__sessionAdmin));
		}
		else
		{
			if(@count(self::$__ic->__session))
				$_SESSION[FLEX_APP_NAME."-".self::$__ic->__instance."-data"]=@serialize(self::$__ic->__session);
		}
	}

	final protected static function action($actName)
	{
		return self::$__c->action($actName);
	}

	final protected static function clientConfigAdd($data=array())
	{
		$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
		return self::$__c->addConfig($class,$data);
	}

	final protected static function dt($dt,$full=false,$sect="-")
	{
		return lib::dt($dt,$full,$sect);
	}

	final protected static function dtR($dt,$full=false,$sect=".")
	{
		return lib::dtR($dt,$full,$sect);
	}

	final protected static function dtRValid($dt)
	{
		return lib::validDtRus($dt);
	}

	final protected static function mediaFetch($id,$childs=true)
	{
		return media::fetch($id,$childs);
	}

	final protected static function mediaFetchArray()
	{
		$args=func_get_args();
		if(!count($args) || (is_int($args[0]) || (is_string($args[0]) && (0+$args[0]>0))))
		{
			$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
			$entity=isset($args[0])?$args[0]:false;
			$filters=isset($args[1])?$args[1]:array();
			$range=isset($args[2])?$args[2]:false;
			$childs=isset($args[3])?$args[3]:false;
		}
		else
		{
			$class=isset($args[0])?$args[0]:"";
			$entity=isset($args[1])?$args[1]:false;
			$filters=isset($args[2])?$args[2]:array();
			$range=isset($args[3])?$args[3]:false;
			$childs=isset($args[4])?$args[4]:false;
		}
		return media::fetchArray($class,$entity,$filters,$range,$childs);
	}

	final protected static function mediaLastMsg()
	{
		return media::lastMsg();
	}

	final protected static function mquotes_gpc()
	{
		return lib::mquotes_gpc();
	}

	final protected static function mquotes_runtime()
	{
		return lib::mquotes_runtime();
	}

	final protected static function msgAdd($msg,$msgType=MSGR_TYPE_INF,$msgShow=MSGR_SHOW_DIALOG)
	{
		msgr::add($msg,$msgType,$msgShow);
	}

	final protected static function pageIndex()
	{
		return content::pageIndex();
	}

	final protected static function post($var)
	{
		return self::$__c->post($var);
	}

	final protected static function q($q,$die=false,$debug=array("msg"=>"Ошибка выполнения запроса к БД."))
	{
		return db::q($q,$die,$debug);
	}

	final protected static function qe($s)
	{
		return db::esc($s);
	}

	final protected static function qf($r,$a="a") {
		return db::fetch($r,$a);
	}

	final protected static function silent()
	{
		return self::$__silent;
	}

	final protected static function tb($name)
	{
		return db::tnm($name);
	}

	final protected static function tbMeta()
	{
		$args=func_get_args();
		$c=count($args);
		if($c<3)
		{
			if(($c==1) || ($c==2 && is_bool($args[1])))
			{
				$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
				$tname=$args[0];
				if($c)$force=$args[1];
				else $force=false;
			}
			else
			{
				if(!$c)$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
				else $class=isset($args[0])?$args[0]:"";
				$tname=isset($args[1])?$args[1]:"";
				$force=false;
			}
		}
		else
		{
			$class=$args[0];
			$tname=$args[1];
			$force=$args[2];
		}
		return db::tMeta($class,$tname,$force);
	}

	final protected static function template()
	{
		return render::template();
	}

	final protected static function tplGet($tplSection="",$tplFile="",$useTemplatesSet="") {
		$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
		return tpl::get($class,$tplSection,$tplFile,$useTemplatesSet);
	}

	final public static function __attach()
	{
		if(!self::$__c)
		{
			self::$__c=_a::core();
			self::$__silent=self::$__c->silent();
			self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
			self::$__inited=true;
		}
	}

	final public static function __callStatic($name,$arguments=array())
	{
		$class=@get_called_class();
		if(!self::_iGet($class))return;
		$done=false;
		if(@method_exists(__NAMESPACE__."\\".__CLASS__,"_".$name))
		{
			$method=new \ReflectionMethod(__NAMESPACE__."\\".__CLASS__,"_".$name);
			if($method->isPublic())
			{
				@call_user_func_array(array(self,"_".$name),$arguments);
				$done=true;
			}
		}
		if(!$done)
		{
			switch($name)
			{
				case "_class":
					return self::$__ic->__instance;
				case "access":
					return @call_user_func_array(array(__NAMESPACE__."\\"."auth","access"),$arguments);
				case "appRoot":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
				case "cacheSet":
					return @call_user_func_array(array(__NAMESPACE__."\\"."cache","set"),$arguments);
				case "config":
					$c=count($arguments);
					if($c>2)return false;
					if(!$c)
					{
						if(isset(self::$__ic->__config["data"]))return self::$__ic->__config["data"];
						else return false;
					}
					if($c==1)
					{
						if(isset(self::$__ic->__config["data"][$arguments[0]]))return self::$__ic->__config["data"][$arguments[0]];
						else return false;
					}
					return @call_user_func_array(array(self::$__c,"config"),$arguments);
				case "lastErr":
					return @call_user_func_array(array(__NAMESPACE__."\\"."msgr","errorGet"),$arguments);
				case "lastMsg":
					return @call_user_func_array(array(__NAMESPACE__."\\"."msgr",$name),$arguments);
				case "libJsonMake":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib","jsonMake"),$arguments);
				case "libJsonPrepare":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib","jsonPrepare"),$arguments);
				case "libLastMsg":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib","lastMsg"),$arguments);
				case "libValidEmail":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib","validEmail"),$arguments);
				case "libValidStr":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib","validStr"),$arguments);
				case "mailSend":
					return @call_user_func_array(array(__NAMESPACE__."\\"."msgr",$name),$arguments);
				case "modHookName":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
				case "modId":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
				case "path":
					return @call_user_func_array(array(self::$__c,"path"),$arguments);
				case "page":
					return @call_user_func_array(array(__NAMESPACE__."\\"."content","item"),$arguments);
				case "pageByModMethod":
					array_unshift($arguments,self::$__ic->__instance);
					return @call_user_func_array(array(__NAMESPACE__."\\"."content",$name),$arguments);
				case "posted":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
				case "resourceScriptAdd":
					if(self::$__silent)return;
					array_unshift($arguments,self::$__ic->__instance);
					if(self::_isAdmn())
					{
						$l=count($arguments);
						if($l==2)array_push($arguments,"",false,true);
						if($l==3)array_push($arguments,false,true);
						if($l==4)array_push($arguments,true);
					}
					return @call_user_func_array(array(__NAMESPACE__."\\"."render","addScript"),$arguments);
				case "resourceStyleAdd":
					if(self::$__silent)return;
					array_unshift($arguments,self::$__ic->__instance);
					if(self::_isAdmn())
					{
						$l=count($arguments);
						if($l==2)array_push($arguments,"",false,true);
						if($l==3)array_push($arguments,false,true);
						if($l==4)array_push($arguments,true);
					}
					return @call_user_func_array(array(__NAMESPACE__."\\"."render","addStyle"),$arguments);
				case "sessionEmpty":
					self::_session(false);
					break;
				case "sessionGet":
					if(!count($arguments)){
						$s=self::_session();
						return $s;
					}
					return self::_session($arguments[0]);
				case "sessionSet":
					if(count($arguments)!=2)return"";
					return self::_session($arguments[0],"set",$arguments[1]);
				case "silentXResponseSet":
					return @call_user_func_array(array(self::$__c,"silentXResponseSet"),$arguments);
				case "user":
					return @call_user_func_array(array(__NAMESPACE__."\\"."auth","user"),$arguments);
				default:
					throw new \Exception("Fatal error: unknown method requested by [".self::$__ic->__instance."::".$name."].");
			}
		}
	}

	final public static function __exec($instance,$srv=false)
	{
        //второй аргумент $srv пока что не используется
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>1)return;
		self::$__ic->__runstage++;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on2exec"))$class::_on2exec();
	}

	final public static function __init($instance,$srv=false)
	{
        //для сервисных модулей __init вызывается 2 раза:
        // 1-й: только для создания экземпляра (из метода ::__service)
        // 2-й: для собственно инициализации (из метода core->__modsStage("__init"))
        if($srv)
        {
            if(!self::_iGet($instance))
            {
                self::_iSet($instance);
                return;
            }
        }
        //если модуль не сервисный, то создаем экземпляр
        //и сразу его инициализируем
        else
        {
            if(!self::_iSet($instance))return;
        }
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		if(isset(static::$configDefault))self::$__ic->__config["data"]=static::$configDefault;
		self::_sessionRead();
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on1init"))$class::_on1init();
		if(@method_exists($class,"_hookLangData"))lang::extend($class::_hookLangData(lang::index()));
	}

	final public static function __install($instance)
	{
		self::$__c=_a::core();
		self::$__silent=self::$__c->silent();
		self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
		if(!self::_isAdmn())
		{
			msgr::add(_t("Can't install from non-admin environment."));
			return false;
		}
		if(!self::_iSet($instance))return;
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		self::$__ic->__instance=$__instance;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on_install"))
		{
			$res=@call_user_func(array($class,"_on_install"));
			if(!is_bool($res))$res=true;
			return $res;
		}
		return true;
	}

	final public static function __uninstall($instance)
	{
		self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
		if(!self::_isAdmn())
		{
			msgr::add(_t("Can't uninstall from non-admin environment."));
			return false;
		}
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		self::$__ic->__instance=$__instance;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on_uninstall"))
		{
			$res=@call_user_func(array($class,"_on_uninstall"));
			if(!is_bool($res))$res=true;
			return $res;
		}
		return true;
	}

	/**
	* Вызов функции рендеринга дочернего класса
	*
	* @param string $instance - имя класса, например FlexEngine\mymod
	* @param integer $sid - номер спота
	* @param string $method - функция рендеринга, по-умолчанию - __render
	*/
	final public static function __render($instance,$sid,$method="")
	{
		if(!self::_iGet($instance))return;
		//получаем все аргументы, и удаляем первый $instance
		//так как это системный аргумент
		$args=func_get_args();
		array_splice($args,0,1);
		//заново формируем $class, так как $instance может
		//содержать класс без указания namespace
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		//если существует пользовательский метод, то это означает
		//что он напрямую прикреплен к споту, поэтому
		//дополнительной удаляем аргументы $sid и $method
		if($method && @method_exists($class,$method))
		{
			array_splice($args,0,2);
			@call_user_func_array(array($class,$method),$args);
		}
		//в противном случае передаем $sid и $method в общую функцию рендеринга
		//$method при этом будет указывать на шаблон $tpl ($method === $tpl)
		elseif(@method_exists($class,"_on3render"))
		{
			//вырезаем системный префикс у названия шаблона
			if(isset($args[1]))$args[1]=str_replace("tpl:","",$args[1]);
			@call_user_func_array(array($class,"_on3render"),$args);
		}
	}

	final public static function __service($instance)
	{
		if(!self::_iGet($instance))return;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on0service"))return $class::_on0service();
		else return false;
	}

	final public static function __sleep($instance)
	{
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>2)return;
		self::$__ic->__runstage++;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on4sleep"))$class::_on4sleep();
		self::_sessionWrite();
	}
}
?>

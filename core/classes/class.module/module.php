<?
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
		if(@strpos($class,"\\")!==false)
		{
			$cl=@explode("\\",$class);
			$class=$cl[@count($cl)-1];
		}
		return $class;
	}

	final private static function _iGet($class,$set=true,$clChk=true)
	{
		if($clChk)$class=self::_iClass($class);
		if(!$class)return($set?self::$__ic=false:false);
		foreach(self::$__is as $i)
			if($i->__instance===$class)return($set?self::$__ic=$i:$i);
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
		if(self::_isAdmn())
		{
			if(isset($_SESSION[self::$__ic->__instance."-data-admin"]))
				self::$__ic->__sessionAdmin=@unserialize(base64_decode($_SESSION[self::$__ic->__instance."-data-admin"]));
		}
		else
		{
			if(isset($_SESSION[self::$__ic->__instance."-data"]))
				self::$__ic->__session=@unserialize($_SESSION[self::$__ic->__instance."-data"]);
		}
	}

	final private static function _sessionWrite()
	{
		if(!self::$__ic)return;
		if(self::_isAdmn())
		{
			if(@count(self::$__ic->__sessionAdmin))
				$_SESSION[self::$__ic->__instance."-data-admin"]=@base64_encode(@serialize(self::$__ic->__sessionAdmin));
		}
		else
		{
			if(@count(self::$__ic->__session))
				$_SESSION[self::$__ic->__instance."-data"]=@serialize(self::$__ic->__session);
		}
	}

				case "q":
					return @call_user_func_array(array(__NAMESPACE__."\\"."db",$name),$arguments);
				case "qe":
					return @call_user_func_array(array(__NAMESPACE__."\\"."db","esc"),$arguments);
				case "qf":
					return @call_user_func_array(array(__NAMESPACE__."\\"."db","fetch"),$arguments);
	final protected static function q() {
	}

	final protected static function qe() {
	}

	final protected static function qf($r,$a="a") {
		return db::fetch($r,$a="a");
	}

	final protected static function tplGet($tplSection="",$tplFile="",$useTemplatesSet="") {
		$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
		return tpl::get($class,$tplSection="",$tplFile="",$useTemplatesSet="");
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
				case "__service":
					if(isset(self::$_srv) && @count($arguments) && isset(self::$_srv[$arguments[0]]))return self::$_srv[$arguments[0]];
					else return false;
				case "_class":
					return self::$__ic->__instance;
				case "action":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
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
				case "dtR":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib",$name),$arguments);
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
				case "mediaFetch":
					array_unshift($arguments,self::$__ic->__instance);
					return @call_user_func_array(array(__NAMESPACE__."\\"."media","fetch"),$arguments);
				case "mediaFetchArray":
					array_unshift($arguments,self::$__ic->__instance);
					return @call_user_func_array(array(__NAMESPACE__."\\"."media","fetchArray"),$arguments);
				case "modHookName":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
				case "modId":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
				case "mquotes_gpc":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib",$name),$arguments);
				case "mquotes_runtime":
					return @call_user_func_array(array(__NAMESPACE__."\\"."lib",$name),$arguments);
				case "msgAdd":
					return @call_user_func_array(array(__NAMESPACE__."\\"."msgr","add"),$arguments);
				case "path":
					return @call_user_func_array(array(self::$__c,"path"),$arguments);
				case "page":
					return @call_user_func_array(array(__NAMESPACE__."\\"."content","item"),$arguments);
				case "pageByModMethod":
					array_unshift($arguments,self::$__ic->__instance);
					return @call_user_func_array(array(__NAMESPACE__."\\"."content",$name),$arguments);
				case "post":
					return @call_user_func_array(array(self::$__c,$name),$arguments);
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
				case "silent":
					return self::$__silent;
				case "silentXResponseSet":
					return @call_user_func_array(array(self::$__c,"silentXResponseSet"),$arguments);
				case "tb":
					return @call_user_func_array(array(__NAMESPACE__."\\"."db","tnm"),$arguments);
				case "user":
					return @call_user_func_array(array(__NAMESPACE__."\\"."auth","user"),$arguments);
				default:
					throw new \Exception("Fatal error: unknown method requested by [".self::$__ic->__instance."::".$name."].");
			}
		}
	}

	final public static function __exec($instance)
	{
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>1)return;
		self::$__ic->__runstage++;
		if(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,"_on2exec"))
			@call_user_func(array(__NAMESPACE__."\\".self::$__ic->__instance,"_on2exec"));
	}

	final public static function __init($instance,$srv=false)
	{
		if(!self::$__inited)
		{
			self::$__c=_a::core();
			self::$__silent=self::$__c->silent();
			self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
			self::$__inited=true;
		}
		if(!self::_iSet($instance) || $srv)return;
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		if(isset(static::$configDefault))self::$__ic->__config["data"]=static::$configDefault;
		self::_sessionRead();
		if(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,"_on1init"))
			@call_user_func_array(array(__NAMESPACE__."\\".self::$__ic->__instance,"_on1init"),array(self::_isAdmn()));
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
		if(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,"_on_install"))
		{
			$res=@call_user_func(array(__NAMESPACE__."\\".self::$__ic->__instance,"_on_install"));
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
		if(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,"_on_uninstall"))
		{
			$res=@call_user_func(array(__NAMESPACE__."\\".self::$__ic->__instance,"_on_uninstall"));
			if(!is_bool($res))$res=true;
			return $res;
		}
		return true;
	}

	final public static function __render($instance,$section="")
	{
		if(!self::_iGet($instance))return;
		$args=func_get_args();
		if(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,$section))
		{
			array_splice($args,0,2);
			@call_user_func_array(array(__NAMESPACE__."\\".self::$__ic->__instance,$section),$args);
		}
		elseif(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,"_on3render"))
		{
			array_splice($args,0,1);
			@call_user_func_array(array(__NAMESPACE__."\\".self::$__ic->__instance,"_on3render"),$args);
		}
	}

	final public static function __service($instance)
	{
		if(!self::_iGet($instance))return;
		$i=__NAMESPACE__."\\".self::$__ic->__instance;
		if(isset($i::$_srv) && count($i::$_srv))return $i::$_srv;
		else return false;
	}

	final public static function __sleep($instance)
	{
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>2)return;
		self::$__ic->__runstage++;
		if(@method_exists(__NAMESPACE__."\\".self::$__ic->__instance,"_on4sleep"))
			@call_user_func(array(__NAMESPACE__."\\".self::$__ic->__instance,"_on4sleep"));
		self::_sessionWrite();
	}


}

?>

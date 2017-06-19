<?php
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
define("AUTH_EXTENDIBLE_LOGOUT","logout");
define("AUTH_EXTENDIBLE_PROFILE","profile");
define("AUTH_EXTENDIBLE_REGISTER","register");
define("AUTH_EXTENDIBLE_UNREGISTER","unregister");
define("AUTH_EXTENDIBLE_USER","user");
define("AUTH_USER_STATUS_NOTCONFIRMED",0);
define("AUTH_USER_STATUS_ACTIVE",1);
define("AUTH_USER_STATUS_BANNED",2);
define("AUTH_USER_STATUS_SUSPENDED",3);
final class auth
{
	private static $_runStep	=	0;
	private static $action		=	"";
	private static $c			=	NULL;
	private static $class		=	__CLASS__;
	private static $config		=	array(
		"external-autoreg"		=>	true,
		"session-timeout"		=>	360
	);
	private static $fields		=	array(
		"login"					=>	array(
			"name"				=>	"",
			"pass"				=>	""
		),
		"reg"					=>	array(
			"name"				=>	"",
			"pass"				=>	"",
			"pass2"				=>	"",
			"email"				=>	"",
			"display"			=>	"",
		)
	);
	private static $session		=	array();
	private static $user		=	array();

	private static function _actionSilentRegLoginCheck()
	{
		if(!isset(self::$session["reg-login-checktime"]))self::$session["reg-login-checktime"]=0;
		$tm=time();
		if(($tm-self::$session["reg-login-checktime"])<2)
		{
			echo"{login:false,email:false,lmsg:false,emsg:false}";
			return;
		}
		self::$session["reg-login-checktime"]=$tm;
		$login=self::$c->post(self::$class."-reg-name");
		$email=self::$c->post(self::$class."-reg-email");
		if(lib::mquotes_gpc())
		{
			$login=@stripslashes($login);
			$email=@stripslashes($email);
		}
		$lmsg="";
		$lres="false";
		$emsg="";
		$eres="false";
		if(!lib::validStr("user",$login,4,32,true,"Логин",false))$lmsg=lib::getLastMsg();
		else
		{
			$r=db::q("SELECT `id` FROM ".db::tnm(self::$class)." WHERE `user`='".db::esc($login)."'",false);
			if($r && !db::rows($r))$lres="true";
		}
		if(!lib::validEmail($email,false))$emsg=lib::getLastMsg();
		else
		{
			$r=db::q("SELECT `id` FROM ".db::tnm(self::$class)." WHERE `email`='".db::esc($email)."'", false);
			if($r && !db::rows($r))$eres="true";
		}
		@header("Content-Type: application/json");
		return"{login:".$lres.",lmsg:".$lmsg.",email:".$eres.",emsg:".$emsg."}";
	}

	private static function _actionExit()
	{
		if(self::$user["id"])
		{
			if($log)self::$user["dlog"]=$dt;
			self::$user["dlast"]=$dt;
			self::$user["cookie"]=$cookie;
			db::q("UPDATE ".db::tnm(self::$class)." SET ".($log?("`ses`='".$cookie."'"):"").($u?("`dlog`='".$dtS."' AND "):"")."`dlast`='".$dtS."' WHERE `id`=".self::$user["id"],false);
		}
		self::_userReset();
		self::_cookie("");
	}

	private static function _actionLogin()
	{
		$name=self::$c->post(self::$class."-login-name");
		$pass=self::$c->post(self::$class."-login-pass");
		if(!$name || !$pass)
		{
			msgr::add(_t("Заполните все поля")."!",MSGR_TYPE_ERR);
			return false;
		}
		return self::_check($name,$pass);
	}

	private static function _actionRegister()
	{
		if(self::$user["id"])
		{
			msgr::add(_t("Для регистрации нового пользователя вы должны сначала выполнить выход из системы."),MSGR_TYPE_ERR);
			return false;
		}

	}

	private static function _actionRemind()
	{
	}

	private static function _check($u="",$p="")
	{
		//проверяем сессию
		$dt=time();
		$dtS=date("Y-m-d H:i:s",$dt);
		if(self::$user["id"])
		{
			if($dt-(0+self::$config["session-timeout"])>self::$user["dlast"])
			{
				self::$user["id"]=0;
				db::q("UPDATE ".db::tnm(self::$class)." SET `ses`='' WHERE `dlast`<DATE_SUB('".$dtS."', INTERVAL ".self::$config["session-timeout"]." SECOND)",false);
			}
		}
		//проверяем куки
		if(isset($_COOKIE[self::$class."-user"]))$cookie=$_COOKIE[self::$class."-user"];
		else $cookie="";
		if($cookie)
		{
			if(!self::$user["id"])
			{
				//пробуем восстановить предыдущую сессию
				$r=db::q("SELECT `id`,`stat`,`rights`,`dlog`,`dlast`,`dreg`,`name`,`email`,`display` FROM ".db::tnm(self::$class)." WHERE `stat`=1 AND `ses`='".db::esc($cookie)."'",true);
				$rows=db::rows($r);
				if($rows)
				{
					if($rows>1)
					{
						//system integrity error(!): deleting all sessions
						db::q("UPDATE ".db::tnm(self::$class)." SET `ses`='' WHERE `ses`='".db::esc($cookie)."'",false);
						$cookie="";
					}
					else
					{
						self::$user=db::fetch($r);
						self::$user["id"]=0+self::$user["id"];
						self::$user["stat"]=0+self::$user["stat"];
						self::$user["admn"]=@substr(self::$user["rights"],0,1)==="1";
					}
				}
				else $cookie="";
			}
			else
			{
				//де-авторизуем сессию, если нет совпадений по кукам
				if($cookie!=self::$user["cookie"])self::$user["id"]=0;
			}
		}
		//пробуем начать новую сессию пользователя
		$log=false;
		if($u || $p)
		{
			if(self::$user["id"])msgr::add(_t("Ошибка: вход уже выполнен."),MSGR_TYPE_ERR);
			else
			{
				$r=db::q("SELECT `id`,`stat`,`rights`,`dreg`,`dlog`,`dlast`,`name`,`email`,`display` FROM ".db::tnm(self::$class)." WHERE `stat`=1 AND `name`='".db::esc($name)."' AND `pass`='".@md5($name.$pass)."'",true);
				$row=db::fetch($r);
				if(!$row)
				{
					msgr::add(_t("Неверное имя пользователя или пароль")."!",MSGR_TYPE_ERR);
					$cookie="";
				}
				else
				{
					$log=true;
					self::$user=$row;
					self::$user["id"]=0+self::$user["id"];
					self::$user["stat"]=0+self::$user["stat"];
					self::$user["admn"]=@substr(self::$user["rights"],0,1)==="1";
					$cookie=session_id();
				}
			}
		}
		//пытаемся авторизоваться через внешние системы авторизации
		//ищем модули, зарегистрировавшие себя как расширения для auth
		$mod="";
		$methods=false;
		$euser=false;
		$srvs=self::$c->services(self::$class);
		if(count($srvs))
		{
			//перебираем внешние системы авторизации, пытаемся авторизоваться
			//и останавливаемся при первой успешной авторизации
			foreach($srvs as $mod=>$methods)
			{
				if(isset($methods[AUTH_EXTENDIBLE_USER]))
				{
					$method=$methods[AUTH_EXTENDIBLE_USER];
					$euser=@call_user_func_array(array(__NAMESPACE__."\\".$mod,$method),array(self::$user["id"]));
					if($euser)break;
				}
			}
			//сигнализируем для будущего кода, что авторизация не прошла
			//ни через один внешний модуль
			if(!$euser)$mod="";
			//если внешняя авторизация прошла успешно, то пытаемся авторизовать (или создать)
			//внутреннего пользователя, при неуспешной авторизации, внешняя сессия будет
			//помечена на выход обнулением параметра $euser["feid"] (ссылка на внутреннего пользователя)
			//сам выход при том будет произведен вконце функции
			if($euser)
			{
				//если внутренний пользователь не авторизован...
				if(!self::$user["id"])
				{
					$id=0;
					//если внешний пользователь не содержит указатель на внутреннего
					//пользователя, то пытаемся создать внутреннего пользователя
					if(!$euser["feid"] && self::$config["external-autoreg"])
					{
						if($mod && isset($methods[AUTH_EXTENDIBLE_REGISTER]) && self::_register($euser))
						{
							$id=0+db::iid();
							$method=$methods[AUTH_EXTENDIBLE_REGISTER];
							if(!@call_user_func(array(__NAMESPACE__."\\".$mod,$method),$id))
							{
								self::_delete($id);
								msgr::errorLog("Can't automatically register external user.",true,self::$class,"_check",__LINE__);
							}
							else $euser["feid"]=$id;
						}
						else msgr::errorLog("Can't automatically register the user: method is not supported by module or insert error.",true,self::$class,"_check",__LINE__);
					}
					//если внешний пользователь содержит указатель на только что созданного
					//внутреннего пользователя, то пытаемся его авторизовать
					if($euser["feid"])
					{
						$r=db::q("SELECT `id`,`stat`,`rights`,`dreg`,`dlog`,`dlast`,`name`,`email`,`display` FROM ".db::tnm(self::$class)." WHERE `stat`=1 AND `id`=".(0+$euser["feid"]),true);
						self::$user=db::fetch($r);
						if(self::$user)
						{
							self::$user["id"]=0+self::$user["id"];
							self::$user["stat"]=0+self::$user["stat"];
							self::$user["admn"]=@substr(self::$user["rights"],0,1)==="1";
							$cookie=session_id();
						}
						//если пользователь не найден, то что-то случилось с записью после ее создания
						//удаляем (подчищаем) запись по внутреннему пользователю
						//и обнуляем ссылку на нее у внешнего пользователя
						else
						{
							self::_delete($id);
							$euser["feid"]=0;
						}
					}
					//если создать внутреннего пользователя не удалось, то
					//помечаем внешнего пользователя на выход
					if(!$euser["feid"])$euser["id"]=0;
				}
				//если внутренний пользователь авторизован...
				else
				{
					//если внешний пользователь не ссылается ни на одного
					//внутреннего пользователя, то выполняем автоматическую привязку
					if(!$euser["feid"])
					{
						if($mod && isset($methods[AUTH_EXTENDIBLE_REGISTER]))
						{
							$method=$methods[AUTH_EXTENDIBLE_REGISTER];
							if(!@call_user_func(array(__NAMESPACE__."\\".$mod,$method),self::$user["id"]))
							{
								msgr::errorLog("Can't automatically register external user.",true,self::$class,"_check",__LINE__);
							}
							else $euser["feid"]=self::$user["id"];
						}
						else msgr::errorLog("Can't automatically register the user: method is not supported by module.",true,self::$class,"_check",__LINE__);
					}
					//а если ссылается, то проверяем на какого именно
					else
					{
						//если ссылается на другого, то выполняем выход текущего внутреннего пользователя
						//и пытаемся авторизовать новго внутреннего пользователя
						if($euser["feid"]!=self::$user["id"])
						{
							//завершаем текущую сессию
							db::q("UPDATE ".db::tnm(self::$class)." SET `ses`='', `dlast`='".$dtS."' WHERE `id`=".self::$user["id"],false);
							//ищем нового
							$r=db::q("SELECT `id`,`stat`,`rights`,`dreg`,`dlog`,`dlast`,`name`,`email`,`display` FROM ".db::tnm(self::$class)." WHERE `stat`=1 AND `id`=".(0+$euser["feid"]),true);
							self::$user=db::fetch($r);
							if(self::$user)
							{
								self::$user["id"]=0+self::$user["id"];
								self::$user["stat"]=0+self::$user["stat"];
								self::$user["admn"]=@substr(self::$user["rights"],0,1)==="1";
								$cookie=session_id();
							}
							//если новый внутренний пользователь не найден,
							//то помечаем внешнего пользователя на выход
							else
							{
								$euser["feid"]=0;
							}
						}
					}
				}
			}
			//если внешняя авторизация не удалась, то помечаем
			//внутреннюю сесссию пользователя на завершение (self::$user["id"]===0)
			//само завершение будет произведено ниже, вконце функции
			else
			{
				self::$user["id"]=0;
			}
		}
		//выполняем выход из внешней системы авторизации,
		//если есть пометка на выход ($euser["feid"]===0)
		if($euser && !$euser["feid"])
		{
			if($mod && isset($methods[AUTH_EXTENDIBLE_LOGOUT]))
			{
				$method=$methods[AUTH_EXTENDIBLE_LOGOUT];
				if(!@call_user_func(array(__NAMESPACE__."\\".$mod,$method)))
				{
					msgr::errorLog("Can't automatically logout an external user.",true,self::$class,"_check",__LINE__);
				}
			}
			else msgr::errorLog("Can't automatically logout an external user: method is not supported by module[".$mod."].",true,self::$class,"_check",__LINE__);
		}
		//сохраняем конечный статус авторизации
		//для внутреннего пользователя
		self::_cookie($cookie);
		if(self::$user["id"])
		{
			if($log)self::$user["dlog"]=$dt;
			self::$user["dlast"]=$dt;
			self::$user["cookie"]=$cookie;
			db::q("UPDATE ".db::tnm(self::$class)." SET ".($log?("`ses`='".$cookie."'"):"").($u?("`dlog`='".$dtS."' AND "):"")."`dlast`='".$dtS."' WHERE `id`=".self::$user["id"],false);
			return true;
		}
		else
		{
			self::_userReset();
			return false;
		}
	}

	private static function _cookie($c="")
	{
		if(!$c)$c=self::$user["cookie"];
		@setcookie(self::$class."-user",$c,time()+3600*24*365*2,"/");
		$_COOKIE[self::$class."-user"]=$c;
	}

	private static function _delete($id)
	{
		db::q("DELETE FROM ".db::tnm(self::$class)." WHERE `id`=".$id,false);
	}

	private static function _passSend($phone)
	{
	}

	private static function _register($d)
	{
		$dt=time();
		$dtS=date("Y-m-d H:i:s",$dt);
		if(!isset($d["name"]) || !$d["name"])return false;
		$r=db::q("SELECT `id` FROM ".db::tnm(self::$class)." WHERE `name`='".$d["name"]."'");
		$row=db::fetch($r);
		if($row)
		{
			$len=strlen($d["name"]);
			if($len>23)return false;
			$suf=md5($dtS.mt_rand(1000000,9999999));
			$suf=substr($suf,0,32-$len-1);
			$d["name"]=$d["name"]."-".$suf;
			$r=db::q("SELECT `id` FROM ".db::tnm(self::$class)." WHERE `name`='".$d["name"]."'");
			$row=db::fetch($r);
			if($row)return false;
		}
		if(!isset($d["stat"]))$d["stat"]="1";
		if(!isset($d["rights"]) || (strlen($d["rights"])!=7))$d["rights"]="0010000";
		if(!isset($d["pass"]))$d["pass"]="";
		if(!isset($d["email"]))$d["email"]="";
		if(!isset($d["display"]))$d["display"]="";
		return db::q("INSERT INTO ".db::tnm(self::$class)." VALUES(NULL,".$d["stat"].",'".$d["rights"]."','".$dtS."','".$dtS."','".$dtS."','".$d["name"]."','".$d["pass"]."','','".$d["email"]."','".$d["display"]."')");
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
		//читаем данные
		if(isset(self::$session["user"]) && is_array(self::$session["user"]))self::$user=&self::$session["user"];
		else self::_userReset();
	}

	/**
	* Запись данных в сессию
	*/
	private static function _sessionWrite()
	{
		//убеждаемся, что сессия не была повреждена во время выполнения
		if(!is_array(self::$session))self::$session=array();
		//записываем данные
		self::$session["user"]=&self::$user;
		//пакуем сессию
		$_SESSION[FLEX_APP_NAME."-".self::$class."-data"]=serialize(self::$session);
	}

	public static function _userReset()
	{
		self::$user=array(
			"admn"		=>	false,
			"cookie"	=>	"",
			"dlast"		=>	0,
			"dlog"		=>	0,
			"dreg"		=>	0,
			"email"		=>	"",
			"id"		=>	0,
			"name"		=>	"anonymous",
			"rights"	=>	"00000",
			"stat"		=>	0
		);
	}

	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
		$checked=false;
		if(self::$c->silent())
		{
			if(self::$c->action(self::$class."-reg-logincheck"))self::_actionSilentRegLoginCheck();
		}
		else
		{
			render::addStyle(self::$class,"",true);
			render::addScript(self::$class,"",true);
			if(self::$c->action(self::$class."-login"))
			{
				$checked=true;
				self::_actionLogin();
			}
			if(self::$c->action(self::$class."-logoff"))
			{
				$checked=true;
				self::_actionExit();
			}
			if(self::$c->action(self::$class."-register"))self::_actionRegister();
		}
		if(!$checked)self::_check();
	}

	public static function _init()
	{
		if(self::$_runStep)return;
		self::$_runStep++;
		$c=explode("\\",self::$class);
		self::$class=array_pop($c);
		self::$c=_a::core();
		self::_sessionRead();
		if(!is_array(self::$user) || !count(self::$user) || !array_key_exists("id",self::$user))self::_userReset();
	}

	public static function _render()
	{
		if(!self::$user["id"])
		{
			self::loginBox();
			return;
		}
		if(self::$c->action(self::$class."-login"))
		{
			echo"<div>"._t("Авторизация успешна")."!<br /><a href=\"".self::$c->pageRoot()."\">"._t("Нажмите здесь")."</a> "._t("для перехода на главную").".</div>";
		}
		else
		{
			echo "<div>"._t("Вы вошли как")." ".self::$user["name"]."<br /><a href=\"\" onclick=\"".self::$class.".logoff();return false;\">"._t("Выйти")."</a></div>";
		}
	}

	public static function _sleep()
	{
		self::_sessionWrite();
	}

	public static function access($owner="core",$access="r",$entity="")
	{
		if(self::$user["admn"])return true;
		if(!$entity)return false;
		return false;
	}

	public static function admin()
	{
		return self::$user["admn"];
	}

	public static function logged()
	{
		if(!self::$user["id"])return false;
		else return self::$user["id"];
	}

	public static function login($u,$p)
	{
		return self::_check($u,$p);
	}

	public static function loginBox($head="",$url="")
	{
		if(self::$user["id"])
		{
			$t=tpl::get(self::$class,"logged-box");
			if(self::$c->action(self::$class."-login"))$msg="Вы успешно авторизовались!";
			else $msg="Вы уже авторизованы.";
			$t->setVar("head","Авторизация");
			$t->setVar("msg",$msg);
			$t->setVar("msg-go","Перейти на главную");
			$t->_render();
			return;
		}
		if(!$head)$head="Пожалуйста, авторизуйтесь";
		if($url)self::$session["login-box-returl"]=$url;
		$t=tpl::get(self::$class,"login-box");
		$t->setVar("head",$head);
		$t->setVar("title-login","Вернувшиеся покупатели: пожалуйста, войдите");
		$t->setVar("title-logname","Логин");
		$t->setVar("title-password","Пароль");
		$t->setVar("btn-login-title","Войти");
		$t->setVar("btn-forgot-title","Забыли пароль?");
		$t->setVar("login-name",self::$fields["login"]["name"]);
		$t->setVar("title-register","Первый раз? Введите информацию о себе");
		$t->setVar("regnote","Все поля обязательны для заполнения");
		$t->setVar("title-regname","Логин");
		$t->setVar("title-password-retype","Пароль еще раз");
		$t->setVar("title-regdisplay","Ваше имя");
		$t->setVar("title-regemail","E-mail");
		$t->setVar("btn-register-title","Зарегистрироваться");
		$t->setVar("reg-name",self::$fields["reg"]["name"]);
		$t->setVar("reg-display",self::$fields["reg"]["display"]);
		$t->setVar("reg-email",self::$fields["reg"]["email"]);
		$t->_render();
	}

	public static function user($key="")
	{
		if(!$key)return self::$user;
		if(!isset(self::$user[$key]))return NULL;
		return self::$user[$key];
	}
}
?>

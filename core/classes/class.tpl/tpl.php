<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
final class template
{
	private $c				= null;
	private $class			= "";
	private $error			= false;
	private $fileData		= "";
	private $fileDir		= "";
	private $fileName		= "";
	private $fromSources	= false;
	private $name			= "default";
	private $section		= "";
	private $sectionData	= "";
	private $vars			= array("appRoot","dirHelpers","dirModuleData","dirModuleTemplate","dirUserData","parent","section","template");

	private function _sectionGet($sect)
	{
		//проверяем кэш
		$this->sectionData=tpl::section($this->class,$sect);
		if($this->sectionData!==false)return;
		//если секции нет в кэше, то получаем ее из общего шаблона
		$pattern="/<!--\/\*fet:%".$sect."%\*\/-->(.*)<!--\/\*\/fet:%".$sect."%\*\/-->/ms";
		preg_match($pattern,$this->fileData,$m);
		if(is_array($m) && count($m))$this->sectionData=$m[1];
		else
		{
			$this->sectionData="";
			return;
		}
		//переменные: имя модуля, секция, текущий темплейт
		$pattern="/<!--\/\*fet:def:parent\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$this->class,$this->sectionData);
		$pattern="/<!--\/\*fet:def:section\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$this->section,$this->sectionData);
		$pattern="/<!--\/\*fet:def:template\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$this->name,$this->sectionData);
		//папка сторонних расширений
		$dir=FLEX_APP_DIR_ROOT.FLEX_APP_DIR_HLP."/";
		$pattern="/<!--\/\*fet:def:dirHelpers\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$dir,$this->sectionData);
		//папка данных модуля
		$dir=FLEX_APP_DIR_ROOT.FLEX_APP_DIR_DAT."/_".$this->class."/";
		$pattern="/<!--\/\*fet:def:dirModuleData\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$dir,$this->sectionData);
		//папка темплейтов модуля
		$dir=FLEX_APP_DIR_ROOT.FLEX_APP_DIR_TPL."/".$this->name."/".$this->class."/";
		$pattern="/<!--\/\*fet:def:dirModuleTemplate\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$dir,$this->sectionData);
		//папка данных всех модулей
		$dir=FLEX_APP_DIR_ROOT.FLEX_APP_DIR_DAT."/";
		$pattern="/<!--\/\*fet:def:dirUserData\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$dir,$this->sectionData);
		//рут сайта
		$pattern="/<!--\/\*fet:def:appRoot\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,FLEX_APP_DIR_ROOT,$this->sectionData);
		if($this->fromSources)
		{
			//ссылки на картинки в темплейтах модулей ядра
			$pattern=preg_quote("/".FLEX_APP_DIR."/".FLEX_APP_DIR_TPL."/".$this->name."/".$this->class."/","/");//считаем, что все совпадения по этому шаблону являются изображениями
			$this->sectionData=preg_replace("/".$pattern."/ms","?feh-rsc-get=auto&path=/".FLEX_APP_DIR."/".FLEX_APP_DIR_TPL."/".$this->name."/".$this->class,$this->sectionData);
			//ссылки на картинки в темплейтах пользовательских модулей
			$pattern=preg_quote("/".FLEX_APP_DIR_TPL."/".$this->name."/".$this->class."/","/");//считаем, что все совпадения по этому шаблону являются изображениями
			$this->sectionData=preg_replace("/".$pattern."/ms","?feh-rsc-get=auto&path=/".FLEX_APP_DIR_TPL."/".$this->name."/".$this->class,$this->sectionData);
		}
	}

	public function __construct($className,$tplSection,$tplFile,$useTemplatesSet)
	{
		$this->c=_a::core();
		$this->class=$className;
		$this->name=$useTemplatesSet?$useTemplatesSet:render::template();
		if(!$tplSection)$tplSection=$className;
		$this->section=$tplSection;
		if(!$className)
		{
			$this->error=true;
			return;
		}
		$this->fileName=$tplFile?$tplFile:$className;
		//проверяем кэш
		$this->fileData=tpl::data($className,$this->fileName);
		//если в кэше нет файла, то пытаемся загрузить
		if($this->fileData===false)
		{
			$dir=FLEX_APP_DIR_TPL."/".$this->name."/".$className;
			if(@file_exists($dir))$this->fileDir=$dir;
			else
			{
				if(@file_exists(FLEX_APP_DIR."/".$dir))$this->fileDir=$dir;
				else
				{
					if(FLEX_APP_DIR_SRC)//режим разработки
					{
						$this->fromSources=true;
						if(@file_exists(FLEX_APP_DIR_SRC.".core/".FLEX_APP_DIR."/".$dir))$this->fileDir=FLEX_APP_DIR_SRC.".core/".FLEX_APP_DIR."/".$dir;
						else
						{
							if(@file_exists(FLEX_APP_DIR_SRC.".classes/.".$className."/".$dir))$this->fileDir=FLEX_APP_DIR_SRC.".classes/.".$className."/".$dir;
						}
					}
				}
			}
			if($this->fileDir==="")
			{
				$this->error=true;
				return;
			}
			else
			{
				$this->fileData=@file_get_contents($this->fileDir."/".$this->fileName.".tpl");
				if($this->fileData===false)
				{
					$this->error=true;
					return;
				}
			}
		}
		//получаем нашу секцию шаблона
		$this->_sectionGet($tplSection);
	}

	public function _render($ret=false)
	{
		$this->fileData="";
		if($this->error)
		{
			echo"Template parse error [".$this->class."::".$this->name."]";
			return;
		}
		$pattern="/<!--\/\*(.*)\*\/-->/Ums";
		$this->sectionData=preg_replace($pattern,"",$this->sectionData);
		if($ret)
		{
			$sd=$this->sectionData;
			$this->sectionData="";
			return $sd;
		}
		else
		{
			echo $this->sectionData;
			$this->sectionData="";
		}
	}

	public function fileData()
	{
		return $this->fileData;
	}

	public function get()
	{
		if($this->error)return"Template parse error [".$this->class."::".$this->name."]";
		$pattern="/<!--\/\*(.*)\*\/-->/Ums";
		$this->sectionData=preg_replace($pattern,"",$this->sectionData);
		return $this->sectionData;
	}

	public function error()
	{
		return $this->error;
	}

	public function sectionData()
	{
		return $this->sectionData;
	}

	public function setCont($var,$val)
	{
		$pattern="/<!--\/\*fet:cont:%".$var."%\*\/-->(.*)<!--\/\*\/fet:cont:%".$var."%\*\/-->/ms";
		$this->sectionData=preg_replace($pattern,$val,$this->sectionData);
	}
	public function setVar($var,$val)
	{
		$pattern="<!--/*fet:var:%".$var."%*/-->";
		$this->sectionData=str_replace($pattern,$val,$this->sectionData);
	}

	public function setArray($data)
	{
		if(!is_array($data))return;
		foreach($data as $var=>$val)
		{
			if(is_int($var))continue;
			if(is_array($val))self::setArrayCycle($var,$val);
			else
			{
				$val="".$val;
				$pattern="<!--/*fet:var:%".$var."%*/-->";
				$this->sectionData=str_replace($pattern,$val,$this->sectionData);
			}
		}
	}

	public function setArrayCycle($name,$vals,$conts=array(),$partData="")
	{
		$pattern="/<!--\/\*fetc:%".$name."%\*\/-->(.*)<!--\/\*\/fetc:%".$name."%\*\/-->/ms";
		if(!$partData)
			preg_match($pattern,$this->sectionData,$m);
		else
			preg_match($pattern,$partData,$m);
		if(is_array($m) && count($m))$data=$m[1];
		else
		{
			if(!$partData)return;
			else return $partData;
		}
		$res="";
		//делаем обход рутового массива только по целым ключам,
		//ассоциаивные ключи пропускаем
		$keys=array_keys($vals);
		$len=count($keys);
		if(!$len)return $res;
		for($cnt=0;$cnt<$len;$cnt++)
		{
			//ключ должен быть целым числом (не ассоциативный)
			$key=$keys[$cnt];
			if(!is_int($key))continue;
			//получаем массив переменных
			$vars=$vals[$key];
			//пытаемся получить массив контейнеров
			if(isset($conts[$key]) && is_array($conts[$key]))$cont=$conts[$cnt];
			else $cont=false;
			//обход массива переменных делаем наоборот -
			//только по ассоциаивным ключам
			$item=$data;
			if(is_array($vars))
			{
				$keys1=array_keys($vars);
				$len1=count($keys1);
			}else $len1=0;
			if($len1)
			{
				for($cnt1=0;$cnt1<$len1;$cnt1++)
				{
					$key1=$keys1[$cnt1];
					if(!is_string($key1))continue;
					$var=$key1;
					$val=$vars[$var];
					if(is_array($val))
					{
						$item=self::setArrayCycle($var,$val,array(),$item);
					}
					else
					{
						$pat="<!--/*fet:var:%".$var."%*/-->";
						$item=str_replace($pat,$val,$item);
					}
				}
			}
			//обходим контейнеры, также - только
			//по ассоциативным ключам
			if(is_array($cont))
			{
				$keys1=array_keys($cont);
				$len1=count($keys1);
			}else $len1=0;
			if($len1)
			{
				for($cnt1=0;$cnt1<$len1;$cnt1++)
				{
					$key1=$keys1[$cnt1];
					if(!is_string($key1))continue;
					$var=$key1;
					$val=$cont[$var];
					$pat="/<!--\/\*fet:cont:%".$var."%\*\/-->(.*)<!--\/\*\/fet:cont:%".$var."%\*\/-->/ms";
					$item=preg_replace($pat,$val,$item);
				}
			}
			$res.=$item;
		}
		if(!$res)
		{
			if(count($vals))$res=$data;
			else $res="";
		}
		if(!$partData)
			$this->sectionData=preg_replace($pattern,$res,$this->sectionData);
		else
			return preg_replace($pattern,$res,$partData);
	}
}

final class tpl
{
	private static $_runStep	= 0;
	private static $sections	=	array();
	private static $templates	=	array();

	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
	}

	public static function _init()
	{
		if(self::$_runStep)return;
		self::$_runStep++;
	}
	public static function _sleep(){}

	public static function data($className,$template)
	{
		if(isset(self::$templates[$className]))return self::$templates[$className];
		else return false;
	}

	/**
	* Возвращает объект для управления определенной секцией темплейта
	*
	* @param $className string[32] - имя класса
	* @param $tplSection string - идентификатор раздела шаблона (напр., "mysection")
	* @param $tplFile string - имя файла с шаблоном (по-умолчанию совпадает с именем класса)
	* @param $useTemplatesSet string - текущий активный набор темплейтов класса (по-умолчанию используется имя системного набора)
	*
	* @return tpl object/false - созданный объект, для управления указанным разделом шаблона
	*/
	public static function get($className,$tplSection="",$tplFile="",$useTemplatesSet="")
	{
		$t=new template($className,$tplSection,$tplFile,$useTemplatesSet);
		if($t->error())return $t;
		//сохраняем общий шаблон
		if(!isset(self::$templates[$className]))self::$templates[$className]=$t->fileData();
		//сохраняем шаблон секции
		if(!isset(self::$sections[$className]))self::$sections[$className]=array();
		if(!isset(self::$sections[$className][$tplSection]))self::$sections[$className][$tplSection]=$t->sectionData();
		return $t;
	}

	public static function section($className,$section)
	{
		if(isset(self::$sections[$className][$section]))return self::$sections[$className][$section];
		else return false;
	}
}
?>
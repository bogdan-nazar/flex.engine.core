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
		//if($this->fromSources)$modDir=FLEX_APP_DIR_INC;
		//else $modDir=FLEX_APP_DIR_MOD;
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
		$this->name=$useTemplatesSet?$useTemplatesSet:$this->c->config("","template");
		if(!$tplSection)$tplSection=$className;
		$this->section=$tplSection;
		if(!$className)
		{
			$this->error=true;
			return;
		}
		$this->fileName=$tplFile?$tplFile:$className;
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
		$this->_sectionGet($tplSection);
	}

	public function _render()
	{
		if($this->error)
		{
			echo"Template parse error [".$this->class."::".$this->name."]";
			return;
		}
		$pattern="/<!--\/\*(.*)\*\/-->/Ums";
		$this->sectionData=preg_replace($pattern,"",$this->sectionData);
		echo $this->sectionData;
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
			$val="".$val;
			if(!$val)continue;
			$pattern="<!--/*fet:var:%".$var."%*/-->";
			$this->sectionData=str_replace($pattern,$val,$this->sectionData);
		}
	}

	public function setArrayCycle($name,$vals,$partData="")
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
		foreach($vals as $vars)
		{
			if(!is_array($vars))continue;
			$item=$data;
			foreach($vars as $var=>$val)
			{
				if(is_array($val))
					$item=self::setArrayCycle($var,$val,$item);
				else
				{
					$pat="<!--/*fet:var:%".$var."%*/-->";
					$item=str_replace($pat,$val,$item);
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
	private static $items		=	array();

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
		if(!isset(self::$items[$className]))self::$items[$className] = array();
		$t=new template($className,$tplSection,$tplFile,$useTemplatesSet);
		if($t->error())return $t;
		self::$items[$className][$tplSection]=$t;
		return $t;
	}
}
?>
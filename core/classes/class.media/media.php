<?
namespace FlexEngine;
defined("FLEX_APP") or die("Forbidden.");
define("MEDIA_IMG_UNK",0);
define("MEDIA_IMG_GIF",1);
define("MEDIA_IMG_JPG",2);
define("MEDIA_IMG_PNG",3);
define("MEDIA_IMG_SWF",4);
define("MEDIA_IMG_PSD",5);
define("MEDIA_IMG_BMP",6);
define("MEDIA_IMG_TIFFI",7);
define("MEDIA_IMG_TIFFM",8);
define("MEDIA_IMG_JPC",9);
define("MEDIA_IMG_JP2",10);
define("MEDIA_IMG_JPX",11);
define("MEDIA_IMG_JB2",12);
define("MEDIA_IMG_SWC",13);
define("MEDIA_IMG_IFF",14);
define("MEDIA_IMG_WBMP",15);
define("MEDIA_IMG_XBM",16);
define("MEDIA_IRES_CROP",0);
define("MEDIA_IRES_FITHOR",1);
define("MEDIA_IRES_FITVER",2);
define("MEDIA_IRES_FITRAT",3);
define("MEDIA_IRES_MAKEGAL",4);
define("MEDIA_TYPE_ANY",0);
define("MEDIA_TYPE_DOC",1);
define("MEDIA_TYPE_IMG",2);
define("MEDIA_TYPE_MIS",3);
define("MEDIA_TYPE_SND",4);
define("MEDIA_TYPE_VID",5);
define("MEDIA_WRITEMODE_INSERT",0);
define("MEDIA_WRITEMODE_UPDATE",1);
final class media
{
	private static $_runStep	=	0;
	private static $c			=	NULL;
	private static $config		=	array(
		"batchMaxCount"			=>	1000,
		"dirOwner"				=>	"media",
		"dirShared"				=>	"_shared",
		"imgMaxCanvasSize"		=>	8000,
		"imgTypeDefault"		=>	MEDIA_IMG_JPG,
		"sizeDelimiter"			=>	"-",
		"thumbsDir"				=>	"thumbs",
		"uploadDir"				=>	"uploads",
		"uploadFileMaxSize"		=>	10485760, //10M
		"uploaderThumbDir"		=>	"uploader",
		"uploaderThumbExt"		=>	"jpg",
		"uploaderThumbSize"		=>	array(160,105)
	);
	private static $class		=	__CLASS__;
	private static $dbFields	=	array();
	private static $fieldsReq	=	array("name_id","name_sized","size_delim","name","directory","title","credit");
	private static $files		=	array();
	private static $imgSup		=	array();
	private static $lastMsg		=	"";
	private static $session		=	array();
	private static $silent		=	false;
	private static $types		=	array(
		MEDIA_TYPE_ANY			=>	array(),
		MEDIA_TYPE_DOC			=>	array("doc","docx","pdf","ppt","pptx","rtf","txt","xls","xlsx"),
		MEDIA_TYPE_IMG			=>	array("gif","jfif","jpeg","jpe","jpg","png"),
		MEDIA_TYPE_MIS			=>	array("rar","zip"),
		MEDIA_TYPE_SND			=>	array("mp3","ogg","wav"),
		MEDIA_TYPE_VID			=>	array("avi","mov","mpg","mpeg","swf")
	);
	private static $uid			=	0;

	private static function _actionSilentLibImgCrop()
	{
		$res="{res:";
		$module=self::$c->post(self::$class."-lib-files-module");
		$entity=0+self::$c->post(self::$class."-lib-files-entity");
		//проверка модуля
		if(!$module || !@class_exists(__NAMESPACE__."\\".$module))
		{
			echo $res."false,msg:\"Ссылочный модуль не найден [".$module."]\"}";
			return;
		}
		$mid=self::$c->modId($module,true);
		if(!$mid)
		{
			echo $res."false,msg:\"Ссылочный модуль не найден [".$module."]\"}";
			return;
		}
		//проверка fid
		$fid=0+self::$c->post(self::$class."-lib-pid");
		if(!$fid)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: задан неверный идентификатор изображения [".$fid."]\"}";
			return;
		}
		$dim=self::$c->post(self::$class."-lib-dim");
		$dim=explode(",",$dim);
		//проверка параметров обрезки
		$x=0;
		$y=0;
		$w=0;
		$h=0;
		if(isset($dim[0]))$x=0+$dim[0];
		if(isset($dim[1]))$y=0+$dim[1];
		if(isset($dim[2]))$w=0+$dim[2];
		if(isset($dim[3]))$h=0+$dim[3];
		if(!$w)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: не указана ширина зоны-источника [".$w."]\"}";
			return;
		}
		if(!$h)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: не указана высота зоны-источника [".$w."]\"}";
			return;
		}
		$scale=0+self::$c->post(self::$class."-lib-scale");
		if($scale<=0 || $scale>1)$scale=1;
		$sw=(int)round($scale*$w);
		$sh=(int)round($scale*$h);
		if(!$sw || !$sh)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: некорректный масштаб scale [".$scale."]\"}";
			return;
		}
		if($sw>self::$config["imgMaxCanvasSize"] || $sh>self::$config["imgMaxCanvasSize"])
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: размер результатирующего изображения слишком велик (>".self::$config["imgMaxCanvasSize"]."\"}";
			return;
		}
		//находим запись
		$uid=auth::user("id");
		$q="SELECT `width`,`height`,`name_id`,`name_sized`,`size_delim`,`extension`,`name`,`directory`,`title`,`credit`,`content_type`
		FROM ".db::tnm(self::$class)." WHERE `id`={$fid} AND `pid`=0 AND `mid`={$mid}".(!auth::admin()?" AND `uid`={$uid}":"");
		$r=db::q($q,false);
		if($r===false)
		{
			echo $res."false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$rec=@mysql_fetch_assoc($r);
		if(!$rec)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: нет доступа или изображение не зарегистрировано\"}";
			return;
		}
		$ctype=$rec["content_type"];
		if(substr($ctype,0,5)!="image")
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: указанный файл не является изображением\"}";
			return;
		}
		$wid=0+$rec["width"];
		$ht=0+$rec["height"];
		$nameId=0+$rec["name_id"];
		$nameSized=0+$rec["name_sized"];
		$sizeDelim=$rec["size_delim"];
		$ext=$rec["extension"];
		$name=$rec["name"];
		$dir=$rec["directory"];
		$title=$rec["title"];
		$credit=$rec["credit"];
		if(!lib::mquotes_runtime())
		{
			$title=str_replace("\"","\\\"",$title);
			$credit=str_replace("\"","\\\"",$credit);
		}
		$imo=$dir."/".($name?(($nameId?($fid.$sizeDelim):"").$name):$fid).($nameSized?($sizeDelim.$wid."x".$ht):"").".".$ext;
		if(!@file_exists($imo))
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: указанный файл изображения не найден\"}";
			return;
		}
		$dt=time();
		$dtSQL=date("Y-m-d H:i:s",$dt);
		//регистрируем файл
		$newName=$name?($name."-copy"):"";
	 	$q="INSERT INTO ".db::tnm(self::$class)." VALUES(NULL,{$fid},{$uid},{$mid},{$sw},{$sh},0,'{$dtSQL}',1,{$nameSized},'{$sizeDelim}','{$ext}','{$newName}','','{$dir}','{$title}','{$credit}','{$ctype}')";
		$r=db::q($q,false);
		if($r===false)
		{
			echo $res."false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$iid=0+mysql_insert_id();
		$im=$dir."/".$iid.($name?($sizeDelim.$newName):"").($nameSized?($sizeDelim.$sw."x".$sh):"").".".$ext;
		//проверка доступа на запись
		if(@file_exists($im))
		{
			@unlink($im);
			if(@file_exists($im))
			{
				echo $res."res:false,msg:\"Ошибка: невозможно создать файл [".$im."], доступ на запись ограничен [".__LINE__."]\"}";
	 			@mysql_query("DELETE FROM ".db::tnm(self::$class)." WHERE `id`={$iid}");
	 			return;
			}
		}
		else
		{
			@file_put_contents($im,"test");
			if(!@is_writable($im))
			{
				echo $res."false,msg:\"Ошибка: невозможно создать файл [".$im."], доступ на запись ограничен [".__LINE__."]\"}";
	 			@mysql_query("DELETE FROM ".db::tnm(self::$class)." WHERE `id`={$iid}");
	 			return;
			}
			@unlink($im);
		}
		if(!self::_resample($imo,$im,MEDIA_IRES_CROP,array("srcRect"=>array($x,$y,$w,$h),"dstRect"=>array($sw,$sh))))
		{
			echo $res."res:false,msg:\"Невозможно выполнить операцию: ошибка изменения размера изображения [".__LINE__."]\"}";
 			@mysql_query("DELETE FROM ".db::tnm(self::$class)." WHERE `id`={$iid}");
 			return;
		}
		db::q("UPDATE ".db::tnm(self::$class)." SET `bytes`=".(0+@filesize($im))." WHERE `id`=".$iid,false);
		$sz=0+@filesize($im);
		//создаем миниатюру для media.uploader
		$yr=date("Y",$dt);
		$mn=date("m",$dt);
		$d=date("d",$dt);
		$thd=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/".self::$config["uploaderThumbDir"]."/".$yr."/".$mn."/".$d;
		if(!@file_exists($thd))
		{
			@mkdir($thd,0777,true);
			if(!@file_exists($thd))$th="";
			else $th=$thd."/".$iid.".".$ext;
		}
		else $th=$thd."/".$iid.".".$ext;
		if($th)self::_resample($im,$th,MEDIA_IRES_CROP,array("dstRect"=>array(self::$config["uploaderThumbSize"][0],self::$config["uploaderThumbSize"][1])));
		$item="{id:".$iid.",pid:".$fid.",width:".$sw.",height:".$sh.",bytes:".$sz.",dt:\"".$dtSQL."\",dts:".$dt.",name_id:1,name_sized:".($nameSized?"1":"0").",size_delim:\"".$sizeDelim."\",extension:\"".$ext."\",name:\"".$newName."\",path:\"".(self::$c->appRoot().$dir)."\",title:\"".$title."\",credit:\"".$credit."\",content_type:\"".$ctype."\"}";
		echo $res."true,msg:\"\",item:".$item."}";
	}

	private static function _actionSilentLibFileEdit()
	{
		$res="{res:";
		$module=self::$c->post(self::$class."-lib-files-module");
		$entity=0+self::$c->post(self::$class."-lib-files-entity");
		//проверка модуля
		if(!$module || !@class_exists(__NAMESPACE__."\\".$module))
		{
			$res.="false,msg:\"Ссылочный модуль не найден [".$module."]\"}";
			echo $res;
			return;
		}
		$mid=self::$c->modId($module,true);
		if(!$mid)
		{
			echo $res."false,msg:\"Ссылочный модуль не найден [".$module."]\"}";
			return;
		}
		//проверяем id
		$id=0+self::$c->post(self::$class."-lib-file-id");
		if(!$id)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: задан неверный идентификатор файла [".$id."]\"}";
			return;
		}
		//проверяем name
		$name=self::$c->post(self::$class."-lib-file-name");
		//проверяем title
		$title=self::$c->post(self::$class."-lib-file-title");
		//проверяем credit
		$credit=self::$c->post(self::$class."-lib-file-credit");
		if(lib::mquotes_gpc())
		{
			$name=stripslashes($name);
			$title=stripslashes($title);
			$credit=stripslashes($credit);
		}
		if(!lib::validStr($name,LIB_STR_TYPE_FILE,true,1,128,true,"Название файла"))
		{
			echo $res."false,msg:\"".lib::lastMsg()."\"}";
			return;
		}
		$name_id=(0+self::$c->post(self::$class."-lib-file-noid"))?0:1;
		if(!$name)$name_id=1;//защита от "пустого" имени
		$name_sized=(0+self::$c->post(self::$class."-lib-file-nosizes"))?0:1;
		//находим запись по id
		$uid=auth::user("id");
		$q="SELECT `width`,`height`,`name_id`,`name_sized`,`size_delim`,`extension`,`name`,`directory`,`content_type`
		FROM ".db::tnm(self::$class)." WHERE `id`={$id} AND `mid`={$mid}".(!auth::admin()?" AND `uid`={$uid}":"");
		$r=db::q($q,false);
		if($r===false)
		{
			echo $res."false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$rec=@mysql_fetch_assoc($r);
		if(!$rec)
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: нет доступа или изображение не зарегистрировано\"}";
			return;
		}
		$old_nameId=0+$rec["name_id"];
		$old_sizeDelim=$rec["size_delim"];
		$ext=$rec["extension"];
		$old_name=$rec["name"];
		$dir=$rec["directory"];
		$ctype=$rec["content_type"];
		$is_image=false;
		if(substr($ctype,0,5)=="image")
		{
			$is_image=true;
			$wd=0+$rec["width"];
			$ht=0+$rec["height"];
			$old_nameSized=0+$rec["name_sized"];
		}
		else $old_nameSized=0;
		$old_filename=($old_name?(($old_nameId?($id.$old_sizeDelim):"").$old_name):$id).($is_image?($old_nameSized?($old_sizeDelim.$wd."x".$ht):""):"").".".$ext;
		$old_filepath=$dir."/".$old_filename;
		if(!@file_exists($old_filepath))
		{
			echo $res."false,msg:\"Невозможно выполнить операцию: указанный файл не найден [".$old_filepath."]\"}";
			return;
		}
		//пытаемся переименовать файл
		$new_filename=($name?(($name_id?($id.$old_sizeDelim):"").$name):$id).($is_image?($name_sized?($old_sizeDelim.$wd."x".$ht):""):"").".".$ext;
		if($new_filename!=$old_filename)
		{
			if(@file_exists($dir."/".$new_filename))
			{
				echo $res."false,msg:\"Невозможно переименовать файл: другой файл с таким же именем уже существует [".$new_filename."]\"}";
				return;
			}
			@rename($old_filepath,$dir."/".$new_filename);
			if(!@file_exists($dir."/".$new_filename))
			{
				echo $res."false,msg:\"Шибка переименования файла: ошибка файловой системы, обратитесь к разработчикам сайта [".msgr::config(MSGR_EMAIL_DEVEL)."].\"}";
				return;
			}
		}
		$q="UPDATE ".db::tnm(self::$class)." SET `name`='".mysql_real_escape_string($name)."',`title`='".mysql_real_escape_string($title)."',
		`credit`='".mysql_real_escape_string($credit)."', `name_id`=".$name_id.",`name_sized`=".$name_sized."
		WHERE `id`=".$id;
		$r=db::q($q,false);
		if($r===false)
		{
			@rename($dir."/".$new_filename,$old_filepath);
			echo $res."false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$item="{id:".$id.",name_id:".($name_id?"true":"false").",name_sized:".($name_sized?"true":"false").",filename:\"".$new_filename."\",name:\"".$name."\",title:\"".str_replace("\"","\\\"",$title)."\",credit:\"".str_replace("\"","\\\"",$credit)."\"}";
		echo $res."true,msg:\"\",item:".$item."}";
	}

	private static function _actionSilentLibListMore()
	{
		$res="{res:";
		$refid=0+self::$c->post(self::$class."-lib-files-refid");
		$module=self::$c->post(self::$class."-lib-files-module");
		$entity=0+self::$c->post(self::$class."-lib-files-entity");
		$type=self::$c->post(self::$class."-lib-files-type");
		$count=0+self::$c->post(self::$class."-lib-files-count");
		//проверка модуля
		if(!$module || !@class_exists(__NAMESPACE__."\\".$module))
		{
			echo $res."false,msg:\"Ссылочный модуль не найден [".$module."]\"}";
			return;
		}
		$mid=self::$c->modId($module,true);
		if(!$mid)
		{
			echo $res."false,msg:\"Ссылочный модуль не найден [".$module."]\"}";
			return;
		}
		//
		if(!$count)$count=self::$config[self::$class."libListPerTime"];
		$items=",items:[";
		if($refid)
		{
			$q="SELECT `uploaded`,`extension` FROM ".db::tnm(self::$class)." WHERE `id`=".$refid;
			$r=db::q($q,false);
			if($r===false)
			{
				$msg=db::lastError()."\"";
				$msg=str_replace("\r\n"," ",$msg);
				$msg=preg_replace("/\\s+/m"," ",$msg);
				$msg=str_replace("\"","\\\"",$msg);
				$res.="false,msg:\"Ошибка выполнения запроса [".__LINE__."]\",debug:{mysql_error:\"".$msg."\"}}";
				echo $res;
				return;
			}
			$rec=@mysql_fetch_assoc($r);
			if(!$rec)
			{
				echo $res."false,msg:\"Ссылочный файл не найден, перезагрузите страницу для актуализации ее состояния [".$refid."]\"}";
				return;
			}
			$dt=$rec["uploaded"];
			$dtSql=" AND `m`.`uploaded`<'{$dt}'";
			if($type!="any")$type=self::typeDefine($rec["extension"]);
			else $type=false;
		}
		else
		{
			if($type=="any")
			{
				echo $res."false,msg:\"Ссылочный файл не найден, перезагрузите страницу для актуализации ее состояния [".$refid."]\"}";
				return;
			}
			$dtSql="";
			if(defined("MEDIA_TYPE_".strtoupper($type)))eval("\$type=MEDIA_TYPE_".strtoupper($type).";");
			else
			{
				echo $res."false,msg:\"Ссылочный файл не найден, перезагрузите страницу для актуализации ее состояния [".$refid."]\"}";
				return;
			}
		}
		//поля
		//id,pid,uid,mid,width,height,uploaded,name_sized,size_delim,extension,name,name_orig,directory,title,credit,content_type
		if($entity)$entSql=" AND `mb`.`oid`=".$entity."";
		else $entSql=" AND (ISNULL(`mb`.`oid`) OR `mb`.`oid`=0)";
		$q="SELECT
		`m`.`id`,`m`.`width`,`m`.`height`,`m`.`bytes`,`m`.`uploaded`,`m`.`name_id`,`m`.`name_sized`,`m`.`size_delim`,
		`m`.`extension`,`m`.`name`,`m`.`directory`,`m`.`title`,`m`.`credit`,`m`.`content_type`
		FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tn("mods")." `md` ON `md`.`id`=`m`.`mid`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `m`.`id`=`mb`.`mid`
		WHERE `m`.`pid`=0".$dtSql.($type?" AND `type`={$type}":"").$entSql."
		AND `md`.`id`=".$mid.(!auth::admin()?" AND `m`.`uid`={$uid}":"")."
		ORDER BY `m`.`uploaded` DESC LIMIT 0,".($count+1);
		$r=db::q($q,false);
		if($r===false)
		{
			$msg=lib::jsonPrepare(db::lastError());
			$res.="false,msg:\"Ошибка выполнения запроса [".__LINE__."]\",debug:{mysql_error:\"".$msg."\"}}";
			echo $res;
			return;
		}
		$cnt=0;
		$ids=array();
		while($rec=@mysql_fetch_assoc($r))
		{
			$cnt++;
			if($cnt>$count)break;
			$id=0+$rec["id"];
			$ids[]=$id;
			$bytes=0+$rec["bytes"];
			$dt=$rec["uploaded"];
			$dts=strtotime($dt);
			$nid=(0+$rec["name_id"])?"true":"false";
			$szd=$rec["size_delim"];
			$ext=$rec["extension"];
			$name=$rec["name"];
			$path=self::$c->appRoot().$rec["directory"];
			$tit=$rec["title"];
			$cred=$rec["credit"];
			if(!lib::mquotes_runtime())
			{
				$tit=str_replace("\"","\\\"",$tit);
				$cred=str_replace("\"","\\\"",$cred);
			}
			$ctp=$rec["content_type"];
			$is_image=false;
			$wd=0;
			$ht=0;
			if(substr($ctp,0,5)=="image")
			{
				$is_image=true;
				$wd=0+$rec["width"];
				$ht=0+$rec["height"];
				$nsd=(0+$rec["name_sized"])?"true":"false";
			} else $nsd="false";
			if(!$bytes)
			{
				$file=$rec["directory"]."/".($name?(($nid=="true"?($id.$szd):"").$name):$id).($is_image?($nsd=="true"?($szd.$wd."x".$ht):""):"").".".$ext;
				$fs=false;
				if (@file_exists($file))$fs=@filesize($file);
				if($fs)db::q("UPDATE ".db::tnm(self::$class)." SET `bytes`=".$fs." WHERE `id`=".$id,false);
			}
			$items.="{id:".$id.",pid:0,width:".$wd.",height:".$ht.",bytes:".$bytes.",dt:\"".$dt."\",dts:".$dts.",name_id:".$nid.",name_sized:".$nsd.",size_delim:\"".$szd."\",extension:\"".$ext."\",name:\"".$name."\",path:\"".$path."\",title:\"".$tit."\",credit:\"".$cred."\",content_type:\"".$ctp."\"},";
		}
		$items=",items_more:".($cnt<=$count?"false":"true").rtrim($items,",")."]";
		$childs=",childs:[";
		if(count($ids))
		{
			$q="SELECT
			`m`.`id`,`m`.`pid`,`m`.`width`,`m`.`height`,`m`.`bytes`,`m`.`uploaded`,`m`.`name_id`,`m`.`name_sized`,`m`.`size_delim`,
			`m`.`extension`,`m`.`name`,`m`.`directory`,`m`.`title`,`m`.`credit`,`m`.`content_type`
			FROM ".db::tnm(self::$class)." `m`
			INNER JOIN ".db::tn("mods")." `md` ON `md`.`id`=`m`.`mid`
			LEFT JOIN ".db::tnm(self::$class."_binds")." `mb` ON `m`.`id`=`mb`.`mid`
			WHERE `m`.`pid`>0 AND (`m`.`id` IN (".implode(",",$ids).")) AND `md`.`id`=".$mid.(!auth::admin()?" AND `m`.`uid`={$uid}":"")."
			ORDER BY `m`.`uploaded` DESC";
			$r=db::q($q,false);
			if($r===false)
			{
				$msg=db::lastError();
				$msg=str_replace("\r\n"," ",$msg);
				$msg=preg_replace("/\\s+/m"," ",$msg);
				$msg=addslashes($msg);
				$res.="false,msg:\"Ошибка выполнения запроса [".__LINE__."]\",debug:{mysql_error:\"".$msg."\"}}";
				echo $res;
				return;
			}
			while($rec=@mysql_fetch_assoc($r))
			{
				$id=0+$rec["id"];
				$pid=0+$rec["pid"];
				$wd=0+$rec["width"];
				$ht=0+$rec["height"];
				$bytes=0+$rec["bytes"];
				$dt=$rec["uploaded"];
				$dts=strtotime($dt);
				$nid=(0+$rec["name_id"])?"true":"false";
				$nsd=(0+$rec["name_sized"])?"true":"false";
				$szd=$rec["size_delim"];
				$ext=$rec["extension"];
				$name=$rec["name"];
				$path=self::$c->appRoot().$rec["directory"];
				$tit=$rec["title"];
				$cred=$rec["credit"];
				if(!lib::mquotes_runtime())
				{
					$tit=str_replace("\"","\\\"",$tit);
					$cred=str_replace("\"","\\\"",$cred);
				}
				$ctp=$rec["content_type"];
				$is_image=false;
				if(substr($ctp,0,5)=="image")
				{
					$is_image=true;
					$wd=0+$rec["width"];
					$ht=0+$rec["height"];
					$nsd=(0+$rec["name_sized"])?"true":"false";
				} else $nsd="false";
				if(!$bytes)
				{
					$file=$rec["directory"]."/".($name?(($nid=="true"?($id.$szd):"").$name):$id).($is_image?($nsd=="true"?($szd.$wd."x".$ht):""):"").".".$ext;
					$fs=false;
					if (@file_exists($file))$fs=@filesize($file);
					if($fs)db::q("UPDATE ".db::tnm(self::$class)." SET `bytes`=".$fs." WHERE `id`=".$id,false);
				}
				$childs.="{id:".$id.",pid:".$pid.",width:".$wd.",height:".$ht.",bytes:".$bytes.",dt:\"".$dt."\",dts:".$dts.",name_id:".$nid.",name_sized:".$nsd.",size_delim:\"".$szd."\",extension:\"".$ext."\",name:\"".$name."\",path:\"".$path."\",title:\"".$tit."\",credit:\"".$cred."\",content_type:\"".$ctp."\"},";
			}
		}
		$childs=rtrim($childs,",")."]";
		echo $res."true,msg:\"\"".$items.$childs."}";
	}

	private static function _actionSilentUploaderImgDel()
	{
		//проверка дилога
		$did=-1;
		$id=-1;
		if(isset($_POST["did"]))$did=0+$_POST["did"];
		//проверка id
		if(isset($_POST["id"]))$id=0+$_POST["id"];
		if($did==-1)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Невозможно выполнить операцию: не задан идентификатор диалога\"}";
			return;
		}
		if(!$id)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Невозможно выполнить операцию: задан неверный идентификатор изображения\"}";
			return;
		}
		//проверка модуля
		if(!isset($_POST["mName"]))
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Невозможно выполнить операцию: модуль-владелец не указан\"}";
			return;
		}
		$mName=$_POST["mName"];
		$q="SELECT `id` FROM ".db::tn("mods")." WHERE `class`='".$mName."'";
		$r=@mysql_query($q);
		if($r===false)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$rec=mysql_fetch_assoc($r);
		if(!$rec)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Невозможно выполнить операцию: модуль-владелец не найден\"}";
			return;
		}
		$mid=0+$rec["id"];
		$uid=auth::user("id");
		//находим запись
		$q="SELECT `width`,`height`,`uploaded`,`name_sized`,`size_delim`,`extension`,`name`,`directory` FROM ".db::tnm(self::$class)." WHERE `id`={$id} AND `pid`!=0 AND `mid`={$mid} AND `uid`={$uid}";
		$r=@mysql_query($q);
		if($r===false)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$rec=mysql_fetch_assoc($r);
		if(!$rec)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Невозможно выполнить операцию: изображение не найдено в БД [".__LINE__."]\"}";
			return;
		}
		//сохраняем параметры
		$wd=0+$rec["width"];
		$ht=0+$rec["height"];
		$mdt=strtotime($rec["uploaded"]);
		$nameSized=((0+$rec["name_sized"])==1);
		$sizeDelim=$rec["size_delim"];
		$ext=$rec["extension"];
		$name=$rec["name"];
		if($name)$name=$sizeDelim.$name;
		$dir=$rec["directory"];
		if($nameSized)
			$imp=$dir."/".$id.$name.$sizeDelim.$wd.$sizeDelim.$ht.".".$ext;
		else
			$imp=$dir."/".$id.$name.".".$ext;
		$yr=date("Y",$mdt);
		$mn=date("m",$mdt);
		$d=date("d",$mdt);
		$thd=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/".self::$config["uploaderThumbDir"];
		$imt=$thd."/".$yr."/".$mn."/".$d."/".$id.".".$ext;
		//удаляем запись
		$q="DELETE FROM ".db::tnm(self::$class)." WHERE `id`={$id} AND `pid`!=0 AND `mid`={$mid} AND `uid`={$uid}";
		$r=@mysql_query($q);
		if($r===false)
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Ошибка операции с базой данных [".__LINE__."]\"}";
			return;
		}
		$rows=mysql_affected_rows();
		if(!$rows || ($rows==-1))
		{
			echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:false,msg:\"Неизвестная ошибка: информация по изображению не удалена из БД [".__LINE__."]\"}";
			return;
		}
		@unlink($imp);
		@unlink($imt);
		echo"{action:\"".self::$class."-uploader-imgdel\",did:".$did.",id:".$id.",res:true,msg:\"\"}";
	}

	private static function _actionSilentUploaderFileDelete()
	{
		echo"{action:'".self::$class."-uploader-filedelete',res:true}";
		//проверка модуля
		if(!isset($_POST["mName"]))return;
		$mName=$_POST["mName"];
		$q="SELECT `id` FROM ".db::tn("mods")." WHERE `class`='".$mName."'";
		$r=@mysql_query($q);
		if($r===false)return;
		$rec=mysql_fetch_assoc($r);
		if(!$rec)return;
		$mid=0+$rec["id"];
		//проверка fid
		if(!isset($_POST["fid"]))return;
		$fid=0+$_POST["fid"];
		if(!$fid)return;
		$uid=auth::user("id");
		//находим запись
		$q="SELECT `width`,`height`,`uploaded`,`name_sized`,`size_delim`,`extension`,`name`,`directory` FROM ".db::tnm("media")." WHERE `id`={$fid} AND `pid`=0 AND `mid`={$mid} AND `uid`={$uid}";
		$r=@mysql_query($q);
		if($r===false)return;
		$rec=mysql_fetch_assoc($r);
		if(!$rec)return;
		$wid=0+$rec["width"];
		$ht=0+$rec["height"];
		$mdt=strtotime($rec["uploaded"]);
		$nameSized=((0+$rec["name_sized"])==1);
		$sizeDelim=$rec["size_delim"];
		$ext=$rec["extension"];
		$name=$rec["name"];
		if($name)$name=$sizeDelim.$name;
		$dir=$rec["directory"];
		$thd=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/".self::$config["uploaderThumbDir"];
		//другие размеры если есть
		$q="SELECT `id`,`width`,`height`,`uploaded`,`name_sized`,`size_delim`,`extension`,`name`,`directory` FROM ".db::tnm("media")." WHERE `pid`={$fid} AND `mid`={$mid} AND `uid`={$uid}";
		$r=@mysql_query($q);
		if($r!==false)
		{
			$cnt=0;
			while($rec=mysql_fetch_assoc($r))
			{
				$cnt++;
				$id=0+$rec["id"];
				$w=0+$rec["width"];
				$h=0+$rec["height"];
				$dt=strtotime($rec["uploaded"]);
				$ns=((0+$rec["name_sized"])==1);
				$sd=$rec["size_delim"];
				$ex=$rec["extension"];
				$nm=$rec["name"];
				if($nm)$nm=$sd.$nm;
				$d=$rec["directory"];
				if($ns)
					$fn=$d."/".$id.$nm.$sd.$w.$sd.$h.".".$ex;
				else
					$fn=$d."/".$id.$nm.".".$ex;
				@unlink($fn);
				$yr=date("Y",$dt);
				$mn=date("m",$dt);
				$d=date("d",$dt);
				@unlink($thd."/".$yr."/".$mn."/".$d."/".$id.".".$ex);
			}
			if($cnt)
				@mysql_query("DELETE FROM ".db::tnm("media")." WHERE `pid`={$fid} AND `mid`={$mid} AND `uid`={$uid}");
		}
		if($nameSized)
			$fn=$dir."/".$fid.$name.$sizeDelim.$wid.$sizeDelim.$ht.".".$ext;
		else
			$fn=$dir."/".$fid.$name.".".$ext;
		@unlink($fn);
		$yr=date("Y",$mdt);
		$mn=date("m",$mdt);
		$d=date("d",$mdt);
		@unlink($thd."/".$yr."/".$mn."/".$d."/".$fid.".".$ext);
		@mysql_query("DELETE FROM ".db::tnm("media")." WHERE `id`={$fid}");
	}

	/**
	* Функция байндинга ресурса к указанному модулю
	* При успешном создании привязки возвращается набор полей (array) с обновленным ключем id.
	* При успешном обновлении привязки возвращается true.
	* При возникновении ошибки, возвращается индекс ошибки (int),
	* по которому можно получить текст ошибки (msgr::errorGet($ind,..)).
	* Если режим задан как целой число, функция расценивает его как id,
	* и переключается в режим обновления.
	*
	* @param int $bind
	*
	* @return array/bool
	*/
	private static function _bind($bind,$mode=MEDIA_WRITEMODE_INSERT)
	{
		if(!isset($bind["id"]))$bind["id"]=0;
		if(($mode==MEDIA_WRITEMODE_UPDATE) && !$bind["id"])return false;
		$prep=self::_prepare(self::$class."_binds",$bind,$mode,false);
		if($prep===false)return false;
		$bind=$prep[0];
		$prep=$prep[1];
		if($mode==MEDIA_WRITEMODE_INSERT)$q="INSERT INTO ".db::tnm(self::$class."_binds")." VALUES(NULL,".$prep.")";
		else $q="UPDATE ".db::tnm(self::$class."_binds")." SET ".$prep." WHERE `id`=".$bind["id"];
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		if(!$bind["id"])$bind["id"]=0+@mysql_insert_id();
		return $bind;
	}

	private static function _count($modId,$entity,$filters)
	{
		$fs=self::_filters($entity,$filters);
		if(($fs[0]===false) || ($fs[1]===false))return false;
		$q="SELECT COUNT(DISTINCT `m`.`id`) AS `cnt` FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tn("mods")." `md` ON `md`.`id`=`m`.`mid`
		LEFT JOIN ".db::tnm(self::$class."_binds")." `mb` ON `m`.`id`=`mb`.`mid`
		WHERE `md`.`id`=".$modId.($fs[0]?$fs[0]:"").($fs[1]?$fs[1]:"");
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		$rec=@mysql_fetch_assoc($r);
		return (0+$rec["cnt"]);
	}

	/**
	* Функция удаляет указанный ресурс и все дочерние узлы.
	* При успешном удалении возвращается массив id удаленных дочерних ресурсов.
	* При возникновении ошибки, возвращается индекс ошибки (int),
	* по которому можно получить текст ошибки (msgr::errorGet($ind,..)).
	*
	* @param mixed $id
	* @param array $item
	* @param bool $err_json
	*
	* @return array $cids
	*/
	private static function _delete($id,$item=false,$err_json=false)
	{
		if(!$item)
		{
			$item=self::_fetch($id);
			if(!is_array($item))return $item;
		}
		$q="SELECT * FROM ".db::tnm(self::$class)." WHERE `pid`=".$item["id"]." ORDER BY `uploaded` DESC LIMIT 0,".self::$config["batchMaxCount"];
		$r=db::q($q,false);
		if($r===false)
		{
			self::$lastMsg="Ошибка операции с базой данных";
			if($err_json)
			{
				$sqlerr=lib::jsonPrepare(db::lastError());
				$sqlstr=lib::jsonPrepare($q);
				$err="{error:\"".$sqlerr."\",query:\"".$sqlstr."\"}";
			}
			else
			{
				$err="Текст ошибки:<br />".db::lastError().",<br /><br /> Запрос:<br />".$q;
			}
			return msgr::errorLog($err,false,self::$class,__FUNCTION__,__LINE__);
		}
		$childs=array();
		$ids=array();
		while($rec=@mysql_fetch_assoc($r))$childs[]=$rec;
		if(!count($childs))
		{
			foreach($childs as $child)
			{
				$child["id"]=0+$child["id"];
				$child["height"]=0+$child["height"];
				$child["width"]=0+$child["width"];
				$child["name_id"]=(0+$child["name_id"])?true:false;
				$child["name_sized"]=(0+$child["name_sized"])?true:false;
				$name=array();
				if($child["name_id"])$name[]="".$child["id"];
				if($child["name"])$name[]=$child["name"];
				if($child["name_sized"])$name[]="".$child["width"]."x".$child["height"];
				$name=implode($child["size_delim"],$name).".".$child["extension"];
				@unlink($child["directory"]."/".$name);
				$ids[]=$childs["id"];
			}
			if(count($ids))
			{
				db::q("DELETE FROM ".db::tnm(self::$class."_binds")." WHERE `mid` IN (".implode(",",$ids).")",false);
				db::q("DELETE FROM ".db::tnm(self::$class)." WHERE `id` IN (".implode(",",$ids).")",false);
			}
		}
		$name=array();
		if($item["name_id"])$name[]="".$item["id"];
		if($item["name"])$name[]=$item["name"];
		if($item["name_sized"])$name[]="".$item["width"]."x".$item["height"];
		$name=implode($item["size_delim"],$name).".".$item["extension"];
		@unlink($item["directory"]."/".$name);
		db::q("DELETE FROM ".db::tnm(self::$class."_binds")." WHERE `mid`=".$id,false);
		db::q("DELETE FROM ".db::tnm(self::$class)." WHERE `id`=".$id,false);
		return $ids;
	}

	private static function _move($id,$destDir)
	{
		//!!!доработать!!!
		$q="SELECT `name_orig`,`extension`,`directory` FROM ".db::tnm(self::$class)." WHERE `id`=".$id;
		$r=@mysql_query($q);
		if($r===false)return false;
		$row=mysql_fetch_assoc($r);
		if(!$row)return false;
		$ext=$row["extension"];
		$odir=$row["directory"];
		if($dir==$odir)return true;
		if(!@file_exists($dir))
			if(!@mkdir($dir,0777,true))return false;
		$ext=($ext?(".".$ext):"");
		$src=$odir."/".$id.$ext;
		if(!@file_exists($src))
		{
			$src=$odir."/".$row["name_orig"];
			if(!@file_exists($src))return false;
		}
		$dest=$dir."/".$id.$ext;
		if(!@copy($src,$dest))return false;
		@chmod($dest,0777);
		@unlink($src);
		$q="UPDATE ".db::tnm(self::$class)." SET `directory`='".mysql_real_escape_string($dir)."' WHERE `id`=".$id;
		@mysql_query($q);
		if($ext && in_array(ltrim($ext,"."),self::$types[MEDIA_TYPE_IMG]))
		{
			$q="SELECT `width`,`height` FROM ".db::tnm(self::$class."_isizes")." WHERE `fid`=".$id;
			$r=@mysql_query($q);
			if($r===false)return true;
			while($row=mysql_fetch_array(($r)))
			{
				$w=0+$row["width"];
				$h=0+$row["height"];
				if(!$w || !$h)continue;
				$src=$odir."/".$id."_".$w."_".$h.$ext;
				$dest=$dir."/".$id."_".$w."_".$h.$ext;
				@copy($src,$dest);
				@chmod($dest,0777);
				@unlink($src);
			}
		}
		return true;
	}

	/**
	* Функция получает данные по ресурсу.
	* При успехе возвращается набор полей (array) с данными.
	* При возникновении ошибки, возвращается false
	*
	* @param int $id
	* @param bool $childs
	*
	* @return array/bool
	*/
	private static function _fetch($id,$childs)
	{
		$q="SELECT `m`.`id`,`m`.`pid`,`m`.`mid` AS `modId`,`m`.`width`,`m`.`height`,`m`.`bytes`,`m`.`uploaded`,
		`m`.`type`,`m`.`name_id`,`m`.`name_sized`,`m`.`size_delim`,`m`.`extension`,`m`.`name`,
		`m`.`name_orig`,`m`.`directory`,`m`.`title`,`m`.`credit`,`m`.`content_type`,
		`mb`.`id` AS `bid`,`mb`.`oid`,`mb`.`par1`,`mb`.`par2`,`mb`.`par3`,`mb`.`res`
		FROM ".db::tnm(self::$class)." `m`
		LEFT JOIN ".db::tnm(self::$class."_binds")." `mb` ON `m`.`id`=`mb`.`mid`
		WHERE `m`.`id`=".$id;
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		$item=array();
		$c=0;
		while($rec=@mysql_fetch_assoc($r))
		{
			if(!$c)
			{
				$item=self::_recFormat($rec);
				if($childs)
				{
					$item["childs"]=self::_fetchArray(0+$rec["modId"],0,array("pid"=>$item["id"]),false,false);
					if($item["childs"]===false)return false;
				}
				$item["binds"]=array();
			}
			$bind=array();
			if(isset($rec["bid"]) && ((0+$rec["bid"])>0))
			{
				$bind["id"]=0+$rec["bid"];
				$bind["oid"]=0+$rec["oid"];
				$bind["par1"]=$rec["par1"];
				$bind["par2"]=$rec["par2"];
				$bind["par3"]=$rec["par3"];
				$bind["res"]=$rec["res"];
			}
			if(count($bind))$item["binds"][]=$bind;
		}
		return $item;
	}

	private static function _fetchArray($modId,$entity,$filters,$range,$childs)
	{
		$fs=self::_filters($entity,$filters);
		if(($fs[0]===false) || ($fs[1]===false))return false;
		$q="SELECT `m`.`id`,`m`.`pid`,`m`.`width`,`m`.`height`,`m`.`bytes`,`m`.`uploaded`,
		`m`.`type`,`m`.`name_id`,`m`.`name_sized`,`m`.`size_delim`,`m`.`extension`,`m`.`name`,
		`m`.`name_orig`,`m`.`directory`,`m`.`title`,`m`.`credit`,`m`.`content_type`,
		`mb`.`id` AS `bid`,`mb`.`oid`,`mb`.`par1`,`mb`.`par2`,`mb`.`par3`,`mb`.`res`
		FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tn("mods")." `md` ON `md`.`id`=`m`.`mid`
		LEFT JOIN ".db::tnm(self::$class."_binds")." `mb` ON `m`.`id`=`mb`.`mid`
		WHERE `md`.`id`=".$modId.($fs[0]?$fs[0]:"").($fs[1]?$fs[1]:"")." ORDER BY `m`.`uploaded` DESC";
		if(is_array($range))$q.=" LIMIT ".$range[0].",".$range[1];
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		$recs=array();
		$ids=array();
		while($rec=@mysql_fetch_assoc($r))
		{
			$bind=array();
			if(($entity!==false) && isset($rec["bid"]) && ((0+$rec["bid"])>0))
			{
				$bind["id"]=0+$rec["bid"];
				$bind["oid"]=0+$rec["oid"];
				$bind["par1"]=$rec["par1"];
				$bind["par2"]=$rec["par2"];
				$bind["par3"]=$rec["par3"];
				$bind["res"]=$rec["res"];
			}
			$rec["id"]=0+$rec["id"];
			if(($entity===false) || !isset($ind[$rec["id"]]))
			{
				$rec=self::_recFormat($rec);
				$rec["binds"]=array();
				if($childs)
				{
					$rec["childs"]=self::_fetch($modId,$entity,array("pid"=>$rec["id"]),false,false);
					if($rec["childs"]===false)return false;
				}
				$recs[]=$rec;
				$ind[$rec["id"]]=count($recs)-1;
			}
			if(count($bind))$recs[$ind[$rec["id"]]]["binds"][]=$bind;
		}
		return $recs;
	}

	/**
	* Функция создает условия для sql-запроса
	*
	* @param int $entity - id дочерней сущности объекта
	* @param array $filters - набор фильтров
	*
	* @return array $res
	*/
	private static function _filters($entity,$filters)
	{
		if(is_array($filters) && count($filters))
		{
			$t=db::tMeta(self::$class);
			$tb=db::tMeta(self::$class."_binds");
			$fs=array_keys($t);
			$fsb=array_keys($tb);
			$fts=array();
			foreach($filters as $key=>$filter)
			{
				if(is_string($key) && in_array($key,$fs))
				{
					if(($t[$key]["type"]=="string") || ($t[$key]["type"]=="text"))
					{
						$filter="".$filter;
						$type="string";
					}
					else $type="other";
					$fts[]=array($type,$filter,"AND",$key,"=");
				}
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
			if(count($fts))$fts=db::filtersMake($fts,true,true,true,"m");
			else $fts="";
			if(!$entity!=false)
			{
				$ftsb=array();
				foreach($filters as $filter)
				{
					if(is_array($filter))
					{
						if(isset($filter[0]) && (in_array($filter[0],$fsb)) && isset($filter[1]) && isset($filter[2]))
						{
							if(($tb[$filter[0]]["type"]=="string") || ($tb[$filter[0]]["type"]=="text"))
							{
								$filter[2]="".$filter[2];
								$type="string";
							}
							else $type="other";
							$ftsb[]=array($type,$filter[2],"AND",$filter[0],$filter[1]);
						}
					}
				}
				if(count($ftsb))$ftsb=db::filtersMake($ftsb,true,true,true,"mb");
				else $ftsb="";
			}
			else $ftsb="";
		}
		else
		{
			$fts="";
			$ftsb="";
		}
		return array($fts,$ftsb);
	}

	private static function _imgExt2FDef($ext)
	{
		$ext=strtolower($ext);
		switch($ext)
		{
			case "gif": return MEDIA_IMG_GIF;
			case "jpg":
			case "jpeg": return MEDIA_IMG_JPG;
			case "png": return MEDIA_IMG_PNG;
			case "swf": return MEDIA_IMG_SWF;
			case "psd": return MEDIA_IMG_PSD;
			case "bmp": return MEDIA_IMG_BMP;
			case "tiff": return MEDIA_IMG_TIFFI;
			case "tiff": return MEDIA_IMG_TIFFM;
			case "jpc": return MEDIA_IMG_JPC;
			case "jp2": return MEDIA_IMG_JP2;
			case "jpx": return MEDIA_IMG_JPX;
			case "jb2": return MEDIA_IMG_JB2;
			case "swc": return MEDIA_IMG_SWC;
			case "iff": return MEDIA_IMG_IFF;
			case "wbmp": return MEDIA_IMG_WBMP;
			case "xbm": return MEDIA_IMG_XBM;
			default: return 0;
		}
	}

	private static function _imgExt2Type($ext)
	{
		$ext=strtolower($ext);
		$cnt=-1;
		foreach(self::$types as $vals)
		{
			$cnt++;
			if(in_array($ext,$vals))return $cnt;
		}
		return 0;
	}

	private static function _imgFDef2Ext($type)
	{
		switch($type)
		{
			case MEDIA_IMG_GIF: return "gif";
			case MEDIA_IMG_JPG: return "jpg";
			case MEDIA_IMG_PNG: return "png";
			case MEDIA_IMG_SWF: return "swf";
			case MEDIA_IMG_PSD: return "psd";
			case MEDIA_IMG_BMP: return "bmp";
			case MEDIA_IMG_TIFFI: return "tiff";
			case MEDIA_IMG_TIFFM: return "tiff";
			case MEDIA_IMG_JPC: return "jpc";
			case MEDIA_IMG_JP2: return "jp2";
			case MEDIA_IMG_JPX: return "jpx";
			case MEDIA_IMG_JB2: return "jb2";
			case MEDIA_IMG_SWC: return "swc";
			case MEDIA_IMG_IFF: return "iff";
			case MEDIA_IMG_WBMP: return "wbmp";
			case MEDIA_IMG_XBM: return "xbm";
			default: return "image";
		}
	}

	/**
	* Функция подготавливает набор данных для вставки через функцию self::_register
	* При возникновении ошибки, возвращается индекс ошибки (msgr::errorGet($ind,..)),
	* или -1 (если нет расширенного описания ошибки)
	*
	* @param string $table
	* @param array $item
	* @param int $mode
	* @param bool $fill
	*
	* @return array/bool
	*/
	private static function _prepare($table,$item,$mode,$fill=false)
	{
		$t=db::tMeta($table);
		$res=array();
		$notset=array();
		foreach($t as $field=>$meta)
		{
			$val=NULL;
			if($field=="id")
			{
				$item["id"]=0+$item["id"];
				continue;
			}
			if(isset($item[$field]))$val=$item[$field];
			if(is_null($val))
			{
				if($mode==MEDIA_WRITEMODE_UPDATE)continue;
				$notset[]=$field;
			}
			switch($meta["type"])
			{
				case "integer":
				case "bit":
					if(is_null($val))$val=0;
					else $val=0+$val;
					$item[$field]=$val;
					break;
				case "float":
					if(is_null($val))$val=0.0;
					else $val=0.0+$val;
					$item[$field]=$val;
					break;
				case "date":
					if(is_int($val))$val=date("Y-m-d H:i:s",$val);
					if(!is_string($val))$val=date("Y-m-d H:i:s",time());
					$item[$field]=$val;
					$val="'".mysql_real_escape_string($val)."'";
					break;
				case "string":
				case "text":
				case "blob":
					if(is_null($val))$val="";
					else
					{
						$len=mb_strlen($val,"UTF-8");
						if($meta["maxLen"] && ($len>$meta["maxLen"]))
						{
							$msg="Поле [{$field}] превышает допустимую длину [".$meta["maxLen"]."]";
							if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
							else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
							return false;
						}
					}
					$item[$field]=$val;
					$val="'".mysql_real_escape_string($val)."'";
					break;
				default:
					if(is_null($val))$val="";
					else $val="".$val;
					$item[$field]=$val;
					$val="'".mysql_real_escape_string($val)."'";
			}
			$res[$field]=$val;
		}
		if(($mode==MEDIA_WRITEMODE_INSERT) && $fill)
		{
			switch($table)
			{
				case self::$class:
					foreach($notset as $field)
					switch($field)
					{
						case "uid":
							$item[$field]=self::$uid;
							$res[$field]="".self::$uid;
							break;
						case "name_id":
						case "name_sized":
							$item[$field]=1;
							$res[$field]="1";
							break;
						case "size_delim":
							$item[$field]=self::$config["sizeDelimiter"];
							$res[$field]="'".$item[$field]."'";
							break;
					}
					break;
			}

		}
		if($mode==MEDIA_WRITEMODE_UPDATE)
		{
			$resu=array();
			foreach($res as $field=>$val)$resu[]="`{$field}`=".$val;
			return array($item,implode(",",$resu));
		}
		return array($item,implode(",",$res));
	}

	private static function _recFormat($rec)
	{
		$rec["id"]=0+$rec["id"];
		$rec["pid"]=0+$rec["pid"];
		$rec["width"]=0+$rec["width"];
		$rec["height"]=0+$rec["height"];
		$rec["bytes"]=0+$rec["bytes"];
		$rec["type"]=0+$rec["type"];
		$rec["name_id"]=0+$rec["name_id"];
		$rec["name_sized"]=0+$rec["name_sized"];
		if(lib::mquotes_runtime())
		{
			$rec["title"]=stripslashes($rec["title"]);
			$rec["credit"]=stripslashes($rec["credit"]);
		}
		$rec["filename"]=($rec["name"]?(($rec["name_id"]?($rec["id"].$rec["size_delim"]):"").$rec["name"]):$rec["id"]).(($rec["type"]==MEDIA_TYPE_IMG)?($rec["name_sized"]?($rec["size_delim"].$rec["width"]."x".$rec["height"]):""):"").".".$rec["extension"];
		$rec["file_path"]=$rec["directory"]."/".$rec["filename"];
		/*
		if(!$rec["bytes"])
		{
			$fs=false;
			if(@file_exists($file))$fs=@filesize($rec["file_path"]);
			if($fs)db::q("UPDATE ".db::tnm(self::$class)." SET `bytes`=".$fs." WHERE `id`=".$rec["id"],false);
		}
		*/
		$rec["url"]=FLEX_APP_DIR_ROOT.$rec["file_path"];
		$rec["url_thumb"]=self::_thumb($rec,self::$config["uploaderThumbExt"]);
		return $rec;
	}

	/**
	* Изменение размера изображения
	*
	* @param string $srcFile
	* @param string/false $dstFile //"" - сохранить в ту же папку, false - вывести в браузер
	* @param int $forceType // MEDIA_IRES_CROP,MEDIA_IRES_FITHOR,MEDIA_IRES_FITVER,MEDIA_IRES_FITRAT,MEDIA_IRES_MAKEGAL
	* @param array $params // напр. array("dstRect"=>array(0,0,100,100))
	* 		"addSizes"		=>	true,					//добавить размеры
	*		"sizeDelim"	=>	"-",					//разделитель размеров
	*		"dstRect"		=>	array(),				//размеры зоны-приемника (def=[размер исходного изображения])
	*		"dstShift"		=>	array(),				//смещение зоны dstRect, если dstSize больше dstRect (def=[по-центру,по-центру]/[-1, -1])
	*		"dstSize"		=>	array(),				//размеры конечного холста (def=[dstRect])
	*		"dstType"		=>	0,
	*		"dstValue"		=>	1,						//ширина/высота или ratio (не используется в режиме ..._CROP)
	*		"srcRect"		=>	array(),				//размеры зоны-источника (def=[размер исходного изображения])
	*		"srcShift"		=>	array(),				//смещение зоны srcRect, если srcSize больше srcRect(def=[по-центру,по-центру]/[-1, -1])
	*		"srcSize"		=>	array($size[0],$size[2]),//размеры исходного файла
	*		"srcType"		=>	$size[2],				//формат исходного файла
	*		"tryTypeDef"	=>	true					//пробовать сохранить в другом формате, если запрошенный не поддерживается в GD
	*
	* @return bool/stream
	*/
	private static function _resample($srcFile,$dstFile,$forceType,$params)
	{
		if(!@extension_loaded("gd"))
		{
			$msg="Библикотека GD не установлена.";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		//проверка источника
		if(!@is_readable($srcFile) || !@is_file($srcFile))
		{
			$msg="Невозможно прочитать файл [".$srcFile."].";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$size=@getimagesize($srcFile);
		if(!$size)
		{
			$msg="Указанный файл имеет недопустимый формат [".$srcFile."].";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		//size[0] - ширина
		//size[1] - высота
		//size[2] - формат, значения:
		//		1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF,
		//		5 = PSD, 6 = BMP, 7 = TIFF(intel byte order)
		//		8 = TIFF(motorola byte order), 9 = JPC,
		//		10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC
		//		14 = IFF, 15 = WBMP, 16 = XBM.
		if(!in_array($size[2],self::$imgSup))
		{
			$msg="Формат файла неподдерживается [".$srcFile."].";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		if($dstFile)
		{
			if(@file_exists($dstFile))
				if(!@is_writeable($dstFile))
				{
					$msg="Конечный файл недоступен для перезаписи [".$srcFile."].";
					self::$lastMsg=$msg;
					msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
					return false;
				}
		}
		if($forceType<MEDIA_IRES_CROP || ($forceType>MEDIA_IRES_MAKEGAL))
		{
			$msg="Неверный тип обработки [forceType:".$forceType."].";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		//массив умолчаний
		$paramsDef=array(
			"addSizes"		=>	true,							//добавить размеры
			"sizeDelim"		=>	self::$config["sizeDelimiter"],	//разделитель размеров
			"dstRect"		=>	array(),						//размеры зоны-приемника (def=[размер исходного изображения])
			"dstShift"		=>	array(),						//смещение зоны dstRect, если dstSize больше dstRect (def=[по-центру,по-центру]/[-1, -1])
			"dstSize"		=>	array(),						//размеры конечного холста (def=[dstRect])
			"dstType"		=>	0,								//тип конечного файла
			"dstValue"		=>	1,								//ширина/высота или ratio (не используется в режиме ..._CROP)
			"srcRect"		=>	array(),						//размеры зоны-источника (def=[размер исходного изображения])
			"srcShift"		=>	array(),						//смещение зоны srcRect, если srcSize больше srcRect(def=[по-центру,по-центру]/[-1, -1])
			"srcSize"		=>	array($size[0],$size[2]),		//размеры исходного файла
			"srcType"		=>	$size[2],						//формат исходного файла
			"tryTypeDef"	=>	false							//пробовать сохранить в другом формате, если запрошенный не поддерживается в GD
		);
		$p=array();
		foreach($paramsDef as $key=>$val)
		{
			if(array_key_exists($key,$params))
			{
				if($key=="dstType")
				{
					if(is_string($params["dstType"]))
					{
						$p["dstType"]=self::_imgExt2Type($params["dstType"]);
						continue;
					}
				}
				if((gettype($val)==gettype($params[$key])) || ((gettype($val)=="boolean") && (is_int($params[$key]))))$p[$key]=$params[$key];
				else $p[$key]=$val;
			}
			else $p[$key]=$val;
		}
		//-------------проверяем умолчания--------------
		//если не задана зона-источник, выбираем всю канву изначального файла
		if(!count($p["srcRect"]))
			$p["srcRect"]=array(0,0,$size[0],$size[1]);
		else
		{
			$msg="Некорректный параметр [srcRect]";
			//проверяем на корректность srcRect
			if($p["srcRect"][2]<=0)
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if($p["srcRect"][3]<=0)
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if(($p["srcRect"][2]+$p["srcRect"][0])>8000)
			{
				$msg.=": размер исходного фрейма превышает 8000 пикселей";
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if(($p["srcRect"][3]+$p["srcRect"][1])>8000)
			{
				$msg.=": размер исходного фрейма превышает 8000 пикселей";
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
		}
		//если не заданы смещения зоны-источника, выбираем "по-центру"
		if(!count($p["srcShift"]))
			$p["srcShift"]=array(-1, -1);
		else
		{
			$msg="Некорректный параметр [srcShift]";
			if($p["srcShift"][0]>100 || ($p["srcShift"][0]<0 && ($p["srcShift"][0]!=-1)))
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if($p["srcShift"][1]>100 || ($p["srcShift"][1]<0 && ($p["srcShift"][1]!=-1)))
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
		}
		//...на всякий случай
		$p["srcSize"]=array($size[0],$size[1]);
		//если не задана зона-приемник, выбираем размеры как у изначального файла
		if(!count($p["dstRect"]))$p["dstRect"]=array(0,0,$size[0],$size[1]);
		else
		{
			if(count($p["dstRect"])==2)array_unshift($p["dstRect"],0,0);
			$msg="Некорректный параметр [dstRect]";
			//проверяем на корректность srcRect
			if($p["dstRect"][2]<=0)
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if($p["dstRect"][3]<=0)
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if(($p["dstRect"][2]+$p["dstRect"][0])>8000)
			{
				$msg.=": размер результатирующего фрейма превышает 8000 пикселей";
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if(($p["dstRect"][3]+$p["dstRect"][1])>8000)
			{
				$msg.=": размер результатирующего фрейма превышает 8000 пикселей";
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
		}
		//если не заданы смещения зоны-приемника, выбираем "по-центру"
		if(!count($p["dstShift"]))$p["dstShift"]=array(-1, -1);
		else
		{
			$msg="Некорректный параметр [dstShift]";
			if($p["dstShift"][0]>100 || ($p["dstShift"][0]<0 && ($p["dstShift"][0]!=-1)))
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if($p["dstShift"][1]>100 || ($p["dstShift"][1]<0 && ($p["dstShift"][1]!=-1)))
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
		}
		//если не заданы размеры конечного файла, устанавливаем размеры зоны-приемника
		if(!count($p["dstSize"]))$p["dstSize"]=array($p["dstRect"][2],$p["dstRect"][3]);
		else
		{
			$msg="Некорректный параметр [dstRect]";
			if(($p["dstRect"][0]<0) || ($p["dstRect"][1]<0))
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			if((($p["dstRect"][0]+$p["dstRect"][2])>$p["dstSize"][0]) || (($p["dstRect"][1]+$p["dstRect"][3])>$p["dstSize"][1]))
			{
				self::$lastMsg=$msg;
				msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
		}
		//если не задан конечный формат файла, устанавливаем формат исходного файла
		if(!$p["dstType"])$p["dstType"]=$p["srcType"];
		else
		{
			$dtype=0+$p["dstType"];
			if(!in_array($dtype,self::$imgSup))
			{
				$try=false;
				if($p["tryTypeDef"])
				{
					if(is_int($p["tryTypeDef"]))
					{
						if(in_array($p["tryTypeDef"],self::$imgSup))$p["dstType"]=$p["tryTypeDef"];
						else $p["dstType"]=self::$config["imgTypeDefault"];
					}
					else $p["dstType"]=self::$config["imgTypeDefault"];
				}
				else
				{
					$msg="Указанный тип результатирующего изображения не поддерживается";
					self::$lastMsg=$msg;
					msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
					return false;
				}
			}
			else $p["dstType"]=$dtype;
		}
		$srcImg=false;
		//--------------проход по режимам----------------
		switch($forceType)
		{
			//обрезка с масштабированием
			case MEDIA_IRES_CROP:
				//подготавливаем канву источника srcSize,
				//так как она может быть меньше, чем задана в параметрах srcRect,
				//в этом случае канву нужно будет увеличить(подогнать) к srcRect
				$newX=0;
				$newY=0;
				$newW=$p["srcSize"][0];
				$newH=$p["srcSize"][1];
				if($p["srcRect"][0]<0)
				{
					$newW=$newW-$p["srcRect"][0];
					$newX=(-1)*$p["srcRect"][0];
				}
				if(($p["srcRect"][0]+$p["srcRect"][2])>$p["srcSize"][0])
					$newW=$newW+($p["srcRect"][0]+$p["srcRect"][2]-$p["srcSize"][0]);
				if($p["srcRect"][1]<0)
				{
					$newH=$newH-$p["srcRect"][1];
					$newY=(-1)*$p["srcRect"][1];
				}
				if(($p["srcRect"][1]+$p["srcRect"][3])>$p["srcSize"][1])
					$newH=$newH+($p["srcRect"][1]+$p["srcRect"][3]-$p["srcSize"][1]);
				if($newW>$p["srcSize"][0] || $newH>$p["srcSize"][1])
				{
					//увеличиваем канву источника по максимальному соотношению
					//с выравниванием по левому верхнему углу
					switch($p["srcType"])
					{
						case 1://gif
							$srcImg=@imagecreatefromgif($srcFile);
							break;
						case 2://jpg
							$srcImg=@imagecreatefromjpeg($srcFile);
							break;
						case 3://png
							$srcImg=@imagecreatefrompng($srcFile);
							imagealphablending($srcImg,false);
							@imagesavealpha($srcImg,true);
							break;
						case 15://wbmp
							$srcImg=@imagecreatefromwbmp($srcFile);
							break;
					}
					if($srcImg===false)
					{
						$msg="Неизвестная ошибка: буферный объект не создан [srcImg]";
						self::$lastMsg=$msg;
						if($err_json)
							$msg="{msg:\"".lib::jsonPrepare($msg)."\",function:\"".__FUNCTION__."\",line:".__LINE__."}";
						return msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
					}
					$srcImg1=@imagecreatetruecolor($newW,$newH);
					if($srcImg1===false)
					{
						@imagedestroy($srcImg);
						$msg="Неизвестная ошибка: буферный объект не создан [srcImg1]";
						self::$lastMsg=$msg;
						msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
						return false;
					}
					//imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h)
					@imagealphablending($srcImg1,false);
					@imagesavealpha($srcImg1,true);
					if(!@imagecopyresampled($srcImg1,$srcImg,$newX,$newY,0,0,$p["srcSize"][0],$p["srcSize"][1],$p["srcSize"][0],$p["srcSize"][1]))
					{
						@imagedestroy($srcImg);
						@imagedestroy($srcImg1);
						$msg="Неизвестная ошибка: обработка не выполнена [imagecopyresampled].";
						self::$lastMsg=$msg;
						msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
						return false;
					}
					@imagedestroy($srcImg);
					$srcImg=$srcImg1;
					$p["srcSize"][0]=$newW;
					$p["srcSize"][1]=$newH;
				}
				//находим параметры srcX, srcY, srcW, srcH в пределах srcRect,
				//которые будут пропорциональны dstRect,
				$ratioW=$p["srcRect"][2]/($p["dstRect"][0]+$p["dstRect"][2]);
				$ratioH=$p["srcRect"][3]/($p["dstRect"][1]+$p["dstRect"][3]);
				$ratio=($ratioW<$ratioH?$ratioW:$ratioH);
				$srcX=$p["srcRect"][0];
				$srcY=$p["srcRect"][1];
				$srcW=intval(round($p["dstRect"][2]*$ratio));
				$srcH=intval(round($p["dstRect"][3]*$ratio));
				//корректируем параметры srcX, srcY,
				//если srcW>$p["srcRect"][2]
				//или srcH>$p["srcRect"][3]
				if(($srcW<$p["srcRect"][2]) && ($p["srcShift"][0]!=0))
				{
					$move=$p["srcRect"][2]-$srcW;
					if($p["srcShift"][0]==-1)$shift=50;
					else $shift=$p["srcShift"][0];
					$srcX=$srcX+intval(round($move*$shift/100));
				}
				if($srcH<($p["srcRect"][3]) && ($p["srcShift"][1]!=0))
				{
					$move=$p["srcRect"][3]-$srcH;
					if($p["srcShift"][1]==-1)$shift=50;
					else $shift=$p["srcShift"][1];
					$srcY=$srcY+intval(round($move*$shift/100));
				}
				$dstX=$p["dstRect"][0];
				$dstY=$p["dstRect"][1];
				$dstW=$p["dstRect"][2];
				$dstH=$p["dstRect"][3];
				if(($dstW<$p["dstSize"][0]) && ($p["dstShift"][0]!=0))
				{
					$move=$p["dstSize"][0]-$dstW;
					if($p["dstShift"][0]==-1)$shift=50;
					else $shift=$p["srcShift"][0];
					$dstX=$dstX+intval(round($move*$shift/100));
				}
				if(($dstH<$p["dstSize"][1]) && ($p["dstShift"][1]!=0))
				{
					$move=$p["dstSize"][1]-$dstH;
					if($p["dstShift"][1]==-1)$shift=50;
					else $shift=$p["srcShift"][1];
					$dstY=$dstY+intval(round($move*$shift/100));
				}
				break;
			//по ширине
			case MEDIA_IRES_FITHOR:
				$srcX=0;
				$srcY=0;
				$srcW=$p["srcSize"][0];
				$srcH=$p["srcSize"][1];
				$dstX=0;
				$dstY=0;
				$dstW=$p["dstValue"];
				$dstH=intval(round($p["srcSize"][1]*($dstW/$p["srcSize"][0])));
				$p["dstSize"][0]=$dstW;
				$p["dstSize"][1]=$dstH;
				break;
			//по ширине
			case MEDIA_IRES_FITVER:
				$srcX=0;
				$srcY=0;
				$srcW=$p["srcSize"][0];
				$srcH=$p["srcSize"][1];
				$dstX=0;
				$dstY=0;
				$dstH=$p["dstValue"];
				$dstW=intval(round($p["srcSize"][0]*($dsH/$p["srcSize"][1])));
				$p["dstSize"][0]=$dstW;
				$p["dstSize"][1]=$dstH;
				break;
			case MEDIA_IRES_FITRAT:
				$srcX=0;
				$srcY=0;
				$srcW=$p["srcSize"][0];
				$srcH=$p["srcSize"][1];
				$dstX=0;
				$dstY=0;
				$dstW=intval(round($p["srcSize"][0]*$p["dstValue"]/100));
				$dstH=intval(round($p["srcSize"][1]*$p["dstValue"]/100));
				$p["dstSize"][0]=$dstW;
				$p["dstSize"][1]=$dstH;
				break;
			//уменьшение изображений для галлереи
			case MEDIA_IRES_MAKEGAL:
				$srcX=0;
				$srcY=0;
				$srcW=$p["srcSize"][0];
				$srcH=$p["srcSize"][1];
				$dstX=0;
				$dstY=0;
				if($p["srcSize"][0]>$p["srcSize"][1])
				{
					$dstW=$p["dstValue"];
					$dstH=intval(round($p["srcSize"][1]*($dstW/$p["srcSize"][0])));
				}
				else
				{
					$dstH=$p["dstValue"];
					$dstW=intval(round($p["srcSize"][0]*($dstH/$p["srcSize"][1])));
				}
				$p["dstSize"][0]=$dstW;
				$p["dstSize"][1]=$dstH;
				break;
		}
		$ext=self::_imgFDef2Ext($p["dstType"]);
		if(($dstFile=="") || ($dstFile=="/"))
		{
			$pi=pathinfo($srcFile);
			if($p["addSizes"])
				$dstFile=$pi["dirname"]."/".$pi["filename"].$p["sizeDelim"].$p["dstSize"][0]."x".$p["dstSize"][1].".".$ext;
			else
				$dstFile=$pi["dirname"]."/".$pi["filename"].$p["sizeDelim"]."resampled.".$ext;
		}
		else
		{
			$filename=true;
			if(substr($dstFile,strlen($dstFile)-1,1)=="/")
			{
				$dstFile.="somefile";
				$filename=false;
			}
			$pi=pathinfo($dstFile);
			if(!$filename)
			{
				$filename=md5(time().mt_rand(111,999));
			}
			else
			{
				$filename=$pi["filename"];
				if(!self::_imgExt2Type($pi["extension"]))$filename.=$pi["extension"];
				//добавляем размеры, или заменяем размеры,
				//если в имени файла присутствуют дефолтовые
				if($p["addSizes"])
				{
					$found=false;
					$parts=explode($p["sizeDelim"],$filename);
					if(count($parts))
					{
						$sz=explode("x",$parts[count($parts)-1]);
						if(count($sz)==2)
						{
							if(((0+$sz[0])>0) && ((0+$sz[1])>0))
							{
								$found=true;
								$filename=str_replace(implode("x",$sz),$p["dstSize"][0]."x".$p["dstSize"][1],$filename);
							}
						}
					}
					if(!$found)
						$filename.=$p["sizeDelim"].$p["dstSize"][0]."x".$p["dstSize"][1];
				}
			}
			$dstFile=$pi["dirname"]."/".$filename.".".$ext;
		}
		if(!$srcImg)
		{
			switch($p["srcType"])
			{
				case 1://gif
					$srcImg=@imagecreatefromgif($srcFile);
					break;
				case 2://jpg
					$srcImg=@imagecreatefromjpeg($srcFile);
					break;
				case 3://png
					$srcImg=@imagecreatefrompng($srcFile);
					@imagealphablending($srcImg,false);
					@imagesavealpha($srcImg,true);
					break;
				case 15://wbmp
					$srcImg=@imagecreatefromwbmp($srcFile);
					break;
			}
		}
		if($srcImg===false)
		{
			$msg="Неизвестная ошибка: буферный объект не создан [srcImg]";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$dstImg=@imagecreatetruecolor($p["dstSize"][0],$p["dstSize"][1]);
		if($dstImg===false)
		{
			@imagedestroy($srcImg);
			$msg="Неизвестная ошибка: буферный объект не создан [dstImg]";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		//imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h)
		if($p["dstType"]==3)@imagealphablending($dstImg,false);
		@imagesavealpha($dstImg,true);
		if(!@imagecopyresampled($dstImg,$srcImg,$dstX,$dstY,$srcX,$srcY,$dstW,$dstH,$srcW,$srcH))
		{
			@imagedestroy($dstImg);
			@imagedestroy($srcImg);
			$msg="Неизвестная ошибка: обработка не выполнена [imagecopyresampled]";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		@imagedestroy($srcImg);
		$res=false;
		if($dstFile===false)
		{
			switch($p["dstType"])
			{
				case 1://gif
					$res=@imagegif($dstImg);
					break;
				case 2://jpg
					$res=@imagejpeg($dstImg,"",100);
					break;
				case 3://png
					$res=@imagepng($dstImg,"",0);
					break;
				case 15://wbmp
					$res=@imagewbmp($dstImg);
					break;
			}
		}
		else
		{
			switch($p["dstType"])
			{
				case 1://gif
					$res=@imagegif($dstImg,$dstFile);
					break;
				case 2://jpg
					$res=@imagejpeg($dstImg,$dstFile,100);
					break;
				case 3://png
					$res=@imagepng($dstImg,$dstFile,0);
					break;
				case 15://wbmp
					$res=@imagewbmp($dstImg,$dstFile);
					break;
			}
		}
		@imagedestroy($dstImg);
		if(!$res)
		{
			$msg="Неизвестная ошибка: обработка не выполнена [imagesave]";
			self::$lastMsg=$msg;
			msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		else
		{
			if($dstFile)@chmod($dstFile,0755);
		}
		if($dstFile===false)return $res;
		else return $dstFile;
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
		if(isset(self::$session["files"]) && is_array(self::$session["files"]))
		{
			//если через POST пришли новые файлы,
			//то объединяем их со списком ранее загруженных файлов
			if(count(self::$files))
			{
				//перебираем все файлы сессии и проверяем их на предмет
				//совпадения id с новыми файлами
				foreach(self::$session["files"] as $id=>&$file)
				{
					//если в сессии уже имеется файл с таким же id,
					//который присутствует в новых POST-данных,
					//то удаляем его из сессии и с диска и заменяем новым файлом
					if(isset(self::$files[$id]))
					{
						$toDel=array();
						if(isset($file[$id]["tmp_name"]))$toDel[]=$file[$id];
						else $toDel=$file[$id];
						foreach($toDel as $del)@unlink($del["tmp_name"]);
						self::$session["files"][$id]=&self::$files[$id];
					}
					else self::$files[$id]=&$file;
				}
			}
			else self::$files=&self::$session["files"];
		}
	}

	/**
	* Запись данных в сессию
	*/
	private static function _sessionWrite()
	{
		//убеждаемся, что сессия не была повреждена во время выполнения
		if(!is_array(self::$session))self::$session=array();
		//записываем данные
		$now=time();
		foreach(self::$files as $id=>&$file)
		{
			//если id указывает не на один файл, а на список файлов,
			//то проверяем этот список
			if(!isset($file["tmp_name"]) && isset($file[0]))
			{
				foreach($file as $idx=>$file1)
				{
					if(!isset($file1["moved"]) || ($file1["moved"]===false))
					{
						if($now-$file1["time"]>self::$config["maxFileAge"])@unlink($file1["tmp_name"]);
						unset(self::$files[$id][$idx]);
					}
				}
				//если после проверки все файлы из списка билы удаленыя,
				//то удаляем весь список
				if(!count(self::$files[$id]))unset(self::$files[$id]);
			}
			//проверяем список загруженных файлов
			//и удаляем "просроченные"
			if(!isset($file["moved"]) || ($file["moved"]===false))
			{
				if($now-$file["time"]>self::$config["maxFileAge"])@unlink($file["tmp_name"]);
				unset(self::$files[$id]);
			}
		}
		self::$session["files"]=&self::$files;
		//пакуем сессию
		$_SESSION[FLEX_APP_NAME."-".self::$class."-data"]=serialize(self::$session);
	}

	private static function _thumb($item,$type,$size=false,$src=false,$dest=false)
	{
		if($dest)$thd=$dest;
		else $thd=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/".self::$config["uploaderThumbDir"];
		if(!@file_exists($thd))
		{
			@mkdir($thd,0755,true);
			if(!@file_exists($thd))
			{
				$msg="Директория миниатюр изображений недоступна для записи!";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__,"Media thumbs directory is not writeable: [".$thd."]");
				return false;
			}
		}
		else
		{
			if(!@is_dir($thd))
			{
				$msg="Директория миниатюр изображений недоступна (имя занято файловым объектом)!";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__,"Media thumbs directory name is already taken by file-object: [".$thd."]");
				return false;
			}
		}
		$dt=strtotime($item["uploaded"]);
		$yr=date("Y",$dt);
		$mn=date("m",$dt);
		$thd=$thd."/".$yr."/".$mn;
		if(!@file_exists($thd))
		{
			@mkdir($thd,0755,true);
			if(!@file_exists($thd))$thd="";
		}
		if($thd)$thf=$thd."/".$item["id"].".".$type;
		else return false;
		if($src===false)return(FLEX_APP_DIR_ROOT.$thf);
		$src.="";
		if($src)
		{
			if($size===false)$size=self::$config["uploaderThumbSize"];
			self::_resample($src,$thf,MEDIA_IRES_CROP,array("addSizes"=>false,"dstRect"=>array($size[0],$size[1]),"dstType"=>$type),true);
		}
		return(FLEX_APP_DIR_ROOT.$thf);
	}

	/**
	* Функция записи данных по файлу в БД
	*
	* @param array $item
	* @param array $bind
	* @param int $mode
	*
	* @return array/bool
	*/
	private static function _write($item,$bind=false,$mode=MEDIA_WRITEMODE_INSERT)
	{
		$prep=self::_prepare(self::$class,$item,$mode,true);
		if($prep===false)return false;
		$item=$prep[0];
		$prep=$prep[1];
		//пишем в БД
		if($mode==MEDIA_WRITEMODE_INSERT)$q="INSERT INTO ".db::tnm(self::$class)." VALUES (NULL,".$prep.")";
		else $q="UPDATE ".db::tnm(self::$class)." SET ".$prep." WHERE `id`=".$item["id"];
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		if($mode==MEDIA_WRITEMODE_INSERT)
		{
			$item["id"]=0+@mysql_insert_id();
			if($bind!==false)$bind["mid"]=$item["id"];
		}
		if($bind!==false)
		{
			$bind=self::_bind($bind,$mode);
			if($bind===false)return false;
			$item["bid"]=$bind["id"];
			if(isset($bind["oid"]))$item["oid"]=$bind["oid"];
			if(isset($bind["par1"]))$item["par1"]=$bind["par1"];
			if(isset($bind["par2"]))$item["par2"]=$bind["par2"];
			if(isset($bind["par3"]))$item["par3"]=$bind["par3"];
		}
		return $item;
	}

	/**
	* Функция пытается зарегистрировать ресурс
	* без проверки входящих данных.
	* При успешной вставке возвращается набор полей (array) с обновленным ключем id.
	* При обновлении возвращается true;
	* При возникновении ошибки, возвращается индекс ошибки (int),
	* по которому можно получить текст ошибки (msgr::errorGet($ind,..)).
	* Если режим задан как целой число, функция расценивает его как id,
	* и переключается в режим обновления.
	*
	* @param array $item
	* @param int $mode/$iid
	* @param bool $err_json
	*
	* @return array/bool/int
	*/
	public static function _exec()
	{
		if(self::$_runStep!=1)return;
		self::$_runStep++;
		if(auth::admin())
		{
			if(self::$c->silent())
			{
				if(self::$c->action(self::$class."-lib-imgcrop"))
					self::_actionSilentLibImgCrop();
				if(self::$c->action(self::$class."-lib-file-edit"))
					self::_actionSilentLibFileEdit();
				if(self::$c->action(self::$class."-lib-files-list-more"))
					self::_actionSilentLibListMore();
			}
		}
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
		self::$silent=self::$c->silent();
		self::$uid=auth::user("id");
		//проверяем поддерживаемые типы
		$types=imagetypes();
		if($types & IMG_GIF)self::$imgSup[]=MEDIA_IMG_GIF;
		if($types & IMG_JPG)self::$imgSup[]=MEDIA_IMG_JPG;
		if($types & IMG_PNG)self::$imgSup[]=MEDIA_IMG_PNG;
		if($types & IMG_WBMP)self::$imgSup[]=MEDIA_IMG_WBMP;
		if($types & IMG_XPM)self::$imgSup[]=MEDIA_IMG_XBM;
		if(!in_array(self::$config["imgTypeDefault"],self::$imgSup))
		{
			if(count(self::$imgSup))self::$config["imgTypeDefault"]=self::$imgSup[0];
			else
			{
				msgr::add("Критическая ошибка ядра [".self::$class."]: не удалось определить поддерживаемые типы изображений!");
				return;
			}
		}
	}

	public static function _sleep()
	{
		self::_sessionWrite();
	}

	public static function captcha($str,$wid=150,$ht=50,$echo=true,$bgFile="")
	{
		// создаем изображение
		if(!is_string($str))settype($str,"string");
		if(!$str)$str="error";
		$simbs=str_split($str);
		$im=imagecreatetruecolor($wid,$ht);
		if($bgFile)
		{
			if(file_exists($bgFile))
			{
				$size=getimagesize($bgFile);
				if($size[0]==0 || !in_array($size[2],array(2)))$bgFile="";
				else
				{
					$srcImg=imagecreatefromjpeg($bgFile);
					imagecopyresampled($im,$srcImg,0,0,0,0,$wid,$ht,$size[0],$size[1]);
					imagedestroy($srcImg);
				}
			}
		}
		if(!$bgFile)
		{
			$bg=imagecolorallocate($im,255,255,255);
			imagefill($im,0,0,$bg);
			$lc1=imagecolorallocate($im,192,192,192);
			// Рисуем сетку
			for ($i=0;$i<=$wid;$i+=5)imageline($im,$i,0,$i,$ht,$lc1);
			for ($i=0;$i<=$ht;$i+=5)imageline($im,0,$i,$wid,$i,$lc1);
		}
		$len=count($simbs);
		// Выделяем четыре случайных темных цвета для символов
		for($cnt=0;$cnt<$len;$cnt++)
			$cl[$cnt]=imagecolorallocate($im,rand(0,128),rand(0,128),rand(0,128));
		// Выводим каждую цифру по отдельности, немного смещая случайным образом
		$intr=intval(round($wid/$len));
		if($intr==0)$intr=1;
		$font=5;
		$marg=10;
		if(file_exists("skin/{$this->skin()}/{$this->name()}/fonts/arial28.gdf"))
		{
			$font=imageloadfont("skin/{$this->skin()}/{$this->name()}/fonts/arial28.gdf");
			$marg=28;
		}
		$prec=intval(round(($intr-($marg/2))/2));
		if($prec==0)$prec=1;
		for($cnt=0;$cnt<$len;$cnt++)
			imagestring($im,$font,$cnt*$intr+rand(-$prec,+$prec),rand(0,$ht-$marg),$simbs[$cnt],$cl[$cnt]);
		// Выделяем цвет для более темных помех (темно-серый)
		$lc2=imagecolorallocate($im,64,64,64);
		// Выводим пару случайных линий темного цвета, прямо поверх символов.
		// Для увеличения количества линий можно увеличить,
		// изменив число выделенное красным цветом
		for ($i=0;$i<8;$i++)
		    imageline($im,rand(0,$wid),rand(0,$ht),rand(0,$wid),rand(0,$ht),$lc2);
		if($echo)
		{
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
 			header("Content-type: image/png");
			$res=imagepng($im,NULL,0);
			imagedestroy($im);
			return true;
		}
		else return $im;
	}

	/**
	* Функция проверяет наличие файлов в запросе и сохраняет их
	* во временную папку
	*/
	public static function check()
	{
		//если в массиве есть файлы, то выходим: редирект
		if(!count($_FILES))return;
		$updir=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["uploadDir"];
		if(!@file_exists($updir))
		{
			if(@mkdir($updir,0777,true)===false)
			{
				msgr::add("Невозможно загрузить файл: ошибка доступа к файловой системе! [".self::$class."::".__FUNCTION__." > ".__LINE__."]");
				self::$files=array();
				return false;
			}
		}
		//нормализуем массив файлов, так как он может передаваться
		//в разных по формату структурах
		$filesMap=array();
		/*
		[name] => MyFile.jpg
		[type] => image/jpeg
		[tmp_name] => /tmp/php/php6hst32
		[error] => UPLOAD_ERR_OK
		[size] => 98174
		*/
		foreach($_FILES as $id=>&$file)
		{
			if(@is_string($file["tmp_name"]))
			{
				$file["id"]=$id;
				$filesMap[]=&$file;
			}
			else
			{
				if(@is_array($file["tmp_name"]) && @is_array($file["name"]))
				{
					$fileNames=&$file["name"];
					foreach($fileNames as $ord=>$name)
					{
						$file1=array(
							"id"		=> $id,
							"name"		=> $name,
							"type"		=> $file["type"][$ord],
							"tmp_name"	=> $file["tmp_name"][$ord],
							"error"		=> $file["error"][$ord],
							"size"		=> $file["size"][$ord]
						);
						$filesMap[]=&$file1;
					}
				}
			}
		}
		//перемещаем распознанные по типу файлы
		//в собственное хранилище
		foreach($filesMap as $idx=>&$file)
		{
			if(!$file["error"])
			{
				$pi=pathinfo($file["name"]);
				$ext=strtolower($pi["extension"]);
				$found=false;
				foreach(self::$types as $type=>$exts)
				{
					if(in_array($ext,self::$types[$type]))
					{
						$found=true;
						break;
					}
				}
				if($found)
				{
					$fname=$updir."/".md5($file["tmp_name"].time()).".tmp";
					if((@move_uploaded_file($file["tmp_name"],$fname))===true)
					{
						if(@chmod($fname,0777))
						{
							$file["tmp_name"]=$fname;
							$file["moved"]=false;
							$file["time"]=time();
							$id=$file["id"];
							if(isset(self::$files[$id]))
							{
								if(isset(self::$files[$id]["tmp_name"]))
								{
									$file1=self::$files[$id];
									self::$files[$id]=array();
									self::$files[$id][]=&$file1;
								}
								self::$files[$id][]=&$file;
							}
							else self::$files[$id]=&$file;
						}
					}
				}
			}
		}
		$_FILES=array();
		self::_sessionRead();
	}

	/**
	* Очистка директории
	*
	* @param string $dir - директория
	* @param bool $rmDirs - удалять поддиректории, по умолчанию false
	* @param bool $rmSelf - удалить вконце очищаемую директорию, по умолчанию false
	*/
	public static function cleanDir($dir,$rmDirs=false,$rmSelf=false)
	{
		$dir=rtrim($dir,"/");
		if(!$dir)return false;
		if(!@is_dir($dir))return false;
		if($dir==FLEX_APP_DIR_MOD)return false;
		if($dir==FLEX_APP_DIR)return false;
		if($dir==(FLEX_APP_DIR."/".FLEX_APP_DIR_MOD))return false;
		if($dir==FLEX_APP_DIR_DAT)return false;
		if($dir==FLEX_APP_DIR_HLP)return false;
		$files=glob($dir."/*");
		$c=count($files);
		$res=true;
		if($c>0)
			foreach ($files as $file)
			{
				if(@is_dir($file))
				{
					if($rmDirs)
					{
						if(self::cleanDir($file,true))
							$res=$res && @rmdir($file);
						else
							$res=false;
					}
					else
					{
						if($rmSelf)$res=false;
					}
				}
				else $res=$res && @unlink($file);
			}
		if($res && $rmSelf)
			$res=$res && @rmdir($dir);
		return $res;
	}

	public static function config($par)
	{
		if(isset(self::$config[$par]))return self::$config[$par];
		else return NULL;
	}

	public static function count_($class,$entity=false,$filters=array())
	{
		//проверка модуля
		if(!$class || !class_exists(__NAMESPACE__."\\".$class))
		{
			$msg="Указанный модуль [".$class."] отсутствует в системе.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$modId=self::$c->modId($class);
		if(!$modId)
		{
			$msg="Указанный модуль [".$class."] не зарегистрирован в системе.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		if(is_array($filters) && count($filters))
		{
			$t=db::tMeta(self::$class);
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
			if(count($fts))$fts=db::filtersMake($fts,true,true,true);
			else $fts="";
		}
		else $fts="";
		if($fts===false)return false;
		//поля id,pid,uid,mid,width,height,uploaded,name_sized,size_delim,extension,name,name_orig,directory,title,credit,content_type
		$q="SELECT COUNT(DISTINCT `m`.`id`) AS `cnt` FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		WHERE `m`.`mid`=".$modId.($fts?$fts:"");
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		$rec=@mysql_fetch_assoc($r);
		return (0+$rec["cnt"]);
	}

	public static function create($class,$file,$values,$bind=false)
	{
		//проверка модуля
		if(!$class || !class_exists(__NAMESPACE__."\\".$class))
		{
			$msg="Указанный модуль [".$class."] отсутствует в системе.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$modId=self::$c->modId($class);
		if(!$modId)
		{
			$msg="Указанный модуль [".$class."] не зарегистрирован в системе.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		//content type
		$is_image=false;
		$mime_from_ext=false;
		$sz=false;
		if(@function_exists("mime_content_type"))$ctp=@mime_content_type($file["tmp_name"]);
		elseif(@function_exists("finfo_file"))
		{
			$finfo=@finfo_open(FILEINFO_MIME);
			$ctp=@finfo_file($finfo,$file["tmp_name"]);
			@finfo_close($finfo);
		}
		else
		{
			$ctp=lib::MIMEType($file["name"]);
			$mime_from_ext=true;
		}
		//окончательная проверка на content type
		if($mime_from_ext)
		{
			$sz=@getimagesize($file["tmp_name"]);
			if($sz===false)$is_image=false;
			else
			{
				$ctp=$sz["mime"];
				$is_image=true;
			}
		}
		else $is_image=(@strpos($ctp,"image")===0);
		if(strlen($ctp)>128)$ctp=substr($ctp,0,128);
		//image size
		$szW=0;
		$szH=0;
		if($is_image)
		{
			if(!$sz)$sz=@getimagesize($file["tmp_name"]);
			$szW=$sz[0];
			$szH=$sz[1];
		}
		else $nameSized=0;
		//file size
		$fsize=@filesize($file["tmp_name"]);
		//оригинальное имя файла
		$name=mb_strtolower($file["name"],"UTF-8");
		//расширение файла
		$ext=explode(".",$name);
		$name=$ext[0];
		if(count($ext)<2)$ext="";
		else $ext=$ext[count($ext)-1];
		//дата
		$dt=time();
		$dtSQL=lib::dt($dt,true);
		$values["id"]="NULL";
		$values["pid"]=0;
		$values["uid"]=self::$uid;
		$values["mid"]=$modId;
		$values["width"]=$szW;
		$values["height"]=$szH;
		$values["bytes"]=$fsize;
		$values["uploaded"]=$dtSQL;
		$values["type"]=self::_imgExt2Type($ext);
		$values["extension"]=$ext;
		$values["name_orig"]=$file["name"];
		$values["content_type"]=$ctp;
		//проверяем пользовательские данные
		//"name_id","name_sized","size_delim","name","title","credit"
		foreach(self::$fieldsReq as $field)
		{
			switch($field)
			{
				case "directory":
					$owner=true;
					if(!isset($values["directory"]) || !$values["directory"])
					{
						if($bind!==false && isset($bind["oid"]))$dir="".$bind["oid"];
						else $dir=self::$config["dirShared"];
						$owner=false;
					}
					else $dir=$values["directory"];
					$values["directory"]=FLEX_APP_DIR_DAT."/_".$class."/".$dir.($owner?("/".self::$config["dirOwner"]):"");
					break;
				case "name_id":
				case "name_sized":
					$values[$field]=(0+$values[$field])?1:0;
					break;
			}
		}
		if(!$values["name"])$values["name_id"]=1;
		if($bind!==false)
		{
			if(!is_array($bind) || !count($bind))
			{
				$msg="Неверные параметры байндинга.";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
		}
		//регистрируем файл
		$item=self::_write($values,$bind,MEDIA_WRITEMODE_INSERT);
		if($item===false)return false;
		$nameId=$item["name_id"];
		$nameSized=$item["name_sized"];
		$sizeDelim=$item["size_delim"];
		$name=$item["name"];
		$dir=$item["directory"];
		//проверяем папки на доступ
		if(!@file_exists($dir))
		{
			@mkdir($dir,0755,true);
			if(!@file_exists($dir))
			{
				self::_delete($item["id"]);
				$msg="Медиа-директория недоступна для записи!";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__,"Media directory is not writeable: [".$dir."]");
				return false;
			}
		}
		else
		{
			if(!@is_dir($dir))
			{
				self::_delete($item["id"]);
				$msg="Медиа-директория недоступна (имя занято файловым объектом)!";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__,"Media thumbs directory name is already taken by file-object: [".$dir."]");
				return false;
			}
		}
		//проверяем на перезапись файл
		$filename=($name?(($nameId?($item["id"].$sizeDelim):"").$name):$item["id"]).($is_image?($nameSized?($sizeDelim.$szW."x".$szH):""):"").".".$ext;
		if(@file_exists($dir."/".$filename))
		{
			self::_delete($item["id"]);
			$msg="Файл с данным именем [".$dir."/".$filename."] уже существует!";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__,"Destination file already exists: [".$dir."/".$filename."]");
			return false;
		}
		//перемещаем файл
		@copy($file["tmp_name"],$dir."/".$filename);
		if(!@file_exists($dir."/".$filename))
		{
			self::_delete($item["id"]);
			$msg="Невозможно переместить файл в запрошенную директорию!";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__,"Destination file is not writeable: [".$dir."/".$filename."]");
			return false;
		}
		else @chmod($dir."/".$filename,0755);
		$item["filename"]=$filename;
		$item["file_path"]=$dir."/".$filename;
		$item["url"]=FLEX_APP_DIR_ROOT.$item["file_path"];
		//проверка папки иконок
		if($is_image)
		{
			$item["url_thumb"]=self::_thumb($item,self::$config["uploaderThumbExt"],self::$config["uploaderThumbSize"],$item["file_path"]);
			if($item["url_thumb"]===false)$item["url_thumb"]=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/thumb-error.gif";
		}
		return $item;
	}

	public static function delete($class,$filters=false)
	{
		$wheres=array();
		if(is_integer($class))
		{
			//удаляем по id
			$wheres[]="`m`.`id`=".$class;
		}
		elseif(is_array($class))
		{
			$ids=array();
			foreach($class as $key=>$val)
			{
				if(is_int($key))
				{
					$val=0+$val;
					if($val)$ids[]=$val;
				}
			}
			if(count($ids))$wheres[]="`m`.`id` IN (".implode($ids).")";
		}
		else
		{
			if(!$class || !class_exists(__NAMESPACE__."\\".$class))
			{
				$msg="Указанный модуль [".$class."] отсутствует в системе.";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			$modId=self::$c->modId($class);
			if(!$modId)
			{
				$msg="Указанный модуль [".$class."] не зарегистрирован в системе.";
				if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
				else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
				return false;
			}
			$wheres[]="`m`.`mid`=".$modId;
			if(is_array($filters) && count($filters))
			{
				$ids=array();
				foreach($filters as $key=>$val)
				{
					if(is_int($key))
					{
						$val=0+$val;
						if($val)$ids[]=$val;
					}
					else
					{
						switch($key)
						{
							case "oid":
								$val=0+$val;
								if($val)$wheres[]="`mb`.`oid`=".$val;
								break;
							case "content_type":
								$wheres[]="`m`.`content_type`='".mysql_real_escape_string($val)."'";
								break;
							case "par1":
							case "par2":
								if(!is_array($par1))$wheres[]="`mb`.`".$key."`='".mysql_real_escape_string($val)."'";
								else
								{
									foreach($val as $key1=>$str)$val[$key]=mysql_real_escape_string($str);
									$wheres[]="`mb`.`par1` IN ('".implode("','",$par1)."')";
								}
								break;
						}
					}
				}
				$count=count($ids);
				if($count>self::$config["batchMaxCount"])
				{
					$msg="Невозможно выполнить операцию: результатирующий набор слишком велик [>".self::$config["batchMaxCount"]."]";
					if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
					else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
					return false;
				}
				if($count)$wheres[]="`m`.`id` IN (".implode($ids).")";
			}
			//поля media
			//id,pid,uid,mid,width,height,bytes,uploaded,name_id,name_sized,size_delim,extension,name,name_orig,directory,title,credit,content_type
			//поля media_binds
			//id,mid,oid,par1,par2,par3
		}
		//
		if(!count($wheres))
		{
			$msg="Не задан ни один параметр вызова [entity,ids,params], ожидается как миниму 1 параметр";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$q="SELECT COUNT(DISTINCT `m1`.`id`) AS `cnt` FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class)." `m1` ON (`m1`.`id`=`m`.`id` || `m1`.`pid`=`m`.`id`)
		WHERE ".implode(" AND ",$wheres);
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		$rec=@mysql_fetch_row($r);
		if((0+$rec["cnt"])>self::$config["batchMaxCount"])
		{
			$msg="Невозможно выполнить операцию: результатирующий набор слишком велик [>".self::$config["batchMaxCount"]."]";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$q="SELECT DISTINCT
		`m1`.`id`,`m1`.`width`,`m1`.`height`,`m1`.`uploaded`,`m1`.`name_id`,`m1`.`size_delim`,`m1`.`name_sized`,
		`m1`.`name`,`m1`.`directory`,`m1`.`extension`,`mb`.`oid`,`mb`.`par1`,`mb`.`par2`,`mb`.`par3`
		FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class)." `m1` ON (`m1`.`id`=`m`.`id` || `m1`.`pid`=`m`.`id`)
		WHERE ".implode(" AND ",$wheres);
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		$ids=array();
		while($rec=@mysql_fetch_assoc($r))
		{
			$id=0+$rec["id"];
			$ids[]=$id;
			$dt=$rec["uploaded"];
			$dts=strtotime($dt);
			$nid=(0+$rec["name_id"])?true:false;
			$szd=$rec["size_delim"];
			$ext=$rec["extension"];
			$name=$rec["name"];
			$wd=0+$rec["width"];
			$ht=0+$rec["height"];
			$nsd=(0+$rec["name_sized"])?true:false;
			$file=$rec["directory"]."/".($name?(($nid?($id.$szd):"").$name):$id).($nsd?($szd.$wd."x".$ht):"").".".$ext;
			if(@file_exists($file))@unlink($file);
			if(self::typeDefine($ext)==MEDIA_TYPE_IMG)
			{
				$yr=date("Y",$dts);
				$mn=date("m",$dts);
				$d=date("d",$dts);
				$th=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/".self::$config["uploaderThumbDir"]."/";
				$thd=$th.$yr."/".$mn."/".$d."/".$id.".".self::$config["uploaderThumbExt"];
				if(@file_exists($thd))@unlink($thd);
				else
				{
					$thd=$th.$yr."/".$mn."/".$d."/".$id.".jpg";
					if(@file_exists($thd))@unlink($thd);
				}
			}
		}
		$q="DELETE `m1`,`mb` FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class)." `m1` ON (`m1`.`id`=`m`.`id` || `m1`.`pid`=`m`.`id`)
		WHERE ".implode(" AND ",$wheres);
		$r=db::q($q,!self::$silent);
		if($r===false)return false;
		return $ids;
	}

	public static function fetch($id,$childs=true)
	{
		$id=0+$id;
		if(!$id)
		{
			$msg="Указан неверный ресурс.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		if(!is_bool($childs))$childs=true;
		return self::_fetch($id,$childs);
	}

	public static function fetchArray($class,$entity=false,$filters=array(),$range=false,$childs=false)
	{
		//проверка модуля
		if(!$class || !@class_exists(__NAMESPACE__."\\".$class))
		{
			$msg="Указанный модуль [".$class."] отсутствует в системе.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		$modId=self::$c->modId($class);
		if(!$modId)
		{
			$msg="Указанный модуль [".$class."] не зарегистрирован в системе.";
			if(!self::$silent)msgr::add($msg,MSGR_TYPE_ERR);
			else msgr::errorLog($msg,false,self::$class,__FUNCTION__,__LINE__);
			return false;
		}
		//проверяем $entity
		if($entity!==false && ((0+$entity)>0))$filters[]=array("oid","=",0+$entity);
		else $entity=false;
		//проверяем $range
		if(!is_array($range))$range=false;
		else
		{
			$c=count($range);
			if(!$c || $c>2)$range=false;
			else
			{
				if($c==1)array_unshift($range,0);
			}
		}
		return self::_fetchArray($modId,$entity,$filters,$range,$childs);
	}

	public static function imageOut($img,$dir="")
	{
		if(!$img)
		{
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			return;
		}
		if($dir)$dir=seld::$dir;
		if(!file_exists($dir."/".$img))
		{
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			return;
		}
		$fn=explode(".",$img);
		$ext=$fn[count($fn)-1];
		$ext=strtolower($ext);
		if(!in_array($ext,array("jpg","jpeg","gif","png","ico","bmp","tif")))
		{
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			return;
		}
		$fs=@filesize($dir."/".$img);
		if($ext=="jpg")$ext="jpeg";
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
 		header("Content-type: image/$ext");
 		header("Content-Length: $fs");
 		readfile($dir."/".$img);
	}

	/**
	* Функция создает обрезанный/измененный экземляр изображения
	*
	* @param int $id - идентификатор исходного изображения
	* @param bool/array $bind - false или параметры для байндинга (oid,par1,par2,par3)
	* @param int $forceType - тип обработки MEDIA_IRES_..
	* @param array $params - параметры обработки ("addSizes"=>bool/true,"sizeDelim"=>str[1]/"-","dstRect"=>ar[n2]/ar[n4],"dstShift"=>arr[n2]/[-1,-1],...see help for more)
	* @param bool/string $name - false или имя файла
	* @param bool $useid - добавлять id в имя файла
	*
	* @return array/mixed
	*/
	public static function imageResample($id,$forceType,$params,$name="",$useid=true,$bind=false)
	{
		if((!is_int($id) && !is_string($id)) || (!(0+$id)))
		{
			self::$lastMsg="Неверный идентификатор изображения.";
			return -1;
		}
		$id=0+$id;
		$img=self::_fetch($id,false);
		if(!is_array($img) || !count($img))return false;
		if($name===true)$name=$img["name"];
		if(!$name)$useid=true;
		$item["id"]=0;
		$item["pid"]=$id;
		$item["uid"]=0;
		$item["mid"]=$img["mid"];
		$item["width"]=0;
		$item["height"]=0;
		$item["bytes"]=0;
		$item["uploaded"]=date("Y-m-d H:i:s",time());
		$item["type"]=2;
		$item["name_id"]=$useid?1:0;
		$item["name_sized"]=isset($params["addSizes"])?($params["addSizes"]?1:0):1;
		$item["size_delim"]=$params["sizeDelim"]?$params["sizeDelim"]:self::$config["sizeDelimiter"];
		$item["extension"]=$img["extension"];
		$item["name"]=$name?$name:(!$useid?($img["name"]?$img["name"]:""):"");
		if(!$item["name"])$item["name_id"]=1;
		$item["directory"]=$img["directory"];
		$item["title"]="";
		$item["credit"]="";
		$item["content_type"]=$img["content_type"];
		if(($bind!==true) && !is_array($bind))$bind=false;
		$item=self::_write($item,$bind,MEDIA_WRITEMODE_INSERT);
		if(!is_array($item))return false;
		$item=self::_recFormat($item);
		$name=array();
		//обрезаем
		$res=self::_resample($img["file_path"],$item["file_path"],$forceType,$params);
		if(!is_string($res))
		{
			self::_delete($item["id"]);
			return false;
		}
		@chmod($item["file_path"],0755);
		//обновляем
		$item["bytes"]=@filesize($item["file_path"]);
		$dims=@getimagesize($item["file_path"]);
		$pi=@pathinfo($item["file_path"]);
		$data=array(
			"id"=>$item["id"],
			"width"=>$dims[0],
			"height"=>$dims[1],
			"bytes"=>$item["bytes"],
			"extension"=>$pi["extension"]
		);
		self::_write($data,false,MEDIA_WRITEMODE_UPDATE);
		$item["width"]=$dims[0];
		$item["height"]=$dims[1];
		//иконка
		$item["url_thumb"]=self::_thumb($item,self::$config["uploaderThumbExt"],self::$config["uploaderThumbSize"],$item["file_path"]);
		if($item["url_thumb"]===false)$item["url_thumb"]=FLEX_APP_DIR_DAT."/_".self::$class."/".self::$config["thumbsDir"]."/thumb-error.gif";
		return $item;
	}

	public static function lastMsg()
	{
		$m=self::$lastMsg;
		self::$lastMsg="";
		return $m;
	}

	public static function postFile($id)
	{
		if(isset(self::$files[$id]))return self::$files[$id];
		else return false;
	}

	public static function postFileMoved($id,$idx=0)
	{
		if(isset(self::$files[$id]))
		{
			if(isset(self::$files[$id]["tmp_name"]))self::$files[$id]["moved"]=true;
			else
			{
				if(isset(self::$files[$id][$idx]))self::$files[$id][$idx]=true;
			}
		}
	}

	/**
	* Обертка для функция media::_prepare
	*
	* @param array $item
	* @param string $mode
	* @param bool $check
	* @param bool $err_json
	*
	* @return array/int
	*/
	public static function typeDefine($ext)
	{
		$ext=strtolower($ext);
		foreach(self::$types as $type=>$exts)
			if(in_array($ext,$exts))return $type;
		return MEDIA_TYPE_ANY;
	}

	public static function unbind($class,$entity=false,$ids=array(),$params=array())
	{
		if(!$class || !class_exists(__NAMESPACE__."\\".$class))
		{
			self::$lastMsg="Указанный модуль [".$class."] отсутствует в системе";
			return false;
		}
		$modId=self::$c->modId($class);
		if(!$modId)
		{
			self::$lastMsg="Указанный модуль [".$class."] не зарегистрирован в системе";
			return false;
		}
		if($entity!==false)
		{
			$entity=0+$entity;
			if($entity<0)$entity=false;
		}
		$par1=NULL;
		$par2=NULL;
		$ctype=NULL;
		if(is_string($ids) || is_integer($ids))
		{
			if(is_string($ids))$ids=0+$ids;
			if(!$ids)
			{
				self::$lastMsg="Некорректный параметр вызова [ids], приемлемые типы: [integer, array of integer]";
				return false;
			}
			$ids=" `m`.`id`=".$ids;
		}
		elseif(is_array($ids))
		{
			if(count($ids))
			{
				$idsi=array();
				foreach($ids as $id)
				{
					$id=0+$id;
					if($id>0)$idsi[]=$id;
				}
				$count=count($idsi);
				if(!$count)
				{
					self::$lastMsg="Некорректный параметр вызова [ids], приемлемые типы: [integer, array of integer]";
					return false;
				}
				if($count>self::$config["batchMaxCount"])
				{
					self::$lastMsg="Невозможно выполнить операцию: результатирующий набор слишком велик [>".self::$config["batchMaxCount"]."]";
					return false;
				}
				$ids=" `m`.`id` IN (".implode(",",$idsi).")";
			}
			else $ids=false;
		}
		else
		{
			self::$lastMsg="Некорректный параметр вызова [ids], приемлемые типы: [integer, array of integer]";
			return false;
		}
		if(is_string($params))$par1=$params;
		else
		{
			for($cnt=1;$cnt<3;$cnt++)
				if(isset($params["par".$cnt]) && $params["par".$cnt])
					eval("\$par".$cnt."=\$params[\"par\"".$cnt."];");
			if(isset($params["content_type"]))$ctype="`m`.`content_type`='".$params["content_type"]."'";
		}
		$wheres=array();
		if($ids)$wheres[]=$ids;
		if($entity!==false)$wheres[]="`mb`.`oid`=".$entity;
		if(!is_null($par1))
		{
			if(!is_array($par1))$wheres[]="`mb`.`par1`='".$par1."'";
			else $wheres[]="`mb`.`par1` IN ('".implode("','",$par1)."')";
		}
		if(!is_null($par2))
		{
			if(!is_array($par1))$wheres[]="`mb`.`par2`='".$par2."'";
			else $wheres[]="`mb`.`par2` IN ('".implode("','",$par2)."')";
		}
		if(!is_null($ctype))$wheres[]=$ctype;
		if(!count($wheres))
		{
			self::$lastMsg="Не задан ни один параметр вызова [entity,ids,params], ожидается как миниму 1 параметр";
			return false;
		}
		$q="SELECT COUNT(DISTINCT `m1`.`id`) AS `cnt` FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class)." `m1` ON (`m1`.`id`=`m`.`id` || `m1`.`pid`=`m`.`id`)
		WHERE `m`.`mid`=".$modId." AND ".implode(" AND ",$wheres);
		$r=db::q($q,false);
		if($r===false)
		{
			self::$lastMsg=db::lastError();
			return false;
		}
		$rec=@mysql_fetch_row($r);
		if((0+$rec["cnt"])>self::$config["batchMaxCount"])
		{
			self::$lastMsg="Невозможно выполнить операцию: результатирующий набор слишком велик [>".self::$config["batchMaxCount"]."]";
			return false;
		}
		$q="SELECT DISTINCT `m`.`id` FROM ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class)." `m1` ON (`m1`.`id`=`m`.`id` || `m1`.`pid`=`m`.`id`)
		WHERE `m`.`mid`=".$modId." AND ".implode(" AND ",$wheres);
		$r=db::q($q,false);
		if($r===false)
		{
			self::$lastMsg=db::lastError();
			return false;
		}
		$ids=array();
		while($rec=@mysql_fetch_assoc($r))$ids[]=0+$rec["id"];
		$q="UPDATE ".db::tnm(self::$class)." `m`
		INNER JOIN ".db::tnm(self::$class."_binds")." `mb` ON `mb`.`mid`=`m`.`id`
		LEFT JOIN ".db::tnm(self::$class)." `m1` ON (`m1`.`id`=`m`.`id` || `m1`.`pid`=`m`.`id`)
		SET `mb`.`oid`=0
		WHERE `m`.`mid`=".$modId." AND ".implode(" AND ",$wheres);
		db::q($q,false);
		return $ids;
	}
}
?>
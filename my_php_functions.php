<?php
/**
 * @authors kingmui muikinghk@yahoo.com.hk
 * @github github.com/kingmui
 * @blog   kingmui.github.io
 * @date   2017-09-23 22:09:28
 */
header("content-type:text/html;charset=utf-8");

// 数据库类详见 09.AJAX.html 14. 数据库操作类实现
/*-------------------------------------数据库连接函数-------------------------------------*/
/**
 * [$dbConf 数据库连接的配置参数]
 * @var string host    [主机名]
 * @var string user    [用户名]
 * @var string pwd     [密码]
 * @var string db      [数据库名]
 * @var string charset [字符集编码]
 */
$dbConf = [
	"host" => "localhost:3306",
	"user" => "root",
	"pwd" => "",
	"db" => "lotion",
	"charset" => "utf8"
];
/**
 * [dbConn 数据库连接]
 * @param  array  $conf    [配置参数数组]
 * @return resource $conn  [建立的连接]
 */
// 调用函数
// $conn = dbConn($dbConf);
function dbConn($conf = []){
	$conn = @mysql_connect($conf["host"],$conf["user"],$conf["pwd"]);
	if(!$conn){
		die("数据库连接失败，错误信息:".mysql_error());
	}
	mysql_select_db($conf["db"],$conn) or die("数据库选择失败，错误信息:".mysql_error());
	mysql_query("set names ".$conf["charset"]) or die("无效的字符集，错误信息:".mysql_error());
	return $conn;
}

/*--------------------------------------文件上传函数--------------------------------------*/
/**
 * [$fileConf 上传文件的配置参数]
 * @var string userFile      [文件域的name属性值]
 * @var string backUrl       [上传失败后返回的链接]
 * @var string dir           [文件保存的路径]
 * @var integer maxSize      [上传文件的最大值]
 * @var integer waitSeconds  [等待的秒数]
 * @var array mimeArr        [网站支持的MIME类型]
 */
$fileConf = [
	"userFile" => "myfile",
	"backUrl" => "./index.html",
	"dir" => "../images/upload/",
	"maxSize" => 1,
	"waitSeconds" => 2,
	"mimeArr" => ["image/png","image/jpeg"]
];
/**
 * [uploadFile 文件上传]
 * @param  array  $conf    [配置参数数组]
 * @return string $saveName  [存入数据库的文件路径]
 */
// 调用函数
// $saveName = uploadFile($fileConf);
function uploadFile($conf = []){
	$url = $conf["backUrl"];
	$waitSec = $conf["waitSeconds"];
	$dir = $conf["dir"];
	if(empty($_FILES)){
		redirFre($url,"上传的文件大小超过了POST_MAX_SIZE参数指定的值",$waitSec);
	}
	$file = $_FILES[$conf["userFile"]];
	$errorMsg = "";
	if($file["error"] <> 0){
		switch ($file["error"]) {
        case 1:
            $errorMsg = '上传的文件大小超过了 php.ini 中UPLOAD_MAX_FILESIZE选项限制的值';
            break;
        case 2:
            $errorMsg = '上传文件的大小超过了FORM表单中MAX_FILE_SIZE参数指定的值';
            break;
        case 3:
            $errorMsg = '文件只有部分被上传';
            break;
        case 4:
            $errorMsg = '没有选择上传文件';
            break;
        case 6:
            $errorMsg = '找不到临时文件夹';
            break;
        default:
        	$errorMsg = '文件上传过程中发生未知错误';
    	}    	
    	redirFre($url,$errorMsg,$waitSec);
	}
	if(!is_uploaded_file($file["tmp_name"])){
		redirFre($url,"非法上传的文件",$waitSec);
	}
	if($file["size"] / 1024 / 1024 > $conf["maxSize"]){
		redirFre($url,"文件大小超出允许的上限",$waitSec);
	}
	$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime = finfo_file($fileInfo,$file["tmp_name"]);
	if(!in_array($mime,$conf["mimeArr"])){
		redirFre($url,"非法的文件类型",$waitSec);
	}
	if(!is_dir($dir)){
		mkdir(iconv("utf-8","gbk",$dir),0777,true);
	}
	$saveName = $dir.date("Ymd-His-").mt_rand(1000,9999).$file["name"];
	$convPath = iconv("utf-8","gbk",$saveName);
	if(move_uploaded_file($file["tmp_name"],$convPath)){
		return $saveName;
	}
}

/*--------------------------------------SQL执行函数--------------------------------------*/
/**
 * [query 执行SQL语句]
 * @param  string  $sql [要执行的语句]
 * @return  [boolean | resource]  [查询结果集]
 */
function query($sql = ""){
	$res = mysql_query($sql);
	if(!$res){
		return false;
	}else{
		return $res;
	}
}

/*---------------------------------------查询一行---------------------------------------*/
/**
 * [selectOneRow 查询一行]
 * @param  string $sql [SQL语句]
 * @return [array]     [空数组或者关联数组]
 */
function selectOneRow($sql = ""){
	$res = query($sql);
	if(mysql_num_rows($res) == 0){
		return [];
	}else{
		return mysql_fetch_assoc($res);
	}
}

/*---------------------------------------查询多行---------------------------------------*/
/**
 * [selectRows 查询多行]
 * @param  string  $sql      [SQL语句]
 * @return [array] $rows     [空数组或者关联数组]
 */
function selectRows($sql = ""){
	$res = query($sql);
	if(mysql_num_rows($res) == 0){
		return [];
	}else{
		while($line = mysql_fetch_assoc($res)){
			$rows[] = $line;
		}
		return $rows;
	}
}

/*---------------------------------查询一个一行一列的值---------------------------------*/
/**
 * [selectOneCol 查询一个一行一列的值]
 * @param  string $sql [SQL语句]
 * @return [boolean | integer | string]
 */
// 统计数据行数，如统计总评论数：$sql ="select count(*) as nums from commentsInfo";
function selectOneCol($sql = ""){
	$res = query($sql);
	if(mysql_num_rows($res) == 0){
		return false;
	}else{
		$line = mysql_fetch_row($res);
		return $line[0];
	}
}

/*--------------------------------------刷新式重定向--------------------------------------*/
/**
 * [redirFre 刷新式重定向]
 * @param  string  $url         [跳转地址]
 * @param  string  $msg         [提示信息]
 * @param  integer $waitSeconds [等待的秒数]
 */
function redirFre($url = "",$msg = "",$waitSeconds = 2){
	header("refresh:$waitSeconds;url=$url");
	die("<h4>".$msg."</h4>");
}

/*--------------------------------------跳转式重定向--------------------------------------*/
/**
 * [redirLoc 跳转式重定向]
 * @param  string  $url         [跳转地址]
 */
function redirLoc($url = ""){
	header("location:$url");
}

/*--------------------------------------弹窗式重定向--------------------------------------*/
/**
 * [redir 弹窗式重定向]
 * @param  string $url [跳转地址]
 * @param  string $msg [提示信息]
 */
function redir($url = "",$msg = ""){
	echo "<script>alert('".$msg."');location.href = '".$url."'</script>";
}

/*--------------------------------------后退式重定向--------------------------------------*/
/**
 * [hisBack 后退式重定向]
 * @param  string $msg [提示信息]
 */
function hisBack($msg = ""){
	echo "<script>alert('".$msg."');history.go(-1)</script>";
}

/*--------------------------------------数组打印函数--------------------------------------*/
/**
 * [dump 数组打印函数]
 * @param  array  $arr [需要打印的数组]
 */
function dump($arr = []){
	if(is_array($arr)){
		echo "<pre>";
		print_r($arr);
		echo "</pre>";
	}else{
		var_dump($arr);
	}
}

/*--------------------------------------验证用户登录--------------------------------------*/
/**
 * [$logConf 验证信息数组]
 * @var string tableName    [查询的表名]
 * @var string userField    [查询表的用户名字段]
 * @var string pwdField     [查询表的密码字段]
 * @var string user         [用户提交的用户名]
 * @var string pwd          [用户提交的密码]
 * @var string faiUrl       [失败返回的页面]
 * @var string sucUrl       [成功跳转的页面]
 * @var string errMsg       [错误信息]
 */
/*
$logConf = [
	"tableName" => "tbname",
	"userField" => "user",
	"pwdField" => "password",
	"user" => $_POST['username'],
	"pwd" => $_POST['pwd'],
	"faiUrl" => "./login.php",
	"sucUrl" => "./index.php",
	"errMsg" => "用户名或者密码出错"
];
*/
/**
 * [logValidate 验证用户登录]
 * @param  array  $conf [验证信息数组]
 */
// 调用函数
// logValidate($logConf);
function logValidate($conf = []){
	$sql = "select * from ".$conf['tableName']." where ".$conf['userField']." = '".$conf['user']."'";
	$res = mysql_query($sql);
	if(!$res || mysql_num_rows($res) == 0){
		redirFre($conf['faiUrl'],$conf['errMsg']);
	}
	$line = mysql_fetch_assoc($res);
	$pwdField = $conf['pwdField'];
	$dbPass = $line["$pwdField"];
	if(md5($conf["pwd"]) === $dbPass){
		session_start();
		$_SESSION['user'] = $conf["user"];
		redirFre($conf['sucUrl'],"登录成功！");
	}else{
		redirFre($conf['faiUrl'],"登录失败！");
	}
}

/*------------------------------------验证用户登录状态------------------------------------*/
/**
 * [is_login 验证用户登录状态]
 * @param  string  $url [登录页面地址]
 * @param  string  $msg [提示信息]
 */
function is_login($url,$msg){
	session_start();
	if(!isset($_SESSION['user'])){
		redir($url,$msg);
	}
}
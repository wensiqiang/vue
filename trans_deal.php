<?php
header("content-type:text/html;charset=utf-8");
$fileName = $_GET['fileName'];
// echo realpath($fileName);die;
$filePath = "E:\\Code\\GitHub\\HelloFrontEnd\\".$fileName;
$filePath = iconv("utf-8", "gbk", $filePath);
$cont = file_get_contents($filePath);
// $lk = "<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,700,400&subset=latin,latin-ext' rel='stylesheet' type='text/css' />";
$md = ".md<";
$padding = " 100px";
$anch = "a { cursor: pointer; }";
// strpos($cont, $lk) !== false || 
if(strpos($cont, $md) !== false || strpos($cont, $padding) !== false || strpos($cont, $anch) !== false){
	// $cont = str_replace($lk, "", $cont);
	$cont = str_replace($md, "<", $cont);
	$cont = str_replace($padding, "", $cont);
	$cont = str_replace($anch, "a { cursor: pointer; text-decoration: none;}", $cont);
	$fp = fopen($filePath, 'w');
	fwrite($fp, $cont);
	echo 1;
}else{
	echo 0;
}
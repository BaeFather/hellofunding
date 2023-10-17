<?

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include_once("../data/office_ipconfig.php");


$url = @base64_decode(preg_replace("/hello/", "=", trim($_REQUEST['url'])));

if(preg_match("/183\.98\.101\.114/", $_SERVER['REMOTE_ADDR'])) {
	if(substr($_SERVER['REQUEST_URI'], -5) != '.html') { header("HTTP/1.0 404 Not Found"); exit; }
}

if($url) {

	$image_path = preg_replace("/https:\/\/www.hellofunding.co.kr\//i", $_SERVER['DOCUMENT_ROOT']."/", $url);

	if(file_exists($image_path)) {
		$print_string = "<img src='".$url."' style='width:98%;max-width:800px;margin:0 auto;'>";
	}
	else {
		$print_string = "<div style='margin-top:20%; text-align:center;color:#fff;font-size:27pt;font-weight:bold'>이미지가 없습니다.</div>\n";
	}

}
else {
	$print_string = "<div style='margin-top:20%; text-align:center;color:#fff;font-size:27pt;font-weight:bold'>이미지가 없습니다.</div>\n";
}


?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="-1">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=0,maximum-scale=10,user-scalable=yes">
<meta name="HandheldFriendly" content="true">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="#073190">
<title>헬로펀딩, 대한민국 P2P 금융의 표준, P2P투자, P2P대출, 소액투자의 시작 헬로펀딩</title>
<link rel="stylesheet" type="text/css" href="/theme/2018/css/mobile.css?ver=20180724">
<link rel="stylesheet" type="text/css" href="/theme/2018/css/layout_m.css?ver=20180724">
<style>
body { background:#000; }
#wrap { width:100%; margin:0; padding:0; }
#xtop { position:fixed; z-index:1; width:100%; height:60px; top:0; left:0; text-align:right; }
#xcontainer { clear:both; width:100%;margin:30px auto; text-align:center; }
</style>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>

<body <? if( !in_array($_SERVER['REMOTE_ADDR'], $CONF['office_ip']) ) { ?>oncontextmenu="return false" onselectstart="return false" ondragstart="return false"<? } ?>>

<div id="wrap" style="border:1px solid #000">
	<div id="xtop"><img src="/images/cancel_w1.png" id="btnBack" style="width:30px;margin:15px;cursor:pointer"></div>
	<div id="xcontainer"><?=$print_string?></div>
</div>

</body>
</html>

<script>
$('#xtop').click(function() {
<?
	if( preg_match("/(iphone|ipad|ipod|android|xoom|sch-i800|tablet|kindle|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile)/i", $_SERVER['HTTP_USER_AGENT']) ) {
		echo "history.back();\n";
	}
	else {
		echo "self.close();\n";
	}
?>
});
</script>
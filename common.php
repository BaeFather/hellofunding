<?php
///////////////////////////////////////////////////////////////////////////////
// common.php 서비스용
///////////////////////////////////////////////////////////////////////////////

include_once('/home/crowdfund/public_html/data/office_ipconfig.php');

$office_connect = true;


if( @$_SERVER['HTTP_USER_AGENT'] ) {
	if( @in_array(@$_SERVER['REMOTE_ADDR'], $CONF['office_ip']) || @in_array(@$_SERVER['HTTP_X_FORWARDED_FOR'], $CONF['office_ip']) ) {
		$office_connect = true;
	}
}
//if(!$office_connect) { header('HTTP/1.0 404 Not Found'); exit; }

define('OFFICE_CONNECT', $office_connect);

if( @$_SERVER['HTTP_USER_AGENT'] ) {

	/* 비정상 IP 체크 : SQL Injection 의심 차단 : cf_visit_status > ip 값에 쿼리 흔적으로 확인 */
	if( !IPCheck($_SERVER['REMOTE_ADDR']) ) {
		header('HTTP/1.0 404 Not Found'); exit;
	}

	/* SQL Injection 의심 IP 차단 : cf_visit_status > referer 및 site_id 값에 쿼리 흔적으로 확인 */
	if( in_array($_SERVER['REMOTE_ADDR'], array('121.78.24.224','118.130.152.231','218.51.218.74','61.33.28.195')) ) {
		header('HTTP/1.0 404 Not Found'); exit;
	}

}


/////////////////////////////////////////////////
// 공용시스템 공지 (2021-08-19 부터 시행)
/////////////////////////////////////////////////
//if(@$_REQUEST['mode']=='xxxxx') {
	if( @$_SERVER['HTTP_USER_AGENT']) {
		if(OFFICE_CONNECT===false) {
			$NOTICONF['sdate'] = '2022-12-05 19:00:00';
			$NOTICONF['edate'] = '2022-12-06 00:00:00';
			if(date('Y-m-d H:i:s') >= $NOTICONF['sdate'] && date('Y-m-d H:i:s') < $NOTICONF['edate']) {
				include($_SERVER['DOCUMENT_ROOT'] . '/system_notice/public_notice/notice.php');
				exit;
			}
		}
	}
//}


/*
//긴급공지 2021-05-03 11:00 ~ 12:00
if(@$_REQUEST['mode']=='xxxxx') {
	if(@$_SERVER['HTTP_USER_AGENT'] && OFFICE_CONNECT==true) {
		$renewal_image = '/images/system_notice.jpg';
		$width= ( preg_match('/(android|iphone)/i', $_SERVER['HTTP_USER_AGENT']) ) ? '90%' : '';
		echo '<div style="width:100%;padding-top:80px;text-align:center"><img src="'.$renewal_image.'" style="width:'.$width.'";></div>';
		exit;
	}
}
*/


$http_origin = @explode("/",str_replace($_SERVER["REQUEST_SCHEME"]."://","",$_SERVER["HTTP_REFERER"]));

$allowed_origin = array(
	"www.hellofunding.co.kr",
	"www1.hellofunding.co.kr",
	"www2.hellofunding.co.kr",
	"manager.hellofunding.co.kr",
	"ext.hellofunding.co.kr",
	"hellolivetv.co.kr",
	"www.hellolivetv.co.kr",
	"chosun.hellofunding.kr",
	"r114.hellofunding.co.kr",
	"itembay.hellofunding.co.kr",
	"kakaopay.hellofunding.co.kr",
	"kakaoweb.hellofunding.co.kr",
	"kakaoweb2.hellofunding.co.kr",
);

$strFullDomain = @$_SERVER["REQUEST_SCHEME"]."://".$http_origin[0];

if(in_array($http_origin[0], $allowed_origin)) header("Access-Control-Allow-Origin: {$strFullDomain}");
else header("Access-Control-Allow-Origin: https://www.hellofunding.co.kr");

header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST,GET,PUT,OPTIONS,DELETE");
header("Access-Control-Allow-Headers: DNT,X-CustomHeader,Keep-Alive,Content-Type,Origin,Authentication,Authorization,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control");


## 주의사항 : cli 방식으로도 인클루드 되므로 $_SERVER 변수를 사용할 경우 @를 붙여 디버깅 패스처리하시오 !!!
## 2019-01-21 암호화 라이브러리(crypt.lib.php) 추가

if( @$_SERVER['HTTP_USER_AGENT'] ) {

	// header('Access-Control-Allow-Origin: *'); // .htaccess 에 규정
	// 자바스크립트에서 go(-1) 함수를 쓰면 폼값이 사라질때 해당 폼의 상단에 사용하면
	// 캐쉬의 내용을 가져옴. 완전한지는 검증되지 않음

	$gmnow = gmdate('D, d M Y H:i:s') . ' GMT';

	header('Content-Type: text/html; charset=utf-8');
	header('Last-Modified: ' . $gmnow);
//header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');			// rfc2616 - Section 14.21

	/* 2019-12-11 전승찬 수정 */
	if( preg_match("/(^hellofunding\.co\.kr)/i", $_SERVER['HTTP_HOST']) ) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: //www.hellofunding.co.kr:8080".$_SERVER['REQUEST_URI']);
	}
	//if($_SERVER['REQUEST_SCHEME']!='https') {
	//	header("Location: https://www.hellofunding.co.kr".$_SERVER['REQUEST_URI']);
	//}
	/* ================== */

}


// 특정페이지들은 파라미터나 조작된 주소로 들어오면 원래의 페이지로 리턴
$NOPARAMPAGE = array(
	'/investment/guide.php',
	'/company/provision.php',
	'/company/privacy.php',
	'/company/provision2.php',
	'/company/codeofethics.php',
	'/reserve/contact_email.php'
);

if( in_array(@$_SERVER['SCRIPT_NAME'], $NOPARAMPAGE) ) {
	if(@$_SERVER['QUERY_STRING'] || @$_SERVER['PATH_INFO'] || @$_SERVER['PATH_TRANSLATED']) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: " . $_SERVER['SCRIPT_NAME']);
		exit;
	}
}


/*******************************************************************************
** 공통 변수, 상수, 코드
*******************************************************************************/
error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING );

// 보안설정이나 프레임이 달라도 쿠키가 통하도록 설정
@header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');

if (!defined('G5_SET_TIME_LIMIT')) define('G5_SET_TIME_LIMIT', 0);
@set_time_limit(G5_SET_TIME_LIMIT);

if( @$_SERVER['HTTP_X_FORWARDED_FOR'] ) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];  // KISA DDOS ZONE 이용시 클라이언트 IP가 KISA 방화벽 IP로만 전달되는것을 방지하기 위한 설정

//==========================================================================================================================
// extract($_GET); 명령으로 인해 page.php?_POST[var1]=data1&_POST[var2]=data2 와 같은 코드가 _POST 변수로 사용되는 것을 막음
//--------------------------------------------------------------------------------------------------------------------------
$ext_arr = array (
	'PHP_SELF', '_ENV', '_GET', '_POST', '_FILES', '_SERVER', '_COOKIE', '_SESSION', '_REQUEST',
	'HTTP_ENV_VARS', 'HTTP_GET_VARS', 'HTTP_POST_VARS', 'HTTP_POST_FILES', 'HTTP_SERVER_VARS',
	'HTTP_COOKIE_VARS', 'HTTP_SESSION_VARS', 'GLOBALS'
);

$ext_cnt = count($ext_arr);

// POST, GET 으로 선언된 전역변수가 있다면 unset() 시킴
for($i=0; $i<$ext_cnt; $i++) {
	if(isset($_GET[$ext_arr[$i]]))  unset($_GET[$ext_arr[$i]]);
	if(isset($_POST[$ext_arr[$i]])) unset($_POST[$ext_arr[$i]]);
}
//==========================================================================================================================

// IP 유효성 검사
function IPCheck($ip) {
	$result = false;
	if( preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/',$ip) ) {
		$result = true;
	}
	return $result;
}

function g5_path()
{
	$chroot = substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], dirname(__FILE__)));
	$result['path'] = str_replace('\\', '/', $chroot.dirname(__FILE__));
	$tilde_remove = preg_replace('/^\/\~[^\/]+(.*)$/', '$1', $_SERVER['SCRIPT_NAME']);
	$document_root = str_replace($tilde_remove, '', $_SERVER['SCRIPT_FILENAME']);
	$pattern = '/' . preg_quote($document_root, '/') . '/i';
	$root = preg_replace($pattern, '', $result['path']);
	$port = ($_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) ? '' : ':'.$_SERVER['SERVER_PORT'];
	$http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 's' : '') . '://';
	$user = str_replace(preg_replace($pattern, '', $_SERVER['SCRIPT_FILENAME']), '', $_SERVER['SCRIPT_NAME']);
	$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	if(isset($_SERVER['HTTP_HOST']) && preg_match('/:[0-9]+$/', $host))
		$host = preg_replace('/:[0-9]+$/', '', $host);
	$host = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*]/", '', $host);
	$result['url'] = $http.$host.$port.$user.$root;
	return $result;
}

$g5_path = g5_path();

include_once($g5_path['path'].'/config.php');   // 설정 파일

unset($g5_path);


$httpOnly  = true;
$secure    = (@$_SERVER['REQUEST_SCHEME']=='https') ? true : false;

if( trim($_GET[md5('token')]) ) {
	$token_str = base64_decode($_GET[md5('token')]);
	setcookie("app_token", $token_str, 0, '/', G5_COOKIE_DOMAIN, $httpOnly, $secure);
}

if( trim($_GET[md5('ver')]) ) {
	$ver_str = base64_decode($_GET[md5('ver')]);
	setcookie("app_version", $ver_str, 0, '/', G5_COOKIE_DOMAIN, $httpOnly, $secure);
}


// multi-dimensional array에 사용자지정 함수적용
function array_map_deep($fn, $array)
{
	if(is_array($array)) {
		foreach($array as $key => $value) {
			if(is_array($value)) {
				$array[$key] = array_map_deep($fn, $value);
			} else {
				$array[$key] = call_user_func($fn, $value);
			}
		}
	} else {
		$array = call_user_func($fn, $array);
	}

	return $array;
}


// SQL Injection 대응 문자열 필터링
function sql_escape_string($str)
{
	if(defined('G5_ESCAPE_PATTERN') && defined('G5_ESCAPE_REPLACE')) {
		$pattern = G5_ESCAPE_PATTERN;
		$replace = G5_ESCAPE_REPLACE;

		if($pattern)
			$str = preg_replace($pattern, $replace, $str);
	}

	$str = call_user_func('addslashes', $str);

	return $str;
}


//==============================================================================
// SQL Injection 등으로 부터 보호를 위해 sql_escape_string() 적용
//------------------------------------------------------------------------------
// magic_quotes_gpc 에 의한 backslashes 제거
if (get_magic_quotes_gpc()) {
	$_POST    = array_map_deep('stripslashes',  $_POST);
	$_GET     = array_map_deep('stripslashes',  $_GET);
	$_COOKIE  = array_map_deep('stripslashes',  $_COOKIE);
	$_REQUEST = array_map_deep('stripslashes',  $_REQUEST);
}

// sql_escape_string 적용
$_POST    = array_map_deep(G5_ESCAPE_FUNCTION,  $_POST);
$_GET     = array_map_deep(G5_ESCAPE_FUNCTION,  $_GET);
$_COOKIE  = array_map_deep(G5_ESCAPE_FUNCTION,  $_COOKIE);
$_REQUEST = array_map_deep(G5_ESCAPE_FUNCTION,  $_REQUEST);
//==============================================================================


// PHP 4.1.0 부터 지원됨
// php.ini 의 register_globals=off 일 경우
@extract($_GET);
@extract($_POST);
@extract($_SERVER);


// 완두콩님이 알려주신 보안관련 오류 수정
// $member 에 값을 직접 넘길 수 있음
$config = array();
$member = array();
$board  = array();
$group  = array();
$g5     = array();


//==============================================================================
// 공통
//------------------------------------------------------------------------------
$dbconfig_file = G5_DATA_PATH.'/'.G5_DBCONFIG_FILE;
if (file_exists($dbconfig_file)) {

	include_once($dbconfig_file);
	include_once(G5_LIB_PATH.'/common.lib.php');	// 공통 라이브러리
	include_once(G5_LIB_PATH.'/crypt.lib.php');		// 암호화 라이브러리 2019-01-21
	include_once(G5_LIB_PATH.'/investment.lib.php');

	$connect_db = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD) or die('MySQL Connect Error!!!');
	$select_db  = sql_select_db(G5_MYSQL_DB, $connect_db) or die('MySQL DB Error!!!');

	$g5['connect_db'] = $connect_db;
	sql_set_charset('utf8', $connect_db);
	if(defined('G5_MYSQL_SET_MODE') && G5_MYSQL_SET_MODE) sql_query("SET SESSION sql_mode = ''");
	if(defined('G5_TIMEZONE')) sql_query("SET time_zone = '".G5_TIMEZONE."'");

	$IP_AREA = IP_AREA($_SERVER['REMOTE_ADDR']);		// 접속지정보 추가 : 2018-08-20

	include_once(G5_DATA_PATH.'/sms_dbconfig.php');		// 메세지 발송 외부 DB설정


	// [파트너ID 체크]
	//  기본 => 홈, 투자리스트, 회사소개, 대출하기(아파트론) 에서 호출
	//  추가적인 파트너ID 체크 설정은 각 페이지에 pid_check.inc.php 을 include 하면 된다.
	if( preg_match("/(\/public_html\/index\.php|\/investment\/invest_list\.php|\/investment\/investment\.php|\/company\/introduce\.php|\/aptloan\/index\.php|\/event\/)/i", $_SERVER['SCRIPT_FILENAME']) ) {
		include_once(G5_PATH.'/pid_check.inc.php');
	}

}
else {

?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>시스템 오류 입니다.</title>
<link rel="stylesheet" href="install_/install.css">
</head>
<body>

<h1>데이터베이스 오류 입니다.</h1>
<div class="ins_inner">
	<p>DBCONFIG_FILE 을 찾을 수 없습니다.</p>
	<p>잠시 후 다시 접속하십시요.</p>
</div>

</body>
</html>

<?
	exit;
}


if( $CONF['flatform'] == 'app') {
	// 앱서비스 종료 공지
	include_once(G5_PATH."/system_notice/20210708/app_notice.php");
}


//==============================================================================
//==============================================================================
// SESSION 설정
//------------------------------------------------------------------------------
$session_maxlifetime = 3600 * 3;
ini_set("session.use_trans_sid",  0);												// PHPSESSID를 자동으로 넘기지 않음
ini_set("url_rewriter.tags",      "");											// 링크에 PHPSESSID가 따라다니는것을 무력화함 (해뜰녘님께서 알려주셨습니다.)
ini_set("session.cookie_domain",  G5_COOKIE_DOMAIN);
ini_set("session.cache_expire",   180);											// 세션 캐쉬 보관시간 (분단위)
ini_set("session.gc_maxlifetime", $session_maxlifetime);		// 아무런 요청이 없을 때 자동으로 세션이 삭제될 시간 (초단위)
ini_set("session.gc_probability", 1);												// session.gc_probability는 session.gc_divisor와 연계하여 gc(쓰레기 수거) 루틴의 시작 확률을 관리합니다. 기본값은 1입니다. 자세한 내용은 session.gc_divisor를 참고하십시오.
ini_set("session.gc_divisor",     100);											// session.gc_divisor는 session.gc_probability와 결합하여 각 세션 초기화 시에 gc(쓰레기 수거) 프로세스를 시작할 확률을 정의합니다. 확률은 gc_probability/gc_divisor를 사용하여 계산합니다. 즉, 1/100은 각 요청시에 GC 프로세스를 시작할 확률이 1%입니다. session.gc_divisor의 기본값은 100입니다.

session_save_path(G5_SESSION_PATH);

if(isset($SESSION_CACHE_LIMITER))
	@session_cache_limiter($SESSION_CACHE_LIMITER);
else
	@session_cache_limiter("no-cache, must-revalidate");

session_set_cookie_params(0, '/', G5_COOKIE_DOMAIN, (@$_SERVER['REQUEST_SCHEME']=='https')?true:false, true);

@session_start();
$session_name = session_name();

if(($session_id = $_COOKIE[$session_name]) != false) {
	@session_start();																					// PHPSESSID가 이미 세팅되어 있고 이 값이 비어있지 않은 경우 세션이 이미 사용중이다. 이 경우 무조건 session_start() 호출
}
else {
	register_shutdown_function('lazy_session_start');					// 현재 세션이 열려있지 않은 상태이다. 모듈 어딘가에서 $_SESSION값을 세팅하고 바로 끝내버리는 경우도 있으므로, 이를 위해서 lazy_session_start를 shutdown_function으로 등록한다.
}


//==============================================================================
// 사용기기 설정
// config.php G5_SET_DEVICE 설정에 따라 사용자 화면 제한됨
// pc 설정 시 모바일 기기에서도 PC화면 보여짐
// mobile 설정 시 PC에서도 모바일화면 보여짐
// both 설정 시 접속 기기에 따른 화면 보여짐
//------------------------------------------------------------------------------
$is_mobile = false;
$set_device = true;
if(defined('G5_SET_DEVICE')) {
	switch(G5_SET_DEVICE) {
		case 'pc':
			$is_mobile  = false;
			$set_device = false;
		break;
		case 'mobile':
			$is_mobile  = true;
			$set_device = false;
		break;
		default:
		break;
	}
}

//==============================================================================
// Mobile 모바일 설정
// 쿠키에 저장된 값이 모바일이라면 브라우저 상관없이 모바일로 실행
// 그렇지 않다면 브라우저의 HTTP_USER_AGENT 에 따라 모바일 결정
// G5_MOBILE_AGENT : config.php 에서 선언
//------------------------------------------------------------------------------
$was_mobile = is_mobile();
if(G5_USE_MOBILE && $set_device) {
	if($_REQUEST['device']=='pc')							$force_mobile = false;
	else if($_REQUEST['device']=='mobile')		$force_mobile = true;
	else if(isset($_SESSION['ss_is_mobile']))	$force_mobile = $_SESSION['ss_is_mobile'];
}
else {
	$set_device = false;
}

$is_mobile = isset($force_mobile) ? $force_mobile : $was_mobile;

if($set_device && isset($force_mobile)) {
	if($is_mobile != $was_mobile)
		$_SESSION['ss_is_mobile'] = $force_mobile;
	else
		unset($_SESSION['ss_is_mobile']);
}
define('G5_IS_MOBILE', $is_mobile);
define('G5_DEVICE_BUTTON_DISPLAY', $set_device);
if(G5_IS_MOBILE) {
	$g5['mobile_path'] = G5_PATH.'/'.$g5['mobile_dir'];
}

// 앱으로 실행시의 별도 세션 발행
if($_SERVER['HTTP_X_REQUESTED_WITH']=='kr.webadsky.hellofunding') {

	ini_set("session.gc_maxlifetime", 86400*7);

	if(!preg_match("/apps/i", $_COOKIE['PHPSESSID'])) {
		session_destroy();
		setcookie("PHPSESSID", "", time()-3600, "/");
		session_commit();

		$_CONF['SESS_ID'] = uniqid('apps' . date('ymd'));
		session_id($_CONF['SESS_ID']);
	}
	session_start();

	$app_os      = ( preg_match("/android/i", $_SERVER['REMOTE_ADDR']) ) ? "android" : "apple";
	$app_key     = base64_decode($_GET[md5('ver')]);
	$app_version = base64_decode($_GET[md5('ver')]);

}

if( preg_match("/220\.117\.134/", $_SERVER['REMOTE_ADDR']) && preg_match("/iphone/i", $_SERVER['HTTP_USER_AGENT']) ) {
	if(!$_COOKIE['renewal_mode']) {
		setcookie("renewal_mode", true, 0, "/", G5_COOKIE_DOMAIN, true, true);
		//echo "<script>location.href='/';</script>";  // 카카오페이에서 본인인증 SMS 오류로 주석처리함 전승찬 2021-06-02 (kakaoweb2 에서 요청)
	}
}

/*
// 와우스타 요청에 대한 별도 세션 발행
if( preg_match("/dev2\.wow4989\.co\.kr|wowstar\.co\.kr/i", $_SERVER['HTTP_REFERER']) || preg_match("/\/syndicate\/wowstar/i", $_SERVER['PHP_SELF']) ) {
	if($_GET['SESS_KEY']) {
		if($_GET['SESS_KEY']!=$_COOKIE['PHPSESSID']) {
			session_destroy();
			setcookie("PHPSESSID", "", time()-3600, "/");
			session_commit();

			$_CONF['SESS_ID'] = $_GET['SESS_KEY'];
			session_id($_CONF['SESS_ID']);
			session_start();
		}
	}
	else {
		if(!preg_match("/wowstar/i", $_COOKIE['PHPSESSID'])) {
			session_destroy();
			setcookie("PHPSESSID", "", time()-3600, "/");
			session_commit();

			$_CONF['SESS_ID'] = uniqid('wowstar' . date('ymd'));
			session_id($_CONF['SESS_ID']);
			session_start();
		}
	}
}
*/

//==============================================================================


//==============================================================================
// 공용 변수
//------------------------------------------------------------------------------
// 기본환경설정
// 기본적으로 사용하는 필드만 얻은 후 상황에 따라 필드를 추가로 얻음
$config = sql_fetch("SELECT * FROM {$g5['config_table']}");

if($_REQUEST['asp']=="chosun") {
	$config['cf_theme'] = "chosun";
}

define('G5_HTTP_BBS_URL',  https_url(G5_BBS_DIR, false));
define('G5_HTTPS_BBS_URL', https_url(G5_BBS_DIR, true));
if ($config['cf_editor'])
	define('G5_EDITOR_LIB', G5_EDITOR_PATH."/{$config['cf_editor']}/editor.lib.php");
else
	define('G5_EDITOR_LIB', G5_LIB_PATH."/editor.lib.php");

define('G5_CAPTCHA_DIR',	!empty($config['cf_captcha']) ? $config['cf_captcha'] : 'kcaptcha');
define('G5_CAPTCHA_URL',	G5_PLUGIN_URL.'/'.G5_CAPTCHA_DIR);
define('G5_CAPTCHA_PATH',   G5_PLUGIN_PATH.'/'.G5_CAPTCHA_DIR);

// 4.00.03 : [보안관련] PHPSESSID 가 틀리면 로그아웃한다.
if (isset($_REQUEST['PHPSESSID']) && $_REQUEST['PHPSESSID'] != session_id())
	goto_url(G5_BBS_URL.'/logout.php');

// QUERY_STRING
$qstr = '';

if (isset($_REQUEST['sca']))  {
	$sca = clean_xss_tags(trim($_REQUEST['sca']));
	if($sca) {
		$sca = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*]/", "", $sca);
		$qstr.= '&amp;sca=' . urlencode($sca);
	}
}
else {
	$sca = '';
}

if (isset($_REQUEST['sfl']))  {
	$sfl = trim($_REQUEST['sfl']);
	$sfl = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*\s]/", "", $sfl);
	if ($sfl)
		$qstr .= '&amp;sfl=' . urlencode($sfl); // search field (검색 필드)
} else {
	$sfl = '';
}


if (isset($_REQUEST['stx']))  { // search text (검색어)
	$stx = get_search_string(trim($_REQUEST['stx']));
	if ($stx || $stx === '0')
		$qstr .= '&amp;stx=' . urlencode(cut_str($stx, 20, ''));
} else {
	$stx = '';
}

if (isset($_REQUEST['sst']))  {
	$sst = trim($_REQUEST['sst']);
	$sst = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*\s]/", "", $sst);
	if ($sst)
		$qstr .= '&amp;sst=' . urlencode($sst); // search sort (검색 정렬 필드)
} else {
	$sst = '';
}

if (isset($_REQUEST['sod']))  { // search order (검색 오름, 내림차순)
	$sod = preg_match("/^(asc|desc)$/i", $sod) ? $sod : '';
	if ($sod)
		$qstr .= '&amp;sod=' . urlencode($sod);
} else {
	$sod = '';
}

if (isset($_REQUEST['sop']))  { // search operator (검색 or, and 오퍼레이터)
	$sop = preg_match("/^(or|and)$/i", $sop) ? $sop : '';
	if ($sop)
		$qstr .= '&amp;sop=' . urlencode($sop);
} else {
	$sop = '';
}

if (isset($_REQUEST['spt']))  { // search part (검색 파트[구간])
	$spt = (int)$spt;
	if ($spt)
		$qstr .= '&amp;spt=' . urlencode($spt);
} else {
	$spt = '';
}

if (isset($_REQUEST['page'])) { // 리스트 페이지
	$page = (int)$_REQUEST['page'];
	if ($page)
		$qstr .= '&amp;page=' . urlencode($page);
} else {
	$page = '';
}

if (isset($_REQUEST['w'])) {
	$w = substr($w, 0, 2);
} else {
	$w = '';
}

if (isset($_REQUEST['wr_id'])) {
	$wr_id = (int)$_REQUEST['wr_id'];
} else {
	$wr_id = 0;
}

if (isset($_REQUEST['bo_table'])) {
	$bo_table = preg_replace('/[^a-z0-9_]/i', '', trim($_REQUEST['bo_table']));
	$bo_table = substr($bo_table, 0, 20);
} else {
	$bo_table = '';
}

// URL ENCODING
if (isset($_REQUEST['url'])) {
	$url = strip_tags(trim($_REQUEST['url']));
	$urlencode = urlencode($url);
} else {
	$url = '';
	$urlencode = urlencode($_SERVER['REQUEST_URI']);
	if (G5_DOMAIN) {
		$p = @parse_url(G5_DOMAIN);
		$urlencode = G5_DOMAIN.urldecode(preg_replace("/^".urlencode($p['path'])."/", "", $urlencode));
	}
}

if (isset($_REQUEST['gr_id'])) {
	if (!is_array($_REQUEST['gr_id'])) {
		$gr_id = preg_replace('/[^a-z0-9_]/i', '', trim($_REQUEST['gr_id']));
	}
} else {
	$gr_id = '';
}
//===================================

if ($_SESSION['ss_mb_id']) { // 로그인중이라면
	//if ($_SESSION['ss_mb_id']=="romrom") {
		include_once($_SERVER["DOCUMENT_ROOT"].'/lib/p2pctr_svc.lib.php');
	//}
}

// 자동로그인 부분에서 첫로그인에 포인트 부여하던것을 로그인중일때로 변경하면서 코드도 대폭 수정하였습니다.
if ($_SESSION['ss_mb_id']) { // 로그인중이라면
	$member = get_member($_SESSION['ss_mb_id']);

	// 차단된 회원이면 ss_mb_id 초기화
	if($member['mb_intercept_date'] && $member['mb_intercept_date'] <= date("Ymd", G5_SERVER_TIME)) {
		set_session('ss_mb_id', '');
		$member = array();
	} else {
		// 오늘 처음 로그인 이라면
		if (substr($member['mb_today_login'], 0, 10) != G5_TIME_YMD) {
			// 첫 로그인 포인트 지급
			//insert_point($member['mb_id'], $config['cf_login_point'], G5_TIME_YMD.' 첫로그인', '@login', $member['mb_id'], G5_TIME_YMD);

			// 오늘의 로그인이 될 수도 있으며 마지막 로그인일 수도 있음
			// 해당 회원의 접근일시와 IP 를 저장
			$sql = " update {$g5['member_table']} set mb_today_login = '".G5_TIME_YMDHIS."', mb_login_ip = '{$_SERVER['REMOTE_ADDR']}' where mb_id = '{$member['mb_id']}' ";
			sql_query($sql);
		}
	}
}
else {
	// 자동로그인 ---------------------------------------
	// 회원아이디가 쿠키에 저장되어 있다면 (3.27)
	if ($tmp_mb_id = get_cookie('ck_mb_id')) {

		$tmp_mb_id = substr(preg_replace("/[^a-zA-Z0-9_]*/", "", $tmp_mb_id), 0, 20);
		// 최고관리자는 자동로그인 금지
		if (strtolower($tmp_mb_id) != strtolower($config['cf_admin'])) {
			$sql = "SELECT mb_password, mb_intercept_date, mb_leave_date, mb_email_certify FROM {$g5['member_table']} WHERE mb_id = '{$tmp_mb_id}'";
			$row = sql_fetch($sql);
			if($row['mb_password']){
				$key = md5($_SERVER['SERVER_ADDR'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $row['mb_password']);
				// 쿠키에 저장된 키와 같다면
				$tmp_key = get_cookie('ck_auto');
				if ($tmp_key === $key && $tmp_key) {
					// 차단, 탈퇴가 아니고 메일인증이 사용이면서 인증을 받았다면
					if ($row['mb_intercept_date'] == '' &&
						$row['mb_leave_date'] == '' &&
						(!$config['cf_use_email_certify'] || preg_match('/[1-9]/', $row['mb_email_certify'])) ) {
						// 세션에 회원아이디를 저장하여 로그인으로 간주
						set_session('ss_mb_id', $tmp_mb_id);

						// 페이지를 재실행
						echo "<script type='text/javascript'> window.location.reload(); </script>";
						exit;
					}
				}
			}
			// $row 배열변수 해제
			unset($row);
		}
	}
	// 자동로그인 end ---------------------------------------
}


$write = array();
$write_table = "";
if ($bo_table) {
	$board = sql_fetch("SELECT * FROM {$g5['board_table']} WHERE bo_table = '$bo_table'");
	if ($board['bo_table']) {
		set_cookie("ck_bo_table", $board['bo_table'], 86400 * 1);
		$gr_id = $board['gr_id'];
		$write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
		//$comment_table = $g5['write_prefix'] . $bo_table . $g5['comment_suffix']; // 코멘트 테이블 전체이름
		if (isset($wr_id) && $wr_id)
			$write = sql_fetch("SELECT * FROM $write_table WHERE wr_id = '$wr_id'");
	}
}

if ($gr_id) {
	$group = sql_fetch("SELECT * FROM {$g5['group_table']} WHERE gr_id = '$gr_id'");
}


// 회원, 비회원 구분
$is_admin = $is_member = $is_guest = false;
if($member['mb_id']) {
	$is_member = true;
	$is_admin = is_admin($member['mb_id']);
	$member['mb_dir'] = substr($member['mb_id'], 0, 2);
}
else {
	$is_guest = true;
	//$member['mb_id'] = '';
	//$member['mb_level'] = ''; // 비회원의 경우 회원레벨을 가장 낮게 설정
}


if ($is_admin != 'super') {
	// 접근가능 IP
	$cf_possible_ip = trim($config['cf_possible_ip']);
	if ($cf_possible_ip) {
		$is_possible_ip = false;
		$pattern = explode("\n", $cf_possible_ip);
		for ($i=0; $i<count($pattern); $i++) {
			$pattern[$i] = trim($pattern[$i]);
			if (empty($pattern[$i]))
				continue;

			$pattern[$i] = str_replace(".", "\.", $pattern[$i]);
			$pattern[$i] = str_replace("+", "[0-9\.]+", $pattern[$i]);
			$pat = "/^{$pattern[$i]}$/";
			$is_possible_ip = preg_match($pat, $_SERVER['REMOTE_ADDR']);
			if ($is_possible_ip)
				break;
		}
		if (!$is_possible_ip)
			die ("접근이 가능하지 않습니다.");
	}

	// 접근차단 IP
	$is_intercept_ip = false;
	$pattern = explode("\n", trim($config['cf_intercept_ip']));
	for ($i=0; $i<count($pattern); $i++) {
		$pattern[$i] = trim($pattern[$i]);
		if (empty($pattern[$i]))
			continue;

		$pattern[$i] = str_replace(".", "\.", $pattern[$i]);
		$pattern[$i] = str_replace("+", "[0-9\.]+", $pattern[$i]);
		$pat = "/^{$pattern[$i]}$/";
		$is_intercept_ip = preg_match($pat, $_SERVER['REMOTE_ADDR']);
		if ($is_intercept_ip)
			die ("접근 불가합니다.");
	}
}


// 테마경로
if(defined('_THEME_PREVIEW_') && _THEME_PREVIEW_ === true) {
	$config['cf_theme'] = trim($_GET['theme']);
}

if(isset($config['cf_theme']) && trim($config['cf_theme'])) {
	$theme_path = G5_PATH.'/'.G5_THEME_DIR.'/'.$config['cf_theme'];
	if(is_dir($theme_path)) {
		define('G5_THEME_PATH',		$theme_path);
		define('G5_THEME_URL',		 G5_URL.'/'.G5_THEME_DIR.'/'.$config['cf_theme']);
		define('G5_THEME_MOBILE_PATH', $theme_path.'/'.G5_MOBILE_DIR);
		define('G5_THEME_LIB_PATH',	$theme_path.'/'.G5_LIB_DIR);
		define('G5_THEME_CSS_URL',	 G5_THEME_URL.'/'.G5_CSS_DIR);
		define('G5_THEME_IMG_URL',	 G5_THEME_URL.'/'.G5_IMG_DIR);
		define('G5_THEME_JS_URL',	  G5_THEME_URL.'/'.G5_JS_DIR);
	}
	unset($theme_path);
}


// 테마 설정 로드
if(is_file(G5_THEME_PATH.'/theme.config.php'))
	include_once(G5_THEME_PATH.'/theme.config.php');


// 쇼핑몰 설정
if (defined('G5_USE_SHOP') && G5_USE_SHOP)
	include_once(G5_PATH.'/shop.config.php');

//=====================================================================================
// 사용기기 설정
// 테마의 G5_THEME_DEVICE 설정에 따라 사용자 화면 제한됨
// 테마에 별도 설정이 없는 경우 config.php G5_SET_DEVICE 설정에 따라 사용자 화면 제한됨
// pc 설정 시 모바일 기기에서도 PC화면 보여짐
// mobile 설정 시 PC에서도 모바일화면 보여짐
// both 설정 시 접속 기기에 따른 화면 보여짐
//-------------------------------------------------------------------------------------
$is_mobile = false;
$set_device = true;

if(defined('G5_THEME_DEVICE') && G5_THEME_DEVICE != '') {
	switch(G5_THEME_DEVICE) {
		case 'pc':
			$is_mobile  = false;
			$set_device = false;
		break;
		case 'mobile':
			$is_mobile  = true;
			$set_device = false;
		break;
		default:
		break;
	}
}

if(defined('G5_SET_DEVICE') && $set_device) {
	switch(G5_SET_DEVICE) {
		case 'pc':
			$is_mobile  = false;
			$set_device = false;
		break;
		case 'mobile':
			$is_mobile  = true;
			$set_device = false;
		break;
		default:
		break;
	}
}
//==============================================================================

//==============================================================================
// Mobile 모바일 설정
// 쿠키에 저장된 값이 모바일이라면 브라우저 상관없이 모바일로 실행
// 그렇지 않다면 브라우저의 HTTP_USER_AGENT 에 따라 모바일 결정
// G5_MOBILE_AGENT : config.php 에서 선언
//------------------------------------------------------------------------------
if(G5_USE_MOBILE && $set_device) {
	if($_REQUEST['device']=='pc')							$is_mobile = false;
	else if($_REQUEST['device']=='mobile')		$is_mobile = true;
	else if(isset($_SESSION['ss_is_mobile']))	$is_mobile = $_SESSION['ss_is_mobile'];
	else if(is_mobile())											$is_mobile = true;
}
else {
	$set_device = false;
}

$_SESSION['ss_is_mobile'] = $is_mobile;
define('G5_IS_MOBILE', $is_mobile);
define('G5_DEVICE_BUTTON_DISPLAY', $set_device);
if(G5_IS_MOBILE) {
	$g5['mobile_path'] = G5_PATH.'/'.$g5['mobile_dir'];
}
//==============================================================================


//==============================================================================
// 스킨경로
//------------------------------------------------------------------------------
if (G5_IS_MOBILE) {
	$board_skin_path   = get_skin_path('board', $board['bo_mobile_skin']);
	$board_skin_url	   = get_skin_url('board', $board['bo_mobile_skin']);
	$member_skin_path  = get_skin_path('member', $config['cf_mobile_member_skin']);
	$member_skin_url   = get_skin_url('member', $config['cf_mobile_member_skin']);
	$new_skin_path     = get_skin_path('new', $config['cf_mobile_new_skin']);
	$new_skin_url      = get_skin_url('new', $config['cf_mobile_new_skin']);
	$search_skin_path  = get_skin_path('search', $config['cf_mobile_search_skin']);
	$search_skin_url   = get_skin_url('search', $config['cf_mobile_search_skin']);
	$connect_skin_path = get_skin_path('connect', $config['cf_mobile_connect_skin']);
	$connect_skin_url  = get_skin_url('connect', $config['cf_mobile_connect_skin']);
	$faq_skin_path     = get_skin_path('faq', $config['cf_mobile_faq_skin']);
	$faq_skin_url      = get_skin_url('faq', $config['cf_mobile_faq_skin']);
} else {
	$board_skin_path   = get_skin_path('board', $board['bo_skin']);
	$board_skin_url    = get_skin_url('board', $board['bo_skin']);
	$member_skin_path  = get_skin_path('member', $config['cf_member_skin']);
	$member_skin_url   = get_skin_url('member', $config['cf_member_skin']);
	$new_skin_path     = get_skin_path('new', $config['cf_new_skin']);
	$new_skin_url      = get_skin_url('new', $config['cf_new_skin']);
	$search_skin_path  = get_skin_path('search', $config['cf_search_skin']);
	$search_skin_url   = get_skin_url('search', $config['cf_search_skin']);
	$connect_skin_path = get_skin_path('connect', $config['cf_connect_skin']);
	$connect_skin_url  = get_skin_url('connect', $config['cf_connect_skin']);
	$faq_skin_path     = get_skin_path('faq', $config['cf_faq_skin']);
	$faq_skin_url      = get_skin_url('faq', $config['cf_faq_skin']);
}
//==============================================================================

if( @$_SERVER['HTTP_USER_AGENT'] && !$is_member ) {

	// 방문자수의 접속을 남김 (그누보드 기본)
	include_once(G5_BBS_PATH.'/visit_insert.inc.php');

	// 레퍼러 분석 데이터 생성하기
	include_once(G5_BBS_PATH.'/visit_status_insert.inc.php');

}


// 일정 기간이 지난 DB 데이터 삭제 및 최적화
include_once(G5_BBS_PATH.'/db_table.optimize.php');


// common.php 파일을 수정할 필요가 없도록 확장합니다.
$extend_file = array();
$tmp = dir(G5_EXTEND_PATH);
while ($entry = $tmp->read()) {
	// php 파일만 include 함
	if (preg_match("/(\.php)$/i", $entry))
		$extend_file[] = $entry;
}

if(!empty($extend_file) && is_array($extend_file)) {
	natsort($extend_file);

	foreach($extend_file as $file) {
		include_once(G5_EXTEND_PATH.'/'.$file);
	}
}
unset($extend_file);

if( @$_SERVER['HTTP_USER_AGENT'] ) {
	if( is_null($html_process) ) {
		$html_process = new html_process();
	}
}

//if(OFFICE_CONNECT) {
//	print_r($g5);
//}

ob_start();

?>

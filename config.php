<?php
date_default_timezone_set('Asia/Seoul');

/*
LB   : 192.168.0.56 (211.56.4.49)
www1 : 192.168.0.8 (10.22.160.29)
www2 : 192.168.0.91 (10.22.160.125)
*/

/********************
    경로 상수
********************/

/*
보안서버 도메인
포트가 있다면 도메인 뒤에 :443 과 같이 입력하세요.
보안서버주소가 없다면 공란으로 두시면 되며 보안서버주소 뒤에 / 는 붙이지 않습니다.
입력예) https://www.domain.com:443/gnuboard5
*/

define('G5_DOMAIN', '');
define('G5_HTTPS_DOMAIN', '');

$cookieDomain = preg_replace("/\:8080/", "", $_SERVER['HTTP_HOST']);
$cookieDomain = preg_replace("/(www\.|www1\.|www2\.|www-stg\.)/", ".", $cookieDomain);
define('G5_COOKIE_DOMAIN',  $cookieDomain);

define('G5_DBCONFIG_FILE',  'dbconfig.php');

define('G5_ADMIN_DIR',      'adm');
define('G5_BBS_DIR',        'bbs');
define('G5_CSS_DIR',        'css');
define('G5_DATA_DIR',       'data');
define('G5_EXTEND_DIR',     'extend');
define('G5_IMG_DIR',        'img');
define('G5_IMAGES_DIR',     'images');
define('G5_JS_DIR',         'js');
define('G5_LIB_DIR',        'lib');
define('G5_PLUGIN_DIR',     'plugin');
define('G5_SKIN_DIR',       'skin');
define('G5_CAPTCHA_DIR',    'kcaptcha');
define('G5_EDITOR_DIR',     'editor');
define('G5_MOBILE_DIR',     'mobile');
define('G5_OKNAME_DIR',     'okname');

define('G5_KCPCERT_DIR',    'kcpcert');
define('G5_LGXPAY_DIR',     'lgxpay');

define('G5_SNS_DIR',        'sns');
define('G5_SYNDI_DIR',      'syndi');
define('G5_SYNDICATE_DIR',  'syndicate');
define('G5_PHPMAILER_DIR',  'PHPMailer');
define('G5_THEME_DIR',      'theme');

if( preg_match("/itembay\.hello/", @$_SERVER['HTTP_HOST']) )     define('G5_SESSION_DIR', 'session_itembay');
else if( preg_match("/r114\.hello/", @$_SERVER['HTTP_HOST']) )   define('G5_SESSION_DIR', 'session_r114');
else if( preg_match("/chosun\.hello/", @$_SERVER['HTTP_HOST']) ) define('G5_SESSION_DIR', 'session_chosun');
else define('G5_SESSION_DIR', 'session');

// URL 은 브라우저상에서의 경로 (도메인으로 부터의)
if(G5_DOMAIN) {
	define('G5_URL', G5_DOMAIN);
}
else {
	if(isset($g5_path['url']))
		define('G5_URL', $g5_path['url']);
	else
		define('G5_URL', '');
}

if(isset($g5_path['path'])) {
	define('G5_PATH', $g5_path['path']);
}
else {
	define('G5_PATH', '');
}

define('G5_COMMUNITY_USE', false);

define('G5_ADMIN_URL',      G5_URL.'/'.G5_ADMIN_DIR);
define('G5_BBS_URL',        G5_URL.'/'.G5_BBS_DIR);
define('G5_CSS_URL',        G5_URL.'/'.G5_CSS_DIR);
define('G5_DATA_URL',       G5_URL.'/'.G5_DATA_DIR);
define('G5_IMG_URL',        G5_URL.'/'.G5_IMG_DIR);
define('G5_IMAGES_URL',     G5_URL.'/'.G5_IMAGES_DIR);
define('G5_JS_URL',         G5_URL.'/'.G5_JS_DIR);
define('G5_SKIN_URL',       G5_URL.'/'.G5_SKIN_DIR);
define('G5_PLUGIN_URL',     G5_URL.'/'.G5_PLUGIN_DIR);
define('G5_CAPTCHA_URL',    G5_PLUGIN_URL.'/'.G5_CAPTCHA_DIR);
define('G5_EDITOR_URL',     G5_PLUGIN_URL.'/'.G5_EDITOR_DIR);
define('G5_OKNAME_URL',     G5_PLUGIN_URL.'/'.G5_OKNAME_DIR);
define('G5_KCPCERT_URL',    G5_PLUGIN_URL.'/'.G5_KCPCERT_DIR);
define('G5_LGXPAY_URL',     G5_PLUGIN_URL.'/'.G5_LGXPAY_DIR);
define('G5_SNS_URL',        G5_PLUGIN_URL.'/'.G5_SNS_DIR);
define('G5_SYNDI_URL',      G5_PLUGIN_URL.'/'.G5_SYNDI_DIR);
define('G5_SYNDICATE_URL',  G5_URL.'/'.G5_SYNDICATE_DIR);
define('G5_MOBILE_URL',     G5_URL.'/'.G5_MOBILE_DIR);
define('TAX_INVOICE_URL',   G5_URL.'/LINKHUB');

// PATH 는 서버상에서의 절대경로
define('G5_ADMIN_PATH',     G5_PATH.'/'.G5_ADMIN_DIR);
define('G5_BBS_PATH',       G5_PATH.'/'.G5_BBS_DIR);
define('G5_DATA_PATH',      G5_PATH.'/'.G5_DATA_DIR);
define('G5_IMG_PATH',       G5_PATH.'/'.G5_IMG_DIR);
define('G5_IMAGES_PATH',    G5_PATH.'/'.G5_IMAGES_DIR);
define('G5_EXTEND_PATH',    G5_PATH.'/'.G5_EXTEND_DIR);
define('G5_LIB_PATH',       G5_PATH.'/'.G5_LIB_DIR);
define('G5_PLUGIN_PATH',    G5_PATH.'/'.G5_PLUGIN_DIR);
define('G5_SKIN_PATH',      G5_PATH.'/'.G5_SKIN_DIR);
define('G5_MOBILE_PATH',    G5_PATH.'/'.G5_MOBILE_DIR);
define('G5_SESSION_PATH',   G5_DATA_PATH.'/'.G5_SESSION_DIR);

define('G5_CAPTCHA_PATH',   G5_PLUGIN_PATH.'/'.G5_CAPTCHA_DIR);
define('G5_EDITOR_PATH',    G5_PLUGIN_PATH.'/'.G5_EDITOR_DIR);
define('G5_OKNAME_PATH',    G5_PLUGIN_PATH.'/'.G5_OKNAME_DIR);
define('TAX_INVOICE_PATH',  G5_PATH.'/LINKHUB');
define('G5_SYNDICATE_PATH', G5_PATH.'/'.G5_SYNDICATE_DIR);

define('G5_KCPCERT_PATH',   G5_PLUGIN_PATH.'/'.G5_KCPCERT_DIR);
define('G5_LGXPAY_PATH',    G5_PLUGIN_PATH.'/'.G5_LGXPAY_DIR);

define('G5_SNS_PATH',       G5_PLUGIN_PATH.'/'.G5_SNS_DIR);
define('G5_SYNDI_PATH',     G5_PLUGIN_PATH.'/'.G5_SYNDI_DIR);
define('G5_PHPMAILER_PATH', G5_PLUGIN_PATH.'/'.G5_PHPMAILER_DIR);

define('AML_DIR',  'AML');
define('AML_URL',  G5_URL.'/'.AML_DIR);
define('AML_PATH', G5_PATH.'/'.AML_DIR);

define('API_URL',  'http://ext.hellofunding.co.kr:8080');
//==============================================================================


//==============================================================================
// 사용기기 설정
// pc 설정 시 모바일 기기에서도 PC화면 보여짐
// mobile 설정 시 PC에서도 모바일화면 보여짐
// both 설정 시 접속 기기에 따른 화면 보여짐
//------------------------------------------------------------------------------
define('G5_SET_DEVICE', 'both');
define('G5_USE_MOBILE', true); // 모바일 홈페이지를 사용하지 않을 경우 false 로 설정
define('G5_USE_CACHE',  true); // 최신글등에 cache 기능 사용 여부


/********************
    시간 상수
********************/
// 서버의 시간과 실제 사용하는 시간이 틀린 경우 수정하세요.
// 하루는 86400 초입니다. 1시간은 3600초
// 6시간이 빠른 경우 time() + (3600 * 6);
// 6시간이 느린 경우 time() - (3600 * 6);
define('G5_SERVER_TIME',    time());
define('G5_TIME_YMDHIS',    date('Y-m-d H:i:s', G5_SERVER_TIME));
define('G5_TIME_YMD',       substr(G5_TIME_YMDHIS, 0, 10));
define('G5_TIME_HIS',       substr(G5_TIME_YMDHIS, 11, 8));

// 입력값 검사 상수 (숫자를 변경하시면 안됩니다.)
define('G5_ALPHAUPPER',      1); // 영대문자
define('G5_ALPHALOWER',      2); // 영소문자
define('G5_ALPHABETIC',      4); // 영대,소문자
define('G5_NUMERIC',         8); // 숫자
define('G5_HANGUL',         16); // 한글
define('G5_SPACE',          32); // 공백
define('G5_SPECIAL',        64); // 특수문자

// 퍼미션
define('G5_DIR_PERMISSION',  0755); // 디렉토리 생성시 퍼미션
define('G5_FILE_PERMISSION', 0644); // 파일 생성시 퍼미션

// 모바일 인지 결정 $_SERVER['HTTP_USER_AGENT']
define('G5_MOBILE_AGENT',   'phone|samsung|lgtel|mobile|[^A]skt|nokia|blackberry|android|sony');


// SMTP 설정 (Gmail 및 외부 smtp 서버 사용할때, G5_SMTP_USE_EXT 1 로 세팅)
//define('G5_SMTP', 'hello.hellofunding.co.kr');
define('G5_SMTP_USE_EXT',  1);

if(defined('G5_SMTP_USE_EXT') && G5_SMTP_USE_EXT) {

	// lib/mailer.lib.php 에서 참조

	/*
	//구글 SMTP
	define('G5_SMTP',        'smtp.gmail.com');
	define('G5_SMTP_PORT',   '587');
	define('G5_SMTP_ID',     'smtpID');
	define('G5_SMTP_PW',     'smtpPWD');
	define('G5_SMTP_SECURE', 'tls');
	*/

	/*
	//네이버 SMTP
	define('G5_SMTP',        'smtp.naver.com');
	define('G5_SMTP_PORT',   '465');		// (465|587)
	define('G5_SMTP_ID',     'naverID');
	define('G5_SMTP_PW',     'naverPWD');
	define('G5_SMTP_SECURE', 'ssl');
	*/

	//hello.hellofunding.co.kr SMTP
	define('G5_SMTP',        'mail.hellofunding.co.kr');
	define('G5_SMTP_PORT',   '25');		// (465|587)
	define('G5_SMTP_ID',     'smtpID');
	define('G5_SMTP_PW',     'smtpPWD');

	define('G5_SMTP_USER',   G5_SMTP_ID);
	define('G5_SMTP_PASS',   G5_SMTP_PW);

}



/********************
    기타 상수
********************/

// 암호화 함수 지정. 사이트 운영 중 설정을 변경하면 로그인이 안되는 등의 문제가 발생합니다.
define('G5_STRING_ENCRYPT_FUNCTION', 'sql_password');

// SQL 에러를 표시할 것인지 지정. 에러를 표시하려면 TRUE 로 변경
define('G5_DISPLAY_SQL_ERROR', FALSE);

// escape string 처리 함수 지정. addslashes 로 변경 가능
define('G5_ESCAPE_FUNCTION', 'sql_escape_string');

// sql_escape_string 함수에서 사용될 패턴
//define('G5_ESCAPE_PATTERN',  '/(and|or).*(union|select|insert|update|delete|from|where|limit|create|drop).*/i');
//define('G5_ESCAPE_REPLACE',  '');

// 게시판에서 링크의 기본개수를 말합니다. 필드를 추가하면 이 숫자를 필드수에 맞게 늘려주십시오.
define('G5_LINK_COUNT', 2);

// 썸네일 jpg Quality 설정
define('G5_THUMB_JPG_QUALITY', 90);

// 썸네일 png Compress 설정
define('G5_THUMB_PNG_COMPRESS', 5);

// 모바일 기기에서 DHTML 에디터 사용여부를 설정합니다.
define('G5_IS_MOBILE_DHTML_USE', false);

// MySQLi 사용여부를 설정합니다.
define('G5_MYSQLI_USE', true);

// Browscap 사용여부를 설정합니다.
define('G5_BROWSCAP_USE', true);

// 접속자 기록 때 Browscap 사용여부를 설정합니다.
define('G5_VISIT_BROWSCAP_USE', false);

// ip 숨김방법 설정
/* 123.456.789.012 ip의 숨김 방법을 변경하는 방법은
\\1 은 123, \\2는 456, \\3은 789, \\4는 012에 각각 대응되므로
표시되는 부분은 \\1 과 같이 사용하시면 되고 숨길 부분은 ♡등의
다른 문자를 적어주시면 됩니다.
*/
define('G5_IP_DISPLAY', '\\1.♡.\\3.\\4');

//https 통신일때 daum 주소 js
$post_api_url = (@$_SERVER['REQUEST_SCHEME']=='https') ? 'https://spi.maps.daum.net/imap/map_js_init/postcode.v2.js' : 'http://dmaps.daum.net/map_js_init/postcode.v2.js';
define('G5_POSTCODE_JS', '	<script src="'.$post_api_url.'?autoload=false"></script>');


include_once('/home/crowdfund/public_html/data/office_ipconfig.php');		//**** 오피스IP 정의 ****//

include_once('/home/crowdfund/public_html/config.service.php');		//**** 헬로펀딩 프라퍼티 ****//



?>

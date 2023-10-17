<?
include_once("../common.php");

$idx = trim($_REQUEST['idx']);

$ROW = sql_fetch("SELECT * FROM cf_sms_noti WHERE idx='".$idx."'");
//print_rr($ROW, "font-size:12px");

if($ROW['idx']=='') {
	msg_replace("게시글이 없습니다.", "/");
}
else {
	$remain_date = ($ROW['edate'] && $ROW['edate']>'0000-00-00 00:00:00') ? $ROW['edate'] : $ROW['rdate'];
	$limit_date  = date('Y-m-d', strtotime($remain_date)+86400*9);

	if( $limit_date < G5_TIME_YMD ) {
		if($is_admin) {
			echo "<div style='text-align:center;background:#FFDDDD;color:#EE0000'>". $limit_date . " 이 후 열람 불가 처리됨!</div>";
		}
		else {
			msg_replace("상품 안내기간이 만료되었습니다.", "/");
		}
	}
	else {
		if($is_admin) echo "<div style='text-align:center;background:#DDDDFF;color:#0000EE'>". $limit_date . " 까지 열람 가능!</div>";
	}
}

switch($ROW['gubun']) {
	case '2' : $title = "헬로펀딩 공지사항";  break;
	case '3' : $title = "헬로펀딩 긴급 공지"; break;
	default  : $title = "헬로펀딩 상품 안내"; break;
}

if( !$is_admin ) {

	$coo = get_cookie('noti');

	if($coo) {
		$COO = explode(",", $coo);

		$is_readed = false;
		if( in_array($idx, $COO) ) {
			$is_readed = true;
		}

		if(!$is_readed) {
			sql_query("UPDATE cf_sms_noti SET view=view+1 WHERE idx='".$idx."'");

			$add_idx = $coo . "," . $idx;
			set_cookie('noti', $add_idx, 60);
		}

	}
	else {
		sql_query("UPDATE cf_sms_noti SET view=view+1 WHERE idx='".$idx."'");
		set_cookie('noti', $idx, 60);
	}

	//echo $coo;
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=0,maximum-scale=10,user-scalable=yes" />
<meta name="HandheldFriendly" content="true" />
<meta name="format-detection" content="telephone=no" />
<meta name="theme-color" content="#073190" />
<title>헬로펀딩 | 대한민국 P2P금융의 표준</title>
<link rel="shortcut icon" type="image/x-icon" href="https://www.hellofunding.co.kr/favicon.ico?ver=20180826" />
<link rel="stylesheet" href="/theme/2018/js/jquery-ui-1.12.1/jquery-ui.min.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js" integrity="sha384-rY/jv8mMhqDabXSo+UCggqKtdmBfd3qC2/KvyTDNQ6PcUJXaxK1tMepoQda4g5vB" crossorigin="anonymous"></script>
<script src="/theme/2018/js/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<script src="/js/common.js?ver=20190218"></script>
<style>
@import url(https://fonts.googleapis.com/earlyaccess/notosanskr.css);
body {margin:0; padding:0; font-size:1.2em; font-family:'Noto Sans KR', sans-serif;}
html, h1, h2, h3, h4, h5, h6, form, fieldset, img {margin:0;padding:0;border:0}
h1, h2, h3, h4, h5, h6 {font-size:1em;font-family:'Noto Sans KR', sans-serif;}
ul,ol,li,dl,dt,dd{list-style:none;margin:0;padding:0}
label, input, button, select, img {vertical-align:middle}
input, button {margin:0;padding:0;font-family:'Noto Sans KR', sans-serif;;font-size:1em}
button {cursor:pointer}
p {margin:0;padding:0;word-break:break-all}
a:link, a:visited, a:hover, a:focus, a:active {text-decoration:none;}

#sms_noti { margin:auto; width:100%; max-width:600px; font-family:'Noto Sans KR', sans-serif; word-break:break-all; }
#sms_noti div { padding:8px; }
#sms_noti .titlebar { background:#073190; color:#FFF; font-size:1.1em; text-align:center; }
#sms_noti .subject { margin-top:20px; font-size:1.0em; font-weight:500; text-align:center; }
#sms_noti .content { margin:0 auto; width:86%; padding:15px 5% 20px; font-size:0.8em; background:#F9F9F9; }
#sms_noti .bgOn { background:#EEE; }
#sms_noti .buttonArea { margin-top:10px; }
#sms_noti .btn_green { display:inline-block; padding:8px 0; width:100%; font-family:"NG"; font-size:0.9em; color:#fff; border-radius:3px; background-color:#00C5B0; border:0; cursor:pointer; }
</style>
</head>

<body>

<div id="sms_noti">
	<div class="titlebar"><?=$title?></div>
	<div class="subject"><?=$ROW['subject']?></div>
<?
for($i=0; $i<10; $i++) {
	if($ROW["cont{$i}"]) {
		$print_text = nl2br(preg_replace("/( )/", "&nbsp;", $ROW["cont{$i}"]));
		$print_text = url_auto_link($print_text);

?>
	<div class="content <?=(($i%2)==1)?'':'bgOn'?>"><table style="width:100%;"><tr><td><?=$print_text?></td></tr></table></div>
<?
	}
}
?>

	<div class="buttonArea">
		<button type="button" onClick="location.href='/investment/invest_list.php';" class="btn_green">투자상품보기</button>
	</div>
</div>

</body>
</html>

<?

sql_close();
exit;

?>
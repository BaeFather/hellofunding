<?
###############################################################################
##  대출자플랫폼수수료현황
###############################################################################

include_once('./_common.php');

$sub_menu = "700800";
$html_title = $menu['menu700'][9][1];
if($_REQUEST['mode']=='new') {
	$g5['title'] = $html_title.' > 수취정보등록';
}
else {
	$g5['title'] = ($idx!='') ? $html_title.' > 상세보기' : $html_title.' > 목록';
}


include_once (G5_ADMIN_PATH.'/admin.head.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }


$qstr = $_SERVER['QUERY_STRING'];
if($idx) {
	$qstr = preg_replace("/&idx=([0-9]){1,10}/", "", $qstr);
}
if($page) {
	$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $qstr);
}

?>

<div class="tbl_head02 tbl_wrap" style="min-width:1500px">

<?
if($mode=='new' || $idx) {

	include_once("loaner_usefee_repay.write.php");
	echo "<br /><br />\n";

}

if($mode!='new') {
	include_once("loaner_usefee_repay.list.php");
}

?>

</div>

<?

include_once (G5_ADMIN_PATH.'/admin.tail.php');

?>
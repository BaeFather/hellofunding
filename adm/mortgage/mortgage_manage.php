<?
/**
 * 주담대 채권관리
 */
$sub_menu = "920200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
if ($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while(list($key, $value) = each($_GET)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}

$g5['title'] = $menu['menu920'][2][1];
include_once('../admin.head.php');
?>



<? include_once ('../admin.tail.php'); ?>
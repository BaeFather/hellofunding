<?
$sub_menu = "200100";
include_once('./_common.php');

while(list($k, $v) = each($_REQUEST)) { ${$k} = trim($v); }

auth_check($auth[$sub_menu], "w");


if($mb_id) {

	$MB = get_member($mb_id);
	//print_rr($MB);


	if( !$MB['mb_no'] ) { msg_go("존재하지 않는 데이터 입니다!"); }
	if( !in_array($MB['member_group'], array('F','L')) ) { msg_go("투자자/대출자 정보 없음!"); }
	if( !in_array($MB['member_type'], array('1','2')) ) { msg_go("법인/개인 정보 없음!"); }
	if( $MB['mb_level'] < 1 && $MB['mb_level'] > 5 ) { msg_go("일반회원이 아닙니다!"); }

	$mode = 'edit';

	if($MB['member_type']=='2') {
		$inc_file = ($MB['member_group']=='L') ? 'member_form_corp_loaner.php' : 'member_form_corp_investor.php';
	}
	else if($MB['member_type']=='1') {
		$inc_file = ($MB['member_group']=='L') ? 'member_form_indi_loaner.php' : 'member_form_indi_investor.php';
	}
	else {
		msg_go("회원정보가 존재하지 않습니다!");
	}

}
else {

	if( !in_array($member_group, array('F','L')) ) { msg_go("투자자/대출자 구분자 전송오류!"); }
	if( !in_array($member_type, array('1','2')) ) { msg_go("법인/개인 구분자 전송오류!"); }

	$mode = 'new';

	if($member_type=='2') {
		$inc_file = ($member_group=='L') ? 'member_form_corp_loaner.php' : 'member_form_corp_investor.php';
	}
	else {
		$inc_file = ($member_group=='L') ? 'member_form_indi_loaner.php' : 'member_form_indi_investor.php';
	}

}

$query_str = str_replace("&mb_id=$mb_id", "", $_SERVER['QUERY_STRING']);


$g5['title'] = '회원정보';
$g5['title'].= ($mb_id) ? ' 수정' : ' 등록';
$g5['title'].= ($member_group=='L' || $MB['member_group']=='L') ? ' > 대출회원' : ' > 투자회원';
$html_title = $g5['title'];


include_once (G5_ADMIN_PATH.'/admin.head.php');


if($inc_file) include_once($inc_file);


include_once (G5_ADMIN_PATH.'/admin.tail.php');

?>
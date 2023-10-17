<?

include_once("./_common.php");

check_demo();
auth_check($auth[$sub_menu], "d");


if($_POST['mb_id']) {

	$mb_id = $_POST['mb_id'];

	$MB = get_member($mb_id);

	if($member['mb_id'] == $MB['mb_id']) {
		alert("로그인 중인 관리자는 삭제 할 수 없습니다.");
	}
	else if(is_admin($MB['mb_id']) == "super") {
		alert("최고 관리자는 삭제할 수 없습니다.");
	}
	else if($MB['mb_level'] >= $member['mb_level']) {
		alert("자신보다 권한이 높거나 같은 회원은 삭제할 수 없습니다.");
	}
	else if($MB['mb_point'] > 0) {
		alert("잔여 예치금이 남아있어 삭제할 수 없습니다.");
	}

	//check_admin_token();

	// 회원자료 삭제
	member_delete($MB['mb_id']);


	if($url) {
		alert("삭제되었습니다.", $url."?".$qstr."&mb_id=".$mb_id);
	}
	else {
		alert("삭제되었습니다.", "./member_list.php?".$_SERVER['QUERY_STRING']);
	}

}
else {

	alert("회원자료가 존재하지 않습니다.");

}

?>

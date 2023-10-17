<?
include_once("_common.php");

if(!$is_member) { exit; }
if(trim($member['virtual_account2'])=='') { exit; }
if($member['insidebank_after_trans_target']=='1') { exit; }

$msg_seq = '201710272000';

$shinhan_vacct = ( trim($member['va_bank_code2']) && trim($member['virtual_account2']) ) ? true : false;
if($shinhan_vacct) {
	if($_SESSION['last_login'] > '2017-10-22') { exit; }		// 최종로그인이 10월 22일 이후인 사람 열람제외
	$CHECK_ROW = sql_fetch("SELECT idx FROM notice_read_check WHERE mb_no='".$member['mb_no']."' AND msg_seq='".$msg_seq."'");
	if($CHECK_ROW['idx']) {	exit; }		// 알림기록있으면 열람제외
	else {
		sql_query("INSERT INTO notice_read_check (msg_seq, mb_no, rdate) VALUES ('".$msg_seq."', '".$member['mb_no']."',NOW())");
	}
}
else {
	exit;
}


$PRNT['name']         = ($member['member_type']=='2') ? $member['mb_co_name'] : $member['mb_name'];
$PRNT['bank']         = $BANK[$member['va_bank_code2']];
$PRNT['bank_account'] = $member['virtual_account2'];
$PRNT['bank_name']    = $member['va_private_name2'];

?>
<div class="title">신한은행 가상계좌 발급 알림</div>
<div class="con" style="font-size:15px;">
	&nbsp; <span style="color:#284893"><?=$PRNT['name']?>님의 가상계좌 :</span>
	<div style="margin:10px 0 30px 0;border:1px solid #222;background-color:#FFFF99;padding:20px;border-radius:3px;font-size:16px;color:#284893">
		<b><?=$PRNT['bank']?> &nbsp; <?=$PRNT['bank_account']?> &nbsp; <?=$PRNT['bank_name']?></b>
	</div>
	<div style="color:#000">
	회원님의 투자금과 예치금의 안전한 관리를 위한 신한은행 제3자 예치금 관리 시스템 적용이 완료되어 <span style='color:green'>기존 가상계좌에서 신한은행 가상계좌로 자동 변경</span>되었습니다.<br>
	<span style='color:brown'>(기존 발급된 가상계좌는 이용하실 수 없습니다.)</span><br><br>
	헬로펀딩의 예치금, 투자금, 상환금은 신한은행이 직접 관리하며, 예치금의 실시간 출금이 가능하므로 더욱 안전하고 편리하게 헬로펀딩을 이용하실 수 있습니다.<br><br>
	감사합니다.
	</div>

	<div style="margin:50px 0 0 0; text-align:center;">
		<a id="noti_close" style="width:250px" class="btn_big_blue">공지내용을 확인하였습니다.</a>
	</div>

</div>

<script>
$('#noti_close').click(function() {
	$('#loading').css('display', 'block');
	setTimeout(function() {
			$('#loading').css('display', 'none');
			$.unblockUI();
		}, 5000
	);
});
</script>
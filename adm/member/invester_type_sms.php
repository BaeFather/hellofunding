<?
include_once('./_common.php');
include_once(G5_LIB_PATH.'/sms.lib.php');

auth_check($auth[$sub_menu], "w");

$html_title = "소득적격 기간만료 안내문자";
$g5['title'] = $html_title.' 발송';
?>
<?
$sms_msg = "[소득적격 정보 갱신 안내]

안녕하세요 헬로펀딩입니다.

회원님의 소득적격 투자자 유효기간이 만료되어 안내드립니다.
소득적격 투자자 유지를 원하시는 경우 아래 조건 중 해당하는 증빙서류를 6월 30일(목)까지 첨부해주세요.

◆ 증빙서류 안내 ◆

+ 사업 및 근로소득 1억원 초과 : 전년도 근로소득 원천징수 영수증

+ 이자 및 배당소득 2천만원 초과 : 전년도 종합소득 과세표준 확정신고서, 종합소득세 신고서 접수증

◆ 파일첨부 방법 ◆

+ 로그인 > 회원정보 > 투자자 유형 > 소득적격 투자자 선택 후 파일등록

6월 30일(목)까지 증빙서류가 재 첨부 되지 않은 경우 일반투자자로 변경됩니다.

감사합니다.";
?>
<div style="width:310px; border:1px solid blue;"><pre style="white-space: pre-wrap;"><?=$sms_msg?></pre></div>
<br/><br/><br/>
<input type="button" value="문자 발송" onclick="send_lms();"/>

<form name="ff" method="post">
<input type="hidden" value="" name="send_yn"/>
</form>

<table class="table table-striped table-bordered table-hover" style="padding-top:0; font-size:12px;">

<?
$ymd = date("Y-m-d");


$sql = "SELECT A.*, B.mb_name, B.mb_hp
		FROM (
			SELECT mb_no, max(rights_end_date) max_rights_end_date 
			  FROM investor_type_change_request 
			 WHERE (judge_memo NOT LIKE '%$ymd 안내문자발송%' OR judge_memo='' OR judge_memo IS NULL)  GROUP BY mb_no
			 ) A
		LEFT JOIN g5_member B ON(A.mb_no=B.mb_no)
		WHERE B.member_investor_type='2' 
		AND B.mb_level='1'
		AND max_rights_end_date<'$ymd' 
		ORDER BY mb_no 
		";

// TEST
/*
$sql = "SELECT A.*, B.mb_name, B.mb_hp
		FROM (SELECT mb_no, max(rights_end_date) max_rights_end_date FROM investor_type_change_request GROUP BY mb_no) A
		LEFT JOIN g5_member B ON(A.mb_no=B.mb_no)
		WHERE B.mb_no= '15130'
		";
*/
$res = sql_query($sql);
$cnt = sql_num_rows($res);

if ($send_yn=="Y") {

	$send_id = get_sms_send_id_smtnt();
	$from_hp = $CONF['admin_sms_number'];

	$send_date = ""; // 즉시발송

}


for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);

	$mb_hp2 = masterDecrypt($row['mb_hp'], false);

	unset($row1);
	$sql1 = "SELECT * FROM investor_type_change_request WHERE mb_no='$row[mb_no]' AND rights_end_date='$row[max_rights_end_date]'";
	$res1 = sql_query($sql1);
	$row1 = sql_fetch_array($res1);

	if ($send_yn=="Y") {
		
		$mb_hp = masterDecrypt($row['mb_hp'], false);
		//$mb_hp = "01086246176";
//		$mb_hp = "01067241409";
die("개발팀에 문의주세요. $mb_hp");

		$sended_yn = unit_sms_send_smtnt($from_hp, $mb_hp, $sms_msg, $send_date, $send_id);
//		die();

		$up_sql = "UPDATE investor_type_change_request SET judge_memo = CONCAT_WS('',judge_memo,'<p>$ymd 안내문자발송</p>') WHERE idx='$row1[idx]'";
		//echo $up_sql;
		sql_query($up_sql);

		//echo "<pre>";echo "($from_hp, $mb_hp, $sms_msg, $send_date, $send_id)";echo "<pre/>";
		//echo "$sended_yn";
		//die();
	}
	?>
	<tr>
		<td><?=$i+1?></td>
		<td style="text-aling:center;"><?=$row["mb_no"]?></td>
		<td><?=$row["mb_name"]?></td>
		<td><?=$mb_hp2?></td>
		<td><?=$row1["rights_start_date"]?> ~ <?=$row1["rights_end_date"]?></td>
		<td><?=$mb_hp?></td>
		<td><?=$sended_yn?></td>
	</tr>
	<?
}
?>
</tabel>

<script>
function send_lms() {
	alert("개발팀에 문의 주세요.");
	return;
	var yn = confirm("LMS를 발송하시겠습니까?");
	var f = document.ff;

	if (yn) {
		f.send_yn.value="Y";
		f.submit();
	}
}
</script>
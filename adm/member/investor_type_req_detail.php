<?
###############################################################################
##   - 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
###############################################################################

$sub_menu = '200400';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "w");

$html_title = "개인투자자 승인";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');

// GET 받은 데이터를 변수화
foreach($_GET as $k=>$v) {
	$$_GET[$k] = $v;
}

$sql = "
	SELECT
		A.*,
		B.mb_id, B.mb_name, B.mb_hp, B.mb_email, LEFT(B.mb_datetime, 10) AS mb_datetime, B.member_investor_type
	FROM
		investor_type_change_request A
	LEFT JOIN
		g5_member B
	ON
		A.mb_no=B.mb_no
	WHERE
		idx='$idx'";
$DATA = sql_fetch($sql);
if(!$DATA) { alert('잘못된 경로 입니다.'); }
$DATA['mb_hp'] = masterDecrypt($DATA['mb_hp'], false);

if($DATA['order_type']=='2')      $print_order_type = "소득적격 투자자";
else if($DATA['order_type']=='3') $print_order_type = "전문 투자자";

//첨부파일 가져오기
$fsql  = "SELECT fname, description FROM investor_type_change_request_file WHERE req_idx='".$DATA['idx']."' ORDER BY idx";
$fres  = sql_query($fsql);
$frcount = sql_num_rows($fres);

$attach_file_tag = "";
for($x=0,$y=1; $x<$frcount; $x++,$y++) {
	$frow = sql_fetch_array($fres);
	$file_path = '/data/member/investor/'. $frow['fname'];
	$attach_file_tag.= "<a href='$file_path' target='_blank' title='".addSlashes($frow['description'])."' class='btn btn-success btn-mini'>$y</a>\n";
}

$tmp_member = sql_fetch("SELECT mb_name FROM g5_member WHERE mb_no='".$DATA['appr_mb_no']."'");
$appr_mb_name = $tmp_member['mb_name'];


// 블라인드 처리
$blind_mb_hp    = (strlen($DATA['mb_hp']) > 4) ? substr($DATA['mb_hp'], 0, strlen($DATA['mb_hp'])-4) . "****" : $DATA['mb_hp'];

if($_SESSION['ss_accounting_admin']) {
	$full_mb_hp = $DATA['mb_hp'];
	$copy_mb_hp = "onClick=\"copy_trackback('".$full_mb_hp."');\"";
}
else {
	$full_mb_hp = $copy_mb_hp = '';
}

?>

<style>
.btn-mini { padding:0;width:25px;height:25px;line-height:24px; border-radius:20px; }
</style>

<script src="/adm/js/jquery.form.js"></script>
<script>
$(function() {
	$(".datepicker").datepicker({
		changeYear: false,
		changeMonth: false,
		monthNames: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
		dateFormat: 'yy-mm-dd'
	});
});
</script>

<div class="tbl_head02 tbl_wrap">

	<table width="100%">
		<colgroup>
			<col width="20%">
			<col width="80%">
		</colgroup>

		<form name="frmEdit" method="post" action="investor_type_req_proc.php" onSubmit="return formSubmit();">
		<input type="hidden" name="mb_no" value="<?=$DATA['mb_no']?>">
		<input type="hidden" name="idx" value="<?=$idx?>">
		<input type="hidden" name="qstr" value="<?=$_SERVER['QUERY_STRING']?>">
		<tr>
			<th scope="col">아이디</th>
			<td><?=$DATA['mb_id']?></td>
		</tr>
		<tr>
			<th scope="col">성 명</th>
			<td><?=$DATA['mb_name']?></td>
		</tr>
		<tr>
			<th scope="col">휴대전화</th>
			<td><span id="hp<?=$i?>" onMouseOver="swapText('hp<?=$i?>','<?=$full_mb_hp?>');" onMouseOut="swapText('hp<?=$i?>','<?=$blind_mb_hp?>');" style="cursor:pointer" <?=$copy_mb_hp?>><?=$blind_mb_hp?></span></td>
		</tr>
		<tr>
			<th scope="col">이메일</th>
			<td><?=$DATA['mb_email']?></td>
		</tr>
		<tr>
			<th scope="col">신청일</th>
			<td><?=$DATA['order_date']?></td>
		</tr>
		<tr>
			<th scope="col">투자자 유형</th>
			<td><font color='red'><?=$print_order_type?></font></td>
		</tr>
		<tr>
			<th scope="col">첨부서류</th>
			<td><?=$attach_file_tag?></td>
		</tr>
		<tr>
			<th scope="col">승인상태</th>
			<td>
<? if($DATA['allow']=='wait') { ?>
				회원구분 :
				<select name="mkind" required>
					<option value="" <?=($DATA['mkind']=='')?'selected':'';?>>::회원구분선택::</option>
					<option value="1" <?=($DATA['mkind']=='1')?'selected':'';?>>신규</option>
					<option value="2" <?=($DATA['mkind']=='2')?'selected':'';?>>갱신</option>
				</select>

				<select name="allow" required>
					<option value="wait" <?=($DATA['allow']=='wait')?'selected':'';?>>대기</option>
					<option value="Y" <?=($DATA['allow']=='Y')?'selected':'';?>>승인</option>
					<option value="N" <?=($DATA['allow']=='N')?'selected':'';?>>거부</option>
				</select>
				&nbsp;&nbsp; ※ 승인 처리시 본 회원의 대기중인 중복 신청건들은 모두 자동 거부 처리됨.
<? } else { ?>
        <div style="color:red"><?=($DATA['allow']=='Y')?'승인':'거부';?> <?php IF($DATA['mkind']) { ECHO ($DATA['mkind']=='1') ? '(신규)':'(갱신)'; } ?></div>
<? } ?>
			</td>
		</tr>

		<tr>
			<th scope="col">유효기간</th>
			<td>
				<? if($DATA['allow']=='wait') { ?>
				<input type="text" class="frm_input datepicker" name="rights_start_date" id="rights_start_date" value="<?=$DATA['rights_start_date']?>" placeholder="시작일"> ~
				<input type="text" class="frm_input datepicker" name="rights_end_date" id="rights_end_date" value="<?=$DATA['rights_end_date']?>" placeholder="종료일">
				<? } else { ?>
				<div style="color:red"><?=$DATA['rights_start_date']?> ~ <?=$DATA['rights_end_date']?></div>
				<? } ?>
			</td>
		</tr>
<? if($DATA['allow']!='wait') { ?>
		<tr>
			<th scope="col">심사자</th>
			<td><?=$appr_mb_name?></td>
		</tr>
<? } ?>
<? if($DATA['allow_date'] && !preg_match("/0000-00-00/", $DATA['allow_date'])) { ?>
		<tr>
			<th scope="col">승인일</th>
			<td><?=$DATA['allow_date']?></td>
		</tr>
<? } ?>
		<tr>
			<th scope="col">심사자 메모</th>
			<td><?=editor_html('judge_memo', get_text(stripSlashes($DATA['judge_memo']), 0))?></td>
		</tr>
	</table>

	<div class="text-center" style="margin-top:10px;">
		<? if($DATA['allow']=='wait') { ?><button class="btn btn-md btn-primary">결제하기</button><? } ?>
		<a href="/adm/member/investor_type_req.php?<?=preg_replace("/idx=([0-9]){1,10}&/", "", $_SERVER['QUERY_STRING']);?>" class="btn btn-md btn-default">목록</a>
	</div>

	</form>
</div>

<script>
function formSubmit() {
<?=get_editor_js("judge_memo")?>
}
</script>


<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
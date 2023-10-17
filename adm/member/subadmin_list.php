<?

$sub_menu = '200800';
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

//print_rr($sad);

if(!$sad['is_inspecter'] && !$sad['is_editor']) msg_go('권한 없음!!', '/adm/');


$html_title = "관리자 설정";
$g5['title'] = $html_title.' > 목록';

include_once (G5_ADMIN_PATH.'/admin.head.php');



$sql = "
	SELECT
		COUNT(A.idx) AS cnt
	FROM
		`g5_sub_admin` A
	LEFT JOIN
		`g5_member` AS B  ON	A.mb_no = B.mb_no";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 100;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = "
	SELECT
		A.idx,
		B.mb_no, B.mb_id, B.mb_level, B.member_type, B.mb_name, B.mb_co_name, B.mb_hp, B.mb_today_login, B.mb_login_ip,
		A.is_inspecter, A.is_editor, A.auth_info, A.privacy_auth, A.hp_auth, A.account_view_auth, A.member_control_auth, A.product_control_auth, A.account_auth, A.allow_location, A.regdate, A.withdrawal, A.withdrawal_date
	FROM
		`g5_sub_admin` A
	LEFT JOIN
		`g5_member` B  ON A.mb_no = B.mb_no
	ORDER BY
		A.withdrawal ASC,
		A.withdrawal DESC,
		A.idx DESC
	LIMIT
		$from_record, $rows";
//print_rr($sql, 'font-size:12px');
$result = sql_query($sql);
$rcount = $result->num_rows;

$num = $total_count - $from_record;


?>

<style>
.btn_area { text-align:left; }
.proc_btn { width:50px; height:50px; line-height:14px; }
</style>

<div class="tbl_head02 tbl_wrap">

	<div class="btn_area">
		<button type="button" onClick="subadmin_inst();" class="btn btn-sm btn-default">관리자 등록</button>
	</div>

	<!-- 리스트 START -->
	<table id="dataList" class="table table-striped table-bordered table-hover" style="font-size:13px;">
		<caption><?=$g5['title']?> 목록</caption>
		<thead style="font-size:13px;">
		<tr>
			<th style="text-align:center;width:50px;" rowspan="2">NO</th>
			<th style="text-align:center;" rowspan="2">회원<br>번호</th>
			<th style="text-align:center;" rowspan="2">직무</th>
			<th style="text-align:center;" rowspan="2">성명</th>
			<th style="text-align:center;" rowspan="2">연락처</th>
			<th style="text-align:center;" colspan="5">권한설정</th>
			<th style="text-align:center;" rowspan="2">유효권한 메뉴</th>
			<th style="text-align:center;" rowspan="2">접속허용IP</th>
			<th style="text-align:center;">권한등록일</th>
			<th style="text-align:center;">최종로그인</th>
			<th style="width:180px;text-align:center;" rowspan="2">관리툴</th>
		</tr>
		<tr>
			<th style="text-align:center;">주민번호 열람</th>
			<th style="text-align:center;">연락처 열람</th>
			<th style="text-align:center;">계좌정보 열람</th>
			<th style="text-align:center;">상품등록.편집</th>
			<th style="text-align:center;">정산관련처리</th>
			<th style="text-align:center;">권한해제일</th>
			<th style="text-align:center;">로그인IP</th>
		</tr>
		</thead>
		<tbody>
<?
if($num > 0) {

	$top_meny_arr_key = array_keys($top_meny_arr);

	for($i=0; $i<$rcount; $i++) {

		$LIST = sql_fetch_array($result);

		$PRINT['mb_id']    = $LIST['mb_id'];

		$MB_NAME = explode("-", $LIST['mb_name']);
		if(count($MB_NAME) > 1) {
			$PRINT['work'] = $MB_NAME[0];
			$PRINT['name'] = $MB_NAME[1];
		}
		else {
			$PRINT['work'] = "";
			$PRINT['name'] = $MB_NAME[0];
		}

		$PRINT['mb_hp']    = ($LIST['mb_hp']) ? masterDecrypt($LIST['mb_hp'], false) : '';

		$PRINT['privacy_auth']         = ($LIST['privacy_auth']=='Y') ? '■' : '<font style="color:#CCC">□</font>';
		$PRINT['hp_auth']              = ($LIST['hp_auth']=='Y') ? '■' : '<font style="color:#CCC">□</font>';
		$PRINT['account_view_auth']    = ($LIST['account_view_auth']=='Y') ? '■' : '<font style="color:#CCC">□</font>';
		$PRINT['member_control_auth']  = ($LIST['member_control_auth']=='Y') ? '■' : '<font style="color:#CCC">□</font>';
		$PRINT['product_control_auth'] = ($LIST['product_control_auth']=='Y') ? '■' : '<font style="color:#CCC">□</font>';
		$PRINT['account_auth']         = ($LIST['account_auth']=='Y') ? '■' : '<font style="color:#CCC">□</font>';
		$PRINT['allow_location']       = ($LIST['allow_location']=='all') ? '전체' : '사내망';
		$PRINT['regdate']              = preg_replace("/-/" , ".", substr($LIST['regdate'], 0, 16));

		$PRINT['last_log'] = (substr($LIST['mb_today_login'], 0, 10) > '0000-00-00') ? preg_replace("/-/" , ".", substr($LIST['mb_today_login'], 0, 16)) . "<br/>\n" . $LIST['mb_login_ip'] : '';

		$auth_info_arr = explode(',',$LIST['auth_info']);
		$auth_info_cnt = count($auth_info_arr);

		$PRINT['auth_info'] = '';
		for($x=0,$y=1; $x<count($top_meny_arr); $x++,$y++) {
			$fcolor = (in_array($top_meny_arr_key[$x], $auth_info_arr)) ? '#000' : '#CCC';
			$PRINT['auth_info'].= '<font style="color:'.$fcolor.'">'.$top_meny_arr[$top_meny_arr_key[$x]].'</font>';
			$PRINT['auth_info'].= ($y<count($top_meny_arr)) ? ' | ' : '';
			if(($y%4)==0) $PRINT['auth_info'].= "<br/>\n";
		}


		if($sad['is_inspecter'] || $sad['is_editor']) {
			$editBtnClass = "btn-default";
			$delBtnClass  = "btn-danger";
			$logBtnClass  = "btn-success";
		}
		else {
			$editBtnClass = "btn-gray";
			$delBtnClass  = "btn-gray";
			$logBtnClass  = "btn-gray";
		}

		$PRINT['proc_button'] = "";
		$PRINT['proc_button'].= "<button type='button' onClick=\"subadmin_modi('{$LIST['mb_no']}');\" class='btn btn-sm $editBtnClass proc_btn'>정보<br/>수정</button>\n";
		$PRINT['proc_button'].= "<button type='button' onclick=\"subadmin_log('{$LIST['mb_no']}');\"  class='btn btn-sm $logBtnClass proc_btn'>로그<br/>관리</button>\n";
		$PRINT['proc_button'].= "<button type='button' onclick=\"subadmin_dele('{$LIST['mb_no']}');\"  class='btn btn-sm $delBtnClass proc_btn'>권한<br/>해제</button>\n";

		// 탈퇴자 표기 내용 차단
		if($LIST['withdrawal']) {

			$PRINT['mb_id'] = $PRINT['work'] = $PRINT['mb_hp'] = $PRINT['privacy_auth'] = $PRINT['hp_auth'] = $PRINT['account_view_auth'] = $PRINT['member_control_auth'] = $PRINT['product_control_auth'] = $PRINT['account_auth'] = $PRINT['allow_location'] = $PRINT['auth_info'] = NULL;

			$PRINT['proc_button'] = "";
			//$PRINT['proc_button'].= "<button type='button' onclick=\"subadmin_log('{$LIST['mb_no']}');\"  class='btn btn-sm $logBtnClass proc_btn'>로그<br/>관리</button>\n";

		}


		$tr_style = ($LIST['withdrawal']=='1') ? 'style="background:#FFDDDD"' : '';


?>
		<tr <?=$tr_style?>>
			<td align="center"><?=$num?></td>
			<td align="center"><?=$LIST['mb_no']?></td>
			<td align="center"><?=$PRINT['work']?></td>
			<td align="center"><?=$PRINT['name']?></td>
			<td align="center"><?=$PRINT['mb_hp']?></td>
			<td align="center" style="font-size:18px;"><?=$PRINT['privacy_auth']?></td>
			<td align="center" style="font-size:18px;"><?=$PRINT['hp_auth']?></td>
			<td align="center" style="font-size:18px;"><?=$PRINT['account_view_auth']?></td>
			<td align="center" style="font-size:18px;"><?=$PRINT['member_control_auth']?></td>
			<td align="center" style="font-size:18px;"><?=$PRINT['account_auth']?></td>
			<td align="left" style="font-size:12px;color:#ccc;"><?=$PRINT['auth_info']?></td>
			<td align="center"><?=$PRINT['allow_location']?></td>
			<td align="center"><?=$PRINT['regdate']?><?if($LIST['withdrawal_date']){?><br/><font color='red'><?=substr($LIST['withdrawal_date'],0,16);?></font><?}?></td>
			<td align="center" style="font-size:12px;"><?=$PRINT['last_log']?></td>
			<td align="center"><?=$PRINT['proc_button']?></td>
		</tr>
<?
		$num--;
	}
}else {
?>

		<tr>
			<td colspan="16" align="center" height="300px";>검색된 데이터가 없습니다.</td>
		</tr>

<?
}
?>
	</table>
	<!-- 리스트 E N D -->

</div>

<? echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>


<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>

<script>
// 관리자 등록
function subadmin_inst() {
<? if($sad['is_inspecter'] || $sad['is_editor']) { ?>
	document.location.href = './subadmin_form.php?&mode=inst';
<? } else { ?>
	alert('관리 권한이 없습니다.');
<? } ?>
}

// 관리자 수정
function subadmin_modi(mb_no) {
<? if($sad['is_inspecter'] || $sad['is_editor']) { ?>
	document.location.href = './subadmin_form.php?mb_no='+mb_no+'&mode=modi';
<? } else { ?>
	alert('관리 권한이 없습니다.');
<? } ?>
}

// 관리자 해제
function subadmin_dele(mb_no) {
<? if($sad['is_inspecter'] || $sad['is_editor']) { ?>
	if(confirm('권한 해제 및 탈퇴 처리 하시겠습니까?')) {
		document.location.href = './subadmin_delete.php?mb_no='+mb_no;
	}
<? } else { ?>
	alert('관리 권한이 없습니다.');
<? } ?>
}

// 관리자 로그 확인
function subadmin_log(mb_no) {
<? if($sad['is_inspecter'] || $sad['is_editor']) { ?>
	document.location.href = './subadmin_log.php?mb_no=' + mb_no;
<? } else { ?>
	alert('권한이 없습니다.');
<? } ?>
}


$(document).ready(function() {
	$('#dataList').floatThead();
});
</script>

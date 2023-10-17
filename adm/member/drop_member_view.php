<?
$sub_menu = '200100';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "w");


$html_title = "탈퇴 회원 상세";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');

$mb = sql_fetch("SELECT * FROM g5_member_drop WHERE mb_no=".$_REQUEST['mb_no']);
//$mb = get_member($_GET['mb_id']);

if (!$mb['mb_id']) alert('존재하지 않는 회원자료입니다.');

switch(get_text($mb['member_type'])) {
	case '2' : $member_type_text = '기업회원'; break;
	case '3' : $member_type_text = 'SNS회원';  break;
	case '1' :
	default  : $member_type_text = '개인회원'; break;
}

$member_type_text.= ($mb['is_creditor']=="Y") ? "(대부업)" : "";

$query_str = $_SERVER["QUERY_STRING"];

// 예치금
$sum_point = get_point_sum($mb['mb_id']);
$member_deposit_point = $sum_point;

// 충전금액 합계
$sql = "
		SELECT
			IFNULL(SUM(va.tr_amt), 0) total_charge_amount
		FROM
			vacs_ahst va
		INNER JOIN
			g5_member_drop mem ON va.iacct_no = mem.virtual_account
		LEFT JOIN
			vacs_vact vv ON mem.virtual_account = vv.acct_no
			AND vv.acct_st = '1'
		WHERE
			mem.mb_id='{$mb['mb_id']}'";
$result = sql_fetch($sql);
$total_charge_amount = $result['total_charge_amount'];

// 일반 투자금 합계
$sql = "
		SELECT
			IFNULL(SUM(cpi.amount), 0) total_invest_amout
		FROM
			cf_product_invest cpi
		LEFT JOIN
			g5_member_drop mem ON cpi.member_idx = mem.mb_no
		WHERE
			mem.mb_id = '{$mb['mb_id']}' AND cpi.invest_state='Y'";
$result = sql_fetch($sql);
$total_invest_amount = $result['total_invest_amout'];

// 이벤트 투자금 합계
$sql = "
		SELECT
			IFNULL(SUM(cepi.amount), 0) total_event_invest_amout
		FROM
			cf_event_product_invest cepi
		LEFT JOIN
			g5_member_drop mem ON cepi.member_idx = mem.mb_no
		WHERE
			mem.mb_id = '{$mb['mb_id']}' AND cepi.invest_state='Y'";
$result = sql_fetch($sql);
$total_invest_amount = $total_invest_amount + $result['total_event_invest_amout'];

// 미달성환불합계
$sql = "
		SELECT
			IFNULL(SUM(A.amount),0) AS 'total_return_price'
		FROM
			cf_product_invest AS A
		LEFT JOIN
			cf_product AS B ON A.product_idx = B.idx
		LEFT JOIN
			g5_member_drop AS C ON A.member_idx = C.mb_no
		WHERE
			B.state = ''
			AND B.end_datetime < now()
			AND B.invest_end_date = ''
			AND A.invest_state = 'Y'
			AND	B.end_date > SUBSTRING(NOW(),1,10)
			AND	C.mb_id = '{$mb['mb_id']}'";

$result = sql_fetch($sql);
$total_return_price = $result['total_return_price'];

// 출금합계
$sql = "
		SELECT
			SUM(req_price) total_withdraw_price
		FROM
			g5_withdrawal
		WHERE
			state = '2'
			AND mb_id = '{$mb['mb_id']}'";

$result = sql_fetch($sql);
$total_withdraw_price = $result['total_withdraw_price'];

$sql = "
		SELECT
			tbl.regdate, tbl.orderReg, tbl.price1, tbl.price2, tbl.price3, tbl.price4, tbl.price5, tbl.state
		FROM
		(
			SELECT
					CONCAT(SUBSTR(va.tr_il, 1, 4), '-', SUBSTR(va.tr_il, 5, 2), '-', SUBSTR(va.tr_il, 7, 2)) AS 'regdate',
					CONCAT(SUBSTR(va.tr_il, 1, 4), '-', SUBSTR(va.tr_il, 5, 2), '-', SUBSTR(va.tr_il, 7, 2), ' ', SUBSTR(va.tr_si, 1, 2), ':', SUBSTR(va.tr_si, 3, 2), ':', SUBSTR(va.tr_si, 5, 2)  ) AS 'orderReg',
					va.tr_amt AS 'price1',
					'' AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					vacs_ahst va
				INNER JOIN
					vacs_vact vv ON va.iacct_no = vv.acct_no AND vv.acct_st = '1'
				LEFT JOIN
					g5_member_drop mem ON va.iacct_no = mem.virtual_account
				WHERE
					mem.mb_id = '{$mb['mb_id']}'

			UNION ALL

				SELECT
					insert_date AS 'regdate',
					concat(A.insert_date, ' ', A.insert_time ) AS 'orderReg',
					'' AS 'price1',
					amount AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_product_invest AS A
				LEFT JOIN
					g5_member_drop AS B ON  A.member_idx = B.mb_no
				WHERE
					invest_state = 'Y'
					AND B.mb_id = '{$mb['mb_id']}'

			UNION ALL

				SELECT
					A.insert_date AS 'regdate',
					CONCAT(A.insert_date, ' ', A.insert_time ) AS 'orderReg',
					'' AS 'price1',
					'' AS 'price2',
					A.amount AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_product_invest AS A
				LEFT JOIN
					cf_product AS B ON A.product_idx = B.idx
				LEFT JOIN
					g5_member_drop AS C ON A.member_idx = C.mb_no
				WHERE
					B.state = ''
					AND B.end_datetime < now()
					AND B.invest_end_date = ''
					AND A.invest_state = 'Y'
					AND B.end_date > SUBSTRING(NOW(),1,10)
					AND C.mb_id = '{$mb['mb_id']}'

			UNION ALL

				SELECT
					insert_date AS 'regdate',
					concat(A.insert_date, ' ', A.insert_time ) AS 'orderReg',
					'' AS 'price1',
					amount AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_event_product_invest AS A
				LEFT JOIN
					g5_member_drop AS B ON  A.member_idx = B.mb_no
				WHERE
					invest_state = 'Y'
					AND B.mb_id = '{$mb['mb_id']}'

			UNION ALL

				SELECT
					A.insert_date AS 'regdate',
					CONCAT(A.insert_date, ' ', A.insert_time ) AS 'orderReg',
					'' AS 'price1',
					'' AS 'price2',
					A.amount AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_event_product_invest AS A
				LEFT JOIN
					cf_event_product AS B ON A.product_idx = B.idx
				LEFT JOIN
					g5_member_drop AS C ON A.member_idx = C.mb_no
				WHERE
					B.state = ''
					AND B.end_datetime < now()
					AND B.invest_end_date = ''
					AND A.invest_state = 'Y'
					AND B.end_date > SUBSTRING(NOW(),1,10)
					AND C.mb_id = '{$mb['mb_id']}'

			UNION ALL

				SELECT
					date(regDate) as 'regdate',
					regDate AS 'orderReg',
					'' AS 'price1',
					'' AS 'price2',
					'' AS 'price3',
					req_price AS 'price4',
					state AS 'state',
					'' AS 'price5'
				FROM
					g5_withdrawal
				WHERE
					state in('1', '2') AND mb_id = '{$mb['mb_id']}'

			UNION ALL

				SELECT
					SUBSTRING(po_datetime,1,10) AS 'regdate',
					po_datetime AS 'orderReg',
					'' AS 'price1',
					'' AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					po_point AS 'price5'
				FROM
					g5_point
				WHERE
					mb_id = '{$mb['mb_id']}' AND po_content in('예치금 지급', '예치금 차감')
		) AS tbl
		ORDER BY tbl.orderReg DESC";

		//echo $sql;

$result = sql_query($sql);
$point_list = array();
while($list = sql_fetch_array($result)){
	array_push($point_list, $list);
}

?>

<style>
#paging_span { margin-top:10px;  text-align:center; }
#paging_span span.arrow { padding:0; border:0; line-height:0; }
#paging_span span { display:inline-block; min-width:30px; padding:0 5px; color:#585657; line-height:28px; border:1px solid #d0d0d0; cursor:pointer }
#paging_span span.now { color:#fff; background-color:#284893; border-color:#284893; cursor:default }

.tmp {border:0; background:#EEEBD9; text-align:center }
.tmp1 {border:0; background:#F5F5F5; color:#000000;}
.tmp1_1 {border:0; background:#FFEEEE; color:brown;}
</style>

<div class="tbl_head02 tbl_wrap">

	<table width="100%">
		<caption><?=$g5['title']?> 목록</caption>
		<colgroup>
			<col width="15%">
			<col width="35%">
			<col width="15%">
			<col width="35%">
		</colgroup>
		<tr>
			<th scope="col" class="text-center">회원유형</th>
			<td align="center"><?=$member_type_text?></td>
			<th scope="col">아이디</th>
			<td align="center"><?=get_text($mb['mb_id']);?></td>
		</tr>
<? if($mb['member_type']=='1') { ?>
		<tr>
			<th scope="col" class="text-center">성명</th>
			<td align="center"><?=$mb['mb_name']?></td>
			<th scope="col" class="text-center">주민등록번호</th>
			<td align="center"><?=($_SESSION['ss_accounting_admin']) ? getJumin($mb['mb_no']) : '<font style="color:#ccc">열람불가</font>'; ?></td>
		</tr>
<? } else { ?>
		<tr>
			<th scope="col" class="text-center">상호명</th>
			<td align="center"><?=$mb['mb_co_name']?></td>
		</tr>
		<tr>
			<th scope="col" class="text-center">사업자등록번호</th>
			<td align="center"><?=$mb['mb_co_reg_num']?>
				<? if($mb['business_license']!="") { echo "&nbsp;<a href='".G5_URL."/mypage/license_download.php?mb_id=".$mb["mb_id"]."'><img src='/images/investment/icon_file.png' width='20px' height='30px'></a>"; } ?>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">담당자명</th>
			<td colspan="3"><?=$mb['mb_name']?></td>
		</tr>
<? } ?>
		<tr>
			<th scope="col" class="text-center">휴대전화</th>
			<td align="center"><?=($_SESSION['ss_accounting_admin']) ? get_text($mb['mb_hp']) : '<font style="color:#ccc">열람불가</font>'; ?></td>
			<th scope="col" class="text-center">이메일</th>
			<td align="center"><?=($_SESSION['ss_accounting_admin']) ? get_text($mb['mb_email']) : '<font style="color:#ccc">열람불가</font>'; ?></td>
		</tr>
		<tr>
			<th scope="col" class="text-center">주소</th>
			<td align="center"><? if($_SESSION['ss_accounting_admin']) { ?>
				(<?=get_text($mb['zip_num'])?>)
				<?=get_text($mb['mb_addr1'])?>
				<?=get_text($mb['mb_addr2'])?>
				<?=get_text($mb['mb_addr3'])?>
				<? } else { ?>
				<font style="color:#ccc">열람불가</font>
				<? } ?>
			</td>
			<th scope="col" class="text-center">원리금 수취방식</th>
			<td align="center">
<?
	if($mb['receive_method']=='1') echo "환급계좌";
	else if($mb['receive_method']=='2') echo "예치금(가상계좌)";
	else echo "미지정";
?>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">환급계좌</th>
			<td align="center"><? if($_SESSION['ss_accounting_admin']) { ?>
				<?=get_text($mb['bank_name'])?>
				<?=get_text($mb['account_num'])?>
				<?=get_text($mb['bank_private_name'])?>
				<? } else { ?>
				<font style="color:#ccc">열람불가</font>
				<? } ?>
			</td>
			<th scope="col" class="text-center">가상계좌</th>
			<td align="center">
				<?=$VBANK[$mb['va_bank_code']]?>
				<?=get_text($mb['virtual_account'])?>
				<?=get_text($mb['va_private_name'])?>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">메일수신</th>
			<td align="center"><?=($mb['mb_mailling']==1) ? '동의함' : '미동의'; ?></td>
			<th scope="col" class="text-center">SMS수신</th>
			<td align="center"><?=($mb['mb_sms'] == 1) ? '동의함' : '미동의'; ?></td>
		</tr>
		<tr>
			<th scope="col" class="text-center">가입정보</th>
			<td align="center" style="color:#aaa">
				date. <font color="black"><?=substr($mb['mb_datetime'], 0, 16)?></font> &nbsp;&nbsp;/&nbsp;&nbsp;
				ip. <font color="black"><?=$mb['mb_ip']?></font> &nbsp;&nbsp;/&nbsp;&nbsp;
				device. <font color="black"><?=($mb['device'])?strtoupper($mb['device']) : '불명';?></font>
			</td>
			<th scope="col" class="text-center">최종접속정보</th>
			<td align="center" style="color:#aaa">
				date. <font color="black"><?=substr($mb['mb_today_login'], 0, 16)?></font> &nbsp;&nbsp;/&nbsp;&nbsp;
				ip. <font color="black"><?=$mb['mb_login_ip']?></font>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">투자위험고지</th>
			<td align="center"><?=($mb['invest_warning_agree']=='Y') ? '동의함' : '미동의'; ?></td>
			<th scope="col" class="text-center">미사용 예치금</th>
			<td align="center"><?=number_format($mb['mb_point'])?>원</td>
		</tr>
	</table>

	<div class="text-right" style="margin-top:10px;"><a href="/adm/member/drop_member_list.php?<?=$query_str?>" class="btn btn-md btn-info">목록</a></div>

	<div style="margin-top:50px;"><h1>활동내역 (투자정보)</h1></div>

	<h3>[예치금 현황]</h3>
	<table width="100%" style="margin-bottom:15px;">
		<tr>
			<th scope="col" class="text-center">예치금 잔액</th>
			<th scope="col" class="text-center">충전금액 합계</th>
			<th scope="col" class="text-center">투자금 합계</th>
			<th scope="col" class="text-center">미달성환불 합계</th>
			<th scope="col" class="text-center">출금 합계</th>
		</tr>
		<tr>
			<td class="text-right"><?=number_format($member_deposit_point);?>원</td>
			<td class="text-right"><?=number_format($total_charge_amount);?>원</td>
			<td class="text-right"><?=number_format($total_invest_amount);?>원</td>
			<td class="text-right"><?=number_format($total_return_price);?>원</td>
			<td class="text-right"><?=number_format($total_withdraw_price);?>원</td>
		</tr>
	</table>

	<h3>[예치금 입출금 내역]</h3>
	<table width="100%" style="margin-bottom:40px;">
		<tr>
			<th scope="col" class="text-center">거래일</th>
			<th scope="col" class="text-center">충전금액</th>
			<th scope="col" class="text-center">투자금액</th>
			<th scope="col" class="text-center">미달성환불</th>
			<th scope="col" class="text-center">출금</th>
			<th scope="col" class="text-center">관리자 지급/차감</th>
		</tr>
<?
if($point_list != null){

	foreach($point_list as $Rows){
?>
		<tr>
			<td class="text-right"><?=$Rows['regdate']?></td>
			<td class="text-right"><?=($Rows['price1'] != '') ? number_format($Rows['price1']).'원' : ''; ?></td>
			<td class="text-right"><?=($Rows['price2'] != '') ? number_format($Rows['price2']).'원' : ''; ?></td>
			<td class="text-right"><?=($Rows['price3'] != '') ? number_format($Rows['price3']).'원' : ''; ?></td>
			<td class="text-right">
				<?
					if($Rows['price4'] != '') echo number_format($Rows['price4']).'원';
					if($Rows['state'] == '1') echo " (예정) ";
				?>
			</td>
			<td class="text-center"><?=($Rows['price5'] != '') ? number_format($Rows['price5']).'원' : ''; ?></td>

		</tr>
<?
	}
}else {
?>
		<tr>
			<td align="center" colspan="6">검색된 데이터가 없습니다.</td>
		</tr>
<?
}
?>

	</table>

	<h3>[투자수익현황]</h3>
	<div id="invest_status">

	</div>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "ajax_member_invest_list.php",
			type: "GET",
			data: { ca:'total_status', mb_no:<?=$mb['mb_no']?> },
			success: function(data) {
				$('#invest_status').html(data);
			},
			error: function () {
				$('#invest_status').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});
	</script>

	<h3>[일반 투자내역]</h3>
	<div id="invest_log">

	</div>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "ajax_member_invest_list.php",
			type: "GET",
			data: { ca:'invest_log', mb_no:<?=$mb['mb_no']?> },
			success: function(data) {
				$('#invest_log').html(data);
			},
			error: function () {
				$('#invest_log').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});

	$(document).on('click','.btn_paging',function() {
		$.ajax({
			url : "./ajax_member_invest_list.php",
			type: "GET",
			data : { ca:'invest_log', mb_no:<?=$mb['mb_no']?>, page:$(this).attr("data-page")},
			success: function(data){
				$('#invest_log').html(data);
			},
			error: function ()	{
				$('#invest_log').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});
	</script>


	<h3>[이벤트 투자내역]</h3>
	<div id="event_invest_log">

	</div>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "ajax_member_invest_list.php",
			type: "GET",
			data: { ca:'event_invest_log', mb_no:<?=$mb['mb_no']?> },
			success: function(data) {
				$('#event_invest_log').html(data);
			},
			error: function () {
				$('#event_invest_log').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});
	</script>
	<!-- 투자내역 상세리스트 끝 //-->

</div>

<script>
function startToggle(btn_id, area_id) {
	$(area_id).slideToggle();
};
</script>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
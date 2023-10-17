<?
###############################################################################
## 상품리스트에서 호출되는 투자자목록 (기표전 또는 기표후 취소등)
###############################################################################

$base_path  = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');
include_once($base_path . '/lib/crypt.lib.php');

$prd_idx = $_REQUEST['prd_idx'];
$PRDT = sql_fetch("SELECT state FROM cf_product WHERE idx='".$prd_idx."'");

$sql = "
	SELECT
		A.idx, A.amount, A.invest_state, A.member_idx, A.product_idx, A.insert_datetime, A.is_advance_invest, A.syndi_id, A.insert_datetime,
		B.mb_id, B.mb_name, B.mb_co_name, B.member_type, B.member_investor_type, B.is_creditor,
		B.receive_method, B.bank_code, B.account_num, B.va_bank_code2, B.virtual_account2,
		(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='') AS self_invest_count,
		(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='1') AS auto_invest_count
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE 1
		AND A.product_idx = '".$prd_idx."'
		AND A.invest_state IN('Y', 'R')
	ORDER BY
		A.idx DESC";
$res = sql_query($sql);
$rows = sql_num_rows($res);
for($i=0; $i<$rows; $i++) {
	$INVEST[$i] = sql_fetch_array($res);
	$INVEST[$i]['account_num'] = masterDecrypt($INVEST[$i]['account_num'], false);
}

$invest_count = count($INVEST);

?>
<div style="padding:4px; text-align:right">
	<button type="button" onClick="bankMoneyCheck('<?=$prd_idx?>');" class="btn btn-sm btn-warning">투자금-예치금 비교</button>
</div>
<table id="listTable" class="tblx table-striped table-bordered">
	<thead>
	<tr align="center" style="background:#EEE">
		<td rowspan="2">NO.</td>
		<td rowspan="2">투자<br/>번호</td>
		<td rowspan="2">회원<br/>번호</td>
		<td rowspan="2">ID</td>
		<td rowspan="2">성명.상호명</td>
		<td rowspan="2">투자금</td>
		<td rowspan="2">신디케이션</td>
		<td rowspan="2">투자구분</td>
		<td rowspan="2">최종투자일시</td>
		<td rowspan="2">수취방식</td>
		<td rowspan="2">지급은행</td>
		<td rowspan="2">계좌번호</td>
		<td colspan="2">누적투자</td>
		<td rowspan="2">투자취소</td>
	</tr>
	<tr align="center" style="background:#EEE">
		<td>건수</td>
		<td>금액</td>
	</tr>
	</thead>
<?
	for($i=0,$num=$invest_count; $i<$invest_count; $i++,$num--) {

		$R = sql_fetch("
			SELECT
				COUNT(idx) AS total_invest_count,
				IFNULL(SUM(amount), 0) AS total_invest_amount
			FROM
				cf_product_invest
			WHERE 1
				AND member_idx = '".$INVEST[$i]['member_idx']."'
				AND invest_state = 'Y'");

		//수취계좌 출력
		if($INVEST[$i]['receive_method']=='1') {
			$receive_method = "계좌환급";
			$bank           = $BANK[$INVEST[$i]['bank_code']];
			$account_num    = substr($INVEST[$i]['account_num'], 0, strlen($INVEST[$i]['account_num'])-4)."****";
		}
		else if($INVEST[$i]['receive_method']=='2') {
			$receive_method = '<font color="#FF2222">예치금</font>';
			$bank           = $BANK[$INVEST[$i]['va_bank_code2']];
			$account_num    = substr($INVEST[$i]['virtual_account2'], 0, strlen($INVEST[$i]['virtual_account2'])-4)."****";
		}
		else {
			$receive_method = "미지정";
			$bank           = "";
			$account_num    = "";
		}

		$INVEST[$i]['mb_name'] = ($INVEST[$i]['member_type']=='2') ? $INVEST[$i]['mb_co_name'] : $INVEST[$i]['mb_name'];
		$INVEST[$i]['jumin']   = ($INVEST[$i]['member_type']=='2') ? $INVEST[$i]['mb_co_reg_num'] : @getJumin($INVEST[$i]['member_idx']);

		$bgcolor = "#FFFFFF";
		if($INVEST[$i]['member_type']=='2') {
			$bgcolor = ($INVEST[$i]['is_creditor']=='Y') ? '#FCE4D6' : '#ffffcc';
		}
		if($INVEST[$j]['insidebank_after_trans_target']=='1') $bgcolor = '#53B5DC';		//신한 예치금 이전 대상자 플래그

		$invest_type  = ($INVEST[$i]['is_advance_invest']=='Y') ? '사전투자' : '';

		$fcolor1 = ($INVEST[$i]['auto_invest_count'] > 0) ? '' : '#ccc';
		$fcolor2 = ($INVEST[$i]['self_invest_count'] > 0) ? '' : '#ccc';

		$invest_type.= "<span style='color:{$fcolor1}'>자동 ".number_format($INVEST[$i]['auto_invest_count'])."건</span>, <span style='color:{$fcolor2}'>직접 ".number_format($INVEST[$i]['self_invest_count'])."건</span>";

		if($INVEST[$i]['insidebank_after_trans_target']=='1') $bgcolor = '#53B5DC';		//신한 예치금 이전 대상자 플래그

		$cancel_button = "";
		if($PRDT['state']=='') {
			if($INVEST[$i]['invest_state']=='Y') {
				$cancel_button = "<button type='button' class='btn btn-sm btn-danger' onClick=\"investCancel('".$INVEST[$i]['idx']."');\">투자취소</button>";
			}
		}

		$print_syndi = ($INVEST[$i]['syndi_id']) ? $CONF['SYNDICATOR'][$INVEST[$i]['syndi_id']]['name'] : '';

?>
	<tr style="background:<?=$bgcolor?>">
		<td align="center"><?=$num?></td>
		<td align="center"><?=$INVEST[$i]['idx']?></td>
		<td align="center"><?=$INVEST[$i]['member_idx']?></td>
		<td align="center">
			<?=$INVEST[$i]['mb_id']?><br>
			<a href="/adm/repayment/invest_list.php?iv_state=Y&field=C.mb_no&keyword=<?=$INVEST[$i]['member_idx']?>" target="_blank" class="btn btn-info" style="font-size:11px; line-height:11px; padding:3px 4px;">전체투자내역</a>
		</td>
		<td align="center"><a href="javascript:;" onClick="balance_check(<?=$INVEST[$i]['member_idx']?>)" style="color:blue"><?=$INVEST[$i]['mb_name']?></a></td>
		<td align="right"><span style="cursor:pointer" onClick="balance_check(<?=$INVEST[$i]['member_idx']?>);"><?=number_format($INVEST[$i]['amount'])?></span></td>
		<td align="center"><?=$print_syndi?></td>
		<td align="center"><?=$invest_type?></td>
		<td align="center"><?=substr($INVEST[$i]['insert_datetime'],0,16)?></td>
		<td align="center"><?=$receive_method?></td>
		<td align="center"><?=$bank?></td>
		<td align="center"><?=$account_num?></td>
		<td align="right"><?if($R['total_invest_count']>0){?><a href="/adm/repayment/invest_list.php?iv_state=Y&field=C.mb_no&keyword=<?=$INVEST[$i]['member_idx']?>" target="_blank"><?}?><?=number_format($R['total_invest_count'])?>건</a></td>
		<td align="right"><?if($R['total_invest_count']>0){?><a href="/adm/repayment/invest_list.php?iv_state=Y&field=C.mb_no&keyword=<?=$INVEST[$i]['member_idx']?>" target="_blank"><?}?><?=number_format($R['total_invest_amount'])?>원</a></td>
		<td align="center"><?=$cancel_button?></td>
	</tr>
<?
	}
?>
</table>

<script>
// 타이틀바 플로팅
$(document).ready(function() {
	$('#listTable').floatThead();
});

bankMoneyCheck = function(idx) {
	if(idx) {
		exectime = '<?=($invest_count*3)."초";?>';
		if(confirm('DB상 기록된 회원 예치금과 신한은행에 등록된 예치금을 비교합니다.\n수행 완료까지 약 ' + exectime + ' 소요 예상됩니다.\n페이지를 호출 하시겠습니까?')) {
			exec_url = '/adm/repayment/investor_amount_check.php?prd_idx=' + idx;
			window.open(exec_url, 'bankMoneyCheckWindow', 'left=100,top=100,width=1100, height=600, scrollbars=1');
			return false;
		}
	}
}

investCancel = function(idx) {
	if( confirm('투자번호: ' + idx
						+ '\n\n해당 투자내역 취소 및 투자된 예치금이 반환되며,\n'
						+ '모집이 완료된 상품인 경우, 모집중인 상태로 전환됩니다.\n\n'
						+ '진행하시겠습니까?') ) {

		$.ajax({
			url: '/adm/repayment/invest_cancel.ajax.php',
			type: 'post',
			dataType: 'json',
			data:{invest_idx: idx},
			success: function(data) {
				if(data.result=='SUCCESS') {
					alert('정상 처리 완료되었습니다.\n\n페이지를 다시 호출 합니다.'); window.location.reload();
				}
				else if(data.result=='ERROR') {
					if(data.message=='LOGIN_PLEASE') {
						window.location.replace('/');
					}
					else {
						alert(data.message);
					}
				}
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});

	}
}
</script>

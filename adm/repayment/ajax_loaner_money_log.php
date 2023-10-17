<?
///////////////////////////////////////////////////////////////////////////////
// 대출자 상환금 입출금 내역 출력 AJAX
//
///////////////////////////////////////////////////////////////////////////////
set_time_limit(0);

include_once('_common.php');

$request_type = ( preg_match("/application\/json/i", $_SERVER['HTTP_ACCEPT']) ) ? 'json' : 'html';

$prd_idx = $_REQUEST['idx'];
$vacct   = $_REQUEST['vacct'];
if($request_type=='json') {
	$order_value = ($_REQUEST['order_value']) ? $_REQUEST['order_value'] : 'all';		// 전체:all, list, sum
}

$print_form = $_REQUEST['print_form'];

$sql = "
	SELECT
		A.*,
		(SELECT COUNT(idx) AS cnt FROM cf_product WHERE display='Y' AND recruit_amount >= 10000 AND repay_acct_no=A.repay_acct_no) AS use_product_count
	FROM
		cf_product A
	WHERE
		A.idx='".$prd_idx."'";
//if(!$_SERVER['HTTP_REFERER']) print_rr($sql.';', 'font-size:12px');
$PRDT = sql_fetch($sql);
if(!$PRDT['idx']) { exit; }

$add_where = "";
$add_where.= ($PRDT['use_product_count'] > 1) ? " AND repay_prd_idx='".$prd_idx."'" : " AND repay_prd_idx IN('', '".$prd_idx."')";		// 그룹상품일 경우 자기 상품번호가 등록된 입금내역을 가져오도록... 자기 상품번호는 사전에 수동 입력해줘야 함


$LIST = array();

$SUM = array(
	'deposit' => 0,
	'withdrawal' => 0
);

// 입금내역
$sql = "
	SELECT
		BANK_ID, ACCT_NB, TR_AMT, REMITTER_NM, MEDIA_GBN, ERP_TRANS_DT
	FROM
		IB_FB_P2P_IP
	WHERE 1
		AND CUST_ID='".$PRDT['loan_mb_no']."'
		AND ACCT_NB='".$PRDT['repay_acct_no']."'
		AND TR_AMT_GBN='20'
		$add_where
	ORDER BY
		SR_DATE, FB_SEQ, ERP_TRANS_DT";
if(!$_SERVER['HTTP_REFERER']) print_rr($sql.';', 'font-size:12px');

$res  = sql_query($sql);
$rows = sql_num_rows($res);
for($i=0; $i<$rows; $i++) {
	$ROW = sql_fetch_array($res);

	$key = $ROW['ERP_TRANS_DT'] . 'XX' . sprintf("%03d",$i);

	$datetime = substr($ROW['ERP_TRANS_DT'], 0, 4)."-".substr($ROW['ERP_TRANS_DT'], 4, 2)."-".substr($ROW['ERP_TRANS_DT'], 6, 2)." ".substr($ROW['ERP_TRANS_DT'], -6, 2).":".substr($ROW['ERP_TRANS_DT'], -4, 2).":".substr($ROW['ERP_TRANS_DT'], -2);
	$date = substr($datetime, 0, 10);
	$pretext = '입금자명: ' . $ROW['REMITTER_NM'];

	$LIST[$key] = array(
		'date'      => $date,
		'datetime'  => $datetime,
		'gubun'     => '1',
		'gubun_str' => '입금',
		'pretext'   => $pretext,
		'amount'    => $ROW['TR_AMT']
	);

	$SUM['deposit'] += $ROW['TR_AMT'];

}

// 상환내역
$sql = "
	SELECT
		turn, turn_sno, is_overdue,
		MAX(banking_date) AS max_banking_date,
		SUM(principal + interest + interest_tax + local_tax + fee) AS amount
	FROM
		cf_product_give
	WHERE 1
		AND product_idx='".$prd_idx."'
	GROUP BY
		turn, turn_sno, is_overdue
	ORDER BY
		turn, turn_sno, is_overdue";
if(!$_SERVER['HTTP_REFERER']) print_rr($sql.';', 'font-size:12px');
$res  = sql_query($sql);
$rows = sql_num_rows($res);
for($i=0; $i<$rows; $i++) {
	$ROW = sql_fetch_array($res);

	$key = preg_replace("/(-| |:)/", "", $ROW['max_banking_date']) . 'XX' . sprintf("%03d",$i);

	$date = substr($ROW['max_banking_date'], 0, 10);
	$pretext = $ROW['turn'].'회차';
	if($ROW['is_overdue']=='Y') {
		$pretext.= ' 연체상환';
	}
	else {
		$pretext.= ($ROW['turn_sno'] > 0) ? ' 원금일부상환' : ' 정규상환';
	}

	$LIST[$key] = array(
		'date'      => $date,
		'datetime'  => $ROW['max_banking_date'],
		'gubun'     => '2',
		'gubun_str' => '출금',
		'pretext'   => $pretext,
		'amount'    => $ROW['amount']
	);

	$SUM['withdrawal'] += $ROW['amount'];

}

// 출금 또는 출금집행 내역 (관리자가 등록)
$sql = "
	SELECT
		A.*,
		B.mb_name
	FROM
		cf_repay_tmp_log A
	LEFT JOIN
		g5_member B  ON A.writer_id = B.mb_id
	WHERE 1
		AND A.product_idx = '".$prd_idx."'
		AND A.draw_id = ''
	ORDER BY
		A.target_date";
if(!$_SERVER['HTTP_REFERER']) print_rr($sql.';', 'font-size:12px');

$res  = sql_query($sql);
$rows = sql_num_rows($res);
for($i=0; $i<$rows; $i++) {
	$ROW = sql_fetch_array($res);

	$key = preg_replace("/(-| |:)/", "", $ROW['target_date']) . 'XX' . sprintf("%03d",$i);

	$date      = substr($ROW['target_date'], 0, 10);
	$gubun     = $ROW['gubun'];
	$gubun_str = ($ROW['gubun']=='2') ? '차감' : '가산';
	$amount    = $ROW['amount'];
	$pretext   = $ROW['pretext'];
	$pretext.= " (관리자 보정데이터: " . $ROW['mb_name'] . " / " . substr($ROW['regdate'],0,16) . ")";

	$LIST[$key] = array(
		'date'     => $date,
		'datetime' => $ROW['target_date'],
		'gubun'    => $gubun,
		'gubun_str'=> $gubun_str,
		'pretext'  => $pretext,
		'amount'   => $amount,
		'tmp_log_idx' => $ROW['idx']
	);

	//if($ROW['gubun']=='1') $SUM['deposit'] += $ROW['deposit']; // 오류수정 전승찬 2020-01-06
	if($ROW['gubun']=='1') $SUM['deposit'] += $ROW['amount'];
	if($ROW['gubun']=='2') $SUM['withdrawal'] += $ROW['amount'];

}

ksort($LIST);

$balance = 0;

$ARR_KEYS = array_keys($LIST);
for($i=0; $i<count($LIST); $i++) {

	if($LIST[$ARR_KEYS[$i]]['gubun']=='2') {
		$balance = $balance - $LIST[$ARR_KEYS[$i]]['amount'];
	}
	else {
		$balance = $balance + $LIST[$ARR_KEYS[$i]]['amount'];
	}

	$LIST[$ARR_KEYS[$i]]['balance'] += $balance;

}
$SUM['balance'] = $balance;

krsort($LIST);

$list_count = count($LIST);
$SUM['list_count'] = $list_count;

if( $request_type == 'json' ) {

	if( $order_value == 'list' ) {
		echo json_encode($LIST, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}
	else if( $order_value == 'sum' ) {
		echo json_encode($SUM, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}
	else {
		$ARR = array(
			'LIST' => $LIST,
			'SUM'  => $SUM
		);
		echo json_encode($ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}

}
else {
?>

<style>
.tblx { font-size:12px; }
.tblx td { padding:4px 6px 3px; }
</style>

<form id="frmTmpLog" name="frmTmpLog" style="<?=($print_form)?'':'display:none;';?>">
	<input type="hidden" name="action" value="insert">
	<input type="hidden" name="prd_idx" value="<?=$prd_idx?>">
	<div><b>입출금 보정등록</b></div>
	<table class="tblx table-bordered">
		<colgroup>
			<col style="width:10%">
			<col style="width:25%">
			<col style="width:20%">
			<col style="width:%">
			<col style="width:9%">
		</colgroup>
		<tr align="center" style="background:#EEE">
			<td>구분</td>
			<td>차감/가산 대상일시</td>
			<td>금액</td>
			<td>명목</td>
			<td></td>
		</tr>
		<tr align="center">
			<td>
				<select id="gubun" name="gubun" class="form-control input-sm" style="width:90px;">
					<option value='2'>차감</option>
					<option value='1'>가산</option>
				</select>
			</td>
			<td>
				<ul class="list-inline" style="width:100%;margin-top:8px;">
					<li><input type="text" id="target_date" name="target_date" value="<?=G5_TIME_YMD?>" class="form-control input-sm datepicker" style="width:90px;text-align:center;" readonly></li>
					<li>
						<select id="target_date_h" name="target_date_h" class="input-sm">
				<?
				for($i=0;$i<24;$i++) {
					$selected = (date('H')==$i) ? 'selected' : '';
					echo "<option value='".sprintf("%02d",$i)."' $selected>".sprintf("%02d",$i)."시</option>";
				}
				?>
						</select>
					</li>
					<li>
						<select id="target_date_i" name="target_date_i" class="input-sm">
				<?
				for($i=0;$i<60;$i++) {
					$selected = (date('i')==$i) ? 'selected' : '';
					echo "<option value='".sprintf("%02d",$i)."' $selected>".sprintf("%02d",$i)."분</option>";
				}
				?>
						</select>
					</li>
				</ul>
			</td>
			<td>
				<ul class="list-inline" style="width:100%;margin-top:8px;">
					<li><input type="text" id="amount" name="amount" class="form-control input-sm" onkeyUp="onlyDigit(this);NumberFormat(this);" style="width:150px;text-align:right;"></li>
					<li>원</li>
				</ul>
			</td>
			<td><input type="text" id="pretext" name="pretext" class="form-control input-sm" style="width:100%;"></td>
			<td><button type="button" id="btnSubmit" class="btn btn-sm btn-primary" style="width:100%;">등록</button></td>
		</tr>
	</table>
	<br/>
</form>

<div style="max-height:300px;overflow-y:auto">
	<table class="tblx table-bordered table-hover">
		<colgroup>
			<col style="width:6%">
			<col style="width:15%">
			<col style="width:10%">
			<col style="width:%">
			<col style="width:12%">
			<col style="width:12%">
			<col style="width:12%">
		</colgroup>
		<tr align="center" style="background:#EEE">
			<td rowspan="2">NO</td>
			<td rowspan="2">일시</td>
			<td rowspan="2">구분</td>
			<td rowspan="2">명목</td>
			<td colspan="2">금액</td>
			<td rowspan="2">계좌잔액</td>
		</tr>
		<tr align="center" style="background:#EEE">
			<td>입금액</td>
			<td>출금액</td>
		</tr>

<?
	if($list_count) {
		if($list_count > 1) {
?>
		<tr align="center" style="color:brown;background:#DDDDFF">
			<td>합계</td>
			<td colspan="3"></td>
			<td align="right"><?=number_format($SUM['deposit'])?>원</td>
			<td align="right"><?=number_format($SUM['withdrawal'])?>원</td>
			<td align="right"><?=number_format($SUM['deposit'] - $SUM['withdrawal'])?>원</td>
		</tr>
<?
		}

		$ARR_KEYS = array_keys($LIST);
		for($i=0,$j=$list_count; $i<$list_count; $i++,$j--) {

			$amount1 = ( in_array($LIST[$ARR_KEYS[$i]]['gubun_str'], array('입금','가산')) ) ? number_format($LIST[$ARR_KEYS[$i]]['amount']).'원' : '';
			$amount2 = ( in_array($LIST[$ARR_KEYS[$i]]['gubun_str'], array('출금','차감')) ) ? number_format($LIST[$ARR_KEYS[$i]]['amount']).'원' : '';

			$fcolor = ( in_array($LIST[$ARR_KEYS[$i]]['gubun_str'], array('가산','차감')) ) ? '#cc0066' : '';

			$print_pretext = $LIST[$ARR_KEYS[$i]]['pretext'];
			$print_pretext.= ( $LIST[$ARR_KEYS[$i]]['tmp_log_idx'] && ($LIST[$i]['writer_id']==$_SESSION['ss_mb_id']) ) ? " &nbsp; <a href='javascript:;' idx='tmp_log_delete' onClick='tmpLogDelete(".$LIST[$ARR_KEYS[$i]]['tmp_log_idx'].");' style='color:red'>×</a>" : "";

			echo '
		<tr style="color:'.$fcolor.'">
			<td align="center">'.$j.'</td>
			<td align="center">'.$LIST[$ARR_KEYS[$i]]['datetime'].'</td>
			<td align="center">'.$LIST[$ARR_KEYS[$i]]['gubun_str'].'</td>
			<td align="left">'.$print_pretext.'</td>
			<td align="right">'.$amount1.'</td>
			<td align="right">'.$amount2.'</td>
			<td align="right">'.number_format($LIST[$ARR_KEYS[$i]]['balance']).'원</td>
		</tr>' . PHP_EOL;

		}

	}
	else {
		echo '		<tr><td colspan="7" align="center">입금 내역이 없습니다.</td></tr>' . PHP_EOL;
	}

?>
	</table>
</div>

<script>
$(function(){
	$(".datepicker").datepicker({
		dateFormat: 'yy-mm-dd',
		changeYear: true,
		changeMonth: true,
		monthNamesShort: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
		dayNamesShort: ['일' ,'월', '화', '수', '목', '금', '토']
	});
});

$('#btnSubmit').on('click', function() {
	var fdata = $('#frmTmpLog').serialize();
	if($('input[name=amount]').val()=='') {
		alert('보정금액을 입력하십시요.');
		$('input[name=amount]').focus();
		return;
	}
	/*
	else if($('input[name=pretext]').val()=='') {
		alert('보정 명목 또는 사유를 입력하십시요.');
		$('input[name=pretext]').focus();
		return;
	}
	*/
	else {
		if(confirm('해당 보정데이터를 등록 하시겠습니까?')) {
			$.ajax({
				url:'/adm/repayment/ajax.repay_tmp_log_proc.php',
				type:'post',
				dataType:'json',
				data:fdata,
				success: function(data) {
					if(data.result=='SUCCESS') {
						//alert('등록 되었습니다.');
						loadLoanerMoneyLog();		// repay_calculate.php 에 선언중
					}
					else if(data.result=='FAIL') { alert(data.msg); }
					else { alert('등록실패!!\n관리자에게 문의 바랍니다.');}
				},
				error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
			});
		}
	}
});


tmpLogDelete = function(idx) {
	if(confirm('해당 보정데이터를 삭제 하시겠습니까?')) {
		$.ajax({
			url:'/adm/repayment/ajax.repay_tmp_log_proc.php',
			type:'post',
			dataType:'json',
			data:{
				action:'draw',
				log_idx:idx
			},
			success: function(data) {
				if(data.result=='SUCCESS') {
					alert('삭제되었습니다.');
					loadLoanerMoneyLog();		// repay_calculate.php 에 선언중
				}
				else if(data.result=='FAIL') { alert(data.msg); }
				else { alert('삭제실패!!\n관리자에게 문의 바랍니다.');}
			},
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});
	}
}
</script>
<?
}

sql_close();
exit;

?>
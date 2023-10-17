<?
###############################################################################
##  투자수익 지급 상품별 상세내역
###############################################################################

include_once('./_common.php');
//include_once(G5_LIB_PATH.'/repay_calculation.php');		// 월별 정산내역 추출함수 호출


foreach($_REQUEST as $k=>$v) { ${$_GET[$k]} = trim($v); }

if($prd_idx=='' || $turn=='') { exit; }

//$prd_idx = 184;
//$turn    = 10;


$sql = "
	SELECT
		A.turn_sno, A.interest, A.principal, A.interest_tax, A.local_tax, A.fee, A.receive_method, A.bank_name, A.bank_private_name, A.account_num, A.banking_date, A.mgtKey,
		(SELECT member_type FROM g5_member WHERE mb_no=A.member_idx) AS member_type
	FROM
		cf_product_give A
	WHERE 1
		AND A.product_idx='".$prd_idx."'
		AND A.turn='".$turn."'
	ORDER BY
		A.idx ASC";
$result = sql_query($sql);
$rcount = $result->num_rows;

$ROW2 = sql_fetch("
	SELECT
		A.start_num, A.title, A.recruit_amount, A.invest_return, A.loan_start_date, A.loan_end_date, A.loan_end_date_orig,
		B.mb_id, IF(member_type='2', mb_co_name, mb_name) AS mb_title
	FROM
		cf_product A
	LEFT JOIN
		g5_member B  ON A.loan_mb_no=B.mb_no
	WHERE
		A.idx='".$prd_idx."'");

$ROW3 = sql_fetch("SELECT IFNULL(SUM(amount),0) AS partial_repay_amount FROM cf_partial_redemption WHERE product_idx='".$prd_idx."' AND turn<='".$turn."'");

$REPAY = array(
	'LIST'       => array(),
	'LIST_SUM'   => array(),
	'LIST_B'     => array(
		'1N' => array('give_count'=>0, 'invest_interest'=>0, 'after_tax_interest'=>0, 'interest_tax'=>0, 'local_tax'=>0, 'tax'=>0, 'fee_supply'=>0, 'fee_vat'=>0, 'fee'=>0, 'last_interest'=>0),
		'2N' => array('give_count'=>0, 'invest_interest'=>0, 'after_tax_interest'=>0, 'interest_tax'=>0, 'local_tax'=>0, 'tax'=>0, 'fee_supply'=>0, 'fee_vat'=>0, 'fee'=>0, 'last_interest'=>0),
		'1C' => array('give_count'=>0, 'invest_interest'=>0, 'after_tax_interest'=>0, 'interest_tax'=>0, 'local_tax'=>0, 'tax'=>0, 'fee_supply'=>0, 'fee_vat'=>0, 'fee'=>0, 'last_interest'=>0),
		'2C' => array('give_count'=>0, 'invest_interest'=>0, 'after_tax_interest'=>0, 'interest_tax'=>0, 'local_tax'=>0, 'tax'=>0, 'fee_supply'=>0, 'fee_vat'=>0, 'fee'=>0, 'last_interest'=>0)
	),
	'LIST_B_SUM' => array()
);



for($i=0; $i<$rcount; $i++) {
	$ROW = sql_fetch_array($result);

	$start_num_title = "헬로펀딩 상품 " . $ROW2['start_num'] . "호 - 투자수익 지급 " . $turn . "회차 상세내역 &nbsp;&nbsp;&nbsp; ::: ";
	$start_num_title.= $ROW2['title'] ." / ";
	$start_num_title.= preg_replace('/-/','.',$ROW2['loan_start_date'])."~".preg_replace('/-/','.',$ROW2['loan_end_date']) . " / ";
	$start_num_title.= $ROW2['invest_return']."%";
	$start_num_title.= number_format($ROW2['recruit_amount'])."원";
	if($ROW3['partial_repay_amount'] > 0) {
		$start_num_title.= " <font color='#FF2222'>(";
		$start_num_title.= "상환:".number_format($ROW3['partial_repay_amount'])."원, ";
		$start_num_title.= "잔여:".number_format($ROW2['recruit_amount']-$ROW3['partial_repay_amount'])."원";
		$start_num_title.= ")</font>";
	}
	$start_num_title.= " / ";
	$start_num_title.= "<a href='/adm/member/member_list.php?member_group=L&key_search=A.mb_id&keyword=".$ROW2['mb_id']."' target='_blank'>".$ROW2['mb_title']." (".$ROW2['mb_id'].")</a>";


	$invest_interest    = $ROW['interest'] + $ROW['interest_tax'] + $ROW['local_tax'] + $ROW['fee'];
	$after_tax_interest = $ROW['interest'] + $ROW['fee'];
	$interest_tax       = $ROW['interest_tax'];
	$local_tax          = $ROW['local_tax'];
	$tax                = $ROW['interest_tax'] + $ROW['local_tax'];
	$fee                = $ROW['fee'];
	$fee_supply         = ceil($fee / 1.1);										// 공급가액
	$fee_vat            = $fee - $fee_supply;									// 부가세
	$last_interest      = $ROW['interest'];
	$last_amount        = $ROW['principal'] + $ROW['interest'];

	$REPAY['LIST'][$i]['principal']          = $ROW['principal'];
	$REPAY['LIST'][$i]['bank_name']          = $ROW['bank_name'];
	$REPAY['LIST'][$i]['bank_private_name']  = $ROW['bank_private_name'];
	$REPAY['LIST'][$i]['account_num']        = ($_SESSION['ss_accounting_admin']) ? $ROW['account_num'] : substr($ROW['account_num'],0,strlen($ROW['account_num'])-4) . "****";

	$REPAY['LIST'][$i]['invest_interest']    = $invest_interest;
	$REPAY['LIST'][$i]['after_tax_interest'] = $after_tax_interest;
	$REPAY['LIST'][$i]['interest_tax']       = $interest_tax;
	$REPAY['LIST'][$i]['local_tax']          = $local_tax;
	$REPAY['LIST'][$i]['tax']                = $tax;
	$REPAY['LIST'][$i]['fee_supply']         = $fee_supply;
	$REPAY['LIST'][$i]['fee_vat']            = $fee_vat;
	$REPAY['LIST'][$i]['fee']                = $fee;
	$REPAY['LIST'][$i]['last_interest']      = $last_interest;
	$REPAY['LIST'][$i]['last_amount']        = $last_amount;
	$REPAY['LIST'][$i]['mgtKey']             = $ROW['mgtKey'];

	if($ROW['is_creditor']=='Y') {
		if($ROW['member_type']=='2') {
			$REPAY['LIST_B']['2C']['give_count']         += 1;
			$REPAY['LIST_B']['2C']['principal']          += $ROW['principal'];
			$REPAY['LIST_B']['2C']['invest_interest']    += $invest_interest;
			$REPAY['LIST_B']['2C']['after_tax_interest'] += $after_tax_interest;
			$REPAY['LIST_B']['2C']['interest_tax']       += $interest_tax;
			$REPAY['LIST_B']['2C']['local_tax']          += $local_tax;
			$REPAY['LIST_B']['2C']['tax']                += $tax;
			$REPAY['LIST_B']['2C']['fee_supply']         += $fee_supply;
			$REPAY['LIST_B']['2C']['fee_vat']            += $fee_vat;
			$REPAY['LIST_B']['2C']['fee']                += $fee;
			$REPAY['LIST_B']['2C']['last_interest']      += $last_interest;
			$REPAY['LIST_B']['2C']['last_amount']        += $last_amount;
		}
		else {
			$REPAY['LIST_B']['1C']['give_count']         += 1;
			$REPAY['LIST_B']['1C']['principal']          += $ROW['principal'];
			$REPAY['LIST_B']['1C']['invest_interest']    += $invest_interest;
			$REPAY['LIST_B']['1C']['after_tax_interest'] += $after_tax_interest;
			$REPAY['LIST_B']['1C']['interest_tax']       += $interest_tax;
			$REPAY['LIST_B']['1C']['local_tax']          += $local_tax;
			$REPAY['LIST_B']['1C']['tax']                += $tax;
			$REPAY['LIST_B']['1C']['fee_supply']         += $fee_supply;
			$REPAY['LIST_B']['1C']['fee_vat']            += $fee_vat;
			$REPAY['LIST_B']['1C']['fee']                += $fee;
			$REPAY['LIST_B']['1C']['last_interest']      += $last_interest;
			$REPAY['LIST_B']['1C']['last_amount']        += $last_amount;
		}
	}
	else {
		if($ROW['member_type']=='2') {
			$REPAY['LIST_B']['2N']['give_count']         += 1;
			$REPAY['LIST_B']['2N']['principal']          += $ROW['principal'];
			$REPAY['LIST_B']['2N']['invest_interest']    += $invest_interest;
			$REPAY['LIST_B']['2N']['after_tax_interest'] += $after_tax_interest;
			$REPAY['LIST_B']['2N']['interest_tax']       += $interest_tax;
			$REPAY['LIST_B']['2N']['local_tax']          += $local_tax;
			$REPAY['LIST_B']['2N']['tax']                += $tax;
			$REPAY['LIST_B']['2N']['fee_supply']         += $fee_supply;
			$REPAY['LIST_B']['2N']['fee_vat']            += $fee_vat;
			$REPAY['LIST_B']['2N']['fee']                += $fee;
			$REPAY['LIST_B']['2N']['last_interest']      += $last_interest;
			$REPAY['LIST_B']['2N']['last_amount']        += $last_amount;
		}
		else {
			$REPAY['LIST_B']['1N']['give_count']         += 1;
			$REPAY['LIST_B']['1N']['principal']          += $ROW['principal'];
			$REPAY['LIST_B']['1N']['invest_interest']    += $invest_interest;
			$REPAY['LIST_B']['1N']['after_tax_interest'] += $after_tax_interest;
			$REPAY['LIST_B']['1N']['interest_tax']       += $interest_tax;
			$REPAY['LIST_B']['1N']['local_tax']          += $local_tax;
			$REPAY['LIST_B']['1N']['tax']                += $tax;
			$REPAY['LIST_B']['1N']['fee_supply']         += $fee_supply;
			$REPAY['LIST_B']['1N']['fee_vat']            += $fee_vat;
			$REPAY['LIST_B']['1N']['fee']                += $fee;
			$REPAY['LIST_B']['1N']['last_interest']      += $last_interest;
			$REPAY['LIST_B']['1N']['last_amount']        += $last_amount;
		}
	}

	$REPAY['LIST_SUM']['give_count']         += 1;
	$REPAY['LIST_SUM']['principal']          += $REPAY['LIST'][$i]['principal'];
	$REPAY['LIST_SUM']['invest_interest']    += $REPAY['LIST'][$i]['invest_interest'];
	$REPAY['LIST_SUM']['after_tax_interest'] += $REPAY['LIST'][$i]['after_tax_interest'];
	$REPAY['LIST_SUM']['interest_tax']       += $REPAY['LIST'][$i]['interest_tax'];
	$REPAY['LIST_SUM']['local_tax']          += $REPAY['LIST'][$i]['local_tax'];
	$REPAY['LIST_SUM']['tax']                += $REPAY['LIST'][$i]['tax'];
	$REPAY['LIST_SUM']['fee_supply']         += $REPAY['LIST'][$i]['fee_supply'];
	$REPAY['LIST_SUM']['fee_vat']            += $REPAY['LIST'][$i]['fee_vat'];
	$REPAY['LIST_SUM']['fee']                += $REPAY['LIST'][$i]['fee'];
	$REPAY['LIST_SUM']['last_interest']      += $REPAY['LIST'][$i]['last_interest'];
	$REPAY['LIST_SUM']['last_amount']        += $REPAY['LIST'][$i]['last_amount'];

}

sql_free_result($result);

$REPAY['LIST_B_SUM']['principal']          = $REPAY['LIST_B']['1N']['principal']          + $REPAY['LIST_B']['2N']['principal']          + $REPAY['LIST_B']['1C']['principal']          + $REPAY['LIST_B']['2C']['principal'];
$REPAY['LIST_B_SUM']['give_count']         = $REPAY['LIST_B']['1N']['give_count']         + $REPAY['LIST_B']['2N']['give_count']         + $REPAY['LIST_B']['1C']['give_count']         + $REPAY['LIST_B']['2C']['give_count'];
$REPAY['LIST_B_SUM']['invest_interest']    = $REPAY['LIST_B']['1N']['invest_interest']    + $REPAY['LIST_B']['2N']['invest_interest']    + $REPAY['LIST_B']['1C']['invest_interest']    + $REPAY['LIST_B']['2C']['invest_interest'];
$REPAY['LIST_B_SUM']['after_tax_interest'] = $REPAY['LIST_B']['1N']['after_tax_interest'] + $REPAY['LIST_B']['2N']['after_tax_interest'] + $REPAY['LIST_B']['1C']['after_tax_interest'] + $REPAY['LIST_B']['2C']['after_tax_interest'];
$REPAY['LIST_B_SUM']['interest_tax']       = $REPAY['LIST_B']['1N']['interest_tax']       + $REPAY['LIST_B']['2N']['interest_tax']       + $REPAY['LIST_B']['1C']['interest_tax']       + $REPAY['LIST_B']['2C']['interest_tax'];
$REPAY['LIST_B_SUM']['local_tax']          = $REPAY['LIST_B']['1N']['local_tax']          + $REPAY['LIST_B']['2N']['local_tax']          + $REPAY['LIST_B']['1C']['local_tax']          + $REPAY['LIST_B']['2C']['local_tax'];
$REPAY['LIST_B_SUM']['tax']                = $REPAY['LIST_B']['1N']['tax']                + $REPAY['LIST_B']['2N']['tax']                + $REPAY['LIST_B']['1C']['tax']                + $REPAY['LIST_B']['2C']['tax'];
$REPAY['LIST_B_SUM']['fee_supply']         = $REPAY['LIST_B']['1N']['fee_supply']         + $REPAY['LIST_B']['2N']['fee_supply']         + $REPAY['LIST_B']['1C']['fee_supply']         + $REPAY['LIST_B']['2C']['fee_supply'];
$REPAY['LIST_B_SUM']['fee_vat']            = $REPAY['LIST_B']['1N']['fee_vat']            + $REPAY['LIST_B']['2N']['fee_vat']            + $REPAY['LIST_B']['1C']['fee_vat']            + $REPAY['LIST_B']['2C']['fee_vat'];
$REPAY['LIST_B_SUM']['fee']                = $REPAY['LIST_B']['1N']['fee']                + $REPAY['LIST_B']['2N']['fee']                + $REPAY['LIST_B']['1C']['fee']                + $REPAY['LIST_B']['2C']['fee'];
$REPAY['LIST_B_SUM']['last_interest']      = $REPAY['LIST_B']['1N']['last_interest']      + $REPAY['LIST_B']['2N']['last_interest']      + $REPAY['LIST_B']['1C']['last_interest']      + $REPAY['LIST_B']['2C']['last_interest'];
$REPAY['LIST_B_SUM']['last_amount']        = $REPAY['LIST_B']['1N']['last_amount']        + $REPAY['LIST_B']['2N']['last_amount']        + $REPAY['LIST_B']['1C']['last_amount']        + $REPAY['LIST_B']['2C']['last_amount'];


//print_rr($REPAY,'font-size:11px');

//echo json_encode($REPAY, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

?>

<label><?=$start_num_title?></label>
<table class="table-bordered table-hover">
	<colgroup>
		<col style="width:7%">
		<col style="width:%">
		<col style="width:9.5%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
		<col style="width:6.8%">
	</colgroup>
	<tr>
		<th style="background:#FCE4D6">은행</th>
		<th style="background:#FCE4D6">계좌번호</th>
		<th style="background:#FCE4D6">예금주</th>
		<th style="background:#FCE4D6">원금</th>
		<th style="background:#FCE4D6">투자수익</th>
		<th style="background:#FCE4D6">이자소득세</th>
		<th style="background:#FCE4D6">지방소득세</th>
		<th style="background:#FCE4D6">원천세계</th>
		<th style="background:#FCE4D6">차감지급액</th>
		<th style="background:#FCE4D6">플랫폼이용료</th>
		<th style="background:#FCE4D6">부가세</th>
		<th style="background:#FCE4D6">플랫폼이용료계</th>
		<th style="background:#FCE4D6">세후금액</th>
		<th style="background:#FCE4D6">실지급액</th>
	</tr>
<?
$list_count = count($REPAY['LIST']);
if($list_count) {
	for($i=0,$j=1; $i<$list_count; $i++,$j++) {
?>
	<tr align="right" style="font-size:12px">
		<td align="center"><?=$REPAY['LIST'][$i]['bank_name']?></td>
		<td align="center"><?=$REPAY['LIST'][$i]['account_num']?></td>
		<td align="center"><?=$REPAY['LIST'][$i]['bank_private_name']?></td>
		<td><?=number_format($REPAY['LIST'][$i]['principal'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['invest_interest'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['interest_tax'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['local_tax'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['tax'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['after_tax_interest'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['fee_supply'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['fee_vat'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['fee'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['last_interest'])?></td>
		<td><?=number_format($REPAY['LIST'][$i]['last_amount'])?></td>
	</tr>
<?
	}
?>
	<!-- 합계 -->
	<tr align="right" style="font-size:12px;background:#F6F6F6;">
		<td colspan="3" align="center">합계</td>
		<td><?=number_format($REPAY['LIST_SUM']['principal'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['invest_interest'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['interest_tax'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['local_tax'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['tax'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['after_tax_interest'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['fee_supply'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['fee_vat'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['fee'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['last_interest'])?></td>
		<td><?=number_format($REPAY['LIST_SUM']['last_amount'])?></td>
	</tr>
	<!-- 합계 -->

	<tr align="center">
		<th style="background:#DDD;"></th>
		<th style="background:#FCE4D6">구분</th>
		<th style="background:#FCE4D6">투자건수</th>
		<th style="background:#FCE4D6">원금</th>
		<th style="background:#FCE4D6">투자수익</th>
		<th style="background:#FCE4D6">이자소득세</th>
		<th style="background:#FCE4D6">지방소득세</th>
		<th style="background:#FCE4D6">원천세계</th>
		<th style="background:#FCE4D6">차감지급액</th>
		<th style="background:#FCE4D6">플랫폼이용료</th>
		<th style="background:#FCE4D6">부가세</th>
		<th style="background:#FCE4D6">플랫폼이용료계</th>
		<th style="background:#FCE4D6">세후금액</th>
		<th style="background:#FCE4D6">실지급액</th>
	</tr>

<?
	$list_b_count = count($REPAY['LIST_B']);
	$LIST_B_KEY = array_keys($REPAY['LIST_B']);

	for($i=0; $i<$list_b_count; $i++) {

		if($LIST_B_KEY[$i]=='1N')      $gubun = "개인-일반";
		else if($LIST_B_KEY[$i]=='2N') $gubun = "기업-일반";
		else if($LIST_B_KEY[$i]=='1C') $gubun = "개인-대부";
		else if($LIST_B_KEY[$i]=='2C') $gubun = "기업-대부";

?>
	<tr align="right" style="font-size:12px">
		<th style="background:#DDD;"></th>
		<td align="center"><?=$gubun?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['give_count'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['principal'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['invest_interest'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['interest_tax'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['local_tax'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['tax'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['after_tax_interest'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['fee_supply'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['fee_vat'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['fee'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['last_interest'])?></td>
		<td><?=number_format($REPAY['LIST_B'][$LIST_B_KEY[$i]]['last_amount'])?></td>
	</tr>
<?
	}
?>
	<tr align="right" style="font-size:12px;background:#F6F6F6;">
		<th style="background:#DDD;"></th>
		<td align="center">합계</td>
		<td><?=number_format($REPAY['LIST_B_SUM']['give_count'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['principal'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['invest_interest'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['interest_tax'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['local_tax'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['tax'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['after_tax_interest'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['fee_supply'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['fee_vat'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['fee'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['last_interest'])?></td>
		<td><?=number_format($REPAY['LIST_B_SUM']['last_amount'])?></td>
	</tr>
<?
}
else {
?>
	<tr>
		<td colspan="13" align="center">데이터가 없습니다.</th>
	</tr>
<?
}
?>
</table>

<?
usleep(500000);
?>
<?

###############################################################################
# 상품별 정산 상세
# 상품-회차별 정상상환, 일부상환, 연체상환 내역 출력
# /adm/repayment/repay_calculate.php 에 의해 호출
# 필수파라미터 : 상품번호(prd_idx)
# 필수파라미터 : 상품번호(turn)
# 옵션파라미터 : 회원번호(mb_no)
###############################################################################

$base_path  = "/home/crowdfund/public_html";
include_once($base_path . '/common.php');
include_once($base_path . '/lib/crypt.lib.php');
include_once($base_path . '/adm/repayment/repayment_util.lib.php');

//print_r($_REQUEST);

$prd_idx = trim($_REQUEST['prd_idx']);
$turn    = trim($_REQUEST['turn']);
$mb_no   = trim($_REQUEST['mb_no']);

if(!$prd_idx) { sql_close(); exit; }

$TBL['product']       = 'cf_product';
$TBL['invest']        = 'cf_product_invest';
$TBL['invest_detail'] = 'cf_product_invest_detail';
$TBL['success']       = 'cf_product_success';
$TBL['give']          = 'cf_product_give_test';
$TBL['member']        = 'g5_member';
$TBL['bill']          = getBillTable($prd_idx);


$PRDT = sql_fetch("SELECT idx, state, loan_start_date, loan_end_date, invest_end_date, ib_trust, invest_period, invest_days, recruit_amount FROM {$TBL['product']} WHERE idx='".$prd_idx."'");
if(!$PRDT['idx']) { sql_close(); exit; }

// 최초투자자번호
$INI['first_invesor_idx'] = '';

$invest_sort = "DESC";

// 투자회원정보중 정산용 정보 추출
$mb_sql = "
	SELECT
		idx AS invest_idx, member_idx, amount, prin_rcv_no
	FROM
		{$TBL['invest']}
	WHERE 1
		AND product_idx='".$prd_idx."' AND invest_state='Y'";
$mb_sql.= ($mb_no) ? " AND member_idx='".$mb_no."'" : "";
$mb_sql.= " ORDER BY idx {$invest_sort}";
//print_rr($mb_sql, 'font-size:12px');
$mb_res  = sql_query($mb_sql);
$mb_rows = sql_num_rows($mb_res);

for($i=0; $i<$mb_rows; $i++) {
	if( $R = sql_fetch_array($mb_res) ) {
		if(empty($INI['first_invesor_idx'])) { $INI['first_invesor_idx'] = $R['member_idx']; }
		if(!$R['prin_rcv_no']) $R['prin_rcv_no'] = 'M' . $R['member_idx'] . 'P' . $prd_idx . 'I' . $R['invest_idx'];

		$INVESTOR[$R['member_idx']] = $R;
	}
}
//print_rr($INVESTOR,'font-size:12px');
sql_free_result($mb_res);



// 특별처리상품 플래그 (초기상품중 종료일이 5일 이전일때 이전회차와 최종상환회차를 동일회차로 처리한 상품 구분)
$exceptionProduct = ($PRDT['idx'] < 162  && $PRDT['ib_trust']=='N' && substr($PRDT['loan_end_date'],-2) <= '05') ? 1 : 0;
$shortTermProduct = ($PRDT['invest_days']>0) ? 1 : 0;

if( in_array($PRDT['state'], array('','1','2','5','8','4','9')) ) {
	if( $PRDT['invest_end_date'] && ($PRDT['loan_start_date'] && $PRDT['loan_end_date']) ) {
		$INI['total_invest_days'] = repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']);		// 상환대상일수
		$INI['total_repay_turn']  = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct);		// 상환차수
	}
}
//echo "INI : "; print_rr($INI, 'font-size:11px;padding:8px;');

$sql = "
	SELECT
		`date` AS schedule_date,
		turn_sno, is_overdue,
		IFNULL(SUM(principal),0) AS principal,
		IFNULL(SUM(interest+interest_tax+local_tax+fee),0) AS invest_interest,
		IFNULL(SUM(interest),0) AS interest,
		IFNULL(SUM(interest_tax+local_tax),0) AS tax,
		IFNULL(SUM(interest_tax),0) AS interest_tax,
		IFNULL(SUM(local_tax),0) AS local_tax,
		IFNULL(SUM(fee),0) AS fee
	FROM
		{$TBL['give']}
	WHERE 1
		AND product_idx='".$prd_idx."' AND turn='".$turn."'";
$sql.= ($mb_no) ? " AND member_idx='".$mb_no."'" : "";
$sql.= " GROUP BY turn_sno, is_overdue";
$sql.= "
	ORDER BY
		(CASE WHEN is_overdue='N' THEN 1 ELSE 2 END) ASC,
		turn_sno ASC";
//print_rr($sql, 'font-size:12px');
$res = sql_query($sql);
$rows = sql_num_rows($res);

// turn_sno 루프
for($i=0; $i<$rows; $i++) {

	$REPAY[$i] = sql_fetch_array($res);


	$remain_principal = 0;

	$sql2 = "
		SELECT
			A.*
		FROM
			{$TBL['give']} A
		WHERE 1
			AND A.product_idx='".$prd_idx."' AND A.turn='".$turn."' AND A.turn_sno='".$REPAY[$i]['turn_sno']."' AND A.is_overdue='".$REPAY[$i]['is_overdue']."'";
	$sql2.= ($mb_no) ? " AND A.member_idx='".$mb_no."'" : "";
	$sql2.= "
		ORDER BY
			invest_idx {$invest_sort}";
	//print_rr($sql2, 'font-size:12px');
	$res2 = sql_query($sql2);
	$rows2 = sql_num_rows($res2);
	for($j=0; $j<$rows2; $j++) {
		$REPAY[$i]['LIST'][$j] = sql_fetch_array($res2);
		$REPAY[$i]['LIST'][$j]['tax'] =  array_sum(array($REPAY[$i]['LIST'][$j]['interest_tax'],$REPAY[$i]['LIST'][$j]['local_tax']));
		$REPAY[$i]['LIST'][$j]['invest_interest'] = array_sum(array($REPAY[$i]['LIST'][$j]['interest'],$REPAY[$i]['LIST'][$j]['tax'],$REPAY[$i]['LIST'][$j]['fee']));


		$bill_sql_add = ($REPAY[$i]['turn_sno'] > 0) ? " AND turn_sno='".$REPAY[$i]['turn_sno']."'" : "";
		$bill_sql = "
			SELECT
				partial_principal, remain_principal
			FROM
				{$TBL['bill']}
			WHERE 1
				AND invest_idx = '".$INVESTOR[$REPAY[$i]['LIST'][$j]['member_idx']]['invest_idx']."'
				AND turn = '".$turn."'
				{$bill_sql_add}
				AND is_overdue = '".$REPAY[$i]['is_overdue']."'
			ORDER BY
				idx DESC LIMIT 1";
		$BILLROW = sql_fetch($bill_sql);
		//print_rr($bill_sql,'font-size:12px');

		$REPAY[$i]['LIST'][$j]['remain_principal'] = $BILLROW['remain_principal'];
		$remain_principal += $BILLROW['remain_principal'];

		unset($BILLROW);

	}

	$detailListCnt = count($REPAY[$i]['LIST']);
	//print_rr($REPAY[$i], 'text-align:left;font-size:12px;');

	$sqlx = "
		SELECT
			MIN(bill_date) AS bill_start_date,
			MAX(bill_date) AS bill_end_date
		FROM
			{$TBL['bill']}
		WHERE 1
			AND product_idx='".$prd_idx."' AND member_idx='".$INI['first_invesor_idx']."'
			AND turn='".$turn."' AND turn_sno='".$REPAY[$i]['turn_sno']."' AND is_overdue='".$REPAY[$i]['is_overdue']."'";
	$BILL = sql_fetch($sqlx);


	$day_count = 0;
	if($REPAY[$i]['turn_sno']==0) {
		if($REPAY[$i]['is_overdue']=='N') {

			// 정상회차 인 경우 해당회차전체중 최종일을 정산대상종료일로 한다.
			$sqlx = "SELECT	 MAX(bill_date) AS bill_end_date FROM {$TBL['bill']} WHERE 1 AND member_idx='".$INI['first_invesor_idx']."' AND product_idx='".$prd_idx."' AND turn='".$turn."'";
			$BILL['bill_end_date'] = sql_fetch($sqlx)['bill_end_date'];

			$day_count = (strtotime($BILL['bill_end_date']." 23:59:59") - strtotime($BILL['bill_start_date']." 00:00:00")) / 86400;
			if($day_count >= 1) $day_count = ceil($day_count);
		}
	}
	else {
		$BILL['bill_start_date'] = $BILL['bill_end_date'] = NULL;
	}

	// 회차요약 및 항목별 합산액 배열화
	$TURN_SUMMARY = array(
    'schedule_date'   => $REPAY[$i]['schedule_date']
   ,'valid_schedule_date' =>  getUsableDate($REPAY[$i]['schedule_date'])
	 ,'turn_sno'        => $REPAY[$i]['turn_sno']
   ,'is_overdue'      => $REPAY[$i]['is_overdue']
   ,'bill_start_date' => $BILL['bill_start_date']
   ,'bill_end_date'   => $BILL['bill_end_date']
   ,'day_count'       => $day_count
	 ,'remain_principal'=> $remain_principal
	 ,'principal'       => $REPAY[$i]['principal']
   ,'invest_interest' => $REPAY[$i]['invest_interest']
   ,'interest'        => $REPAY[$i]['interest']
   ,'tax'             => $REPAY[$i]['tax']
   ,'interest_tax'    => $REPAY[$i]['interest_tax']
   ,'local_tax'       => $REPAY[$i]['local_tax']
   ,'fee'             => $REPAY[$i]['fee']
	);
	echo "<div style='font-size:12px;margin-bottom:10px;'>TURN_SUMMARY :<br/>\n"; print_r($TURN_SUMMARY); echo "</div>\n";

	$print_title = $turn . " / " . $INI['total_repay_turn'] . "회차 정산내역";
	if($REPAY[$i]['is_overdue']=='Y') {
		$print_title.= " - 연체상환";
	}
	else {
		if($REPAY[$i]['turn_sno'] > 0) $print_title.= " - 원금일부상환(".$REPAY[$i]['turn_sno'].")";
	}
	if($REPAY[$i]['is_overdue']=='Y') $print_title = "<font color=red>".$print_title."</font>";


	$print_valid_schedule_date = "";
	if($TURN_SUMMARY['schedule_date']==$TURN_SUMMARY['valid_schedule_date']) {
		$print_schedule_date = $TURN_SUMMARY['schedule_date'] . " " . get_yoil($TURN_SUMMARY['valid_schedule_date'], 1);
		$print_valid_schedule_date = "";
	}
	else {
		$print_schedule_date = $TURN_SUMMARY['schedule_date'];
		$print_valid_schedule_date = "(유효일: " . $TURN_SUMMARY['valid_schedule_date'] . " " . get_yoil($TURN_SUMMARY['valid_schedule_date'], 1) . ")";
	}


?>

				<style>
				ul.investor {list-style:none; width:100%; margin:0; padding:0; display:inline-block;}
				ul.investor > li { float:left; }
				ul.investor > li:nth-child(odd) { width:20%; min-width:70px; color:#888; }
				ul.investor > li:nth-child(even) { text-align:center; }
				</style>

				<table id="table<?=$prd_idx.$turn.$i?>" class="table table-striped table-bordered" style="width:100%;margin-bottom:0; font-size:12px">
					<colgroup>
						<col style="width:5%">
						<col style="%">
						<col style="width:7.8%">
						<col style="width:7.8%">
						<col style="width:7.8%"><col style="width:7.8%"><col style="width:7.8%"><col style="width:7.8%"><col style="width:7.8%">
						<col style="width:7.8%">
						<col style="width:7.8%">
					</colgroup>
					<thead style="font-size:13px; background:#F8F8EF;">
						<tr>
							<td colspan="11" style="text-align:left;padding-left:20px;">
								<strong><?=$print_title?></strong>
								<span style="margin-left:30px">지급예정일 : <?=$print_schedule_date?> <?=$print_valid_schedule_date?></span>
								<span style="margin-left:20px"><? if($TURN_SUMMARY['day_count']){ ?>정산대상기간 : <?=$TURN_SUMMARY['bill_start_date']?> ~ <?=$TURN_SUMMARY['bill_end_date']?><? } ?></span>
								<span style="margin-left:20px"><? if($TURN_SUMMARY['day_count']){ ?>정산일수 : <?=$TURN_SUMMARY['day_count']?>일<? } ?></span>
							</td>
						</tr>
						<tr>
							<th class="border_r">NO</th>
							<th class="border_r">투자자정보</th>

							<th>투자원금</th>
							<th class="border_r">잔여투자원금</th>

							<th>세전이자</th>
							<th>플랫폼이용료</th>
							<th>원천징수</th>
							<th>지급이자</th>
							<th class="border_r">지급원금</th>
							<th class="border_r">지급여부</th>
							<th>세금계산서</th>
						</tr>
						<tr style="background:#EEEEFF;color:brown">
							<th class="border_r">합계</th>
							<th class="border_r"></th>
							<th style="text-align:right"><?=number_format($PRDT['recruit_amount'])?></th>
							<th class="border_r" style="text-align:right"><?=number_format($TURN_SUMMARY['remain_principal'])?></th>
							<th style="text-align:right"><?=number_format($TURN_SUMMARY['invest_interest'])?></th>
							<th style="text-align:right"><?=number_format($TURN_SUMMARY['fee'])?></th>
							<th style="text-align:right"><?=number_format($TURN_SUMMARY['tax'])?></th>
							<th style="text-align:right"><?=number_format($TURN_SUMMARY['interest'])?></th>
							<th class="border_r" style="text-align:right"><?=number_format($TURN_SUMMARY['principal'])?></th>
							<th class="border_r"></th>
							<th></th>
						</tr>
					</thead>
					<tbody style="font-size:12px">
<?
	for($j=0,$num=$detailListCnt; $j<$detailListCnt; $j++,$num--) {

		if($j==0) { echo "<div style='font-size:12px;margin-bottom:10px;'>LIST[$j] :<br/>\n"; print_r($REPAY[$i]['LIST'][$j]); echo "</div>\n"; }

		$PRINT = $REPAY[$i]['LIST'][$j];

		while( list($key,$value) = each($PRINT) ) {
			if( in_array($key, array('invest_interest','fee','tax','interest','principal')) ) {
				$PRINT[$key] = number_format($PRINT[$key]);
				if($PRINT[$key]==0) $PRINT[$key] = '<font color=#aaaaaa>'.$PRINT[$key].'</font>';
			}
		}

		// 정산처리일이 지난 회차의 회원정보는 회원로그 테이블 또는 탈퇴회원테이블을 이용하여 회원정보를 가져온다.
		if(substr($TURN_SUMMARY['bill_end_date'],0,7) < date('Y-m'))	{
			$MBLOG = newGetMember($REPAY[$i]['LIST'][$j]['member_idx'], $TURN_SUMMARY['bill_end_date']);
		}
		else {
			$MBLOG = newGetMember($REPAY[$i]['LIST'][$j]['member_idx']);
		}

		$print_member_type = "";
		$print_member_type.= ($MBLOG['member_type']=='2') ? "법인" : "개인";
		$print_member_type.= ($MBLOG['is_creditor']=='Y') ? "-대부" : "";

		$print_investor_name = "";
		if($MBLOG['member_type']=='2') {
			$print_investor_name = $MBLOG['mb_co_name'];
		}
		else {
			$print_investor_name = ($_SESSION['ss_accounting_admin']) ? $MBLOG['mb_name'] : hanStrMasking($MBLOG['mb_name']);
		}

		$print_acct_info = "";
		if($REPAY[$i]['LIST'][$j]['account_num']) {
			$print_acct_info = $REPAY[$i]['LIST'][$j]['bank_name'] . " " . $REPAY[$i]['LIST'][$j]['account_num'];
			if($_SESSION['ss_accounting_admin']) $print_acct_info.= " " . $REPAY[$i]['LIST'][$j]['bank_private_name'];
		}
		else {
			$print_acct_info = $BANK[$MBLOG['bank_code']] . " " . $MBLOG['account_num'];
			if($_SESSION['ss_accounting_admin']) $print_acct_info.= " " . $MBLOG['bank_private_name'];
		}

		$bgcolor = ($MBLOG['member_type']=='2') ? '#FFF2CC' : '';
		$bgcolor = ($MBLOG['is_creditor']=='Y') ? '#FCE4D6' : $bgcolor;

		$print_taxinvoice = "";
		if($REPAY[$i]['LIST'][$j]['mgtKey']) {
			if(preg_match('/P_/i', $REPAY[$i]['LIST'][$j]['mgtKey']))       $taxinvoicetype = '현금영수증';
			else if(preg_match('/C_/i', $REPAY[$i]['LIST'][$j]['mgtKey']))  $taxinvoicetype = '세금계산서';
			else $taxinvoicetype = '직접확인';

			$print_taxinvoice = '<a href="/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL.php?mgtKey='.$REPAY[$i]['LIST'][$j]['mgtKey'].'" target="_blank">'.$taxinvoicetype.'</a>';
		}

		$receive_method = ($REPAY[$i]['LIST'][$j]['receive_method']) ? $REPAY[$i]['LIST'][$j]['receive_method'] : $INVESTOR[$REPAY[$i]['LIST'][$j]['member_idx']]['receive_method'];
		$print_receive_method = ($receive_method=='1') ? '<font color=royalblue>환급계좌</font>' : '<font color=#FF2222>예치금</font>';

		/*
		if($j==0) {
			print_rr($REPAY[$i]['LIST'][$j],'font-size:11px');
		}
		*/

?>

						<tr align="right" <?if($bgcolor){?>style="background:<?=$bgcolor?>;"<?}?>>
							<td align="center" class="border_r"><?=$num?></td>
							<td align="center" class="border_r" style="padding:0">
								<ul class="investor" style="border-bottom:1px dotted #ddd; margin-top:8px;">
									<li>수취권번호</li>
									<li><?=$INVESTOR[$REPAY[$i]['LIST'][$j]['prin_rcv_no']?></li>
								</ul>
								<ul class="investor" style="border-bottom:1px dotted #ddd">
									<li>회원구분</li>
									<li><?=$print_member_type?></li>
								</ul>
								<ul class="investor" style="border-bottom:1px dotted #ddd">
									<li>아이디</li>
									<li><?=$MBLOG['mb_id']?> <? if($_SESSION['ss_accounting_admin']) { echo "(" . $print_investor_name . ")"; } ?></li>
								</ul>
								<ul class="investor" style="border-bottom:1px dotted #ddd">
									<li>간편조회</li>
									<li>
										<a href="/adm/member/member_list.php?key_search=A.mb_no&keyword=<?=$REPAY[$i]['LIST'][$j]['member_idx']?>" target="_blank" class="btn btn-sm btn-success" style="font-size:11px; line-height:11px; padding:3px 4px;">회원정보</a>
										<a href="/adm/repayment/invest_list.php?iv_state=Y&field=C.mb_no&keyword=<?=$REPAY[$i]['LIST'][$j]['member_idx']?>" target="_blank" class="btn btn-sm btn-success" style="font-size:11px; line-height:11px; padding:3px 4px;">투자내역</a>
										<? if($mb_no) { ?>
										<a href="?idx=<?=$prd_idx?>" class="btn btn-sm btn-info" style="font-size:11px; line-height:11px; padding:3px 4px;">전체상환내역 출력</a>
										<? }else{?>
										<a href="?idx=<?=$prd_idx?>&mb_no=<?=$REPAY[$i]['LIST'][$j]['member_idx']?>" class="btn btn-sm btn-info" style="font-size:11px; line-height:11px; padding:3px 4px;">본상환내역만 출력</a>
										<? } ?>
									</li>
								</ul>
								<ul class="investor" style="border-bottom:1px dotted #ddd">
									<li>수취방식</li>
									<li><?=$print_receive_method?></li>
								</ul>
								<ul class="investor">
									<li>지급계좌</li>
									<li><?=$print_acct_info?></li>
								</ul>
							</td>
							<td><?=number_format($INVESTOR[$REPAY[$i]['LIST'][$j]['member_idx']]['amount'])?></td>
							<td class="border_r"><?=number_format($REPAY[$i]['LIST'][$j]['remain_principal'])?></td>
							<td>
								<?=$PRINT['invest_interest']?>
								<div style="width:100%;margin-top:20px;">
									<button type="button" onClick="openBillDetail('<?=$prd_idx?>','<?=$turn?>','<?=$REPAY[$i]['LIST'][$j]['member_idx']?>','<?=$TURN_SUMMARY['is_overdue']?>','<?=($_REQUEST['lib']=='20210102')?'old':''?>');" class="btn btn-xs btn-default" style="width:100%;">상세보기</button>
								</div>
							</td>
							<td><?=$PRINT['fee']?></td>
							<td><?=$PRINT['tax']?></td>
							<td><?=$PRINT['interest']?></td>
							<td class="border_r"><?=$PRINT['principal']?></td>
							<td class="border_r" align="center"><?=$REPAY[$i]['LIST'][$j]['banking_date']?></td>
							<td align="center"><?=$print_taxinvoice?></td>
						</tr>

<?
	}
?>
					</tbody>
				</table>
				<script>
				$(document).ready(function() {
					$('#table<?=$prd_idx.$turn.$i?>').floatThead();
				});
				</script>

				<br/><br/>

<?
}

sql_close();
exit;

?>
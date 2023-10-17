<?
///////////////////////////////////////////////////////////////////////////////
// 특정일 기준 상품카테고리별 투자자자격별 대출잔액
///////////////////////////////////////////////////////////////////////////////

include_once("_common.php");

if($_REQUEST['target_date']) {
	$target_date = trim($_REQUEST['target_date']);
}
else {
	$target_date = '2021-08-31';
}


function getActiveProductIdx($std_date, $category, $category2, $mortgage_guarantees, $loaner_member_type='') {

	global $g5;

	$where_ca = " AND category = '$category'";

	if($category2 != 'all') $where_ca.= " AND category = '$category'";
	if($mortgage_guarantees != 'all') $where_ca.= " AND mortgage_guarantees = '$mortgage_guarantees'";

	if($loaner_member_type) $where_loaner_type = " AND B.member_type = '".$loaner_member_type."'";


	$sql = "
		SELECT
			idx, recruit_amount
		FROM
			cf_product A
		LEFT JOIN
			g5_member B  ON A.loan_mb_no=B.mb_no
		WHERE 1
			AND (display='Y' AND isTest = '')
			AND loan_start_date <= '$std_date'
			AND (loan_end_date > '$std_date' OR state='8')
			$where_ca
			$where_loaner_type
		ORDER BY
			idx";
	$res = sql_query($sql);
	$rows = $res->num_rows;
	//print_rr($sql,'font-size:12px');

	$ARR = array(
		'product_count'  => $rows,
		'recruit_amount' => 0,
		'idxs'           => ''
	);


	$recruit_amount = 0;
	$idxs = '';

	for($i=0,$j=1; $i<$rows; $i++,$j++) {
		$row = sql_fetch_array($res);

		$recruit_amount += $row['recruit_amount'];
		$idxs.= ($j<$rows) ? $row['idx'].',' : $row['idx'];
	}

	$ARR['recruit_amount'] = $recruit_amount;
	$ARR['idxs'] = $idxs;

	return $ARR;

}

function getInvestorIdx($product_idx) {

	global $g5;

	$res = sql_query("SELECT member_idx FROM cf_product_invest WHERE product_idx='".$product_idx."' AND invest_state='Y' ORDER BY idx ASC");
	$rows = $res->num_rows;

	$member_idxs = '';

	for($i=0,$j=1; $i<$rows; $i++,$j++) {
		$row = sql_fetch_array($res);

		$member_idxs.= ($j<$rows) ? $row['member_idx'].',' : $row['member_idx'];

	}

	return $member_idxs;

}



function productInvestInfo($product_idxs, $standard_date) {

	global $g5;

	$PROD = explode(",", $product_idxs);

	$ARR['invest_count']  = 0;
	$ARR['invest_amount'] = 0;
	$ARR['paid_amount']   = 0;
	$ARR['live_amount']   = 0;

	$ARR['corp_invest_count']  = 0;
	$ARR['corp_invest_amount'] = 0;
	$ARR['corp_paid_amount']   = 0;
	$ARR['corp_live_amount']   = 0;

	$ARR['indi1_invest_count']  = 0;
	$ARR['indi1_invest_amount'] = 0;
	$ARR['indi1_paid_amount']   = 0;
	$ARR['indi1_live_amount']   = 0;

	$ARR['indi2_invest_count']  = 0;
	$ARR['indi2_invest_amount'] = 0;
	$ARR['indi2_paid_amount']   = 0;
	$ARR['indi2_live_amount']   = 0;

	$ARR['indi3_invest_count']  = 0;
	$ARR['indi3_invest_amount'] = 0;
	$ARR['indi3_paid_amount']   = 0;
	$ARR['indi3_live_amount']   = 0;

	for($i=0; $i<count($PROD); $i++) {

		$investor_idxs = getInvestorIdx($PROD[$i]);
		$MIDX = explode(",", $investor_idxs);

		for($k=0; $k<count($MIDX); $k++) {

			$ARR['invest_count'] += 1;

			$sql = "
				SELECT
					mb_no, mb_name, mb_co_name, member_type, member_investor_type,
					(SELECT amount FROM cf_product_invest WHERE product_idx='".$PROD[$i]."' AND member_idx = mb_no AND invest_state='Y') AS invest_amount,
					(SELECT IFNULL(SUM(principal),0) FROM cf_product_give WHERE product_idx='".$PROD[$i]."' AND member_idx = mb_no AND banking_date <= '{$standard_date} 23:59:59') AS paid_amount
				FROM
					g5_member
				WHERE
					mb_no = '".$MIDX[$k]."'";
			//print_rr($sql,'font-size:12px;line-height:14px');
			$R = sql_fetch($sql);
			//echo "<span style='font-size:12px'>"; print_r($R); echo "</span><br/>\n";

			$ARR['invest_amount'] += $R['invest_amount'];
			$ARR['paid_amount']   += $R['paid_amount'];

			if($R['member_type']=='2') {
				$ARR['corp_invest_count']  += 1;
				$ARR['corp_invest_amount'] += $R['invest_amount'];
				$ARR['corp_paid_amount']   += $R['paid_amount'];
			}
			else {
				if($R['member_investor_type']=='1') {
					$ARR['indi1_invest_count']  += 1;
					$ARR['indi1_invest_amount'] += $R['invest_amount'];
					$ARR['indi1_paid_amount']   += $R['paid_amount'];
				}
				if($R['member_investor_type']=='2') {
					$ARR['indi2_invest_count']  += 1;
					$ARR['indi2_invest_amount'] += $R['invest_amount'];
					$ARR['indi2_paid_amount']   += $R['paid_amount'];
				}
				if($R['member_investor_type']=='3') {
					$ARR['indi3_invest_count']  += 1;
					$ARR['indi3_invest_amount'] += $R['invest_amount'];
					$ARR['indi3_paid_amount']   += $R['paid_amount'];
				}
			}


			$PF['INVEST_LIST'][] = $R;			// 투자자 내역도 배열에 포함시

		}		// end for($k=0; $k<count($MIDX); $k++)


		$ARR['live_amount'] = $ARR['invest_amount'] - $ARR['paid_amount'];

		$ARR['corp_live_amount']  = $ARR['corp_invest_amount'] - $ARR['corp_paid_amount'];
		$ARR['indi1_live_amount'] = $ARR['indi1_invest_amount'] - $ARR['indi1_paid_amount'];
		$ARR['indi2_live_amount'] = $ARR['indi2_invest_amount'] - $ARR['indi2_paid_amount'];
		$ARR['indi3_live_amount'] = $ARR['indi3_invest_amount'] - $ARR['indi3_paid_amount'];

	}		// end for($i=0; $i<count($PROD); $i++)

	return $ARR;

}


// 부동산-PF
$CORP_LOANER_PF = getActiveProductIdx($target_date, '2', '', '', '2');		// 법인차주
$PF_C = productInvestInfo($CORP_LOANER_PF['idxs'], $target_date);

$INDI_LOANER_PF = getActiveProductIdx($target_date, '2', '', '', '1');		// 개인차주
$PF_I = productInvestInfo($INDI_LOANER_PF['idxs'], $target_date);


// 부동산-주담대
$CORP_LOANER_JD= getActiveProductIdx($target_date, '2', '', '1', '2');		// 법인차주
$JD_C = productInvestInfo($CORP_LOANER_JD_PRODUCT['idxs'], $target_date);

$INDI_LOANER_JD = getActiveProductIdx($target_date, '2', '', '1', '1');		// 개인차주
$JD_I = productInvestInfo($INDI_LOANER_JD['idxs'], $target_date);


// 매출채권
$CORP_LOANER_MC= getActiveProductIdx($target_date, '3', 'all', '', '2');		// 법인차주
$MC_C = productInvestInfo($CORP_LOANER_MC['idxs'], $target_date);

$INDI_LOANER_MC = getActiveProductIdx($target_date, '3', 'all', '', '1');		// 개인차주 (==> 개인차주가 없음)
$MC_I = productInvestInfo($INDI_LOANER_MC['idxs'], $target_date);

sql_close();

?>

<style>
th, td { padding:4px; }
</style>

<h2><?=date("Y년 m월 d일", strtotime($target_date))?>기준 대출 및 투자내역</h2>

<table border="1" style="width:100%;border-collapse:collapse;font-size:12px;">
	<tr bgcolor="#F8F8EF">
		<th rowspan="2">상품구분</th>
		<th rowspan="2">차주구분</th>
		<th colspan="4">대출</th>
		<th colspan="4">법인</th>
		<th colspan="4">개인-일반</th>
		<th colspan="4">개인-소득적격</th>
		<th colspan="4">개인-전문</th>
	</tr>
	<tr bgcolor="#F8F8EF">
		<th>건수</th>
		<th>금액</th>
		<th>상환액</th>
		<th>잔액</th>

		<th>투자건수</th>
		<th>투자금액</th>
		<th>상환금액</th>
		<th>투자잔액</th>

		<th>투자건수</th>
		<th>투자금액</th>
		<th>상환금액</th>
		<th>투자잔액</th>

		<th>투자건수</th>
		<th>투자금액</th>
		<th>상환금액</th>
		<th>투자잔액</th>

		<th>투자건수</th>
		<th>투자금액</th>
		<th>상환금액</th>
		<th>투자잔액</th>
	</tr>

	<tr align="right">
		<td align="center" rowspan="2">부동산-PF</td>
		<td align="center">법인</td>

		<td><?=(int)$CORP_LOANER_PF['product_count']?></td>
		<td><?=(int)$CORP_LOANER_PF['recruit_amount']?></td>
		<td><?=$PF_C['paid_amount']?></td>
		<td><?=$PF_C['live_amount']?></td>

		<td><?=$PF_C['corp_invest_count']?></td>
		<td><?=$PF_C['corp_invest_amount']?></td>
		<td><?=$PF_C['corp_paid_amount']?></td>
		<td><?=$PF_C['corp_live_amount']?></td>

		<td><?=$PF_C['indi1_invest_count']?></td>
		<td><?=$PF_C['indi1_invest_amount']?></td>
		<td><?=$PF_C['indi1_paid_amount']?></td>
		<td><?=$PF_C['indi1_live_amount']?></td>

		<td><?=$PF_C['indi2_invest_count']?></td>
		<td><?=$PF_C['indi2_invest_amount']?></td>
		<td><?=$PF_C['indi2_paid_amount']?></td>
		<td><?=$PF_C['indi2_live_amount']?></td>

		<td><?=$PF_C['indi3_invest_count']?></td>
		<td><?=$PF_C['indi3_invest_amount']?></td>
		<td><?=$PF_C['indi3_paid_amount']?></td>
		<td><?=$PF_C['indi3_live_amount']?></td>
	</tr>
	<tr align="right">
		<td align="center">개인</td>

		<td><?=(int)$INDI_LOANER_PF['product_count']?></td>
		<td><?=(int)$INDI_LOANER_PF['recruit_amount']?></td>
		<td><?=$PF_I['paid_amount']?></td>
		<td><?=$PF_I['live_amount']?></td>

		<td><?=$PF_I['corp_invest_count']?></td>
		<td><?=$PF_I['corp_invest_amount']?></td>
		<td><?=$PF_I['corp_paid_amount']?></td>
		<td><?=$PF_I['corp_live_amount']?></td>

		<td><?=$PF_I['indi1_invest_count']?></td>
		<td><?=$PF_I['indi1_invest_amount']?></td>
		<td><?=$PF_I['indi1_paid_amount']?></td>
		<td><?=$PF_I['indi1_live_amount']?></td>

		<td><?=$PF_I['indi2_invest_count']?></td>
		<td><?=$PF_I['indi2_invest_amount']?></td>
		<td><?=$PF_I['indi2_paid_amount']?></td>
		<td><?=$PF_I['indi2_live_amount']?></td>

		<td><?=$PF_I['indi3_invest_count']?></td>
		<td><?=$PF_I['indi3_invest_amount']?></td>
		<td><?=$PF_I['indi3_paid_amount']?></td>
		<td><?=$PF_I['indi3_live_amount']?></td>
	</tr>

	<tr align="right">
		<td align="center" rowspan="2">부동산-주택담보</td>
		<td align="center">법인</td>

		<td><?=(int)$CORP_LOANER_JD['product_count']?></td>
		<td><?=(int)$CORP_LOANER_JD['recruit_amount']?></td>
		<td><?=$JD_C['paid_amount']?></td>
		<td><?=$JD_C['live_amount']?></td>

		<td><?=$JD_C['corp_invest_count']?></td>
		<td><?=$JD_C['corp_invest_amount']?></td>
		<td><?=$JD_C['corp_paid_amount']?></td>
		<td><?=$JD_C['corp_live_amount']?></td>

		<td><?=$JD_C['indi1_invest_count']?></td>
		<td><?=$JD_C['indi1_invest_amount']?></td>
		<td><?=$JD_C['indi1_paid_amount']?></td>
		<td><?=$JD_C['indi1_live_amount']?></td>

		<td><?=$JD_C['indi2_invest_count']?></td>
		<td><?=$JD_C['indi2_invest_amount']?></td>
		<td><?=$JD_C['indi2_paid_amount']?></td>
		<td><?=$JD_C['indi2_live_amount']?></td>

		<td><?=$JD_C['indi3_invest_count']?></td>
		<td><?=$JD_C['indi3_invest_amount']?></td>
		<td><?=$JD_C['indi3_paid_amount']?></td>
		<td><?=$JD_C['indi3_live_amount']?></td>
	</tr>
	<tr align="right">
		<td align="center">개인</td>

		<td><?=(int)$INDI_LOANER_JD['product_count']?></td>
		<td><?=(int)$INDI_LOANER_JD['recruit_amount']?></td>
		<td><?=$JD_I['paid_amount']?></td>
		<td><?=$JD_I['live_amount']?></td>

		<td><?=$JD_I['corp_invest_count']?></td>
		<td><?=$JD_I['corp_invest_amount']?></td>
		<td><?=$JD_I['corp_paid_amount']?></td>
		<td><?=$JD_I['corp_live_amount']?></td>

		<td><?=$JD_I['indi1_invest_count']?></td>
		<td><?=$JD_I['indi1_invest_amount']?></td>
		<td><?=$JD_I['indi1_paid_amount']?></td>
		<td><?=$JD_I['indi1_live_amount']?></td>

		<td><?=$JD_I['indi2_invest_count']?></td>
		<td><?=$JD_I['indi2_invest_amount']?></td>
		<td><?=$JD_I['indi2_paid_amount']?></td>
		<td><?=$JD_I['indi2_live_amount']?></td>

		<td><?=$JD_I['indi3_invest_count']?></td>
		<td><?=$JD_I['indi3_invest_amount']?></td>
		<td><?=$JD_I['indi3_paid_amount']?></td>
		<td><?=$JD_I['indi3_live_amount']?></td>
	</tr>

	<tr align="right">
		<td align="center" rowspan="2">매출채권</td>
		<td align="center">법인</td>

		<td><?=(int)$CORP_LOANER_MC['product_count']?></td>
		<td><?=(int)$CORP_LOANER_MC['recruit_amount']?></td>
		<td><?=$MC_C['paid_amount']?></td>
		<td><?=$MC_C['live_amount']?></td>

		<td><?=$MC_C['corp_invest_count']?></td>
		<td><?=$MC_C['corp_invest_amount']?></td>
		<td><?=$MC_C['corp_paid_amount']?></td>
		<td><?=$MC_C['corp_live_amount']?></td>

		<td><?=$MC_C['indi1_invest_count']?></td>
		<td><?=$MC_C['indi1_invest_amount']?></td>
		<td><?=$MC_C['indi1_paid_amount']?></td>
		<td><?=$MC_C['indi1_live_amount']?></td>

		<td><?=$MC_C['indi2_invest_count']?></td>
		<td><?=$MC_C['indi2_invest_amount']?></td>
		<td><?=$MC_C['indi2_paid_amount']?></td>
		<td><?=$MC_C['indi2_live_amount']?></td>

		<td><?=$MC_C['indi3_invest_count']?></td>
		<td><?=$MC_C['indi3_invest_amount']?></td>
		<td><?=$MC_C['indi3_paid_amount']?></td>
		<td><?=$MC_C['indi3_live_amount']?></td>
	</tr>

	<tr align="right">
		<td align="center">개인</td>

		<td><?=(int)$INDI_LOANER_MC['product_count']?></td>
		<td><?=(int)$INDI_LOANER_MC['recruit_amount']?></td>
		<td><?=$MC_I['paid_amount']?></td>
		<td><?=$MC_I['live_amount']?></td>

		<td><?=$MC_I['corp_invest_count']?></td>
		<td><?=$MC_I['corp_invest_amount']?></td>
		<td><?=$MC_I['corp_paid_amount']?></td>
		<td><?=$MC_I['corp_live_amount']?></td>

		<td><?=$MC_I['indi1_invest_count']?></td>
		<td><?=$MC_I['indi1_invest_amount']?></td>
		<td><?=$MC_I['indi1_paid_amount']?></td>
		<td><?=$MC_I['indi1_live_amount']?></td>

		<td><?=$MC_I['indi2_invest_count']?></td>
		<td><?=$MC_I['indi2_invest_amount']?></td>
		<td><?=$MC_I['indi2_paid_amount']?></td>
		<td><?=$MC_I['indi2_live_amount']?></td>

		<td><?=$MC_I['indi3_invest_count']?></td>
		<td><?=$MC_I['indi3_invest_amount']?></td>
		<td><?=$MC_I['indi3_paid_amount']?></td>
		<td><?=$MC_I['indi3_live_amount']?></td>
	</tr>
</table>
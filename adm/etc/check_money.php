<?

###############################################################################
## 출금가능금액 계산
###############################################################################


include_once("_common.php");

$mb_id = trim($_REQUEST['mb_id']);
$MB = get_member($mb_id);
//print_rr($MB, 'font-size:12px');

echo "<pre style='font-size:12px'>\n";

$before24_datetime = date("Y-m-d H:i:s", time()-86400);

$BEFORE_1DAY = sql_fetch("
	SELECT
		(SELECT IFNULL(SUM(TR_AMT), 0) FROM IB_FB_P2P_IP WHERE 1 AND CUST_ID = '".$MB['mb_no']."' AND ERP_TRANS_DT > '".preg_replace("/(-| |:)/","",$before24_datetime)."') AS insert_amt,
		(SELECT IFNULL(SUM(amount), 0) AS invest_amt FROM cf_product_invest WHERE 1 AND member_idx = '".$MB['mb_no']."' AND invest_state = 'Y' AND insert_datetime > '".$before24_datetime."') AS invest_amt,
		(SELECT IFNULL(SUM(req_price), 0) AS withdrawal_amt FROM g5_withdrawal WHERE 1 AND mb_id='".$MB['mb_id']."' AND regdate > '".$before24_datetime."' AND state='2') AS withdrawal_amt
");

echo "24시간내 입금액: " . number_format($BEFORE_1DAY['insert_amt']) . "\n";
echo "24시간내 투자액: " . number_format($BEFORE_1DAY['invest_amt']) . "\n";
echo "24시간내 출금액: " . number_format($BEFORE_1DAY['withdrawal_amt']) . "\n\n";


$now_amt    = get_point_sum($MB['mb_id']);
$lock_amt   = max(($BEFORE_1DAY['insert_amt'] - $BEFORE_1DAY['invest_amt']), 0);
$unlock_amt = max(($now_amt - $lock_amt), 0);		// 출금가능금액

$row['lock_amount'] = (int)$lock_amt;
$row['withdrawal_posible_amount'] = (int)$unlock_amt;


echo "현재예치금: " . number_format($now_amt) . "\n";
echo "잠긴금액: " . number_format($lock_amt) . "\n";
echo "<font color=red>출금가능금액: " . number_format($unlock_amt) . "</font>\n";
echo "</pre>\n";

?>
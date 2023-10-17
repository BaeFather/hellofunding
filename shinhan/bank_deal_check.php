<?php

exit;		// manager 에서 실행

///////////////////////////////////////////////////////////////////////////////
// 예치금총잔액(BAL_ALLAMT), BAL_TRUAMT(예치금신탁계좌잔액)는 당일 누적분만 리포팅 됨.
// php -q /home/crowdfund/public_html/shinhan/bank_deal_check.php [direct] [대상일자]
///////////////////////////////////////////////////////////////////////////////

set_time_limit(60);

define('_GNUBOARD_', true);
define('G5_DISPLAY_SQL_ERROR', false);
define('G5_MYSQLI_USE', true);

$path = '/home/crowdfund/public_html';
include_once($path . '/common.cli.php');
include_once($path . '/lib/insidebank.lib.php');
include_once($path . '/lib/sms.lib.php');

// 점검시간중 STOP
if( date('Y-m-d H:i:s') >= $CONF['BANK_STOP_SDATE'] && date('Y-m-d H:i:s') < $CONF['BANK_STOP_EDATE'] ) {
	@sql_close();
	exit;
}


// 당일 데이터
$target_date = ( @isset($_SERVER['argv'][2]) ) ? $_SERVER['argv'][2] : date('Y-m-d');

$ARR['REQ_NUM']    = '044';
$ARR['STAND_DATE'] = preg_replace('/-/', '', $target_date);

$RESULT = insidebank_request('256', $ARR);

if($RESULT['RCODE']=='00000000') {

	$ROW = sql_fetch("SELECT idx FROM IB_deal_daylog WHERE TARGET_DATE = '".$target_date."'");

	$sql_add = "TARGET_DATE = '".$target_date."', " .
	           "BAL_DEP_CNT = '".$RESULT['BAL_DEP_CNT']."', " .
	           "BAL_DEP_AMT = '".$RESULT['BAL_DEP_AMT']."', " .
	           "BAL_RET_CNT = '".$RESULT['BAL_RET_CNT']."', " .
	           "BAL_RET_AMT = '".$RESULT['BAL_RET_AMT']."', " .
	           "REPAY_CNT = '".$RESULT['REPAY_CNT']."', " .
	           "REPAY_AMT = '".$RESULT['REPAY_AMT']."', " .
	           "LOAN_CNT = '".$RESULT['LOAN_CNT']."', " .
	           "LOAN_AMT = '".$RESULT['LOAN_AMT']."', " .
	           "PRIN_CNT = '".$RESULT['PRIN_CNT']."', " .
	           "PRIN_AMT = '".$RESULT['PRIN_AMT']."', " .
	           "BAL_ALLAMT = '".$RESULT['BAL_ALLAMT']."', " .
	           "BAL_TRUAMT = '".$RESULT['BAL_TRUAMT']."'";

	if($ROW['idx']) {
		$sql_add.= ", editdate = NOW()";
		$sql = "UPDATE IB_deal_daylog SET $sql_add WHERE idx='" . $ROW['idx'] . "'";
	}
	else {
		$sql_add.= ", RDATE = NOW()";
		$sql = "INSERT INTO IB_deal_daylog SET " . $sql_add;
	}
	$res = sql_query($sql);
	$change_count = sql_affected_rows();
	//echo $change_count."\n";

	if($change_count) {

		// 금일 요청인 경우에만 상환용 모계좌 잔액 업데이트
		if( $target_date == date('Y-m-d') ) {

			$idx = ($ROW['idx']) ? $ROW['idx'] : sql_insert_id();

			$ARR2['REQ_NUM']    = '044';
			$ARR2['TOT_GBN_CD'] = '2';
			$ARR2['STAND_DATE'] = preg_replace('/-/', '', $target_date);

			$RESULT2 = insidebank_request('256', $ARR2);
			if($RESULT2['RCODE']=='00000000' && $idx) {
				$sql2 = "
					UPDATE
						IB_deal_daylog
					SET
						BAL_TRUAMT2 = '".$RESULT2['BAL_TRUAMT']."',
						editdate = NOW()
					WHERE
						idx = '".$idx."'";
				$res2 = sql_query($sql2);
				//echo sql_affected_rows()."\n";
			}

		}

		echo "success";

	}

}

@sql_close();
exit;

?>
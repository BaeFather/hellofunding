<?php

exit;		// manager 에서 실행

///////////////////////////////////////////////////////////////////////////////
// 신한은행 점검시간 : 00:30 ~ 01:30
// 집계조회 : 크론탭으로 00:01분, 00:02분, 00:03분, 01:00분, 01:30분, 01:35분에 실행
// 예치금총잔액(BAL_ALLAMT), BAL_TRUAMT(예치금신탁계좌잔액)는 당일 누적분만 리포팅 되므로 본 페이지에서는 저장하지 않음.
// /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/bank_deal_check_daily.php (yes|debug)
// 1-5 0 * * * /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/bank_deal_check_daily.php yes
// 1,30,35 1 * * * /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/bank_deal_check_daily.php yes
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

$action = ($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : "debug";

// 1일전 데이터
$target_date       = date('Y-m-d', strtotime('-1 day'));
$ARR['REQ_NUM']    = '044';
$ARR['STAND_DATE'] = preg_replace('/-/', '', $target_date);


$ROW = sql_fetch("SELECT idx, LEFT(editdate,10) AS edit_dd FROM IB_deal_daylog WHERE TARGET_DATE = '".$target_date."'");
if($action == 'debug') print_r($ROW);


if( $ROW['idx'] && ($ROW['edit_dd'] < date('Y-m-d')) ) {

	$RESULT = insidebank_request('256', $ARR);

	if($RESULT['RCODE']=='00000000') {

		$sql = "
			UPDATE
				IB_deal_daylog
			SET
				TARGET_DATE = '".$target_date."',
				BAL_DEP_CNT = '".$RESULT['BAL_DEP_CNT']."',
				BAL_DEP_AMT = '".$RESULT['BAL_DEP_AMT']."',
				BAL_RET_CNT = '".$RESULT['BAL_RET_CNT']."',
				BAL_RET_AMT = '".$RESULT['BAL_RET_AMT']."',
				REPAY_CNT = '".$RESULT['REPAY_CNT']."',
				REPAY_AMT = '".$RESULT['REPAY_AMT']."',
				LOAN_CNT = '".$RESULT['LOAN_CNT']."',
				LOAN_AMT = '".$RESULT['LOAN_AMT']."',
				PRIN_CNT = '".$RESULT['PRIN_CNT']."',
				PRIN_AMT = '".$RESULT['PRIN_AMT']."',
				BAL_ALLAMT = '".$RESULT['BAL_ALLAMT']."',
				BAL_TRUAMT = '".$RESULT['BAL_TRUAMT']."',
				editdate = NOW()
			WHERE
				idx = '".$ROW['idx']."'";

		if($action == 'yes') {

			$res = sql_query($sql);
			$change_count = sql_affected_rows();

			if($change_count) {
				$ARR2['REQ_NUM']    = '044';
				$ARR2['TOT_GBN_CD'] = '2';
				$ARR2['STAND_DATE'] = preg_replace('/-/', '', $target_date);

				$RESULT2 = insidebank_request('256', $ARR2);

				if($RESULT2['RCODE']=='00000000') {
					$sql2 = "
						UPDATE
							IB_deal_daylog
						SET
							BAL_TRUAMT2 = '".$RESULT2['BAL_TRUAMT']."',
							editdate = NOW()
						WHERE
							idx = '".$ROW['idx']."'";
					$res2 = sql_query($sql2);
				}
			}

		}
		else {
			print_r($sql);
		}

	}

}


@sql_close();
exit;

?>
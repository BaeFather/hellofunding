<?php
###############################################################################
## 인사이드뱅크로 원리금 지급요청전문 날리기
## - 전문발송이 정상적으로 실행되면 인사이드뱅크 스케쥴러가 지정된 시간에 IB_FB_P2P_REPAY_REQ, IB_FB_P2P_REPAY_REQ_DETAIL
##   테이블을 크롤링하여 상환요청자료를 수집하고 요청리스트파일을 생성하여 기관으로 넘긴다.
##  ★★★★ 1일 99회차까지 요청 가능 ★★★★
##
## 크론탭
## * 09-18 * * * /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/repay_request_send.php yes
##
## $reg_seq 는 실행차수가 아닌 등록된 순서로 변경되었음
###############################################################################

set_time_limit(0);

$path = '/home/crowdfund/public_html';

include_once($path . '/common.cli.php');
include_once($path . '/lib/insidebank.lib.php');
include_once($path . '/lib/sms.lib.php');


$action = (@$_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';

$sdate = date('Ymd');
$stime = date('Hi');
//$stime = '1020';	// 임의의 시간에 강제 실행시 stime값을 ready 테이블에 있는 데이터와 동일하게 셋팅하고 본 파일을 강제로 실행해준다.


// 전문대기 허용시간대 설정(09:00~18:00)
// ※17시 이후 자료는 비상대비 임의 추가시간임
if($stime < '0900' || $stime > '1800') { echo "실행 허용된 시간이 아닙니다.\n"; sql_close(); exit; }

$_stime = $stime . '00';

$sql = "
	SELECT
		*
	FROM
		IB_FB_P2P_REPAY_REQ_ready
	WHERE 1
		AND SDATE = '".$sdate."'
		AND STIME = '".$_stime."'
		AND EXEC_STATUS = '00'
	ORDER BY
		SDATE, STIME
	DESC
		LIMIT 1";
$DATA = sql_fetch($sql);
if($action=='debug') { print_r($DATA); }

// 일자별회차 조건에 일치하는 전송요청이 있다면 실제 등록요청용 테이블로 이동
if($DATA['idx']) {

	// 실행로그 등록 --------------------------------------------------------------------
	$log_sql = "
		INSERT INTO
			IB_repay_request_exec_log
		SET
			path = '".$_SERVER['PHP_SELF']."',
			param = '".$action."',
			rdate = NOW()";
	sql_query($log_sql);
	$log_insert_idx = sql_insert_id();
	// 실행로그 등록 --------------------------------------------------------------------

	$sql2 = "
		INSERT INTO
			IB_FB_P2P_REPAY_REQ
		SET
			SDATE          = '".$DATA['SDATE']."',
			REG_SEQ        = '".$DATA['REG_SEQ']."',
			PARTNER_CD     = '".$DATA['PARTNER_CD']."',
			STIME          = '".$DATA['STIME']."',
			TOTAL_CNT      = '".$DATA['TOTAL_CNT']."',
			TOTAL_TR_AMT   = '".$DATA['TOTAL_TR_AMT']."',
			TOTAL_TR_AMT_P = '".$DATA['TOTAL_TR_AMT_P']."',
			TOTAL_CTAX_AMT = '".$DATA['TOTAL_CTAX_AMT']."',
			TOTAL_FEE      = '".$DATA['TOTAL_FEE']."',
			TRAN_DATE      = '".$DATA['TRAN_DATE']."',
			TRAN_TIME      = '".$DATA['TRAN_TIME']."',
			TOTAL_S_CNT    = '".$DATA['TOTAL_S_CNT']."',
			TOTAL_E_CNT    = '".$DATA['TOTAL_E_CNT']."',
			RESP_CODE      = '".$DATA['RESP_CODE']."',
			RESP_MSG       = '".$DATA['RESP_MSG']."',
			EXEC_STATUS    = '".$DATA['EXEC_STATUS']."',
			idx            = '".$DATA['idx']."',
			apply          = '".$DATA['apply']."'";
	if( sql_query($sql2) ) {

		if($action=='yes') {

			$RETURN_ARR = insidebank_request('001', $DATA['REG_SEQ']);		// 인사이드뱅크 원리금지급요청 전문(2500) 발송 (동일회차 재요청시 에러코드 리턴함)
			//print_r($RETURN_ARR);

			if($RETURN_ARR['RCODE']=='00000000') {
				sql_query("DELETE FROM IB_FB_P2P_REPAY_REQ_ready WHERE SDATE='".$DATA['SDATE']."' AND idx='".$DATA['idx']."'");		// 전송요청 성공시 IB_FB_P2P_REPAY_REQ_ready 데이터 삭제
			}
			else {
				sql_query("DELETE FROM IB_FB_P2P_REPAY_REQ WHERE idx='".$DATA['idx']."'");					// 전송요청 실패시 입력된 IB_FB_P2P_REPAY_REQ 데이터 삭제

				if( function_exists('unit_sms_send_smtnt') ) {
					$send_msg = "원리금지급요청실패\n(" . $sdate . "  " . $stime . " 요청분)";
					for($i=0; $i<count($CONF['event_receive_phone']); $i++) {
						unit_sms_send_smtnt($CONF['admin_sms_number'], $CONF['event_receive_phone'][$i], $send_msg);
					}
				}
			}

			if($log_insert_idx) {
				$data = sql_escape_string(json_encode($DATA, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES));
				@sql_query("UPDATE IB_repay_request_exec_log SET data='".$data."' WHERE idx='".$log_insert_idx."'");
				@sql_query("DELETE FROM IB_repay_request_exec_log WHERE LEFT(rdate, 10) < '".date('Y-m-d', strtotime('-30 day'))."'");		// 30일분 로그 저장
			}

		}
		else {
			print_r($ARR);
		}

	}

}

@sql_close();
exit;

?>
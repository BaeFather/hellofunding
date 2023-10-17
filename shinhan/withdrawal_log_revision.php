#!/usr/local/php/bin/php -c /etc/php.ini -q
<?
###############################################################################
## * * * * * /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/withdrawal_log_revision.php yes|debug
## 인사이드뱅크 출금전송결과 반환 지연건 출금 내역 보정 (현재는 핀크 출금 오류만 대상으로 가져옴, 정산페이지에서 출금지연으로 등록된 건은 취급 불가!!!)
## 결과값이 IS0102 인 출금 전송건을 대상으로 함.
###############################################################################

set_time_limit(0);


$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.cli.php");
include_once($base_path . "/lib/insidebank.lib.php");


// 점검시간중 STOP
if( date('Y-m-d H:i:s') >= $CONF['BANK_STOP_SDATE'] && date('Y-m-d H:i:s') < $CONF['BANK_STOP_EDATE'] ) {
	@sql_close();
	exit;
}

$action = (@$_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';

$range_start = date('Y-m-d H:i', time()-60*12);		// 12분전
$range_end   = date('Y-m-d H:i', time()-60*2);		// 2분전


$sql = "
	SELECT
		idx, regdate, request_arr
	FROM
		IB_request_log
	WHERE (1)
		AND request_code='3200'
		AND rcode='IS0102'
		AND exec_path='/syndicate/finnq/api/withdrawal/request.php'
		AND LEFT(regdate,16) BETWEEN '".$range_start."' AND '".$range_end."'
	ORDER BY
		idx ASC";

if($action=='debug') { debug_flush($sql."\n"); }

$res  = sql_query($sql);
$rows = $res->num_rows;
if($rows==0) exit;

for($i=0,$j=1; $i<$rows; $i++,$j++) {

	$LIST = sql_fetch_array($res);

	$REQUEST_ARR = explode("&", $LIST['request_arr']);
	$request_arr_count = count($REQUEST_ARR);
	$LIST['REQUESTED'] = array();
	for($y=0; $y<$request_arr_count; $y++) {
		$PARAM = explode("=", $REQUEST_ARR[$y]);
		$LIST['REQUESTED'][$PARAM[0]] = $PARAM[1];
	}

	if($action=='debug') { debug_flush($LIST['idx'] . " " . $LIST['REQUESTED']['FB_SEQ']); }

	if( $LIST['REQUESTED']['FB_SEQ'] ) {

		// 결번요청(8400) -> 전문 실행 결과값 재전송받기
		$ARR['SUBMIT_GBN'] = "04";
		$ARR['TRAN_DATE']  = substr(preg_replace("/(-| |:)/", "", $LIST['regdate']), 0, 8);
		$ARR['ORI_FB_SEQ'] = $LIST['REQUESTED']['FB_SEQ'];

		$ISB_RESULT = insidebank_request("000", $ARR);

		if($action=='debug') {
			debug_flush(print_r($ISB_RESULT));
			debug_flush(" " . $ISB_RESULT['ORI_FB_REQCODE'] . " : \n");
		}

		// 과거 전송이력의 결과값이 정상으로 체크된 경우 '출금기록등록', '예치금 차감', '로그중 결과값 수정' 처리를 한다.
		if($ISB_RESULT['ORI_FB_REQCODE']=='00000000' && $LIST['REQUESTED']['TRAN_AMT']) {

			$MB = sql_fetch("SELECT mb_id FROM g5_member WHERE mb_no='".$LIST['REQUESTED']['CUST_ID']."'");

			$memo = "자동보정등록데이터 \n등록일시:".date('Y-m-d H:i:s');

			// 전송로그 결과값 수정
			$sqlX0 = "UPDATE IB_request_log SET rcode='_00000000' WHERE idx='".$LIST['idx']."'";

			// 출금 기록 등록
			$sqlX1 = "
				INSERT INTO
					g5_withdrawal
				SET
					mb_id      = '".$MB['mb_id']."',
					req_price  = '".$LIST['REQUESTED']['TRAN_AMT']."',
					state      = '2',
					admin_memo = '".$memo."',
					regdate    = '".$LIST['regdate']."',
					admin_editdate = NOW(),
					GUAR_SEQ   = '".$LIST['REQUESTED']['FB_SEQ']."'";

			$point = (int)$LIST['REQUESTED']['TRAN_AMT'] * -1;

			if($action=='debug') {

				debug_flush($sqlX1 . "\n" . "insert_point({$MB['mb_id']}, $point, '예치금 출금', '@system_repair', {$LIST['REQUESTED']['FB_SEQ']}, '', 0);\n" . $sqlX0 ."\n");

			}
			else if($action=='yes') {

				// g5_withdrawal 보정데이터 유무 확인
				$WDL_LOG = sql_fetch("SELECT COUNT(idx) AS cnt FROM g5_withdrawal WHERE GUAR_SEQ='".$LIST['REQUESTED']['FB_SEQ']."'");
				if(!$WDL_LOG['cnt']) {
					$resX1 = sql_query($sqlX1);
				}

				// g5_point 보정데이터 유무 확인
				$POINT_LOG = sql_fetch("SELECT COUNT(po_id) AS cnt FROM g5_point WHERE po_rel_table='@system_repair' AND po_rel_id='".$LIST['REQUESTED']['FB_SEQ']."'");
				if(!$POINT_LOG['cnt']) {
					// 예치금 차감 기록 등록
					$resX2 = insert_point($MB['mb_id'], $point, '예치금 출금', '@system_repair', $LIST['REQUESTED']['FB_SEQ'], '', 0);    //insert_point($mb_id, $point, $content, $rel_table, $rel_id, $rel_action, $expire=0)
				}

				if($resX1 && $resX2) {
					debug_flush($resX1 . " " . $resX2 . "\n");
					$resX0 = sql_query($sqlX0);
				}

				$resX0 = $resX1 = $resX2 = NULL;

			}

		}

		$ARR = NULL;

	}

	if($action=='debug') { debug_flush("\n\n"); }

}

sql_close();
exit;

?>
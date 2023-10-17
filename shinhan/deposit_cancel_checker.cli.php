#!/usr/local/php/bin/php -q
<?
###############################################################################
## 인사이드뱅크 펌뱅킹 입금취소 통지
##
## 주의: 특별한 사유없이 본 파일을 강제로 실행하지 말것!!!!!!!!
##
## /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/deposit_cancel_checker.cli.php (yes|debug) &
##
## 실행파일: schedule_work/shinhan_investor_deposit_checker.sh
##
###############################################################################
## 20181019 : 개인회원 입금자명과 예금주명이 다를 경우 회원예치금으로 귀속시키지 않고
##            관리자 페이지에서 해당 예치금만큼 관리자가 지급해주는 방식으로 변경
###############################################################################

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.cli.php");
//include_once($base_path . "/lib/sms.lib.php");

$action = (@$_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';


$x = true;

while($x > 0) {

	if($action=='debug') { debug_flush("[" . date('Y-m-d H:i:s') . "]\n"); }

	// 일반 입금내역 추출
	$sql = "
		SELECT SQL_NO_CACHE
			A.SR_DATE, A.FB_SEQ, A.CUST_ID, A.TR_AMT, A.REMITTER_NM, A.TR_AMT_GBN, TR_ORG_DATE, TR_ORG_SEQ,
			B.mb_no, B.mb_id, B.member_type, B.mb_name, B.mb_co_name, B.bank_private_name, B.finnq_userid, B.oligo_userid
		FROM
			IB_FB_P2P_IP_CANCEL A
		INNER JOIN
			g5_member B  ON A.CUST_ID=B.mb_no
		WHERE 1
			AND trans_to_point != 'OK'
		ORDER BY
			SR_DATE DESC, FB_SEQ DESC";
	if($action=='debug') { debug_flush($sql . "\n"); }
	$res  = sql_query($sql);
	$rows = $res->num_rows;

	$content = "예치금 충전 취소 (금융기관이체오류)";

	for($i=0; $i<$rows; $i++) {

		$LIST[$i] = sql_fetch_array($res);

		$LIST[$i]['TR_AMT'] = (int)$LIST[$i]['TR_AMT'] * (-1);

		if($LIST[$i]['TR_AMT_GBN']=='10') {

			if($action=='yes') {

				insert_point($LIST[$i]['mb_id'], $LIST[$i]['TR_AMT'], $content, '@deposit_cancel', $LIST[$i]['mb_id'], $LIST[$i]['mb_id'].'-'.uniqid(''));		// 예치금 차감

				// 등록내역 확인 플래그 업데이트
				$resX2 = sql_query("
					UPDATE
						IB_FB_P2P_IP_CANCEL
					SET
						trans_to_point='OK',
						trans_date='".date('Y-m-d H:i:s')."'
					WHERE (1)
						AND SR_DATE='".$LIST[$i]['SR_DATE']."'
						AND FB_SEQ='".$LIST[$i]['FB_SEQ']."'
						AND CUST_ID='".$LIST[$i]['CUST_ID']."'");

				// 누적 잔여포인트 가져오기
				$POINT_LOG = sql_fetch("SELECT SUM(po_point) AS sum_po_point FROM g5_point WHERE mb_id='".$LIST[$i]['mb_id']."' ORDER BY po_id DESC LIMIT 1");

				$sqlX2 = "UPDATE g5_member SET mb_point='".$POINT_LOG['sum_po_point']."' WHERE mb_id='".$LIST[$i]['mb_id']."'";
				sql_query($sqlX2);

			}

			else {

				debug_flush("insert_point(".$LIST[$i]['mb_id'].", ".$LIST[$i]['TR_AMT'].", ".$content.", '@deposit_cancel', ".$LIST[$i]['mb_id'].", ".$LIST[$i]['mb_id'].'-'.uniqid('').");\n");

			}

		}


	}

	if($action=='debug') {
		print_r($LIST);
	}

	sql_free_result($res);
	unset($LIST);

	usleep(10000000);

}

?>

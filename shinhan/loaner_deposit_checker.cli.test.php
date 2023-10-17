#!/usr/local/php/bin/php -q
<?
###############################################################################
## 인사이드뱅크 펌뱅킹 원리금 지급요청테이블 실행정보를 통한 실지급 반영하기
##
## 주의 : public_html/config.php 를 호출하지 않아 상수설정정보가 없으므로
##        common.lib.php 에서 DB쿼리를 사용하는 함수는 사용하면 안된다!!!
##
## /usr/local/php/bin/php -q /home/crowdfund/public_html/shinhan/loaner_deposit_checker.cli.test.php (yes|debug) &
## 실행파일: schedule_work/shinhan_loaner_deposit_checker.sh
###############################################################################

set_time_limit(0);

define('_GNUBOARD_', true);
define('G5_DISPLAY_SQL_ERROR', false);
define('G5_MYSQLI_USE', true);

$path = '/home/crowdfund/public_html';
include_once($path . '/data/dbconfig.php');
include_once($path . '/lib/common.lib.php');


$action = (@$_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';

$x = true;

while($x > 0) {



	$link = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
	sql_set_charset("UTF8", $link);


	$TRAN_DATE = date('Ymd');

	// 원리금 상환지급요청 및 결과 정보
	$sql1 = "
		SELECT
			idx, TOTAL_CNT, TOTAL_TR_AMT, TOTAL_S_CNT, TOTAL_E_CNT, RESP_CODE
		FROM
			IB_FB_P2P_REPAY_REQ
		WHERE 1=1
			AND TRAN_DATE = '".$TRAN_DATE."'
			AND EXEC_STATUS = '02'
			AND apply = ''
		ORDER BY
			TRAN_TIME ASC
		LIMIT 1";
	if($action=="debug") { echo $sql1."\n\n"; }
	$REPAY_REQUEST = sql_fetch($sql1, "", $link);

	if($REPAY_REQUEST['RESP_CODE']) {

		if($REPAY_REQUEST['RESP_CODE']=='00000000') {
			if($REPAY_REQUEST['TOTAL_CNT'] > 0 && $REPAY_REQUEST['TOTAL_CNT']==$REPAY_REQUEST['TOTAL_S_CNT']) {

				// 요청 및 성공카운트가 동일할 경우
				// -> 요청자료 테이블(IB_FB_P2P_REPAY_REQ, IB_FB_P2P_REPAY_REQ_DETAIL)의 apply필드값(플래그) 변경

				// 처리요청별 기관처리정보 추출 (상품별 소트)
				$sql2 = "
					SELECT
						COUNT(SEQ) AS TOTAL_CNT,
						IFNULL(SUM(TR_AMT), 0) AS TOTAL_TR_AMT
					FROM
						IB_FB_P2P_REPAY_REQ_DETAIL
					WHERE 1=1
						AND SDATE = '".$TRAN_DATE."'
						AND RESP_CODE = '00000000'
						AND req_idx = '".$REPAY_REQUEST['idx']."'";
				if($action=="debug") { echo $sql2."\n\n"; }
				$DETAILSUM = sql_fetch($sql2, "", $link);

				// 요청테이블과 상세테이블간 성공 카운트 비교
				if(($REPAY_REQUEST['TOTAL_CNT']==$DETAILSUM['TOTAL_CNT']) && ($REPAY_REQUEST['TOTAL_CNT']==$DETAILSUM['TOTAL_CNT'])) {

					//요청카운트와 상세데이터(전송완료분)카운트가 동일하면 플래그 처리
					$sql3 = "UPDATE IB_FB_P2P_REPAY_REQ SET apply='Y' WHERE idx='".$REPAY_REQUEST['idx']."'";
					if($action=="yes") {
						sql_query($sql3, true, $link);
					}
					else {
						echo $sql3."\n\n";
					}

					// 당요청회차의 상품 및 이자지급회차정보 가져옴
					$sql4 = "
						SELECT
							DC_NB, turn, turn_sno, is_overdue
						FROM
							IB_FB_P2P_REPAY_REQ_DETAIL
						WHERE 1
							AND req_idx = '".$REPAY_REQUEST['idx']."'
						GROUP BY
							DC_NB, turn, turn_sno, is_overdue
						ORDER BY
							DC_NB, turn, turn_sno, is_overdue";

					$res4 = sql_query($sql4, true, $link);
					while( $ROW = sql_fetch_array($res4) ) {

						$update_fld = 'invest_give_state';							// 일반원리금
						if($ROW['is_overdue']=='Y') {
							$update_fld = 'overdue_give';									// 연체이자
						}
						else {
							if($ROW['turn_sno'] > 0) $update_fld = 'invest_principal_give';		// 원금일부상환
						}

						// 정상상환 배분요청 결과 플래그 기록
						$sql5 = "
							UPDATE
								cf_product_success
							SET
								{$update_fld} = 'S'
							WHERE 1
								AND product_idx = '".$ROW['DC_NB']."' AND turn = '".$ROW['turn']."' AND turn_sno = '".$ROW['turn_sno']."'
								AND {$update_fld} = 'W'";

						if($action=='yes') {
							sql_query($sql5, true, $link);
						}
						else {
							echo $sql5 . "\n";
						}

					}
				}

			}
		}
		else {

			//////////////////////////////////////
			// 요청한 배분 처리가 안된 경우
			// -> 재신청 할 수 있도록 플래그 삭제 -> 어드민에서 재신청
			//////////////////////////////////////

			$sqlx = "UPDATE IB_FB_P2P_REPAY_REQ SET apply = 'C' WHERE idx = '".$REPAY_REQUEST['idx']."'";
			$resx = sql_query($sqlx, true, $link);

			$sqlx = "UPDATE IB_FB_P2P_REPAY_REQ_DETAIL SET RESP_CODE = 'C' WHERE req_idx = '".$REPAY_REQUEST['idx']."'";
			$resx = sql_query($sqlx, true, $link);

			// 당요청회차의 상품 및 이자지급회차정보 가져옴
			$sql4 = "
				SELECT
					DC_NB, turn, turn_sno, is_overdue
				FROM
					IB_FB_P2P_REPAY_REQ_DETAIL
				WHERE 1
					AND req_idx = '".$REPAY_REQUEST['idx']."'
				GROUP BY
					DC_NB, turn, turn_sno, is_overdue
				ORDER BY
					DC_NB, turn, turn_sno, is_overdue";

			$res4 = sql_query($sql4, true, $link);
			while( $ROW = sql_fetch_array($res4) ) {

				// 일반원리금
				$update_fld  = 'ib_request_ready';
				$update_fld2 = 'invest_give_state';

				if($ROW['is_overdue']=='Y') {
					// 연체이자
					$update_fld  = 'overdue_ib_request_ready';
					$update_fld2 = 'overdue_give';
				}
				else {
					if($ROW['turn_sno'] > 0) {
						// 원금일부상환
						$update_fld  = 'ib_request_ready';
						$update_fld2 = 'invest_principal_give';
					}
				}

				$sql5 = "
					UPDATE
						cf_product_success
					SET
						{$update_fld} = '',
						{$update_fld2} = ''
					WHERE 1
						AND product_idx = '".$ROW['DC_NB']."' AND turn = '".$ROW['turn']."' AND turn_sno = '".$ROW['turn_sno']."'";

				if($action=='yes') {
					sql_query($sql5, true, $link);
				}
				else {
					echo $sql5 . "\n";
				}

			}

		}
	}

	$sql1 = $sql2 = $sql3 = $sql4 = $sql5 = $REPAY_REQUEST = $DETAILSUM = NULL;

	sql_close($link);

	sleep(15);

}

?>
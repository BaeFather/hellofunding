<?
###############################################################################
## 정산 일괄 플래그(수급현황) 수정
##	repay_schedule.ajax 를 통해 호출
###############################################################################

include_once("_common.php");
//include_once(G5_PATH . "/lib/sms.lib.php");

$g5['title'] = "정산일괄처리(수급현황수정)";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록


$mode = trim($mode);
$schedule_date = trim($schedule_date);

$CHK = $_POST['chk'];

if( !in_array($mode, array('receive_interest','receive_principal')) || $schedule_date=='' ) {

	$ARR = array('result' => 'FAIL', 'msg' => "필수값 미전송 오류!!");

}
else {

	// 로그 등록
	$sql = "
		INSERT INTO
			batch_success_flag_log
		SET
			mode = '".$mode."',
			repay_schedule_date = '".$schedule_date."',
			sdatetime = NOW(),
			admin_id = '".$member['mb_id']."'";
	$res = sql_query($sql);
	$log_idx = sql_insert_id();


	$product_count = count($CHK);
	$prd_idx_set = "";

	if($product_count) {

		$proc_count = 0;		// DB처리 카운트
		$script = "";

		// 상품번호 셋팅
		for($i=0,$j=1; $i<$product_count; $i++,$j++) {
			$IDX = explode('-', $CHK[$i]);

			$prd_idx = $IDX[0];
			$turn    = $IDX[1];

			$prd_idx_set.= $prd_idx;
			$prd_idx_set.= ($j < $product_count) ? ',' : '';

			//echo $prd_idx_set;


			$sql = "
				SELECT
					idx, loan_interest_state, loan_principal_state
				FROM
					cf_product_success
				WHERE
					product_idx = '".$prd_idx."' AND turn = '".$turn."' AND turn_sno='0'";
			$FLAG = sql_fetch($sql);
			//print_r($FLAG);

			///////////////////////////////////////////////////////////////////////////
			// 이자수급현황 완료 처리
			///////////////////////////////////////////////////////////////////////////
			if($mode=='receive_interest') {

				$sqlx = "";
				if($FLAG['idx']) {
					if($FLAG['loan_interest_state']!='Y') {
						$sqlx = "
							UPDATE
								cf_product_success
							SET
								loan_interest_state = 'Y'
							WHERE
								idx = '".$FLAG['idx']."'";
					}
				}
				else {
					$sqlx = "
						INSERT INTO
							cf_product_success
						SET
							product_idx = '".$prd_idx."',
							turn = '".$turn."',
							loan_interest_state = 'Y',
							`date` = CURDATE()";
				}

				if($sqlx) {
					//echo $sqlx . "\n";
					$resx = sql_query($sqlx);
					if( sql_affected_rows() ) {
						$proc_count += 1;
					}
				}

			}


			///////////////////////////////////////////////////////////////////////////
			// 원금수급현황 완료 처리
			///////////////////////////////////////////////////////////////////////////
			if($mode=='receive_principal') {

				$PRDT = sql_fetch("SELECT invest_period, invest_days, loan_start_date, loan_end_date FROM cf_product WHERE idx='".$prd_idx."'");
				$shortTermProduct = ($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) ? 1 : 0;		// 단기상품여부
				$max_repay_turn = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], false, $shortTermProduct);

				$sqlx = "";
				if($FLAG['idx']) {

					if($FLAG['loan_principal_state']!='Y' && $turn == $max_repay_turn) {
						// 최종회차인지 체크
						$sqlx = "
							UPDATE
								cf_product_success
							SET
								loan_principal_state = 'Y'
							WHERE
								idx = '".$FLAG['idx']."'";

						$resx = sql_query($sqlx);
						if( sql_affected_rows() ) {
							$proc_count += 1;
						}
					}

				}

				unset($max_repay_turn);

			}

		}		// end for($i=0,$j=1; $i<$product_count; $i++,$j++)


		if($proc_count > 0) {
			$ARR = array(
				'result'  => "SUCCESS",
				'msg'     => "대상상품: " . number_format($product_count) . "건 \n처리완료: " . number_format($proc_count) . "건",
			);
		}
		else {
			$ARR = array('result' => 'FAIL', 'msg' => "수급현황의 변경 내역이 없습니다.");
		}

	}
	else {

		$ARR = array('result' => 'FAIL', 'msg' => "대상 상품이 없습니다.");

	}

}

$json_txt = json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
echo $json_txt;


// 로그 종료
$sql = "
	UPDATE
		batch_success_flag_log
	SET
		edatetime = NOW(),
		result_msg = '".$json_txt."'
	WHERE
		idx = '".$log_idx."'";

sql_query($sql);
$res = sql_query($sql);

exit;

?>
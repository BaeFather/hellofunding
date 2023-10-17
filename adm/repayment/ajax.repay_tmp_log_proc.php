<?
###############################################################################
## 대출자 입출금 내역 추가 보정 데이터 처리
###############################################################################

include_once('./_common.php');

while(list($k, $v) = each($_REQUEST)) { ${$k} = sql_real_escape_string(trim($v)); }

if($action=='insert') {

	$amount = preg_replace("/\,/", "", $amount);
	$target_date = $target_date . ' ' . sprintf('%02d', $target_date_h) . ':' . sprintf('%02d', $target_date_i) . ':00';

	$sql = "
		INSERT INTO
			cf_repay_tmp_log
		SET
			product_idx = '".$prd_idx."',
			gubun = '".$gubun."',
			amount = '".$amount."',
			pretext = '".$pretext."',
			target_date = '".$target_date."',
			writer_id = '".$member['mb_id']."',
			regdate = NOW()";
	$res = sql_query($sql);
	if( sql_affected_rows() ) {
		$ARR = array('result' => 'SUCCESS', 'msg' => '');
	}
	else {
		$ARR = array('result' => 'FAIL', 'msg' => '변경된 데이터가 없습니다.');
	}

	echo json_encode($ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

}

else if($action=='draw') {

	if($log_idx) {

		$TMP_LOG = sql_fetch("SELECT idx, draw_id FROM cf_repay_tmp_log WHERE idx='".$log_idx."'");

		if($TMP_LOG['idx']) {
			if($TMP_LOG['draw_id']) {
				$ARR = array('result' => 'FAIL', 'msg' => '이미 무효처리된 데이터 입니다.');
			}
			else {

				$sql = "
					UPDATE
						cf_repay_tmp_log
					SET
						draw_id = '".$member['mb_id']."',
						draw_date = NOW()
					WHERE
						idx = '".$log_idx."'";
				$res = sql_query($sql);
				if( sql_affected_rows() ) {
					$ARR = array('result' => 'SUCCESS', 'msg' => '');
				}
				else {
					$ARR = array('result' => 'FAIL', 'msg' => '변경된 데이터가 없습니다.');
				}

			}
		}
		else {
			$ARR = array('result' => 'FAIL', 'msg' => '보정데이터 로그를 찾을 수 없습니다.');
		}

	}
	else {

		$ARR = array('result' => 'FAIL', 'msg' => '로그 데이터 번호 누락');

	}

	echo json_encode($ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

}

sql_close();
exit;

?>
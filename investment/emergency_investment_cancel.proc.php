<?
/*******************************************************************************************
	긴급 투자 취소처리 (투자완료 후 후처리(중앙기록관리등..) 오류 발생시 투자 취소처리
	CLI방식만 실행 허용
	/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/emergency_investment_cancel.proc.php [상세투자번호] [투자자회원번호]
	/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/emergency_investment_cancel.proc.php 45088 8323

	// 테스트서버에서 cli 실행 테스트 완료. 이상없음. 리얼에 적용대기
 *******************************************************************************************/

$invest_detail_idx = @$_SERVER['argv'][1];
$mb_no = @$_SERVER['argv'][2];

if(!$invest_detail_idx) { echo 0; exit; }
if(!$mb_no) { echo 0; exit; }

$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.cli.php");		// cli타입 common.php 호출금지


$member = sql_fetch("SELECT * FROM g5_member WHERE mb_no = '".$mb_no."' AND member_group = 'F' AND mb_level BETWEEN 1 AND 5");

if( !$member['mb_no'] ) { echo 0; exit; }

/*
// 임시로그 등록 ----------------------------
$logsql = "INSERT INTO cf_tmp_log (path, content, rdt) VALUES ('".$_SERVER['PHP_SELF']."', '$mb_no', CURRENT_TIMESTAMP(6))";
sql_query($logsql);
// 임시로그 등록 ----------------------------
*/

// 투자정보 확인
$sql0 = "
	SELECT
		B.idx, B.product_idx, B.member_idx, B.amount, B.prin_rcv_no, B.bank_inquiry_id,
		A.idx AS DTL_idx, A.invest_idx AS DTL_invest_idx, A.amount AS DTL_amount,
		(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.invest_idx AND invest_state='Y') AS invest_detail_count
	FROM
		cf_product_invest_detail A
	LEFT JOIN
		cf_product_invest B  ON A.invest_idx = B.idx
	WHERE 1
		AND A.idx = '".$invest_detail_idx."' AND B.invest_state = 'Y'
		AND A.member_idx = '".$mb_no."'";
$INVEST = sql_fetch($sql0);
//print_r($INVEST); echo "\n";

if($INVEST['DTL_idx']) {

	$product_idx = $INVEST['product_idx'];
	$invest_idx  = $INVEST['idx'];
	$cancel_date = date('Y-m-d H:i:s');

	$cancel_by = 'p2pctr';

	// 상세투자내역 취소
	$sqlx = "
		UPDATE
			cf_product_invest_detail
		SET
			invest_state = 'N',
			cancel_date  = '".$cancel_date."',
			cancel_by    = '".$cancel_by."'
		WHERE 1
			AND idx = '".$invest_detail_idx."' AND invest_state = 'Y'";
	//print_r($sqlx); echo "\n";
	$resx = sql_query($sqlx);
	$change_ok = sql_affected_rows();

	if($change_ok) {

		if($INVEST['invest_detail_count'] > 1) {

			// 최종 정상투자건 다시 불러오기
			$sql1 = "SELECT amount, insert_date, insert_time FROM cf_product_invest_detail WHERE invest_idx = '".$invest_idx."' AND invest_state='Y' ORDER BY idx DESC LIMIT 1";
			$SUCC_INVEST_DETAIL = sql_fetch($sql1);

			$last_insert_datetime = $SUCC_INVEST_DETAIL['insert_date'] . " " . $SUCC_INVEST_DETAIL['insert_time'];


			// 중앙기록관리로 보낸 해당투자정보가 모두 삭제된 이후 이므로, 새로운 prin_rcv_no로 업데이트 ★★★
			$prin_rcv_no_new = ResetRcvNo($invest_idx);

			// 투자테이블 금액 및 투자시간 업데이트
			$sqlx2 = "
				UPDATE
					cf_product_invest
				SET
					amount = amount - ".$INVEST['DTL_amount']."
					,insert_date = '".$SUCC_INVEST_DETAIL['insert_date']."'
					,insert_time = '".$SUCC_INVEST_DETAIL['insert_time']."'
					,insert_datetime = '".$last_insert_datetime."'
					,prin_rcv_no = '".$prin_rcv_no_new."'
					,bank_inquiry_id = ''
				WHERE
					idx = '".$invest_idx."'";
			//print_r($sqlx2); echo "\n";
			sql_query($sqlx2);

		}
		else {

			// 투자테이블 투자내역 완전취소
			$sqlx2 = "
				UPDATE
					cf_product_invest
				SET
					invest_state = 'N'
					,cancel_date = '".$cancel_date."'
					,cancel_by = '".$cancel_by."'
				WHERE
					idx = '".$invest_idx."'";
			//print_r($sqlx2); echo "\n";
			sql_query($sqlx2);

		}


		$PRDT = sql_fetch("SELECT title, recruit_amount, invest_end_date FROM cf_product WHERE idx = '".$product_idx."'");

		////////////////////////////////////////////////////////////////////
		// 예치금 반환 처리
		//////////////////////////////////////////////////////////////////////
		$po_content = $PRDT['title'] . "-투자 취소 (중앙기록관리등록불가)";
		insert_point($member['mb_id'], $INVEST['DTL_amount'], $po_content, '@cancel', $member['mb_id'], $member['mb_id'].'-'.uniqid(''), 0);


		//////////////////////////////////////////////////////////////////////////
		// (!중요)상품관리테이블에 실시간 모집금액 반영하기
		//////////////////////////////////////////////////////////////////////////
		$total_invest_amount = sql_fetch("SELECT IFNULL(SUM(amount),0) AS sum_amount FROM cf_product_invest WHERE product_idx = '".$product_idx."' AND invest_state='Y'")['sum_amount'];

		$sqlx3Add = "";
		if( $PRDT['recruit_amount'] > $total_invest_amount )  {
			if($PRDT['invest_end_date']) $sqlx3Add = " , invest_end_date = ''";
		}

		$sqlx3 = "
			UPDATE
				cf_product
			SET
				live_invest_amount = '".$total_invest_amount."'
				{$sqlx3Add}
			WHERE
				idx = '".$product_idx."'";
		sql_query($sqlx3);
		//////////////////////////////////////////////////////////////////////////

		echo 1;

	}

}


sql_close();
exit;

?>
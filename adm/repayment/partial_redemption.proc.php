<?
###############################################################################
## 부분상환금액 등록처리
###############################################################################

include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') {
	$RETURN_ARR = array('result'=>'ERROR', 'message'=>'최고관리자만 접근 가능합니다.');
	echo json_encode($RETURN_ARR);
	exit;
}

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }

/*
$_POST['mode']
$_POST['idx']
$_POST['turn']
$_POST['amount']
$_POST['date']
*/

$prd_idx = $idx;
$account_day = $date;


if($mode=='new') {

	/*
	부분상환등록금액과 대출상환 입금 확인 프로세스 필요

	*/

	if( $PRDT = sql_fetch("SELECT idx, recruit_amount FROM cf_product WHERE idx='".$prd_idx."'") ) {


		$R1 = sql_fetch("SELECT IFNULL(SUM(amount),0) AS sum_partial_amount FROM cf_partial_redemption WHERE product_idx='".$PRDT['idx']."'");
		$total_partial_amount = $R1['sum_partial_amount'] + $amount;

		// 부분상환총액이 대출금액보다 크면 반려처리
		if($PRDT['recruit_amount'] <= $total_partial_amount) {
			$msg = "부분상환총액이 대출금액 이상인 경우 부분상환처리 불가함.\n\n상품대출액 : ".number_format($PRDT['recruit_amount'])."원\n상환등록총액 : ".number_format($total_partial_amount)."원\n\n필요한 경우 중도상환 처리하십시요.";
			$RETURN_ARR = array('result'=>'ERROR', 'message'=>$msg);
			echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
		}

		// 설정일자가 요청된 날짜 이전이면 반려처리
		$R2 = sql_fetch("
			SELECT
				IFNULL(MAX(turn_sno),0) AS max_turn_sno,
				MAX(account_day) AS last_account_day
			FROM
				cf_partial_redemption
			WHERE 1
				AND product_idx = '".$prd_idx."' AND turn='".$turn."'");
		$next_turn_sno = $R2['max_turn_sno'] + 1;

		if($R2['last_account_day'] && ($account_day <= $R2['last_account_day']) ) {
			$msg = "추가 일부상환 등록시 최종 일부상환 등록일 「" . date("Y년m월d일", strtotime($R2['last_account_day'])) . "」이 후의 날짜를 지정하여 등록하십시요.";
			$RETURN_ARR = array('result'=>'ERROR', 'message'=>$msg);
			echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
		}

		// 일부상환금 등록
		$sqlA = "
			INSERT INTO
				cf_partial_redemption
			SET
				product_idx = '".$prd_idx."',
				turn        = '".$turn."',
				turn_sno    = '".$next_turn_sno."',
				amount      = '".$amount."',
				account_day = '".$account_day."',
				FB_SEQ      = '".$fbseq."',
				memo        = '".$memo."',
				writer_id   = '".$member['mb_id']."',
				rdate       = NOW()";
		$resA = sql_query($sqlA);

		// 플래그자료 등록
		$R3 = sql_fetch("SELECT idx cf_product_success WHERE product_idx = '".$prd_idx."' AND turn = '".$turn."' AND turn_sno = '".$next_turn_sno."'");
		if( !$R3['idx'] ) {
			$sqlB = "
				INSERT INTO
					cf_product_success
				SET
					product_idx = '".$prd_idx."',
					turn        = '".$turn."',
					turn_sno    = '".$next_turn_sno."',
					`date`      = NOW()";
			$resB = sql_query($sqlB);
		}

		if( $resA ) {
			$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'정상입니다.');
			echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

			shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/adm/repayment/make_turn_member.php {$prd_idx} > /dev/null &");
		}
		else {
			$RETURN_ARR = array('result'=>'ERROR', 'message'=>'DB 에러입니다. 관리자에게 문의하십시요.');
			echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
		}

	}

}

sql_close();

exit;

?>
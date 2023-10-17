<?

include_once('./_common.php');

while(list($k, $v)=each($_REQUEST)) { ${$k} = trim($v); }


$loan_usefee_amt = ($loan_usefee_amt) ? preg_replace("/\,/", "", $loan_usefee_amt) : 0;
$commission_fee_amt = ($commission_fee_amt) ? preg_replace("/\,/", "", $commission_fee_amt) : 0;
$schedule_amt = ($schedule_amt) ? preg_replace("/\,/", "", $schedule_amt) : 0;
$deposit_amt  = ($deposit_amt) ? preg_replace("/\,/", "", $deposit_amt) : 0;
$return_amt   = ($return_amt) ? preg_replace("/\,/", "", $return_amt) : 0;
$repay_amt    = ($repay_amt) ? preg_replace("/\,/", "", $repay_amt) : 0;
$supply_price = ($supply_price) ? preg_replace("/\,/", "", $supply_price) : 0;
$tax          = ($tax) ? preg_replace("/\,/", "", $tax) : 0;


if($collect_date && $deposit_amt=='') {
	echo json_encode(array('result' => 'ERROR', 'message' => '입금 금액을 입력하십시요.'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}


if($idx) {

	$DATA = sql_fetch("SELECT * FROM cf_loaner_fee_collect WHERE idx = '".$idx."'");

	if(!$DATA['idx']) {
		echo json_encode(array('result' => 'ERROR', 'message' => '빈 데이터 입니다.'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
	}
	if($DATA['mgtKey']) {
		echo json_encode(array('result' => 'ERROR', 'message' => '이미 계산서가 발행된 데이터 입니다.'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
	}
	if($DATA['collect_exec_date']!='' && $DATA['collect_ok']=='1') {
		echo json_encode(array('result' => 'ERROR', 'message' => '수취기록이 존재하는 데이터는 수정이 불가합니다.'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
	}

	if( strcmp($DATA['schedule_date'], $schedule_date) ) {
		$CHANGE_SQL[] = ($schedule_date) ? "schedule_date = '".$schedule_date."'" : "schedule_date = ''";
	}

	if( strcmp($DATA['loan_usefee_amt'], $loan_usefee_amt) )        $CHANGE_SQL[] = "loan_usefee_amt = '".$loan_usefee_amt."'";
	if( strcmp($DATA['commission_fee_amt'], $commission_fee_amt) )  $CHANGE_SQL[] = "commission_fee_amt = '".$commission_fee_amt."'";
	if( strcmp($DATA['schedule_amt'], $schedule_amt) )              $CHANGE_SQL[] = "schedule_amt = '".$schedule_amt."'";
	if( strcmp($DATA['bank_code'], $bank_code) )                    $CHANGE_SQL[] = "bank_code = '".$bank_code."'";
	if( strcmp($DATA['acct_no'], $acct_no) )                        $CHANGE_SQL[] = "acct_no = '".$acct_no."'";
	if( strcmp($DATA['depositor'], $depositor) )                    $CHANGE_SQL[] = "depositor = '".$depositor."'";
	if( strcmp($DATA['deposit_amt'], $deposit_amt) )                $CHANGE_SQL[] = "deposit_amt = '".$deposit_amt."'";
	if( strcmp($DATA['repay_amt'], $repay_amt) )                    $CHANGE_SQL[] = "repay_amt = '".$repay_amt."'";
	if( strcmp($DATA['return_amt'], $return_amt) )                  $CHANGE_SQL[] = "return_amt = '".$return_amt."'";
	if( strcmp($DATA['return_ok'], $return_ok) )                    $CHANGE_SQL[] = "return_ok = '".$return_ok."'";
	if( strcmp($DATA['supply_price'], $supply_price) )              $CHANGE_SQL[] = "supply_price = '".$supply_price."'";
	if( strcmp($DATA['tax'], $tax) )																$CHANGE_SQL[] = "tax = '".$tax."'";

	if( strcmp($DATA['collect_date'], $collect_date) ) {
		if($collect_date) {
			$CHANGE_SQL[] = "collect_date = '".$collect_date."'";
			$CHANGE_SQL[] = "collect_ok = '1'";
		}
		else {
			$CHANGE_SQL[] = "collect_date = NULL";
			$CHANGE_SQL[] = "collect_ok = ''";
		}
	}


	$change_fld_count = count($CHANGE_SQL);
	if($change_fld_count) {

		$sql ="UPDATE cf_loaner_fee_collect SET ";
		for($i=0,$j=1; $i<$change_fld_count; $i++,$j++) {
			$sql.= $CHANGE_SQL[$i];
			$sql.= ($j<$change_fld_count) ? ', ' : '';
		}
		$sql.= ", last_editdate = NOW() WHERE idx = '".$idx."'";

		//echo $sql; exit;

		if( sql_query($sql) ) {
			echo json_encode(array('result' => 'SUCCESS', 'message' => ''), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
		}
		else {
			echo json_encode(array('result' => 'FAIL', 'message' => 'SQL ERROR'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
		}

	}
	else {
		echo json_encode(array('result' => 'FAIL', 'message' => '변동사항 없음.'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}

}

sql_close();
exit;

?>
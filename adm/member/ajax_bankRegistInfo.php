<?

include_once("./_common.php");
include_once(G5_PATH.'/lib/insidebank.lib.php');

$RETURN_ARR = array('result'=>'', 'message'=>'');

if($_POST['mb_no']) {

	// 고객정보조회(1400)
	$ARR['REQ_NUM']    = "010";
	$ARR['SUBMIT_GBN'] = "04";
	$ARR['CUST_ID']    = $_POST['mb_no'];

	$IB_RESULT = insidebank_request('256', $ARR);

	$str = "";
	foreach($IB_RESULT AS $k=>$v) {
		$str.= $k . " : ". $v . "\n";
	}

	$RETURN_ARR['result'] = 'SUCCESS';
	$RETURN_ARR['message'] = $str;

}
else {
	$RETURN_ARR['result'] = 'FAIL';
	$RETURN_ARR['message'] = '정보없음.';
}

echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);


@sql_close();
exit;

?>
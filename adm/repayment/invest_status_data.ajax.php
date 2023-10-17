<?

set_time_limit(0);

include_once($_SERVER['DOCUMENT_ROOT'] . '/common.cli.php');


while(list($k, $v)=each($_REQUEST)) { ${$k} = @trim($v); }

if($prd_idx=='') {
	$RETURN_ARR = array('result'=>'ERROR', 'msg'=>'필수 데이터가 전달 되지 않았습니다.');
	echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}


$exec_path = "/usr/local/php/bin/php -q {$_SERVER['DOCUMENT_ROOT']}/adm/repayment/invest_status_data.exec.php " . $prd_idx;
$result = system($exec_path);

sql_close();
exit;

?>
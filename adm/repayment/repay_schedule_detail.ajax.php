<?

while(list($k, $v)=each($_REQUEST)) { ${$k} = @trim($v); }

if($prd_idx=='' || $turn=='' || $date=='') {
	$RETURN_ARR = array('result'=>'ERROR', 'msg'=>'');
	echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}

$exec_path = "/usr/local/php/bin/php -q {$_SERVER['DOCUMENT_ROOT']}/adm/repayment/repay_schedule_detail.exec.php {$prd_idx} {$turn} {$date}";
$result = system($exec_path);
exit;

?>
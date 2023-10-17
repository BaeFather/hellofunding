<?

include_once("_common.php");

while(list($key, $value)=each($_POST)) {
	${$key} = trim($value);
}

if($member['mb_no'] && $agree=='Y') {
	if(sql_query("UPDATE g5_member SET invest_warning_agree='Y' WHERE mb_no='".$member['mb_no']."'")) {
		echo 1;
	}
}

exit;

?>
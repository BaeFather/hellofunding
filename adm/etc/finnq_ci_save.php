#!/usr/local/php/bin/php -q
<?

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";

include_once($base_path . '/common.cli.php');


$action = ($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'debug';

$sql = "SELECT input FROM finnq_request_member_log ORDER BY idx ASC";
$res = sql_query($sql);
$rows = $res->num_rows;

$j = 1;
for($i=0; $i<$rows; $i++) {

	$ROW = sql_fetch_array($res);
	$finnq_userid = trim(str_f6($ROW['input'], "\"memberNumber\":\"", "\""));
	$mb_ci = trim(str_f6($ROW['input'], "\"connectingInformation\":\"", "\""));

	if($finnq_userid && $mb_ci) {
		$MB = sql_fetch("SELECT mb_no, mb_id, mb_ci FROM g5_member WHERE finnq_userid='".$finnq_userid."' AND mb_level='1' AND member_group='F'");
		if($MB['mb_no'] && $MB['mb_ci']=='') {

			$sqlx = "UPDATE g5_member SET mb_ci = '".$mb_ci."' WHERE mb_no = '".$MB['mb_no']."'";
			if($action=='yes') {
				sql_query($sqlx);
			}
			else {
				echo $sqlx . ";\n";
			}

			$j++;

		}

	}

}


sql_close();

?>
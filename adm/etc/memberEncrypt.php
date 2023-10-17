<?

exit;

set_time_limit(0);

$path = '/home/crowdfund/public_html';
include_once($path . '/common.cli.php');


$action = $_SERVER['argv'][1];


$sql = "
	SELECT
		mb_no, corp_phone
	FROM
		g5_member_history
	WHERE 1
		AND corp_phone !=''
	ORDER BY
		mb_no ASC";

$res  = sql_query($sql);
$rows = $res->num_rows;

if($rows) {

	for($i=0,$j=1; $i<$rows; $i++,$j++) {

		$R = sql_fetch_array($res);

		$ENC['corp_phone'] = masterEncrypt($R['corp_phone'], false);


		$sqlx = "UPDATE g5_member_history SET corp_phone = '".$ENC['corp_phone']."' WHERE mb_no = '".$R['mb_no']."'";

		if( $action != date('YmdH') )  {
			echo $j . " :  " . $sqlx . "\n";
		}
		else {
			if( sql_query($sqlx) ) {
				echo $j . " :  " . $sqlx . "(" . sql_affected_rows() . ")\n";
			}
		}

		//if( ($j%1000) == 0 ) sleep(1);

	}

}

sql_close();
exit;

?>
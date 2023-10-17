#!/usr/local/php/bin/php -q
<?

set_time_limit(0);

define('_GNUBOARD_', true);
define('G5_DISPLAY_SQL_ERROR', false);
define('G5_MYSQLI_USE', true);

$path = '/home/crowdfund/public_html';
include_once($path . '/data/dbconfig.php');
include_once($path . '/lib/common.lib.php');

$link = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
sql_set_charset("UTF8", $link);

$sql = "
	SELECT
		A.mb_no, A.mb_id, A.kyc_order_id, A.kyc_reg_dd, A.kyc_allow_yn, A.kyc_allow_dd, A.kyc_next_dd, A.kyc_allow_cnt,
		(SELECT COUNT(mb_no) FROM g5_member_kyc_judge_log WHERE mb_id=A.mb_no) AS kyc_judge_cnt
	FROM
		g5_member A
	WHERE 1
		AND A.kyc_order_id!='' AND A.kyc_allow_yn='Y'
		AND (SELECT COUNT(mb_no) FROM g5_member_kyc_judge_log WHERE mb_id=A.mb_no) = 0
	ORDER BY
		A.kyc_reg_dd ASC";

$res = sql_query($sql, true, $link);
$rows = $res->num_rows;

if($rows) {

	$sqlx = "INSERT INTO g5_member_kyc_judge_log (mb_no, mb_id, kyc_order_id, kyc_reg_dd, kyc_allow_yn, kyc_allow_dd, kyc_next_dd, kyc_allow_cnt, judge_mb_id, judge_dt) VALUES \n";

	for($i=0,$j=1; $i<$rows; $i++,$j++) {

		$R = sql_fetch_array($res);

		$judge_dt = $R['kyc_reg_dd'] . " 19:00:00";

		$sqlx.= "('".$R['mb_no']."', '".$R['mb_id']."', '".$R['kyc_order_id']."', '".$R['kyc_reg_dd']."', '".$R['kyc_allow_yn']."', '".$R['kyc_allow_dd']."', '".$R['kyc_next_dd']."', '".$R['kyc_allow_cnt']."', 'system', '".$judge_dt."')\n";
		$sqlx.= ($j<$rows) ? ',' : '';

	}

	echo $sqlx."\n\n";

	$res = sql_query($sqlx, true, $link);
	echo sql_affected_rows($link);

}


sql_close($link);
exit;

?>
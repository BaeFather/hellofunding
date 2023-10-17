#!/usr/local/php/bin/php -q
<?

exit;

set_time_limit(0);

define('_GNUBOARD_', true);
define('G5_DISPLAY_SQL_ERROR', false);
define('G5_MYSQLI_USE', true);

$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.php");


$table1 = 'g5_point';
$table2 = 'g5_point_x';

$sql = "SELECT * FROM $table1 ORDER BY po_id";
$res = sql_query($sql);

while( $row = sql_fetch_array($res) ) {

	$sqlx = "
		INSERT INTO
			$table2
		SET
			po_id          = '".$row['po_id']."',
			mb_no          = '".$row['mb_no']."',
			mb_id          = '".$row['mb_id']."',
			po_datetime    = '".$row['po_datetime']."',
			po_content     = '".$row['po_content']."',
			po_point       = '".$row['po_point']."',
			po_use_point   = '".$row['po_use_point']."',
			po_expired     = '".$row['po_expired']."',
			po_expire_date = '".$row['po_expire_date']."',
			po_mb_point    = '".$row['po_mb_point']."',
			po_rel_table   = '".$row['po_rel_table']."',
			po_rel_id      = '".$row['po_rel_id']."',
			po_rel_action  = '".$row['po_rel_action']."',
			po_memo        = '".$row['po_memo']."'";

	debug_flush($sqlx . "\n");

	//sql_query($sqlx);

}


echo "Finished\n";

exit;

?>
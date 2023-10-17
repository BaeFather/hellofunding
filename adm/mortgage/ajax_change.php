<?
include_once('_common.php');
?>
<?
$res = array();
$yn = $_REQUEST["yn"];
$idx = $_REQUEST["idx"];
$srch_ym = $_REQUEST["srch_ym"];


if (!$idx) die("product idx ERROR");
$res["idx"] = $_REQUEST["idx"];
$res["srch_ym"] = $_REQUEST["srch_ym"];

$ymd = date("Y-m-d");
$ym  = date("Y-m");

$up_sql1 = "UPDATE cf_loaner_push_schedule 
			   SET send_yn='$yn'
			 WHERE product_idx='$idx'
			   AND send_end_yn='N'
			   AND send_date>='$ymd'
			";
sql_query($up_sql1);



$up_sql2 = "UPDATE cf_product_turn 
			   SET sms_send_yn='$yn'
			 WHERE product_idx='$idx'
			   AND ym>='$ym'";
sql_query($up_sql2);

$turn_sql = "SELECT * FROM cf_product_turn WHERE product_idx='$idx' AND ym='$srch_ym'";
$turn_row = sql_fetch($turn_sql);
$res['res_send_yn'] = $turn_row["sms_send_yn"];

//$res['sql'] = $up_sql2;

echo json_encode($res , JSON_UNESCAPED_UNICODE);
?>
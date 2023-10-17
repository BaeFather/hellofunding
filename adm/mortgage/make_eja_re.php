<?
include_once('./_common.php');
?>
<?
$product_idx = $_REQUEST["product_idx"];

if (!$product_idx) die("품번 오류");
?>
<?
$chrow = sql_fetch("SELECT count(*) cnt FROM cf_product_turn WHERE product_idx='$product_idx' AND eja_in_date=''");

//echo $chrow["cnt"]."<br/>";

//echo "전산 담당자에게 연락주세요.";

$up_sql = "UPDATE cf_product_turn SET eja=0 WHERE product_idx='$product_idx' AND eja_in_date=''";
//echo "<br/>".$up_sql;
sql_query($up_sql);

shell_exec("/usr/local/php/bin/php /home/crowdfund/public_html/adm/mortgage/make_loaner_interest_sms_eja.php");

echo "재정산 완료";
?>
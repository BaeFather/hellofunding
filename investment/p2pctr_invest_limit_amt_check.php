#!/usr/local/php/bin/php -c /etc/php.ini -q
<?
$product_idx = $argv[1];
$member_idx  = $argv[2];


$base_path = "/home/crowdfund/public_html";
include_once($base_path . '/common.php');
include_once($base_path . '/lib/p2pctr_svc.lib.php');

$psql = "SELECT mb_id FROM g5_member WHERE mb_no='$member_idx'";
$prow = sql_fetch($psql);
$mb_id = $prow["mb_id"];


$LMT = get_p2pctr_limit($mb_id, $product_idx);
$LMT["min"] = min($LMT);
$LMT2= json_encode($LMT, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE);
echo $LMT2;
?>
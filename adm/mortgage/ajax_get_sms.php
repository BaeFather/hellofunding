<?
$base_path = "/home/crowdfund/public_html";
include_once('./_common.php');
include_once($base_path . '/adm/mortgage/mortgage_common.php');

while(list($k, $v) = each($_REQUEST)) { ${$k} = sql_real_escape_string(trim($v)); }

if (!$idx) {
	echo json_encode(array("res"=>"fail") , JSON_UNESCAPED_UNICODE);
	exit;
}

$rtn_array = array();

/*
$sql = "SELECT * FROM cf_loaner_push_schedule WHERE idx='$idx'";
$row = sql_fetch($sql);
$rtn_array['msg'] = $row['msg'];
*/

$msg = get_msg($idx);

$rtn_array['msg'] = $msg;
echo json_encode($rtn_array , JSON_UNESCAPED_UNICODE);
?>
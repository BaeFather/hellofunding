<?
include_once('./_common.php');
include_once('/home/crowdfund/public_html/lib/sms.lib.php');

$idx = $_GET['idx'];
$hcseq = $_GET['hcseq'];

$from_hp = "15885210";
$to_hp   = $_POST['to_hp'];
$send_msg = $_POST['sms_msg'];

echo $to_hp."<br>";
echo $send_msg."<br>";

if ($to_hp) {
	
	$smssql = "
			INSERT INTO 
				hloan_comment 
			SET 
				divi	= 'sms',
				req_idx = '$hcseq',
				writer  = '".$member['mb_name']."',
				mb_id   = '".$member['mb_id']."',
				comment = '$send_msg', 
				regdate = NOW()";
	
	//echo $smssql; die();
	sql_query($smssql);

	$sms_id = unit_sms_send($from_hp, $to_hp, $send_msg, $send_date=null, $send_id=null); 
	echo $sms_id;
}


goto_url('./mortgage_detail_form.php?idx='.$idx, false);

?>
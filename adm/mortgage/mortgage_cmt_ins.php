<?
include_once('./_common.php');

$CMT = $_POST["comment"];

$ins_sql = "INSERT INTO hloan_comment SET 
				divi='admin',
				req_idx='$hcseq', 
				writer='".$member['mb_name']."',
				mb_id='".$member['mb_id']."',
				comment='".addslashes($CMT)."',
				regdate=NOW()";

$ins_res = sql_query($ins_sql);


goto_url('./mortgage_detail_form.php?idx='.$idx, false);

?>


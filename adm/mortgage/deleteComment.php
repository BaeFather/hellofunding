<?
include_once('./_common.php');

$idx   = $_GET['idx'];
$cidx   = $_GET['cidx'];

if (!$cidx or !$member['mb_id']) die("권한 오류");

$sql = "DELETE 
		FROM
			hloan_comment 
		WHERE 
			idx='$cidx' AND mb_id='$member[mb_id]'";

$res = sql_query($sql);


goto_url('./mortgage_detail_form.php?save=Y&idx='.$idx, false);

?>
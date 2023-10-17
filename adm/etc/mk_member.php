<?
###############################################################################
##  원금 연체 이자 일자별 자동 생성
## CLI 로 메일 실행됨
###############################################################################
include_once('/home/crowdfund/public_html/common.php');
//include_once('./_common.php');
?>
<?
$ym = "2016-09";

// 신규회원 체크
$sql = "SELECT * FROM g5_member 
		 WHERE member_group='F' 
		   AND mb_level='1'
		   AND mb_datetime >='$ym-01 00:00:00' AND mb_datetime <='$ym-31 23:59:59'";
$res = sql_query($sql);
$cnt = $res->num_rows;

for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);

	$chk_sql = "SELECT COUNT(idx) cnt FROM cf_mkt_member where member_idx='$row[mb_no]'";
	$chk_row = sql_fetch($chk_sql);
	$chk_m = $chk_row["cnt"];

	if (!$chk_m) {
		$ins_sql = "INSERT INTO cf_mkt_member
							SET member_idx = '$row[mb_no]',
								mb_id = '".$row["mb_id"]."',
								mb_name = '".$row["mb_name"]."',
								member_type = '$row[member_type]',
								birth_y = '".substr($row["mb_birth"],0,4)."'";
		echo "$ins_sql <br/>";
	}

	if ($chk_m) $sw="";
	else $sw="INSERT";

	echo $i." ) ".$row["mb_no"]." - ".$sw."<br/>";
}
?>

<?
$sql = "SELECT * FROM cf_product_invest WHERE substring(insert_date,1,7)='$ym' AND invest_state='Y'";
echo $sql."<br/>";
?>

<?
$sql = "SELECT * FROM g5_member WHERE substring(mb_leave_date,1,7)='$ym' ";
echo $sql;
?>

<?
include_once("_common.php");

$sql = "
	SELECT
		B.mb_no, B.mb_id, B.account_num,
		COUNT(A.idx) AS invest_count
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx = B.mb_no
	WHERE 1
		AND account_num > 0
		AND B.finnq_userid = ''
		AND B.member_group='F' AND B.mb_level=1
	GROUP BY
		A.member_idx
	ORDER BY
		B.mb_no ASC";
echo $sql;
$res = sql_query($sql);

while($LIST = sql_fetch_array($res)) {

	$sql2 = "SELECT count(mb_no) AS cnt FROM IB_auth_withdrawal WHERE mb_no='".$LIST['mb_no']."'";
	$R = sql_fetch($sql2);
	if($R['cnt']==0) {
		$sql3 = "
			INSERT INTO
				IB_auth_withdrawal
			SET
				mb_no       = '".$LIST['mb_no']."',
				account_num = '".$LIST['account_num']."',
				auth_admin  = '".$member['mb_id']."',
				rdate       = NOW()";
		if($_REQUEST['action']==date(YmdHi)) {
			if(sql_query($sql3)) {
				debug_flush($succ . ": " . $sql3 . "<br>\n");
				$succ++;
			}
		}
		else {
			debug_flush($sql3 . "<br>\n");
		}
	}

}

debug_flush("Finish!!<br>\n");

?>
<?

set_time_limit(0);

include_once('_common.php');

$sql = "
	SELECT
		mb_id,
		COUNT(po_id) AS cnt,
		SUM(po_point) AS point
	FROM
		g5_point
	GROUP BY
		mb_id
	ORDER
		BY mb_id";

$res = sql_query($sql);

while( $POINT = sql_fetch_array($res) ) {

	$MB = sql_fetch("SELECT mb_no FROM g5_member WHERE mb_id='".$POINT['mb_id']."'");

	if($MB['mb_no']) {
		if($POINT['mb_no']=='') {
			echo "ID : " . $POINT['mb_id'] . " 등록수 : " . $POINT['cnt'] . " 잔액 : " . $POINT['point'] . " 회원번호 : " . $MB['mb_no'] . "<br>\n";

			$sqlx = "UPDATE g5_point SET mb_no='".$MB['mb_no']."' WHERE mb_id='".$POINT['mb_id']."' AND mb_no IS NULL";
			echo $sqlx."<br>\n";
			$resx = sql_query($sqlx);

			echo "<br><br>\n";
		}
	}


	if($MB['mb_no']=='') {
		if($POINT['mb_no']=='') {
			$DMB = sql_fetch("SELECT mb_no FROM g5_member_drop WHERE mb_id='".$POINT['mb_id']."' ORDER BY mb_no DESC LIMIT 1");

			echo "ID : " . $POINT['mb_id'] . " 등록수 : " . $POINT['cnt'] . " 잔액 : " . $POINT['point'] . " 회원번호 : " . $DMB['mb_no'] . "<br>\n";

			$sqlx = "UPDATE g5_point SET mb_no='".$DMB['mb_no']."' WHERE mb_id='".$POINT['mb_id']."' AND mb_no IS NULL";
			echo $sqlx."<br>\n";
			$resx = sql_query($sqlx);

			echo "<br><br>\n";
		}
	}


/*
	if($MB['mb_no']=='') {
		if($POINT['mb_no']=='') {
			echo "ID : " . $POINT['mb_id'] . " 등록수 : " . $POINT['cnt'] . " 잔액 : " . $POINT['point'] . " 회원번호 : " . $MB['mb_no'] . "<br>\n";

			$res2 = sql_query("SELECT po_id FROM g5_point WHERE mb_id='".$POINT['mb_id']."'");
			while( $plist = sql_fetch_array($res2) ) {
				$sqlx2 = "INSERT INTO g5_point_tmp_drop SELECT * FROM g5_point WHERE po_id='".$plist['po_id']."'";
				echo $sqlx2."<br>\n";
				//$resx2 = sql_query($sqlx2);
			}

			$sqlx = "DELETE FROM g5_point SET mb_id='".$POINT['mb_id']."'";
			echo $sqlx."<br>\n";
			//$resx = sql_query($sqlx);

			echo "<br><br>\n";
		}
	}
*/

}

?>
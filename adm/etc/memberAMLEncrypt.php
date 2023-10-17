<?

//exit;

set_time_limit(0);

$path = '/home/crowdfund/public_html';
include_once($path . '/common.cli.php');


$action = $_SERVER['argv'][1];


$sql = "
	SELECT
		mb_no, RNM_NO, REAL_OWNER_RNM_NO, REAL_OWNR_RNM_NO, CELL_PHONE_NO
	FROM
		g5_member_aml_indi
	WHERE 1
		AND LEFT(CELL_PHONE_NO,3) = '010'
	ORDER BY
		reg_dt ASC";
$res  = sql_query($sql);
$rows = $res->num_rows;

if($rows) {

	for($i=0,$j=1; $i<$rows; $i++,$j++) {

		$R = sql_fetch_array($res);

		$ENC['RNM_NO']            = ($R['RNM_NO']) ? masterEncrypt($R['RNM_NO'], false) : '';
		$ENC['REAL_OWNER_RNM_NO'] = ($R['REAL_OWNER_RNM_NO']) ? masterEncrypt($R['REAL_OWNER_RNM_NO'], false) : '';
		$ENC['REAL_OWNR_RNM_NO']  = ($R['REAL_OWNR_RNM_NO']) ? masterEncrypt($R['REAL_OWNR_RNM_NO'], false) : '';
		$ENC['CELL_PHONE_NO']     = ($R['CELL_PHONE_NO']) ? masterEncrypt($R['CELL_PHONE_NO'], false) : '';


		$sqlx = "
			UPDATE g5_member_aml_indi
			SET
				RNM_NO = '".$ENC['RNM_NO']."',
				REAL_OWNER_RNM_NO = '".$ENC['REAL_OWNER_RNM_NO']."',
				REAL_OWNR_RNM_NO = '".$ENC['REAL_OWNR_RNM_NO']."',
				CELL_PHONE_NO = '".$ENC['CELL_PHONE_NO']."'
			WHERE
				mb_no='".$R['mb_no']."' AND RNM_NO='".$R['RNM_NO']."' AND REAL_OWNER_RNM_NO='".$R['REAL_OWNER_RNM_NO']."' AND REAL_OWNR_RNM_NO='".$R['REAL_OWNR_RNM_NO']."' AND CELL_PHONE_NO='".$R['CELL_PHONE_NO']."'";

		if( $action != date('YmdH') )  {
			echo $j . " :  " . $sqlx . "\n";
		}
		else {
			if( sql_query($sqlx) ) {
				echo $j . " :  " . $sqlx . "(" . sql_affected_rows() . ")\n";
			}
		}

		//if( ($j%1000) == 0 ) sleep(1);

	}

}

sql_close();
exit;

?>
#!/usr/local/php/bin/php -q
<?

###############################################################################
## 새로운 암호화체계 적용 마이그레이션 (AES256, RSA256 -> ARIA256)
###############################################################################

set_time_limit(0);

$path = '/home/crowdfund/public_html';
include_once($path . '/common.cli.php');


$action = true;

$member_table  = "g5_member";
$private_table = "member_private";


$sql = "
	SELECT
		mb_no, mb_id, member_group, member_type, mb_hp, mb_hp_ineb, account_num, account_num_ineb, corp_phone, corp_phone_ineb
	FROM
		{$member_table}
	WHERE 1
		-- AND mb_no = '58654'
		AND mb_level = '1'
		AND ( (mb_hp != '' AND mb_hp_ineb ='') OR (account_num != '' AND account_num_ineb = '') OR (corp_phone != '' AND corp_phone_ineb = '') )
	ORDER BY
		mb_no DESC";

$res  = sql_query($sql);
$rows = $res->num_rows;

if($rows) {

	$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2);

	for($i=0,$j=1; $i<$rows; $i++,$j++) {

		$INEB_ENC = NULL;

		$R = sql_fetch_array($res);

		$R['mb_hp'] = masterDecrypt($R['mb_hp'], false);
		if($R['account_num']) $R['account_num'] = masterDecrypt($R['account_num'], false);
		if($R['corp_phone'])  $R['corp_phone']  = masterDecrypt($R['corp_phone'], false);

		if($action) {
			$R['jumin'] = getJumin($R['mb_no']);
		}

		echo "[".$j."]" . " :::::::::::::::::::::\n";

		// 휴대번호, 계좌번호 암호화
		if($R['mb_hp'] || $R['account_num'] || $R['corp_phone']) {


			if( in_array(substr($R['mb_hp'],0,3), array('010','011','016','017','018','019')) ) {
				$INEB_ENC['mb_hp'] =  DGuardEncrypt($R['mb_hp']);
			}

			if($R['account_num']) {
				$INEB_ENC['account_num'] = DGuardEncrypt($R['account_num']);
			}

			if($R['corp_phone']) {
				$INEB_ENC['corp_phone'] = DGuardEncrypt($R['corp_phone']);
			}


			//echo $R['mb_hp'] . " => " . $INEB_ENC['mb_hp'] ."\n";
			//echo $R['account_num'] . " => " . $INEB_ENC['account_num'] . "\n";

			$sqlx = "UPDATE {$member_table} SET mb_hp_ineb = '".$INEB_ENC['mb_hp']."'";
			if($R['account_num']) $sqlx.= ", account_num_ineb = '".$INEB_ENC['account_num']."'";
			if($R['corp_phone']) $sqlx.= ", corp_phone_ineb = '".$INEB_ENC['corp_phone']."'";
			$sqlx.= " WHERE mb_no = '".$R['mb_no']."'";
			echo $sqlx;
			if($action) {
				$resx = sql_query($sqlx);
				if($resx) echo " (".sql_affected_rows().")";
			}
			echo "\n";

		}


		if($R['jumin']) {

			$INEB_ENC['jumin'] = DGuardEncrypt($R['jumin']);

			//echo $R['jumin'] . " => " . $INEB_ENC['jumin'] . "\n";


			$PRIVATE_DATA = sql_fetch("SELECT idx, mb_no  FROM {$private_table} WHERE mb_no='".$R['mb_no']."'", "", $linkX);

			if($PRIVATE_DATA['idx']) {
				$sqlx2 = "UPDATE {$private_table} SET regist_number_ineb = '".$INEB_ENC['jumin']."' WHERE idx = '".$PRIVATE_DATA['idx']."'";
			}
			else {
				$sqlx2 = "INSERT INTO {$private_table} SET mb_no = '".$R['mb_no']."', regist_number_ineb = '".$INEB_ENC['jumin']."'";
			}

			echo $sqlx2;
			$resx2 = sql_query($sqlx2, "", $linkX);
			if($resx2) echo "(".sql_affected_rows($linkX).")";
			echo "\n";

			//if($j==1000) break;

		}

		if(($j%1000)==0) usleep(1000000);

	}			// end for($i=0,$j=1; $i<$rows; $i++,$j++)

	sql_close($linkX);

}

sql_close();
exit;


?>
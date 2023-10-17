#!/usr/local/php/bin/php -q
<?
###############################################################################
## 투자진행중인 상품의 투자요약정보 만들기
## CRONTAB: * * * * * /home/crowdfund/schedule_work/make_invest_summary_json.checker.sh > /dev/null &
## 매분마다 프로세스체커 실행
## 진행중인 상품이 없을 경우 exit
###############################################################################

$mode = @$_SERVER['argv'][1];

set_time_limit(0);

define('_GNUBOARD_', true);
define('G5_DISPLAY_SQL_ERROR', false);
define('G5_MYSQLI_USE', true);

$path = '/home/crowdfund/public_html';
include_once($path . '/data/dbconfig.php');
include_once($path . '/lib/common.lib.php');

$jsonDir = $path . '/data/invest_json';


$x = true;

while($x > 0) {

	if($mode=='debug') {
		$begin_time = get_microtime();
	}

	//---------------------------------------------------------------------------
	$link = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
	sql_set_charset("UTF8", $link);
	//---------------------------------------------------------------------------


	$sql = "
		SELECT
			idx, recruit_amount
		FROM
			cf_product
		WHERE 1
			AND state = ''
			AND isTest = ''
			AND recruit_amount > 10000
			AND recruit_amount >= live_invest_amount
			AND start_datetime <= NOW()-INTERVAL 1 MINUTE
			AND end_datetime >= NOW()
			AND (invest_end_date = '' OR invest_end_date = CURDATE())
		ORDER BY
			start_num ASC";

	$res  = sql_query($sql, false, $link);
	$live_product_count = $res->num_rows;

	if($live_product_count) {

		for($i=0,$j=1; $i<$live_product_count; $i++,$j++) {

			$PRDT = sql_fetch_array($res);

			$invest_sql = "
				SELECT
					COUNT(idx) AS cnt,
					IFNULL(SUM(amount),0) AS amount
				FROM
					cf_product_invest
				WHERE 1
					AND product_idx='".$PRDT['idx']."'
					AND invest_state='Y'";
			$R = sql_fetch($invest_sql, false, $link);

			$PRDT['invest_count']    = (string)$R['cnt'];
			$PRDT['invest_amount']   = (string)$R['amount'];
			$PRDT['recruit_balance'] = (string)($PRDT['recruit_amount'] - $PRDT['invest_amount']);		// 잔여모집액

			$recruit_percent = @($PRDT['invest_amount'] / $PRDT['recruit_amount']) * 100;		// 모집율
			$PRDT['recruit_percent'] = floatRtrim(floatCutting($recruit_percent, 2)) . '%';

			$json = json_encode($PRDT, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);


			$fName = $PRDT['idx'].'.json';
			$fPath = $jsonDir . '/' . $fName;

			$byte  = file_put_contents($fPath, $json);

			if($mode=='debug') echo $fPath . " (" . $byte . "byte)\n";

		}

	}

	sql_free_result($res);
	sql_close($link);

	if($mode=='debug') {
		$exec_time = get_microtime() - $begin_time;
		echo "RUN TIME : " . floatCutting($exec_time, 4) ."\n";
	}

	// 투자중인 상품이 없을 경우 종료
	if(!$live_product_count) {
		shell_exec("find $jsonDir -mtime +30 | xargs rm -f\n");		// 30일이 지난 json파일 삭제
		exit;
	}

	usleep(300000);

}



exit;

?>

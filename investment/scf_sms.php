<?
################################################################################
## SCF 모집완료시 관리자 문자발송
################################################################################

include_once('_common.php');


include_once(G5_LIB_PATH.'/sms.lib.php');
//include_once(G5_LIB_PATH.'/mailer.lib.php');
//include_once(G5_LIB_PATH.'/invest_queue.lib.php');

$input_datetime = date('Y-m-d H:i:s');
$INPUT_DATE = explode(" ", $input_datetime);
$input_day  = $INPUT_DATE[0];

$chk_so_sql1 = "SELECT COUNT(*) scf_not_end FROM cf_product WHERE category='3' AND start_date='$input_day'";
$chk_so_row1 = sql_fetch($chk_so_sql1);

$chk_so_sql = "SELECT COUNT(*) scf_not_end FROM cf_product WHERE category='3' AND start_date='$input_day' AND invest_end_date=''";
$chk_so_row = sql_fetch($chk_so_sql);

if ($chk_so_row1["scf_not_end"]>0 and $chk_so_row["scf_not_end"]==0) {					
	echo "완료";
	//$report_idx = fn_cf_product_admin_report_scf($input_day);		// 리포트 데이터 생성
	//fn_hello_status_smssend_scf($report_idx);			// SMS전송
} else echo "미완료";

?>
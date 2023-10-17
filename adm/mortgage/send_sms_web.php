<?
//exec('/usr/local/php/bin/php /home/crowdfund/schedule_work/interest_sms.php',$output, $retval);
$ymd = $_REQUEST["ymd"];
echo "----- $ymd ----- <br/><br/>";
$output = shell_exec('/usr/local/php/bin/php /home/crowdfund/schedule_work/interest_sms.php '.$ymd);
/**
 * 관리자 웹에서 문자 보내기
 */
//include_once('./_common.php');


echo "<pre>";
echo $output;
echo "</pre>";
?>
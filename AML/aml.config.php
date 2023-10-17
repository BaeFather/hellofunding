<?

$AMLCF['report_domain'] = 'http://10.22.162.37';
$AMLCF['report_port']   = '8080';
$AMLCF['aml_log_table'] = 'aml_curl_request_log';

$AMLCF['db_host']    = '10.22.162.37';
$AMLCF['db_port']    = '3306';
$AMLCF['db_user']    = 'amluser';
$AMLCF['db_passwd']  = 'amlpwd^1q2w3e!@#';
$AMLCF['db_name']    = 'amlsystem';

$amlDBConn   = sql_connect($AMLCF['db_host'], $AMLCF['db_user'], $AMLCF['db_passwd'], $AMLCF['db_name']) or die('AML MySQL Connect Error!!!');
$amlSelectDB = sql_select_db($AMLCF['db_name'], $amlDBConn) or die('AML MySQL DB Error!!!');

sql_set_charset('utf8', $amlDBConn);
if(defined('G5_MYSQL_SET_MODE') && G5_MYSQL_SET_MODE) sql_query("SET SESSION sql_mode = ''");

?>
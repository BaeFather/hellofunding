#!/usr/local/php/bin/php -q
<?

set_time_limit(0);

$sdate = '2021-05-01'

$ets = time();
$sts = strtotime($sdate);

$days = ceil(($sts - $ets) / 86400);

echo $days;



?>
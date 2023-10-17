<?

$geoip_database_info = geoip_database_info(GEOIP_COUNTRY_EDITION);
$geoip_db_filename   = geoip_db_filename(GEOIP_COUNTRY_EDITION);
$all_info            = geoip_db_get_all_info();

echo "geoip_database_info = ".$geoip_database_info."<br>\n";
echo "geoip_db_filename   = ".$geoip_db_filename."<br>\n";

// 도메인별로 서버가 어느 국가에 위치해 있는지 알아보기
$arURL  = array(
	'110.35.149.186',
	'110.70.26.27',
	'112.147.213.91',
	'115.161.240.45',
	'115.21.113.102',
	'118.235.8.148',
	'119.70.61.175',
	'121.133.118.215',
	'122.45.102.70',
	'124.54.207.105',
	'125.180.20.55',
	'175.115.60.238',
	'175.223.22.44',
	'182.226.225.161',
	'211.211.87.98',
	'211.212.232.20',
	'211.248.149.48',
	'211.44.78.97',
	'218.237.234.33',
	'218.237.235.248',
	'220.118.225.128',
	'220.118.79.130',
	'222.233.119.99',
	'223.38.42.52',
	'223.38.47.203',
	'223.38.53.104',
	'223.38.54.166',
	'39.115.105.92',
	'39.117.105.59',
	'39.7.24.159',
	'39.7.24.162',
	'39.7.28.135',
	'58.127.198.98',
	'61.82.107.35'
);

foreach( $arURL as $u ) {
	$country        = geoip_country_name_by_name($u);
	$country_code   = geoip_country_code_by_name($u);
	$country_3code  = geoip_country_code3_by_name($u);

	echo "* $u = $country, $country_code, $country_3code <br>\n";
}

/*
echo "<pre>";
print_r($all_info);
echo "</pre>";
*/


?>
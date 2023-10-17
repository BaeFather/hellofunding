#!/usr/local/php/bin/php -q
<?
###############################################################################
## 일괄 지급플래그 처리하기
###############################################################################

exit;

set_time_limit(0);

$base_path = "/home/crowdfund/public_html";

include_once($base_path . '/common.cli.php');

$action = ($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'debug';

$REP = array(
		array('prd_idx'=>'1014', 'turn'=>'12')
	, array('prd_idx'=>'1093', 'turn'=>'12')
	, array('prd_idx'=>'1211', 'turn'=>'10')
	, array('prd_idx'=>'1232', 'turn'=>'11')
	, array('prd_idx'=>'1263', 'turn'=>'11')
	, array('prd_idx'=>'1266', 'turn'=>'11')
	, array('prd_idx'=>'1300', 'turn'=>'11')
	, array('prd_idx'=>'1370', 'turn'=>'10')
	, array('prd_idx'=>'1458', 'turn'=>'10')
	, array('prd_idx'=>'1484', 'turn'=>'9')
	, array('prd_idx'=>'1485', 'turn'=>'9')
	, array('prd_idx'=>'1533', 'turn'=>'8')
	, array('prd_idx'=>'1553', 'turn'=>'9')
	, array('prd_idx'=>'1617', 'turn'=>'9')
	, array('prd_idx'=>'1624', 'turn'=>'9')
	, array('prd_idx'=>'1627', 'turn'=>'8')
	, array('prd_idx'=>'1672', 'turn'=>'8')
	, array('prd_idx'=>'1674', 'turn'=>'8')
	, array('prd_idx'=>'1675', 'turn'=>'8')
	, array('prd_idx'=>'1681', 'turn'=>'8')
	, array('prd_idx'=>'1712', 'turn'=>'8')
	, array('prd_idx'=>'1730', 'turn'=>'8')
	, array('prd_idx'=>'1731', 'turn'=>'8')
	, array('prd_idx'=>'1749', 'turn'=>'8')
	, array('prd_idx'=>'1767', 'turn'=>'8')
	, array('prd_idx'=>'1782', 'turn'=>'7')
	, array('prd_idx'=>'1813', 'turn'=>'7')
	, array('prd_idx'=>'1824', 'turn'=>'7')
	, array('prd_idx'=>'1825', 'turn'=>'7')
	, array('prd_idx'=>'1843', 'turn'=>'7')
	, array('prd_idx'=>'1845', 'turn'=>'7')
	, array('prd_idx'=>'1852', 'turn'=>'7')
	, array('prd_idx'=>'1853', 'turn'=>'7')
	, array('prd_idx'=>'1864', 'turn'=>'7')
	, array('prd_idx'=>'1884', 'turn'=>'6')
	, array('prd_idx'=>'1888', 'turn'=>'7')
	, array('prd_idx'=>'1899', 'turn'=>'7')
	, array('prd_idx'=>'1907', 'turn'=>'7')
	, array('prd_idx'=>'1925', 'turn'=>'7')
	, array('prd_idx'=>'1945', 'turn'=>'7')
	, array('prd_idx'=>'1976', 'turn'=>'7')
	, array('prd_idx'=>'1999', 'turn'=>'6')
	, array('prd_idx'=>'2013', 'turn'=>'6')
	, array('prd_idx'=>'2029', 'turn'=>'6')
	, array('prd_idx'=>'2102', 'turn'=>'6')
	, array('prd_idx'=>'2123', 'turn'=>'6')
	, array('prd_idx'=>'2145', 'turn'=>'5')
	, array('prd_idx'=>'2190', 'turn'=>'5')
	, array('prd_idx'=>'2228', 'turn'=>'5')
	, array('prd_idx'=>'2244', 'turn'=>'5')
	, array('prd_idx'=>'2251', 'turn'=>'5')
	, array('prd_idx'=>'2258', 'turn'=>'5')
	, array('prd_idx'=>'2284', 'turn'=>'5')
	, array('prd_idx'=>'2286', 'turn'=>'5')
	, array('prd_idx'=>'2362', 'turn'=>'4')
	, array('prd_idx'=>'2363', 'turn'=>'4')
	, array('prd_idx'=>'2379', 'turn'=>'4')
	, array('prd_idx'=>'2410', 'turn'=>'4')
	, array('prd_idx'=>'2411', 'turn'=>'4')
	, array('prd_idx'=>'2431', 'turn'=>'4')
	, array('prd_idx'=>'2432', 'turn'=>'4')
	, array('prd_idx'=>'2446', 'turn'=>'4')
	, array('prd_idx'=>'2447', 'turn'=>'4')
	, array('prd_idx'=>'2448', 'turn'=>'4')
	, array('prd_idx'=>'2451', 'turn'=>'4')
	, array('prd_idx'=>'2479', 'turn'=>'4')
	, array('prd_idx'=>'2488', 'turn'=>'4')
	, array('prd_idx'=>'2495', 'turn'=>'4')
	, array('prd_idx'=>'2513', 'turn'=>'4')
	, array('prd_idx'=>'2514', 'turn'=>'4')
	, array('prd_idx'=>'2515', 'turn'=>'4')
	, array('prd_idx'=>'2517', 'turn'=>'3')
	, array('prd_idx'=>'2542', 'turn'=>'4')
	, array('prd_idx'=>'2543', 'turn'=>'3')
	, array('prd_idx'=>'2544', 'turn'=>'3')
	, array('prd_idx'=>'2563', 'turn'=>'3')
	, array('prd_idx'=>'2564', 'turn'=>'3')
	, array('prd_idx'=>'2597', 'turn'=>'3')
	, array('prd_idx'=>'2598', 'turn'=>'3')
	, array('prd_idx'=>'2630', 'turn'=>'3')
	, array('prd_idx'=>'2640', 'turn'=>'3')
	, array('prd_idx'=>'2642', 'turn'=>'3')
	, array('prd_idx'=>'2643', 'turn'=>'3')
	, array('prd_idx'=>'2664', 'turn'=>'3')
	, array('prd_idx'=>'2674', 'turn'=>'3')
	, array('prd_idx'=>'2677', 'turn'=>'3')
	, array('prd_idx'=>'2679', 'turn'=>'3')
	, array('prd_idx'=>'2689', 'turn'=>'3')
	, array('prd_idx'=>'2700', 'turn'=>'3')
	, array('prd_idx'=>'2714', 'turn'=>'3')
	, array('prd_idx'=>'2722', 'turn'=>'3')
	, array('prd_idx'=>'2742', 'turn'=>'3')
	, array('prd_idx'=>'2746', 'turn'=>'3')
	, array('prd_idx'=>'2769', 'turn'=>'3')
	, array('prd_idx'=>'2770', 'turn'=>'3')
	, array('prd_idx'=>'2786', 'turn'=>'2')
	, array('prd_idx'=>'2788', 'turn'=>'2')
	, array('prd_idx'=>'2801', 'turn'=>'2')
	, array('prd_idx'=>'2803', 'turn'=>'2')
	, array('prd_idx'=>'2810', 'turn'=>'2')
	, array('prd_idx'=>'2816', 'turn'=>'2')
	, array('prd_idx'=>'2824', 'turn'=>'2')
	, array('prd_idx'=>'2827', 'turn'=>'2')
	, array('prd_idx'=>'2828', 'turn'=>'2')
	, array('prd_idx'=>'2829', 'turn'=>'2')
	, array('prd_idx'=>'2831', 'turn'=>'2')
	, array('prd_idx'=>'2842', 'turn'=>'2')
	, array('prd_idx'=>'2852', 'turn'=>'2')
	, array('prd_idx'=>'2854', 'turn'=>'2')
	, array('prd_idx'=>'2861', 'turn'=>'2')
	, array('prd_idx'=>'2872', 'turn'=>'2')
	, array('prd_idx'=>'2875', 'turn'=>'2')
	, array('prd_idx'=>'2885', 'turn'=>'2')
	, array('prd_idx'=>'2886', 'turn'=>'2')
	, array('prd_idx'=>'2894', 'turn'=>'2')
	, array('prd_idx'=>'2895', 'turn'=>'2')
	, array('prd_idx'=>'2897', 'turn'=>'2')
	, array('prd_idx'=>'2898', 'turn'=>'2')
	, array('prd_idx'=>'2899', 'turn'=>'2')
	, array('prd_idx'=>'2906', 'turn'=>'2')
	, array('prd_idx'=>'2907', 'turn'=>'2')
	, array('prd_idx'=>'2918', 'turn'=>'2')
	, array('prd_idx'=>'2919', 'turn'=>'2')
	, array('prd_idx'=>'2920', 'turn'=>'2')
	, array('prd_idx'=>'2921', 'turn'=>'2')
	, array('prd_idx'=>'2930', 'turn'=>'2')
	, array('prd_idx'=>'2933', 'turn'=>'2')
	, array('prd_idx'=>'2934', 'turn'=>'2')
	, array('prd_idx'=>'2935', 'turn'=>'2')
	, array('prd_idx'=>'2948', 'turn'=>'2')
	, array('prd_idx'=>'2949', 'turn'=>'2')
	, array('prd_idx'=>'2950', 'turn'=>'2')
	, array('prd_idx'=>'2960', 'turn'=>'2')
	, array('prd_idx'=>'2961', 'turn'=>'2')
	, array('prd_idx'=>'2974', 'turn'=>'2')
	, array('prd_idx'=>'2975', 'turn'=>'2')
	, array('prd_idx'=>'2976', 'turn'=>'2')
	, array('prd_idx'=>'2978', 'turn'=>'2')
	, array('prd_idx'=>'2981', 'turn'=>'2')
	, array('prd_idx'=>'2982', 'turn'=>'2')
	, array('prd_idx'=>'2986', 'turn'=>'2')
	, array('prd_idx'=>'2994', 'turn'=>'2')
	, array('prd_idx'=>'2997', 'turn'=>'2')
	, array('prd_idx'=>'2998', 'turn'=>'2')
	, array('prd_idx'=>'2999', 'turn'=>'2')
	, array('prd_idx'=>'3001', 'turn'=>'2')
	, array('prd_idx'=>'3008', 'turn'=>'2')
	, array('prd_idx'=>'3020', 'turn'=>'1')
	, array('prd_idx'=>'3021', 'turn'=>'2')
	, array('prd_idx'=>'3022', 'turn'=>'2')
	, array('prd_idx'=>'3023', 'turn'=>'2')
	, array('prd_idx'=>'3024', 'turn'=>'2')
	, array('prd_idx'=>'3031', 'turn'=>'2')
	, array('prd_idx'=>'3032', 'turn'=>'2')
	, array('prd_idx'=>'3034', 'turn'=>'2')
	, array('prd_idx'=>'3036', 'turn'=>'2')
	, array('prd_idx'=>'3046', 'turn'=>'1')
	, array('prd_idx'=>'3047', 'turn'=>'1')
	, array('prd_idx'=>'3049', 'turn'=>'1')
	, array('prd_idx'=>'3050', 'turn'=>'1')
	, array('prd_idx'=>'3051', 'turn'=>'1')
	, array('prd_idx'=>'3052', 'turn'=>'1')
	, array('prd_idx'=>'3061', 'turn'=>'1')
	, array('prd_idx'=>'3063', 'turn'=>'1')
	, array('prd_idx'=>'3064', 'turn'=>'1')
	, array('prd_idx'=>'3066', 'turn'=>'1')
	, array('prd_idx'=>'3067', 'turn'=>'1')
	, array('prd_idx'=>'3068', 'turn'=>'1')
	, array('prd_idx'=>'3075', 'turn'=>'1')
	, array('prd_idx'=>'3076', 'turn'=>'1')
	, array('prd_idx'=>'3077', 'turn'=>'1')
	, array('prd_idx'=>'3078', 'turn'=>'1')
	, array('prd_idx'=>'3079', 'turn'=>'1')
	, array('prd_idx'=>'3086', 'turn'=>'1')
	, array('prd_idx'=>'3100', 'turn'=>'1')
	, array('prd_idx'=>'3107', 'turn'=>'1')
	, array('prd_idx'=>'3108', 'turn'=>'1')
	, array('prd_idx'=>'3109', 'turn'=>'1')
	, array('prd_idx'=>'3110', 'turn'=>'1')
	, array('prd_idx'=>'3118', 'turn'=>'1')
	, array('prd_idx'=>'3119', 'turn'=>'1')
	, array('prd_idx'=>'3120', 'turn'=>'1')
	, array('prd_idx'=>'3121', 'turn'=>'1')
	, array('prd_idx'=>'3122', 'turn'=>'1')
	, array('prd_idx'=>'3123', 'turn'=>'1')
	, array('prd_idx'=>'3124', 'turn'=>'1')
	, array('prd_idx'=>'3131', 'turn'=>'1')
	, array('prd_idx'=>'3132', 'turn'=>'1')
	, array('prd_idx'=>'3145', 'turn'=>'1')
	, array('prd_idx'=>'3146', 'turn'=>'1')
	, array('prd_idx'=>'3147', 'turn'=>'1')
	, array('prd_idx'=>'3148', 'turn'=>'1')
	, array('prd_idx'=>'3149', 'turn'=>'1')
	, array('prd_idx'=>'3150', 'turn'=>'1')
	, array('prd_idx'=>'3152', 'turn'=>'1')
	, array('prd_idx'=>'3153', 'turn'=>'1')
	, array('prd_idx'=>'3155', 'turn'=>'1')
	, array('prd_idx'=>'3156', 'turn'=>'1')
	, array('prd_idx'=>'3157', 'turn'=>'1')
	, array('prd_idx'=>'3164', 'turn'=>'1')
	, array('prd_idx'=>'3165', 'turn'=>'1')
	, array('prd_idx'=>'3166', 'turn'=>'1')
	, array('prd_idx'=>'3167', 'turn'=>'1')
	, array('prd_idx'=>'3168', 'turn'=>'1')
	, array('prd_idx'=>'3175', 'turn'=>'1')
	, array('prd_idx'=>'3176', 'turn'=>'1')
	, array('prd_idx'=>'3177', 'turn'=>'1')
	, array('prd_idx'=>'3178', 'turn'=>'1')
	, array('prd_idx'=>'3179', 'turn'=>'1')
	, array('prd_idx'=>'3186', 'turn'=>'1')
	, array('prd_idx'=>'3187', 'turn'=>'1')
	, array('prd_idx'=>'3194', 'turn'=>'1')
	, array('prd_idx'=>'3201', 'turn'=>'1')
	, array('prd_idx'=>'3215', 'turn'=>'1')
	, array('prd_idx'=>'3223', 'turn'=>'1')
);

$list_count = count($REP);


for($i=0,$j=1; $i<$list_count; $i++,$j++) {

	$sql = "SELECT idx, loan_interest_state FROM cf_product_success WHERE product_idx='".$REP[$i]['prd_idx']."' AND turn='".$REP[$i]['turn']."' AND turn_sno='0'";
	//echo $sql.";\n";
	$FLAG = sql_fetch($sql);


	$sql2 = '';

	if($FLAG['idx']=='') {
		$sql2 = "
			INSERT INTO
				cf_product_success
			SET
				product_idx = '".$REP[$i]['prd_idx']."',
				turn = '".$REP[$i]['turn']."',
				-- turn_sno = '0',
				loan_interest_state = 'Y',
				date = '".date('Y-m-d')."'";

	}
	else {
		if($FLAG['loan_interest_state'] == '') {
			$sql2 = "
				UPDATE
					cf_product_success
				SET
					loan_interest_state = 'Y'
				WHERE
					idx = '".$FLAG['idx']."'";
		}
	}

	if($sql2) {
		echo $sql2 . ";";

		if($action == 'yes') {
			$res2 = sql_query($sql2);
			echo " (" . sql_affected_rows() . ")";
		}

		echo "\n\n";

	}


}


sql_close();
exit;

?>
<?
exit;

// cf_product_success 에 일괄 등록
// 쿼리 출력만 해줌

include_once("_common.php");

$prdt = "1458,1624,1617,1533,1627,1674,1681,1675,1712,1749,1767,1730,1825,1813,1845,1852,1853,1888,1907,1899,1782,1945,1976,1884,2190,2145,2228,2244,2362,2363,2379,2411,2517,2742,2801,2842,2852,2854,2861,2872,2875,2897,2898,2919,2921,2934,2949,2960,2961,2974,2975,2997,3008,2933,3023,3063,3079,3086,3020,3107,3110,3118,3131,3155,3132,3157,3167,3187,3194,3201,3168,3178,3215,3208,3179,3223,3224,3226,3239,3225,3247,3249,3256,3263,3270,3248,3278,3279,3299,3306,3308,3271,3307,3315,3324,3323,3332,3333,3334,3349,3341,3359,3366,3382,3391,3390,3401,3422,3419,3432,3442,3433,3434,3452,3451,3461,3462,3470,3471,3472,3481,3490,3491,3492,3493,3501,3511,3515,3516,3538,3539,3540,3510,3552,3556,3551,3512,3567,3575,3550,3553,3554,3557,3558,3587,3586,3577,3596,3555,3409,3607,3597,3598,3609,3616,3621,3611,3613,3618,3624,3608,3610,3612,3614,3615,3617,3619,3620,3622,3623,3625,3626,3627,3628";
$PRDT_ARR = explode(",", $prdt);

echo "<pre style='font-size:12px;'>";

for($i=0,$j=1; $i<count($PRDT_ARR); $i++,$j++) {

	$sql = "SELECT idx, turn, loan_interest_state, invest_give_state FROM cf_product_success WHERE product_idx='".$PRDT_ARR[$i]."' AND turn_sno='0' ORDER BY idx DESC LIMIT 1";
	$DATA = sql_fetch($sql);

	//echo $j . " : ";
	if($DATA['idx']) {
		if($DATA['invest_give_state']=='Y') {
			$next_turn = $DATA['turn'] + 1;
			$sql3 = "INSERT INTO cf_product_success (product_idx, turn, turn_sno, loan_interest_state, `date`) VALUES('".$PRDT_ARR[$i]."', '".$next_turn."', '0', 'Y', CURDATE())";
		}
		else {
			$sql3 = "UPDATE cf_product_success SET loan_interest_state='Y', `date`=CURDATE WHERE idx='".$DATA['idx']."'";
		}
	}
	else {
		$next_turn = $DATA['turn'] + 1;
		$sql3 = "INSERT INTO cf_product_success (product_idx, turn, turn_sno, loan_interest_state, `date`) VALUES('".$PRDT_ARR[$i]."', '".$next_turn."', '0', 'Y', CURDATE())";
	}
	echo $sql3 . ";";
	echo "\n";

}

echo "</pre>";

/*
$sql = "
	SELECT
		A.idx,
		(SELECT IFNULL(MAX(turn),0) FROM cf_product_success WHERE product_idx=A.idx AND turn_sno='0' AND loan_interest_state='Y') AS last_turn
	FROM
		cf_product A
	WHERE
		A.idx IN(
			1458,1624,1617,1533,1627,1674,1681,1675,1712,1749,
			1767,1730,1825,1813,1845,1852,1853,1888,1907,1899,
			1782,1945,1976,1884,2190,2145,2228,2244,2362,2363,
			2379,2411,2517,2742,2801,2842,2852,2854,2861,2872,
			2875,2897,2898,2919,2921,2934,2949,2960,2961,2974,
			2975,2997,3008,2933,3023,3063,3079,3086,3020,3107,
			3110,3118,3131,3155,3132,3157,3167,3187,3194,3201,
			3168,3178,3215,3208,3179,3223,3224,3226,3239,3225,
			3247,3249,3256,3263,3270,3248,3278,3279,3299,3306,
			3308,3271,3307,3315,3324,3323,3332,3333,3334,3349,
			3341,3359,3366,3382,3391,3390,3401,3422,3419,3432,
			3442,3433,3434,3452,3451,3461,3462,3470,3471,3472,
			3481,3490,3491,3492,3493,3501,3511,3515,3516,3538,
			3539,3540,3510,3552,3556,3551,3512,3567,3575,3550,
			3553,3554,3557,3558,3587,3586,3577,3596,3555,3409,
			3607,3597,3598,3609,3616,3621,3611,3613,3618,3624,
			3608,3610,3612,3614,3615,3617,3619,3620,3622,3623,
			3625,3626,3627,3628
		)
	ORDER BY
		A.loan_start_date,
		A.idx";
$res  = sql_query($sql);
$rows = sql_num_rows($res);

echo "<pre style='font-size:12px;'>";

for($i=0,$j=1; $i<$rows; $i++,$j++) {

	$R = sql_fetch_array($res);

	$sql2 = "SELECT idx, loan_interest_state FROM cf_product_success WHERE product_idx='".$R['idx']."' AND turn='".$R['last_turn']."' AND turn_sno='0'";
	$DATA = sql_fetch($sql2);

	echo $j . " : ";
	if($DATA['idx']) {
		if($DATA['loan_interest_state']!='Y') {
			$sql3 = "UPDATE cf_product_success SET loan_interest_state='Y', `date`=CURDATE WHERE product_idx='".$R['idx']."' AND turn='".$R['last_turn']."' AND turn_sno='0'";
		}
	}
	else {
		$sql3 = "INSERT INTO cf_product_success (product_idx, turn, turn_sno, loan_interest_state, `date`) VALUES('".$R['idx']."', '".$R['last_turn']."', '0', 'Y', CURDATE());";
	}
	echo $sql3 . ";";
	echo "\n";
}

*/


sql_close();

?>
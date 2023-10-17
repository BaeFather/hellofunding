<?
/////////////////////////////////////////////////
// 임시 예치금 보정 : 2020-08-05
/////////////////////////////////////////////////

include_once('./_common.php');

if( $_REQUEST['action'] != date('YmdHi') ) { exit; }


$MEMPOINT = array(
/*
		array('member_idx'=>6992, 'repair_point'=>870000)
	, array('member_idx'=>6898, 'repair_point'=>10000)
	, array('member_idx'=>19804, 'repair_point'=>50000)
	, array('member_idx'=>7101, 'repair_point'=>100000)
	, array('member_idx'=>23100, 'repair_point'=>10000)
	, array('member_idx'=>23662, 'repair_point'=>200000)
	, array('member_idx'=>18501, 'repair_point'=>150000)
	, array('member_idx'=>11492, 'repair_point'=>1000000)
	, array('member_idx'=>28479, 'repair_point'=>100000)
	, array('member_idx'=>23842, 'repair_point'=>300000)
	, array('member_idx'=>21481, 'repair_point'=>5000000)
	, array('member_idx'=>15961, 'repair_point'=>3010000)
	, array('member_idx'=>12557, 'repair_point'=>600000)
	, array('member_idx'=>8963, 'repair_point'=>100000)
	, array('member_idx'=>2984, 'repair_point'=>1760000)
	, array('member_idx'=>7348, 'repair_point'=>1000000)
	, array('member_idx'=>8997, 'repair_point'=>1000000)
	, array('member_idx'=>15163, 'repair_point'=>10000)
	, array('member_idx'=>14715, 'repair_point'=>10000)
	, array('member_idx'=>19631, 'repair_point'=>2500000)
	, array('member_idx'=>10571, 'repair_point'=>1000000)
	, array('member_idx'=>23349, 'repair_point'=>960000)
	, array('member_idx'=>4955, 'repair_point'=>1000000)
	, array('member_idx'=>11278, 'repair_point'=>100000)
	, array('member_idx'=>27624, 'repair_point'=>1000000)
	, array('member_idx'=>27982, 'repair_point'=>570000)
	, array('member_idx'=>12478, 'repair_point'=>100000)
	, array('member_idx'=>18982, 'repair_point'=>100000)
	, array('member_idx'=>8415, 'repair_point'=>500000)
	, array('member_idx'=>19941, 'repair_point'=>10000)
	, array('member_idx'=>27500, 'repair_point'=>100000)
	, array('member_idx'=>21279, 'repair_point'=>200000)
	, array('member_idx'=>11020, 'repair_point'=>10000)
	, array('member_idx'=>3461, 'repair_point'=>200000)
	, array('member_idx'=>22606, 'repair_point'=>10000)
	, array('member_idx'=>7877, 'repair_point'=>100000)
	, array('member_idx'=>23465, 'repair_point'=>10000)
	, array('member_idx'=>20230, 'repair_point'=>200000)
	, array('member_idx'=>24739, 'repair_point'=>10000)
	, array('member_idx'=>15010, 'repair_point'=>100000)
	, array('member_idx'=>3399, 'repair_point'=>10000)
	, array('member_idx'=>20338, 'repair_point'=>20000)
	, array('member_idx'=>12671, 'repair_point'=>200000)
	, array('member_idx'=>14107, 'repair_point'=>10000)
	, array('member_idx'=>11707, 'repair_point'=>40000)
	, array('member_idx'=>5994, 'repair_point'=>200000)
	, array('member_idx'=>18804, 'repair_point'=>100000)
	, array('member_idx'=>3608, 'repair_point'=>10000)
	, array('member_idx'=>11387, 'repair_point'=>50000)
	, array('member_idx'=>5773, 'repair_point'=>10000)
	, array('member_idx'=>6480, 'repair_point'=>10000)
	, array('member_idx'=>7772, 'repair_point'=>10000)
	, array('member_idx'=>17485, 'repair_point'=>10000)
	, array('member_idx'=>18145, 'repair_point'=>200000)
	, array('member_idx'=>14182, 'repair_point'=>200000)
	, array('member_idx'=>14961, 'repair_point'=>30000)
	, array('member_idx'=>19406, 'repair_point'=>10000)
	, array('member_idx'=>7406, 'repair_point'=>10000)
	, array('member_idx'=>3537, 'repair_point'=>30000000)
	, array('member_idx'=>4597, 'repair_point'=>100000)
	, array('member_idx'=>23851, 'repair_point'=>30000)
	, array('member_idx'=>10089, 'repair_point'=>30000)
	, array('member_idx'=>22899, 'repair_point'=>300000)
	, array('member_idx'=>4200, 'repair_point'=>10000)
	, array('member_idx'=>8777, 'repair_point'=>200000)
	, array('member_idx'=>8651, 'repair_point'=>200000)
	, array('member_idx'=>26023, 'repair_point'=>900000)
	, array('member_idx'=>25743, 'repair_point'=>100000)
	, array('member_idx'=>5302, 'repair_point'=>10000)
	, array('member_idx'=>103, 'repair_point'=>10000)
	, array('member_idx'=>22243, 'repair_point'=>100000)
	, array('member_idx'=>10808, 'repair_point'=>200000)
	, array('member_idx'=>9075, 'repair_point'=>100000)
	, array('member_idx'=>5322, 'repair_point'=>200000)
	, array('member_idx'=>19258, 'repair_point'=>100000)
	, array('member_idx'=>21234, 'repair_point'=>20000)
	, array('member_idx'=>11055, 'repair_point'=>100000)
	, array('member_idx'=>7211, 'repair_point'=>10000)
	, array('member_idx'=>28027, 'repair_point'=>1000000)
	, array('member_idx'=>13838, 'repair_point'=>330000)
	, array('member_idx'=>6835, 'repair_point'=>10000)
	, array('member_idx'=>14680, 'repair_point'=>150000)
	, array('member_idx'=>7667, 'repair_point'=>20000)
	, array('member_idx'=>9875, 'repair_point'=>300000)
	, array('member_idx'=>4675, 'repair_point'=>500000)
	, array('member_idx'=>2670, 'repair_point'=>100000)
	, array('member_idx'=>19529, 'repair_point'=>500000)
	, array('member_idx'=>7495, 'repair_point'=>10000)
	, array('member_idx'=>12641, 'repair_point'=>10000)
	, array('member_idx'=>7670, 'repair_point'=>100000)
	, array('member_idx'=>13275, 'repair_point'=>10000)
	, array('member_idx'=>21731, 'repair_point'=>100000)
	, array('member_idx'=>5893, 'repair_point'=>100000)
	, array('member_idx'=>25273, 'repair_point'=>10000)
	, array('member_idx'=>15004, 'repair_point'=>40000)
	, array('member_idx'=>23650, 'repair_point'=>20000)
	, array('member_idx'=>11982, 'repair_point'=>990000)
	, array('member_idx'=>4866, 'repair_point'=>100000)
	, array('member_idx'=>17330, 'repair_point'=>50000)
	, array('member_idx'=>14633, 'repair_point'=>100000)
	, array('member_idx'=>10861, 'repair_point'=>10000)
	, array('member_idx'=>4950, 'repair_point'=>10000)
	, array('member_idx'=>5476, 'repair_point'=>200000)
	, array('member_idx'=>5340, 'repair_point'=>100000)
	, array('member_idx'=>12302, 'repair_point'=>100000)
	, array('member_idx'=>18304, 'repair_point'=>300000)
	, array('member_idx'=>4291, 'repair_point'=>500000)
	, array('member_idx'=>14787, 'repair_point'=>50000)
	, array('member_idx'=>14612, 'repair_point'=>150000)
	, array('member_idx'=>10368, 'repair_point'=>100000)
	, array('member_idx'=>6070, 'repair_point'=>10000)
	, array('member_idx'=>4665, 'repair_point'=>100000)
	, array('member_idx'=>5124, 'repair_point'=>80000)
	, array('member_idx'=>10482, 'repair_point'=>200000)
	, array('member_idx'=>6354, 'repair_point'=>100000)
	, array('member_idx'=>14650, 'repair_point'=>2000000)
	, array('member_idx'=>2896, 'repair_point'=>50000)
	, array('member_idx'=>19626, 'repair_point'=>1000000)
	, array('member_idx'=>4758, 'repair_point'=>30000)
	, array('member_idx'=>6175, 'repair_point'=>10000)
	, array('member_idx'=>24867, 'repair_point'=>10000)
	, array('member_idx'=>19692, 'repair_point'=>10000)
	, array('member_idx'=>6251, 'repair_point'=>10000)
	, array('member_idx'=>14511, 'repair_point'=>20000)
	, array('member_idx'=>10021, 'repair_point'=>100000)
	, array('member_idx'=>6099, 'repair_point'=>10000)
	, array('member_idx'=>8665, 'repair_point'=>100000)
	, array('member_idx'=>14092, 'repair_point'=>20000)
	, array('member_idx'=>5973, 'repair_point'=>10000)
	, array('member_idx'=>17864, 'repair_point'=>10000)
	, array('member_idx'=>5390, 'repair_point'=>40000)
	, array('member_idx'=>5970, 'repair_point'=>80000)
	, array('member_idx'=>14544, 'repair_point'=>50000)
	, array('member_idx'=>23237, 'repair_point'=>10000)
	, array('member_idx'=>9531, 'repair_point'=>250000)
	, array('member_idx'=>10486, 'repair_point'=>10000)
	, array('member_idx'=>7122, 'repair_point'=>500000)
	, array('member_idx'=>11009, 'repair_point'=>70000)
	, array('member_idx'=>10647, 'repair_point'=>100000)
	, array('member_idx'=>5815, 'repair_point'=>10000)
	, array('member_idx'=>23094, 'repair_point'=>100000)
	, array('member_idx'=>13937, 'repair_point'=>100000)
	, array('member_idx'=>18863, 'repair_point'=>10000)
	, array('member_idx'=>2829, 'repair_point'=>100000)
	, array('member_idx'=>17200, 'repair_point'=>10000)
	, array('member_idx'=>16165, 'repair_point'=>500000)
	, array('member_idx'=>9834, 'repair_point'=>10000)
	, array('member_idx'=>19620, 'repair_point'=>50000)
	, array('member_idx'=>13610, 'repair_point'=>1190000)
	, array('member_idx'=>6444, 'repair_point'=>20000)
	, array('member_idx'=>5455, 'repair_point'=>30000)
	, array('member_idx'=>11289, 'repair_point'=>50000)
	, array('member_idx'=>15351, 'repair_point'=>1100000)
	, array('member_idx'=>23578, 'repair_point'=>500000)
	, array('member_idx'=>8707, 'repair_point'=>20000)
	, array('member_idx'=>6492, 'repair_point'=>10000)
	, array('member_idx'=>19315, 'repair_point'=>40000)
	, array('member_idx'=>7430, 'repair_point'=>100000)
	, array('member_idx'=>10252, 'repair_point'=>400000)
	, array('member_idx'=>4872, 'repair_point'=>20000)
	, array('member_idx'=>11447, 'repair_point'=>500000)
	, array('member_idx'=>10715, 'repair_point'=>580000)
	, array('member_idx'=>9057, 'repair_point'=>10000)
	, array('member_idx'=>5490, 'repair_point'=>100000)
	, array('member_idx'=>14239, 'repair_point'=>800000)
	, array('member_idx'=>18226, 'repair_point'=>500000)
	, array('member_idx'=>4415, 'repair_point'=>100000)
	, array('member_idx'=>18809, 'repair_point'=>20000)
	, array('member_idx'=>2492, 'repair_point'=>500000)
	, array('member_idx'=>10777, 'repair_point'=>40000)
	, array('member_idx'=>16282, 'repair_point'=>20000)
	, array('member_idx'=>22859, 'repair_point'=>10000)
	, array('member_idx'=>7173, 'repair_point'=>500000)
	, array('member_idx'=>14163, 'repair_point'=>1000000)
	, array('member_idx'=>17400, 'repair_point'=>500000)
	, array('member_idx'=>9649, 'repair_point'=>30000)
	, array('member_idx'=>9595, 'repair_point'=>50000000)
	, array('member_idx'=>6458, 'repair_point'=>20000)
	, array('member_idx'=>7114, 'repair_point'=>200000)
	, array('member_idx'=>23307, 'repair_point'=>300000)
	, array('member_idx'=>19681, 'repair_point'=>100000)
	, array('member_idx'=>13468, 'repair_point'=>100000)
	, array('member_idx'=>8056, 'repair_point'=>100000)
	, array('member_idx'=>11008, 'repair_point'=>10000)
	, array('member_idx'=>19327, 'repair_point'=>1000000)
	, array('member_idx'=>24069, 'repair_point'=>100000)
	, array('member_idx'=>16698, 'repair_point'=>10000)
	, array('member_idx'=>5093, 'repair_point'=>100000)
	, array('member_idx'=>10868, 'repair_point'=>1000000)
	, array('member_idx'=>14486, 'repair_point'=>10000)
	, array('member_idx'=>12862, 'repair_point'=>1000000)
	, array('member_idx'=>25362, 'repair_point'=>1000000)
	, array('member_idx'=>25296, 'repair_point'=>1000000)
	, array('member_idx'=>7937, 'repair_point'=>1000000)
	, array('member_idx'=>19684, 'repair_point'=>110000)
	, array('member_idx'=>10351, 'repair_point'=>1000000)
	, array('member_idx'=>4831, 'repair_point'=>1000000)
	, array('member_idx'=>11572, 'repair_point'=>500000)
	, array('member_idx'=>5776, 'repair_point'=>2000000)
	, array('member_idx'=>19965, 'repair_point'=>5000000)
	, array('member_idx'=>19928, 'repair_point'=>100000)
	, array('member_idx'=>15480, 'repair_point'=>5000000)
	, array('member_idx'=>19831, 'repair_point'=>1000000)
	, array('member_idx'=>19828, 'repair_point'=>1000000)
	, array('member_idx'=>28465, 'repair_point'=>10000)

	,	array('member_idx'=>11374, 'repair_point'=>10000)
	, array('member_idx'=>14380, 'repair_point'=>100000)
	, array('member_idx'=>20978, 'repair_point'=>100000)
	,	array('member_idx'=>23362, 'repair_point'=>10000)			// 14:05분 현재 원금 차감시 -9703 되어 차감불가함.
	, array('member_idx'=>11472, 'repair_point'=>1000000)		//14:05분 현재 원금 차감시 --28042 되어 차감불가함.
	*/

);


$target_count = count($MEMPOINT);


//$PROC = array('action'=>'charge', 'str'=>'지급');
$PROC = array('action'=>'discharge', 'str'=>'차감');

$proc_count = 0;
$proc_amount = 0;

for( $i=0; $i < $target_count; $i++ ) {

	$MB = sql_fetch("SELECT mb_id FROM g5_member WHERE mb_no='".$MEMPOINT[$i]['member_idx']."' AND member_group='F' AND mb_level BETWEEN 1 AND 8");

	if($MB['mb_id']) {

		if($PROC['action']=='charge') {

			$amount = $MEMPOINT[$i]['repair_point'];

			$point_text = "예치금 지급: 보정지급";

			insert_point($MB['mb_id'], $amount, $point_text, "@charge", $MB['mb_id'], $MB['mb_id'].'-'.uniqid(''), 0);
			echo "insert_point({$MB['mb_id']}, {$amount}, '{$point_text}', '@charge', {$MB['mb_id']}, {$MB['mb_id']}.'-'.uniqid(''), 0);<br>\n";

			$proc_count += 1;
			$proc_amount += $MEMPOINT[$i]['repair_point'];

		}
		else if($PROC['action']=='discharge') {

			$amount  =  $MEMPOINT[$i]['repair_point'] * -1;
			insert_point($MB['mb_id'], $amount, "예치금 차감: 관리자에 의한 2813호 상품 원금 지급 취소", "@discharge", $MB['mb_id'], $MB['mb_id'].'-'.uniqid(''), 0);
			echo "insert_point({$MB['mb_id']}, {$amount}, '예치금 차감: 관리자에 의한 2813호 상품 원금 지급 취소', '@discharge', {$MB['mb_id']}, {$MB['mb_id']}.'-'.uniqid(''), 0);<br>\n";

			$proc_count += 1;
			$proc_amount += $MEMPOINT[$i]['repair_point'];

		}

	}

}



$message = number_format($proc_count)."명의 회원에게 예치금 ".number_format($proc_amount)."원이 " . $PROC['str'] . " 되었습니다.";
$RESULT_ARR = array("result" => "success", "message" => $message);
echo json_encode($RESULT_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

sql_close();
exit;

?>
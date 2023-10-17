<?
// 이벤트 지급기록 테이블 변경 -> 새 항목에 대한 값 입력작업


include_once("_common.php");

$action_key = date('YmdHi');

$PRDT = array(

	'0'=>array(
		'idx'=>'26',
		'give_date'=>'2017-05-08',
		'amount'=>'5000',
		'USER'=> array(
'hmh5233'=>array('bank_name'=>'우리은행', 'account_num'=>'1002490890211', 'bank_private_name'=>'한민형'),
'hjhj81'=>array('bank_name'=>'SC은행', 'account_num'=>'69720127653', 'bank_private_name'=>'김현주'),
'your4leaf'=>array('bank_name'=>'현대증권', 'account_num'=>'248938222', 'bank_private_name'=>'주현우'),
'rupang602'=>array('bank_name'=>'기업은행', 'account_num'=>'37807665501012', 'bank_private_name'=>'장재호'),
'tkdtnr80'=>array('bank_name'=>'국민은행', 'account_num'=>'202211771301', 'bank_private_name'=>'박상숙'),
'tnwls911021'=>array('bank_name'=>'농협은행', 'account_num'=>'3560487035813', 'bank_private_name'=>'김수진'),
'brooke97'=>array('bank_name'=>'산업은행', 'account_num'=>'02060020984706', 'bank_private_name'=>'윤보영'),
'halovej'=>array('bank_name'=>'국민은행', 'account_num'=>'65300104094895', 'bank_private_name'=>'윤은하'),
'godhot2240'=>array('bank_name'=>'국민은행', 'account_num'=>'95910201411963', 'bank_private_name'=>'강재원'),
'novasense'=>array('bank_name'=>'KEB하나은행', 'account_num'=>'620198966251', 'bank_private_name'=>'정성용'),
'mjma8474'=>array('bank_name'=>'농협은행', 'account_num'=>'3120108267611', 'bank_private_name'=>'마명진'),
'sjkim4383'=>array('bank_name'=>'농협은행', 'account_num'=>'3010075216571', 'bank_private_name'=>'김세진'),
'aswithin17'=>array('bank_name'=>'기업은행', 'account_num'=>'20801953202019', 'bank_private_name'=>'정숙희'),
'sangboom'=>array('bank_name'=>'농협은행', 'account_num'=>'3027666863891', 'bank_private_name'=>'김상범'),
'pcb1152'=>array('bank_name'=>'농협은행', 'account_num'=>'77802211884', 'bank_private_name'=>'박춘배'),
'sht1984'=>array('bank_name'=>'삼성증권', 'account_num'=>'707401154305', 'bank_private_name'=>'손현탁'),
'silky7942'=>array('bank_name'=>'국민은행', 'account_num'=>'61320104055807', 'bank_private_name'=>'최정현'),
'rock9879'=>array('bank_name'=>'우리은행', 'account_num'=>'40713479802001', 'bank_private_name'=>'권환길'),
'claramh'=>array('bank_name'=>'KEB하나은행', 'account_num'=>'45591008494407', 'bank_private_name'=>'김명희'),
'mystardust'=>array('bank_name'=>'SC은행', 'account_num'=>'20320240396', 'bank_private_name'=>'최윤희'),
'skc813'=>array('bank_name'=>'NH투자증권', 'account_num'=>'20101619578', 'bank_private_name'=>'신기철'),
'blueswoo'=>array('bank_name'=>'신한은행', 'account_num'=>'110007594226', 'bank_private_name'=>'김범수'),
'relish0423'=>array('bank_name'=>'기업은행', 'account_num'=>'55103152601014', 'bank_private_name'=>'최민지'),
'see1323'=>array('bank_name'=>'KEB하나은행', 'account_num'=>'74891055592607', 'bank_private_name'=>'왕가영'),
'ecopsy8'=>array('bank_name'=>'신한은행', 'account_num'=>'110300342734', 'bank_private_name'=>'이주헌')
		)
	)

);



$arr_no = '0';
//print_rr($PRDT[$arr_no], 'font-size:12px;');

$sql = "
	SELECT
		A.idx, A.member_idx, A.product_idx,
		B.mb_id
	FROM
		cf_event_product_invest A
	LEFT JOIN
		g5_member B
	ON
		A.member_idx=B.mb_no
	WHERE (1)
		AND A.product_idx='".$PRDT[$arr_no]['idx']."'
		AND A.invest_state='Y'
	ORDER BY
		idx";
$res = sql_query($sql);
$n = 1;

while($INVEST = sql_fetch_array($res)) {

	//print_rr($INVEST, 'font-size:12px;');

	$INPUT = array();

	$GIVE = sql_fetch("SELECT idx, date, invest_amount, invest_idx, member_idx, product_idx, bank_name, bank_private_name, account_num, banking_date FROM cf_event_product_give WHERE invest_idx='".$INVEST['idx']."'");

	if($GIVE['idx']=='') {

		$INPUT['date']              = $PRDT[$arr_no]['give_date'];
		$INPUT['invest_amount']     = $PRDT[$arr_no]['amount'];
		$INPUT['invest_idx']        = $INVEST['idx'];
		$INPUT['member_idx']        = $INVEST['member_idx'];
		$INPUT['product_idx']       = $INVEST['product_idx'];
		$INPUT['bank_name']         = $PRDT[$arr_no]['USER'][$INVEST['mb_id']]['bank_name'];
		$INPUT['bank_private_name'] = $PRDT[$arr_no]['USER'][$INVEST['mb_id']]['bank_private_name'];
		$INPUT['account_num']       = $PRDT[$arr_no]['USER'][$INVEST['mb_id']]['account_num'];
		$INPUT['banking_date']      = $PRDT[$arr_no]['give_date']." 12:00:00";


		$sqlx = "INSERT INTO cf_event_product_give (date, invest_amount, invest_idx, member_idx, product_idx, bank_name, bank_private_name, account_num, banking_date) " .
		        "VALUES ('".$INPUT['date']."', '".$INPUT['invest_amount']."', '".$INPUT['invest_idx']."', '".$INPUT['member_idx']."', '".$INPUT['product_idx']."', '".$INPUT['bank_name']."', '".$INPUT['bank_private_name']."', '".$INPUT['account_num']."', '".$INPUT['banking_date']."')";

		echo "<div style='font-size:9pt;color:red'>" . $n . ": " . $sqlx . "</div>\n";

	}
	else {

		$INPUT['member_idx']        = $INVEST['member_idx'];
		$INPUT['bank_name']         = $PRDT[$arr_no]['USER'][$INVEST['mb_id']]['bank_name'];
		$INPUT['bank_private_name'] = $PRDT[$arr_no]['USER'][$INVEST['mb_id']]['bank_private_name'];
		$INPUT['account_num']       = $PRDT[$arr_no]['USER'][$INVEST['mb_id']]['account_num'];
		$INPUT['banking_date']      = $PRDT[$arr_no]['give_date']." 12:00:00";

		$sql2 = "";
		$sql2.= (!$GIVE['member_idx'] && $INPUT['member_idx']) ? "member_idx='".$INPUT['member_idx']."'" : "";
		$sql2.= ($GIVE['bank_name']=='' && $INPUT['bank_name']) ? ", bank_name='".$INPUT['bank_name']."'" : "";
		$sql2.= ($GIVE['bank_private_name']=='' && $INPUT['bank_private_name']) ? ", bank_private_name='".$INPUT['bank_private_name']."'" : "";
		$sql2.= ($GIVE['account_num']=='' && $INPUT['account_num']) ? ", account_num='".$INPUT['account_num']."'" : "";
		$sql2.= ($GIVE['banking_date']=='' && $INPUT['banking_date']) ? ", banking_date='".$INPUT['banking_date']."'" : "";

		if($sql2) {
			$sqlx = "UPDATE cf_event_product_give SET " . $sql2 . " WHERE idx='".$GIVE['idx']."'";
		}

		echo "<div style='font-size:9pt;color:#000'>" . $n . ": " . $sqlx . "</div>\n";

	}

	if($action==$action_key && $sqlx) {
		sql_query($sqlx);
	}

	unset($sql2); unset($sqlx); unset($INPUT);

	$n++;

}

?>
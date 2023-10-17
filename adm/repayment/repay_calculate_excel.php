<?

## /adm/product_calculate_excel.php 를 본파일로 대체

set_time_limit(0);

$sub_menu = '700200';
include_once('./_common.php');





auth_check($auth[$sub_menu], "w");
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

include_once(G5_LIB_PATH.'/repay_calculation.php');		// 월별 정산내역 추출함수 호출


$prd_idx              = trim($_REQUEST['idx']);											//상품번호기준
$mb_id                = trim($_REQUEST['mb_id']);										// 특정 투자자만 조회 할 경우
//$invest_period      = trim($_REQUEST['invest_period']);						// (시뮬레이션용) 투자개월수
//$loan_start_date    = trim($_REQUEST['loan_start_date']);					// (시뮬레이션용) 투자시작일
//$loan_end_date      = trim($_REQUEST['loan_end_date']);						// (시뮬레이션용) 투자만기일
//$invest_usefee      = trim($_REQUEST['invest_usefee']);						// (시뮬레이션용) 플랫폼이용료율
//$invest_usefee_type = trim($_REQUEST['invest_usefee_type']);			// (시뮬레이션용) 플랫폼이용료 징수방식
$turn                 = trim($_REQUEST['turn']);


$INV_ARR   = repayCalculation($prd_idx, $mb_id);
$INI       = $INV_ARR['INI'];
$PRDT      = $INV_ARR['PRDT'];
$LOANER    = $INV_ARR['LOANER'];
$INVEST    = $INV_ARR['INVEST'];
$MTOTAL_INVEST_SUM = $INV_ARR['MTOTAL_INVEST_SUM'];
$REPAY     = $INV_ARR['REPAY'];
$REPAY_SUM = $INV_ARR['REPAY_SUM'];


$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;


$now_date  = date('Y-m-d');
$file_name = "헬로펀딩_".$now_date."_정산(".$PRDT['title']." ".$turn."회차).xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP4 Generated Data" );


$i = $turn-1;
$list_count = count($REPAY[$i]['LIST']);

?>

<table border="1" style="border-collapse:collapse">
	<tr>
		<td align="center" rowspan="2" bgcolor="#F6F6F6">NO</td>
		<th align="center" colspan="<?=($_SESSION['ss_accounting_admin'])?9:8;?>" bgcolor="#F6F6F6">투자자</th>
		<th align="center" colspan="2" bgcolor="#F6F6F6">예상이자</th>
		<th align="center" colspan="3" bgcolor="#F6F6F6">누적</th>
		<th align="center" colspan="3" bgcolor="#F6F6F6">당월</th>
		<th align="center" rowspan="2" bgcolor="#F6F6F6">지급여부</th>
		<th align="center" rowspan="2" bgcolor="#F6F6F6">세금계산서</th>
	</tr>
	<tr>
		<td align="center" bgcolor="#F6F6F6">구분</td>
		<td align="center" bgcolor="#F6F6F6">ID</td>
		<td align="center" bgcolor="#F6F6F6">성명/상호명</td>
		<? if($_SESSION['ss_accounting_admin']) { ?><td align="center" bgcolor="#F6F6F6">주민.사업자번호</td><? } ?>
		<td align="center" bgcolor="#F6F6F6">투자금</td>
		<td align="center" bgcolor="#F6F6F6">수취방식</td>
		<td align="center" bgcolor="#F6F6F6">지급은행</td>
		<td align="center" bgcolor="#F6F6F6">계좌번호</td>
		<td align="center" bgcolor="#F6F6F6">예금주</td>

		<td align="center" bgcolor="#F6F6F6">전체</td>
		<td align="center" bgcolor="#F6F6F6">당월</td>

		<td align="center" bgcolor="#F6F6F6">플랫폼이용료</td>
		<td align="center" bgcolor="#F6F6F6">원천징수</td>
		<td align="center" bgcolor="#F6F6F6">실수령이자</td>

		<td align="center" bgcolor="#F6F6F6">플랫폼이용료</td>
		<td align="center" bgcolor="#F6F6F6">원천징수</td>
		<th align="center" bgcolor="#F6F6F6">실수령이자</th>
	</tr>
<?
for($j=0,$num=$list_count; $j<$list_count; $j++,$num--) {

	$member_id = $REPAY[$i]['LIST'][$j]['mb_id'];

	$member_type = "";
	$member_type.= ($REPAY[$i]['LIST'][$j]['member_type']=='2') ? "기업" : "개인";
	$member_type.= ($REPAY[$i]['LIST'][$j]['is_creditor']=='Y') ? "-대부" : "";

	if($REPAY[$i]['LIST'][$j]['receive_method']) {
		$receive_method = ($REPAY[$i]['LIST'][$j]['receive_method']=='1') ? '환급계좌' : '<font color="#FF0000">예치금(가상계좌)</font>';
	}
	else {
		$receive_method = "미지정";
	}

	// 세금계산서 정보
	if($REPAY[$i]['LIST'][$j]['member_type']=='2' || $REPAY[$i]['LIST'][$j]['is_owner_operator']=='1') {
		$TAX_INVOICE[$i]['C'] = $TAX_INVOICE[$i]['C'] + 1;
		if($REPAY[$i]['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['C_SUCC'] = $TAX_INVOICE[$i]['C_SUCC'] + 1; }
	}
	else {
		$TAX_INVOICE[$i]['P'] = $TAX_INVOICE[$i]['P'] + 1;
		if($REPAY[$i]['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['P_SUCC'] = $TAX_INVOICE[$i]['P_SUCC'] + 1; }
	}

	if($REPAY[$i]['LIST'][$j]['mgtKey']) {
		if(preg_match('/P_/i', $REPAY[$i]['LIST'][$j]['mgtKey']))       $taxinvoicetype = '현금영수증';
		else if(preg_match('/C_/i', $REPAY[$i]['LIST'][$j]['mgtKey']))  $taxinvoicetype = '세금계산서';
		else $taxinvoicetype = '직접확인';

		$taxinvoice_link = '<a href="'.G5_URL.'/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL.php?mgtKey='.$REPAY[$i]['LIST'][$j]['mgtKey'].'" target="_blank" style="font-size:10pt">'.$taxinvoicetype.'</a>';
	}
	else {
		$taxinvoice_link = '';
	}

	$bgcolor = ($REPAY[$i]['LIST'][$j]['member_type']=='2') ? '#FFF2CC' : '';
	$bgcolor = ($REPAY[$i]['LIST'][$j]['is_creditor']=='Y') ? '#FCE4D6' : $bgcolor;

?>
	<tr>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$num?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$member_type?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$REPAY[$i]['LIST'][$j]['mb_id']?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$REPAY[$i]['LIST'][$j]['mb_name']?></td>
		<? if($_SESSION['ss_accounting_admin']) { ?><td bgcolor="<?=$bgcolor?>" align="center" style="mso-number-format:'@';"><?=$REPAY[$i]['LIST'][$j]['jumin']?></td><? } ?>
		<td bgcolor="<?=$bgcolor?>" align="right"><?=number_format($REPAY[$i]['LIST'][$j]['amount'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$receive_method?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$REPAY[$i]['LIST'][$j]['bank']?></td>
		<td bgcolor="<?=$bgcolor?>" align="center" style="mso-number-format:'@';"><?=preg_replace('/-/', '', $REPAY[$i]['LIST'][$j]['account_num'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$REPAY[$i]['LIST'][$j]['bank_private_name']?></td>

		<td bgcolor="<?=$bgcolor?>" align="right" style="color:#aaa"><?=number_format($REPAY_SUM[$member_id]['invest_interest'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="right"><?=number_format($REPAY[$i]['LIST'][$j]['invest_interest'])?></td>

		<td bgcolor="<?=$bgcolor?>" align="right" style="color:#aaa"><?=number_format($REPAY[$i]['MEMBER_NUJUK'][$member_id]['invest_usefee'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="right" style="color:#aaa"><?=number_format($REPAY[$i]['MEMBER_NUJUK'][$member_id]['TAX']['sum'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="right" style="color:#aaa"><?=number_format($REPAY[$i]['MEMBER_NUJUK'][$member_id]['interest'])?></td>

		<td bgcolor="<?=$bgcolor?>" align="right" style="color:brown"><?=number_format($REPAY[$i]['LIST'][$j]['invest_usefee'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="right" style="color:brown"><?=number_format($REPAY[$i]['LIST'][$j]['TAX']['sum'])?></td>
		<td bgcolor="<?=$bgcolor?>" align="right" style="color:#3366FF"><?=number_format($REPAY[$i]['LIST'][$j]['interest'])?></td>

		<td bgcolor="<?=$bgcolor?>" align="center"><?=($REPAY[$i]['LIST'][$j]['paied']=='Y') ? '지급' : '미지급'; ?></td>
		<td bgcolor="<?=$bgcolor?>" align="center"><?=$taxinvoice_link?></td>
	</tr>
<?
}
?>
	<tr style="color:blue;">
		<td bgcolor="#DCE6F1" align="center" colspan="<?=($_SESSION['ss_accounting_admin'])?5:4;?>"><?=$turn?>회차 합계</td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['SUM']['amount'])?></td>
		<td bgcolor="#DCE6F1" align="center"></td>
		<td bgcolor="#DCE6F1" align="center"></td>
		<td bgcolor="#DCE6F1" align="center"></td>
		<td bgcolor="#DCE6F1" align="center"></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY_SUM['invest_interest'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['SUM']['invest_interest'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['NUJUK_SUM']['invest_usefee'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['NUJUK_SUM']['TAX']['sum'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['NUJUK_SUM']['interest'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['SUM']['invest_usefee'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['SUM']['TAX']['sum'])?></td>
		<td bgcolor="#DCE6F1" align="right"><?=number_format($REPAY[$i]['SUM']['interest'])?></td>
		<td bgcolor="#DCE6F1" align="center"></td>
		<td bgcolor="#DCE6F1" align="center"></td>
	</tr>
</table>
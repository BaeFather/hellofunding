<?
###############################################################################
## /adm/product_calculate.php 를 본파일로 대체
##
###############################################################################

//if($_REQUEST['idx']=='3023') { require_once("repay_calculate-".trim($_REQUEST['idx']).".php"); exit; }		// 상품별 특수 처리시 본 파일을 수정하는것을 지양하고 다음과 같이 생성하여 사용할것
//if( in_array($_REQUEST['idx'], array('3187','3194','3201','3215','3223','3224')) ) { require_once("repay_calculate-".trim($_REQUEST['idx']).".php"); exit; }

/*
3023                      ::: 정상이자 지급:2020-08-05 / 원금,연체이자 지급:2020-08-10
3187,3194,3201            ::: 정상이자 지급:2020-09-07 / 원금,연체이자 지급:2020-09-10
3215,3223,3224            ::: 정상이자 지급:2020-09-07 / 원금,연체이자 지급:2020-09-15
3315,3324,3334,3341,3359,3382  ::: 정상이자 지급:2020-10-05
3391,3422
*/

set_time_limit(0);
ini_set('memory_limit','1024M');

include_once('./_common.php');
if($_REQUEST['mode']=='test') {
	include_once(G5_LIB_PATH.'/repay_calculation_new_test.php');
	echo "테스트 라이브러리";
}
else {
	include_once(G5_LIB_PATH.'/repay_calculation_new.php');
}

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

$sub_menu = '700100';
$g5['title'] = $menu['menu700'][1][1] . " > 정산상세";

/*
if( in_array(trim($_REQUEST['idx']), $CONF['OVDPRDT']) ) {
	require_once("repay_calculate-dutyfreeshop.php");
	exit;
}
*/

$is_control_user = ( in_array($_SESSION['ss_mb_id'], $CONF['OPERATOR']) ) ? true : false;

$prd_idx = trim($_REQUEST['idx']);											// 상품번호기준
$mb_id   = trim($_REQUEST['mb_id']);										// 특정 투자자만 조회 할 경우


$INV_ARR = repayCalculationNew($prd_idx, $mb_id);

$INI       = $INV_ARR['INI'];
$PRDT      = $INV_ARR['PRDT'];
$LOANER    = $INV_ARR['LOANER'];
$INVEST    = $INV_ARR['INVEST'];
$REPAY     = $INV_ARR['REPAY'];
$REPAY_SUM = $INV_ARR['REPAY_SUM'];
$PAIED_SUM = $INV_ARR['PAIED_SUM'];

unset($INV_ARR);

// 원리금상환 참조계좌정보
$TMP = sql_fetch("SELECT ref_prdt_idx, ref_prdt_repay_acct_no FROM cf_product WHERE idx = '".$prd_idx."'");
$PRDT['ref_prdt_idx'] = $TMP['ref_prdt_idx'];
$PRDT['ref_prdt_repay_acct_no'] = $TMP['ref_prdt_repay_acct_no'];

// 대출자 정보
$LOANER  = sql_fetch("SELECT mb_no, mb_id, member_type, mb_name, mb_co_name, mb_hp, mb_hp_ineb  FROM g5_member WHERE mb_no='".$PRDT['loan_mb_no']."'");


$PRINT['loaner_nm']      = ($LOANER['member_type']=='2') ? $LOANER['mb_co_name'] : $LOANER['mb_name'];
$PRINT['loaner_hp']      = ($LOANER['mb_hp_ineb']) ? DGuardDecrypt($LOANER['mb_hp_ineb']) : masterDecrypt($LOANER['mb_hp'], false);
$PRINT['invest_period']  = ($PRDT['state'] == '') ? $PRDT['invest_period'].'개월' : preg_replace('/-/', '.', $PRDT['loan_start_date']).' ~ '.preg_replace('/-/', '.', $PRDT['loan_end_date']);		// 대출기간
$PRINT['invest_month']   = ($PRDT['invest_days'] > 0) ? $PRDT['invest_days'].'일' : $PRDT['invest_period'].'개월';


// 상환회차 계산
if($PRDT['loan_start_date'] > '0000-00-00' && $PRDT['loan_end_date'] > '0000-00-00' ) {

	// 특별처리상품 플래그 (초기상품중 종료일이 5일 이전일때 이전회차와 최종상환회차를 동일회차로 처리한 상품 구분)
	$exceptionProduct = ($PRDT['idx'] < 162  && $PRDT['ib_trust']=='N' && substr($PRDT['loan_end_date'],-2) <= '05') ? 1 : 0;
	$shortTermProduct = ($PRDT['invest_days']>0) ? 1 : 0;

	$PRINT['total_invest_days'] = repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']);		// 상환대상일수
	$PRINT['total_repay_turn']  = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct);		// 상환차수

}

$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;

$date  = G5_TIME_YMDHIS;
$state = '';
if($PRDT['state']) {
	if($PRDT['state']=='1') { $state = '이자상환중'; $state_code = '2'; }
	if($PRDT['state']=='2') { $state = '상품마감'; }
	if($PRDT['state']=='4') { $state = '부실'; }
	if($PRDT['state']=='5') { $state = '중도상환'; $state_code = '2'; }
	if($PRDT['state']=='6') { $state = '대출취소(기표전)'; }
	if($PRDT['state']=='7') { $state = '대출취소(기표후)'; }
	if($PRDT['state']=='8') { $state = '연체'; }
	if($PRDT['state']=='9') { $state = '부도(상환불가)'; }
}
else {
	if($PRDT['open_datetime'] > $date) { $state = '투자대기중'; }
	if($PRDT['start_datetime'] < $date && $PRDT['end_datetime'] > $date && $PRDT['invest_end_date'] == '') { $state = '투자모집중'; }
	if($PRDT['end_datetime'] < $date && $PRDT['invest_end_date'] == '') { $state = '투자금 모집실패'; $state_code = '3'; }
	if($PRDT['invest_end_date'] != '' && $PRDT['state'] == '') { $state = '대기중'; $state_code = '1'; }
}

$PRINT['last_paid_turn'] = sql_fetch("SELECT IFNULL(MAX(turn),0) AS max_turn FROM cf_product_give WHERE product_idx='".$prd_idx."' AND is_overdue='N' AND banking_date IN NOT NULL")['max_turn'];

//대출정보 - 누적납입
$ROW = sql_fetch("SELECT SUM(invest_amount) AS sum_invest_amount FROM cf_product_give WHERE product_idx='$prd_idx' AND banking_date IN NOT NULL");
$LOAN['plus_loan_interest'] = $ROW['sum_invest_amount'];
unset($ROW);

//대출정보 - 당월납입이자
$date_s = date('Y-m') . '-01';
$date_e = date('Y-m') . '-' . date('t', strtotime($date_s));

$ROW2 = sql_fetch("SELECT IFNULL(SUM(invest_amount),0) AS sum_invest_amount FROM cf_product_give WHERE product_idx='$prd_idx' AND `date` BETWEEN '$date_s' AND '$date_e' AND banking_date IN NOT NULL");
$LOAN['month_loan_interest'] = $ROW2['sum_invest_amount'];
unset($ROW2);

if($ib_trust && $PRDT['invest_end_date']) {

	// 투자자수와 신한정상등록투자자수 비교
	$ROW3 = sql_fetch("SELECT COUNT(idx) AS cnt_idx FROM cf_product_invest WHERE product_idx='$prd_idx' AND invest_state='Y' AND ib_regist='1'");

	if($ROW3['cnt_idx'] > 0) {
		if($PRDT['invest_count']==$ROW3['cnt_idx']) {
			$ib_investor_regist_button = '<button type="button" class="btn btn-gray">등록완료</button>';
		}
		else {
			$none_regist_count = $PRDT['invest_count'] - $ROW3['cnt_idx'];
			$ib_investor_regist_button = '<button type="button" id="ib_investor_reg_btn" class="btn btn-success">추가등록실행('.$none_regist_count.'건)</button>';
		}
	}
	else {
		$ib_investor_regist_button = '<button type="button" id="ib_investor_reg_btn" class="btn btn-success">등록실행</button>';
	}
	unset($ROW3);

	//대출정보 - 대출금 지급 처리 (펌뱅킹 대출금 입금 통지내역 정의 테이블 조회)
	$ROW4 = sql_fetch("SELECT SUM(DCA_IP_AMT) AS SUM_DCA_IP_AMT FROM IB_FB_P2P_DC_IP WHERE DC_NB='$prd_idx' AND EXEC_YN='Y' AND ERR_CD='00000000'");
	if($ROW4['SUM_DCA_IP_AMT']) {
		$dc_ip_result_button = ($ROW4['SUM_DCA_IP_AMT']==$PRDT['recruit_amount']) ? '<button type="button" class="btn btn-gray">지급완료</button>' : '<button type="button" class="btn btn-danger">금액오류</button>';
	}
	unset($ROW4);

}


$bill_table = getBillTable($PRDT['idx']);


if($PRDT['loan_start_date']) {
	$BROW = sql_fetch("SELECT COUNT(product_idx) AS cnt FROM $bill_table WHERE product_idx='".$PRDT['idx']."' AND is_overdue='N'");
	$bill_count = $BROW['cnt'];
	unset($BROW);
}


$invest_count = count($INVEST);

//투자시뮬레이션 버튼
$simulation_button = "<button type='button' class='btn btn-default' onClick=\"window.open('../invest_repay_simulation.php?idx={$prd_idx}','');\">투자시뮬레이션</button>";

// 정산내역산정(재산정) 버튼
$proc_title = ($bill_count) ? '재산정' : '산정';
if($PRDT['invest_end_date']) {
	if( in_array($PRDT['state'], array('1','8','9')) ) {
		if(@shell_exec("ps -ef | grep -v grep | grep 'make_bill_exec.php {$prd_idx}' | wc -l") > 0) {
			$makebill_button = "<button id='make_bill_button' type='button' class='btn btn-gray'>정산내역 {$proc_title}</button>";
		}
		else {
			$makebill_button = "<button id='make_bill_button' type='button' class='btn btn-danger'>정산내역 {$proc_title}</button>";
		}
	}
	else {
		$makebill_button = "<button type='button' class='btn btn-gray' onClick=\"alert('종료된 상품은 정산내역을 재산정 할 수 없습니다.');\">정산내역 {$proc_title}</button>";
	}
}


// 대출취소 버튼 설정
if($PRDT['state']=='') {
	if( $is_control_user ) {
		$loan_cancel_button = '<button type="button" id="loan_cancel_btn" onClick="changeState(\'6\');" class="btn btn-danger">대출취소(기표전)</button>';
	}
	else {
		$loan_cancel_button = '<button type="button" class="btn btn-gray">대출취소</button>';
	}
}
else if($PRDT['state']=='1') {
	if( $is_control_user ) {
		if( (time()-strtotime($PRDT['loan_start_date'])) > (86400*3) ) {
			$loan_cancel_button = '<button type="button" class="btn btn-gray">대출취소</button>';
		}
		else {
			$loan_cancel_button = '<button type="button" id="loan_cancel_btn" onClick="changeState(\'7\');" class="btn btn-danger">대출취소(기표후)</button>';
		}
	}
	else {
		$loan_cancel_button = '<button type="button" class="btn btn-gray">대출취소</button>';
	}
}
else if( in_array($PRDT['state'], array('6','7')) ) {
	$title_add = ($PRDT['state']=='6') ? '기표전' : '기표후';
	$loan_cancel_button = '<button type="button" class="btn btn-gray">대출취소('.$title_add.')</button>';
}
else {
	$loan_cancel_button = '<button type="button" onClick="alert(\'대출실행처리 완료되었거나, 완료처리 이력이 있는 대출건은 취소가 불가 합니다.\');" class="btn btn-gray">대출취소</button>';
}

// 대출정상종료 버튼 설정
if( in_array($PRDT['state'],array('1','8')) ) {
	if($PRDT['loan_end_date'] >= $PRDT['loan_end_date_orig']) {
		if(G5_TIME_YMD < $PRDT['loan_end_date_orig']) {
			$loan_finish_button = '<button type="button" onClick="alert(\'대출실행시 설정된 대출 만료일에만 정상종료 가능합니다.\');" class="btn btn-default">대출정상종료</button>';
		}
		else if(G5_TIME_YMD >= $PRDT['loan_end_date']) {
			if($is_control_user) {
				$loan_finish_button = '<button type="button" id="principal_repay_btn" onClick="changeState(\'2\');" class="btn btn-danger">대출정상종료</button>';
			}
			else {
				$loan_finish_button = '<button type="button" onClick="alert(\'권한없음!!\')" class="btn btn-gray">대출정상종료</button>';
			}
		}
	}
}
else if($PRDT['state']=='2') {
	$loan_finish_button = '<button type="button" class="btn btn-gray">대출정상종료</button>';
}

// 중도상환 버튼 설정
if($PRDT['state']=='5') {
	$early_repay_button = '<button type="button" class="btn btn-gray">중도상환</button>';
}
else if($PRDT['state']=='1') {

	if($PRDT['loan_end_date'] < $PRDT['loan_end_date_orig']) {

		// 최종회차 상환처리 확인
		$succsql = "SELECT idx, invest_give_state, invest_principal_give FROM cf_product_success WHERE 1 AND product_idx='".$PRDT['idx']."' AND turn='".$INI['repay_turn']."' AND turn_sno='0'";
		$SUCC_R = sql_fetch($succsql);

		if($SUCC_R['invest_give_state']=='Y' && $SUCC_R['invest_principal_give']=='Y') {
			$early_repay_button = '<button type="button" id="early_repay_btn" onClick="changeState(\'5\');" class="btn btn-danger" style="margin:0;width:100%;">중도상환</button>';
		}
		else {
			$early_repay_button = '<button type="button" onClick="alert(\'최종 회차의 이자 또는 원금 지급 기록 없음.\');" class="btn btn-gray" style="margin:0;width:100%;">중도상환</button>';
		}

	}

	$date_change_button =	'<button type="button" id="early_repay_date_reg_btn" class="btn btn-warning" style="margin:0;width:100%;">일자변경</button>';

}

// 부실 처리 버튼 설정
if($PRDT['state']=='8') {
	if($is_control_user) {
		$loan_defunc_button = '<button type="button" id="bad_loan_btn" onClick="changeState(\'4\');" class="btn btn-danger">부실</button>';
	}
	else {
		$loan_defunc_button = '<button type="button" class="btn btn-gray">부실</button>';
	}
}
else {
	$loan_defunc_button = '<button type="button" class="btn btn-gray">부실</button>';
}


// 상환용가상계좌폐쇄 버튼 설정
$loaner_vacct_drop_button = '';
$vacct_drop_msg = '';
if( $ib_trust && $PRDT['repay_acct_no'] ) {
	$ING_PRDT = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product WHERE state IN('1','8','9') AND repay_acct_no='".$PRDT['repay_acct_no']."'");
	if(!$ING_PRDT['cnt']) {

		$DATA1 = sql_fetch("SELECT COUNT(*) AS cnt FROM KSNET_VR_ACCOUNT WHERE VR_ACCT_NO = '".$PRDT['repay_acct_no']."' AND USE_FLAG = 'Y'");
		$DATA2 = sql_fetch("SELECT COUNT(FB_SEQ) AS cnt FROM IB_vact_hellocrowd WHERE acct_no = '".$PRDT['repay_acct_no']."' AND acct_st = '1'");
		if($DATA1['cnt'] || $DATA2['cnt']) {
			$vacct_drop_msg.= "처리 후 당 계좌로의 입금이 되지 않습니다.\\n상환용 가상계좌를 해제 하시겠습니까?";
			$loaner_vacct_drop_button = '<button type="button" onClick="if(confirm(\''.$vacct_drop_msg.'\')){ loanerVacctDrop(\''.$PRDT['idx'].'\'); }" class="btn btn-danger">상환계좌해제</button>';
		}
		else {
			$loaner_vacct_drop_button = '<button type="button" class="btn btn-gray">상환계좌해제</button>';
		}
	}
}


$page_reload_msg = "페이지를 다시 호출 합니다.";


include_once(G5_ADMIN_PATH.'/admin.head.php');

?>

<style>
.table th.border_r { border-right:1px solid #999; }
.table td.border_r { border-right:1px solid #999; }
input::placeholder { text-align:center; }

ul.statusbar > li { padding:4px 0; }

stt {font-size:11px;color:#aaa;}
</style>

<div class="row" style="width:99.9%; min-width:1500px;">
	<div class="col-lg-12">
		<form id="form1" name='form1' class="form-horizontal">
			<input type="hidden" name="idx"    value="<?=$PRDT['idx']?>">
			<input type="hidden" name="state"  value="<?=$state_code?>">
		<div class="panel-body">
			<div class="dataTable_wrapper">
				<h3 style="padding-bottom:10px;">상품 정보 : <span style="color:royalblue"><?=$PRDT['title']?></span>

					<span style="float:right; display:inline-block;">
						<button type="button" onClick="location.href='/adm/product/product_form.php?idx=<?=$prd_idx?>';" class="btn btn-default">상품상세정보</button>
						<button type="button" onClick="window.open('/adm/product_calculate_pop.php?idx=<?=$prd_idx?>','product_calculate_pop','width=600,height=550');" class="btn btn-default">대출정보</button>
						<?=$simulation_button?>
						<?=$makebill_button?>
						<a href="/adm/product_calculate.php?idx=<?=$prd_idx?>" target="_blank" class="btn btn-success">(구)정산내역</a>
						<?=$loaner_vacct_drop_button?>
					</span>
					<script>
					$('#make_bill_button').click(function() {
						if( confirm('정산내역을 <?=($bill_count)?'재생성':'생성'?> 하시겠습니까?\n이미 등록된 정산내역은 삭제 됩니다.') ) {
							$.ajax({
								url:'/adm/repayment/make_bill.php',
								type:'post',
								dataType:'json',
								data:{ prd_idx:'<?=$prd_idx?>' },
								success: function(data) {
									if(data.result=='SUCCESS') { alert(data.message); /* window.location.replace('<?=$_SERVER['REQUEST_URI']?>');*/ }
									else if(data.result=='PRDT_NULL') { alert(data.message); }
									else if(data.result=='CHECK_SDATE') { alert(data.message); }
									else if(data.result=='CHECK_EDATE') { alert(data.message); }
									else if(data.result=='CHECK_DATE_BALANCE') { alert(data.message); }
									else if(data.result=='CREATE_TABLE_ERROR') { alert(data.message); }
									else {
										if(data.message) {
											alert(data.message);
										}
										else {
											alert('등록시 이상이 발생. 관리자에게 문의 바랍니다.');
										}
									}
								},
								error: function () { alert("make_bill.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
							});
						}
					});
					</script>
				</h3>
				<table class="table table-bordered">
					<colgroup>
						<col width="%">
						<col width="%">
						<col width="%">
						<col width="%">
						<col width="%">
						<col width="%">
						<col width="%">
						<col width="%">
					</colgroup>
					<thead>
						<tr style="background:#F9F9EF">
							<th class="text-center">총대출금액</th>
							<th class="text-center">대출이자</th>
							<th class="text-center">대출기간</th>
							<th class="text-center">이자계산일수</th>
							<th class="text-center">최종종료처리일</th>
							<th class="text-center">지급회차</th>
							<th class="text-center">대출자 성명</th>
							<th class="text-center">대출자 연락처</th>
						</tr>
					</thead>
					<tbody>
						<tr align="center">
							<td><?=($PRDT['recruit_amount'] >= 10000) ? price_cutting($PRDT['recruit_amount']) : number_format($PRDT['recruit_amount'])?>원 (￦<?=number_format($PRDT['recruit_amount'])?>)</td>
							<td><span style="font-size:10px">(연)</span><?=sprintf('%.2f', $PRDT['loan_interest_rate'])?>%</td>
							<td><?=$PRINT['invest_period']?> (<?=$PRINT['invest_month']?>)</td>
							<td><?=($PRINT['total_invest_days'])?$PRINT['total_invest_days']."일":"";?></td>
							<td><?=(in_array($PRDT['state'], array('2','5','9')))?$PRDT['down_date']:"";?></td>
							<td><?=$PRINT['last_paid_turn']?> / <?=$PRINT['total_repay_turn']?></td>
							<td><a href="/adm/member/member_list.php?member_group=L&key_search=A.mb_id&keyword=<?=$LOANER['mb_id']?>"><?=$PRINT['loaner_nm']?></a></td>
							<td><?=$PRINT['loaner_hp']?></td>
						</tr>
					</tbody>
				</table>

				<h3>실행.상환 설정 <? if($ib_trust) { ?><span style="margin-left:2px;padding:2px 6px;font-size:12px;border-radius:10px;color:#fff;background:blue">예치금신탁</span><? } ?></h3>
				<table class="table table-bordered">
					<colgroup>
						<col style="width:14.2%">
						<col style="width:14.2%">
						<col style="width:14.2%">
						<col style="width:14.2%">
						<col style="width:14.2%">
						<col style="width:14.2%">
						<col style="width:%">
						<!--<col style="width:12.5%">-->
					</colgroup>
					<thead>
						<tr style="background:#F9F9EF">
							<th class="text-center">대출취소</th>
							<th class="text-center">투자자등록(신한)</th>
							<th class="text-center">대출실행</th>
							<th class="text-center">대출금지급처리</th>
							<th class="text-center">원리금 상환.지급 완료</th>
							<th class="text-center">중도상환</th>
							<th class="text-center">부실</th>
							<!--<th class="text-center">투자금반환</th>-->
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="padding:4px;text-align:center;" alt="대출취소"><?=$loan_cancel_button?></td>
							<td style="padding:4px;text-align:center;" alt="투자자등록(신한)"><?=$ib_investor_regist_button?></td>
							<td style="padding:4px;text-align:center;" alt="대출실행">

								<? if( $PRDT['state'] >= 1) { ?>
								<button type="button" class="btn btn-gray">실행완료</button>
								<? } else if( $PRDT['state']=='' && ($PRDT['invest_end_date'] && $PRDT['recruit_amount']==$PRDT['invest_principal']) ) { ?>
								<ul style="list-style:none;margin:0;padding:0;display:inline-block">
									<li style="float:left;"><input type="text" name="date" value="<?=G5_TIME_YMD?>" placeholder="일자선택" class="form-control datepicker" style="text-align:center;width:100px" required readonly></li>
									<li style="float:left;margin-left:4px"><button type="button" id="loan_start_btn" onClick="changeState('1');" class="btn btn-success">대출실행</button></li>
								</ul>
								<? } ?>

							</td>
							<td style="padding:4px;text-align:center;" alt="대출지급처리"><?=$dc_ip_result_button?></td>
							<td style="padding:4px;text-align:center;" alt="대출정상종료"><?=$loan_finish_button?></td>
							<td style="padding:4px;text-align:center;" alt="중도상환">

								<? if($PRDT['state']=='5') { ?>
								<?=$early_repay_button?>
								<? } else if($PRDT['state']=='1') { ?>
								<ul style="list-style:none;margin:0;padding:0;display:inline-block;">
									<li style="float:left;width:60%;margin:0 0 4px;">
										<input type="text" id="loan_end_date" name="loan_end_date" value="<?=$PRDT['loan_end_date']?>" placeholder="일자선택" class="form-control datepicker" style="text-align:center;" required readonly>
									</li>
									<li style="float:right;width:39%;">
										<?=$date_change_button?>
									</li>
									<li style="width:100%;">
										<?=$early_repay_button?>
									</li>
								</ul>
								<? } ?>

							</td>
							<td style="padding:4px;text-align:center;" alt="부실"><?=$loan_defunc_button?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		</form>

		<script>
		// 투자자 등록
		$('#ib_investor_reg_btn').click(function() {
			if( confirm('「신한은행 제3자 예치금 관리시스템」으로 투자자 등록 전문을 발송 하시겠습니까?') ) {
				$('#ajax_return_txt_zone').css('display','block');
				$.ajax({
					url: "repay_proc.php",
					type: "POST",
					data: {
						action:'ib_investor_regist',
						idx:'<?=$prd_idx?>'
					},
					success:function(data) {
						$('#ajax_return_txt').val(data);
					},
					beforeSend: function() { loading('on'); },
					complete: function() { loading('off'); },
					error: function () { alert("repay_proc.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
				});
			}
		});

		// 중도상환 처리를 위한 대출종료일자 변경처리
		$('#early_repay_date_reg_btn').click(function() {
			if($('#loan_end_date').val()=='') { alert('대출종료일(중도상환일)을 입력하십시요.');$('#loan_end_date').focus(); }
			else {
				if( confirm('대출종료일자를 변경하시겠습니까?\n\n본 대출건에 대한 정산내역이 자동 재산정 됩니다.') ) {
					$.ajax({
						url: "repay_proc.php",
						type: "POST",
						dataType: "json",
						data: {
							action: 'repay_date_change',
							idx: '<?=$prd_idx?>',
							loan_end_date: $('#loan_end_date').val()
						},
						success:function(data) {
							$('#ajax_return_txt').val(data.result);
							if(data.result=='SUCCESS') {
								alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload();
							}
							else {
								alert(data.message);
							}
						},
						beforeSend: function() { loading('on'); },
						complete: function() { loading('off'); },
						error: function () { alert("repay_proc.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
					});
				}
			}
		});

		/////////////////////////////
		// 상태값 변경
		/////////////////////////////
		changeState = function(change_state) {

			if(change_state=='') { return; }

			var f = document.form1;
			var exec = false;

			// 대출실행(이자상환중)
			if(change_state=='1') {
				if(f.date.value=='') {
					alert('대출실행일자를 입력하십시요.'); f.date.focus(); return
				}
				else {
					if( confirm('대출을 실행 하시겠습니까?<? if($ib_trust) { ?>\n\n금융기관 예치금 관리시스템 연계가 진행되므로 신중함을 요함!<? } ?>') ) {
						f.state.value = change_state;
						exec = true;
					}
				}
			}

			// 대출취소
			else if(change_state=='6' || change_state=='7') {
				if( confirm('대출취소 처리 하시겠습니까?\n본 상품에 대한 모든 투자금은 예치금으로 반환 처리 됩니다.<? if($ib_trust) { ?>\n\n실행 후에는 금융기관 예치금 관리시스템 재연계 불가능!<? } ?>') ) {
					f.state.value = change_state;
					exec = true;
				}
			}

			// 중도상환
			else if(change_state=='5') {
				if($('#loan_end_date').val()=='') {
					alert('중도상환 일자를 입력하십시요.');$('#loan_end_date').focus(); return
				}
				else {
					if( confirm('중도상환 처리 하시겠습니까?\n\n - 중도상환 처리는 은 모든 이자 및 원금 지급을 완료한 후 최종적으로 요청하여야 합니다. 원리금 지급 여부를 반드시 확인하십시요.<? if($ib_trust) { ?>\n\n - 실행 후에는 금융기관 예치금 관리시스템 재연계 불가능!<? } ?>') ) {
						f.state.value = change_state;
						exec = true;
					}
				}
			}

			// 정상상환
			else if(change_state=='2') {
				if( confirm('대출정상종료 처리 하시겠습니까?<? if($ib_trust) { ?>\n\n실행 후에는 금융기관 예치금 관리시스템 재연계 불가능!<? } ?>') ) {
					f.state.value = change_state;
					exec = true;
				}
			}

			// 부실
			else if(change_state=='4') {
				if( confirm('부실 처리 하시겠습니까?<? if($ib_trust) { ?>\n\n실행 후에는 금융기관 예치금 관리시스템 재연계 불가능!<? } ?>') ) {
					f.state.value = change_state;
					exec = true;
				}
			}

			if(exec) {
				fdata = $('#form1').serialize();
				$.ajax({
					url: 'ajax.state_proc.php',
					type: 'post',
					data: fdata,
					dataType: 'json',
					success: function(data)
					{
						if(data.result == 'SUCCESS') {
							alert('처리완료!\n\n<?=$page_reload_msg?>'); window.location.reload();
						}
						else {
							alert(data.message);
						}
					},
					beforeSend: function() { loading('on'); },
					complete: function() { loading('off'); },
					error: function () { alert("ajax.state_proc.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
				});
			}

		}
		</script>



		<div class="col-lg-6">
			* 소수점이하 절사 처리된 데이터 입니다.
			<div class="panel panel-primary">
				<div class="panel-heading">대출 정보</div>
				<div class="panel-body">
					<div class="dataTable_wrapper">
						<table class="table table-bordered">
							<thead>
								<tr style="background:#EEE">
									<th class="text-center">대출금액</th>
									<th class="text-center">전체이자</th>
									<th class="text-center">납입이자(누적)</th>
									<th class="text-center">납입이자(당월)</th>
									<th class="text-center">연이자율</th>
									<th class="text-center">대출기간</th>
									<th class="text-center">이자계산일수</th>
								</tr>
							</thead>
							<tbody>
								<tr class="odd">
									<td align="center"><?=number_format($PRDT['invest_principal'])?>원</td>
									<td align="center"><?=number_format($LOAN['invest_interest'])?></td>
									<td align="center"><?=number_format($LOAN['plus_loan_interest'])?></td>
									<td align="center"><?=number_format($LOAN['month_loan_interest'])?></td>
									<td align="center"><?=$PRDT['loan_interest_rate']?>%</td>
									<td align="center"><?=$loan_date_range?></td>
									<td align="center"><?=$INI['total_day_count']?>일</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6">
			&nbsp;
			<div class="panel panel-primary">
				<div class="panel-heading">투자 정보</div>
				<div class="panel-body">
					<div class="dataTable_wrapper">
						<table class="table table-bordered">
							<thead>
								<tr style="background:#EEE">
									<th class="text-center">연수익률</th>
									<th class="text-center">예상이자</th>
									<th class="text-center">플랫폼 이용료율</th>
									<th class="text-center">플랫폼 이용료</th>
									<th class="text-center">원천징수</th>
									<th class="text-center">지급이자</th>
								</tr>
							</thead>
							<tbody>
								<tr class="odd">
									<td align="center"><?=$PRDT['invest_return']?>%</td>
									<td align="center"><?=number_format($REPAY_SUM['invest_interest'])?>원</td>
									<td align="center"><?=$PRDT['invest_usefee']?>%</td>
									<td align="center"><?=number_format($REPAY_SUM['invest_usefee'])?>원</td>
									<td align="center"><?=number_format($REPAY_SUM['TAX']['sum'])?>원</td>
									<td align="center"><?=number_format($REPAY_SUM['interest'])?>원</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

<?
if($ib_trust) {

	$USE_PRDT = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product WHERE display='Y' AND repay_acct_no='".$PRDT['repay_acct_no']."'");					// 상환용 가상계좌 사용상품수 추출
	$KSNET    = sql_fetch("SELECT VR_ACCT_NO, REF_NO FROM KSNET_VR_ACCOUNT WHERE USE_FLAG='Y' AND VR_ACCT_NO='".$PRDT['repay_acct_no']."'");		// KSNET 가상계좌 등록정보 추출

	$change_repay_acct_yn = ( $USE_PRDT['cnt'] > 1 && in_array($PRDT['state'], array('1','8','9')) ) ? true : false;		// 상환계좌 변경가능상품

?>
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<div>상환금 입출금 내역</div><br/>

					<ul style="list-style:none;margin:8px 0 0;display:inline-block">
						<li style="float:left;">상환계좌:</li>
						<li style="float:left;margin-left:15px;"><?=$PRDT['repay_acct_no']?></li>
						<li style="float:left;margin-left:15px;"><? if($change_repay_acct_yn) { ?><button type="button" id="setRepayTarget" class="btn btn-sm btn-default" style="height:24px;margin-left:20px;padding-top:3px;" <?=($prd_idx==$KSNET['REF_NO'])?'disabled':''?>>본상품의 상환계좌로 지정</button><? } ?></li>
					</ul><br/>
					<? if($PRDT['ref_prdt_idx']) { ?>
					<ul style="list-style:none;margin:0;display:inline-block">
						<li style="float:left;">참조계좌:</li>
						<li style="float:left;margin-left:15px;"><?=$PRDT['ref_prdt_repay_acct_no']?></li>
					</ul>
					<? } ?>

					<? if($change_repay_acct_yn) { ?>
					<script>
					$('#setRepayTarget').click(function() {
						if(confirm('본 상품번호를 참조번호로 설정하시겠습니까?\n\n주) 설정이후 <?=$PRDT['repay_acct_no']?> 계좌로 입금되는 내역은\n[품번.<?=$prd_idx?>] 상품의 상환건으로 등록됩니다.')) {
							$.ajax({
								url : 'repay_proc.php',
								type: 'POST',
								dataType: 'json',
								data: {
									action:'set_repay_target',
									idx:'<?=$PRDT['idx']?>',
									vacct:'<?=$KSNET['VR_ACCT_NO']?>'
								},
								success:function(data) {
									if(data.result=='SUCCESS') {
										alert('해당 계좌의 참조번호가 설정 되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload();
									}
									else {
										alert(data.message);
									}
								},
								beforeSend: function() { loading('on'); },
								complete: function() { loading('off'); },
								error: function () { alert("repay_proc.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
							});
						}
					});
					</script>
					<? } ?>

				</div>
				<div class="panel-body">
					<div id="loanerMoneyLog" class="dataTable_wrapper">

					</div>
				</div>
			</div>
		</div>

		<script>
		loadLoanerMoneyLog = function() {
			$.ajax({
				url : "./ajax_loaner_money_log.php?<?=$PRDT['idx']?>",
				type: "POST",
				data:{
					idx:'<?=$PRDT['idx']?>',
					vacct:'<?=$KSNET['VR_ACCT_NO']?>',
					print_form:'1'
				},
				success:function(data){
					$('#loanerMoneyLog').html(data);
				},
				beforeSend: function() { loading('on'); },
				complete: function() { loading('off'); },
				error: function () {
					alert("ajax_loaner_money_log.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
				}
			});
		}
		$(document).ready(function(){ loadLoanerMoneyLog(); });
		</script>
<?
}
?>

		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					원금일부상환 등록내역 &nbsp;
				</div>
				<div class="panel-body">
					<div class="dataTable_wrapper">
						<table class="table table-striped table-bordered">
							<thead>
								<tr style="background:#EEE">
									<th class="text-center">NO</th>
									<th class="text-center">상환일</th>
									<th class="text-center">상환금액</th>
									<th class="text-center">귀속회차</th>
									<th class="text-center">등록일시</th>
								</tr>
							</thead>
							<tbody>
<?
$sql  = "SELECT * FROM cf_partial_redemption WHERE product_idx='".$prd_idx."' ORDER BY idx ASC";
$res  = sql_query($sql);
$rows = $res->num_rows;
if($rows) {
	for($i=0,$no=1; $i<$rows; $i++,$no++) {
		$R = sql_fetch_array($res);
?>
								<tr align="center">
									<td><?=$no?></td>
									<td><?=$R['account_day']?></td>
									<td align="right"><?=number_format($R['amount']);?></td>
									<td><?=$R['turn']?>회차</td>
									<td><?=substr($R['rdate'],0,16)?></td>
								</tr>
<?
	}
}
else {
	echo "
								<tr align='center'>
									<td colspan='8'>내역이 없습니다.</td>
								</tr>\n";
}
?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-12">
			<div class="panel panel-warning">
				<div class="panel-heading">관리자 코멘트</div>
				<div class="panel-body">
					<div id="memo_list_area" style="margin-bottom:10px;width:100%"><!--목록영역--></div>
					<div class="dataTable_wrapper" style="margin:0 auto;">
						<input type="hidden" name="product_idx" value="<?=$PRDT['idx']?>">
						<textarea name="memo" id="memo" style="width:91%;height:80px"></textarea>
						<span id="memo_input_btn" class="btn btn-primary" style="width:8.5%;height:80px;padding-top:30px;">입력</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
	loadMemo = function(){
		$.ajax({
			url : "../ajax_invest_memo.php",
			type: "POST",
			data:{ product_idx:<?=$PRDT['idx']?> },
			success:function(data){
				$('#memo_list_area').html(data);
			},
			error: function () {
				alert("ajax_invest_memo.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
			}
		});
	}
	$(document).ready(function(){ loadMemo(); });

	delMemo = function(idx){
		if(confirm('삭제 하시겠습니까?')) {
			$.ajax({
				url : "../ajax_invest_memo.php",
				type: "POST",
				data:{ mode:'delete', idx:idx },
				success:function(data){
					loadMemo();
				},
				error: function () {
					alert("ajax_invest_memo.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
				}
			});
		}
	}

	$('#memo_input_btn').on('click', function(){
		var memo_val = $.trim( $('#memo').val() );
		if(memo_val=='') {
			alert('내용은 없냐?');
		}
		else {
			$.ajax({
				url : "../ajax_invest_memo.php",
				type: "POST",
				data:{ mode:'new', product_idx:<?=$PRDT['idx']?>, memo:memo_val },
				async:false,
				success:function(data){
					$('#memo').val('');
					$('#memo_list_area').html(data);
				},
				error: function () {
					alert("ajax_invest_memo.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
				}
			});
		}
	});
	</script>


<?
$c_tax_num = 0;					// 세금계산서 발급대상자 카운트
$p_tax_num = 0;					// 현금영수증 발급대상자 카운트
$c_tax_succ_num = 0;		// 세금계산서 발급완료 카운트
$p_tax_succ_num = 0;		// 현금영수증 발급완료 카운트

if( in_array($PRDT['state'], array('1','2','4','5','7','8','9')) ) {

	$repay_count = count($REPAY);
	for($i=0,$turn=1; $i<$repay_count; $i++,$turn++) {

		// 전체지급 요청 버튼 설정
		$repay_request_button = '';
		if( in_array($PRDT['state'], array('1','8')) ) {
			if($REPAY[$i]['SUCCESS']['loan_interest_state']=='Y') {
				if($REPAY[$i]['SUCCESS']['invest_give_state']=='') {
					if($ib_trust) {
						if($REPAY[$i]['SUCCESS']['ib_request_ready']=='Y') {
							$repay_request_button = '<button type="button" class="btn btn-primary" onClick="requestPopup(\'R\')">배분요청등록</button>';
						}
						else {
							$repay_request_button = '<button type="button" class="btn btn-warning" onClick="divideReady(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');">배분요청대기</button>';
						}
					}
					else {
						$repay_request_button = '<button type="button" class="btn btn-primary" onClick="loanInterestGive(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');">전체지급</button>';
					}
				}
				if($REPAY[$i]['SUCCESS']['invest_give_state']=='W') {		// 기관처리결과대기중일 경우 상태요약 출력
					$repay_request_button = '처리결과<br>대기중';
				}
				if($REPAY[$i]['SUCCESS']['invest_give_state']=='S') {		// 기관처리완료시 지급액션버튼 출력
					$repay_request_button = '<button type="button" class="btn btn-danger" onClick="loanInterestGive(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');">전체지급</button>';
				}
				if($REPAY[$i]['SUCCESS']['invest_give_state']=='Y') {		// 지급처리완료
					//$repay_request_button = '<button type="button"  class="btn btn-gray" style="color:gray">전체지급완료</button>';
				}
			}
		}

		if($REPAY[$i]['repay_schedule_date']) {
			$LT['BTN']['id']  = 'list_button'.$turn;
			$LT['ZONE']['id'] = 'list_area'.$turn;
			if(substr($REPAY[$i]['repay_schedule_date'], 0, 7)==date('Y-m')) {
				if($REPAY[$i]['SUCCESS']['invest_give_state']=='') {
					$LT['BTN']['title'] = '접기 <span class="glyphicon glyphicon-minus"></span>';
					$LT['BTN']['class'] = 'btn btn-xs btn-default';
					$LT['ZONE']['display'] = 'block';
				}
				else {
					$LT['BTN']['title'] = '내역보기 <span class="glyphicon glyphicon-list"></span>';
					$LT['BTN']['class'] = 'btn btn-xs btn-primary';
					$LT['ZONE']['display'] = 'none';
				}
			}
			else {
				$LT['BTN']['title'] = '내역보기 <span class="glyphicon glyphicon-list"></span>';
				$LT['BTN']['class'] = 'btn btn-xs btn-primary';
				$LT['ZONE']['display'] = 'none';
			}

			$list_toggle_button = '<a id="'.$LT['BTN']['id'].'" onClick="listToggle(\''.$turn.'\')" class="'.$LT['BTN']['class'].'" style="width:90px;">'.$LT['BTN']['title'].'</a>';
		}


		$OVERDUE = array();
		if($REPAY[$i]['OVERDUE']['day_count']) {

			if($REPAY[$i]['OVERDUE']['start_date'] && $REPAY[$i]['OVERDUE']['start_date']>'0000-00-00') {
				$OVERDUE['flag_color'] = ($REPAY[$i]['SUCCESS']['overdue_give']=='Y') ? 'blue' : 'red';
				$OVERDUE['day_comment'] = '>> 연체일수 : ' . number_format($REPAY[$i]['OVERDUE']['day_count']).'일';
			}
			else {
				$repay_left_date = ceil((strtotime(G5_TIME_YMD) - strtotime($REPAY[$i]['target_edate']))/86400);
				if( G5_TIME_YMD > $REPAY[$i]['repay_schedule_date'] ) {
					$OVERDUE['day_comment'] = '원리금 수납만료일로 부터 '.number_format($repay_left_date).'일 경과';
				}
			}

		}
		else {

			if($REPAY[$i]['SUCCESS']['invest_give_state']!='Y') {
				$repay_left_date = ceil((strtotime(G5_TIME_YMD) - strtotime($REPAY[$i]['target_edate']))/86400);
				//if( G5_TIME_YMD > $REPAY[$i]['repay_schedule_date'] ) {
				if( G5_TIME_YMD > $REPAY[$i]['target_edate'] ) {			// 회차별 종료일 이후 연체등록버튼 활성화: 2020-12-04 이상규과장 요청
					$OVERDUE['day_comment'] = '원리금 수납만료일로 부터 '.number_format($repay_left_date).'일 경과';
					$OVERDUE['txt_field']   = '<input type="text" id="overdue_start_date" placeholder="연체등록일" style="margin-left:20px;width:100px;text-align:center;" class="datepicker" readonly>';
					$OVERDUE['proc_button'] = '<a id="overdue_proc_button" class="btn btn-xs btn-danger" data-idx="'.$prd_idx.'" data-turn="'.$turn.'" style="width:90px;">연체등록</a>';
				}
			}

		}

?>
	<div class="col-lg-12">
		<div class="panel-body" style="padding-bottom: 0;" <?=($i==0)?"id='list_start'" : "";?>>
			<div style="width:100%;margin:4px 0 4px 0; padding:4px 20px 4px 20px; border:1px solid #ddd; border-radius:3px; background:#ffebcc;">
				<ul class="list-inline statusbar" style="margin:0;">
					<li style="min-width:120px"><strong>이자지급 <?=$turn?>회차</strong></li>
					<li style="width:90px;"><?=$list_toggle_button?></li>
					<li style="margin-left:20px;">지급예정일 : <?=$REPAY[$i]['repay_schedule_date']?></li>
					<li>|</li>
					<li>정산대상기간 : <?=preg_replace('/-/', '.', $REPAY[$i]['target_sdate'])?> ~ <?=preg_replace('/-/', '.', $REPAY[$i]['target_edate'])?></li>
					<li>|</li>
					<li>이자계산일수 : <?=$REPAY[$i]['day_count']?>일</li>

					<? if(count($OVERDUE)) { ?>
					<li>|</li>
					<li style="color:<?=$OVERDUE['flag_color']?>"><?=$OVERDUE['day_comment']?></li>
					<li><?=$OVERDUE['txt_field']?></li>
					<li><?=$OVERDUE['proc_button']?></li>
					<? } ?>

					<? if($REPAY[$i]['day_count'] > 1 && G5_TIME_YMD <= $REPAY[$i]['target_edate']) { ?>
					<li style="margin-left:20px;">
						<select id="sudden_repay_date<?=$i?>" style="height:22px;font-size:12px">
							<option value=''>:일부상환일선택:</option>";
							<?
							for($x=0,$y=1; $x<$REPAY[$i]['day_count']; $x++,$y++) {
								$date = date("Y-m-d", strtotime($REPAY[$i]['target_sdate'] . " +$x day"));

								echo "<option value='".$date."'>".$date."</option>";
							}
							?>
						</select>
						<input type="text" id="sudden_repay_amount<?=$i?>" onKeyUp="NumberFormat(this);" placeholder="상환금액입력" style="width:120px;height:22px;font-size:13px;text-align:right;">
						<button type="button" class="btn btn-xs btn-success" onClick="registPartialRedemption('sudden_repay_date<?=$i?>','sudden_repay_amount<?=$i?>','<?=$prd_idx?>', '<?=$REPAY[$i]['turn']?>')">부분상환등록</button>
					</li>
					<? } ?>
				</ul>
			</div>
			<div id="<?=$LT['ZONE']['id']?>" class="dataTable_wrapper" style="display:<?=$LT['ZONE']['display']?>">
				<table id="<?=$LT['ZONE']['id']?>_table" class="table table-striped table-bordered" style="width:100%;margin-bottom:0; font-size:12px">
					<colgroup>
						<col style="width:4%">
						<col style="%">
						<col style="width:6%">
						<col style="width:6%"><col style="width:6%"><col style="width:6%"><col style="width:6%"><col style="width:6%">
						<col style="width:6%"><col style="width:6%"><col style="width:6%"><col style="width:6%">
						<col style="width:7%">
						<col style="width:7%">
					</colgroup>
					<thead style="font-size:13px; background:#F8F8EF;">
						<tr align="center">
							<th rowspan="2" class="border_r">NO</th>
							<th rowspan="2" class="border_r">투자자정보</th>
							<th rowspan="2" class="border_r">잔여투자원금</th>
							<th colspan="5" class="border_r">당월정산</th>
							<th colspan="4" class="border_r" style="color:#AAA">누적현황</th>
							<th rowspan="2" class="border_r">지급여부</th>
							<th rowspan="2">세금계산서</th>
						</tr>
						<tr align="center">
							<th>세전이자</th>
							<th>플랫폼<br>이용료</th>
							<th>원천징수</th>
							<th>지급이자</th>
							<th class="border_r">원금</th>

							<th style="color:#AAA">플랫폼<br>이용료</th>
							<th style="color:#AAA">원천징수</th>
							<th style="color:#AAA">지급이자</th>
							<th class="border_r" style="color:#AAA">지급원금</th>
						</tr>
					</thead>
					<tbody>

<?
		$list_count = count($REPAY[$i]['LIST']);
		for($j=0,$num=$list_count; $j<$list_count; $j++,$num--) {

			//if($member['mb_id']=='admin_sori9th') {
				//if($REPAY[$i]['LIST'][$j]['member_type']=='2') print_rr($REPAY[$i]['LIST'][$j]['OTAX'], 'color:#FF2222');		// 소액부징수 미적용 세액 출력
			//}

			$member_id   = $REPAY[$i]['LIST'][$j]['mb_id'];
			$member_idx  = $REPAY[$i]['LIST'][$j]['mb_no'];
			$member_type = "";
			$member_type.= ($REPAY[$i]['LIST'][$j]['member_type']=='2') ? "법인" : "개인";
			$member_type.= ($REPAY[$i]['LIST'][$j]['is_creditor']=='Y') ? "-대부" : "";

			if($REPAY[$i]['LIST'][$j]['receive_method']) {
				$receive_method = ($REPAY[$i]['LIST'][$j]['receive_method']=='1') ? '환급계좌' : '<font color="#FF2222">예치금</font>';
			}
			else {
				$receive_method = "미지정";
			}

			$bgcolor = ($REPAY[$i]['LIST'][$j]['member_type']=='2') ? '#FFF2CC' : '';
			$bgcolor = ($REPAY[$i]['LIST'][$j]['is_creditor']=='Y') ? '#FCE4D6' : $bgcolor;

			$invest_type = ($REPAY[$i]['LIST'][$j]['is_advance_invest']=='Y') ? '사전투자' : '일반투자';

			$repay_result = $repair_query = "";
			if(in_array($PRDT['state'], array('1','2','5','8'))) {

				if($REPAY[$i]['SUCCESS']['ib_request_ready']=='Y') {
					$repay_result = "배분요청대기중";
				}

				if($REPAY[$i]['LIST'][$j]['paied']=='Y') {
					$repay_result = "<span style='color:#AAA'>지급완료<br>".substr($REPAY[$i]['LIST'][$j]['banking_date'], 0, 16)."</span>\n";
					// 실수령-이체금액 체크
					if($REPAY[$i]['LIST'][$j]['interest'] != $REPAY[$i]['LIST'][$j]['paied_amount']) {
						$repair_query = "";
						$repair_query.= "UPDATE cf_product_give SET";
						$repair_query.= " interest='".$REPAY[$i]['LIST'][$j]['interest']."',";
						$repair_query.= " interest_tax='".$REPAY[$i]['LIST'][$j]['TAX']['interest_tax']."',";
						$repair_query.= " local_tax='".$REPAY[$i]['LIST'][$j]['TAX']['local_tax']."',";
						$repair_query.= " fee='".$REPAY[$i]['LIST'][$j]['invest_usefee']."'";
						$repair_query.= " WHERE idx='".$REPAY[$i]['LIST'][$j]['give_idx']."';";

						$repay_result.= "<div style='margin:12px 0; text-align:left;color:red'>[실지급기입액]<br/>\n";
						$repay_result.= "플랫폼이용료. " . number_format($REPAY[$i]['LIST'][$j]['paied_fee']) ."<br/>\n";
						$repay_result.= "원천징수. " . number_format($REPAY[$i]['LIST'][$j]['paied_interest_tax']+$REPAY[$i]['LIST'][$j]['paied_local_tax']) ."<br/>\n";
						$repay_result.= "지급이자. " . number_format($REPAY[$i]['LIST'][$j]['paied_amount']);
						$repay_result.= "</div>\n";

						$repair_query.= "<div style='text-align:left'>" . $repair_query . "</div>\n";
					}
				}
				else {
					if($REPAY[$i]['SUCCESS']['invest_give_state']=='W') {
						$repay_result = "처리결과<br>대기중";
					}
					else if($REPAY[$i]['SUCCESS']['invest_give_state']=='S') {  // 기관회수처리 완료 -> 투자자의 잔고에 원리금이 상계처리됨을 뜻함.
						switch($REPAY[$i]['LIST'][$j]['ib_withdraw']) {
							case '00000000' : $repay_result = '기관측 회수금<br>배분완료<br>'.substr($REPAY[$i]['LIST'][$j]['ib_withdraw_datetime'], 0, 16); break;
							case 'C'        : $repay_result = '<span style="color:red">회수처리실패</span>'; break;
							default         : break;
						}
					}
				}

			}

			if($REPAY[$i]['LIST'][$j]['member_type']=='2') {
				$TAX_INVOICE[$i]['C'] = $TAX_INVOICE[$i]['C'] + 1;
				if($REPAY[$i]['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['C_SUCC'] = $TAX_INVOICE[$i]['C_SUCC'] + 1; }
			}
			else {
				if($REPAY[$i]['LIST'][$j]['is_owner_operator']=='1') {
					$TAX_INVOICE[$i]['C'] = $TAX_INVOICE[$i]['C'] + 1;
					if($REPAY[$i]['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['C_SUCC'] = $TAX_INVOICE[$i]['C_SUCC'] + 1; }
				}
				else {
					$TAX_INVOICE[$i]['P'] = $TAX_INVOICE[$i]['P'] + 1;
					if($REPAY[$i]['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['P_SUCC'] = $TAX_INVOICE[$i]['P_SUCC'] + 1; }
				}
			}

			$taxinvoice_link = "";
			if($REPAY[$i]['LIST'][$j]['mgtKey']) {
				if(preg_match('/P_/i', $REPAY[$i]['LIST'][$j]['mgtKey']))       $taxinvoicetype = '현금영수증';
				else if(preg_match('/C_/i', $REPAY[$i]['LIST'][$j]['mgtKey']))  $taxinvoicetype = '세금계산서';
				else $taxinvoicetype = '직접확인';

				$taxinvoice_link = '<a href="/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL.php?mgtKey='.$REPAY[$i]['LIST'][$j]['mgtKey'].'" target="_blank">'.$taxinvoicetype.'</a>';
			}

			if($REPAY[$i]['LIST'][$j]['insidebank_after_trans_target']=='1') $bgcolor = '#53B5DC';

			// 원리금수취권번호
			$prin_rcv_no = ($INVEST[$j]['prin_rcv_no']) ? $INVEST[$j]['prin_rcv_no'] : 'M' . $REPAY[$i]['LIST'][$j]['mb_no'] .'P'.$PRDT['idx'].'I'.$REPAY[$i]['LIST'][$j]['invest_idx'];

			// 투자자별 누적
			$NUJUK[$member_idx]['invest_usefee']   +=	$REPAY[$i]['LIST'][$j]['invest_usefee'];			// 플랫폼이용료
			$NUJUK[$member_idx]['tax']             +=	$REPAY[$i]['LIST'][$j]['TAX']['sum'];					// 원천징수
			$NUJUK[$member_idx]['interest']        +=	$REPAY[$i]['LIST'][$j]['interest'];						// 지급이자
			$NUJUK[$member_idx]['repay_principal'] +=	$REPAY[$i]['LIST'][$j]['repay_principal'];		// 지급원금

			// 누적합계
			$NUJUK_SUM['invest_usefee']   +=	$REPAY[$i]['LIST'][$j]['invest_usefee'];			// 누적현황:플랫폼이용료
			$NUJUK_SUM['tax']             +=	$REPAY[$i]['LIST'][$j]['TAX']['sum'];					// 누적현황:원천징수
			$NUJUK_SUM['interest']        +=	$REPAY[$i]['LIST'][$j]['interest'];						// 누적현황:지급이자
			$NUJUK_SUM['repay_principal'] +=	$REPAY[$i]['LIST'][$j]['repay_principal'];		// 누적현황:지급원금

?>
						<tr <?if($bgcolor){?>style="background:<?=$bgcolor?>;"<?}?>>
							<td align="center" class="border_r"><?=$num?></td>
							<td align="left" class="border_r" style="padding:2px">
								<dl style="margin:0">
									<dd style='margin:0 8px'><stt>수취권번호.</stt> <?=$prin_rcv_no?></span> &nbsp; <stt>수취방식.</stt> <?=$receive_method?></dd>
									<dd style='margin:0 8px'><stt>ID.</stt> <a href="/adm/member/member_view.php?&mb_id=<?=$member_id?>"><?=$member_id?></a> <? if(!$_REQUEST['mb_id']){ ?> &nbsp; <a href="<?=$_SERVER['PHP_SELF']?>?idx=<?=$prd_idx?>&mb_id=<?=$member_id?>" style="font-size:12px;color:#3366FF">[본건만 출력]</a><? } ?></dd>
									<dd style='margin:0 8px'><stt>구분.</stt> <?=$member_type?> &nbsp; <stt>성명/상호.</stt> <?=($_SESSION['ss_accounting_admin'])?$REPAY[$i]['LIST'][$j]['mb_name']:hanStrMasking($REPAY[$i]['LIST'][$j]['mb_name']);?></dd>
								</dl>
							</td>

							<td align="right" class="border_r"><?=number_format($REPAY[$i]['LIST'][$j]['remain_principal'])?></td>

							<!-- 당월정산 -->
							<td align="right"><span style='color:#3366FF'><?=number_format($REPAY[$i]['LIST'][$j]['invest_interest'])?></span>
								<div style="width:100%;margin-top:8px;"><button type="button" onClick="openBillDetail('<?=$prd_idx?>','<?=$REPAY[$i]['turn']?>','<?=$REPAY[$i]['LIST'][$j]['mb_no']?>','<?=$REPAY[$i]['is_overdue']?>','<?=($_REQUEST['lib']=='20210102')?'old':''?>');" class="btn btn-xs btn-default" style="width:100%;">상세보기</button></div>
							</td>
							<td align="right"><?=number_format($REPAY[$i]['LIST'][$j]['invest_usefee'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['LIST'][$j]['TAX']['sum'])?></td>
							<td align="right"><span style='color:#2222FF'><?=number_format($REPAY[$i]['LIST'][$j]['interest'])?></span></td>
							<td align="right" class="border_r">
								<span style='color:#2222FF'><?=number_format($REPAY[$i]['LIST'][$j]['repay_principal'])?></span>
								<?
								if($REPAY[$i]['LIST'][$j]['revision_principal']) {
									echo "<span style='color:brown'>\n";
									echo "<br><br>\n";
									echo number_format($REPAY[$i]['LIST'][$j]['calc_repay_principal']) . "<br>\n";
									echo "(";
									if($REPAY[$i]['LIST'][$j]['revision_principal'] > 0) echo '+';
									echo  number_format($REPAY[$i]['LIST'][$j]['revision_principal'])."원 보정)";
									echo "</span>\n";
								}
								?>
							</td>
							<!-- 당월정산 -->

							<!-- 누적현황 -->
							<td align="right"><span style='color:#aaa'><?=number_format($NUJUK[$member_idx]['invest_usefee'])?></span></td>
							<td align="right"><span style='color:#aaa'><?=number_format($NUJUK[$member_idx]['tax'])?></span></td>
							<td align="right"><span style='color:#aaa'><?=number_format($NUJUK[$member_idx]['interest'])?></span></td>
							<td align="right" class="border_r"><span style='color:#aaa'><?=number_format($NUJUK[$member_idx]['repay_principal'])?></span></td>
							<!-- 누적현황 -->

							<!-- 지급여부 -->
							<td align="center" class="border_r">
								<?=$repay_result?>
								<?=($debug_mode=='1')?$repair_query:'';?>
							</td>

							<td align="center"><?=$taxinvoice_link?></td>
						</tr>

<?

		}		// end for($j=0,$num=$list_count; $j<$list_count; $j++,$num--)

		// 합계출력
		if(!$mb_id) {
?>
						<tr align="center" style="background:#EDF4FC;color:#2222FF;">
							<td colspan="2" class="border_r"><?=$turn?>회차 합계</td>
							<td align="right" class="border_r"><?=number_format($REPAY[$i]['SUM']['amount'])?></td>

							<td align="right"><?=number_format($REPAY[$i]['SUM']['invest_interest'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['SUM']['invest_usefee'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['SUM']['TAX']['sum'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['SUM']['interest'])?></td>
							<td align="right" class="border_r"><?=number_format($REPAY[$i]['SUM']['repay_principal'])?></td>

							<td align="right"><span style='color:#aaa'><?=number_format($NUJUK_SUM['invest_usefee'])?></span></td>
							<td align="right"><span style='color:#aaa'><?=number_format($NUJUK_SUM['tax'])?></span></td>
							<td align="right"><span style='color:#aaa'><?=number_format($NUJUK_SUM['interest'])?></span></td>
							<td align="right" class="border_r"><span style='color:#aaa'><?=number_format($NUJUK_SUM['repay_principal'])?></span></td>

							<td class="border_r"><?=$repay_request_button?></td>
							<td></td>
						</tr>
<?
		}
?>
					</tbody>
				</table>
				<script>
				$(document).ready(function() {
					$('#<?=$LT['ZONE']['id']?>_table').floatThead();
				});
				</script>

				<div class="panel-body" style="text-align:right;">
<?
		// ※ state: 진행현황(1:이자상환중|2:상환완료(투자종료)|3:투자금모집실패|4:부실|5:중도일시상환|6:대출취소)
		if(in_array($PRDT['state'], array('1','2','4','5'))) {
			echo '<a href="./repay_calculate_excel.php?idx='.$PRDT['idx'].'&turn='.$turn.'&mb_id='.$mb_id.'" target="_blank" class="btn btn-success" style="width:160px;">엑셀저장</a>' . PHP_EOL;
		}

		if(in_array($PRDT['state'], array('1','5','8'))) {			// if(in_array($PRDT['state'], array('1','5'))) {

			// [대출이자 수급완료 처리버튼]
			if($REPAY[$i]['SUCCESS']['loan_interest_state']=='Y') {
				echo '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">대출이자 수급완료</button>' . PHP_EOL;
			}
			else {
				echo '<button type="button" class="btn btn-danger" onClick="loanInterestSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">대출이자 수급완료</button>' . PHP_EOL;
			}

			// [대출원금 수급완료 처리버튼]
			if($turn==$INI['repay_turn']) {
				if($REPAY[$i]['SUCCESS']['loan_principal_state']=='Y') {
					echo '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">대출원금 수급완료</button>' . PHP_EOL;
				}
				else {
					// 상환방식에 따른 구분 (1:만기일시상환|2:원리금균등상환|3:원금균등상환)
					if($PRDT['repay_type']=='1') {
						if($turn==$repay_count) {
							echo '<button type="button" class="btn btn-danger" onClick="loanPrincipalSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">대출원금 수급완료</button>' . PHP_EOL;
						}
						else {
							echo '<button type="button" class="btn btn-gray" onClick="alert(\'만기일시상환 방식의 대출건 입니다.\');" style="width:160px;">대출원금 수급완료</button>' . PHP_EOL;
						}
					}
					else {
						echo '<button type="button" class="btn btn-danger" onclick="loanPrincipalSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">대출원금 수급완료</button>' . PHP_EOL;
					}
				}
			}

			// [투자수익금 지급완료 처리버튼]
			if($REPAY[$i]['SUCCESS']['invest_give_state']=='Y') {
				echo '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">투자수익금 지급완료</button>' . PHP_EOL;
			}
			else {
				echo '<button type="button" class="btn btn-danger" onClick="investGiveSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">투자수익금 지급완료</button>' . PHP_EOL;
			}


			// [투자원금 지급완료 처리버튼]
			if($turn==$INI['repay_turn']) {
				if($REPAY[$i]['SUCCESS']['invest_principal_give']=='Y') {
					echo '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">투자원금 지급완료</button>' . PHP_EOL;
				}
				else {
					// 상환방식에 따른 구분 (1:만기일시상환|2:원리금균등상환|3:원금균등상환)
					if($PRDT['repay_type']=='1') {
						if($turn==$repay_count) {
							echo '<button type="button" class="btn btn-danger" onClick="investPrincipalGiveSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">투자원금 지급완료</button>' . PHP_EOL;
						}
						else {
							echo '<button type="button" class="btn btn-gray" onClick="alert(\'만기일시상환 방식의 대출건 입니다.\');" style="width:160px;">투자원금 지급완료</button>' . PHP_EOL;
						}
					}
					else {
						echo '<button type="button" class="btn btn-danger" onClick="investPrincipalGiveSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">투자원금 지급완료</button>' . PHP_EOL;
					}
				}
			}

		}


?>
				</div><!-- /.panel-body -->

<?
		///////////////////////////////////////////////////////////////////////////////
		// 부분상환 목록
		///////////////////////////////////////////////////////////////////////////////
		$ptlRepay_count = count($REPAY[$i]['PARTIAL']);		// 부분상환 횟수
		if($ptlRepay_count) {

			for($ptlArr_no=0,$ptlnum=1; $ptlArr_no<$ptlRepay_count; $ptlArr_no++,$ptlnum++) {

				$PTLREPAY = $REPAY[$i]['PARTIAL'][$ptlArr_no];

				//if($_SERVER['REMOTE_ADDR']=='211.248.149.48') print_rr($PTLREPAY, 'font-size:12px');

?>
				<div style="width:100%;margin:0 0 4px; padding:4px 20px 4px 20px; border:1px solid #3366FF; border-radius:3px; background:#ccebff;">
					<ul class="list-inline" style="margin:0;">
						<li style="min-width:120px"><strong><?=$REPAY[$i]['turn']?>회차 원금 일부상환 정산 내역 - <?=$ptlArr_no+1?></strong></li>
						<li>상환일. <strong><?=$PTLREPAY['account_day']?></strong></li>
						<li>상환총액. <strong><?=number_format($PTLREPAY['amount'])?>원</strong></li>
					</ul>
				</div>
				<table class="table table-striped table-bordered" style="margin-bottom:0; font-size:12px;">
					<colgroup>
						<col style="width:4%">
						<col>
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:43%">
						<col style="width:7%">
					</colgroup>
					<thead>
						<tr align="center" style="background:#F8F8EF">
							<th class="border_r">NO</th>
							<th class="border_r">투자자정보</th>
							<th class="border_r">투자원금</th>
							<th>누적상환원금</th>
							<th class="border_r">잔여투자원금</th>
							<th class="border_r">상환원금</th>
							<th class="border_r">-</th>
							<th rowspan="2">지급여부</th>
						</tr>
					</thead>
					<tbody>
<?
				$ptlRepay_list_count = count($PTLREPAY['LIST']);		// 투자자 카운트
				for($x=0,$num=$ptlRepay_list_count; $x<$ptlRepay_list_count; $x++,$num--) {

					// 원리금수취권번호
					$prin_rcv_no = ($INVEST[$j]['prin_rcv_no']) ? $INVEST[$x]['prin_rcv_no'] : $prin_rcv_no = 'M' . $PTLREPAY['LIST'][$x]['mb_no'] .'P'.$PRDT['idx'].'I'.$PTLREPAY['LIST'][$x]['invest_idx'];

					$member_id  = $PTLREPAY['LIST'][$x]['mb_id'];
					$member_idx = $PTLREPAY['LIST'][$x]['mb_no'];

					$member_type = "";
					$member_type.= ($PTLREPAY['LIST'][$x]['member_type']=='2') ? "법인" : "개인";
					$member_type.= ($PTLREPAY['LIST'][$x]['is_creditor']=='Y') ? "-대부" : "";

					$receive_method = "미지정";
					if($PTLREPAY['LIST'][$x]['receive_method']) {
						$receive_method = ($PTLREPAY['LIST'][$x]['receive_method']=='1') ? '환급계좌' : '<font color="#FF2222">예치금</font>';
					}

					$bgcolor = ($PTLREPAY['LIST'][$x]['member_type']=='2') ? '#FFF2CC' : '';
					$bgcolor = ($PTLREPAY['LIST'][$x]['is_creditor']=='Y') ? '#FCE4D6' : $bgcolor;

					$ptl_repay_result = "";
					if($PTLREPAY['LIST'][$x]['paied']=='Y') {
						$ptl_repay_result.= "<span style='color:#AAA'>지급완료<br>".substr($PTLREPAY['LIST'][$x]['banking_date'], 0, 16)."</span>\n";

						// 실수령-이체금액 체크
						if($PTLREPAY['LIST'][$x]['repay_principal'] <> $PTLREPAY['LIST'][$x]['paied_amount']) {
							$diff_amount = $PTLREPAY['LIST'][$x]['paied_amount'] - $PTLREPAY['LIST'][$x]['repay_principal'];		// 지급액 - 계산액
							$diff_amount_str = ($diff_amount > 0) ? '과지급' : '미지급';

							$ptl_repay_result.= "<br/><span style='color:red'>지급: ".number_format($PTLREPAY['LIST'][$x]['paied_amount'])."원</span>\n";
							$ptl_repay_result.= "<br/><span style='color:red'>{$diff_amount_str}: ".number_format(abs($diff_amount))."원</span>\n";
							if($prd_idx <> '1782') $ptl_repay_result.= "<br/>UPDATE cf_product_give SET interest='0', principal='".$PTLREPAY['LIST'][$x]['repay_principal']."' WHERE idx='".$PTLREPAY['LIST'][$x]['give_idx']."';\n";

						}
					}
					else {
						if($PTLREPAY['SUCCESS']['invest_give_state']=='W') {
							$ptl_repay_result.= "처리결과<br>대기중";
						}
						else if($PTLREPAY['SUCCESS']['invest_principal_give']=='S') {  // 기관회수처리 완료 -> 투자자의 잔고에 원리금이 상계처리됨을 뜻함.
							switch($PTLREPAY['LIST'][$x]['ib_withdraw']) {
								case '00000000' : $ptl_repay_result.= '기관측 회수금<br>배분완료<br>'.substr($PTLREPAY['LIST'][$x]['ib_withdraw_datetime'], 0, 16); break;
								case 'C'        : $ptl_repay_result.= '<span style="color:red">회수처리실패</span>'; break;
								default         : break;
							}
						}
					}

?>
						<tr <?if($bgcolor){?>style="background:<?=$bgcolor?>;"<?}?>>
							<td align="center" class="border_r"><?=$num?></td>
							<td align="left" class="border_r" style="padding:2px">
								<dl style="margin:0">
									<dd style='margin:0 8px'><stt>수취권번호.</stt> <?=$prin_rcv_no?></span> &nbsp; <stt>수취방식.</stt> <?=$receive_method?></dd>
									<dd style='margin:0 8px'><stt>ID.</stt> <a href="/adm/member/member_view.php?&mb_id=<?=$member_id?>"><?=$member_id?></a> <? if(!$_REQUEST['mb_id']){ ?> &nbsp; <a href="<?=$_SERVER['PHP_SELF']?>?idx=<?=$prd_idx?>&mb_id=<?=$member_id?>" style="font-size:12px;color:#3366FF">[본건만 출력]</a><? } ?></dd>
									<dd style='margin:0 8px'><stt>구분.</stt> <?=$member_type?> &nbsp; <stt>성명/상호.</stt> <?=$PTLREPAY['LIST'][$x]['mb_name']?></dd>
								</dl>
							</td>
							<td align="right" class="border_r"><?=number_format($PTLREPAY['LIST'][$x]['invest_amount'])?></td>
							<td align="right"><?=number_format($PTLREPAY['LIST'][$x]['partial_principal'])?></td>
							<td align="right" class="border_r"><?=number_format($PTLREPAY['LIST'][$x]['remain_principal'])?></td>
							<td align="right" class="border_r">
								<span style='color:#2222FF'><?=number_format($PTLREPAY['LIST'][$x]['repay_principal'])?></span>
								<div style="width:100%;margin-top:20px;"><button type="button" onClick="openBillDetail('<?=$prd_idx?>','<?=$REPAY[$i]['turn']?>','<?=$PTLREPAY['LIST'][$x]['mb_no']?>','N','<?=($_REQUEST['lib']=='20210102')?'old':''?>');" class="btn btn-xs btn-default" style="width:100%;">상세보기</button></div>
							</td>
							<td class="border_r"></td>
							<td align="center"><?=$ptl_repay_result?></td>
						</tr>
<?
					//if(!$mb_id) {
						$PTLREPAY_SUM['invest_amount']     += $PTLREPAY['LIST'][$x]['invest_amount'];
						$PTLREPAY_SUM['partial_principal'] += $PTLREPAY['LIST'][$x]['partial_principal'];
						$PTLREPAY_SUM['remain_principal']  += $PTLREPAY['LIST'][$x]['remain_principal'];
						$PTLREPAY_SUM['repay_principal']   += $PTLREPAY['LIST'][$x]['repay_principal'];
					//}

				}
?>
					</tbody>

<?
				//if(!$mb_id) {

					// 전체지급/요청 버튼 설정
					$ptlRepay_request_button = '';
					if($PTLREPAY['SUCCESS']['loan_principal_state']=='Y') {

						if($PTLREPAY['SUCCESS']['invest_principal_give']=='') {
							if($ib_trust) {
								if($PTLREPAY['SUCCESS']['ib_request_ready']=='Y') {
									$ptlRepay_request_button = '<button type="button" class="btn btn-primary" onClick="requestPopup(\'P\');">배분요청등록</button>';
								}
								else {
									$ptlRepay_request_button = '<button type="button" class="btn btn-warning" onClick="partialDivideReady(\''.$PRDT['idx'].'\', \''.$PTLREPAY['account_day'].'\', \''.$REPAY[$i]['turn'].'\', \''.$PTLREPAY['turn_sno'].'\');">배분요청대기</button>';
								}
							}
							else {
								$ptlRepay_request_button = '<button type="button" class="btn btn-primary" onClick="partialPrincipalGive(\''.$PRDT['idx'].'\', \''.$PTLREPAY['account_day'].'\', \''.$REPAY[$i]['turn'].'\', \''.$PTLREPAY['turn_sno'].'\');">전체지급</button>';
							}
						}
						if($PTLREPAY['SUCCESS']['invest_principal_give']=='W') {		// 기관처리결과대기중일 경우 상태요약 출력
							$ptlRepay_request_button = '처리결과<br>대기중';
						}
						if($PTLREPAY['SUCCESS']['invest_principal_give']=='S') {		// 기관처리완료시 지급액션버튼 출력
							$ptlRepay_request_button = '<button type="button" class="btn btn-danger" onClick="partialPrincipalGive(\''.$PRDT['idx'].'\', \''.$PTLREPAY['account_day'].'\', \''.$REPAY[$i]['turn'].'\', \''.$PTLREPAY['turn_sno'].'\');">전체지급</button>';
						}
						if($PTLREPAY['SUCCESS']['invest_principal_give']=='Y') {		// 지급처리완료
							//$ptlRepay_request_button = '<button type="button"  class="btn btn-gray" style="color:gray">전체지급완료</button>';
						}

					}

?>
						<tr align="center" style="background:#EDF4FC;color:#2222FF;">
							<td class="border_r">합계</td>
							<td class="border_r"></td>
							<td align="right" class="border_r"><?=number_format($PTLREPAY_SUM['invest_amount'])?></td>
							<td align="right"><?=number_format($PTLREPAY_SUM['partial_principal'])?></td>
							<td align="right" class="border_r"><?=number_format($PTLREPAY_SUM['remain_principal'])?></td>
							<td align="right" class="border_r"><?=number_format($PTLREPAY_SUM['repay_principal'])?></td>
							<td class="border_r"></td>
							<td><?=$ptlRepay_request_button?></td>
						</tr>
<?
					unset($PTLREPAY_SUM);
				//}
?>

				</table>

				<div class="panel-body" style="text-align:right;">
<?
				// [일부상환 수급완료 버튼]
				if($PTLREPAY['SUCCESS']['loan_principal_state']=='Y') {
					echo '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">일부상환 수급완료</button>' . PHP_EOL;
				}
				else {
					echo '<button type="button" class="btn btn-danger" onClick="partialPrincipalSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['turn'].'\', \''.$PTLREPAY['turn_sno'].'\');" style="width:160px;">일부상환 수급완료</button>' . PHP_EOL;
				}

				// [일부상환 지급완료 버튼]
				if($PTLREPAY['SUCCESS']['invest_principal_give']=='Y') {
					echo '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">일부상환 지급완료</button>' . PHP_EOL;
				}
				else {
					echo '<button type="button" class="btn btn-danger" onClick="partialPrincipalGiveSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['turn'].'\', \''.$PTLREPAY['turn_sno'].'\');" style="width:160px;">일부상환 지급완료</button>' . PHP_EOL;
				}
?>
				</div>

<?
			}		// end for($ptlArr_no = 0; $ptlArr_no < $ptlRepay_count; $ptlArr_no++)
		}		// end if(count($REPAY[$i]['PARTIAL']))


		///////////////////////////////////////////////////////////////////////////////
		// 연체목록
		///////////////////////////////////////////////////////////////////////////////
		if($REPAY[$i]['SUCCESS']['overdue_start_date'] > '0000-00-00') {

			$LT['ZONE']['id'] = 'overdue_list_area'.$turn;

?>

				<div style="width:100%;margin:0 0 4px; padding:4px 20px 4px 20px; border:1px solid#brown; border-radius:3px; background:#FF2222;">
					<ul class="list-inline" style="margin:0;color:#fff">
						<li style="min-width:120px"><strong>연체 정산 내역</strong></li>
						<li>
							귀속기간 : <?=preg_replace('/-/', '.', $REPAY[$i]['OVERDUE']['start_date'])?> ~ <?=($REPAY[$i]['OVERDUE']['end_date']>'0000-00-00') ? preg_replace('/-/', '.', $REPAY[$i]['OVERDUE']['end_date']) : G5_TIME_YMD?> /
							귀속일수 : <?=$REPAY[$i]['OVERDUE']['day_count']?>일 /
							이자율 : 연<?=$REPAY[$i]['OVERDUE']['rate']?>%
						</li>
						<?
						// 연체중 원금 일부상환([제2892호] 서울 신림 역세권 숙박시설 ABL 2차) 처리를 위해 2022-01-12 전승찬 추가
						if($REPAY[$i]['OVERDUE']['day_count'] > 1 && G5_TIME_YMD <= $REPAY[$i]['OVERDUE']['end_date']) { ?>
						<li style="margin-left:20px;">
							<select id="sudden_repay_date<?=$i?>" style="height:22px;font-size:12px;color:black;">
								<option value=''>:일부상환일선택:</option>
								<?
								for($x=0,$y=1; $x<$REPAY[$i]['OVERDUE']['day_count']; $x++,$y++) {
									$date = date("Y-m-d", strtotime($REPAY[$i]['OVERDUE']['start_date'] . " +$x day"));

									echo "<option value='".$date."'>".$date."</option>";
								}
								?>
							</select>
							<input type="text" id="sudden_repay_amount<?=$i?>" onKeyUp="NumberFormat(this);" placeholder="상환금액입력" style="width:120px;height:22px;font-size:13px;text-align:right;color:black;">
							<button type="button" class="btn btn-xs btn-success" onClick="registPartialRedemption('sudden_repay_date<?=$i?>','sudden_repay_amount<?=$i?>','<?=$prd_idx?>', '<?=$REPAY[$i]['turn']?>')">부분상환등록</button>
						</li>
						<? } ?>
					</ul>
				</div>
				<table id="<?=$LT['ZONE']['id']?>" class="table table-striped table-bordered" style="margin-bottom:0; font-size:12px;">
					<colgroup>
						<col style="width:4%">
						<col style="%">
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:6%">
						<col style="width:30%">
						<col style="width:7%">
						<col style="width:7%">
					</colgroup>
					<thead style="background:#F8F8EF;">
						<tr align="center">
							<th class="border_r">NO</th>
							<th class="border_r">투자자정보</th>
							<th class="border_r">투자금</th>
							<th>세전이자</th>
							<th>플랫폼<br>이용료</th>
							<th>원천징수</th>
							<th class="border_r">지급이자</th>
							<th class="border_r"></th>
							<th class="border_r">지급여부</th>
							<th>세금계산서</th>
						</tr>
					</thead>
					<tbody>
<?
			for($j=0,$num=$list_count; $j<$list_count; $j++,$num--) {

				$member_id   = $REPAY[$i]['OVERDUE']['LIST'][$j]['mb_id'];
				$member_idx   = $REPAY[$i]['OVERDUE']['LIST'][$j]['mb_no'];
				$member_type = "";
				$member_type.= ($REPAY[$i]['OVERDUE']['LIST'][$j]['member_type']=='2') ? "법인" : "개인";
				$member_type.= ($REPAY[$i]['OVERDUE']['LIST'][$j]['is_creditor']=='Y') ? "-대부" : "";

				if($REPAY[$i]['OVERDUE']['LIST'][$j]['receive_method']) {
					$receive_method = ($REPAY[$i]['OVERDUE']['LIST'][$j]['receive_method']=='1') ? '환급계좌' : '<font color="#FF2222">예치금</font>';
				}
				else {
					$receive_method = "미지정";
				}

				$bgcolor = ($REPAY[$i]['OVERDUE']['LIST'][$j]['member_type']=='2') ? '#FFF2CC' : '#FFFFFF';
				$bgcolor = ($REPAY[$i]['OVERDUE']['LIST'][$j]['is_creditor']=='Y') ? '#FCE4D6' : $bgcolor;

				$invest_type = ($REPAY[$i]['OVERDUE']['LIST'][$j]['is_advance_invest']=='Y') ? '사전투자' : '일반투자';


				$ovd_repay_result = "";
				if($REPAY[$i]['OVERDUE']['LIST'][$j]['paied']=='Y') {
					$ovd_repay_result = "<span style='color:#AAA'>지급완료<br>".substr($REPAY[$i]['OVERDUE']['LIST'][$j]['banking_date'], 0, 16)."</span>\n";

					// 실수령-이체금액 체크
					if($REPAY[$i]['OVERDUE']['LIST'][$j]['interest'] != $REPAY[$i]['OVERDUE']['LIST'][$j]['paied_amount']) {
						$ovd_repay_result.= "<span style='color:red'>".number_format($REPAY[$i]['OVERDUE']['LIST'][$j]['paied_amount'])."</span>\n";
						$ovd_repay_result.= "<br>UPDATE cf_product_give SET interest='".$REPAY[$i]['OVERDUE']['LIST'][$j]['interest']."' WHERE idx='".$REPAY[$i]['OVERDUE']['LIST'][$j]['give_idx']."';\n";
					}
				}
				else {
					if($REPAY[$i]['SUCCESS']['overdue_give']=='W') {
						$ovd_repay_result = "처리결과<br>대기중";
					}
					else if($REPAY[$i]['SUCCESS']['overdue_give']=='S') {  // 기관회수처리 완료 -> 투자자의 잔고에 원리금이 상계처리됨을 뜻함.
						switch($REPAY[$i]['OVERDUE']['LIST'][$j]['ib_withdraw']) {
							case '00000000' : $ovd_repay_result = '기관측 회수금<br>배분완료<br>'.substr($REPAY[$i]['OVERDUE']['LIST'][$j]['ib_withdraw_datetime'], 0, 16); break;
							case 'C'        : $ovd_repay_result = '<span style="color:red">회수처리실패</span>'; break;
							default         : break;
						}
					}
				}

				if($REPAY[$i]['OVERDUE']['LIST'][$j]['member_type']=='2') {
					$TAX_INVOICE[$i]['C'] = $TAX_INVOICE[$i]['C'] + 1;
					if($REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['C_SUCC'] = $TAX_INVOICE[$i]['C_SUCC'] + 1; }
				}
				else {
					if($REPAY[$i]['OVERDUE']['LIST'][$j]['is_owner_operator']=='1') {
						$TAX_INVOICE[$i]['C'] = $TAX_INVOICE[$i]['C'] + 1;
						if($REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['C_SUCC'] = $TAX_INVOICE[$i]['C_SUCC'] + 1; }
					}
					else {
						$TAX_INVOICE[$i]['P'] = $TAX_INVOICE[$i]['P'] + 1;
						if($REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey']) { $TAX_INVOICE[$i]['P_SUCC'] = $TAX_INVOICE[$i]['P_SUCC'] + 1; }
					}
				}

				$taxinvoice_link = '';
				if($REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey']) {
					if(preg_match('/P_/i', $REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey']))       $taxinvoicetype = '현금영수증';
					else if(preg_match('/C_/i', $REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey']))  $taxinvoicetype = '세금계산서';
					else $taxinvoicetype = '직접확인';

					$taxinvoice_link = '<a href="/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL.php?mgtKey='.$REPAY[$i]['OVERDUE']['LIST'][$j]['mgtKey'].'" target="_blank">'.$taxinvoicetype.'</a>';
				}


				$prin_rcv_no = 'M' . $REPAY[$i]['OVERDUE']['LIST'][$j]['mb_no'] .'P'.$PRDT['idx'].'I'.$REPAY[$i]['OVERDUE']['LIST'][$j]['invest_idx'];

?>
						<tr style="background:<?=$bgcolor?>;">
							<td align="center" class="border_r" alt="NO"><?=$num?></td>
							<td align="left" class="border_r" style="padding:2px">
								<dl style="margin:0">
									<dd style='margin:0 8px'><stt>수취권번호.</stt> <?=$prin_rcv_no?></span> &nbsp; <stt>수취방식.</stt> <?=$receive_method?></dd>
									<dd style='margin:0 8px'><stt>ID.</stt> <a href="/adm/member/member_view.php?&mb_id=<?=$member_id?>"><?=$member_id?></a> <? if(!$_REQUEST['mb_id']){ ?> &nbsp; <a href="<?=$_SERVER['PHP_SELF']?>?idx=<?=$prd_idx?>&mb_id=<?=$member_id?>" style="font-size:12px;color:#3366FF">[본건만 출력]</a><? } ?></dd>
									<dd style='margin:0 8px'><stt>구분.</stt> <?=$member_type?> &nbsp; <stt>성명/상호.</stt> <?=$REPAY[$i]['OVERDUE']['LIST'][$j]['mb_name']?></dd>
								</dl>
							</td>
							<td align="right" class="border_r"><?=number_format($REPAY[$i]['OVERDUE']['LIST'][$j]['invest_amount'])?></td>
							<td align="right"><span style="color:#3366FF"><?=number_format($REPAY[$i]['OVERDUE']['LIST'][$j]['invest_interest'])?></span></td>
							<td align="right"><?=number_format($REPAY[$i]['OVERDUE']['LIST'][$j]['invest_usefee'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['OVERDUE']['LIST'][$j]['TAX']['sum'])?></td>
							<td align="right" class="border_r"><span style="color:#FF2222"><?=number_format($REPAY[$i]['OVERDUE']['LIST'][$j]['interest'])?></span></td>
							<td align="center" class="border_r"></td>
							<td align="center" class="border_r"><?=$ovd_repay_result?></td>
							<td align="center"><?=$taxinvoice_link?></td>
						</tr>

<?
			}		// end for($j=0,$num=$list_count; $j<$list_count; $j++,$num--)

			//if(!$mb_id) {

				// 전체지급/요청 버튼 설정
				$ovd_repay_request_button = '';
				if( in_array($PRDT['state'], array('1','8')) ) {

					if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_give']=='') {
						if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_receive']=='Y') {
							if($ib_trust) {
								if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_ib_request_ready']=='Y') {
									$ovd_repay_request_button = '<button type="button" class="btn btn-primary" onClick="requestPopup(\'O\')">배분요청등록</button>';
								}
								else {
									$ovd_repay_request_button = '<button type="button" class="btn btn-warning" onClick="overdueDivideReady(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');">배분요청대기</button>';
								}
							}
							else {
								$ovd_repay_request_button = '<button type="button" class="btn btn-primary" onClick="overdueGive(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');">전체지급</button>';
							}
						}
					}
					else if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_give']=='W') {		// 기관처리결과대기중일 경우 상태요약 출력
						$ovd_repay_request_button = '처리결과<br/>대기중';
					}
					else if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_give']=='S') {		// 기관처리완료시 지급액션버튼 출력
						$ovd_repay_request_button = '<button type="button" class="btn btn-danger" onClick="overdueGive(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');">전체지급</button>';
					}
					else if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_give']=='Y') {		// 지급처리완료
						//$ovd_repay_request_button = '전체지급<br/>완료';
					}

				}

?>
						<tr align="center" style="background:#FFDDDD;color:brown;">
							<td class="border_r">합계</td>
							<td class="border_r"></td>
							<td align="right" class="border_r"><?=number_format($REPAY[$i]['OVERDUE']['SUM']['invest_amount'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['OVERDUE']['SUM']['invest_interest'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['OVERDUE']['SUM']['invest_usefee'])?></td>
							<td align="right"><?=number_format($REPAY[$i]['OVERDUE']['SUM']['TAX']['sum'])?></td>
							<td align="right" class="border_r"><?=number_format($REPAY[$i]['OVERDUE']['SUM']['interest'])?></td>
							<td class="border_r"></td>
							<td class="border_r"><?=$ovd_repay_request_button?></td>
							<td></td>
						</tr>
					</tbody>
<?
			//}
?>
				</table>

<?
			// [연체이자 수급완료 처리버튼]
			if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_receive']=='Y') {
				$ovd_rcv_flag_btn = '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">연체이자 수급완료</button>';
			}
			else {
				$ovd_rcv_flag_btn = '<button type="button" class="btn btn-danger" onClick="overdueRcvSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">연체이자 수급완료</button>';
			}

			// [연체이자 지급완료 처리버튼]
			if($REPAY[$i]['OVERDUE']['SUCCESS']['overdue_give']=='Y') {
				$ovd_give_flag_btn = '<button type="button" class="btn btn-gray" onClick="alert(\'이미 처리 되었습니다.\');" style="width:160px;">연체이자 지급완료</button>';
			}
			else {
				$ovd_give_flag_btn = '<button type="button" class="btn btn-danger" onClick="overdueGiveSuccess(\''.$PRDT['idx'].'\', \''.$REPAY[$i]['repay_date'].'\', \''.$turn.'\');" style="width:160px;">연체이자 지급완료</button>';
			}

?>

				<div class="panel-body" style="text-align:right;">
					<a href="./repay_calculate_excel_overdue.php?idx=<?=$PRDT['idx']?>&turn=<?=$turn?>&mb_id=<?=$mb_id?>" target="_blank" class="btn btn-success" style="width:160px;">엑셀저장</a>
					<?=$ovd_rcv_flag_btn?>
					<?=$ovd_give_flag_btn?>
				</div>

				<script>
				$(document).ready(function() {
					$('#<?=$LT['ZONE']['id']?>_table').floatThead();
				});
				</script>

<?
		}		// end if($REPAY[$i]['SUCCESS']['overdue_start_date'] > '0000-00-00')
?>
			</div>
		</div>

	</div><!-- /.col-lg-12 -->
<?
	}		// 회차루프 끝

}



###############################################################################
## 대출실행이 되지 않은 상품 투자내역
###############################################################################
else if( in_array($PRDT['state'], array('','3','6')) ) {
?>
	<div class="col-lg-12">
		<div class="panel-body" style="padding-bottom: 0;">
			<div class="dataTable_wrapper">
				<table class="table table-striped table-bordered" style="margin-bottom:0; font-size:12px">
					<thead>
						<tr style="background-color:#F8F8EF">
							<th class="text-center" colspan="14">
								투자자 &nbsp;&nbsp;
								<a href="/adm/repayment/investor_amount_check.php?prd_idx=<?=$prd_idx?>" target="_blank" style="color:#FF2222"><< 투자금, 예치금 비교 >></a>
							</th>
						</tr>
						<tr style="background-color:#F8F8EF">
							<td class="text-center">NO.</td>
							<td class="text-center">투자<br/>번호</td>
							<td class="text-center">회원<br/>번호</td>
							<td class="text-center">ID</td>
							<td class="text-center">성명.상호명<? if($_SESSION['ss_accounting_admin']) { ?><br>(주민.사업자번호)<? } ?></td>
							<td class="text-center">투자금</td>
							<td class="text-center">투자구분</td>
							<td class="text-center">최종투자일시</td>
							<td class="text-center">수취방식</td>
							<td class="text-center">지급은행</td>
							<td class="text-center">계좌번호</td>
							<td class="text-center">투자취소</td>
						</tr>
					</thead>
					<tbody>
<?
	$plus_day += $last_day;

	for($j=0,$num=$invest_count; $j<$invest_count; $j++,$num--)
	{

			$where = " AND product_idx = '".$PRDT['idx']."' AND member_idx = '".$INVEST[$j]['mb_no']."' AND invest_state = 'Y'";

			$sqlx = "SELECT insert_datetime FROM cf_product_invest WHERE 1 " . $where;
			$TMP = sql_fetch($sqlx);

			$sqlx2 = "
				SELECT
					(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE 1 $where AND is_auto_invest='') AS self_invest_count,
					(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE 1 $where AND is_auto_invest='1') AS auto_invest_count";
			$TMP2 = sql_fetch($sqlx2);
			//print_r($TMP2); echo "<br>\n";

			//수취계좌 출력
			if($INVEST[$j]['receive_method']=='1') {
				$receive_method = "환급계좌";
				$bank           = $BANK[$INVEST[$j]['bank_code']];
				$account_num    = $INVEST[$j]['account_num'];
			}
			else if($INVEST[$j]['receive_method']=='2') {
				$receive_method = '<font color="#FF2222">예치금</font>';
				$bank           = $BANK[$INVEST[$j]['va_bank_code2']];
				$account_num    = $INVEST[$j]['virtual_account2'];
			}
			else {
				$receive_method = "미지정";
				$bank           = "";
				$account_num    = "";
			}

			$INVEST[$j]['mb_name'] = ($INVEST[$j]['member_type']=='2') ? $INVEST[$j]['mb_co_name'] : $INVEST[$j]['mb_name'];
			$INVEST[$j]['jumin']   = ($INVEST[$j]['member_type']=='2') ? $INVEST[$j]['mb_co_reg_num'] : @getJumin($INVEST[$j]['member_idx']);

			$bgcolor = ($INVEST[$j]['member_type']=='2') ? '#ffffcc' : '#FFFFFF';
		//$bgcolor = ($INVEST[$j]['is_creditor']=='Y') ? '#FCE4D6' : $bgcolor;

			$invest_type  = ($INVEST[$j]['is_advance_invest']=='Y') ? '사전투자' : '일반투자';

			$invest_type2 = "";
			$invest_type2 = "자동 {$TMP2['auto_invest_count']}건, 직접 {$TMP2['self_invest_count']}건";

			if($INVEST[$j]['insidebank_after_trans_target']=='1') $bgcolor = '#53B5DC';		//신한 예치금 이전 대상자 플래그

			$cancel_button = "";
			if($PRDT['state']=='') {
				if($INVEST[$j]['invest_state']=='Y') {
					$cancel_button = "<button type='button' class='btn btn-sm btn-danger' onClick=\"investCancel('".$INVEST[$j]['idx']."');\">투자취소</button>";
				}
			}

?>
						<tr style="background:<?=$bgcolor?>">
							<td align="center"><?=$num?></td>
							<td align="center"><?=$INVEST[$j]['idx']?></td>
							<td align="center"><?=$INVEST[$j]['member_idx']?></td>
							<td align="center">
								<a href="<?=$_SERVER['PHP_SELF']?>?idx=<?=$prd_idx?>&mb_id=<?=$INVEST[$j]['mb_id']?>"><?=$INVEST[$j]['member_idx']?></a><br>
								<a href="/adm/member/member_view.php?&mb_id=<?=$INVEST[$j]['mb_id']?>#ft" class="btn btn-info" style="font-size:11px; line-height:11px; padding:3px 4px;">전체투자내역</a>
							</td>
							<td align="center">
								<a href="javascript:;" onClick="balance_check(<?=$INVEST[$j]['member_idx']?>)" style="color:blue"><?=$INVEST[$j]['mb_name']?></a>
								<? if($_SESSION['ss_accounting_admin']) { echo '<br>('.$INVEST[$j]['jumin'].')'; } ?>
							</td>
							<td align="right"><span style="cursor:pointer" onClick="balance_check(<?=$INVEST[$j]['member_idx']?>);"><?=number_format($INVEST[$j]['amount'])?></span></td>
							<td align="center"><?=$invest_type?><br/><?=$invest_type2?></td>
							<td align="center"><?=substr($TMP['insert_datetime'],0,16)?></td>
							<td align="center"><?=$receive_method?></td>
							<td align="center"><?=$bank?></td>
							<td align="center"><?=$account_num?></td>
							<td align="center"><?=$cancel_button?></td>
						</tr>
<?
	}		//	end if( in_array($PRDT['state'], array('','3','6')) )
?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<script>
	investCancel = function(idx) {
		if( confirm('투자번호: ' + idx
			        + '\n\n해당 투자내역 취소 및 투자된 예치금이 반환되며,\n'
		          + '모집이 완료된 상품인 경우, 모집중인 상태로 전환됩니다.\n\n'
		          + '진행하시겠습니까?') ) {

			$.ajax({
				url: '/adm/repayment/invest_cancel.ajax.php',
				type: 'post',
				dataType: 'json',
				data:{
					invest_idx: idx
				},
				success: function(data) {
					if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
					else if(data.result=='ERROR') {
						if(data.message=='LOGIN_PLEASE') {
							window.location.replace('/');
						}
						else {
							alert(data.message);
						}
					}
				},
				beforeSend: function() { loading('on'); },
				complete: function() { loading('off'); },
				error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\ninvest_cancel.ajax.php"); }
			});

		}
	}
	</script>
<?
}		// end if( in_array($PRDT['state'], array('1','2','4','5','7','8','9')) )
?>

</div><!-- /.row -->

<style>
#divBillDetail { display:none; position:fixed; z-index:1000000; width:100%;height:100%; left:0; top:0; min-width:1000px; min-height:500px; }
</style>
<div id="divBillDetail"></div>
<script>
openBillDetail = function(prd_idx, turn, member_idx, is_overdue, mode) {
	$.blockUI({
		message: $('#divBillDetail'),css:{ 'border':'0', 'position':'fixed' },
	});
	$('#divBillDetail').draggable();

	$.ajax({
		url: 'ajax.bill_detail.php',
		type: 'post',
		data:{
			prd_idx: prd_idx,
			turn: turn,
			member_idx: member_idx,
			is_overdue: is_overdue,
			mode: mode
		},
		success: function(data) {
			$('#divBillDetail').empty();
			$('#divBillDetail').html(data);
		},
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\najax.bill_detail.php"); }
	});
}
</script>


<!-- 인사이드뱅크 데이터 전송요청 창 //-->
<div id="repay_request_div" style="position:fixed; z-index:1; top:1px; left:1px; width:100%; height:100%; display:none;"></div>
<!-- 인사이드뱅크 데이터 전송요청 창 //-->

<script>
listToggle = function(no) {
	$field  = $('#list_area' + no);
	$button = $('#list_button' + no);

	$button.removeClass();

	if($field.css('display')=='block') {
		$button.html('내역보기 <span class="glyphicon glyphicon-list"></span>');
		$button.addClass('btn btn-xs btn-primary');
	}
	else {
		$button.html('접기 <span class="glyphicon glyphicon-minus"></span>');
		$button.addClass('btn btn-xs btn-default');
	}
	$field.toggle();
}

// 연체등록처리
$('#overdue_proc_button').click(function() {
	$this = $(this);
	$idx_val       = $this.data('idx');
	$turn_val      = $this.data('turn');
	$ovd_sdate_val = $('#overdue_start_date').val();

	if($ovd_sdate_val == '') {
		alert('연체등록일자를 설정하십시요.');$('#overdue_start_date').focus();
	}
	else {
		if( !confirm("연체 등록 하시겠습니까?") ) { return; }
		// 플래그 갱신
		$.ajax({
			url: 'ajax.state_proc.php',
			type: 'post',
			dataType: 'json',
			data: {
				idx: $idx_val,
				turn: $turn_val,
				start_date: $ovd_sdate_val,
				state: '8'
			},
			success: function(data2) {
				if(data2.result=='SUCCESS') { alert('등록완료!\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data2.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\najax.state_proc.php"); }
		});
	}
});

// 연체명세서생성
overdueMakeBill = function(idx, turn, ovd_sdate, ovd_edate, data_drop, print_result) {
	$.ajax({
		url : 'make_bill_overdue.php',
		type: 'post',
		dataType: 'json',
		data: {
			idx: idx,
			turn: turn,
			start_date: ovd_sdate,
			end_date: ovd_edate,
			data_drop: data_drop,
			print_result : print_result
		},
		success: function(data) {
			if(data.result=='SUCCESS') {
				return data.result;
			}
			else {
				return data.message;
			}
		},
		beforeSend: function() { loading('on'); },
		complete: function() { loading('off'); },
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nmake_bill_overdue.php"); }
	});
}
</script>

<script>
requestPopup = function(arg) {
	if(typeof arg=='undefined' || arg==null || arg=='') {
		if( $('#repay_request_div').css('display')=='block' ) $('#repay_request_div').fadeOut();
	}
	else {
		if( $('#repay_request_div').css('display')=='none' ) {
			$('#repay_request_div').fadeIn();
			$.ajax({
				url:'./ajax_ib_repay_request.php',
				data:{repay_type:arg},
				type:'get',
				success: function(result) {
					$('#repay_request_div').html(result);
					$.ajax({
						url:'./ajax_ib_send_wait_list.php',
						data:{
							repay_type:arg,
							now_prd_idx:<?=$prd_idx?>
						},
						type:'get',
						success: function(result) {
							$('#ib_wait_list').html(result);
						}
					});
				},
				error: function() { alert('통신 에러입니다.\najax_ib_send_wait_list.php'); }
			});
		}
	}
}

///////////////////////////////////////
// 정규 원리금 처리
///////////////////////////////////////
loanInterestSuccess = function(idx, date, turn) {
	if(confirm("'대출이자 수급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'loan_interest_success',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				//$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nrepay_proc.php"); }
		});
	}
}

loanPrincipalSuccess = function(idx, date, turn) {
	if(confirm("'대출원금 수급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc.php',
			type: 'post',
			dataType: 'json',
			data:{
				action: 'loan_principal_success',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nrepay_proc.php"); }
		});
	}
}

divideReady = function(idx, date, turn) {
	if(confirm("지급원리금 배분요청대기 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'devide_ready',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nrepay_proc.php"); }
		});
	}
}

loanInterestGive = function(idx, date, turn) {
	if(confirm("원리금 지급을 시작 하시겠습니까?")) {
		$.ajax({
			url : 'repay_proc.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'loan_interest_give',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nrepay_proc.php"); }
		});
	}
}

investGiveSuccess = function(idx, date, turn) {
	if(confirm("'투자수익금 지급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc.php',
			type: 'post',
			dataType: 'json',
			data:{
				action: 'invest_give_success',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nrepay_proc.php"); }
		});
	}
}

investPrincipalGiveSuccess = function(idx, date, turn) {
	if(confirm("'투자원금 지급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc.php',
			type: 'post',
			dataType: 'json',
			data:{
				action: 'invest_principal_give_success',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\nrepay_proc.php"); }
		});
	}
}


///////////////////////////////////////
// 연체이자 처리
///////////////////////////////////////
overdueRcvSuccess = function(idx, date, turn) {
	if(confirm("'연체이자 수급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc_overdue.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'overdue_rcv_success',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_overdue.php"); }
		});
	}
}

overdueDivideReady = function(idx, date, turn) {
	if(confirm("연체이자 지급요청대기 처리 하시겠습니까?")) {
		$.ajax({
			url : 'repay_proc_overdue.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'overdue_devide_ready',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				if(data.result=='SUCCESS') {
					alert(data.message);
					window.location.reload();
				}
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_overdue.php"); }
		});
	}
}

overdueGive = function(idx, date, turn, turn_sno) {
	if(confirm("연체이자 지급을 시작 하시겠습니까?")) {
		$.ajax({
			url : 'repay_proc_overdue.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'overdue_give',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_overdue.php"); }
		});
	}
}

overdueGiveSuccess = function(idx, date, turn) {
	if(confirm("연체이자 지급완료 처리 하시겠습니까?")) {
		$.ajax({
			url : 'repay_proc_overdue.php',
			type: 'post',
			dataType: 'json',
			data:{
				action: 'overdue_give_success',
				idx: idx,
				date: date,
				turn: turn
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_overdue.php"); }
		});
	}
}


///////////////////////////////////////
// 일부상환 처리
///////////////////////////////////////
partialPrincipalSuccess = function(idx, turn, turn_sno) {
	if(confirm("'일부상환 수급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: './repay_proc_partial.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'partial_principal_success',
				idx: idx,
				turn: turn,
				turn_sno: turn_sno
			},
			success: function(data) {
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_partial.php"); }
		});
	}
}

partialDivideReady = function(idx, date, turn, turn_sno) {
	if(confirm(" 원리금 지급요청대기 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc_partial.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'partial_devide_ready',
				idx: idx,
				date: date,
				turn: turn,
				turn_sno: turn_sno
			},
			success: function(data) {
				if(data.result=='SUCCESS') {
					alert(data.message);
					window.location.reload();
				}
				else {
					alert(data.message);
				}
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_partial.php"); }
		});
	}
}

partialPrincipalGive = function(idx, date, turn, turn_sno) {
	if(confirm("일부상환 지급을 시작 하시겠습니까?")) {
		$.ajax({
			url : 'repay_proc_partial.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'partial_give',
				idx: idx,
				date: date,
				turn: turn,
				turn_sno: turn_sno
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_partial.php"); }
		});
	}
}

partialPrincipalGiveSuccess = function(idx, turn, turn_sno) {
	if(confirm("'일부상환 지급완료' 처리 하시겠습니까?")) {
		$.ajax({
			url: 'repay_proc_partial.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'partial_principal_give_success',
				idx: idx,
				turn: turn,
				turn_sno: turn_sno
			},
			success: function(data) {
				if(data.result=='SUCCESS') { alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\nrepay_proc_partial.php"); }
		});
	}
}
</script>

<script>
// 세금계산서 발행
taxInvoiceRequest = function(doc_type, idx, turn, overdue, turn_sno) {
	if(doc_type=='c') {
		_doc_type = '세금계산서';
		url = 'tax_invoice_request_c.php';
	}
	else {
		_doc_type = '현금영수증';
		url = 'tax_invoice_request_p.php';
	}

	if(overdue=='overdue') {
		msg = '연체금상환(' + turn + '회차)건의 플랫폼이용료에 관한 ' + _doc_type + ' 발행을 실시합니다. 처리 하시겠습니까?';
	}
	else {
		msg = '원리금상환' + turn + '회차의 플랫폼이용료에 관한 ' + _doc_type + ' 발행을 실시합니다. 처리 하시겠습니까?';
	}

	if(confirm(msg)) {
		$.ajax({
			url: url,
			type: 'post',
			dataType:'json',
			data:{
				idx: idx,
				turn: turn,
				overdue: overdue,
				turn_sno: turn_sno,
				doc_type: doc_type
			},
			success: function(data) {
				$('#ajax_return_txt').val(data.result);
				if(data.result=='SUCCESS') {
					alert('정상 처리 완료되었습니다.\n\n<?=$page_reload_msg?>'); window.location.reload();
				}
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\ntax_invoice_request"); }
		});
	}
}

// 부분상환등록
registPartialRedemption = function(date_obj, amount_obj, idx, turn) {

	var date = $('#'+date_obj).val();
	var amount = $('#'+amount_obj).val()

	if(date=='') {
		alert('상환일자를 선택하십시요.');
		return;
	}

	amount_length = amount.length;

	if(amount_length > 0) {
		for(i=0; i<amount_length; i++) {
			amount = amount.replace(',','');
		}
	}
	else {
		alert('상환금액을 입력하십시요.');
		return;
	}

	if(confirm('상환내역을 등록하시겠습니까?')) {
		$.ajax({
			url: 'partial_redemption.proc.php',
			type: 'post',
			dataType: 'json',
			data: {
				mode: 'new',
				idx: idx,
				turn: turn,
				amount: amount,
				date: date
			},
			success: function(data) {
				console.log(data);
				if(data.result=='SUCCESS') {
					alert('정상 처리 완료되었습니다.\n정산내역을 재산정 하십시요.');
					$('#make_bill_button').focus();
				}
				else {
					alert(data.message);
				}
			},
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.\n\npartial_redemption.proc.php"); }
		});
	}

}

<? if($loaner_vacct_drop_button) { ?>
loanerVacctDrop = function(idx) {
	$.ajax({
		url: 'ajax.loaner_vacct.proc.php',
		type: 'post',
		dataType: 'json',
		data: {mode:'drop', idx:idx,},
		success: function(data) {
			if(data.result=='SUCCESS') {
				alert('해제 완료 되었습니다.');
				window.location.reload();
			}
			else {
				alert(data.message);
			}
		},
		error: function(e) { console.log(e); }
	});
}
<? } ?>

// 팝업 닫기
popupClose = function() {
	$.unblockUI();
	return false;
}
</script>

<?
unset($INI);
unset($REPAY);
unset($REPAY_SUM);


include_once (G5_ADMIN_PATH.'/admin.tail.php');

sql_close();
?>
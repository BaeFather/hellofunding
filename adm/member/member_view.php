<?
$sub_menu = '200100';
include_once('./_common.php');
include_once(G5_PATH.'/mypage/crypt.php');
include_once(G5_LIB_PATH.'/insidebank.lib.php');

auth_check($auth[$sub_menu], "w");

while( list($k, $v) = each($_REQUEST) ) { if( !is_array(${$k}) ) ${$k} = trim($v); }

$html_title = "회원 상세";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');


$ROW = sql_fetch("SELECT mb_no, member_group, mb_level FROM g5_member WHERE mb_id='".$mb_id."'");

if( date('H:i') < $CONF['P2PCTR_PAUSE']['STIME'] && date('H:i') > $CONF['P2PCTR_PAUSE']['ETIME'] ) {
	if( $ROW['mb_no'] && $ROW['member_group']=='F' && in_array($ROW['mb_level'], array('1','2','3','4','5')) ) {
		////////////////////////////////////
		// 투자한도 업데이트 실행
		////////////////////////////////////
		$exec_str = "/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/get_p2pctr_limit_amt.exec.php " .  $ROW['mb_no'];
		$exec_result = shell_exec($exec_str);
	}
}

$mb = get_member($mb_id);
//print_rr($mb, 'font-size:12px');

if (!$mb['mb_id']) alert('존재하지 않는 회원자료입니다.');

$mb['auto_inv_conf'] = get_auto_inv_conf($mb['mb_no']);

/*
$row = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM IB_auth_withdrawal WHERE mb_no='".$mb['mb_no']."' AND account_num='".$mb['account_num']."' ORDER BY rdate DESC LIMIT 1");
$auth_withdrawal = $row['cnt'];
*/
$row = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM IB_auth_withdrawal WHERE mb_no='".$mb['mb_no']."'");
$auth_withdrawal = $row['cnt'];


switch(get_text($mb['member_type'])) {
	case '2' : $member_type_text = '기업회원'; break;
	case '3' : $member_type_text = 'SNS회원';  break;
	case '1' :
	default  : $member_type_text = '개인회원'; break;
}

$member_type_text.= ($mb['is_creditor']=="Y") ? " (대부업)" : "";

$query_str = $_SERVER["QUERY_STRING"];

// 예치금
$sum_point = get_point_sum($mb['mb_id']);
$member_deposit_point = $sum_point;

// 충전금액 합계 (세틀뱅크)
$sql = "
		SELECT
			IFNULL(SUM(A.tr_amt), 0) total_charge_amount
		FROM
			vacs_ahst A
		INNER JOIN
			g5_member B ON A.iacct_no=B.virtual_account
		LEFT JOIN
			vacs_vact C ON B.virtual_account=C.acct_no
		WHERE
			B.mb_id='".$mb['mb_id']."' AND C.acct_st='9'";
$result = sql_fetch($sql);
$total_charge_amount = $result['total_charge_amount'];

// 충전금액 합계 (신한은행)
$sql = "
		SELECT
			IFNULL(SUM(A.TR_AMT), 0) total_charge_amount
		FROM
			IB_FB_P2P_IP A
		INNER JOIN
			g5_member B
		ON
			A.ACCT_NB=B.virtual_account2
		LEFT JOIN
			IB_vact C
		ON
			B.virtual_account2=C.acct_no
		WHERE
			B.mb_id='".$mb['mb_id']."'  AND C.acct_st='1'";
$result = sql_fetch($sql);
$total_charge_amount2 = $result['total_charge_amount'];

// 일반 투자금 누적합계
$sql = "SELECT IFNULL(SUM(amount), 0) total_invest_amout FROM cf_product_invest WHERE member_idx='".$mb['mb_no']."' AND invest_state='Y'";
$result = sql_fetch($sql);
$total_invest_amount = $result['total_invest_amout'];


// 투자한도내의 투자잔액 합계
$sql = "SELECT IFNULL(SUM(amount), 0) total_invest_amout FROM cf_product_invest WHERE member_idx='".$mb['mb_no']."' AND invest_state='Y' AND insert_date>'2017-05-28' AND (ISNULL(is_return) OR is_return='N')";
$result = sql_fetch($sql);
$total_invest_amount2 = $result['total_invest_amout'];


// 이벤트 투자금 합계
$sql = "SELECT IFNULL(SUM(amount), 0) total_event_invest_amout FROM cf_event_product_invest WHERE member_idx='".$mb['mb_no']."' AND invest_state='Y'";
$result = sql_fetch($sql);
$total_invest_amount = $total_invest_amount + $result['total_event_invest_amout'];


// 미달성환불합계
$sql = "
	SELECT
		IFNULL(SUM(A.amount),0) AS 'total_return_price'
	FROM
		cf_product_invest AS A
	LEFT JOIN
		cf_product B
	ON
		A.product_idx=B.idx
	LEFT JOIN
		g5_member C
	ON
		A.member_idx=C.mb_no
	WHERE 1=1
		AND B.state=''
		AND B.end_datetime<NOW()
		AND B.invest_end_date=''
		AND A.invest_state='Y'
		AND	B.end_date > SUBSTRING(NOW(),1,10)
		AND	C.mb_id='{$mb['mb_id']}'";

$result = sql_fetch($sql);
$total_return_price = $result['total_return_price'];


// 출금합계
$sql = "SELECT SUM(req_price) total_withdraw_price FROM g5_withdrawal WHERE state='2' AND mb_id='".$mb['mb_id']."'";
$result = sql_fetch($sql);
$total_withdraw_price = $result['total_withdraw_price'];


// 예치금 입출금 내역
$sql = "
		SELECT
			tbl.regdate, tbl.orderReg, tbl.price1, tbl.price1_ib, tbl.price2, tbl.price3, tbl.price4, tbl.price5, tbl.state
		FROM
		(
				SELECT
					CONCAT( SUBSTR(va.tr_il, 1, 4), '-', SUBSTR(va.tr_il, 5, 2), '-', SUBSTR(va.tr_il, 7, 2) ) AS 'regdate',
					CONCAT( SUBSTR(va.tr_il, 1, 4), '-', SUBSTR(va.tr_il, 5, 2), '-', SUBSTR(va.tr_il, 7, 2), ' ', SUBSTR(va.tr_si, 1, 2), ':', SUBSTR(va.tr_si, 3, 2), ':', SUBSTR(va.tr_si, 5, 2) ) AS 'orderReg',
					va.tr_amt AS 'price1',
					'' AS 'price1_ib',
					'' AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					vacs_ahst va
				INNER JOIN
					vacs_vact vv  ON va.iacct_no=vv.acct_no
				LEFT JOIN
					g5_member mem  ON va.iacct_no=mem.virtual_account
				WHERE 1=1
					AND mem.mb_id='".$mb['mb_id']."'
					AND vv.acct_st='9'

			UNION ALL

				SELECT
					CONCAT( SUBSTR(ibva.ERP_TRANS_DT, 1, 4), '-', SUBSTR(ibva.ERP_TRANS_DT, 5, 2), '-', SUBSTR(ibva.ERP_TRANS_DT, 7, 2) ) AS 'regdate',
					CONCAT( SUBSTR(ibva.ERP_TRANS_DT, 1, 4), '-', SUBSTR(ibva.ERP_TRANS_DT, 5, 2), '-', SUBSTR(ibva.ERP_TRANS_DT, 7, 2), ' ', SUBSTR(ibva.ERP_TRANS_DT, 9, 2), ':', SUBSTR(ibva.ERP_TRANS_DT, 11, 2), ':', SUBSTR(ibva.ERP_TRANS_DT, 13, 2) ) AS 'orderReg',
					'' AS 'price1',
					ibva.TR_AMT AS 'price1_ib',
					'' AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					IB_FB_P2P_IP ibva
				INNER JOIN
					IB_vact ibvv  ON ibva.ACCT_NB=ibvv.acct_no
				LEFT JOIN
					g5_member mem  ON ibva.ACCT_NB=mem.virtual_account2
				WHERE 1=1
					AND mem.mb_id='".$mb['mb_id']."'
					AND ibvv.acct_st='1'

			UNION ALL

				SELECT
					insert_date AS 'regdate',
					concat(A.insert_date, ' ', A.insert_time) AS 'orderReg',
					'' AS 'price1',
					'' AS 'price1_ib',
					amount AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_product_invest AS A
				LEFT JOIN
					g5_member AS B  ON A.member_idx=B.mb_no
				WHERE 1=1
					AND invest_state='Y'
					AND B.mb_id='".$mb['mb_id']."'

			UNION ALL

				SELECT
					A.insert_date AS 'regdate',
					CONCAT(A.insert_date, ' ', A.insert_time) AS 'orderReg',
					'' AS 'price1',
					'' AS 'price1_ib',
					'' AS 'price2',
					A.amount AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_product_invest AS A
				LEFT JOIN
					cf_product AS B  ON A.product_idx=B.idx
				LEFT JOIN
					g5_member AS C  ON A.member_idx=C.mb_no
				WHERE 1=1
					AND B.state=''
					AND B.end_datetime < NOW()
					AND B.invest_end_date=''
					AND A.invest_state='Y'
					AND B.end_date > SUBSTRING(NOW(),1,10)
					AND C.mb_id='".$mb['mb_id']."'

			UNION ALL

				SELECT
					insert_date AS 'regdate',
					concat(A.insert_date, ' ', A.insert_time) AS 'orderReg',
					'' AS 'price1',
					'' AS 'price1_ib',
					amount AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_event_product_invest AS A
				LEFT JOIN
					g5_member AS B  ON A.member_idx=B.mb_no
				WHERE
					invest_state='Y'
					AND B.mb_id='".$mb['mb_id']."'

			UNION ALL

				SELECT
					A.insert_date AS 'regdate',
					CONCAT(A.insert_date, ' ', A.insert_time) AS 'orderReg',
					'' AS 'price1',
					'' AS 'price1_ib',
					'' AS 'price2',
					A.amount AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					'' AS 'price5'
				FROM
					cf_event_product_invest AS A
				LEFT JOIN
					cf_event_product AS B  ON A.product_idx=B.idx
				LEFT JOIN
					g5_member AS C  ON A.member_idx=C.mb_no
				WHERE 1=1
					AND B.state=''
					AND B.end_datetime<NOW()
					AND B.invest_end_date=''
					AND A.invest_state='Y'
					AND B.end_date > SUBSTRING(NOW(),1,10)
					AND C.mb_id='".$mb['mb_id']."'

			UNION ALL

				SELECT
					date(regDate) as 'regdate',
					regDate AS 'orderReg',
					'' AS 'price1',
					'' AS 'price1_ib',
					'' AS 'price2',
					'' AS 'price3',
					req_price AS 'price4',
					state AS 'state',
					'' AS 'price5'
				FROM
					g5_withdrawal
				WHERE 1=1
					AND state IN('1', '2')
					AND mb_id='".$mb['mb_id']."'

			UNION ALL

				SELECT
					SUBSTRING(po_datetime,1,10) AS 'regdate',
					po_datetime AS 'orderReg',
					'' AS 'price1',
					'' AS 'price1_ib',
					'' AS 'price2',
					'' AS 'price3',
					'' AS 'price4',
					'' AS 'state',
					po_point AS 'price5'
				FROM
					g5_point
				WHERE 1=1
					AND mb_id='".$mb['mb_id']."'
					AND po_content IN('예치금 지급', '예치금 차감')
		) AS tbl
		ORDER BY
			tbl.orderReg DESC";
//echo $sql;
$result = sql_query($sql);
$point_list = array();
while($list = sql_fetch_array($result)){
	array_push($point_list, $list);
}
sql_free_result($result);

$print_mb_name = ($_SESSION['ss_accounting_admin']) ? $mb['mb_name'] : hanStrMasking($mb['mb_name']);
$jumin = ($_SESSION['ss_accounting_admin']) ? getJumin($mb['mb_no']) : "";

$m_hp = $mb['mb_hp'];
$m_jumin = $jumin;
$m_account_num = $mb['account_num'];
$m_virtual_account2 = $mb['virtual_account2'];
if (!$_SESSION['ss_accounting_admin']) {
	if ($mb['mb_hp']) $mb['mb_hp'] = substr($mb['mb_hp'],0,strlen($mb['mb_hp'])-4)."●●●●";
	if ($m_jumin) $m_jumin = substr($m_jumin,0,strlen($m_jumin)-6)."●●●●●●";
	if ($mb['account_num']) $mb['account_num'] = substr($mb['account_num'],0,strlen($mb['account_num'])-4)."●●●●";
	if ($mb['virtual_account2']) $mb['virtual_account2'] = substr($mb['virtual_account2'],0,strlen($mb['virtual_account2'])-4)."●●●●";
}

if($mb['mb_sex']=='m') {
	$print_mb_sex = '남';
}
else if($mb['mb_sex']=='w') {
	$print_mb_sex = '여';
}
else {
	$print_mb_sex = '';
}

$blind_mb_hp    = (strlen($mb['mb_hp']) > 4) ? substr($mb['mb_hp'], 0, strlen($mb['mb_hp'])-4) . "●●●●" : $mb['mb_hp'];
$blind_acct_num = (strlen($mb['account_num']) > 4) ? substr($mb['account_num'],0,strlen($mb['account_num'])-4) . "●●●●" : $mb['account_num'];
$blind_jumin    = (strlen($jumin) > 6) ? substr($jumin,0,strlen($jumin)-6)."●●●●●●" : strlen($jumin);
$blind_cms_num  = (strlen($mb['virtual_account2']) > 4) ? substr($mb['virtual_account2'],0,strlen($mb['virtual_account2'])-4) . "●●●●" : $mb['virtual_account2'];

if($_SESSION['ss_accounting_admin']) {
	$full_jumin    = $jumin;
	$full_mb_hp    = $mb['mb_hp'];
	$full_acct_num = $mb['account_num'];
	$full_cms_num  = $mb['virtual_account2'];

	$copy_jumin    = "onClick=\"copy_trackback('".$full_jumin."');\"";
	$copy_mb_hp    = "onClick=\"copy_trackback('".$full_mb_hp."');\"";
	$copy_acct_num = "onClick=\"copy_trackback('".$full_acct_num."');\"";
	$copy_cms_num  = "onClick=\"copy_trackback('".$full_cms_num."');\"";
}
else {
	$full_jumin = $full_mb_hp = $full_acct_num = $full_cms_num  = '';
	$copy_jumin = $copy_mb_hp = $copy_acct_num = $copy_cms_num  = '';
}

if($mb['mb_name']==$mb['bank_private_name']) {
	$print_bank_private_name = ($_SESSION['ss_accounting_admin']) ? $mb['bank_private_name'] : hanStrMasking($mb['bank_private_name']);
}


// 출금가능금액 체크
$WITHDRAWAL_POSIBLE_AMOUNT = array();
$WITHDRAWAL_POSIBLE_AMOUNT['local'] = $mb['withdrawal_posible_amount'];

//////////////////////////////////
// 출금가능금액 조회 (신한은행)
//////////////////////////////////
if($mb['bank_code'] && $mb['account_num'] && $mb['va_bank_code2'] && $mb['virtual_account2']) {
	// 고객 투자정보조회(4100)
	$ARR['REQ_NUM'] = "041";
	$ARR['CUST_ID'] = $mb['mb_no'];
	$IB_RESULT = insidebank_request('256', $ARR);
	if($IB_RESULT['RCODE']=='00000000') {
		$WITHDRAWAL_POSIBLE_AMOUNT['bank'] = $IB_RESULT['WITH_AMT'];
	}
}
$withdrawal_posible_amount = min($WITHDRAWAL_POSIBLE_AMOUNT);		// 출금가능금액 확정

?>

<style>
#paging_span { margin-top:10px;  text-align:center; }
#paging_span span.arrow { padding:0; border:0; line-height:0; }
#paging_span span { display:inline-block; min-width:30px; padding:0 5px; color:#585657; line-height:30px; border:1px solid #d0d0d0; cursor:pointer }
#paging_span span.now { color:#fff; background-color:#284893; border-color:#284893; cursor:default }

.tmp {border:0; background:#EEEBD9; text-align:center }
.tmp1 {border:0; background:#F5F5F5; color:#000000;}
.tmp1_1 {border:0; background:#FFEEEE; color:brown;}

.tbl_wrap table { font-size:14px; }
</style>

<div class="tbl_head02 tbl_wrap">

	<h3>○ 가입정보</h3>
	<table width="100%">
		<colgroup>
			<col width="15%">
			<col width="35%">
			<col width="15%">
			<col width="35%">
		</colgroup>
		<tr>
			<th scope="col">회원번호/아이디</th>
			<td align="center"><?=$mb['mb_no']?> / <?=get_text($mb['mb_id']);?></td>
			<th scope="col" class="text-center">회원유형</th>
			<td align="center"><?=$member_type_text?><?=$mb['pid']=="toomics"?" (투믹스 회원번호 ".$mb['mb_3'].")":""?></td>
		</tr>
<?
if($mb['member_type']=='1') {

	$invest_possible_amount = (in_array($mb['member_investor_type'], array('1','2'))) ? price_cutting($mb['invest_possible_amount'])."원" : "제한 없음";
	$invest_possible_amount_prpt = (in_array($mb['member_investor_type'], array('1','2'))) ? price_cutting($mb['invest_possible_amount_prpt'])."원" : "제한 없음";

?>
		<tr>
			<th scope="col" class="text-center">투자자 유형</th>
			<td align="center">
				<?=$INDI_INVESTOR[$mb['member_investor_type']]['title']?>
				<? if($mb['member_type']=='1' && $mb['member_investor_type']>'1') { ?>&nbsp; <button type="button" onClick="location.href='investor_type_req.php?key_search=B.mb_id&keyword=<?=$mb['mb_id']?>'">승인이력검색</button><? } ?>
			</td>
			<th scope="col" class="text-center">투자한도</th>
			<td align="center">
				<table>
					<tr>
						<td style="padding:1px;width:20%;text-align:center;background:#888;color:#fff">총한도액</td>
						<td colspan="3" style="padding:1px 10px;width:30%;text-align:right;"><?=price_cutting($INDI_INVESTOR[$mb['member_investor_type']]['site_limit'])?>원</td>
					</tr>
					<tr>
						<td style="padding:1px;width:20%;text-align:center;background:#888;color:#fff">투자잔액</td>
						<td colspan="3" style="padding:1px 10px;width:30%;text-align:right;"><?=price_cutting($mb['ing_invest_amount'])?>원</td>
					</tr>
					<tr>
						<td style="padding:1px;width:20%;text-align:center;background:#888;color:#fff">잔여한도</td>
						<td colspan="3" style="padding:1px 10px;text-align:right;"><?=$invest_possible_amount?></td>
					</tr>
					<? if($mb['member_investor_type']=='1') { ?>
					<tr>
						<td style="padding:1px;width:20%;text-align:center;background:#EEE">부동산.주택담보</td>
						<td style="padding:1px 10px;width:30%;text-align:right;"><?=$invest_possible_amount_prpt?></td>
						<td style="padding:1px;width:20%;text-align:center;background:#EEE">동산.헬로페이</td>
            <td style="padding:1px 10px;width:30%;text-align:right;"><?=price_cutting($mb['invest_possible_amount_ds'])?>원</td>
					</tr>
					<? } ?>
				</table>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">성명</th>
			<td align="center">
				<?=$print_mb_name?><? if( in_array($member['mb_id'], $CONF['SECRET_LOGIN_USER']) ) { ?><a href="javascript:;" onClick="if(confirm('<?=$mb['mb_name']?> 회원에게 비상경계경보를 발령합니다.\n중대한 사안이므로 신중히 결정하십시요.\n\n진행하시겠습니까?')){ location.replace('/adm/simple_login.php?mb_no=<?=$mb['mb_no']?>'); }">.</a><? } ?>
			</td>

			<th scope="col" class="text-center"><?=($_SESSION['ss_accounting_admin'])?'주민등록번호':'생년월일'?></th>
			<td align="center">
<?
if($_SESSION['ss_accounting_admin']) {
	if($full_jumin) {
		echo "<span id=\"jumin0\" onMouseOver=\"swapText('jumin0','{$full_jumin}');\" onMouseOut=\"swapText('jumin0','{$blind_jumin}');\" {$copy_jumin} style=\"cursor:pointer\">{$blind_jumin}</span> (만" . getFullAge($mb['mb_birth']) . "세, {$print_mb_sex})";
	}
	else {
		if($mb['mb_birth']) echo "생년월일 : " . $mb['mb_birth'] . ", (만" . getFullAge($mb['mb_birth']) . "세, {$print_mb_sex})";
	}

	echo ($mb['tvtalk_userid']) ? decrypt($mb['mb_1'],'jumin') : "";

}
else {
	if($mb['mb_birth']) echo $mb['mb_birth'];
}
?>
			</td>
		</tr>
<?
	if($mb['is_creditor']=='Y') {
?>
		<tr>
			<th scope="col" class="text-center">상호명</th>
			<td align="center"><?=$mb['mb_co_name']?></td>
			<th scope="col" class="text-center">사업자등록번호</th>
			<td align="center"><?=$mb['mb_co_reg_num']?></td>
		</tr>
<?
	}

}
else {
?>
		<tr>
			<th scope="col" class="text-center">상호명</th>
			<td align="center"><?=$mb['mb_co_name']?></td>
			<th scope="col" class="text-center">사업자등록번호</th>
			<td align="center"><?=$mb['mb_co_reg_num']?></td>
		</tr>
		<tr>
			<th scope="col" class="text-center">담당자명</th>
			<td class="text-center"><?=$mb['mb_name']?></td>
			<th scope="col" class="text-center">투자잔액</th>
			<td class="text-center"><?=number_format($mb['ing_invest_amount'])?>원</td>
		</tr>
<?
}

if($member_group=='F' || ($member_group=='L' && $member_type=='1')) {
?>
		<tr>
			<th scope="col" class="text-center">휴대전화</th>
			<td align="center">
				<span id="hp0" onMouseOver="swapText('hp0','<?=$full_mb_hp?>');" onMouseOut="swapText('hp0','<?=$blind_mb_hp?>');" <?=$copy_mb_hp?> style="cursor:pointer"><?=$blind_mb_hp?></span>
			</td>
			<th scope="col" class="text-center">이메일</th>
			<td align="center"><?=get_text($mb['mb_email']);?></td>
		</tr>
<?
}
?>
		<tr>
			<th scope="col" class="text-center">주소</th>
			<td align="center">
				<? if($mb['zip_num']) { echo "(".$mb['zip_num'].")"; } ?>
				<?=get_text($mb['mb_addr1'])?>
				<?=get_text($mb['mb_addr2'])?>
				<?=get_text($mb['mb_addr3'])?>
			</td>
			<th scope="col" class="text-center">원리금 수취방식</th>
			<td align="center">
<?
	if($mb['receive_method']=='1') echo "환급계좌";
	else if($mb['receive_method']=='2') echo "예치금(가상계좌)";
	else echo "미지정";
?>
			</td>
		</tr>

		<tr>
			<th scope="col" class="text-center">환급계좌</th>
			<td align="center">
				<?=get_text($mb['bank_name'])?>
				<span id="acct0" onMouseOver="swapText('acct0','<?=$full_acct_num?>');" onMouseOut="swapText('acct0','<?=$blind_acct_num?>');" <?=$copy_acct_num?> style="cursor:pointer"><?=$blind_acct_num?></span>
				<?=get_text($print_bank_private_name)?><?=($mb['bank_private_name_sub']) ? '('.$mb['bank_private_name_sub'].')': ''?>
			</td>
			<th scope="col" class="text-center">가상계좌</th>
			<td align="center">
				신한계좌: <span id="cms0" onMouseOver="swapText('cms0','<?=$full_cms_num?>');" onMouseOut="swapText('cms0','<?=$blind_cms_num?>');" <?=$copy_cms_num?> style="cursor:pointer"><?=$blind_cms_num?></span>
				<? if($mb['va_bank_code'] && $mb['virtual_account'] && $mb['va_private_name']) { ?>
				<br/><font color="#aaa">세틀뱅크: <?=$BANK[$mb['va_bank_code']].' '.$mb['virtual_account'].' '.$mb['va_private_name'];?></font>
				<? } ?>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">메일수신</th>
			<td align="center"><?=($mb['mb_mailling']==1) ? '동의함' : '미동의'; ?></td>
			<th scope="col" class="text-center">SMS수신</th>
			<td align="center"><?=($mb['mb_sms'] == 1) ? '동의함' : '미동의'; ?></td>
		</tr>
		<tr>
			<th scope="col" class="text-center">가입정보</th>
			<td align="center" style="color:#aaa">
				date. <font color="black"><?=substr($mb['mb_datetime'], 0, 16)?></font> &nbsp;&nbsp;/&nbsp;&nbsp;
				ip. <font color="black"><?=$mb['mb_ip']?></font> &nbsp;&nbsp;/&nbsp;&nbsp;
				device. <font color="black"><?=($mb['device'])?strtoupper($mb['device']) : '불명';?></font>
			</td>
			<th scope="col" class="text-center">최종접속정보</th>
			<td align="center" style="color:#aaa">
				date. <font color="black"><?=substr($mb['mb_today_login'], 0, 16)?></font> &nbsp;&nbsp;/&nbsp;&nbsp;
				ip. <font color="black"><?=$mb['mb_login_ip']?></font>
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">투자위험고지</th>
			<td align="center"><?=($mb['invest_warning_agree']=='Y') ? '동의함' : '미동의'; ?></td>
			<th scope="col" class="text-center">예치금</th>
			<td align="center">
				<a href="javascript:;" onClick="balance_check(<?=$mb['mb_no']?>)" style="color:blue"><?=number_format($mb['mb_point'])?>원</a>
				(출금가능: <?=number_format($withdrawal_posible_amount)?>원)
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">첨부서류</th>
			<td align="center">
				<ul style="list-style:none">
				  <? if($mb['business_license']){ ?><li style="float:left;padding:0 2px 2px 0;"><a href="download.php?mb_no=<?=$mb['mb_no']?>&orderFile=business_license" class="btn btn-success" style="width:160px">사업자등록증</a></li><? } ?>
				  <? if($mb['bankbook'])        { ?><li style="float:left;padding:0 2px 2px 0;"><a href="download.php?mb_no=<?=$mb['mb_no']?>&orderFile=bankbook" class="btn btn-success" style="width:160px">통장사본</a></li><? } ?>
				  <? if($mb['loan_co_license']) { ?><li style="float:left;padding:0 2px 2px 0;"><a href="download.php?mb_no=<?=$mb['mb_no']?>&orderFile=loan_co_license" class="btn btn-success" style="width:160px">대부업등록증</a></li><? } ?>
				</ul>
				<ul style="list-style:none">
					<? if($mb['junior_doc1']) { ?><li style="float:left;padding:0 2px 2px 0;"><a href="download.php?mb_no=<?=$mb['mb_no']?>&orderFile=junior_doc1" class="btn btn-warning" style="width:160px">법정대리인동의서</a></li><? } ?>
				  <? if($mb['junior_doc2']) { ?><li style="float:left;padding:0 2px 2px 0;"><a href="download.php?mb_no=<?=$mb['mb_no']?>&orderFile=junior_doc2" class="btn btn-warning" style="width:160px">가족관계증명서</a></li><? } ?>
				  <? if($mb['junior_doc2']) { ?><li style="float:left;padding:0 2px 2px 0;"><a href="download.php?mb_no=<?=$mb['mb_no']?>&orderFile=junior_doc3" class="btn btn-warning" style="width:160px">법정대리인신분증사본</a></li><? } ?>
				</ul>
			</td>
			<th scope="col" class="text-center">즉시출금허용</th>
			<td class="text-center">
				<input type="radio" id="withdrawal" name="withdrawal" value="Y" <?=($auth_withdrawal)?'checked':''?> >예
				<input type="radio" id="withdrawal" name="withdrawal" value="N" <?=(!$auth_withdrawal)?'checked':''?> style="margin-left:30px">아니오
			</td>
		</tr>
		<tr>
			<th scope="col" class="text-center">자동투자설정</th>
			<td align="center">
		<?
		if (count($mb['auto_inv_conf'])>0) {
			?>
				<?//=number_format($mb['auto_inv_conf'][0]["setup_amount"])?> <!-- 원 &nbsp;&nbsp;  -->
			<?
			for ($mm=0 ; $mm<count($mb['auto_inv_conf']) ; $mm++) {
				//if ($mm<>0) echo " , ";
				echo $mb['auto_inv_conf'][$mm]["grp_title"]."=>".number_format($mb['auto_inv_conf'][$mm]["setup_amount"])." &nbsp;&nbsp;&nbsp; ";
			}
		} else echo "&nbsp;";
		?>
			</td>

			<th scope="row" class="text-center">투자설명서</th>
			<td class="text-center">
				<? if($mb['invested_mailling']==1){ ?>투자설명서 발급 동의<? } ?>
			</td>

		</tr>
	</table>

<? if($mb['pid']) { ?>
	<br/>
	<h3>○ 마케팅정보</h3>
	<table width="100%">
		<colgroup>
			<col width="15%">
			<col width="35%">
			<col width="15%">
			<col width="35%">
		</colgroup>
		<tr>
			<th scope="col" class="text-center">제휴사명</th>
			<td align="center"><?=$CONF['PARTNER'][$mb['pid']]['name']?></td>
			<th scope="col" class="text-center">제휴사발급코드</th>
			<td class="text-center"><?=$mb['mb_3']?></td>
		</tr>
	</table>
<? } ?>

	<br/>
	<table width="100%">
		<colgroup>
			<col width="15%">
			<col width="85%">
		</colgroup>
		<tr>
			<th scope="col">KYC</th>
			<td>
				<table>
					<colgroup>
						<col width="10%">
						<col width="15%">
						<col width="10%">
						<col width="15%">
						<col width="10%">
						<col width="15%">
						<col width="10%">
						<col width="15%">
					</colgroup>
					<tr align="center">
						<td style="background:#eee;">등록/갱신일</td><td><?=$mb['kyc_reg_dd']?></td>
						<td style="background:#eee;">승인일</td><td><?=$mb['kyc_allow_dd']?></td>
						<td style="background:#eee;">승인업데이트</td><td><?=$mb['kyc_allow_cnt']?>회</td>
						<td style="background:#eee;">승인만료일</td><td><?=$mb['kyc_next_dd']?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th scope="col">관리자메모</th>
			<td><?=nl2br($mb['mb_memo'])?></td>
		</tr>
	</table>

	<script>
	$('input:radio[name=withdrawal]').on('click', function() {
		if( confirm('즉시출금허용 설정 하시겠습니까?') ) {
			var cv = $('input:radio[name=withdrawal]:checked').val();

			$.ajax({
				url : "/adm/deposit_withdrawal/ajax.auth_withdrawal_proc.php",
				type: "POST",
				data: { action:'auth_regist', mb_no:'<?=$mb['mb_no']?>', checkval:cv },
				success: function(data) {
					//$('#ajax_return_txt_zone').css("display","block"); $('#ajax_return_txt').val(data);
					if(data=='ERROR-LOGIN') { location.href='/'; }
					else if(data=='UPDATE_SUCCESS') { alert('즉시 출금 허용 계좌 정보 수정 되었음.'); }
					else if(data=='DELETE_SUCCESS') { alert('즉시 출금 허용 설정 해제 되었음.'); }
					else if(data=='INSERT_SUCCESS') { alert('즉시 출금 허용 계좌 설정 되었음.'); }
					else if(data=='ALREADY_SET_VALUE') { /**/ }
					else { alert(data); }
				},
				error: function () {
					alert('통신오류 입니다. 잠시 후 다시 시도하십시오.');
				}
			});
		}
		else {
			return false;
		}
	});
	</script>

	<div class="text-right" style="margin-top:10px;"><a href="/adm/member/member_list.php?<?=$query_str?>" class="btn btn-md btn-info">목록</a></div>

	<div style="margin-top:50px;"><h1>활동내역 (투자정보)</h1></div>

	<h3>○ 예치금 현황</h3>
	<table width="100%" style="margin-bottom:30px;">
		<colgroup>
			<col style="width:12.5%">
			<col style="width:12.5%">
			<col style="width:12.5%">
			<col style="width:12.5%">
			<col style="width:12.5%">
			<col style="width:12.5%">
		<tr>
			<th scope="col" class="text-center" colspan="3">예치금 잔액</th>
			<th scope="col" class="text-center" colspan="2">입금 합계</th>
			<th scope="col" class="text-center" rowspan="2">투자금 합계</th>
			<th scope="col" class="text-center" rowspan="2">미달성환불 합계</th>
			<th scope="col" class="text-center" rowspan="2">출금 합계</th>
		</tr>
		<tr>
			<th scope="col" class="text-center">총액</th>
			<th scope="col" class="text-center">출금불가</th>
			<th scope="col" class="text-center">출금가능</th>
			<th scope="col" class="text-center">세틀뱅크</th>
			<th scope="col" class="text-center">신한은행</th>
		</tr>
		<tr>
			<td class="text-right"><font color="red"><?=number_format($member_deposit_point);?>원</font></td>
			<td class="text-right"><font color="brown"><?=number_format($mb['lock_amount']);?>원</font></td>
			<td class="text-right"><font color="blue"><?=number_format($mb['withdrawal_posible_amount']);?>원</font></td>

			<td class="text-right"><?=number_format($total_charge_amount);?>원</td>
			<td class="text-right"><?=number_format($total_charge_amount2);?>원</td>
			<td class="text-right"><?=number_format($total_invest_amount);?>원</td>
			<td class="text-right"><?=number_format($total_return_price);?>원</td>
			<td class="text-right"><?=number_format($total_withdraw_price);?>원</td>
		</tr>
	</table>

	<h3>○ 예치금 입출금 내역</h3>
	<div style="width:100%;max-height:200px; overflow-y:auto; border:1px solid #EEE; margin-bottom:30px;">
		<table width="100%">
			<colgroup>
				<col style="width:%">
				<col style="width:14.28%">
				<col style="width:14.28%">
				<col style="width:14.28%">
				<col style="width:14.28%">
				<col style="width:14.28%">
				<col style="width:14.28%">
			</colgroup>
			<tr>
				<th scope="col" class="text-center" rowspan="2">거래일시</th>
				<th scope="col" class="text-center" colspan="2">입금내역</th>
				<th scope="col" class="text-center" rowspan="2">투자금액</th>
				<th scope="col" class="text-center" rowspan="2">미달성환불</th>
				<th scope="col" class="text-center" rowspan="2">출금</th>
				<th scope="col" class="text-center" rowspan="2">관리자 지급/차감</th>
			</tr>
			<tr>
				<th scope="col" class="text-center">세틀뱅크</th>
				<th scope="col" class="text-center">신한은행</th>
			</tr>
<?
if($point_list != null){

	foreach($point_list as $Rows){
?>
			<tr>
				<td class="text-center"><?=$Rows['orderReg']?></td>
				<td class="text-right"><?=($Rows['price1']!='') ? number_format($Rows['price1']).'원' : ''; ?></td>
				<td class="text-right"><?=($Rows['price1_ib']!='') ? number_format($Rows['price1_ib']).'원' : ''; ?></td>
				<td class="text-right"><?=($Rows['price2']!='') ? number_format($Rows['price2']).'원' : ''; ?></td>
				<td class="text-right"><?=($Rows['price3']!='') ? number_format($Rows['price3']).'원' : ''; ?></td>
				<td class="text-right">
<?
		if($Rows['price4'] != '') echo number_format($Rows['price4']).'원';
		if($Rows['state'] == '1') echo " (예정) ";
?>
				</td>
				<td class="text-center"><?=($Rows['price5'] != '') ? number_format($Rows['price5']).'원' : ''; ?></td>
			</tr>
<?
	}
}else {
?>
			<tr>
				<td align="center" colspan="6">검색된 데이터가 없습니다.</td>
			</tr>
<?
}
?>
		</table>
	</div>

	<h3>○ 투자수익현황</h3>
	<div id="invest_status">

	</div>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "ajax_member_invest_list.php",
			type: "GET",
			data: { ca:'total_status', mb_no:<?=$mb['mb_no']?> },
			success: function(data) {
				$('#invest_status').html(data);
			},
			error: function () {
				$('#invest_status').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});
	</script>

	<h3>○ 일반 투자내역</h3>
	<div id="invest_log">

	</div>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "ajax_member_invest_list.php",
			type: "GET",
			data: { ca:'invest_log', mb_no:<?=$mb['mb_no']?> },
			success: function(data) {
				$('#invest_log').html(data);
			},
			error: function () {
				$('#invest_log').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});

	$(document).on('click','.btn_paging',function() {
		$.ajax({
			url : "./ajax_member_invest_list.php",
			type: "GET",
			data : { ca:'invest_log', mb_no:<?=$mb['mb_no']?>, page:$(this).attr("data-page")},
			success: function(data){
				$('#invest_log').html(data);
			},
			error: function ()	{
				$('#invest_log').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});
	</script>

	<h3>○ 상품별 투자내역</h3>
<?
$sqlct = "SELECT b.category, b.mortgage_guarantees, b.ai_grp_idx, b.invest_period, count(a.idx) cnt_idx ,SUM(a.amount) sum_amt FROM cf_product_invest a LEFT JOIN cf_product b ON(a.product_idx=b.idx) WHERE a.member_idx='$mb[mb_no]' and b.state<>'6' and b.state<>'7' and b.start_num>0 and a.invest_state='Y' GROUP BY b.category, b.mortgage_guarantees, b.ai_grp_idx, b.invest_period";
//echo "$sqlct";
$resct = sql_query($sqlct);
$cntct = sql_num_rows($resct);

for ($ii=0 ; $ii<$cntct ; $ii++) {
	$rowct = sql_fetch_array($resct);
	//echo "<pre>"; print_r($rowct); echo "</pre>";

	if ($rowct['category']=="1") {  // 동산
		$dongsan["cnt"] += $rowct['cnt_idx'];
		$dongsan["amt"] += $rowct['sum_amt'];

	} else if ($rowct['category']=="2") {  // 부동산
		if ($rowct['mortgage_guarantees']=="1")  {  // 주택담보
			$judam["cnt"] += $rowct['cnt_idx'];
			$judam["amt"] += $rowct['sum_amt'];
		} else { // 부동산 PF
			$budam["cnt"] += $rowct['cnt_idx'];
			$budam["amt"] += $rowct['sum_amt'];
		}
	} else if ($rowct['category']=="3") {  // 확정매출채권
		if ($rowct['ai_grp_idx']=="16" or $rowct['ai_grp_idx']=="10" or $rowct['ai_grp_idx']=="8" or $rowct['invest_period']<=1)  {  // 소상공인
			$sosang["cnt"] += $rowct['cnt_idx'];
			$sosang["amt"] += $rowct['sum_amt'];
		} else {  // 면세점
			$nodle["cnt"] += $rowct['cnt_idx'];
			$nodle["amt"] += $rowct['sum_amt'];
		}
	}

	$tota["cnt"] += $rowct['cnt_idx'];
	$tota["amt"] += $rowct['sum_amt'];
}
?>
	<div id="invest_product_type_log">
		<table width="100%" style="margin-bottom:50px;">
			<tr>
				<th scope="col" class="text-center" style="width:16%;">구분</th>
				<th scope="col" class="text-center" style="width:14%;">동산</th>
				<th scope="col" class="text-center" style="width:14%;">부동산</th>
				<th scope="col" class="text-center" style="width:14%;">주택담보</th>
				<th scope="col" class="text-center" style="width:14%;">헬로페이(소상공인)</th>
				<th scope="col" class="text-center" style="width:14%;">헬로페이(면세점)</th>
				<th scope="col" class="text-center" style="width:14%;">합 계</th>
			</tr>
			<tr>
				<td class="text-center">투자건수</td>
				<td class="text-right"><?=number_format($dongsan["cnt"])?> 건</td>
				<td class="text-right"><?=number_format($budam["cnt"])?> 건</td>
				<td class="text-right"><?=number_format($judam["cnt"])?> 건</td>
				<td class="text-right"><?=number_format($sosang["cnt"])?> 건</td>
				<td class="text-right"><?=number_format($nodle["cnt"])?> 건</td>
				<td class="text-right"><?=number_format($tota["cnt"])?> 건</td>
			</tr>
			<tr>
				<td class="text-center">투자금액</td>
				<td class="text-right"><?=number_format($dongsan["amt"])?> 원</td>
				<td class="text-right"><?=number_format($budam["amt"])?> 원</td>
				<td class="text-right"><?=number_format($judam["amt"])?> 원</td>
				<td class="text-right"><?=number_format($sosang["amt"])?> 원</td>
				<td class="text-right"><?=number_format($nodle["amt"])?> 원</td>
				<td class="text-right"><?=number_format($tota["amt"])?> 원</td>
			</tr>
		</table>
	</div>

	<h3>○ 이벤트 투자내역</h3>
	<div id="event_invest_log">

	</div>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "ajax_member_invest_list.php",
			type: "GET",
			data: { ca:'event_invest_log', mb_no:<?=$mb['mb_no']?> },
			success: function(data) {
				$('#event_invest_log').html(data);
			},
			error: function () {
				$('#event_invest_log').html('<font color="red">통신 에러 발생!!!</font>');
			}
		});
	});
	</script>
	<!-- 투자내역 상세리스트 끝 //-->

</div>

<script>
function startToggle(btn_id, area_id) {
	$(area_id).slideToggle();
};
</script>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
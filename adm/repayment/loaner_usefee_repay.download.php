<?
$filename = "헬로펀딩 대출자플랫폼수수료현황 내역_" . date('YmdHis');
$filename = iconv('UTF-8', 'EUC-KR', $filename);
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
header('Cache-Control: max-age=0');

include_once('./_common.php');

$sub_menu = "700800";

$sql_post = stripslashes($_POST['sql']);

$where = "1=1 AND A.is_drop='' AND C.isTest='' AND C.ib_product_regist='Y' ";

$sql = "
	SELECT
		COUNT(A.idx) AS cnt
	FROM
		cf_loaner_fee_collect A
	LEFT JOIN
		cf_loanerTaxinvoiceLog B  ON A.mgtKey=B.mgtKey
	LEFT JOIN
		cf_product C  ON A.product_idx=C.idx
	LEFT JOIN
		g5_member D  ON C.loan_mb_no=D.mb_no
	WHERE
		$where";

$sql = "
	SELECT
		A.idx, A.product_idx, A.loan_usefee_type, A.turn, A.schedule_date, A.bank_code, A.acct_no, A.depositor,
		A.schedule_amt, A.loan_usefee_amt, A.commission_fee_amt, A.deposit_amt, A.repay_amt, A.return_amt, A.return_ok, A.supply_price, A.tax,
		A.mgtKey, A.collect_ok, A.collect_date, A.rdate,
		B.req_date,
		C.category, C.category2, C.mortgage_guarantees, C.start_num, C.state, C.title, C.recruit_amount, C.loan_interest_rate, C.loan_start_date, C.loan_end_date, C.invest_period, C.invest_days,
		C.loan_usefee, C.loan_usefee_repay_count, C.ib_trust,
		(SELECT commission_fee FROM cf_product_container WHERE product_idx=A.product_idx) AS commission_fee,
		D.mb_no, D.mb_id, D.mb_name, D.mb_co_name, D.member_type, D.mb_hp
	FROM
		cf_loaner_fee_collect A
	LEFT JOIN
		cf_loanerTaxinvoiceLog B  ON A.mgtKey=B.mgtKey
	LEFT JOIN
		cf_product C  ON A.product_idx=C.idx
	LEFT JOIN
		g5_member D  ON C.loan_mb_no=D.mb_no
	WHERE
		$where
	ORDER BY
		C.loan_start_date DESC,
		A.idx DESC,
		A.turn ASC";

$result = sql_query($sql_post);
$rcount = sql_num_rows($result);

$SUM = array(
	'schedule_amt'      => 0,
	'loan_usefee_amt'   => 0,
	'commission_fee_amt'=> 0,
	'deposit_amt'       => 0,
	'repay_amt'         => 0,
	'return_amt'        => 0,
	'supply_price'      => 0,
	'tax'               => 0
);

for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);

	$SUM['schedule_amt']       += $LIST[$i]['schedule_amt'];
	$SUM['loan_usefee_amt']    += $LIST[$i]['loan_usefee_amt'];
	$SUM['commission_fee_amt'] += $LIST[$i]['commission_fee_amt'];
	$SUM['deposit_amt']        += $LIST[$i]['deposit_amt'];
	$SUM['repay_amt']          += $LIST[$i]['repay_amt'];
	$SUM['return_amt']         += $LIST[$i]['return_amt'];
	$SUM['diff_amt']           += $LIST[$i]['diff_amt'];
	$SUM['supply_price']       += $LIST[$i]['supply_price'];
	$SUM['tax']                += $LIST[$i]['tax'];
}

$list_count = count($LIST);

?>

    <table border="1">
        <thead style="text-align: center;">
			<tr style="text-align: center; font-weight: bold; font-size: 22px;">
				<td colspan="18">헬로펀딩 대출자플랫폼수수료현황 내역</td>
			</tr>
            <tr style="text-align: center;">   
                <td rowspan="2">호번</td>
                <td rowspan="2">차주번호</td>
                <td colspan="4">수수료</td>
                <td colspan="8">수취정보</td>
                <td colspan="3">세무정보</td>
                <td rowspan="2">등록일</td>
            </tr>
            <tr style="text-align: center;">
                <td>플랫폼이용료</td>
                <td>중개수수료</td>
                <td>수취방식</td>
                <td>분납회수</td>
                <td>납입회차</td>
                <td>납입예정일</td>
                <td>수취예정<br />수취금액</td>
                <td>수취일</td>
                <td>반환액</td>
                <td>반환여부</td>
                <td>정산차액</td>
                <td>최종정산액</td>
                <td>공급가</td>
                <td>세액</td>
                <td>발급구분</td>
            </tr>
        </thead>

        <tbody style="text-align: right;">
<?
if($list_count) {
	if($list_count > 1) {
?>
		<tr align="center" style="color:red;">
			<td>합계</td>
			<td></td>
			<td align="right" alt="플랫폼이용료"><?=number_format($SUM['loan_usefee_amt'])?>원</td>
			<td align="right" alt="중개수수료"><?=number_format($SUM['commission_fee_amt'])?>원</td>
			<td></td>
			<td></td>
			<td alt="납입회차"></td>
			<td alt="납입예정일"></td>
			<td align="right" alt="수취예정금액 수취금액">
				<?=number_format($SUM['schedule_amt'])?>원<br/>
				<?=number_format($SUM['deposit_amt'])?>원
			</td>
			<td alt="수취일"></td>
			<td align="right" alt="반환액"><?=number_format($SUM['return_amt'])?>원</td>
			<td alt="반환여부"></td>
			<td align="right" alt="정산차액"><?=number_format($SUM['diff_amt'])?>원</td>
			<td align="right" alt="최종정산액"><?=number_format($SUM['repay_amt'])?>원</td>

			<td align="right" alt="공급가"><?=number_format($SUM['supply_price'])?>원</td>
			<td align="right" alt="세액"><?=number_format($SUM['tax'])?>원</td>
			<td></td>
			<td></td>
		</tr>
<?
	}

	for($i=0,$num=$list_count; $i<$list_count; $i++,$$num--) {

		$PRINT = '';

		if($LIST[$i]['loan_started']) {
			$PRINT['invest_period'].= preg_replace("/-/", ".", $LIST[$i]['loan_start_date'])." ~ ".preg_replace("/-/", ".", $LIST[$i]['loan_end_date']);
			$PRINT['invest_period'].= ' ('.$LIST[$i]['total_days'].'일)';
		}

		$PRINT['loan_interest_rate'] = '(연) ' . floatRtrim($LIST[$i]['loan_interest_rate']) . '%';
		$PRINT['loan_date_range']    = ($LIST[$i]['loan_started']) ? preg_replace("/-/", ".", $LIST[$i]['loan_start_date'])." ~ ".preg_replace("/-/", ".", $LIST[$i]['loan_end_date']) : '';

		$PRINT['loan_usefee']    = floatRtrim($LIST[$i]['loan_usefee']).'%';
		$PRINT['commission_fee'] = floatRtrim($LIST[$i]['commission_fee']).'%';


		$PRINT['schedule_amt']       = ($LIST[$i]['schedule_amt'] <> 0) ? number_format($LIST[$i]['schedule_amt']) . '원' : '<span>0원</span>';
		$PRINT['loan_usefee_amt']    = ($LIST[$i]['loan_usefee_amt'] <> 0) ? number_format($LIST[$i]['loan_usefee_amt']) . '원' : '<span>0원</span>';
		$PRINT['commission_fee_amt'] = ($LIST[$i]['commission_fee_amt'] <> 0) ? number_format($LIST[$i]['commission_fee_amt']) . '원' : '<span>0원</span>';

		if($LIST[$i]['loan_usefee_type']=='A') {
			$PRINT['loan_usefee_type']         = '후취';
			$PRINT['loan_usefee_repay_count']  = $LIST[$i]['loan_usefee_repay_count'] . '회';
			$PRINT['loan_usefee_amt_month'] = number_format($LIST[$i]['loan_usefee_amt_month']) . '원';
		}
		else {
			$PRINT['loan_usefee_type']         = '선취';
			$PRINT['loan_usefee_repay_count']  = '-';
			$PRINT['loan_usefee_amt_month'] = '';
		}

		$PRINT['turn'] =  $LIST[$i]['turn'];

		if($LIST[$i]['collect_ok']) {
			$PRINT['collect_date'] = ($LIST[$i]['collect_date']) ? preg_replace("/-/", ".", $LIST[$i]['collect_date']) : '기록없음';
		}
		else {
			$PRINT['collect_date']= '';
		}

		$PRINT['schedule_amt']= number_format($LIST[$i]['schedule_amt']) . '원</span>';
		$PRINT['deposit_amt']= number_format($LIST[$i]['deposit_amt']) . '원</span>';
		$PRINT['repay_amt']  = number_format($LIST[$i]['repay_amt']) . '원</span>';
		$PRINT['diff_amt']   = number_format($LIST[$i]['diff_amt']) . '원</span>';
		$PRINT['return_amt'] = number_format($LIST[$i]['return_amt']) . '원</span>';
		$PRINT['return_ok']  = ($LIST[$i] > 0 && $LIST[$i]['return_ok']=='1') ? '반환' : '';

		$PRINT['schedule_date'] = preg_replace("/-/", ".", $LIST[$i]['schedule_date']);
		$PRINT['rdate'] = preg_replace("/-/", ".", substr($LIST[$i]['rdate'], 0, 10));

		$PRINT['supply_price'] = number_format($LIST[$i]['supply_price']) . '원</span>';
		$PRINT['tax'] = number_format($LIST[$i]['tax']) . '원</span>';

		$taxinvoicetype = ($LIST[$i]['member_type']=='2') ? '세금계산서' : '현금영수증';
		if($LIST[$i]['mgtKey']) {
			$PRINT['taxinvoice'] = $taxinvoicetype;
		}
		else {
			$PRINT['taxinvoice'] = '<span style="color:#CCC">'.$taxinvoicetype.'</span>';
		}

		$PRINT['member_type'] = ($LIST[$i]['member_type']=='2') ? '법인회원' : '개인회원';

		if($LIST[$i]['member_type']=='2') {
			$PRINT['mb_title'] = $LIST[$i]['mb_co_name'];
			$PRINT['mb_hp']    = $LIST[$i]['mb_hp'];
		}
		else {
			$PRINT['mb_title'] = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);
			$PRINT['mb_hp']    = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_hp'] : substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "****";
		}


		$PRINT['product_info_lnk'] = "/adm/product/product_list.php?field=A.idx&keyword=" . $LIST[$i]['product_idx'];

		$PRINT['member_info_lnk'] = "/adm/member/member_list.php?member_group=L&key_search=A.mb_no&keyword=" . $LIST[$i]['mb_no'];

		$PRINT['click_action'] = ($_SESSION['ss_accounting_admin']) ? "location.href='?{$qstr}&idx={$LIST[$i]['idx']}'" : "alert('접근권한이 없습니다.');";

?>

		<tr align="center">
			<td>
				<span onClick="fieldToggle('detail_<?=$i?>');"><?=$LIST[$i]['start_num']?></span>
			</td>
			<td>
				<span onClick="fieldToggle('mbdetail_<?=$i?>');"><?=$LIST[$i]['mb_no']?></span>
			</td>
			<td align="right"><?=$PRINT['loan_usefee_amt']?></td>
			<td align="right"><?=$PRINT['commission_fee_amt']?></td>
			<td><?=$PRINT['loan_usefee_type']?></td>
			<td><?=$PRINT['loan_usefee_repay_count']?></td>
			<td><?=$PRINT['turn']?></td>
			<td><?=$PRINT['schedule_date']?></td>
			<td align="right">
				<?=$PRINT['schedule_amt']?><br/>
				<?=$PRINT['deposit_amt']?>
			</td>
			<td align="center"><?=$PRINT['collect_date']?></td>
			<td align="right"><?=$PRINT['return_amt']?></td>
			<td><?=$PRINT['return_ok']?></td>
			<td align="right"><?=$PRINT['diff_amt']?></td>
			<td align="right"><?=$PRINT['repay_amt']?></td>
			<td align="right"><?=$PRINT['supply_price']?></td>
			<td align="right"><?=$PRINT['tax']?></td>
			<td><?=$PRINT['taxinvoice']?></td>
			<td><?=$PRINT['rdate']?></td>
		</tr>

<?
		$num++;
	}
}
else {
	echo "<tr><td colspan='18' align='center'>데이터가 없습니다.</td></tr>\n";
}
?>
	</tbody>
</table>

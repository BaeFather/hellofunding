<?

include_once("_common.php");

if($idx) {

	$sql = "
		SELECT
			A.idx, A.product_idx, A.loan_usefee_type, A.turn, A.loan_usefee_amt, A.commission_fee_amt, A.schedule_amt, A.schedule_date, A.bank_code, A.acct_no, A.depositor, A.mgtKey,
			A.deposit_amt, A.repay_amt, A.return_amt, A.return_ok, A.supply_price, A.tax, A.mgtKey, A.writer_id, A.rdate,
			A.collect_exec_date, A.collect_ok, A.collect_date,
			C.state, C.title, C.category, C.category2, C.mortgage_guarantees, C.recruit_amount, C.loan_interest_rate, C.loan_start_date, C.loan_end_date, C.invest_period, C.invest_days,
			C.loan_usefee,
			(SELECT commission_fee FROM cf_product_container WHERE product_idx=A.product_idx) AS commission_fee
		FROM
			cf_loaner_fee_collect A
		LEFT JOIN
			cf_loanerTaxinvoiceLog B  ON A.mgtKey=B.mgtKey
		LEFT JOIN
			cf_product C  ON A.product_idx=C.idx
		WHERE 1
			AND A.idx = '".$idx."'";
	$DATA = sql_fetch($sql);
//print_rr($DATA, 'font-size:12px');

}

if($DATA['idx']) {
//$save_button = ($DATA['mgtKey']) ? "" : "<button type='button' id='frmSubmit' class='btn btn-sm btn-primary' style='width:80px'>수 정</button>";
	$save_button = "<button type='button' id='frmSubmit' class='btn btn-sm btn-primary' style='width:80px'>수 정</button>";
}
else {
	$save_button.= "<button type='button' id='frmSubmit' class='btn btn-sm btn-primary' style='width:80px'>등 록</button>";
}

if($DATA['idx']) {

	$PRINT['recruit_amount'] = number_format($DATA['recruit_amount']) . '원';
	$PRINT['invest_period'] = "";

	if($DATA['category']=='2') {
		$PRINT['category'] = ($DATA['mortgage_guarantees']=='1') ? '부동산 > 주택담보' : '부동산 > PF';
	}
	else if($DATA['category']=='3') {
		$PRINT['category'] = ($DATA['category2']=='2') ? '매출채권 > 면세점' : '매출채권 > 소상공인';
	}
	else {
		$PRINT['category'] = '동산';
	}

}

?>
<table class="table-bordered" style="width:1000px; font-size:13px">
	<colgroup>
		<col width="16.66%">
		<col width="16.66%">
		<col width="16.66%">
		<col width="16.66%">
		<col width="16.66%">
		<col width="16.67%">
	</colgroup>
	<form id="form1" name="form1">
		<input type="hidden" name="idx" value="<?=$idx?>">
	<tr>
		<th>상품</th>
		<td>
			<select id="state" class="form-control input-sm" style="margin:0" <?=($idx)?'disabled':''?>>
				<option value=''>전체상품</option>
				<option value='1' selected>이자상환중</option>
				<option value='finished'>상환완료전체</option>
				<option value='2'> - 정상상환</option>
				<option value='5'> - 중도상환</option>
				<option value='8'>연체중</option>
			</select>
		</td>
		<td colspan="4">
			<select id="product_idx" name="product_idx" class="form-control input-sm"></select>
		</td>
	</tr>
	<tr>
		<th>대출금액</th>
		<td id="recruit_amount" align="center">&nbsp;</td>
		<th>대출기간</th>
		<td id="invest_period" colspan="3" align="center">&nbsp;</td>
	</tr>

	<tr>
		<th>카테고리</th>
		<td id="category" align="center">&nbsp;</td>
		<th>이용료 수취방식</th>
		<td id="loan_usefee_type" align="center">&nbsp;</td>
		<th>분납회수</th>
		<td id="loan_usefee_repay_count" colspan="3" align="center">&nbsp;</td>
	</tr>

	<tr>
		<th>납입회차</th>
		<td align="center">
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;">
					<input type="text" id="turn" name="turn" value="<?=$DATA['turn']?>" <?=($DATA['turn'])?'readonly':''?> onKeyUp="onlyDigit(this);" class="form-control input-sm" style="width:40px;">
				</li>
				<li style="float:left;padding:6px 0 0 4px">회</li>
			</ul>
		</td>
		<th>수취예정일</th>
		<td colspan="3"><input type="text" id="schedule_date" name="schedule_date" value="<?=$DATA['schedule_date']?>" class="form-control input-sm datepicker" style="width:154px; text-align:center;"></td>
	</tr>

	<tr>
		<th>수취예정금액</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="schedule_amt" name="schedule_amt" value="<?=number_format($DATA['schedule_amt'])?>" onKeyUp="onlyDigit(this);NumberFormat(this);" class="form-control input-sm" style="text-align:right;width:130px"></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
		<th>플랫폼이용료</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="loan_usefee_amt" name="loan_usefee_amt" value="<?=number_format($DATA['loan_usefee_amt'])?>" onKeyUp="onlyDigit(this);NumberFormat(this);" class="form-control input-sm" style="text-align:right;width:130px"></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
		<th>중개수수료</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="commission_fee_amt" name="commission_fee_amt" value="<?=number_format($DATA['commission_fee_amt'])?>" onKeyUp="onlyDigit(this);NumberFormat(this);" class="form-control input-sm" style="text-align:right;width:130px"></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
	</tr>

	<tr>
		<th>입금(수금)은행</th>
		<td>
			<select name="bank_code" class="form-control input-sm">
				<option value="">::선택::</option>
<?
		$BANK_KEYS = array_keys($BANK);
		for($x=0; $x<count($BANK); $x++) {
			$selected = ($DATA['bank_code']==$BANK_KEYS[$x]) ? 'selected' : '';
			echo '<option value="'.$BANK_KEYS[$x].'" '.$selected.'>'.$BANK[$BANK_KEYS[$x]].'</option>' . PHP_EOL;
		}
?>
			</select>
		</td>
		<th>입금(수금)계좌번호</th>
		<td><input type="text" id="acct_no" name="acct_no" value="<?=$DATA['acct_no']?>" onKeyUp="onlyDigit(this);" class="form-control input-sm"></td>
		<th>자동수취실행일</th>
		<td><input type="text" id="collect_exec_date" name="collect_exec_date" value="<?=$DATA['collect_exec_date']?>" <?=($DATA['loan_usefee_type'])?'disabled':'';?> class="form-control input-sm datepicker" style="text-align:center;"></td>
	</tr>

	<tr>
		<th>수취금액</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="deposit_amt" name="deposit_amt" value="<?=number_format($DATA['deposit_amt'])?>" onKeyUp="onlyDigit(this);NumberFormat(this);repayAmtCalc();" class="form-control input-sm" style="text-align:right;width:130px"></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
		<th>수취일</th>
		<td><input type="text" id="collect_date" name="collect_date" value="<?=$DATA['collect_date']?>" class="form-control input-sm datepicker" style="text-align:center;"></td>
		<th>입금자명</th>
		<td><input type="text" id="depositor" name="depositor" value="<?=$DATA['depositor']?>" class="form-control input-sm"></td>
	</tr>

	<tr>
		<th>반환금액</th>
		<td colspan="5">
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="return_amt" name="return_amt" value="<?=number_format($DATA['return_amt'])?>" onKeyUp="onlyDigit(this);NumberFormat(this);repayAmtCalc();" class="form-control input-sm" style="text-align:right;width:130px"></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
				<li style="float:left;padding:6px 0 0 20px;"><label class="checkbox-inline"><input type="checkbox" name="return_ok" value="1" <?=($DATA['return_ok']=='1')?'checked':''?>> 반환완료</label></li>
			</ul>
		</td>
	</tr>
	<tr>
		<th>정산금액</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="repay_amt" name="repay_amt" value="<?=number_format($DATA['repay_amt'])?>" class="form-control input-sm" style="text-align:right;width:130px" readonly></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
		<th>공급가</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="supply_price" name="supply_price" value="<?=number_format($DATA['supply_price'])?>" onKeyUp="onlyDigit(this);NumberFormat(this);" readonly class="form-control input-sm" style="text-align:right;width:130px"></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
		<th>세액</th>
		<td>
			<ul style="list-style:none;display:inline-block; padding:0; margin:0;">
				<li style="float:left;"><input type="text" id="tax" name="tax" value="<?=number_format($DATA['tax'])?>" class="form-control input-sm" style="text-align:right;width:130px" readonly></li>
				<li style="float:left;padding:6px 0 0 4px">원</li>
			</ul>
		</td>
	</tr>
	</form>
</table>

<div style="width:1000px;text-align:center; margin-top:9px;">
	<?=$save_button?>
	<? if($DATA['idx'] && $DATA['mgtKey']==''){ ?><button type="button" onClick="dropData('<?=$DATA['idx']?>')" class="btn btn-sm btn-danger" style="width:80px">삭제</button><? } ?>
	<button type="button" id="list_button" onClick="location.href='<?=$_SERVER['PHP_SELF']?>';" class="btn btn-sm btn-default" style="width:80px">목록보기</button>
</div>

<script>
makeProductOptionList = function() {
	$.ajax({
		url : 'ajax.product_info.php',
		type: 'post',
		dataType: 'json',
		data:{
			mode:'list',
			state: $('#state').val(),
			idx:'<?=$DATA['product_idx']?>'
		},
		success:function(data) {
			if(data.result=='FAIL') {
				alert(data.message);
			}
			else {
				var view_product_idx = '<?=$DATA['product_idx']?>';

				$('#product_idx').empty();
				$('#product_idx').append('<option value="">::상품선택::</option>');

				$.each(data, function() {
					selected = (view_product_idx==this.idx) ? 'selected' : '';
					print_title = this.title;
					xstyle = (view_product_idx==this.idx) ? "style='background:#FFFFCC;font-weight:bold;color:red'" : '';

					option_str = "<option value='"+ this.idx +"' " + selected + " " + xstyle + ">"+ print_title +"</option>";
					$('#product_idx').append(option_str);

					if(view_product_idx==this.idx) getProductInfo();

				});

			}
		},
		error:function (e) { console.log(e); alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});
	return true;
}

$('#state').on('change', function() {
	makeProductOptionList();
});

$(document).ready(function() {
	makeProductOptionList();
});

getProductInfo = function() {
	$.ajax({
		url : 'ajax.product_info.php',
		type: 'post',
		dataType: 'json',
		data:{
			mode:'detail',
			idx: $('#product_idx').val()
		},
		success:function(data) {
			if(data.result=='NULL') {
				return;
			}
			else if(data.result=='FAIL') {
				alert(data.message);
			}
			else {
				$('#recruit_amount').html(number_format(data.recruit_amount) + '원');
				$('#invest_period').html(data.print_invest_period);
				$('#category').html(data.print_category);
				$('#loan_usefee_type').html(data.print_loan_usefee_type);
				$('#loan_usefee_repay_count').html(data.print_loan_usefee_repay_count);
			}
		},
		error:function (e) { console.log(e); alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});
}

$('#product_idx').on('change', function() {
	getProductInfo();
});
</script>

<script>
repayAmtCalc = function() {
	if( $('input[name=deposit_amt]').val() == '') {
		//$('input[name=deposit_amt]').val(0);
	}
	if( $('input[name=return_amt]').val() == '') {
		//$('input[name=return_amt]').val(0);
	}

	if( $('input[name=deposit_amt]').val() ) {
		depositAmt = $('input[name=deposit_amt]').val();
	}
	else {
		depositAmt = $('input[name=schedule_amt]').val();
	}
	//alert(depositAmt);

	returnAmt = $('input[name=return_amt]').val();

	repayAmt = depositAmt.replace(/\D/g,'') - returnAmt.replace(/\D/g,'');

	supplyPrice = Math.ceil(repayAmt / 1.1);
	tax = repayAmt - supplyPrice;

	supplyPrice = (supplyPrice < 0) ? '-' + number_format(supplyPrice) : number_format(supplyPrice);
	tax = (tax < 0) ? '-' + number_format(tax) : number_format(tax);

	repayAmt = (repayAmt < 0) ? '-' + number_format(repayAmt) : number_format(repayAmt);

	$('input[name=repay_amt]').val(repayAmt);
	$('input[name=supply_price]').val(supplyPrice);
	$('input[name=tax]').val(tax);
}

$('#frmSubmit').on('click', function() {
	if(confirm('데이터를 저장 하시겠습니까?')) {
		var params = $('#form1').serialize();

		$.ajax({
			url : 'loaner_usefee_repay.write_proc.php',
			type: 'post',
			dataType: 'json',
			data: params,
			success:function(data) {
				if(data.result=='SUCCESS') {
					alert('저장완료');window.location.reload();
				}
				else {
					alert(data.message);
				}
			},
			error:function (e) { console.log(e); alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});

	}
});

dropData = function() {
	if(confirm('데이터를 삭제 하시겠습니까?')) {
		alert('메롱');
	}
}
</script>
<?
$sub_menu = "920200";
include_once('./_common.php');
include_once('mortgage_common.php');

auth_check($auth[$sub_menu], 'w');
if ($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while(list($key, $value) = each($_GET)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}

$html_title = "채권관리 상세";
$g5['title'] = $html_title.' 정보';

$idx = $_GET["idx"];


// 상품 테이블의 정보와 협력사 주담대 신청(구)의 데이터 가지고 오기
$sql = "SELECT
			A.idx, A.title, A.recruit_amount, A.invest_return, A.invest_period, A.invest_days,
			A.invest_usefee_type, A.loan_usefee, A.loan_dep_bank_cd1, A.loan_dep_acct_nb1, A.loan_dep_acct_memo1,
			B.laddr, B.pname, B.pbirth, B.pphone1, B.pphone2, B.pcp_name, B.pcp_addr, B.pcp_contact, B.pcredit_score,
			B.dambo_pname, B.dambo_pbirth, B.dambo_pphone, B.dambo_cscore,
			B.brokerage_fee, B.brokerage_reamount, B.middle_refee, B.middle_repayment_list, B.seller, B.sale_detail,
			B.origin_file, B.temp_file, B.hash_tag,
			B.hmseq, B.hcseq, B.mkind, B.mortgage_ranking, B.mortgage_info, B.hellofee, B.fees
		FROM
			cf_product A
		LEFT JOIN
			hloan_content B ON A.idx = B.product_idx
		WHERE
			A.idx='$idx'";

$row = sql_fetch($sql);

// 후취, 선취 타입 설정
if ($row['invest_usefee_type']=="A") $uf_type="후취";
else if ($row['invest_usefee_type']=="B") $uf_type="선취";
else $uf_type="-";

// 협력사 주담대 신청(구) 협력사명 데이터 가지고 오기, hloan_member의 hmseq는 hloan_content의 hmseq의 조건을 가짐.
$hsql = "SELECT
			hmseq, cname
		FROM
			hloan_member
		WHERE
			hmseq = '$row[hmseq]'
";

$hrow = sql_fetch($hsql);

?>

<style type="text/css">
	.mort_title {text-align: left; font-size: 18px; margin: 0; font-weight: 800; color: #295f98;}
	.mor_detail_tbl {font-size: 14px;}
	.mor_detail_tbl .inner_tbl {text-align: center; font-size: 12px;}
	.mor_detail_tbl .inner_tbl th {background-color: #dedede; border: 1px solid #eee;}
	.mor_detail_tbl #fileupload {position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;}
	.mor_detail_tbl label[for='fileupload'] {display: inline-block; padding: .5em .75em; margin:0; font-size: 12px; color: #fff; font-weight: 500; line-height: normal; background-color: #337ab7; cursor: pointer; border-radius: .25em; float: right;}
	.mor_detail_tbl .file_del_btn, .upload_file_delbtn {color: #777; cursor: pointer; text-decoration: none; border: 1px solid #ccc; border-radius: 5px; font-size: 11px; padding: 3px 5px; line-height: 2.5; margin-left: 3px;}
	.mor_detail_tbl .file_del_btn:hover, .upload_file_delbtn:hover {color: #000; text-decoration: none;}

	.sms_wrap {position: fixed; left: 1500px; z-index: 10; top: 30%; border: 1px solid #eee;}
	.sms_wrap .sms_area {width: 100%; padding: 7% 5%;}
	.sms_wrap .sms_title {background-color: #3C5B9B; color: #fff; font-weight: 700; padding: 10px; border-radius: 3px 3px 0 0; text-align: center;}
	.sms_wrap textarea[name='sms_msg'] {width: 100%; height: 185px;}
	.sms_wrap .sms_info {clear: both; margin: 0 13px;}
	.sms_wrap .sms_info > div {margin-bottom: 20px;}

	.input01 {width:100%;border-radius:3px;line-height:24px;font-size:14px;text-align:left;border:1px solid #CCC; background-color: #fff;}
	.input02 {width:95%;line-height:24px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
	.input03 {width:50%;line-height:24px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
	.input04 {width:19.5%;line-height:24px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
</style>
<?
include_once('../admin.head.php');
?>

<div class="tbl_head02 tbl_wrap" style="max-width: 1500px; text-align: center;">
	<form name="mortgageDetailForm" method="post" enctype="multipart/form-data">
		<input type="hidden" name="idx" value="<?=$idx?>" />
		<input type="hidden" name="hcseq" value="<?=$row['hcseq']?>" />

		<p class="mort_title"><?=$row['title']?></p>
		<table class="table table-bordered mor_detail_tbl">
			<colgroup>
				<col width="10%">
				<col width="15%">
				<col width="30%">
				<col width="15%">
				<col width="30%">
			</colgroup>
			<tbody>
				<tr>
					<th scope="col" rowspan='6' class="tbl_th">상품정보</th>
					<th>담보물주소</th>
					<td><?=$row['laddr']?></td>
					<th>상품정보</th>
					<td><input type="text" class="input02" name="hash_tag" value="<?=$row['hash_tag']?>" id="hashTag"/></td>
				</tr>
				<tr>
					<th>대출금액</th>
					<td><?=number_format($row['recruit_amount'])?>원</td>
					<th>대출기간</th>
					<td><?=$row['mkind']?>개월</td>
				</tr>
				<tr>
					<th>대출금리</th>
					<td><?=$row['invest_return']?></td>
					<th>IDX</th>
					<td><?=$row['idx']?></td>
				</tr>
				<tr>
					<th>플랫폼이용료율</th>
					<td><?=floatRtrim($row['fees'])?>%</td>
					<th>플랫폼이용료</th>
					<td id="print_loan_usefee_amt"><?=number_format($row['hellofee'])?>원</td>
				</tr>
				<tr>
					<th>플랫폼이용료 수취방식</th>
					<td><?=$uf_type?></td>
					<th></th>
					<td></td>
				</tr>
				<tr>
					<th>저당순위</th>
					<td><input type="text" class="input02" name="mortgage_ranking" value="<?=$row['mortgage_ranking']?>" autocomplete="off"/></td>
					<th>저당정보</th>
					<td><input type="text" class="input02" name="mortgage_info" value="<?=$row['mortgage_info']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th scope="col" rowspan='4'>차주정보</th>
					<th>성명</th>
					<td><?=$row['pname']?></td>
					<th>생년월일</th>
					<td><input type="text" class="input02" name="pbirth" value="<?=$row['pbirth']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th>연락처1</th>
					<td><input type="text" class="input02" name="pphone1" value="<?=$row['pphone1']?>" autocomplete="off"/></td>
					<th>연락처2</th>
					<td><input type="text" class="input02" name="pphone2" value="<?=$row['pphone2']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th>직장명</th>
					<td><input type="text" class="input02" name="pcp_name" value="<?=$row['pcp_name']?>" autocomplete="off"/></td>
					<th>직장주소</th>
					<td><input type="text" class="input02" name="pcp_addr" value="<?=$row['pcp_addr']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th>직장 연락처</th>
					<td><input type="text" class="input02" name="pcp_contact" value="<?=$row['pcp_contact']?>" autocomplete="off"/></td>
					<th>신용점수</th>
					<td><input type="text" class="input02" name="pcredit_score" value="<?=$row['pcredit_score']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th scope="col" rowspan='2'>담보 제공자 정보</th>
					<th>성명</th>
					<td><input type="text" class="input02" name="dambo_pname" value="<?=$row['dambo_pname']?>" autocomplete="off"/></td>
					<th>생년월일</th>
					<td><input type="text" class="input02" name="dambo_pbirth" value="<?=$row['dambo_pbirth']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th>연락처</th>
					<td><input type="text" class="input02" name="dambo_pphone" value="<?=$row['dambo_pphone']?>" autocomplete="off"/></td>
					<th>신용점수</th>
					<td><input type="text" class="input02" name="dambo_cscore" value="<?=$row['dambo_cscore']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th>기표정보</th>
					<th>기표계좌</th>
					<td><?=$row['loan_dep_acct_nb1']?></td>
					<th>계좌주</th>
					<td><?=$row['loan_dep_acct_memo1']?></td>
				</tr>
				<tr>
					<th scope="col" rowspan='2'>중개업체수수료</th>
					<th>중개 업체명</th>
					<td><?=$hrow['cname']?></td>
					<th>중계수수료</th>
					<td><input type="text" class="input03 chk_number" name="brokerage_fee" value="<?=$row['brokerage_fee']?>" autocomplete="off"/> 원</td>
				</tr>
				<tr>
					<th scope="col">중개환수금액</th>
					<td colspan="3"><input type="text" class="input04 chk_number" name="brokerage_reamount" value="<?=$row['brokerage_reamount']?>" autocomplete="off"/> 원</td>
				</tr>
				<tr>
					<th scope="col" rowspan="2">중도상환</th>
					<th>중도상환수수료</th>
					<td><input type="text" class="input03 chk_number" name="middle_refee" value="<?=$row['middle_refee']?>" autocomplete="off"/> %</td>
					<th>납입금</th>
					<td><?=number_format($row['recruit_amount']*$row['middle_refee']/100)?>원</td>
				</tr>
				<tr>
					<th scope="col">납입내역</th>
					<td colspan="3"><input type="text" class="input02" name="middle_repayment_list" value="<?=$row['middle_repayment_list']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th>매각처</th>
					<th>매각처</th>
					<td><input type="text" class="input02" name="seller" value="<?=$row['seller']?>" autocomplete="off"/></td>
					<th>매각내용</th>
					<td><input type="text" class="input02" name="sale_detail" value="<?=$row['sale_detail']?>" autocomplete="off"/></td>
				</tr>
				<tr>
					<th scope="col" rowspan="2">이자관리</th>
				</tr>
				<tr>
					<td colspan="13" style="padding: 3px;">
<?
$TLIST = array();
$tsql = "SELECT * FROM cf_product_turn WHERE product_idx='$idx' ORDER BY turn";
$tres = sql_query($tsql);
$tcnt = $tres->num_rows;
$ty = 0;
for ($j=0 ; $j<$tcnt ; $j++) {
	$trow = sql_fetch_array($tres);

	$tyn = $j % 12;
	$TLIST[$ty]["turn"][$tyn] = $j+1;  // 회차
	$TLIST[$ty]["dday"][$tyn] = $trow["ym"]."-".$trow["dday"];  // 지급일
	$TLIST[$ty]["eja"][$tyn]  = $trow["eja"];  // 이자

	if ($tyn==11) {
		$ty  = $ty+1;
		$tyn = 0;
	}
}
echo "<pre>"; print_r($TLIST);echo "</pre>";

?>
<?
for ($l = 0 ; $l<count($TLIST) ; $l++) {
	$safe_sw++;
	?>
						<table class="inner_tbl">
							<colgroup>
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
							</colgroup>
							<tbody>
	<?
	for ($m=0 ; $m<3; $m++) {


		echo "<tr>";

		for ($n=0 ; $n<12; $n++) {

			if ($m==0) echo "<td>".$TLIST[$l]['turn'][$n]." 회차</td>";
			if ($m==1) echo "<td>".$TLIST[$l]['dday'][$n]."</td>";
			if ($m==2) echo "<td>".number_format($TLIST[$l]['eja'][$n])."</td>";

		}
		?>
								</tr>
		<?
	}
	?>
							</tbody>
						</table>
						<br/>
						<br/>
	<?
	if ($safe_sw>50) die();
}
?>
						<table class="inner_tbl">
							<colgroup>
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
							</colgroup>
							<tbody>
								<tr>
									<th>회차</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>".$i."회차</td>";
										}
									?>
								</tr>
								<tr>
									<th>지급일</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>".$i."월 5일</td>";
										}
									?>
								</tr>
								<tr>
									<th>회차이자</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>100,000원</td>";
										}
									?>
								</tr>
								<tr>
									<th>수취이자</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>100,000원</td>";
										}
									?>
								</tr>
								<tr>
									<th>차액</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>0원</td>";
										}
									?>
								</tr>
								<tr>
									<th>상태</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>예정</td>";
										}
									?>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<th scope="col">연체이자관리</th>
					<td colspan="13" style="padding: 3px;">
						<table class="inner_tbl">
							<colgroup>
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
							</colgroup>
							<tbody>
								<tr>
									<th>연체일수</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>3일</td>";
										}
									?>
								</tr>
								<tr>
									<th>연체이자</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>180원</td>";
										}
									?>
								</tr>
								<tr>
									<th>상태</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>지급완료</td>";
										}
									?>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<th scope="col">이자요약</th>
					<td colspan="13" style="padding: 3px;">
						<table class="inner_tbl">
							<colgroup>
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
								<col width="7%">
							</colgroup>
							<tbody>
								<tr>
									<th>합계</th>
									<?
										for($i=1; $i<=13; $i++)
										{
											echo "<td>-</td>";
										}
									?>
								</tr>
								<tr style="height: 31px;">
									<th>연체금리</th>
									<td></td>
									<th>연체일수</th>
									<td></td>
									<th>연체금액</th>
									<td></td>
									<th>수취필요금액</th>
									<td></td>
									<th>상환계좌번호</th>
									<td colspan="2"></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<th scope="col">첨부서류</th>
					<td colspan="4">
						<input type="file" id="fileupload" name="origin_file[]" multiple>
						<label for="fileupload">파일추가</label>
						<?
						$tmp = explode(";", $row['origin_file']);
						$tmp2 = explode(";", $row['temp_file']);
						?>
						<div id="fileList">
						<?
						for($i=0; $i<count($tmp); $i++) {
						?>
							<p class="file_name_test" style="margin: 0; padding: 0;">
								<a id="uploadFileName" href="uploads/<?=$tmp2[$i]?>"><?=$tmp[$i]?></a>
								<?
								if($tmp[$i]) {
								?>
								<a onclick="deleteUploadFile('<?=$row['idx']?>','<?=$row['hcseq']?>','<?=$tmp[$i]?>','<?=$tmp2[$i]?>');" class="upload_file_delbtn">삭제</a>
								<?
								}
								?>
							</p>
						<?
						}
						?>
						</div>
						<script type="text/javascript">
							function deleteUploadFile(idx, hcseq, ori_name, tmp_name) {

								var delYn = confirm("선택한 파일을 삭제하시겠습니까?");

								if (delYn) {
									window.location.replace("deleteFile.php?idx="+idx+"&hcseq="+hcseq+"&oname="+ori_name+"&tname="+tmp_name,"_blank");
								}

							}
						</script>
					</td>
				</tr>
				<tr>
					<th scope="col">상담내역</th>
					<td colspan="4">
						<div>
							<ul class="list-inline">
								<li style="width:85%; height:80px">
									<textarea id="comment" name="comment" style="width:100%;height:100%;"></textarea>
								<li style="width:14.6%">
									<button type="button" id="frmCmtSubmit" class="btn btn-primary" style="width:100%;height:80px;" onclick="go_save_cmd();">등 록</button>
								</li>
							</ul>
							<table style="font-size:12px">
								<colgroup>
									<col width="10%">
									<col width="10%">
									<col width="65%">
									<col width="15%">
								</colgroup>
								<tr style='background:#FAFAFA'>
									<td align="center">구분</td>
									<td align="center">작성자</td>
									<td align="center">내용</td>
									<td align="center">일시</td>
									<td align="center"></td>
								</tr>
								<?php
								$cres = sql_query("SELECT idx, divi, writer, mb_id, comment, regdate FROM hloan_comment WHERE req_idx='$row[hcseq]' ORDER BY idx DESC");
								$ccnt = $cres->num_rows;

								for($i=0; $i<$ccnt; $i++) {
									$crow = sql_fetch_array($cres);

									$delete_tag = "";

									if(($crow['mb_id']==$member['mb_id']) || $member['mb_level']=='10') {
										$delete_tag = "<span style='cursor:pointer;color:red;'>×</span>";
									}

									$comm = nl2br(htmlSpecialChars($crow['comment']));
								?>
								<tr>
									<td align="center"><? if($crow['divi'] == 'admin') echo "코멘트"; ?></td>
									<td align="center"><?=$crow['writer']?></td>
									<td><?=$comm?></td>
									<td align="center"><?=$crow['regdate']?></td>
									<td align="center"><?=$delete_tag?></td>
								<tr>
								<?php
										}
								?>

							</table>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<!--button type="submit" id="listUpdate" class="btn btn-default" style="margin-right: 1%;">수정하기</button-->
		<button type="button" id="listUpdate" class="btn btn-default" style="margin-right: 1%;">수정하기</button>
		<button type="button" class="btn btn-default" onclick="" style="margin-right: 1%;">목록보기</button>
		<button type="button" class="btn btn-default" onclick="" style="margin-right: 1%;">정산</button>
		<button type="button" class="btn btn-default" onclick="">상품수정</button>
	</form>
</div>

<script type="text/javascript">
$('#listUpdate').click(function() {

	var yn = confirm("이대로 수정하시겠습니까?");

	if (yn) {
		$("form[name='mortgageDetailForm']").attr("action", "mortgage_detail_form_update.php");
		document.mortgageDetailForm.submit();
	}

});
</script>

<div class="sms_wrap">
	<div class="sms_area pull-left">
		<div class="sms_title">SMS 문자전송</div>
		<div class="sms_msg">
			<textarea rows="20" name="sms_msg" id="sms_msg" placeholder="메세지 내용을 입력해주세요" onKeyUp="bytePrint();"></textarea>
		</div>
		<span class="sms_error" id="msg_err"></span>
	</div>
	<div class="sms_info">
		<div>발신번호 : <input type="text" class="frm_input" name="from_hp" id="from_hp" size="20" value="15885210"> <span class="sms_error" id="from_hp_err"></span></div>
		<div>수신번호 : <input type="text" class="frm_input" name="to_hp" id="to_hp" size="20" value=""> <span class="sms_error" id="to_hp_err"></span></div>
		<div>발송시간 :
			<select name="send_time" id="send_time" onchange="check_sms_time(this.value);">
				<option value="d" selected>즉시발송</option>
				<option value="r">예약발송</option>
			</select>
			<span id="send_t_area" style="display:none;">
				<input type="text" class="frm_input datepicker" name="send_ymd" id="send_ymd" size="10" value="" placeholder="날짜선택">
				<select id="send_h" name="send_h">
				<?
				for($i=0; $i<=23; $i++) {
					if(strlen($i) == 1) {
						$j = '0'.$i;
					}else {
						$j = $i;
					}
					echo '<option value='.$j.'>'.$j.'시</option>';
				}
				?>
				</select>
				<select id="send_i" name="send_i">
				<?
				for($i=0; $i<=59; $i++) {
					if(strlen($i) == 1) {
						$j = '0'.$i;
					}else {
						$j = $i;
					}
					echo '<option value='.$j.'>'.$j.'분</option>';
				}
				?>
				</select>
				<span class="sms_error" id="send_time_err"></span>
			</span>
		</div>
		<div><button type="button" id="btn_submit" onClick="sms_send(document.fsms);" class="btn btn-lg btn-primary" style="width: 100%;">SMS 전송</button></div>
		<div id="sms_result" class="sms_error"></div>
	</div>
</div>

<script>
function go_save_cmd() {  // 코멘트 저장

	var cmdYn = confirm("등록하시겠습니까?");

	if (cmdYn) {
		var f = document.mortgageDetailForm;
		f.action = "mortgage_cmt_ins.php";
		f.submit() ;

	}
}


// 파일이 추가되는 순간 addFiles 함수가 실행된다.
$(document).ready(function() {
	$("#fileupload").on("change", addFiles);
});

var filesTempArr = [];

// 파일 추가
function addFiles(e) {
	var files = e.target.files;
	var filesArr = Array.prototype.slice.call(files);
	var filesArrLen = filesArr.length;
	var filesTempArrLen = filesTempArr.length;

	for( var i=0; i<filesArrLen; i++ ) {
		filesTempArr.push(filesArr[i]);
		$("#fileList").append("<div>" + filesArr[i].name + "<span class='file_del_btn' onclick='deleteFile(event," + (filesTempArrLen + i) + ");'>삭제</span></div>");
	}

}

// 파일 삭제
function deleteFile(eventParam, orderParam) {
	filesTempArr.splice(orderParam, 1);
	var innerHtmlTemp = "";
	var filesTempArrLen = filesTempArr.length;

	for(var i=0; i<filesTempArrLen; i++) {
		innerHtmlTemp += "<div>" + filesTempArr[i].name + "<span class='file_del_btn' onclick='deleteFile(event,"+ i +");'>삭제</span></div>";
	}
	$("#fileList div").html(innerHtmlTemp);
}

// input 숫자만 입력하게끔
$(document).on("keypress", "input[type=text].chk_number", function () {

    if((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105) && (event.keyCode != 8) && (event.keyCode != 46)) {
		event.returnValue = false;
	}

});

// input 자동 , 찍히게끔
$(document).on("keyup", "input[type=text].chk_number", function () {

    var $this = $(this);
    var num = $this.val().replace(/[,]/g, "");
    var parts = num.toString().split(".");

    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

    $this.val(parts.join("."));

});


</script>

<?
if ($_GET["save"] == "Y") {
?>
<script>
$(window).load(function() {
	alert('수정되었습니다.');
	location.replace("mortgage_detail_form.php?idx=<?=$idx?>");
});
</script>
<?
}
?>


<? include_once ('../admin.tail.php'); ?>
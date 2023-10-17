<?
$sub_menu = "920200";
include_once('./_common.php');
include_once('mortgage_common.php');

// 권한체크 
auth_check($auth[$sub_menu], 'w');
if ($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

// get으로 받은 데이터들을 변수화 
while(list($key, $value) = each($_GET)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}

$html_title = "채권관리 상세";
$g5['title'] = $html_title.' 정보';


// 상품 테이블의 정보와 협력사 주담대 신청(구)의 데이터 가지고 오기
$sql = "SELECT
			A.idx, A.title, A.recruit_amount, A.invest_return, A.invest_period, A.invest_days, A.invest_usefee_type,
			A.loan_usefee, A.loan_dep_bank_cd1, A.loan_dep_acct_nb1, A.loan_dep_acct_memo1, A.overdue_rate, A.repay_acct_no,
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
			hmseq = '$row[hmseq]'";

$hrow = sql_fetch($hsql);

// 수수료율 데이터 가지고 오기
$fsql = "SELECT
			product_idx, commission_fee
		 FROM
			cf_product_container
		 WHERE
			product_idx = '$idx'";

$frow = sql_fetch($fsql);

// 계좌 잔액
$resql = "SELECT
			 A.* , B.virtual_account2, C.title
		  FROM
			 cf_loaner_push_schedule A
		  LEFT JOIN
			 g5_member B ON(B.mb_no = A.mb_no)
		  LEFT JOIN
			 cf_product C ON(C.idx = A.product_idx)
		  WHERE
			 product_idx = '$idx'";

$rerow = sql_fetch($resql);

$remain_amt = get_chaju_remain_amt("$rerow[mb_no]", "$rerow[virtual_account2]", "$rerow[product_idx]");  // 계좌잔액(현재)

?>

<style type="text/css">
	.mort_title {text-align: left; font-size: 18px; margin: 0; font-weight: 800; color: #295f98;}
	.mor_detail_tbl {font-size: 14px;}
	.mor_detail_tbl .inner_tbl {text-align: center; font-size: 12px;}
	.mor_detail_tbl .inner_tbl th {background-color: #dedede; border: 1px solid #eee;}
	.mor_detail_tbl #fileupload {position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;}
	.mor_detail_tbl label[for='fileupload'] {display: inline-block; padding: .5em .75em; margin:0; font-size: 12px; color: #fff; font-weight: 500; line-height: normal; background-color: #337ab7; cursor: pointer; border-radius: .25em; float: right;}
	.mor_detail_tbl .file_del_btn, .upload_file_delbtn {color: #777; cursor: pointer; text-decoration: none; border: 1px solid #ccc; border-radius: 5px; font-size: 11px; padding: 3px 5px; line-height: 2.5; margin-left: 3px;}
	.cmt_del_btn {display: inline-block; cursor: pointer; color: red; width: 36px;}
	.mor_detail_tbl .file_del_btn:hover, .upload_file_delbtn:hover {color: #000; text-decoration: none;}

	.sms_wrap {display: inline-block; right: 10px; width: 245px; height: 560px; border: 1px solid #eee; position: fixed; left: 1500px; z-index: 10; top: 29%;}

	.input01 {width:100%;border-radius:3px;line-height:24px;font-size:14px;text-align:left;border:1px solid #CCC; background-color: #fff;padding: 0 3px;}
	.input02 {width:95%;line-height:24px;font-size:14px;text-align:left;border:1px solid #CCC;margin:0 auto;padding: 0 3px;}
	.input03 {width:50%;line-height:24px;font-size:14px;text-align:left;border:1px solid #CCC;margin:0 auto;padding: 0 3px;}
	.input04 {width:19.5%;line-height:24px;font-size:14px;text-align:left;border:1px solid #CCC;margin:0 auto;padding: 0 3px;}

	.tbl_toggle_btn {display: block; width: 60px; height: 30px; margin: 0 auto; border: 1px solid #7d7d7d; border-radius: 5px; line-height: 2.3; text-align: center; color: #656565; cursor: pointer; font-size: 13px; transition: all .5s;}
	.tbl_toggle_btn:hover {background-color: #eaeaea;}
	.tbl_more_btn {display: none; margin: 25px auto 10px 50%; width: 120px; height: 30px; text-align: center; background-color: #7d7d7d; border-radius: 25px; line-height: 2.5; font-size: 12px; color: #fff; cursor: pointer;}
</style>

<?
include_once('../admin.head.php');
?>

<div class="tbl_head02 tbl_wrap" style="max-width: 1500px; text-align: center;">
	<form name="mortgageDetailForm" method="post" enctype="multipart/form-data">  <!-- enctype 파일 첨부로 인해 multipart/form-data -->
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
					<th>중개 수수료</th>
					<td><?=number_format($row['recruit_amount']*floatRtrim($frow['commission_fee'])/100)?>원</td>
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
					<td colspan="13" style="padding: 1%;">
						<p class="tbl_toggle_btn">보기</p>
						<p class="tbl_toggle_btn" style="display: none;">접기</p>

						<table class="inner_tbl eja_tbl" style="font-size: 12px; display: none; margin-top: 15px; color: #5a5a5a;">
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
							</colgroup>
							<thead>
								<tr>
									<th>회차</th>
									<th>지급일정</th>
									<th>회차이자</th>
									<th>수취이자</th>
									<th>차액</th>
									<th>상태(정상이자)</th>
									<th>연체일수</th>
									<th>연체이자</th>
									<th>상태(연체이자)</th>
									<th>합계</th>
							   </tr>
							</thead>
							<tbody>
							<?
							$tsql = "SELECT * FROM cf_product_turn WHERE product_idx='$idx' ORDER BY turn ASC";
							$tres = sql_query($tsql);
							$tcnt = $tres->num_rows;

							$actsql = "SELECT * FROM cf_product_turn WHERE repay_yn='N' AND product_idx='$idx' ORDER BY turn ASC";
							$actrow = sql_fetch($actsql);
							$imsi_list = array(); // 차액 배열로

							for ($j=0; $j<$tcnt; $j++) {

								$trow = sql_fetch_array($tres);
								$imsi_list[$j] = $trow;  // 차액

								// 수취이자를 구해봅시드아아아아아
								$nean_don = "";
								$dday = $trow["ym"]."-".$trow["dday"];
								$this_day = date("Y-m-d");

								if ($dday < $this_day) {  // 현재보다 일정이 작은 경우, 과거
									if ($trow["eja_in_date"]) {
										$nean_don = $trow["eja"];
									}
								} else if ($actrow['ym']==$trow['ym']) {  // 현재(활성회차)
									if ($trow["eja_in_date"]) {
										$nean_don = $trow["eja"];
									} else {
										
									}
								} else {  // 미래 
									if ($trow["eja_in_date"]) { 
										$nean_don = $trow["eja"];
									} else {
										$nean_don = 0;
									}
								}

								// 차액을 구해봅시드아아아아
								$cha_don = $trow["remain_amt"];  // 차액
								$sub_don = $trow["remain_amt"] - $imsi_list[$j-1]['remain_amt'];  // 현재 차액 - 이전 차액

								if($dday < $this_day) {  // 현재 보다 일정이 작은 경우

									if($trow["eja_in_date"]) {  // 이자 지급일 값이 있을 때
										$cha_don = 0;  // 차액 = 0
									}

								} else if($actrow['ym']==$trow['ym']) {  // 활성 회차의 경우
									
									if($trow["eja_in_date"]) {

										$nean_don = $trow['eja'];
										$cha_don = 0;

									} else {

										if($imsi_list[$j-1]['remain_amt'] >= $trow['eja']) {  // 수취일 경우
											$nean_don = $trow['eja'];
											$cha_don = 0;

											//$next_month = date('Y-m', strtotime($trow['ym'].'1 month'));


										} else {  // 미수취인 경우

											$nean_don = abs($sub_don);
											$cha_don = $trow['eja'] - $nean_don;
										}

									}

								} else {  // 미래의 경우

									if($trow["eja_in_date"]) {  // 이자 지급일 값이 있을 때
										$nean_don = $trow['eja'];
										$cha_don = 0;
									} else {
										if ($imsi_list[$j-1]['remain_amt']) {
											$nean_don = $imsi_list[$j-1]['remain_amt'];
											$cha_don = $trow["eja"]-$imsi_list[$j-1]['remain_amt'];
										} else {
											$nean_don = 0;
											$cha_don = 0;
										}
									}
								}

								$state = "";

								if($trow["eja_in_date"] && $actrow['ym'] == $trow['ym']) {
									$state = '지급완료';
								} elseif($imsi_list[$j-1]['remain_amt'] >= $trow['eja'] || $actrow['ym'] > $trow['ym']) {
									$state = '수취';
								} else {
									$state = '미수취';
								}


							?>
							<tr>
								<?
								if($actrow['ym']==$trow['ym']) {
									echo '<td style="background-color:#fffddc;">'.$actrow['turn'].'회차</td>';
									echo '<td style="background-color:#fffddc;">'.$actrow['ym'].'</td>';
									echo '<td style="background-color:#fffddc;padding-right:15px;text-align:right">'.number_format($actrow['eja']).' 원</td>';
									if($nean_don) {
										echo '<td style="padding-right:15px;text-align:right;background-color:#fffddc;">'.number_format($nean_don).' 원</td>';
									} else {
										echo '<td style="padding-right:15px;text-align:right;background-color:#fffddc;">'.$nean_don.' 원</td>';
									}
									echo '<td style="background-color:#fffddc;padding-right:15px;text-align:right">'.number_format($cha_don).' 원</td>';
									echo '<td style="background-color:#fffddc;">'.$state.'</td>';
								} else {
									echo '<td>'.$trow['turn'].'회차</td>';
									echo '<td>'.$trow['ym'].'</td>';
									echo '<td style="padding-right:15px;text-align:right">'.number_format($trow['eja']).' 원</td>';
									if($nean_don) {
										echo '<td style="padding-right:15px;text-align:right">'.number_format($nean_don).' 원</td>';
									} else {
										echo '<td style="padding-right:15px;text-align:right">'.$nean_don.' 원</td>';
									}
									echo '<td style="padding-right:15px;text-align:right">'.number_format($cha_don).' 원</td>';
									echo '<td>'.$state.'</td>';
								}
								
								?>
							
								<td>-</td>
								<td>-</td>
								<td>-</td>
								<td>-</td>

							</tr>
							<?
							}
							?>
							</tbody>
						</table>
						<p class="tbl_more_btn" onclick="listMoreBtn();">+ 더보기</p>
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
								<tr style="height: 31px;">
									<th>연체금리</th>
									<td>연 <?=($row['overdue_rate'])?floatRtrim($row['overdue_rate']):'24';?>%</td>
									<th>연체일수</th>
									<td>일</td>
									<th>연체금액</th>
									<td>원</td>
									<th>수취필요금액</th>
									<td>
									<? 
									$tsql = "SELECT * FROM cf_product_turn WHERE product_idx='$idx' ORDER BY turn ASC";
									$tres = sql_query($tsql);
									$tcnt = $tres->num_rows;

									$actsql = "SELECT * FROM cf_product_turn WHERE repay_yn='N' AND product_idx='$idx' ORDER BY turn ASC";
									$actrow = sql_fetch($actsql);

									for ($j=0; $j<$tcnt; $j++) {

										$trow = sql_fetch_array($tres);
										$nean_don = "";

										if($actrow['ym'] == $trow['ym']) {  // 활성회차일 때
											if($trow['eja_in_date']) {  // 이자 지급일이 있으면                
												$nean_don = $trow['eja'];
												$cha_don = 0;
											} else {  // 없으면
												$nean_don = 0;
												$cha_don = $trow['eja'] - $nean_don;
											}

												echo number_format($cha_don);  // 차액
										}

									}
									?>원</td>
									<th>상환계좌번호</th>
									<td colspan="2"><?=$row['repay_acct_no']?></td>
									<th>계좌잔액</th>
									<td colspan="2"><?=number_format($remain_amt);?> 원</td>
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
					</td>
				</tr>
				<tr>
					<th scope="col">상담내역</th>
					<td colspan="4">
						<div>
							<ul class="list-inline">
								<li style="width:85%; height:80px">
									<textarea id="comment" name="comment" style="width:100%;height:100%;"></textarea>
								</li>
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
								$cres = sql_query("
											SELECT *
											FROM (
												(SELECT A.idx AS idx, A.mb_id AS id, A.writer AS writer, A.comment AS cont, A.regdate AS dt, divi AS gubun FROM hloan_comment A LEFT JOIN hloan_content B ON(A.req_idx=B.hcseq) WHERE B.product_idx='$idx')
												UNION ALL
												(SELECT idx AS idx, ' ' AS id, '이자 자동 문자' AS writer, sended_msg AS cont, CONCAT(send_date,' ', send_time) AS dt, 'sms' AS gubun FROM cf_loaner_push_schedule WHERE send_datetime IS NOT NULL AND product_idx='$idx')
											 ) result
											ORDER BY dt desc");

								$ccnt = $cres->num_rows;

								for($i=0; $i<$ccnt; $i++) {
									$crow = sql_fetch_array($cres);

									$delete_btn = "";

									if(($crow['id']==$member['mb_id']) || $member['mb_level']=='10') {
										$delete_btn = "<span class='cmt_del_btn' onclick='cmtDelBtn(".$row['idx'].",".$crow['idx'].");'>삭제</span>";
									}

									$comm = nl2br(htmlSpecialChars($crow['comment']));
								?>
								<tr>
									<td align="center"><?if($crow['gubun'] == 'sms') echo "문자메세지"; elseif($crow['gubun'] == 'admin' || !$crow['gubun']) echo "COMMENT";?></td>
									<td align="center"><?=$crow['writer']?></td>
									<td><?=$crow['cont']?></td>
									<td align="center"><?=$crow['dt']?></td>
									<td align="center"><?=$delete_btn?></td>
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
		<button type="button" id="listUpdate" class="btn btn-default" style="margin-right: 1%;">수정하기</button>
		<button type="button" class="btn btn-default" onclick="mortListView();" style="margin-right: 1%;">목록보기</button>
		<button type="button" class="btn btn-default" onclick="repayPage('<?=$row['idx']?>');" style="margin-right: 1%;">정산</button>
		<button type="button" class="btn btn-default" onclick="productMod('<?=$row['idx']?>');">상품수정</button>
	</form>
</div>

<!--// 문자발송 //-------------------------------------->
<div class="sms_wrap">
	<iframe id="sms_frame" name="sms_frame" src="/adm/sms_sender/sms.form.php?to_hp=<?=$row['pphone1']?>&hcseq=<?=$row['hcseq']?>" frameborder="0" scrolling="no" style="width:100%;height:100%;"></iframe>
</div>
<!--// 문자발송 //-------------------------------------->

<script>

// 코멘트 저장
function go_save_cmd() {

	var cmdYn = confirm("등록하시겠습니까?");

	if (cmdYn) {
		var f = document.mortgageDetailForm;
		f.action = "mortgage_cmt_ins.php";
		f.submit();

	}
}

// 코멘트 삭제
function cmtDelBtn(idx, cidx) {
	var cmtYn = confirm('등록된 내용을 지우시겠습니까?');

	if (cmtYn) {
		window.location.replace('deleteComment.php?idx='+idx+'&cidx='+cidx);
	}
}

// 수정버튼 클릭시 항목 업데이트
$('#listUpdate').click(function() {

	var yn = confirm("이대로 수정하시겠습니까?");

	if (yn) {
		$("form[name='mortgageDetailForm']").attr("action", "mortgage_detail_form_update.php");
		document.mortgageDetailForm.submit();
	}

});

// 파일 삭제
function deleteUploadFile(idx, hcseq, ori_name, tmp_name) {

	var delYn = confirm("선택한 파일을 삭제하시겠습니까?");

	if (delYn) {
		window.location.replace("deleteFile.php?idx="+idx+"&hcseq="+hcseq+"&oname="+ori_name+"&tname="+tmp_name);
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

// 보기, 펼치기
$('.tbl_toggle_btn').on('click', function(){

	$('.eja_tbl tr').slice(0,14).show();
	$('.eja_tbl tr').slice(14,$('.eja_tbl tr').length).hide();

	if($('.eja_tbl tr').length > 14) {
		$('.tbl_more_btn').show();
	}

	$('.tbl_toggle_btn').toggle();
	$('.eja_tbl').toggle();

});

// 더보기(남은 리스트 전부 보여주기)
function listMoreBtn() {

	if($('.eja_tbl tr').length > 14) {
		$('.eja_tbl tr').show();

		if($('.eja_tbl tr').length == $('.eja_tbl tr').size()) {
			$('.tbl_more_btn').css('display','none');
		}

	}

}

// 버튼 연동
function repayPage(idx) {
	window.open("/adm/repayment/repay_calculate.php?&idx="+idx);
}

function productMod(idx) {
	window.open("/adm/product/product_form.php?idx="+idx);
}

function mortListView() {
	window.location.replace("/adm/mortgage/mortgage_sms.php");
}

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
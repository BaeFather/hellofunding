<?

if($MB['kyc_reg_dd']) {

?>
				<h3 style="margin-top:50px">
					KYC 승인 정보
					<select id="kyc_allow_yn" name="kyc_allow_yn" class="input-sm" style="margin-left:20px;"><!-- <?=( in_array($MB['kyc_allow_yn'], array('Y','N')) )?'disabled':'';?>-->
						<option value="">:: 승인설정 ::</option>
						<!--<option value="W">대기</option>-->
						<option value="I">심사중</option>
						<option value="Y">승인</option>
						<option value="N">반려</option>
					</select>
				</h3>

				<div style="text-align:right;margin-bottom:8px;">

				</div>

				<table style="border-top:2px solid #000">
					<colgroup>
						<col width="16.5%">
						<col width="16.5%">
						<col width="16.5%">
						<col width="16.5%">
						<col width="16.5%">
						<col width="%">
					</colgroup>
					<tbody>
						<tr height="40">
							<th scope="row" class="tit">요청번호</th>
							<th scope="row" class="tit">자료등록일</th>
							<th scope="row" class="tit">승인여부</th>
							<th scope="row" class="tit">유효일<br/><span style='color:#777;font-size:11px'>다음KYC시작일</span></th>
							<th scope="row" class="tit">처리일시</th>
							<th scope="row" class="tit">담당자</th>
						</tr>
<?
	if($MB['kyc_allow_yn']=='W') {

?>
						<tr align="center">
							<td><?=$MB['kyc_order_id']?></td>
							<td><?=$MB['kyc_reg_dd']?></td>
							<td>대기</td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
<?
	}

	$log_res = sql_query("SELECT * FROM g5_member_kyc_judge_log WHERE mb_no='".$MB['mb_no']."' ORDER BY judge_dt DESC");
	$log_count = $log_res->num_rows;

	if($log_count) {

		for($i=0; $i<$log_count; $i++) {
			$JUDGE_LOG = sql_fetch_array($log_res);

			if($JUDGE_LOG['kyc_allow_yn']=='W')      $kyc_allow_yn = "대기";
			else if($JUDGE_LOG['kyc_allow_yn']=='I') $kyc_allow_yn = "심사중";
			else if($JUDGE_LOG['kyc_allow_yn']=='Y') $kyc_allow_yn = "승인";
			else if($JUDGE_LOG['kyc_allow_yn']=='N') $kyc_allow_yn = "반려";

			$judge_name = '';
			if($JUDGE_LOG['judge_mb_id']=='system') {
				$judge_name = "자동승인";
			}
			else {
				$judge_name = sql_fetch("SELECT mb_name FROM g5_member WHERE mb_id='".$JUDGE_LOG['judge_mb_id']."'")['mb_name'];
			}

?>
						<tr align="center">
							<td><?=$JUDGE_LOG['kyc_order_id']?></td>
							<td><?=$JUDGE_LOG['kyc_reg_dd']?></td>
							<td><span style="color:#3366FF"><?=$kyc_allow_yn?></span></td>
							<td><span style="color:#3366FF"><?=$JUDGE_LOG['kyc_next_dd']?></span></td>
							<td><?=substr($JUDGE_LOG['judge_dt'],0,16);?></td>
							<td><?=$judge_name?></td>
						</tr>
<?
		}
	}
?>
					</tbody>
				</table>

				<script>
				$('#kyc_allow_yn').on('change', function() {
					if( $('#kyc_allow_yn').val()!='' && $('#kyc_allow_yn').val() != '<?=$MB['kyc_allow_yn']?>' ) {

						var msg = '승인정보를 변경하시겠습니까?';
						//if($('#kyc_allow_yn').val()=='Y' || $('#kyc_allow_yn').val()=='N') {
						//	msg += '\n승인 또는 반려 처리 후 변경은 되지 않습니다.';
						//}

						if( confirm(msg) ) {
							$.ajax({
								url: 'ajax_kyc_status.proc.php',
								type: 'post',
								dataType: 'json',
								data: {
									'mb_no': '<?=$AML['mb_no']?>',
									'kyc_allow_yn': $('#kyc_allow_yn').val()
								},
								success: function(data) {

									if(data.result=='success') {
										window.location.reload();
									}
									else {
										alert(data.message);
									}

								},
								beforeSend: function() { loading('on'); },
								complete: function() { loading('off'); },
								error: function(e) { return; }
							});
						}
					}
				});
				</script>
<?
}
?>
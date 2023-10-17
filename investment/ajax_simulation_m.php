			<h3><?=$PSTATE['PRDT']["title"]?></h3>
			<div style="height:16px;margin-bottom:4px;font-size:12px;line-height:16px;text-align:right;padding-right:8px">
			  ● 수익산정기간 : <span style="color:#222"><?=preg_replace("/-/", ".", $PSTATE['INI']['loan_start_date'])?> ~ <?=preg_replace("/-/", ".", $PSTATE['INI']['loan_end_date'])?></span>
			</div>
			<div class="type04 mb30">
				<table>
					<colgroup>
						<col style="width:33.4%">
						<col style="width:33.3%">
						<col style="width:33.3%">
					</colgroup>
					<tbody>
						<tr height="59">
							<td valign="top">투자원금<br>
								<div class="num"><?=number_format($PSTATE['INI']['principal'])?><span style="font-size:0.8em">원</span></div>
							</td>
							<td valign="top">수익률/투자기간<br>
								<div class="num"><span style="font-size:0.8em">(연)</span><?=$DATA['invest_return']?><span style="font-size:0.8em">%</span> / <?=$invest_period?><span style="font-size:0.8em"><?=$invest_period_unit?></span></div>
							</td>
							<td valign="top">수익<span style="font-size:0.8em">(세전)</span><br>
								<div class="num"><?=number_format($PSTATE['REPAYSUM']['invest_interest'])?><span style="font-size:0.8em">원</span></div>
							</td>
						</tr>
						<tr height="59">
							<td valign="top">플랫폼이용료<br>
								<div class="num"><?=number_format($PSTATE['REPAYSUM']['invest_usefee'])?><span style="font-size:0.8em">원</span></div>
							</td>
							<td valign="top">세금<br>
								<div class="num"><?=number_format($PSTATE['REPAYSUM']['withhold'])?><span style="font-size:0.8em">원</span></div>
							</td>
							<td valign="top">총수익<span style="font-size:0.8em">(세후)</span><br>
								<div class="num"><?=number_format($PSTATE['INI']['principal'] + $PSTATE['REPAYSUM']['interest'])?><span style="font-size:0.8em">원</span></div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="type03 profit mb40">
				<table id="simul_table">
					<colgroup>
						<col style="width:33.4%">
						<col style="width:33.3%">
						<col style="width:33.3%">
					</colgroup>
					<tbody>
						<tr>
							<th style="text-align:center">지급일자(차수)</th>
							<th style="text-align:center">원금</th>
							<th style="text-align:center">수익</th>
						</tr>
						<tr align="center">
							<th style="text-align:center">플랫폼이용료</th>
							<th style="text-align:center">세금</th>
							<th style="text-align:center">실입금액</th>
						</tr>
<?
for ($i=0,$j=1; $i<count($PSTATE['REPAY']); $i++,$j++) {
?>
						<tr>
							<td style="text-align:center"><?=$PSTATE['REPAY'][$i]['repay_day']?> (<?=$j?>차)</td>
							<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['principal'])?>원</td>
							<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['invest_interest'])?>원</td>
						</tr>
						<tr>
							<td style="border-bottom:1px solid #555;text-align:right"><?=number_format($PSTATE['REPAY'][$i]['invest_usefee'])?>원</td>
							<td style="border-bottom:1px solid #555;text-align:right"><?=number_format($PSTATE['REPAY'][$i]['withhold'])?>원</td>
							<td style="border-bottom:1px solid #555;text-align:right"><?=number_format($PSTATE['REPAY'][$i]['send_price'])?>원</td>
						</tr>
<?
}
?>
					</tbody>
					<tfoot>
						<tr>
							<td style="text-align:center">합계</td>
							<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['principal'])?>원</td>
							<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['invest_interest'])?>원</td>
						</tr>
						<tr>
							<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['invest_usefee'])?>원</td>
							<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['withhold'])?>원</td>
							<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['send_price'])?>원</td>
						</tr>
					</tfoot>
				</table>
			</div>

<?
exit;
?>
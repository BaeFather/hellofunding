<?
include_once('./_common.php');


if ($_SERVER["REQUEST_METHOD"]!="GET") { echo "ERROR"; exit; }

$gubun = $_GET["gubun"];

if($gubun=="") { echo "ERROR"; exit; }

$date_time = preg_replace("/(-| |:)/i", "", G5_TIME_YMDHIS);  // YmdHis

if($gubun=="all") {
	$product_query = "SELECT COUNT(idx) FROM cf_product WHERE 1=1 AND display='Y'";
}
else if($gubun=="before") {
	$product_query = "
		SELECT
			COUNT(*)
		FROM
			(SELECT *, REPLACE(REPLACE(REPLACE(open_datetime,'-',''),' ',''),':','') AS open_date_time FROM cf_product a WHERE 1=1 AND a.display='Y' ) AS temp
		WHERE
			open_date_time > '$date_time'";

}
else if($gubun=="ing") {
	$product_query = "
		SELECT
			COUNT(*)
		FROM
			(
				SELECT
					*,
					REPLACE(REPLACE(REPLACE(start_datetime,'-',''),' ',''),':','') AS start_date_time,
					REPLACE(REPLACE(REPLACE(end_datetime,'-',''),' ',''),':','') AS end_date_time,
					(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_amount FROM cf_product a WHERE 1=1 AND a.display='Y') AS temp
		WHERE
			start_date_time <= '$date_time'
			AND end_date_time >= '$date_time'
			AND recruit_amount > total_invest_amount";

}
else if($gubun=="end") {
	$product_query = "
		SELECT
			COUNT(*)
		FROM
			(
				SELECT
					*,
					REPLACE(REPLACE(REPLACE(end_datetime,'-',''),' ',''),':','') AS end_date_time,
					(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_amount FROM cf_product a WHERE 1=1 AND a.display='Y' ) AS temp
		WHERE
			(end_date_time < '$date_time' || recruit_amount <= total_invest_amount)
			AND state =''";

}
else if($gubun=="repayment_ing"){
	$product_query = "SELECT COUNT(*) FROM cf_product a WHERE 1=1 AND a.display='Y' AND a.state ='1'";
}
else if($gubun=="repayment_end"){
	$product_query = "SELECT COUNT(*) FROM cf_product a WHERE 1=1 AND a.display='Y' AND a.state ='2'";
}
else{
	exit;
}

$product_result = query($product_query);
$product_cnt = mysqli_fetch_row($product_result);
if($product_cnt[0]>0){

	if($gubun=="all"){
		$product_query = "
			SELECT
				a.*,
				(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y') AS total_invest_amount,
				(SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_count
			FROM
				cf_product a
			WHERE
				1=1
				AND a.display='Y'
			ORDER BY
				a.start_date DESC, a.idx DESC";
	}
	else if($gubun=="before"){

		$product_query = "
			SELECT
				temp.*
			FROM
				(SELECT *, REPLACE(REPLACE(REPLACE(open_datetime,'-',''),' ',''),':','') AS open_date_time, (SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y') AS total_invest_amount,
				(SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_count FROM cf_product a WHERE 1=1 AND a.display='Y') AS temp
			WHERE
				open_date_time > '$date_time'
			ORDER BY
				open_date_time DESC, idx DESC";
	}
	else if($gubun=="ing"){
		$product_query = "
			SELECT
				temp.*
			FROM
				(
					SELECT
						*,
						REPLACE(REPLACE(REPLACE(start_datetime,'-',''),' ',''),':','') AS start_date_time,
						REPLACE(REPLACE(REPLACE(end_datetime,'-',''),' ',''),':','') AS end_date_time,
						(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y') AS total_invest_amount,
						(SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_count
					FROM
						cf_product a
					WHERE
						1=1 AND a.display='Y'
				) AS temp
			WHERE
				start_date_time <= '$date_time'
				AND end_date_time >=  '$date_time'
				AND recruit_amount > total_invest_amount
			ORDER BY
				start_date_time ASC, idx DESC";
	}
	else if($gubun=="end"){
		$product_query = "
			SELECT
				temp.*
			FROM
				(SELECT *, REPLACE(REPLACE(REPLACE(end_datetime,'-',''),' ',''),':','') AS end_date_time,
				(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y') AS total_invest_amount,
				(SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_count FROM cf_product a WHERE 1=1 AND a.display='Y') AS temp
			WHERE
				(end_date_time < '$date_time' || recruit_amount <= total_invest_amount) AND state =''
			ORDER BY
				end_date_time DESC, idx DESC";

	}
	else if($gubun=="repayment_ing"){
		$product_query = "
			SELECT
				a.*,
				(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y') AS total_invest_amount,
				(SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_count
			FROM
				cf_product a
			WHERE
				1=1
				AND a.display='Y'
				AND a.state ='1'
			ORDER BY
				a.start_date DESC, a.idx DESC";
	}
	else if($gubun=="repayment_end"){
		$product_query = "
			SELECT
				a.*,
				(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y') AS total_invest_amount,
				(SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE a.idx = product_idx AND invest_state='Y' ) AS total_invest_count
			FROM
				cf_product a
			WHERE
				1=1
				AND a.display='Y'
				AND a.state ='2'
			ORDER BY
				a.start_date DESC, a.idx DESC";
	}

	$product_result = query($product_query);

	while($product_row = mysqli_fetch_array($product_result)) {
		/* 나중에 함수로 수정*/

		if($product_row["recruit_amount"]>0){
			if($product_row["total_invest_amount"]>0){
				$product_invest_percent =  round((($product_row["total_invest_amount"]/$product_row["recruit_amount"])*100),2);
			}
			else{
				$product_invest_percent = 0;
			}
		}
		else{
			$product_invest_percent = 0;
		}


		$product_open_date    = preg_replace("/(-| |:)/", "", $product_row["open_datetime"]);		/* 상점오픈 (투자시작가능) */
		$product_invest_sdate = preg_replace("/(-| |:)/", "", $product_row["start_datetime"]);	/* 상품오픈 (투자시작가능) */
		$product_invest_edate = preg_replace("/(-| |:)/", "", $product_row["end_datetime"]);		/* 상품종료 (투자마감) */

		$evaluate_star1      = $product_row["evaluate_star1"];
		$evaluate_star2      = $product_row["evaluate_star2"];
		$evaluate_star3      = $product_row["evaluate_star3"];
		$invest_end_date     = preg_replace("/-/", "", $product_row["invest_end_date"]);
		$total_evaluate_star = $evaluate_star1 + $evaluate_star2 + $evaluate_star3;

		$product_state = get_product_state(
											 $product_row["recruit_period_start"],
											 $product_row["recruit_period_end"],
											 $product_open_date,
											 $product_invest_sdate,
											 $product_invest_edate,
											 $product_row["state"],
											 $product_row["recruit_amount"],
											 $product_row["total_invest_amount"],
											 $invest_end_date);

?>
			<div class="box product_count">
        <div class="imgArea">
          <img src="../images/investment/level_<?=strtolower($_evaluation_grade_array[$total_evaluate_star])?>.png" alt="<?=$_evaluation_grade_array[$total_evaluate_star]?>" class="label" style="display:none" alt="로컬등급" />
<?
					if($product_row['main_image']){
						if(is_file(G5_DATA_PATH."/product/".$product_row['main_image'])){
							echo "<a href='./investment.php?prd_idx={$product_row['idx']}'><img src='".G5_DATA_URL."/product/{$product_row['main_image']}' /></a>\n";
						}
						else {
							echo "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_more'>더보기</a>\n";
						}
					}
					else {
						echo "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_more'>더보기</a>\n";
					}
?>
          <? if($product_row['purchase_guarantees']=='Y') { ?><!--<div class="flag_green">채권매입계약</div>--><? echo "\n"; }?>
          <? if($product_row['advanced_payment']=='Y') { ?><div class="flag_orange">이자 선지급</div><? echo "\n"; } ?>
        </div>
        <div class="con" <? if($is_admin=='super' && $product_row['display']=='N') { ?>style="border:1px dotted #999;border-top:0;"<? } ?>>
          <div class="title">
            <b><?=$product_row["title"]?></b>
            모집기간 : <?=$product_row["recruit_period_start"]?> ~ <?=$product_row["recruit_period_end"]?>
          </div>
          <ul class="info">
            <li>수익률(연)&nbsp;<b><?=$product_row["invest_return"]?>%</b></li>
            <li>기간&nbsp;<b><?=$product_row["invest_period"]?>개월</b></li>
            <li>모집금액&nbsp;<b><?=price_cutting($product_row["recruit_amount"])?>원<!--<?=number_format($invest_row["total_invest_amount"])?>원--></b></li>
            <li><?=$product_state?>&nbsp;<b><?=price_cutting($product_row["total_invest_amount"])?>원 / <?=price_cutting($product_row["recruit_amount"])?>원</b></li>
            <li>참여진행률&nbsp;<b><span class="blue"><?=$product_invest_percent?>%</span><!--(<?=number_format($product_row["total_invest_count"])?>명)--></b>
              <div class="rate"><img src="../images/investment/rate_blue.gif" alt="진행률" style="width:<?=($product_invest_percent)?$product_invest_percent:0.2;?>%" /></div>
            </li>
          </ul>
<?
				$invest_finished = false;
				$invest_button = "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_big_blue' style='width:47%;margin:0;'>상품상세보기</a>\n";
				if($product_invest_sdate<=date("YmdHis") && $product_invest_edate>=date("YmdHis")) {
					if($product_row["recruit_amount"] > $product_row["total_invest_amount"]) {
						$invest_button = "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_big_blue'style='width:47%;margin:0;'>상품상세보기</a>\n";
					}
					else {
						$invest_finished = true;
						$invest_button = "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_big_gray' style='width:100%;margin:0;'>투자모집완료</a>\n";
					}
				}
				else {
					if($product_row["recruit_amount"] > $product_row["total_invest_amount"]) {
						if( preg_replace("/-/", "", $product_row["recruit_period_start"])>date("Ymd") ) {
							$invest_button = "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_big_blue'style='width:47%;margin:0;'>상품상세보기</a>\n"; //투자대기상태
						}
						else if( preg_replace("/-/", "", $product_row["recruit_period_end"])<date("Ymd") ) {
							$invest_finished = true;
							$invest_button = "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_big_gray' style='width:100%;margin:0;'>투자모집완료</a>\n";
						}
					}
					else {
						$invest_finished = true;
						$invest_button = "<a href='./investment.php?prd_idx={$product_row['idx']}' class='btn_big_gray' style='width:100%;margin:0;'>투자모집완료</a>\n";
					}
				}
?>
          <div style="width:100%;text-align:center;">
            <? if($invest_finished==false) { ?><a href="./simulation.php?prd_idx=<?=$product_row["idx"]?>" class="btn_big_link" style="width:47%;margin:0;">투자시뮬레이션</a><? } ?>
            <?=$invest_button?>
          </div>
        </div>
      </div>
<?
	}
}
else {
?>
			<div style="<?=(G5_IS_MOBILE)?'margin:20px 0 0 0;':'width:97%; margin:35px 37px 0 0;';?>" >
			  <div style="border:1px solid #CACACA;background-color:#F7F7F7;color:#CACACA;text-align:center;padding-top:150px;padding-bottom:150px;">DATA NOT FOUND</div>
      </div>
<?
}
?>
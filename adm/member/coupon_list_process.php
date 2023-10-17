<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

include_once("_common.php");

	$strPost = ARRAY(
					ARRAY("mb_no","","Y"),ARRAY("cnumber","","Y")
					);

	FOR($i=0;$i<COUNT($strPost);$i++)
	{
		IF($strPost[$i][1] > 0)
		{
			FOR($j=0;$j<COUNT($_POST[$strPost[$i][0]]);$j++)
			{
				IF($j == 0) { ${$strPost[$i][0]} = ""; }
				IF($j > 0)
				{
					${$strPost[$i][0]} .=  ":";
				}
				${$strPost[$i][0]} .= replace_integer(urldecode($_POST[$strPost[$i][0]][$j]));
			}

		} ELSE {
			IF($strPost[$i][2] == "Y")
			{
				IF($_POST[$strPost[$i][0]]<>"")
				{
					${$strPost[$i][0]} = urldecode($_POST[$strPost[$i][0]]);
				} ELSE {
					$objval = ARRAY("retcode"=>"X","retalert"=>STR_REPLACE("+"," ",urlencode("값이 올바르지 않습니다. 다시 시도하여 주십시오 : ".$i)),"retval"=>"");
					ECHO json_encode($objval);
					EXIT;
				}
			} ELSE {
				${$strPost[$i][0]} = urldecode($_POST[$strPost[$i][0]]);
			}
		}
	}
	// 처리페이지
	$Query = "SELECT rcidx FROM hloan_cupoint_reg WHERE cnumber='".TRIM($cnumber)."'";
	$row = sql_fetch($Query);

	IF($row["rcidx"])
	{
		$Q3 = "UPDATE g5_member SET pid='naverpay' WHERE mb_no='".TRIM($mb_no)."'";
		sql_query($Q3);

		$Q1 = "UPDATE hloan_cupoint_reg SET use_date=now() , mem_date=now(), recyn='Y' WHERE rcidx='".$row["rcidx"]."'";
		sql_query($Q1);

		$Q2 = "insert into recommend_reward_log (event_no, member_idx, position, reward_amount, rcidx,	cnumber) values ('8','".TRIM($mb_no)."','recmder',0,'".$row["rcidx"]."','".TRIM($cnumber)."');";
		sql_query($Q2);
	}

	sql_close($connectdb);

	$objval = ARRAY("retcode"=>"OK","retalert"=>STR_REPLACE("+"," ",urlencode("쿠폰이 정상 발급 되었습니다")),"retval"=>"/adm/member/coupon_list.php");
	ECHO json_encode($objval);
	EXIT;
?>
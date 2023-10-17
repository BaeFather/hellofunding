<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
//아이디 처리
?>
<?php
include_once('./_common.php');
include_once('../admin.loan.function.php');
?>
<?php
	$strPost = ARRAY(
							ARRAY("chk","",""),ARRAY("S2","",""),ARRAY("S3","",""),ARRAY("S1","",""),
							ARRAY("STXT","",""),ARRAY("page","","")
					);

	FOR($i=0;$i<COUNT($strPost);$i++)
	{
		IF($strPost[$i][1] > 0)
		{
			$strPostTarget = "";
			FOR($j=0;$j<COUNT($_POST[$strPost[$i][0]]);$j++)
			{
				$strPostVal = "";
				IF($j > 0)
				{
					$strPostTarget .=  ":";
					//${$strPost[$i][0]} .=  ",";
				}
				$strPostVal		 =& $_POST[$strPost[$i][0]][$j];
				$strPostTarget	.= replace_integer($strPostVal);
				//${$strPost[$i][0]} .= $_POST[$strPost[$i][0]][$j];
			}
			${$strPost[$i][0]} = $strPostTarget;

		} ELSE {
			IF($strPost[$i][2] == "Y")
			{
				IF($_POST[$strPost[$i][0]]<>"")
				{
					${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
				} ELSE {
					$objval = ARRAY("retcode"=>"X","retalert"=>STR_REPLACE("+"," ",urlencode("값이 올바르지 않습니다. 다시 시도하여 주십시오")),"retval"=>"");
					ECHO json_encode($objval);
					EXIT;
				}
			} ELSE {
				${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
			}
		}
	}

	IF(!$S3)
	{
		$objval = ARRAY("retcode"=>"OK","retalert"=>STR_REPLACE("+"," ",urlencode("상태를 선택해야 합니다.")),"retval"=>"/adm/withholding/?".$strRet[1]);
//		sql_close();
		ECHO json_encode($objval);
		exit;
	}
	IF(!$S2)
	{
		IF(!$chk[0])
		{
			sql_close($connect_db);
			$objval = ARRAY("retcode"=>"OK","retalert"=>STR_REPLACE("+"," ",urlencode("대상을 하나 이상 선택해야 합니다.")),"retval"=>"/adm/withholding/?".$strRet[1]);
			//sql_close();
			ECHO json_encode($objval);
			exit;
		}
	}

	IF($S2 == "Y") // 모든회원
	{
		$strWhere = " WHERE recyn='N'";

	} ELSE {

		FOR($i=0;$i<COUNT($chk);$i++)
		{
			IF($i == 0)
			{
				$strWhere = " WHERE cwrseq IN (";
			}
			IF($i > 0)
			{
				$strWhere .= ",";
			}
			$strWhere .= "'".$chk[$i]."'";
			IF($i == COUNT($chk)-1)
			{
				$strWhere .= ")";
			}
		}
	}

	$strColumn = ARRAY("recyn");
	$strValues = ARRAY($S3);
	$strTable = "cf_withholding_request";

	fn_general_query_update("update",$strColumn, $strValues,$strTable,"","",$strWhere, $connect_db);
	//sql_close();


	$objval = ARRAY("retcode"=>"OK","retalert"=>STR_REPLACE("+"," ",urlencode("상태값이 정상 반영 되었습니다")),"retval"=>"/adm/withholding/index.php?S1=".$S1."&STXT=".$STXT."&page=".$page);
	ECHO json_encode($objval);
	EXIT;
?>
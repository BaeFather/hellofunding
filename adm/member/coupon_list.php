<?php
include_once('./_common.php');

include_once (G5_ADMIN_PATH.'/admin.head.nomenu.php');
include_once (G5_ADMIN_PATH.'/admin.loan.function.php');

Class Cupoint_List
{
	public $ndate;

	Public Function __construct()
	{
		$this->ndate = DATE("Y-m-d");
	}

	Public Function __destruct()
	{
	}

	Public Function hloan_ad_master()
	{
		$Query = "SELECT pid, company FROM hloan_ad_master WHERE recyn='Y' ORDER BY idx DESC";
		$Result = sql_query($Query);

		$i = 0;
		WHILE($Row=sql_fetch_array($Result))
		{
			$RowPid			=	$Row["pid"];
			$RowCompany		=	$Row["company"];

			$retval[]		=	ARRAY($RowPid, $RowCompany);
			$i++;
		}
		IF($i > 0)
		{
			sql_free_result($Result);
		}

		return $retval;
	}

	Public Function Cupoint_Cnt($pid)
	{
		$Query = "SELECT recyn, COUNT(*) as CNT FROM hloan_cupoint_reg ";
		IF($pid)
		{
			$Query .=" WHERE pid='".add_str($pid)."'";
		}
		$Qyery = " GROUP BY recyn ";

		$Result = sql_query($Query);

		$i = 0;
		WHILE($Row=sql_fetch_array($Result))
		{
			$RowRecyn		=	$Row["recyn"];
			$RowCnt			=	$Row["CNT"];

			$retval[]		=	ARRAY("recyn"=>$RowRecyn, "CNT"=>$RowCnt);
			$i++;
		}
		IF($i > 0)
		{
			sql_free_result($Result);
		}

		return $retval;
	}

	Public Function Cupoint_Reg_List($pid, $strColumn,$page, $num_per_page)
	{
		$strTable = "
		(SELECT t1.rcidx, t1.pid, t1.cnumber, t1.use_date, t1.ava_sdate,t1.ava_edate,t1.recyn, IFNULL(t2.member_idx,'') as mb_no FROM hloan_cupoint_reg t1 LEFT JOIN  recommend_reward_log t2 ON t1.rcidx=t2.rcidx) t1";

		$strWhere = "";
		IF($pid)
		{
			$strWhere =" WHERE pid='".add_str($pid)."'";
		}

		$strlimit2	=	$num_per_page;
		$strOrder =" rcidx DESC";
		$Result = sql_query($Query);

		$rowList = fr_board_list($strColumn,$strTable,$frQuery,$strWhere,$strOrder,"",$strlimit2,"2000",$connect);

		return $rowList;
	}

}

$strCuponClass = new Cupoint_List();

$strHlonAdMaster	=	$strCuponClass->hloan_ad_master();
$strHlonCucnt		=	$strCuponClass->Cupoint_Cnt($pid);
?>
<style>
	.searchGuide {list-style:none;overflow:hidden;margin:20px 0px;}
	.searchGuide .li1 {float:left;}
	.searchGuide .li2 {float:left;padding-left:10px;}
	.searchGuide .li3 {float:left;padding-left:10px;font-size:12px;}

	.searchtable {width:98%;border:1px solid #CCC;padding:0px;}
	.searchtable th {background-color:#EEE;color:#333;font-weight:bold;padding:5px 0;border:1px solid #CCC;font-size:12px;}
	.searchtable td {text-align:center;padding:3px 0;font-size:11px;}
	.inputs {width:120px;text-align:center;border:1px solid #666; height:30px;}
	.fred {color:#ff0000;}
</style>
<form name="sregfm" id="sregfm">
<ul class="searchGuide">
	<li class="li1">
		회원고유번호 : <input type="text" name="mb_no" id="mb_no" class="inputs" required itemname="회원고유번호" />
	</li>
	<li class="li2">
		쿠폰번호 : <input type="text" name="cnumber" id="cnumber"  class="inputs" required  itemname="쿠폰번호" />
	</li>
	<li class="li3">
		<button type="submit" class="btn btn-sm btn-warning" onClick="fn_Issue('sregfm',event);">발급</button>
	</li>
</ul>
</form>

<table class="searchtable">
<tr>
	<th rowspan="2">파트너구분</th>
	<th rowspan="2">쿠폰번호</th>
	<th colspan="2">쿠폰유효기간</th>
	<th rowspan="2">쿠폰사용유무</th>
	<th rowspan="2">쿠폰발급대상</th>
</tr>
<tr>
	<th>쿠폰시작일</th>
	<th>쿠폰만료일</th>
</tr>
<?php
	$num_per_page = 20;
	IF(!$page) { $page = 1; }
	$strColumn = ARRAY("pid","cnumber","use_date","ava_sdate","ava_edate","recyn","mb_no");
	$rowList = $strCuponClass->Cupoint_Reg_List($S1, $strColumn,  $page, $num_per_page);


	IF($rowList[1] > 0)
	{
		FOR($i=0;$i<COUNT($rowList[2]);$i++)
		{
			unset($RowLink);

			FOR($j=0;$j<COUNT($strColumn);$j++)
			{
				${$strColumn[$j]} = $rowList[2][$i][$j];
			}

			ECHO "<tr>\n";
			ECHO "	<td>".$pid."</td>\n";
			ECHO "	<td>".$cnumber."</td>\n";
			ECHO "	<td>".$ava_sdate."</td>\n";
			ECHO "	<td>".$ava_edate."</td>\n";
			ECHO "	<td>".$use_date."</td>\n";
			ECHO "	<td>".$mb_no."</td>\n";
			ECHO "	<td></td>\n";
			ECHO "</tr>\n";

		}
	}
ECHO "</table>";
?>
<?php
$qstr="coupon_list.php?";
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $rowList[0], $qstr);
?>
<?php
sql_close($connect_db);
?>

<script type="text/javascript">
<!--
	function check_form(sval)
	{
		var arr = document.getElementsByName(sval)[0].elements;

		for(var i=0;i<arr.length;i++)
		{
			attAttArr = "";

			if(arr[i].getAttribute("itemname") != undefined)
			{
				if(arr[i].type == "text" || arr[i].type == "textarea" || arr[i].type == "password" || arr[i].type == "select-one")
				{
					if(!arr[i].value) {
							alert(arr[i].getAttribute("itemname")+' 필수 항목 입니다.');
							arr[i].focus();
							return false;
					}
				}

				if(arr[i].getAttribute("itematt") != undefined)
				{
					var attAttArr = arr[i].getAttribute("itematt").split("^");
					if(attAttArr[0] == "int")
					{
						if((parseInt(attAttArr[1]) > parseInt(arr[i].value.length)) || !OnlyNum(arr[i].value))
						{
							alert(arr[i].getAttribute("itemname")+' 는 숫자만 입력이 가능하며 '+attAttArr[1]+' 자 이상이어야 합니다');
							arr[i].value="";
							arr[i].focus();
							return false;
						}
					}
				}

				if(arr[i].getAttribute("itemlan") != undefined)
				{
					var attAttArr = arr[i].getAttribute("itemlan").split("^");
					if(attAttArr[0] == "ko")
					{
						if((parseInt(attAttArr[1]) > parseInt(arr[i].value.length)) || !korCodeCheck(arr[i].value))
						{
							alert(arr[i].getAttribute("itemname")+' 는 한글만 입력이 가능하며 '+attAttArr[1]+' 자 이상이어야 합니다');
							arr[i].value="";
							arr[i].focus();
							return false;
						}
						if (isEmpty(arr[i].value))
						{
							alert(arr[i].getAttribute("itemname")+' 는 공백없이 입력이 가능하며 '+attAttArr[1]+' 자 이상이어야 합니다');
							arr[i].value="";
							arr[i].focus();
							return false;
						}
					}
				}

				if(arr[i].type == "radio" || arr[i].type == "checkbox")
				{
					var radiotrue = false;

					var radioname = arr[i].getAttribute("name");

					var radionamelen = document.getElementsByName(radioname).length;

					for(var j=0;j<radionamelen;j++)
					{
						if(document.getElementsByName(radioname)[j].checked == true)
						{
							 radiotrue = true;
							 break;
						}
					}

					if(radiotrue == false)
					{
						alert(arr[i].getAttribute("itemname")+' 필수 항목 입니다.');
						arr[i].focus();
						return false;
					}
				}
			}
			else
			{
				if(arr[i].getAttribute("itematt") != undefined)
				{
					var attAttArr = arr[i].getAttribute("itematt").split("^");
					if(attAttArr[0] == "int" && parseInt(arr[i].value.length) > 0)
					{
						if((parseInt(attAttArr[1]) > parseInt(arr[i].value.length)) || !OnlyNum(arr[i].value))
						{
							alert(attAttArr[2]+' 는 숫자만 입력이 가능하며 '+attAttArr[1]+' 자 이상이어야 합니다');
							arr[i].value="";
							arr[i].focus();
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	function korCodeCheck($str){
		var str = $str;
		var korCodeCheck = true;
		for(i=0; i<str.length; i++){
			if(!((str.charCodeAt(i) > 0x3130 && str.charCodeAt(i) < 0x318F) || (str.charCodeAt(i) >= 0xAC00 && str.charCodeAt(i) <= 0xD7A3)))
			{
				korCodeCheck = false; //한글이 아닐경우
				break;
			}
		}
		return korCodeCheck
	}

	function onlyNumber(obj) {
		$(obj).keyup(function(){
			 $(this).val($(this).val().replace(/[^0-9]/g,""));
		});
	}

	function fn_Issue(fmname,event)
	{
		if(confirm('정말 발급 하시겠습니까?'))
		{
			if(!event)
			{
			   event =window.event;
			}
			if(event.stopPropagation)
			{
				event.preventDefault();
				event.stopPropagation();
			} else {
				event.cancelBubble = true;
			}

			var form = check_form(fmname);

			if(form == false)
			{
				return false;
			}

			var frm = $('#'+fmname);
			var str = frm.serialize();

			$.ajax({
			type : 'POST',
			url : "coupon_list_process.php",
			data : str,
			dataType: 'json',
			success : function(data){

				if(data.retcode == "OK"){
					var stralert = decodeURIComponent(data.retalert);
						alert(stralert.replace("+"," "));
						window.location = data.retval;

				} else if(data.retcode == "X") {
					var stralert = decodeURIComponent(data.retalert);
						alert(stralert.replace("+"," "));

				}
			},
			error : function(XMLHttpRequest, textStatus, errorThrown){
				alert("처리중 오류가 발생하였습니다. 다시 시도하여주십시오");
				console.log("XMLHttpRequest : "+XMLHttpRequest+", textStatus : "+textStatus);
				console.log(errorThrown);
				return false;
			}
		});
		}
	}

//-->
</script>

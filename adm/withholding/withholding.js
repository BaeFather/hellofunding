// 아이디 체크
var withholdingProcessUrl = "/adm/withholding/process.php";
var withholdingRecynProcessUrl = "/adm/withholding/request.proc.ajax.php";

dropComment = function(commidx) {
	if(confirm('게시글을 삭제 하시겠습니까?')) {
		$.ajax({
			url : "request.proc.ajax.php",
			type: "POST",
			dataType: "JSON",
			data:{
				mode: 'delete',
				commidx: commidx
			},
			success:function(data) {
				if(data.result=='SUCCESS') { alert('삭제 되었습니다.'); window.location.reload(); }
				else { alert(data.message); }
			},
			error:function (e) { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});
	}
}

function fn_number_coma(target, obj, idx)
{
		obj = obj.replace(/,/gi,"");

		if(!OnlyNum(obj))
		{
			alert("숫자만 입력이 가능합니다");
			//$("input[name='"+target+"']").val("");
			return false;
		}
		var retval = numberWithCommas(obj);
		$("input[name='"+target+"']").eq(idx).val(retval);
}

function numberWithCommas(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function fn_calc_ltv()
{
	var tmoney = 0;
	var ddmoney = $("input[name='ddmoney']").val().replace(/,/gi,""); // 희망대출금액
	var examount = 0;

	if(!ddmoney) { ddmoney =0; }

	var examountTarget = $("input[name='examount[]']");
	var kbprice = $("input[name='kbprice']").val(); // 일반가
	var kbllimit = $("input[name='kbllimit']").val(); // 하한가

	if(!kbprice) { kbprice =0; }
	if(!kbllimit) { kbllimit =0; }

	for(var i=0;i<examountTarget.length;i++)
	{
			if(examountTarget.eq(i).val().replace(/,/gi,""))
			{
				examount = examount + parseInt(examountTarget.eq(i).val().replace(/,/gi,""));
			}
	}
	tmoney = parseInt(ddmoney) + parseInt(examount);

	var ltvkind = $("input[name='ltvkind']:checked").val();
	if(!ltvkind)
	{
		alert("일반가나 하한가를 선택하셔야 합니다");
		return false;
	}
	if(ltvkind == "1")
	{
		tmoney = parseInt(tmoney) / parseInt(kbprice.replace(/,/gi,""));
	} else if(ltvkind == "2") {
		tmoney = parseInt(tmoney) / parseInt(kbllimit.replace(/,/gi,""));
	}
	if(tmoney) { tmoney = tmoney * 100; }
	$("input[name='ltvmoney']").val(tmoney.toFixed(2));
}

function check_search(fmname)
{
	var form = check_form(fmname);

	if(form == false)
	{
		return false;
	}
	$("#"+fmname).attr("method","get");
	$("#"+fmname).submit();

}

function check_w_form(fmname, event)
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

	  var checkform = check_form(fmname);

		if(checkform == false)
		{
			  return false;
		}

		var frm = $('#'+fmname);
		var str = frm.serialize();

		$.ajax({
			type : 'POST',
			url : withholdingProcessUrl,
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

function fn_additem_examount(kind)
{
	var t1content = "";
	var t2content = "";
	var t1layer = $("#examountarea");
	var t2layer = $("#maxbondarea");

	var intlength = $("input[name='examount[]']").length;

	if(kind == "plus")
	{
		t1content = "<input type='text' name='examount[]' value='' class='input02' OnKeyUp=\"fn_number_coma('examount[]',this.value, "+intlength+");\" /> ";
		t2content = "<input type='text' name='maxbond[]' value='' class='input02' OnKeyUp=\"fn_number_coma('maxbond[]',this.value, "+intlength+");\" /> ";

		t1layer.append(t1content);
		t2layer.append(t2content);
		}
}



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

// 공백체크
function isEmpty( data ) {
	 for ( var i = 0 ; i < data.length ; i++ )    {
		if ( data.substring( i, i+1 ) == " " )
		 return true;
	 }
	 return false;
}

// 숫자만 입력 기입
function OnlyNum(word)
{
	reOnlyNum = new RegExp("[0-9]", "g");
	var returnValue = true;
	for(i=0;i<word.length;i++)  {
		 if(!(word.substr(i,1).match(reOnlyNum))) {
			returnValue=false;
		}
	}
	return returnValue;
}

function check_view(obj)
{
	var objval = $("#"+obj).html();
	alert(objval);
}

function fn_all_check()
{
	if(allchk == false)
	{
		$('input[name="chk[]"]').each(function() {
				$(this).prop('checked', true);
		});
		allchk = true;
	} else {
		$('input[name="chk[]"]').each(function() {
				$(this).prop('checked', false);
		});
		allchk = false;
	}
}


function fn_member_recyn_att(fmname, event)
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

		var s2 = $('input[name="S2"]:checked').val();
		var s3 = $('select[name="S3"]').val();

		if(!s3)
		{
			alert("상태를 선택하여 주십시오");
			return false;
		}
	  var checkform = check_form(fmname);

		if(checkform == false)
		{
			  return false;
		}

		var frm = $('#'+fmname);
		var str = frm.serialize();
		str += "&S2="+s2+"&S3="+s3;

		$.ajax({
			type : 'POST',
			url : withholdingRecynProcessUrl,
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
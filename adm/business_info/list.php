<?php
$sub_menu = '300800';
include_once('./_common.php');


auth_check($auth[$sub_menu], "w");
if ($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

include_once (G5_ADMIN_PATH.'/admin.head.php');

?>

<style>
.row {
    display: block;
}
.list-box table {
    font-size: 15px;
    text-align: center;
}

.list-box table tr td {
    vertical-align: middle;
}

.btn:focus,.btn:active {
   outline: none !important;
   box-shadow: none;
}

.layer-wrap {
    position: absolute;
    top : 220px;
    display: none;
    width:100%;
    text-align:center;
}

.layer-wrap .layer-back {
    position: fixed;
    background-color: #000000;
    z-index: 100;
    width: 100%;
    height: 100%;
    opacity: 0.7;
    top: 0px;
}

.layer-wrap .layer-popup {
    position: relative;
    background: #ffffff;
    z-index: 110;
    width: 700px;
    margin:0 auto;

    padding: 30px 0 0 10px;
}

.layer-popup label {
    width: 100px;
    text-align: center !important;
    padding: 5px;
}
</style>

<!-- 재무 현황 리스트 :: START -->
<div class="row">
	<div class="col-lg-12">
		<div class="panel-body">
            <!-- 등록하기 버튼 -->
			<p class="text-right">
          <button type="button" class="btn btn-sm btn-success popup_reg_btn" data-section="<?php ECHO $SD;?>">등록하기</button>
			</p>
            <!-- 리스트 :: START -->
			<div class="dataTable_wrapper list-box">
			<?php INCLUDE_ONCE("./list.skin".$SD.".php"); ?>
			</div>
            <!-- 리스트 :: END -->

      <p class="text-center">
          <button type="button" class="btn btn-success popup_list_btn">목록보기</button>
			</p>

		</div>
		<!-- /.panel-body -->
	</div>
	<!-- /.col-lg-12 -->
</div>
<!-- /.row -->
<!-- 대주주 현황 리스트 :: END -->


<!-- 레이어 팝업 :: 등록&수정 :: START -->
<div class="layer-wrap">
	<div class="layer-back"></div>
	<div class="row layer-popup">
		<form name="reg_form" id="reg_form" method="post" action="proc.php" class="form-horizontal">
			<input type="hidden" name="section" value="<?=$SD?>"> <!-- 항목 분류 -->
			<input type="hidden" name="kind" id="kind" value=""> <!-- 등록&수정 분류 -->
			<input type="hidden" name="SE" id="SE" value="">
			<input type="hidden" name="page" value="<?=$page?>">
			<input type="hidden" name="RD" value="<?=$RD?>">
			<div class="col-lg-12" id="pop_area">내용영역</div>
			<p class="text-center">
				<button type="button" class="btn btn-success" id="reg_btn">등록</button>
				<button type="button" class="btn btn-default reg_cls_btn" id="">취소</button>
			</p>
		</form>
	</div>
</div>
<!-- 레이어 팝업 :: 등록&수정 :: END -->

<script type="text/javascript">
$(function(){

  var product_idx = 0;
  /* 레이어팝업 :: START */
  //등록 -> 레이어팝업 보이기
  $(".popup_reg_btn").click(function() {
      $("#reg_btn").text("등록");
      $("#SE").val("");
      $("#kind").val('save');

      var section = $(this).data("section");
      var str = "section=" + section;
      $.ajax({
  			type : 'POST',
  			url : "list.proc.php",
        dataType : "json",
  			data : str,
  			success : function(data){
    				$("#pop_area").html(data.lbody);
    			},
    			error : function(XMLHttpRequest, textStatus, errorThrown){
    				alert("처리중 오류가 발생하였습니다. 다시 시도하여주십시오");
    				return false;
    			}
  		});

      $(".layer-wrap").show();
      $("#layer_pop").show();
  });

	$(document).on("click", "#load_product_info", function() {
		var obj = $("#product_idx").val();
		var str = 'ldx=' + obj;
    $.ajax({
      type : 'POST',
      url : 'proc.list.php',
      data : str,
      dataType: 'json',
      success : function(data){
				$('#category').val(data.retval.category);
				$('#start_num').val(data.retval.start_num);
				$('#recruit_amount').val(data.retval.recruit_amount);
				$('#mask_recruit_amount').val(data.retval.mask_recruit_amount);
			},
			error : function(e){
				alert('처리중 오류가 발생하였습니다. 다시 시도하여주십시오');
				return false;
			}
    });
  });

/*
  $(document).on("blur","#product_idx", function() {
		var obj = $(this).val();
		var str = 'ldx='+obj;
    $.ajax({
      type : 'POST',
      url : 'proc.list.php',
      data : str,
      dataType: 'json',
      success : function(data){
				$('#category').val(data.retval.category);
				$('#start_num').val(data.retval.start_num);
				$('#recruit_amount').val(data.retval.recruit_amount);
			},
			error : function(XMLHttpRequest, textStatus, errorThrown){
				alert('처리중 오류가 발생하였습니다. 다시 시도하여주십시오');
				return false;
			}
    });
  });
*/



  //수정 -> 레이어팝업 보이기
  $(document).on("click", "#mod_btn", function() {
      var section = $(this).data("section");
      var mod_id = $(this).data("num");

      var str = "section="+section+"&SE="+mod_id;
      $.ajax({
  			type : 'POST',
  			url : "list.proc.php",
  			data : str,
        dataType: 'json',
  			success : function(data){
    				$("#pop_area").html(data.lbody);
            if(data.product_idx)  // 매각내역 이라면
            {
              var str2 = 'ldx='+data.product_idx;
              $.ajax({
                type : 'POST',
                url : 'proc.list.php',
                data : str2,
                dataType: 'json',
                success : function(data){
                    $('#category').val(data.retval.category);
                    $('#start_num').val(data.retval.start_num);
                    $('#recruit_amount').val(data.retval.recruit_amount);
                  },
                  error : function(XMLHttpRequest, textStatus, errorThrown){
                    alert('처리중 오류가 발생하였습니다. 다시 시도하여주십시오');
                    return false;
                  }
              });
            }

    			},
    			error : function(XMLHttpRequest, textStatus, errorThrown){
    				alert("처리중 오류가 발생하였습니다. 다시 시도하여주십시오");
    				return false;
    			}
  		});

      $("#SE").val(mod_id);
      $("#reg_btn").text("수정");
      $("#kind").val('update');
      $(".layer-wrap").show();
      $("#layer_pop").show();
  });


  //삭제 -> 레이어팝업 보이기
  $(document).on("click", "#del_btn", function() {
		if(confirm('데이터를 삭제 하시겠습니까?\n삭제 후 복구가 되지 않습니다.')) {
			var del_id = $(this).data("num");
			$("#kind").val('update');

      var str = "section=4&kind=DELETE&SE="+del_id;

			$.ajax({
				type : 'POST',
				url : 'proc.php',
				data : str,
				dataType: 'json',
				success : function(data) {
					if(data.retcode=='OK') {
						alert('삭제되었습니다.');location.reload();
					}
					else {
						alert(data.retalert);
					}
				},
				error : function(XMLHttpRequest, textStatus, errorThrown){
					alert('처리중 오류가 발생하였습니다. 다시 시도하여주십시오');
					return false;
				}
			});

		}
	});


  //취소
  $(".reg_cls_btn").click(function() {
      $(".layer-wrap").hide();
      $("#layer_pop").hide();

      $("#reg_form")[0].reset();
  });

  $(".popup_list_btn").click(function() {
    window.location = "./";
  });

  var doubleSubmitFlag = false;

  function doubleSubmitCheck(){
      if(doubleSubmitFlag){
          return doubleSubmitFlag;
      }else{
          doubleSubmitFlag = true;
          return false;
      }
  }

  //form 데이터 등록
  $("#reg_btn").click(function(e) {
    if(doubleSubmitCheck()) return;

    e.preventDefault();
		e.stopPropagation();

    var str = $("#reg_form").serialize();
    $.ajax({
      type : 'POST',
      url : "proc.php",
      dataType: 'json',
      data : str,
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
          return false;
        }
    });

  });
  /* 레이어팝업 :: END */
});
</script>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>

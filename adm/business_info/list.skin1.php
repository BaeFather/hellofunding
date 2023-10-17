
<table class="table table-striped table-bordered table-hover">
  <colgroup>
      <col width="100px">
      <col width="100px">
      <col width="300px">
      <col width="">
      <col width="200px">
  </colgroup>
  <thead>
    <tr>
      <th class="text-center">NO.</th>
      <th class="text-center">년도</th>
      <th class="text-center">내용</th>
      <th class="text-center">URL</th>
      <th class="text-center">관리</th>
    </tr>
  </thead>
  <tbody id="fin_list">
  <?php FOR($i=0;$i<COUNT($strList[1]);$i++) {  ?>
      <tr class="odd">
          <td align="center"><?php ECHO $strList[1][$i]["idx"];?></td>
          <td align="center"><?php ECHO $strList[1][$i]["biz_year"];?></td>
          <td align="center"><?php ECHO $strList[1][$i]["fin_contents"];?></td>
          <td align="center">
            <?php
      				$strUrl = $Business_Info->Replace_Url($strList[1][$i]["fin_url"]);
      				ECHO $strUrl[0];
      				ECHO "보러가기";
      				ECHO $strUrl[1];
      			?>
          </td>
          <td align="center">
              <button type="button" class="btn btn-primary" data-section="<?php ECHO $SD;?>" data-num="<?php ECHO $strList[1][$i]["idx"];?>" id="mod_btn">수정</button>
          </td>
      </tr>
  <?php } ?>
  </tbody>
</table>

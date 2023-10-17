
<table class="table table-striped table-bordered table-hover">
            <colgroup>
                <col width="100px">
                <col width="100px">
                <col width="">
                <col width="">
                <col width="">
                <col width="200px">
            </colgroup>
  <thead>
    <tr>
      <th class="text-center">NO.</th>
      <th class="text-center">년도</th>
      <th class="text-center">임직원</th>
                    <th class="text-center">여신심사역</th>
      <th class="text-center">전문인력</th>
                    <th class="text-center">관리</th>
    </tr>
  </thead>
  <tbody id="emp_list">
  <?php FOR($i=0;$i<COUNT($strList[1]);$i++) {  ?>
                <tr class="odd">
                    <td align="center"><?php ECHO $strList[1][$i]["idx"];?></td>
                    <td align="center"><?php ECHO $strList[1][$i]["biz_year"];?></td>
                    <td align="center"><?php ECHO $strList[1][$i]["emp_member"];?></td>
                    <td align="center"><?php ECHO $strList[1][$i]["emp_simsa"];?></td>
                    <td align="center"><?php ECHO $strList[1][$i]["emp_professional"];?></td>
                    <td align="center">
                        <button type="button" class="btn btn-primary"  data-section="<?php ECHO $SD;?>" data-num="<?php ECHO $strList[1][$i]["idx"];?>" id="mod_btn">수정</button>
                    </td>
                </tr>
  <?php } ?>
  </tbody>
</table>

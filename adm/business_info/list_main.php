
<style>
.tbl-main table caption {
    padding-top: 0px;
}

table td {
    text-align: center;
}

ul {
    list-style: none;
    padding: 0 10px;
    margin: 0;
}

.dropbox-op ul li {
    float: right;
    width: 100px;
}

.tbl-main .th-title {
    font-size: 15px !important;
}

.tbl-main .th-title .btn-primary {
    color: #ffffff;
    text-decoration: none;
    margin-left: 10px;
}

.tr-shareholder, .tr-employee, .tr-finance{
    display: none;
}
</style>

<!-- 재무 현황 :: START -->
<div class="tbl_head01 tbl_wrap tbl-main">
    <div class="dropbox-op">
        <ul>
            <li>
                <select class="form-control input-sm" id="selectbox_finance">
					<option value=""><?php ECHO $strSelectYear[1];?></option>
                </select>
            </li>
        </ul>
    </div>
    <table class="tbl-finance">
        <caption>재무현황 안내</caption>
        <colgroup>
            <col width="100%">
        </colgroup>
        <thead>
            <tr>
                <th class="th-title">
                    재무현황
                    <a href="./?RD=2&SD=1" class="btn btn-primary">관리</a>
                </th>
            </tr>
        </thead>
        <tbody id="tb-finance">
			<?php
				$strUrl = $Business_Info->Replace_Url($strList[1][2]);
				ECHO "<td>";
				ECHO $strUrl[0];
				ECHO $strList[1][1];
				ECHO $strUrl[1];
				ECHO "</td>";
			?>
        </tbody>
    </table>
</div>
<!-- 재무 현황 :: END -->

<!-- 임직원 현황 :: START -->
<div class="tbl_head01 tbl_wrap tbl-main">
    <div class="dropbox-op">
        <ul>
            <li>
                <select class="form-control input-sm" id="selectbox_employee">
					<option value=""><?php ECHO $strSelectYear[2];?></option>
                </select>
            </li>
        </ul>
    </div>
    <table class="tbl-employee">
        <caption>임직원 현황 안내</caption>
        <colgroup>
            <col width="33.3%">
            <col width="33.3%">
            <col width="33.3%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3" class="th-title">
                    임직원 현황
                    <a href="./?RD=2&SD=2" class="btn btn-primary">관리</a>
                </th>
            </tr>
        </thead>
        <tbody id="tb-employee">
            <tr>
                <td>임직원</td>
                <td>여신심사역</td>
                <td>전문인력</td>
            </tr>
            <tr>
                <td><?php ECHO $strList[2][2];?></td>
                <td><?php ECHO $strList[2][3];?></td>
                <td><?php ECHO $strList[2][4];?></td>
            </tr>
        </tbody>
    </table>
</div>
<!-- 임직원 현황 :: END -->

<!-- 대주주 현황 :: START -->
<div class="tbl_head01 tbl_wrap tbl-main">
    <div class="dropbox-op">
        <ul>
            <li>
                <select class="form-control input-sm" id="selectbox_shareholder">
					             <option value=""><?php ECHO $strSelectYear[3];?></option>
                </select>
            </li>
        </ul>
    </div>
    <table class="tbl-shareholder">
        <caption>대주주 현황 안내</caption>
        <colgroup>
            <col width="100%">
        </colgroup>
        <thead>
            <tr>
                <th class="th-title">
                    대주주 현황
                    <a href="./?RD=2&SD=3" class="btn btn-primary">관리</a>
                </th>
            </tr>
        </thead>
        <tbody id="td-shareholder">
            <tr>
                <td><?php ECHO $strList[3][2];?></td>
            </tr>
        </tbody>
    </table>
</div>
<!-- 대주주 현황 :: END -->

<!-- 매각내역 :: START -->
<div class="tbl_head01 tbl_wrap tbl-main">
    <table>
        <caption>매각 내역 안내</caption>
        <colgroup>
            <col width="100%">
        </colgroup>
        <thead>
            <tr>
                <th class="th-title">
                    매각 내역
                    <a href="./?RD=2&SD=4" class="btn btn-primary">관리</a>
                </th>
            </tr>
        </thead>
    </table>
</div>
<!-- 매각내역  :: END -->

<?php
Class Business_Info
{
  private $strTable;
  private $ndate;
  private $strDomain;

  Public Function __construct()
  {
	$this->ndate = DATE("Y-m-d");
    $this->strTable = "cf_biz_info_re";
    $this->strDomain = "dev.hellofunding.co.kr";
	}

  Public Function __destruct()
  {
  }

  public Function Fn_Category()
  {
     $retArr = ARRAY(
				ARRAY("", "", ARRAY("")),
				ARRAY("1", "재무현황",    ARRAY("idx","fin_contents","fin_url","biz_year")),
				ARRAY("2", "임직원 현황", ARRAY("idx","biz_year","emp_member","emp_simsa","emp_professional")),
				ARRAY("3", "대주주 현황", ARRAY("idx","biz_year","major_shareholder")),
				ARRAY("4", "매각 내역",   ARRAY("idx","product_idx","sale_amount","sale_place","sale_date","product_mask_title","mask_recruit_amount"))
     );

     return $retArr;
  }

  Public Function Replace_Url($obj)
  {
	  $retVal1 = "";
	  $retVal2 = "";

	  IF($obj)
	  {
		  IF(strpos($obj,"http") == false)
		  {
			$obj = "http://".$obj;
		  }
      $retVal1 = "<a href='".$obj."' target='_blank'>";
		  $retVal2 = "</a>";
	  }

	  return ARRAY($retVal1, $retVal2);
  }

  Public Function fn_product_type_val()
  {
    $retval = ARRAY(
			ARRAY("1","동산"),
			ARRAY("2","부동산(주택담보)"),
			ARRAY("3","PF"),
			ARRAY("4","매출채권")
		);
    return $retval;
  }

  Public Function fn_product_type_select($obj)
  {
    $strValues = $this->fn_product_type_val();

    $retval = "<select name='product_type' class='form-control input-sm'>";
    FOR($i=0;$i<COUNT($strValues);$i++)
    {
      $retval .= "<option value='".$strValues[$i][0]."'";
      IF($strValues[$i][0] == $obj)
      {
        $retval .= " selected";
      }
      $retval .= ">".$strValues[$i][1]."</option>";
    }
    $retval .= "</select>";

    return $retval;
  }

  Public Function fn_product_type($obj)
  {
    $strValues = $this->fn_product_type_val();
    FOR($i=0;$i<COUNT($strValues);$i++)
    {
      IF($strValues[$i][0] == $obj)
      {
        $retVal = $strValues[$i][1];
        break;
      }
    }
    return $retVal;
  }

  Public Function Fn_Date_Select($section="")
  {
	$strWhere = "";
	$strOrder = "";

	IF(!$section) {
		$strQuery = "
				SELECT section,biz_year FROM
				(
					SELECT '1' as section, MAX(biz_year) as biz_year FROM ".$this->strTable." WHERE section='1'
					UNION ALL
					SELECT '2' as section, MAX(biz_year) as biz_year FROM ".$this->strTable." WHERE section='2'
					UNION ALL
					SELECT '3' as section, MAX(biz_year) as biz_year FROM ".$this->strTable." WHERE section='3'
				) t1";
	}
	ELSE {
		IF($section)
		{
			$strWhere = " WHERE section = '".$section."'";
		}
		$strOrder = " section ASC";
	}

    $Result = sql_query($strQuery.$strWhere.$strOrder);

    $i = 0;
    WHILE($Row=sql_fetch_array($Result))
    {
			$strSection = $Row["section"];
      $strBizYear[$strSection] = $Row["biz_year"];
      $i++;
    }
    IF($i > 0)
    {
      sql_free_result($Result);
    }

    return $strBizYear;
  }

  Public Function Fn_Main_List()
  {
		$strColumn = ARRAY(
			"section",
			"biz_year",
			"fin_contents",
			"fin_url",
			"emp_member",
			"emp_simsa",
			"emp_professional",
			"major_shareholder",
			"product_idx",
			"product_type",
			"start_num",
			"recruit_amount",
			"sale_amount",
			"sale_place",
			"sale_date",
			"product_mask_title",
			"mask_recruit_amount"
		);

		$strSection = ARRAY("1","2","3");

		FOR($i=0;$i<COUNT($strSection);$i++)
		{
			IF($i > 0)
			{
				$strQuery .= "UNION ALL";
			}
			$strQuery .= "
				SELECT * FROM (SELECT MAX(biz_year) as biz_year FROM ".$this->strTable." WHERE section='".$strSection[$i]."') t1 LEFT JOIN ".$this->strTable." t2 ON t1.biz_year=t2.biz_year WHERE t2.section='".$strSection[$i]."'";
    }

    $Result = sql_query($strQuery);

    $i = 0;
    WHILE($Row=sql_fetch_array($Result))
    {
  		FOR($j=0;$j<COUNT($strColumn);$j++)
  		{
  			${$strColumn[$j]} = $Row[$strColumn[$j]];
  		}

  		$strColumnSub = $this->Fn_Column($section);

  		FOR($k=0;$k<COUNT($strColumnSub);$k++)
  		{
  			$retVal[$section][] = ${$strColumnSub[$k]};
  		}

	    $i++;
    }
    IF($i > 0)
    {
      sql_free_result($Result);
    }

		return $retVal;
  }

  Public Function fn_category_txt($strCategory, $strMortaggeGuarantees)
  {
    //if($strCategory=='1') { $retval =  ($strMortaggeGuarantees=='1') ? '부동산 담보' : '주택담보'; }  전승찬 수정
    //if($strCategory=='2') { $retval =  ($strMortaggeGuarantees=='1') ? '부동산 담보' : '주택담보'; }
		if($strCategory=='2') { $retval =  ($strMortaggeGuarantees=='1') ? '주택담보' : '부동산담보'  ; }
    else if($strCategory=='3') $retval = '매출채권';
    else $retval = '동산';

    return $retval;
  }

  Function productTable()
  {
    $retval = "
			(
				SELECT
					A.idx, A.biz_year,A.section, A.product_idx, A.sale_amount, A.sale_place, A.sale_date,
					B.start_num, B.recruit_amount, B.category, B.category2, B.mortgage_guarantees,
					A.product_mask_title, A.mask_recruit_amount,
					( B.recruit_amount - (SELECT IFNULL(SUM(principal),0) FROM cf_product_give WHERE product_idx=A.product_idx AND banking_date IS NOT NULL) ) AS remain_principal
				FROM
					cf_biz_info_re A
				LEFT JOIN
					cf_product B  ON A.product_idx=B.idx
				WHERE
					A.section = '4'
			) t1";
    return $retval;
  }

  Public Function Fn_List($section,$intLimit, $num_per_page)
  {
    IF($section == "4")
    {
      $this->strTable = $this->productTable();
      $strColumn = ARRAY(
				"idx",
				"biz_year",
				"product_idx",
				"sale_amount",
				"sale_place",
				"sale_date",
				"category",
				"mortgage_guarantees",
				"start_num",
				"recruit_amount",
				"product_mask_title",
				"mask_recruit_amount"
			);
      $strWhere  = "";
      $strOrder  = " ORDER BY sale_date DESC";
    } ELSE {
      $strColumn = $this->Fn_Column($section);
      $strWhere  = " WHERE section='".$section."'";
      $strOrder  = " ORDER BY biz_year DESC";
    }

    $strQuery = "SELECT ";
    FOR($i=0;$i<COUNT($strColumn);$i++)
    {
      IF($i > 0) { $strQuery .= ","; }
      $strQuery .= $strColumn[$i];
    }
    $strQuery .= " FROM ".$this->strTable;
    $strLimit  = " LIMIT ".$intLimit.",".$num_per_page;

    $Result = sql_query($strQuery.$strWhere.$strOrder.$strLimit);

    $i = 0;
    WHILE($Row=sql_fetch_array($Result))
    {
  		FOR($j=0;$j<COUNT($strColumn);$j++)
  		{
  			$retVal[$i][$strColumn[$j]] = $Row[$strColumn[$j]];
  		}
      $i++;
		}

    IF($i > 0)
    {
      sql_free_result($Result);
    }

    $RowCnt = sql_fetch("SELECT COUNT(*) as CNT FROM ".$this->strTable.$strWhere);
    $intCnt = $RowCnt["CNT"];

	  return ARRAY($intCnt, $retVal);
  }

  Public Function Fn_View($section,$SE)
  {
    IF($section == "4")
    {
      $this->strTable = $this->productTable();
      $strColumn = ARRAY(
				"idx",
				"biz_year",
				"product_idx",
				"sale_amount",
				"sale_place",
				"sale_date",
				"category",
				"mortgage_guarantees",
				"start_num",
				"recruit_amount",
				"product_mask_title",
				"mask_recruit_amount"
			);
    }
		ELSE {
      $strColumn = $this->Fn_Column($section);
    }

    $strQuery = "SELECT ";
    FOR($i=0;$i<COUNT($strColumn);$i++)
    {
      IF($i > 0) { $strQuery .= ","; }
      $strQuery .= $strColumn[$i];
    }
    $strQuery .= " FROM ".$this->strTable;
    $strWhere  = " WHERE idx='".$SE."'";

    $Result = sql_query($strQuery.$strWhere);

    IF($Row=sql_fetch_array($Result))
    {
  		FOR($j=0;$j<COUNT($strColumn);$j++)
  		{
  			$retVal[$strColumn[$j]] = $Row[$strColumn[$j]];
  		}
      sql_free_result($Result);
		}

	  return $retVal;
  }

  Public Function Fn_Category_Sum($intProductIdx)
  {
      $this->strTable = "cf_product";
      $strColumn = ARRAY("start_num", "category", "mortgage_guarantees", "recruit_amount");
      $strQuery = "SELECT ";
      FOR($i=0;$i<COUNT($strColumn);$i++)
      {
        IF($i > 0) { $strQuery .= ","; }
        $strQuery .= $strColumn[$i];
      }
      $strQuery .= " FROM ".$this->strTable;
      $strWhere  = " WHERE idx='".$intProductIdx."'";

      $Result = sql_query($strQuery.$strWhere);

      IF($Row=sql_fetch_array($Result))
      {
    		FOR($j=0;$j<COUNT($strColumn);$j++)
    		{
    		  ${$strColumn[$j]} = $Row[$strColumn[$j]];
    		}
        sql_free_result($Result);
  		}

      $strCategoryTxt = $this->fn_category_txt($category, $mortgage_guarantees);

      return ARRAY(
                    "category" => $strCategoryTxt,
                    "start_num" => $start_num,
                    "recruit_amount" => $recruit_amount
                  );
  }


  Public Function Fn_Column($section)
  {
    $target = $this->fn_category();
    $strRet   = $target[$section][2];

    return $strRet;
  }

  Public Function Fn_Title($section)
  {
    $target = $this->fn_category();
    $strRet   = $target[$section][1];

    return $strRet;
  }

  Public Function Fn_Board_Process($strKind,$strSection, $strValues, $SE)
  {
    IF($strSection)
    {
      $strCategory  = $this->fn_category();
      $strColumn    = $strCategory[$strSection][2];

      $intColumn = COUNT($strColumn);
      $intValues = COUNT($strValues);

      IF($intColumn == $intValues) {
        IF($strKind == "save") {
					$strQuery = "INSERT INTO ";
					$strWhere = "";
        }
				ELSE IF($strKind == "update") {
					$strQuery = "UPDATE ";
					$strWhere = " WHERE idx ='".$SE."'";
        }
        $strQuery .= $this->strTable." SET ";

        $j = 0;
        FOR($i=0;$i<COUNT($strColumn);$i++)
        {
          IF($strColumn[$i] <> "idx")
          {
            IF($j > 0) { $strQuery .= ","; }
            $strQuery .= $strColumn[$i]."='".sql_real_escape_string($strValues[$i])."'";
            $j++;
          }
        }
      }

			IF($strKind == "save")
      {
        $strQuery .= ",section='".$strSection."'";
      }

			$retQuery = $strQuery . $strWhere;
			sql_query($retQuery);

      IF($strKind == "save")
      {
        $SE = sql_insert_id();
      }

      return $SE;
    }
  }
}
?>

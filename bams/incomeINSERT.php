<?php
//initialize the session
if (!isset($_SESSION)) {
  session_start();
}

// ** Logout the current user. **
$logoutAction = $_SERVER['PHP_SELF']."?doLogout=true";
if ((isset($_SERVER['QUERY_STRING'])) && ($_SERVER['QUERY_STRING'] != "")){
  $logoutAction .="&". htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_GET['doLogout'])) &&($_GET['doLogout']=="true")){
  //to fully log out a visitor we need to clear the session varialbles
  $_SESSION['MM_Username'] = NULL;
  $_SESSION['MM_UserGroup'] = NULL;
  $_SESSION['PrevUrl'] = NULL;
  unset($_SESSION['MM_Username']);
  unset($_SESSION['MM_UserGroup']);
  unset($_SESSION['PrevUrl']);
	
  $logoutGoTo = "index.php";
  if ($logoutGoTo) {
    header("Location: $logoutGoTo");
    exit;
  }
}
?>
<?php
if (!isset($_SESSION)) {
  session_start();
}
$MM_authorizedUsers = "admin,user";
$MM_donotCheckaccess = "false";

// *** Restrict Access To Page: Grant or deny access to this page
function isAuthorized($strUsers, $strGroups, $UserName, $UserGroup) { 
  // For security, start by assuming the visitor is NOT authorized. 
  $isValid = False; 

  // When a visitor has logged into this site, the Session variable MM_Username set equal to their username. 
  // Therefore, we know that a user is NOT logged in if that Session variable is blank. 
  if (!empty($UserName)) { 
    // Besides being logged in, you may restrict access to only certain users based on an ID established when they login. 
    // Parse the strings into arrays. 
    $arrUsers = Explode(",", $strUsers); 
    $arrGroups = Explode(",", $strGroups); 
    if (in_array($UserName, $arrUsers)) { 
      $isValid = true; 
    } 
    // Or, you may restrict access to only certain users based on their username. 
    if (in_array($UserGroup, $arrGroups)) { 
      $isValid = true; 
    } 
    if (($strUsers == "") && false) { 
      $isValid = true; 
    } 
  } 
  return $isValid; 
}

$MM_restrictGoTo = "index.php";
if (!((isset($_SESSION['MM_Username'])) && (isAuthorized("",$MM_authorizedUsers, $_SESSION['MM_Username'], $_SESSION['MM_UserGroup'])))) {   
  $MM_qsChar = "?";
  $MM_referrer = $_SERVER['PHP_SELF'];
  if (strpos($MM_restrictGoTo, "?")) $MM_qsChar = "&";
  if (isset($QUERY_STRING) && strlen($QUERY_STRING) > 0) 
  $MM_referrer .= "?" . $QUERY_STRING;
  $MM_restrictGoTo = $MM_restrictGoTo. $MM_qsChar . "accesscheck=" . urlencode($MM_referrer);
  header("Location: ". $MM_restrictGoTo); 
  exit;
}
?>
<?php require_once('Connections/MySQL_Union.php'); ?>
<?php
function GetSQLValueString($MySQL_Union, $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

function WesternizeDate($ROCdate) {
	$ROCdate = explode('-', $ROCdate);
	$ROCdate[0] = $ROCdate[0] + 1911;
	$Westerndate = implode('-', $ROCdate);
	return $Westerndate;
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}
/* Now calculate the rates per the entered date in the form. Union Dues and Medical Ins 
are calculated monthly, Labor Ins is daily */
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  // if new member checkbox then set new member dues
  $newMemDues = 20;
  $newMemDues2 = 0;
  if ($_POST['newMemberTag'] == 1) {
  	$newMemDues = 20;
	  $newMemDues2 = 1000;
  }
  
  // convert ROC to Western Date
  $beginDate = $_POST['startDate'];
  $beginDate = WesternizeDate($beginDate);

  $insertSQL = sprintf("
  INSERT INTO income (idNumber, monthNum, duesYear, duesHalf, unionDues, laborIns, medIns, newMemDues, newMemDues2, billType)
  SELECT %1\$s AS idNumber, 
    IF(m.monthlyBill, rM.monthNum, NULL) AS monthNum, 
    YEAR(%2\$s) AS duesYear, 
    IF(QUARTER(%2\$s) > 2, 2, 1) AS duesHalf, 
    IF(m.monthlyBill, rM.unionDues, rBi.unionDues) AS unionDues,
    IF(m.monthlyBill, rM.laborIns, rBi.laborIns) AS laborIns,
    IF(m.monthlyBill, rM.medIns, rBi.medIns) AS medIns,
    IF(m.monthlyBill, rM.newMemDues, rBi.newMemDues) AS newMemDues,
    IF(m.monthlyBill, rM.newMemDues2, rBi.newMemDues2) AS newMemDues2, 
    3 AS billType
  FROM (members m, 
  /* Start section for monthlyBill members */
  ((
  /* Start of monthly calculation for whole months */
  SELECT idNumber, monthNum, unionDues, laborIns, medIns, %3\$s AS newMemDues, %4\$s AS newMemDues2
  FROM (
    SELECT mem.idNumber, mem.monthlyBill, ROUND(uR.unionDues / 6) AS unionDues,
      IF(mem.insureLabor, (CEILING(uR.laborIns * hD.percentage/100)), 0) laborIns,
      IF(mem.insureHealth, (FLOOR(((uR.medIns) * LEAST(IFNULL(d.numDeps,0)+1, 4)) - 
        (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * uR.medIns)))), 0)  medIns
    FROM members mem
    JOIN (
      /* get rates the billing period will start in */
      SELECT uRR.ID, uRR.salary, unionDues, laborIns, medIns
      FROM unionRates uRR
      INNER JOIN (
        SELECT MAX(ID) AS ID, salary
        FROM unionRates
        WHERE laborIns_dateEffective <= %2\$s
        AND medIns_dateEffective <= %2\$s
        GROUP BY salary
          ) latest_uR
        ON uRR.ID = latest_uR.ID
      WHERE (salEndDate > %2\$s OR ISNULL(salEndDate))
          ) uR
      USING (salary)
    JOIN handicapAdjustment hD ON IFNULL(mem.handicap,1) = hD.level
    LEFT JOIN (
      /* get number of deps + handicap discount */
      SELECT dep.idParent, SUM(hDis.discount) depDis, COUNT(*) numDeps
      FROM dependents dep, handicapDiscount hDis
      WHERE hDis.level = dep.handicap
      AND hDis.level < 4
      AND dep.inactive IS NULL
      GROUP BY dep.idParent
        ) d
      ON mem.idNumber = d.idParent
    WHERE mem.idNumber = %1\$s
    ) wholeMonthCalc
  NATURAL JOIN billingMonths
  WHERE billingMonths.duesHalf = (IF(QUARTER(%2\$s) > 2, 2, 1))
  AND billingMonths.monthNum > MONTH(%2\$s)

  ) UNION (

  /* Start of monthly calculation for pro rata month */
  SELECT idNumber, MONTH(%2\$s) AS monthNum, unionDues, laborIns, medIns, 
    %3\$s AS newMemDues, %4\$s AS newMemDues2
  FROM (
    SELECT mem.idNumber, mem.monthlyBill, ROUND(uR.unionDues / 6) AS unionDues,
      IF(mem.insureLabor, (CEILING(uR.laborIns * hD.percentage/100 * dC.factorDay)), 0) laborIns,
      IF(mem.insureHealth, (FLOOR(((uR.medIns) * LEAST(IFNULL(d.numDeps,0)+1, 4)) - 
        (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * uR.medIns)))), 0)  medIns
    FROM (members mem, 
      (SELECT (DATEDIFF(LAST_DAY(%2\$s), %2\$s) + 1) / DAY(LAST_DAY(%2\$s)) AS factorDay) dC
      )
    JOIN (
      /* get rates the billing period will start in */
      SELECT uRR.ID, uRR.salary, unionDues, laborIns, medIns
      FROM unionRates uRR
      INNER JOIN (
        SELECT MAX(ID) AS ID, salary
        FROM unionRates
        WHERE laborIns_dateEffective <= %2\$s
        AND medIns_dateEffective <= %2\$s
        GROUP BY salary
          ) latest_uR
        ON uRR.ID = latest_uR.ID
      WHERE (salEndDate > %2\$s OR ISNULL(salEndDate))
          ) uR
      USING (salary)
    JOIN handicapAdjustment hD ON IFNULL(mem.handicap,1) = hD.level
    LEFT JOIN (
      /* get number of deps + handicap discount */
      SELECT dep.idParent, SUM(hDis.discount) depDis, COUNT(*) numDeps
      FROM dependents dep, handicapDiscount hDis
      WHERE hDis.level = dep.handicap
      AND hDis.level < 4
      AND dep.inactive IS NULL
      GROUP BY dep.idParent
        ) d
      ON mem.idNumber = d.idParent
    WHERE mem.idNumber = %1\$s
  ) proRataMonthCalc
  )) rM, 

  /* Start section for bi-annual members */
  (
  SELECT idNumber, unionDues, laborIns, medIns, newMemDues, %4\$s AS newMemDues2
  FROM (
    SELECT mem.idNumber, ROUND(uR.unionDues * dC.factorMonth) AS unionDues,
      IF(mem.insureLabor=0, 0, (CEILING(uR.laborIns * hD.percentage/100 * dC.factorDay
        + uR.laborIns * hD.percentage/100 * dC.monthsLeft))) laborIns,
      IF(mem.insureHealth=0, 0, (FLOOR((((uR.medIns * 6) * LEAST(IFNULL(d.numDeps,0)+1, 4)) - 
        (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * uR.medIns) * 6)) * dC.factorMonth)))  medIns,
      (%3\$s * 6 * dC.factorMonth) AS newMemDues
    FROM (members mem, (
      SELECT (DATEDIFF(LAST_DAY(%2\$s), %2\$s) + 1) / DAY(LAST_DAY(%2\$s)) AS factorDay,
        IF(MONTH(%2\$s) < 7, 
          (7 - MONTH(%2\$s))/6,
          (13 - MONTH(%2\$s))/6) AS factorMonth,
        IF(MONTH(%2\$s) < 7, 
          (6 - MONTH(%2\$s)),
          (12 - MONTH(%2\$s))) AS monthsLeft
      ) dC
      )
    JOIN (
      /* get rates the billing period will start in */
      SELECT uRR.ID, uRR.salary, unionDues, laborIns, medIns
      FROM unionRates uRR
      INNER JOIN (
        SELECT MAX(ID) AS ID, salary
        FROM unionRates
        WHERE laborIns_dateEffective <= %2\$s
        AND medIns_dateEffective <= %2\$s
        GROUP BY salary
          ) latest_uR
        ON uRR.ID = latest_uR.ID
      WHERE (salEndDate > %2\$s OR ISNULL(salEndDate))
          ) uR
      USING (salary)
    JOIN handicapAdjustment hD ON IFNULL(mem.handicap,1) = hD.level
    LEFT JOIN (
      /* get number of deps + handicap discount */
      SELECT dep.idParent, SUM(hDis.discount) depDis, COUNT(*) numDeps
      FROM dependents dep, handicapDiscount hDis
      WHERE hDis.level = dep.handicap
      AND hDis.level < 4
      AND dep.inactive IS NULL
      GROUP BY dep.idParent
        ) d
      ON mem.idNumber = d.idParent
    WHERE mem.idNumber = %1\$s
    ) calc
  ) AS rBi)
  WHERE m.idNumber = %1\$s
  ORDER BY monthNum ASC
  ",
            GetSQLValueString($MySQL_Union, $_GET['idNumber'], "text"),	// 1
            GetSQLValueString($MySQL_Union, $beginDate, "date"),			// 2
					  $newMemDues,										// 3
					  $newMemDues2);									// 4

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));
  
  $insertGoTo = "memberDETAIL.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));

}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 增加繳款記錄 - Insert Pro Rata Income</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("startDate"));

	return true;
}
</script>
<!-- InstanceEndEditable --><!-- InstanceParam name="NavBarLeft" type="boolean" value="false" -->
<script type="text/javascript">
<!--
function openHelpWindow(winName,features) { //v2.0
  var sPath = window.location.pathname;
  var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);
  sPage = sPage.replace(/php/,"html");
  theURL = "help/" + sPage;
  window.open(theURL,winName,features);
}
//-->
</script>
</head>
<!-- The structure of this file is exactly the same as 2col_rightNav.html;
     the only difference between the two is the stylesheet they use -->
<body>
<div id="masthead">
  <div id="logout"><!-- InstanceBeginEditable name="logout" --><a href="<?php echo $logoutAction ?>" class="red">登出</a><!-- InstanceEndEditable --></div>
  <h1 id="siteName">音樂工會</h1>
  <div id="globalNav"> <a href="memberSEARCH.php">首頁 - 會員搜尋</a> | <a href="transactionSEARCH.php">收入與支出</a> | <a href="assetSEARCH.php">資產與負債</a> | <a href="billingUnpaidDUES.php"> 繳費作業</a></div>
</div>
<!-- end masthead -->
<div id="content">
  <div id="breadCrumb">
    <div id="help"><a href="#" onclick="openHelpWindow('help','scrollbars=yes,resizable=yes,width=400,height=400')">輔助說明</a></div>
    <!-- InstanceBeginEditable name="Breadcrumbs" --> <a href="memberSEARCH.php">首頁
    - 會員搜尋</a> / 成員搜索結果 / <a href="memberDETAIL.php?recordID=<?php echo $_GET['recordID']; ?>">會員資料總表</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->增加繳款記錄<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1">
      <table class="tableForm">
        <tr>
          <th class="tableForm">開始日期:</th>
          <td class="tableForm"><input type="text" id="startDate" name="startDate" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" />
            (請鍵入開始計費日期 年-月-日)</td>
        </tr>
        <tr>
          <th class="tableForm">新加入:</th>
          <td class="tableForm"><input name="newMemberTag" type="checkbox" id="newMemberTag" value="1" /></td>
        </tr>
        <tr>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm"><input name="buttonInsert" type="submit" id="buttonInsert" value="計算自即日起插入之繳款費" /></td>
        </tr>
        <tr>
          <td colspan="2" class="tableForm">(電腦會自動計算自即日起至最近一期的繳費截止日之費率)</td>
        </tr>
      </table>
      <input type="hidden" name="MM_insert" value="form1" />
    </form>
    <!-- InstanceEndEditable -->
    </div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>

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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

// insert new bills for selected period
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "bi-annual")) {
  $insertSQL = sprintf("
/* set the period start date to use in the query */
INSERT INTO income ( idNumber, monthNum, duesYear, duesHalf, unionDues, laborIns, medIns, newMemDues, changeDate, billType)
SELECT idNumber, monthNum, %1\$s + 1911 AS duesYear, %2\$s AS duesHalf, unionDues, laborIns, medIns, newMemDues, 
  CURDATE() AS changeDate, 1 AS billType
FROM 
(
	SELECT mem.monthlyBill, mem.idNumber, mem.salary,
		ROUND(IF(mem.monthlyBill, uR.unionDues / 6, uR.unionDues)) AS unionDues,
		IF(mem.insureLabor, 
			IF(mem.monthlyBill, CEILING(uR.laborIns * hD.percentage/100), (CEILING(uR.laborIns * hD.percentage/100) * 6)), 0) AS laborIns,
		IF(mem.insureHealth, 
			IF(mem.monthlyBill, ((uR.medIns * LEAST(IFNULL(d.numDeps,0)+1, 4)) - (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * uR.medIns))), 
			(((uR.medIns * 6) * LEAST(IFNULL(d.numDeps,0)+1, 4)) - (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * uR.medIns) * 6))), 0) AS medIns,
		IF(mem.monthlyBill, 20, 120) AS newMemDues
	FROM members mem
	JOIN (
			/* get rates the period billing will start in */
			SELECT uRR.ID, uRR.salary, unionDues, laborIns, medIns
			FROM unionRates uRR
			INNER JOIN (
				SELECT MAX(ID) AS ID, salary
				FROM unionRates
				WHERE laborIns_dateEffective <= CONCAT(%1\$s + 1911,'-0',IF(%2\$s=1,1,7),'-01')
				AND medIns_dateEffective <= CONCAT(%1\$s + 1911,'-0',IF(%2\$s=1,1,7),'-01')
				GROUP BY salary
					) latest_uR
				ON uRR.ID = latest_uR.ID
			WHERE (salEndDate > CONCAT(%1\$s + 1911,'-0',IF(%2\$s=1,1,7),'-01') OR ISNULL(salEndDate))
					) uR
			USING (salary)
	JOIN handicapAdjustment hD ON IFNULL(mem.handicap,1) = hD.level
	LEFT JOIN 
		(
		SELECT dep.idParent, SUM(hDis.discount) depDis, COUNT(*) numDeps
		FROM dependents dep, handicapDiscount hDis
		WHERE hDis.level = dep.handicap
		AND hDis.level < 4
		AND dep.inactive IS NULL
		GROUP BY dep.idParent
		) d
		ON mem.idNumber = d.idParent
	WHERE mem.inactive IS NULL
) calc
NATURAL JOIN billingMonths
WHERE billingMonths.duesHalf IN (0, %2\$s)
",
                       GetSQLValueString($MySQL_Union, $_POST['duesYear'], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['duesHalf'], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));

  $insertGoTo = "billingUnpaidDUES.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}

// duesDiff insert for income bumped members
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "duesDiff")) {
  $insertSQL = sprintf("
INSERT INTO income 
	(idNumber, monthNum, duesYear, duesHalf, unionDues, laborIns, medIns, newMemDues, newMemDues2, changeDate, billType)
SELECT idNumber, 0 AS monthNum, 
	YEAR(DATE_ADD(CURDATE(),INTERVAL 1 MONTH)) AS duesYear, 
	ROUND(QUARTER(DATE_ADD(CURDATE(),INTERVAL 1 MONTH))/2) AS duesHalf, 
	0 AS unionDues,
	GREATEST(CEILING((laborInsAdj - laborInsBilled) * monthsLeft),0) AS laborInsDiff,
	GREATEST(CEILING((medInsAdj - medInsBilled) * monthsLeft),0) AS medInsDiff,
	0 AS newMemDues, 0 AS newMemDues2, CURDATE() AS changeDate, 2 AS billType
FROM (
	SELECT mem.idNumber, incomeChangeDate, changeDateSal, 
  ABS((IF(MONTH(changeDateSal) BETWEEN 6 AND 12, 13, 7)) - MONTH(changeDateSal)) AS monthsLeft,
		laborInsBilled, medInsBilled,
		IF(insureLabor, CEILING(uR.laborIns * hD.percentage/100), 0) AS laborInsAdj,
		IF(insureHealth, ((uR.medIns) * LEAST(IFNULL(d.numDeps,0)+1, 4)) - 
			(FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * uR.medIns)), 0) AS medInsAdj
	FROM members mem
	JOIN (
		/* get what members have been billed for already */
		SELECT idNumber, MAX(income.changeDate) AS incomeChangeDate,
			(SUM(income.laborIns)/6) AS laborInsBilled, (SUM(income.medIns)/6) AS medInsBilled
		FROM members
		JOIN income USING (idNumber)
		WHERE inactive IS NULL
		AND salaryIncrease = 1 /* only pull members who are signed up for salary increases */
		AND insureLabor = 1 /* should only be those who have labor insurance */
		/* only select members who have changeDateSal this month */
		AND changeDateSal > CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01')
		AND insureDateLabor <= CONCAT(YEAR(CURDATE()),IF(MONTH(CURDATE()) < 7,'-01-01','-07-01'))
		/* pull only those with dues in the half year they will have income bumped for */
		AND duesYear = YEAR(DATE_ADD(CURDATE(),INTERVAL 1 MONTH))
		AND duesHalf = ROUND(QUARTER(DATE_ADD(CURDATE(),INTERVAL 1 MONTH))/2)	
		GROUP BY idNumber
		) billed
		USING (idNumber)
	JOIN (
		/* get rates the period the bump will start in */
		/* NOTE: if bump is > current highest rate, then won't match, but OK since they don't owe extra for this period */
		SELECT uRR.ID, uRR.salary, unionDues, laborIns, medIns
		FROM unionRates uRR
		INNER JOIN (
			SELECT MAX(ID) AS ID, salary
			FROM unionRates
			WHERE laborIns_dateEffective <= LAST_DAY(DATE_ADD(CURDATE(),INTERVAL 1 MONTH))
			AND medIns_dateEffective <= LAST_DAY(DATE_ADD(CURDATE(),INTERVAL 1 MONTH))
			GROUP BY salary
				) latest_uR
			ON uRR.ID = latest_uR.ID
		WHERE (salEndDate > LAST_DAY(DATE_ADD(CURDATE(),INTERVAL 1 MONTH)) OR ISNULL(salEndDate))
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
	) sourceQ
WHERE incomeChangeDate != CURDATE() /* try to prevent any accidental adding to a bill already there */
AND ((laborInsAdj - laborInsBilled) > 0 /* only want to pull members who actually owe something */
	OR (medInsAdj - medInsBilled) > 0)
	");

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));

  $insertGoTo = "billingUnpaidDiffDUES.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}


mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_duesHalfName = "SELECT * FROM duesHalfName";
$duesHalfName = mysqli_query($MySQL_Union, $query_duesHalfName) or die(mysqli_error($MySQL_Union));
$row_duesHalfName = mysqli_fetch_assoc($duesHalfName);
$totalRows_duesHalfName = mysqli_num_rows($duesHalfName);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 插入新建費率 - Insert New Member Rates</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<style type="text/css">
<!--
.w200px {
	width: 200px;
	vertical-align: top;
}
-->
</style>
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("duesYear"));

	return true;
}
</script>
<!-- InstanceEndEditable --><!-- InstanceParam name="NavBarLeft" type="boolean" value="true" -->
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
    <!-- InstanceBeginEditable name="Breadcrumbs" --><a href="memberSEARCH.php">首頁
    - 會員搜尋</a> / <a href="billingRatesDETAIL.php"> 繳費作業</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->插入新建費率<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="bi-annual">
      <table>
        <tr>
          <td class="w200px">若收費費率有更改,可由'<a href="billingRatesDETAIL.php">薪金及費率矩陣</a>'修正和刪除以及更新。且在以下動作指令之前,確定'薪金及費率矩陣'之金額無誤。</td>
          <td>&nbsp;</td>
          <td><table class="tableForm">
              <thead class="data">
                <tr class="data">
                  <td colspan="2" class="data">鍵入欲收費之年月份以得收費費率</td>
                </tr>
              </thead>
              <tr>
                <th class="tableForm">年:</th>
                <td class="tableForm"><input type="text" name="duesYear" id="duesYear" value="" size="4" /></td>
              </tr>
              <tr>
                <th class="tableForm">上下半年:</th>
                <td class="tableForm"><select name="duesHalf">
                    <?php 
do {  
?>
                    <option value="<?php echo $row_duesHalfName['duesHalf']?>" ><?php echo $row_duesHalfName['duesHalfName']?></option>
                    <?php
} while ($row_duesHalfName = mysqli_fetch_assoc($duesHalfName));
?>
                  </select>
                </td>
              </tr>
              <tr>
                <th class="tableForm">&nbsp;</th>
                <td class="tableForm"><input type="submit" value="插入新建費率" /></td>
              </tr>
            </table></td>
        </tr>
      </table>
      <input type="hidden" name="MM_insert" value="bi-annual" />
    </form>
    <p><a href="billingBillPeriodDELETE.php">刪除新建費率 - Delete New Rates </a><br />
      請注意這個動作會將所有新建收費費率刪除! </p>
    <br />
    <form action="<?php echo $editFormAction; ?>" method="post" id="duesDiff">
      <span class="strong"><strong>要新增提高薪資會員的勞,健保繳費單,請按此按鍵.</strong></span>
      <input name="duesDiffSubmit" type="submit" value="提高薪資繳費單" />
      <input type="hidden" name="MM_insert" value="duesDiff" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
  <div id="sectionLinks">
    <ul>
      <li><a href="billingRatesDETAIL.php">薪金和費率矩陣</a></li>
      <li><a href="billingUnpaidDUES.php">尚未繳費名單</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>創建收費帳單作業<br />
      (半年度)</h3>
    <ul>
      <li>第一: <a href="billingRatesDETAIL.php">薪金和費率矩陣</a></li>
      <li>第二: <a href="billingIncomeBUMP.php">提高投保薪資</a></li>
      <li>第三: <a href="billingBillPeriodINSERT.php">插入新建費率</a></li>
      <li>第四: <a href="billingUnpaidDUES.php">尚未繳費名單</a></li>
      <li><a href="billingUnpaidDiffDUES.php">提高薪資尚未繳費名單</a></li>
    </ul>
  </div>
  <!--  <div class="relatedLinks">
    <h3>Related Link Category</h3>
    <ul>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
    </ul>
  </div>
 -->
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($duesHalfName);
?>

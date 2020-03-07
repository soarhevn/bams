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

// start Database ops
require_once('Connections/MySQL_Union.php');

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

// get dependent idNumber
$colname_rsDependents = "1";
if (isset($_GET['recordID'])) {
  $colname_rsDependents = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}

// get info for member & dep
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsDependents = sprintf("
	SELECT d.ID, d.idNumber, d.name AS nameDep, 
	d.idParent, m.name AS nameParent, m.cardNum AS cardNumParent, m.insureHealth
	FROM dependents d, members m 
	WHERE d.ID = '%s' AND m.idNumber = d.idParent
	", $colname_rsDependents);
$rsDependents = mysqli_query($MySQL_Union, $query_rsDependents) or die(mysqli_error($MySQL_Union));
$row_rsDependents = mysqli_fetch_assoc($rsDependents);
$totalRows_rsDependents = mysqli_num_rows($rsDependents);

$idParent = $row_rsDependents['idParent'];

// start submit logic

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

// Now calculate the refund per the entered date in the form 
// Medical Ins is calculated monthly (and only med is needed here for dep checkout)
// + paid till checkout date w/ dep
// + recalc of checkout date till end of half
// - what was already paid
// = refund

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  
  // convert ROC to Western Date
  $beginDate = $_POST['endDate'];
  $beginDate = WesternizeDate($beginDate);
  
  // only calculate a refund if the member has health insurance
  if ($row_rsDependents['insureHealth'] == 1) {
  
  // calc the amount member should have paid up till the dep check out date 
  $query_rsPayTillCheckout = sprintf("
  	SELECT mem.idNumber, 
	# Figure med insurance prorated to the month 
	# (always paying the full month if ANY days are used in that month)
	IF(mem.insureHealth=0, 0, (FLOOR((((uR.medIns * 6) * 
	LEAST(IFNULL(d.numDeps,0)+1, 4)) - (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * 
	uR.medIns) * 6)) * dC.factorMonth))) AS medIns

	FROM (members mem, handicapAdjustment hD, (
		# get the rates effective for the date of the billing
		SELECT d1.ID, d1.salary, d1.unionDues, d1.laborIns, d1.medIns
		FROM (
			SELECT *
			FROM unionRates
			WHERE laborIns_dateEffective <= %2\$s 
			AND medIns_dateEffective <= %2\$s
			) d1
		LEFT OUTER JOIN (
			SELECT *
			FROM unionRates
			WHERE laborIns_dateEffective <= %2\$s 
			AND medIns_dateEffective <= %2\$s
			) d2
		ON (d1.salary = d2.salary 
		AND (d1.laborIns_dateEffective < d2.laborIns_dateEffective OR
		 d1.medIns_dateEffective < d2.medIns_dateEffective))
		WHERE d2.salary IS NULL
		) uR,
	 (SELECT 
	 (DATEDIFF(%2\$s, CONCAT(YEAR(%2\$s),'-', 
	 	MONTH(%2\$s), '-', '01')) + 1) / DAY(LAST_DAY(%2\$s)) AS factorDay,
	   IF(MONTH(%2\$s) < 7, 
		(MONTH(%2\$s))/6,
		(MONTH(%2\$s) - 6)/6) AS factorMonth,
	   IF(MONTH(%2\$s) < 7, 
		(MONTH(%2\$s)),
		(MONTH(%2\$s) - 6)) AS monthsLeft) dC
	)
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
	WHERE IFNULL(mem.handicap, 1) = hD.level
	AND uR.salary = mem.salary
	AND mem.idNumber = %1\$s
  	",
	   GetSQLValueString($MySQL_Union, $idParent, "text"),	// 1
	   GetSQLValueString($MySQL_Union, $beginDate, "date"));						// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $DetailPayTillCheckout = mysqli_query($MySQL_Union, $query_rsPayTillCheckout) 
  	or die(mysqli_error($MySQL_Union));
  $row_DetailPayTillCheckout = mysqli_fetch_assoc($DetailPayTillCheckout);
  
  // set dep as inactive by updating checkout date
  $insertCheckOutDateSQL = sprintf("
  	UPDATE dependents 
  	SET inactive = %2\$s
  	WHERE ID = %1\$s
  	",
	   GetSQLValueString($MySQL_Union, $colname_rsDependents, "int"),	// 1
	   GetSQLValueString($MySQL_Union, $beginDate, "date"));			// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $ResultInsertCheckoutDate = mysqli_query($MySQL_Union, $insertCheckOutDateSQL) 
  	or die(mysqli_error($MySQL_Union));

  // recalc from dep checkout date what member should have paid
  // note that med runs full months, so calc from month after checkout date
  $query_rsPayFromCheckout = sprintf("
	SELECT mem.idNumber, 
	IF(mem.insureHealth=0, 0, (FLOOR(((uR.medIns * LEAST(IFNULL(d.numDeps,0)+1, 4)) - 
	(FLOOR(hD.discount + IFNULL(d.depDis,0)/100 * uR.medIns)))))) * monthsLeft AS medIns
	FROM (members mem, handicapAdjustment hD, (
		SELECT d1.ID, d1.salary, d1.unionDues, d1.laborIns, d1.medIns
		FROM (
			SELECT *
			FROM unionRates
			WHERE laborIns_dateEffective <= %2\$s AND medIns_dateEffective <= %2\$s
			) d1
		LEFT OUTER JOIN (
			SELECT *
			FROM unionRates
			WHERE laborIns_dateEffective <= %2\$s AND medIns_dateEffective <= %2\$s
			) d2
		ON (d1.salary = d2.salary 
		AND (d1.laborIns_dateEffective < d2.laborIns_dateEffective 
		OR d1.medIns_dateEffective < d2.medIns_dateEffective))
		WHERE d2.salary IS NULL
		) uR,
	 (SELECT 
	   IF(MONTH(%2\$s) < 7, 
		(6 - MONTH(%2\$s)),
		(12 - MONTH(%2\$s))) AS monthsLeft) dC
	)
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
	WHERE IFNULL(mem.handicap,1) = hD.level
	AND uR.salary = mem.salary
	AND mem.idNumber = %1\$s
	",
	   GetSQLValueString($MySQL_Union, $idParent, "text"),			// 1
	   GetSQLValueString($MySQL_Union, $beginDate, "date"));			// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $PayFromCheckout = mysqli_query($MySQL_Union, $query_rsPayFromCheckout) 
  	or die(mysqli_error($MySQL_Union));
  $row_PayFromCheckout = mysqli_fetch_assoc($PayFromCheckout);
  
  // insert refund amount for overage (still need to insert the calculations)
  $insertIncomeSQL = sprintf("
	INSERT INTO income 
	(idNumber, monthNum, duesYear, duesHalf, medIns, billType, changeDate, paidDate, wire)

	SELECT %1\$s AS idNumber, 0 AS monthNum, 
	YEAR(%2\$s) AS duesYear, 
	IF(QUARTER(%2\$s) > 2, 2, 1) AS duesHalf,
	# paid till checkout + should pay from checkout - paid 
	%3\$s + %4\$s - paid.medIns AS medIns,	
	5 AS billType, NOW() AS changeDate, %2\$s AS paidDate, 0 AS wire
	FROM (
		SELECT SUM(medIns) AS medIns
		FROM income
		WHERE idNumber = %1\$s 
		AND duesYear = YEAR(%2\$s)
		AND duesHalf = IF(QUARTER(%2\$s) > 2, 2, 1)
		AND paidDate IS NOT NULL
		) paid
	",
	   GetSQLValueString($MySQL_Union, $idParent, "text"),								// 1
	   GetSQLValueString($MySQL_Union, $beginDate, "date"),								// 2
	   GetSQLValueString($MySQL_Union, $row_DetailPayTillCheckout['medIns'], "double"),	// 3
	   GetSQLValueString($MySQL_Union, $row_PayFromCheckout['medIns'], "double")			// 4
	   );

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result_insertIncomeSQL = mysqli_query($MySQL_Union, $insertIncomeSQL) 
  	or die(mysqli_error($MySQL_Union));
  
  // get last inserted ID, will need to add for insert to transMaster
  $incomeID = mysqli_insert_id($MySQL_Union);
    
  // insert a tranaction to transMaster for refund, return last insert ID
  $insertTransMasterSQL = sprintf("
  	INSERT INTO transactionsMaster (transDate, changeDate, memIdNum, notes, marker, code) 
		values  
		(%1\$s, NOW(), %2\$s, %3\$s, %4\$s, 'r')",
			GetSQLValueString($MySQL_Union, $beginDate, "date"),							// 1
			GetSQLValueString($MySQL_Union, $row_rsDependents['idParent'], "text"),		// 2
			GetSQLValueString($MySQL_Union, $row_rsDependents['cardNumParent'] . " / " . 
			   $row_rsDependents['nameParent'] . " / " . 
			   $idParent . " 眷屬" . 
			   $row_rsDependents['nameDep'] . "退費", "text"),				// 3
			GetSQLValueString($MySQL_Union, $incomeID, "int"));							// 4
		mysqli_select_db($MySQL_Union, $database_MySQL_Union);
		$Result_insertTransMasterSQL = mysqli_query($MySQL_Union, $insertTransMasterSQL) 
			or die(mysqli_error($MySQL_Union));

	// get last inserted ID, will need to add for refund trans inserts
	// In the case of a multiple-row INSERT statement, mysqli_insert_id($MySQL_Union) returns 
	// the first automatically generated AUTO_INCREMENT value
	$transMasterID = mysqli_insert_id($MySQL_Union);
  	
  // get the medIns values from the income table
	$selectIncomeSQL = sprintf("
		SELECT medIns
		FROM income
		WHERE ID = %s
		",
				GetSQLValueString($MySQL_Union, $incomeID, "int"));
  	$DetailRSincome = mysqli_query($MySQL_Union, $selectIncomeSQL) 
  		or die(mysqli_error($MySQL_Union));
	$row_DetailRSincome = mysqli_fetch_assoc($DetailRSincome);
  
  // insert tranactions for refund
  // accountID 47 med A/P or 114 A/R ; 2 med income
  $insertSQLtrans = sprintf("
  	INSERT INTO transactions 
		(idMaster, accountID, debit, credit) 
		VALUES 
		(%1\$s, 3, ABS(%2\$s), NULL),
		(%1\$s, IF(%2\$s < 0,47,114), NULL, ABS(%2\$s))
		",
				GetSQLValueString($MySQL_Union, $transMasterID, "int"), 						// 1
				GetSQLValueString($MySQL_Union, $row_DetailRSincome['medIns'], "double") 		// 2
				);
        mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$Result_insertSQLtrans = mysqli_query($MySQL_Union, $insertSQLtrans) 
  			or die(mysqli_error($MySQL_Union));
  
  // update the Income refund insertion with the transaction ID  
  $updateIncomeSQL = sprintf("
		UPDATE income 
		SET medInsID = %1\$s
		WHERE ID = %2\$s
		",
				GetSQLValueString($MySQL_Union, $transMasterID, "int"), 			// 1
				GetSQLValueString($MySQL_Union, $incomeID, "int") 				// 2
				);
        mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$Result_updateIncomeSQL = mysqli_query($MySQL_Union, $updateIncomeSQL) 
  			or die(mysqli_error($MySQL_Union));

 } // end health refund Logic
 else { // just update the checkout date for the dep if no health
 
   // set dep as inactive by updating checkout date
  $insertCheckOutDateSQL = sprintf("
  	UPDATE dependents 
  	SET inactive = %2\$s
  	WHERE ID = %1\$s
  	",
	   GetSQLValueString($MySQL_Union, $colname_rsDependents, "int"),	// 1
	   GetSQLValueString($MySQL_Union, $beginDate, "date"));			// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $ResultInsertCheckoutDate = mysqli_query($MySQL_Union, $insertCheckOutDateSQL) 
  	or die(mysqli_error($MySQL_Union));

 } // end if health insured logic 

  $insertGoTo = "memberDETAIL.php?idNumber=";
  $insertGoTo = $insertGoTo . $idParent;
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
<title>音樂工會: 會員退會與退費 - Insert Pro Rata Refund</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("endDate"));

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
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->會員退會與退費<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <p>
若是只有單是眷屬退會,因目前系統未設計出自動計算眷屬退出的部分,所以,請暫時先用過去方式手動計算退費.
</p><br />

    <form action="<?php echo $editFormAction; ?>" method="post" id="form1">
      <table class="tableForm">
        <tr>
          <th class="tableForm">退會日:</th>
          <td class="tableForm"><input type="text" id="endDate" name="endDate" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" />
            (請鍵入正確退會日期 年-月-日)</td>
        </tr>
        <tr>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm"><input name="buttonInsert" type="submit" id="buttonInsert" value="計算需退費金額" /></td>
        </tr>
        <tr>
          <td colspan="2" class="tableForm">(電腦會自動計算自退會日起需要退出的金額)</td>
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
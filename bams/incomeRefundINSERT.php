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

// start Database ops
require_once('Connections/MySQL_Union.php');

// get member idNumber
$colname_idNumber = "1";
if (isset($_GET['idNumber'])) {
  $colname_idNumber = (get_magic_quotes_gpc()) ? $_GET['idNumber'] : addslashes($_GET['idNumber']);
}

// get name and cardNum of the member
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsMember = sprintf("
SELECT name, cardNum, idNumber
FROM members 
WHERE idNumber = '%s'", 
	$colname_idNumber);
$rsMember = mysqli_query($MySQL_Union, $query_rsMember) or die(mysqli_error($MySQL_Union));
$row_rsMember = mysqli_fetch_assoc($rsMember);
$totalRows_rsMember = mysqli_num_rows($rsMember);

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
  
  // convert ROC to Western Date
  $beginDate = $_POST['endDate'];
  $beginDate = WesternizeDate($beginDate);

  /* refund SQL calculating what should have been paid up to the check out date - what was paid */
  $insertIncomeSQL = sprintf("
	# Calculate refund based on time left in the half year
	# Based on current rates and current salary level
	# Assume member is paid up for the half year
	SELECT mem.idNumber, 0 AS monthNum, 
	YEAR(%2\$s) AS duesYear, 
	IF(QUARTER(%2\$s) > 2, 2, 1) AS duesHalf, 
	# If need to prorate unionDues: ROUND(uR.unionDues * dC.factorMonth)
	# union dues are generally non-refundable: uR.unionDues - pD.unionDues AS unionDues, 
	# Figure labor insurance prorated to the day
	IF(mem.insureLabor=0, 0, -(CEILING(uR.laborIns * hD.percentage/100 * dC.factorDay
			+ uR.laborIns * hD.percentage/100 * (dC.monthsLeft)))) 
			AS laborIns,
	# Figure med insurance prorated to the month 
	# (always paying the full month if ANY days are used in that month)
	IF(mem.insureHealth=0, 0, -(FLOOR((((uR.medIns * 6) * 
	LEAST(IFNULL(d.numDeps,0)+1, 4)) - (FLOOR((hD.discount + IFNULL(d.depDis,0))/100 * 
	uR.medIns) * 6)) * dC.factorMonth))) AS medIns,
	5 AS billType, NOW() AS changeDate, %2\$s AS paidDate, 0 AS wire

	FROM (members mem, handicapAdjustment hD, (
		# get the rates effective for the date of the billing
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
		AND (d1.laborIns_dateEffective < d2.laborIns_dateEffective OR
		 d1.medIns_dateEffective < d2.medIns_dateEffective))
		WHERE d2.salary IS NULL
		) uR,
	# Calculate time left in the half year
	 (SELECT 
	 1 - (DATEDIFF(%2\$s, CONCAT(YEAR(%2\$s),'-', MONTH(%2\$s), '-', '01')) 
		+ 1) / DAY(LAST_DAY(%2\$s)) AS factorDay,
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
                       GetSQLValueString($MySQL_Union, $_GET['idNumber'], "text"),	// 1
                       GetSQLValueString($MySQL_Union, $beginDate, "date"));			// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertIncomeSQL) or die(mysqli_error($MySQL_Union));
  
  // get last inserted ID, will need to add for # 2 - 4 inserts
  $incomeID = mysqli_insert_id($MySQL_Union);
  
  // set member as inactive by updating checkout date
  $insertCheckOutDateSQL = sprintf("
  	UPDATE members 
  	SET inactive=%2\$s
  	WHERE idNumber=%1\$s
  	",
                       GetSQLValueString($MySQL_Union, $_GET['idNumber'], "text"),	// 1
                       GetSQLValueString($MySQL_Union, $beginDate, "date"));			// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result2 = mysqli_query($MySQL_Union, $insertCheckOutDateSQL) or die(mysqli_error($MySQL_Union));
  
  // set deps as inactive by updating checkout date
  $insertCheckOutDateDepSQL = sprintf("
  	UPDATE dependents 
  	SET inactive=%2\$s
  	WHERE idParent=%1\$s
  	AND inactive IS NULL
  	",
                       GetSQLValueString($MySQL_Union, $_GET['idNumber'], "text"),	// 1
                       GetSQLValueString($MySQL_Union, $beginDate, "date"));			// 2

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $ResultCheckoutDep = mysqli_query($MySQL_Union, $insertCheckOutDateDepSQL) 
  	or die(mysqli_error($MySQL_Union));
  
  // insert 2 tranactions to transMaster for refund, return last insert ID
  $insertTransMasterSQL = sprintf("
  	INSERT INTO transactionsMaster (transDate, changeDate, memIdNum, notes, marker, code) 
		values 
		(%1\$s, NOW(), %2\$s, %3\$s, %4\$s, 'r'), 
		(%1\$s, NOW(), %2\$s, %3\$s, %4\$s, 'r')",
				GetSQLValueString($MySQL_Union, $beginDate, "date"),					// 1
				GetSQLValueString($MySQL_Union, $_GET['idNumber'], "text"),			// 2
				GetSQLValueString($MySQL_Union, $row_rsMember['name'] . " / " . 
				   $row_rsMember['cardNum'] . " / " . 
				   $row_rsMember['idNumber'] . " 退費", "text"),			// 3
				GetSQLValueString($MySQL_Union, $incomeID, "int"));					// 4
		mysqli_select_db($MySQL_Union, $database_MySQL_Union);
		$Result3 = mysqli_query($MySQL_Union, $insertTransMasterSQL) or die(mysqli_error($MySQL_Union));

	// get last inserted ID, will need to add for refund trans inserts
	// In the case of a multiple-row INSERT statement, mysqli_insert_id($MySQL_Union) returns 
	// the first automatically generated AUTO_INCREMENT value
	$transMasterID = mysqli_insert_id($MySQL_Union);
  	
  // get the laborIns, medIns values from the income table
	$selectIncomeSQL = sprintf("
		SELECT laborIns, medIns
		FROM income
		WHERE ID = %s
		",
				GetSQLValueString($MySQL_Union, $incomeID, "int"));
  	$DetailRSincome = mysqli_query($MySQL_Union, $selectIncomeSQL) 
  		or die(mysqli_error($MySQL_Union));
	$row_DetailRSincome = mysqli_fetch_assoc($DetailRSincome);
  
  // insert tranactions for refund
  // accountID 46 labor A/P or 113 A/R ; 2 labor income
  // accountID 47 med A/P or 114 A/R ; 2 med income
  $insertSQLtrans = sprintf("
  	INSERT INTO transactions 
		(idMaster, accountID, debit, credit) 
		VALUES 
		(%1\$s, 2, ABS(%2\$s), NULL),	
		(%1\$s, IF(%2\$s < 0,46,113), NULL, ABS(%2\$s)),
		(%1\$s + 1, 3, ABS(%3\$s), NULL),
		(%1\$s + 1, IF(%3\$s < 0,47,114), NULL, ABS(%3\$s))
		",
				GetSQLValueString($MySQL_Union, $transMasterID, "int"), 						// 1
				GetSQLValueString($MySQL_Union, $row_DetailRSincome['laborIns'], "double"), 	// 2
            	GetSQLValueString($MySQL_Union, $row_DetailRSincome['medIns'], "double")   	// 3
				);
        mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$Result4 = mysqli_query($MySQL_Union, $insertSQLtrans) or die(mysqli_error($MySQL_Union));
  
  // update the Income refund insertion with the transaction ID  
  $updateIncomeSQL = sprintf("
		UPDATE income 
		SET laborInsID = %1\$s, medInsID = %1\$s + 1
		WHERE ID = %2\$s
		",
				GetSQLValueString($MySQL_Union, $transMasterID, "int"), 			// 1
				GetSQLValueString($MySQL_Union, $incomeID, "int") 				// 2
				);
        mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$Result5 = mysqli_query($MySQL_Union, $updateIncomeSQL) or die(mysqli_error($MySQL_Union));

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

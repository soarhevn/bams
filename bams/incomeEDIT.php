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
$colname_rsIncome = "1";
if (isset($_GET['incomeID'])) {
  $colname_rsIncome = (get_magic_quotes_gpc()) ? $_GET['incomeID'] : addslashes($_GET['incomeID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsIncome = sprintf("SELECT name, cardNum, income.ID, income.idNumber, duesYear - 1911 AS duesYear, monthNum, duesHalf, unionDues, laborIns, medIns, TRIM(LEADING '0' FROM (DATE_SUB(paidDate, INTERVAL 1911 YEAR))) AS paidDate, newMemDues, newMemDues2, wire, TRIM(LEADING '0' FROM (DATE_SUB(income.changeDate, INTERVAL 1911 YEAR))) AS changeDate, unionDuesID, laborInsID, medInsID, newMemDuesID  FROM income, members WHERE ID = %s AND members.idNumber = income.idNumber", $colname_rsIncome);
$rsIncome = mysqli_query($MySQL_Union, $query_rsIncome) or die(mysqli_error($MySQL_Union));
$row_rsIncome = mysqli_fetch_assoc($rsIncome);
$totalRows_rsIncome = mysqli_num_rows($rsIncome);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsDuesHalf = "SELECT * FROM duesHalfName";
$rsDuesHalf = mysqli_query($MySQL_Union, $query_rsDuesHalf) or die(mysqli_error($MySQL_Union));
$row_rsDuesHalf = mysqli_fetch_assoc($rsDuesHalf);
$totalRows_rsDuesHalf = mysqli_num_rows($rsDuesHalf);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAssetAccounts = "SELECT * FROM accountNames 
	WHERE inactive < 1 
	AND accountType = 2
	AND accountID IN (40, 42, 43, 44)
	ORDER BY accountName ASC";
$rsAssetAccounts = mysqli_query($MySQL_Union, $query_rsAssetAccounts) or die(mysqli_error($MySQL_Union));
$row_rsAssetAccounts = mysqli_fetch_assoc($rsAssetAccounts);
$totalRows_rsAssetAccounts = mysqli_num_rows($rsAssetAccounts);

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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $updateSQL = sprintf("UPDATE income SET duesYear=%1\$s + 1911, duesHalf=%2\$s, unionDues=%3\$s, laborIns=%4\$s, medIns=%5\$s, newMemDues=%6\$s, newMemDues2=%11\$s, paidDate=REPLACE(%7\$s, (SUBSTRING_INDEX(%7\$s, '-', 1)), ((SUBSTRING_INDEX(%7\$s, '-', 1)) + 1911)), monthNum=%8\$s, wire=%9\$s, changeDate=NOW() 
  WHERE ID=%10\$s",
                       GetSQLValueString($MySQL_Union, $_POST['duesYear'], "date"),		// 1
                       GetSQLValueString($MySQL_Union, $_POST['duesHalf'], "int"),		// 2
                       GetSQLValueString($MySQL_Union, $_POST['unionDues'], "int"),		// 3
                       GetSQLValueString($MySQL_Union, $_POST['laborIns'], "int"),		// 4
                       GetSQLValueString($MySQL_Union, $_POST['medIns'], "int"),			// 5
                       GetSQLValueString($MySQL_Union, $_POST['newMemDues'], "int"),		// 6
					   GetSQLValueString($MySQL_Union, $_POST['paidDate'], "date"),		// 7
					   GetSQLValueString($MySQL_Union, $_POST['monthNum'], "int"),		// 8
					   GetSQLValueString($MySQL_Union, $_POST['wire'], "int"),			// 9
                       GetSQLValueString($MySQL_Union, $_POST['ID'], "int"),				// 10
					   GetSQLValueString($MySQL_Union, $_POST['newMemDues2'], "int"));	// 11

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

 
// ** start post income update changes based on paidDate and wire **

// first delete transactions if set previously
if (($row_rsIncome['unionDuesID'] > 0) or ($row_rsIncome['laborInsID'] > 0) or ($row_rsIncome['medInsID'] > 0)) {
	DeleteTransactions();
}

// figure the year of the paid date entered, if it is old system, < 2007, then don't write to transmaster

  $paidDate = $_POST['paidDate'];
  $yearPos = strpos($paidDate,"-");
  $paidDateYear = substr($paidDate,0,$yearPos);
  $paidDateYear = (int)$paidDateYear;
  $paidDateYear += 1911;
  
//  echo $paidDateYear;

// paidDate filled in save transactions
if ($paidDateYear > 2006) {
	// check if wire to account is set
	if ($_POST['wire'] < 1) {
		// insert trans to transMaster for paid dues (no wire)
		$transMasterID = InsertValuesToTransactionMaster("");
	
		// insert tranactions for paid dues (no wire)
		InsertTranactionsPaidDues("40", "42", "43", "44", $transMasterID);
        
		} else {
		
		// insert trans to transMaster for paid dues (with wire)
		$transMasterID = InsertValuesToTransactionMaster(" / 匯款繳費");
	
		// insert tranactions for paid dues (with wire)
		$wire = $_POST['wire'];
		InsertTranactionsPaidDues($wire, $wire, $wire, $wire, $transMasterID);
		} 
		// end of insert transactions for paid dues

	// update income to have transMaster id's
	UpdateIncomeWithTransactionValues($transMasterID);
  }

  switch ($_GET['refer']) {
  case "transactionMASTER":
  	$updateGoTo = "transactionMASTER.php";
	break;
  case "assetMASTER":
  	$updateGoTo = "assetMASTER.php";
	break;
  default:
  	$updateGoTo = "memberDETAIL.php";
  }
/*  $updateGoTo = (isset($_GET['refer'])) ? "transactionMASTER.php" : "memberDETAIL.php"; */
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}

function UpdateIncomeWithTransactionValues($transMasterID) 
	// update income to have transMaster id's
{
	global $database_MySQL_Union, $MySQL_Union;
	$updateSQLcreate = sprintf("
	UPDATE income 
	SET 
	unionDuesID = IF(ISNULL(%3\$s) OR %3\$s = 0, NULL, %1\$s), 
	laborInsID = IF(ISNULL(%4\$s) OR %4\$s = 0, NULL, %1\$s + 1), 
	medInsID = IF(ISNULL(%5\$s) OR %5\$s = 0, NULL, %1\$s + 2), 
	newMemDuesID = IF(ISNULL(%6\$s) OR %6\$s = 0, NULL, %1\$s + 3), 
	newMemDues2ID = IF(ISNULL(%7\$s) OR %7\$s = 0, NULL, %1\$s + 4)
	WHERE ID = %2\$s",
			GetSQLValueString($MySQL_Union, $transMasterID, "int"),			// 1
			GetSQLValueString($MySQL_Union, $_POST['ID'], "int"),				// 2
			GetSQLValueString($MySQL_Union, $_POST['unionDues'], "double"), 	// 3
			GetSQLValueString($MySQL_Union, $_POST['laborIns'], "double"),  	// 4
			GetSQLValueString($MySQL_Union, $_POST['medIns'], "double"),		// 5
			GetSQLValueString($MySQL_Union, $_POST['newMemDues'], "double"),	// 6
			GetSQLValueString($MySQL_Union, $_POST['newMemDues2'], "double")	// 7
			);
	mysqli_select_db($MySQL_Union, $database_MySQL_Union);
	$Result2 = mysqli_query($MySQL_Union, $updateSQLcreate) or die(mysqli_error($MySQL_Union));
	
	// clean up tranactions with zero values
	$SQLcleanUpZeroValueTranactions = sprintf("DELETE t, tM 
	FROM transactions t, transactionsMaster tM
	WHERE (t.debit = 0 OR t.debit IS NULL) 
	AND (t.credit = 0 OR t.credit IS NULL)
	AND tM.idMaster = t.idMaster");
	mysqli_select_db($MySQL_Union, $database_MySQL_Union);
	$result2a = mysqli_query($MySQL_Union, $SQLcleanUpZeroValueTranactions) or die(mysqli_error($MySQL_Union));	
}

function InsertValuesToTransactionMaster($wireText)
	// insert 4 tranactions to transMaster for paid dues, return last insert ID
{
	global $database_MySQL_Union, $MySQL_Union, $row_rsIncome;
	$insertSQL2 = sprintf("INSERT INTO transactionsMaster (transDate, changeDate, memIdNum, notes, marker) 
		values (REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), 
		((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), %2\$s, %3\$s, %4\$s),
		(REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), 
		((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), %2\$s, %3\$s, %4\$s),
		(REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), 
		((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), %2\$s, %3\$s, %4\$s),
		(REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), 
		((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), %2\$s, %3\$s, %4\$s),
		(REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), 
		((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), %2\$s, %3\$s, %4\$s)",
				GetSQLValueString($MySQL_Union, $_POST['paidDate'], "date"), 			// 1
				GetSQLValueString($MySQL_Union, $row_rsIncome['idNumber'], "text"),	// 2
				GetSQLValueString($MySQL_Union, $row_rsIncome['name'] . " / " . 
				   $row_rsIncome['cardNum'] . " / " . 
				   $row_rsIncome['idNumber'] . $wireText, "text"),		// 3
				GetSQLValueString($MySQL_Union, $_POST['ID'], "int"));				// 4
		mysqli_select_db($MySQL_Union, $database_MySQL_Union);
		$Result3 = mysqli_query($MySQL_Union, $insertSQL2) or die(mysqli_error($MySQL_Union));

	// get last inserted ID, will need to add for # 2 - 4 inserts
	$transMasterID = mysqli_insert_id($MySQL_Union);
	return $transMasterID;
}

function InsertTranactionsPaidDues($unionAccID, $laborAccID, $medAccID, $newMemAccID, $transMasterID)
	// insert tranactions for paid dues
{
	global $database_MySQL_Union, $MySQL_Union;
	$insertSQLtrans = sprintf("INSERT INTO transactions 
		(idMaster, accountID, credit, debit) 
		VALUES (%1\$s, 1, %2\$s, NULL),	
		(%1\$s, %6\$s, NULL, %2\$s),
		(%1\$s + 1, 2, %3\$s, NULL),
		(%1\$s + 1, %7\$s, NULL, %3\$s),
		(%1\$s + 2, 3, %4\$s, NULL),		
		(%1\$s + 2, %8\$s, NULL, %4\$s),
		(%1\$s + 3, 4, %5\$s, NULL),		
		(%1\$s + 3, %9\$s, NULL, %5\$s),
		(%1\$s + 4, 6, %10\$s, NULL),		
		(%1\$s + 4, %6\$s, NULL, %10\$s)",
				GetSQLValueString($MySQL_Union, $transMasterID, "int"), 			// 1
				GetSQLValueString($MySQL_Union, $_POST['unionDues'], "double"), 	// 2
            	GetSQLValueString($MySQL_Union, $_POST['laborIns'], "double"),  	// 3
				GetSQLValueString($MySQL_Union, $_POST['medIns'], "double"),		// 4
				GetSQLValueString($MySQL_Union, $_POST['newMemDues'], "double"),	// 5
				GetSQLValueString($MySQL_Union, $unionAccID, "int"),				// 6
				GetSQLValueString($MySQL_Union, $laborAccID, "int"),				// 7
				GetSQLValueString($MySQL_Union, $medAccID, "int"),				// 8
				GetSQLValueString($MySQL_Union, $newMemAccID, "int"),				// 9
				GetSQLValueString($MySQL_Union, $_POST['newMemDues2'], "double")	// 10
				);
        mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$Result4 = mysqli_query($MySQL_Union, $insertSQLtrans) or die(mysqli_error($MySQL_Union));
}

function DeleteTransactions()
	// delete transactions & update income erase transaction links
{
	global $database_MySQL_Union, $MySQL_Union;
	$SQLdeleteTrans = sprintf("DELETE t, tM
		FROM transactions t, transactionsMaster tM,
		(
			SELECT unionDuesID, laborInsID, medInsID, newMemDuesID, newMemDues2ID
			FROM income
			WHERE id = %s
		) incIDs
		WHERE t.idMaster IN (incIDs.unionDuesID, incIDs.laborInsID, incIDs.medInsID, 
			incIDs.newMemDuesID, incIDs.newMemDues2ID)
		AND tM.idMaster IN (incIDs.unionDuesID, incIDs.laborInsID, incIDs.medInsID, 
			incIDs.newMemDuesID, incIDs.newMemDues2ID)",
				GetSQLValueString($MySQL_Union, $_POST['ID'], "int"));
		mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$ResultDelete = mysqli_query($MySQL_Union, $SQLdeleteTrans) or die(mysqli_error($MySQL_Union));
  		
  	$SQLupdateIncome = sprintf("UPDATE income SET 
  		unionDuesID = NULL, laborInsID = NULL, medInsID = NULL, 
  		newMemDuesID = NULL, newMemDues2ID = NULL
  		WHERE ID = %s",
  				GetSQLValueString($MySQL_Union, $_POST['ID'], "int"));
		mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$ResultUpdate = mysqli_query($MySQL_Union, $SQLupdateIncome) or die(mysqli_error($MySQL_Union));
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 編輯會員繳款記錄 - Edit Member Income Record</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript">
<!--
function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_validateForm() { //v4.0
  var i,p,q,nm,test,num,min,max,errors='',args=MM_validateForm.arguments;
  for (i=0; i<(args.length-2); i+=3) { test=args[i+2]; val=MM_findObj(args[i]);
    if (val) { nm=args[i+1]; if ((val=val.value)!="") {
      if (test.indexOf('isEmail')!=-1) { p=val.indexOf('@');
        if (p<1 || p==(val.length-1)) errors+='- '+nm+' must contain an e-mail address.\n';
      } else if (test!='R') { num = parseFloat(val);
        if (isNaN(val)) errors+='- '+nm+' 必須包含編號.\n';
        if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
          min=test.substring(8,p); max=test.substring(p+1);
          if (num<min || max<num) errors+='- '+nm+' 必須包含一個編號在 '+min+' 和 '+max+' 之間.\n';
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' 被要求.\n'; }
  } if (errors) alert('以下錯誤發生了:\n'+errors);
  document.MM_returnValue = (errors == '');
}
//-->
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
    - 會員搜尋</a> / 成員搜索結果 / <a href="memberDETAIL.php?idNumber=<?php echo $row_rsIncome['idNumber']; ?>">會員資料總表</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->編輯會員繳款記錄<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" onsubmit="MM_validateForm('duesYear','年','RinRange95:200');return document.MM_returnValue">
      <table class="tableForm">
        <thead class="data">
          <tr class="data">
            <th colspan="4" class="data"><?php echo $row_rsIncome['name']; ?> / <?php echo $row_rsIncome['cardNum']; ?> / <a href="memberDETAIL.php?idNumber=<?php echo $row_rsIncome['idNumber']; ?>"> <?php echo $row_rsIncome['idNumber']; ?></a></th>
          </tr>
        </thead>
        <tr>
          <th class="tableForm">年:</th>
          <td class="tableForm"><input name="duesYear" type="text" value="<?php echo $row_rsIncome['duesYear']; ?>" size="4" maxlength="4" /></td>
          <th class="tableForm">月:</th>
          <td class="tableForm"><input name="monthNum" type="text" value="<?php echo $row_rsIncome['monthNum']; ?>" size="2" maxlength="2" /></td>
        </tr>
        <tr>
          <th class="tableForm">上下半年:</th>
          <td colspan="3" class="tableForm"><select name="duesHalf">
              <?php 
do {  
?>
              <option value="<?php echo $row_rsDuesHalf['duesHalf']?>" <?php if (!(strcmp($row_rsDuesHalf['duesHalf'], $row_rsIncome['duesHalf']))) {echo "SELECTED";} ?>><?php echo $row_rsDuesHalf['duesHalfName']?></option>
              <?php
} while ($row_rsDuesHalf = mysqli_fetch_assoc($rsDuesHalf));
?>
            </select>
          </td>
        </tr>
        <tr>
          <th class="tableForm">經常會費:</th>
          <td class="tableForm"><input type="text" name="unionDues" value="<?php echo $row_rsIncome['unionDues']; ?>" size="10" /></td>
          <th class="tableForm">互助金:</th>
          <td class="tableForm"><input type="text" name="newMemDues" value="<?php echo $row_rsIncome['newMemDues']; ?>" size="10" /></td>
        </tr>
        <tr>
          <th class="tableForm">勞保費:</th>
          <td class="tableForm"><input type="text" name="laborIns" value="<?php echo $row_rsIncome['laborIns']; ?>" size="10" /></td>
          <th class="tableForm">入會費:</th>
          <td class="tableForm"><input type="text" name="newMemDues2" value="<?php echo $row_rsIncome['newMemDues2']; ?>" size="10" /></td>
        </tr>
        <tr>
          <th class="tableForm">健保費:</th>
          <td class="tableForm"><input type="text" name="medIns" value="<?php echo $row_rsIncome['medIns']; ?>" size="10" /></td>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="3" class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">繳款日期:</th>
          <td class="tableForm"><input name="paidDate" type="text" value="<?php echo $row_rsIncome['paidDate']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日&nbsp;&nbsp;</td>
          <th class="tableForm">匯款繳費:</th>
          <td class="tableForm"><select name="wire" id="wire">
              <option value="0" <?php if (!(strcmp(0, $row_rsIncome['wire']))) {echo "SELECTED";} ?>>-</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsAssetAccounts['accountID']?>"<?php if (!(strcmp($row_rsAssetAccounts['accountID'], $row_rsIncome['wire']))) {echo "SELECTED";} ?>><?php echo $row_rsAssetAccounts['accountName']?></option>
              <?php
} while ($row_rsAssetAccounts = mysqli_fetch_assoc($rsAssetAccounts));
  $rows = mysqli_num_rows($rsAssetAccounts);
  if($rows > 0) {
      mysqli_data_seek($rsAssetAccounts, 0);
	  $row_rsAssetAccounts = mysqli_fetch_assoc($rsAssetAccounts);
  }
?>
            </select></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input type="submit" value="儲存更新" /></td>
          <td colspan="2" class="tableForm">記錄更改日期: <?php echo $row_rsIncome['changeDate']; ?></td>
        </tr>
      </table>
      <input type="hidden" name="MM_update" value="form1" />
      <input type="hidden" name="ID" value="<?php echo $row_rsIncome['ID']; ?>" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsIncome);

mysqli_free_result($rsDuesHalf);

mysqli_free_result($rsAssetAccounts);
?>

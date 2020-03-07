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
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountTypes = "SELECT acctTypeID, atName, atNameEn, IF(debit, '增量 (+)', '減少 (-)') AS debit, IF(debit, '減少 (-)', '增量 (+)') AS credit FROM accountType ORDER BY acctTypeID ASC";
$rsAccountTypes = mysqli_query($MySQL_Union, $query_rsAccountTypes) or die(mysqli_error($MySQL_Union));
$row_rsAccountTypes = mysqli_fetch_assoc($rsAccountTypes);
$totalRows_rsAccountTypes = mysqli_num_rows($rsAccountTypes);
?>
<?php require_once('Connections/MySQL_Union.php'); 
	/* 
    ** get chainedSelectors class 
    */ 
    require("assets/javascript/chainedSelectors.php"); 
?>
<?php
//prepare names for chainedSelectors
$selectorNames = array( 
    CS_FORM=>"transaction",  
    CS_FIRST_SELECTOR=>"select_accountID",  
    CS_SECOND_SELECTOR=>"select_xAccountID");

$memIdNum = NULL;
if (isset($_GET['idNumber'])) {
  $memIdNum = (get_magic_quotes_gpc()) ? $_GET['idNumber'] : addslashes($_GET['idNumber']);
}

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

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  $insertSQL = sprintf("INSERT INTO transactionsMaster (transDate, changeDate, memIdNum, notes) 
VALUES 
(REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), ((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), %2\$s, %3\$s)",
                       GetSQLValueString($MySQL_Union, $_POST['transDate'], "date"),	// 1
					   GetSQLValueString($MySQL_Union, $_POST['memIdNum'], "text"),	// 2
                       GetSQLValueString($MySQL_Union, $_POST['notes'], "text"));		// 3

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));
  
  $master_ID = mysqli_insert_id($MySQL_Union);
  
  $insertSQL2 = sprintf("INSERT INTO transactions (idMaster, accountID, debit, credit) 
values (%5\$s, %1\$s, %3\$s, %4\$s), (%5\$s, %2\$s, %4\$s, %3\$s)",
                       GetSQLValueString($MySQL_Union, $_POST['select_accountID'], "int"),	// 1
					   GetSQLValueString($MySQL_Union, $_POST['select_xAccountID'], "int"),	// 2
					   GetSQLValueString($MySQL_Union, $_POST['debit'], "double"),			// 3
                       GetSQLValueString($MySQL_Union, $_POST['credit'], "double"),			// 4
					   GetSQLValueString($MySQL_Union, $master_ID, "int"));					// 5
  
  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result2 = mysqli_query($MySQL_Union, $insertSQL2) or die(mysqli_error($MySQL_Union));

  	switch ($_GET['refer']) {
 	case "memberDETAIL":
  		$insertGoTo = "memberDETAIL.php";
		break;
	case "transactionMASTER":
		$insertGoTo = "transactionMASTER.php";
		break;
	default:
  		$insertGoTo = "transactionSEARCH.php";
  	}
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_AccountNamesDropDowns = "(
SELECT aN.accountID, CONCAT(aN.acctCODE, ' ', aN.accountName, ' - ', aT.atName) AS accountName, 
aT.debit, xaN.accountID AS xAccountID, CONCAT(xaN.acctCODE, ' ', xaN.accountName, ' - ', 
xaT.atName, IF(aN.xAccountID = xaN.accountID, ' (預設選擇)', '')) AS xAccountName, 
aN.accountType, IF(aN.xAccountID = xaN.accountID, 0, 1)  AS defaultX
FROM accountNames aN 
INNER JOIN accountType aT ON aN.accountType = aT.acctTypeID 
INNER JOIN accountNames xaN ON aN.accountType != xaN.accountType
INNER JOIN accountType xaT ON xaN.accountType = xaT.acctTypeID
WHERE aN.inactive != 1
AND aN.accountType >= 0
AND xaN.accountType >= 0
) UNION (
SELECT 0, '-', '', 0, '-', '', '')
ORDER BY accountName, accountID, defaultX, xAccountName";
$rs_AccountNamesDropDowns = mysqli_query($MySQL_Union, $query_rs_AccountNamesDropDowns) or die(mysqli_error($MySQL_Union));
$row_rs_AccountNamesDropDowns = mysqli_fetch_assoc($rs_AccountNamesDropDowns);
$totalRows_rs_AccountNamesDropDowns = mysqli_num_rows($rs_AccountNamesDropDowns);

// assemble data for selectors
while($row = mysqli_fetch_object($rs_AccountNamesDropDowns)) 
    { 
        $selectorData[] = array( 
            CS_SOURCE_ID=>$row->accountID,  
            CS_SOURCE_LABEL=>$row->accountName,
            CS_TARGET_ID=>$row->xAccountID,  
            CS_TARGET_LABEL=>$row->xAccountName); 
    }             

//instantiate class 
$xAccountDropDown = new chainedSelectors( 
    $selectorNames,  
    $selectorData);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 增加筆數 - Insert Transaction</title>
<script type="text/javascript">
<!--
<?php 
    $xAccountDropDown->printUpdateFunction(); 
?>

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
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' is required.\n'; }
  } if (errors) alert('以下錯誤發生了:\n'+errors);
  document.MM_returnValue = (errors == '');
}
//-->
</script>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("transDate"));

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
    <!-- InstanceBeginEditable name="Breadcrumbs" --><a href="memberSEARCH.php">首頁
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> /<!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->增加筆數<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="transaction" name="transaction" onsubmit="MM_validateForm('debit','借','NisNum','credit','貸','NisNum','notes','摘要','R');return document.MM_returnValue">
      <input name="memIdNum" type="hidden" value="<?php echo $memIdNum; ?>" />
      <table class="tableForm">
        <tr>
          <th class="tableForm">日期:</th>
          <td colspan="3" class="tableForm"><input name="transDate" id="transDate" type="text" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">科目 / 抵消科目:</th>
          <td colspan="2" class="tableForm"><?php $xAccountDropDown->printSelectors(); ?></td>
        </tr>
        <tr>
          <td class="tableForm"></td>
          <th class="tableForm"><div align="left">借 - Debit</div></th>
          <th class="tableForm"><div align="left">貸 - Credit</div></th>
          <th class="tableForm">&nbsp;</th>
        </tr>
        <tr>
          <td class="tableForm"></td>
          <td class="tableForm"><input name="debit" type="text" size="10" maxlength="19" /></td>
          <td class="tableForm"><input name="credit" type="text" size="10" maxlength="19" /></td>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">摘要:</th>
          <td colspan="3" class="tableForm"><input name="notes" type="text" value="" size="45" maxlength="100" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="3" class="tableForm"><input type="submit" value="儲存" /></td>
        </tr>
      </table>
      <input type="hidden" name="MM_insert" value="form1" />
    </form>
    <h2>帳戶類型指南 </h2>
    <table class="data">
      <thead class="data">
        <tr class="data">
          <th class="data">中文</th>
          <th class="data">English</th>
          <th class="data">借 - Debit</th>
          <th class="data">貸 - Credit</th>
        </tr>
      </thead>
      <?php do { 
	  		$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
        <tr class="<?php echo $class; ?>">
          <td class="data center"><?php echo $row_rsAccountTypes['atName']; ?></td>
          <td class="data"><?php echo $row_rsAccountTypes['atNameEn']; ?></td>
          <td class="data center"><?php echo $row_rsAccountTypes['debit']; ?></td>
          <td class="data center"><?php echo $row_rsAccountTypes['credit']; ?></td>
        </tr>
        <?php } while ($row_rsAccountTypes = mysqli_fetch_assoc($rsAccountTypes)); ?>
    </table>
    <script type="text/javascript"> 
		<?php $xAccountDropDown->initialize(); ?> 
	</script>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsAccountTypes);

mysqli_free_result($rs_AccountNamesDropDowns);
?>

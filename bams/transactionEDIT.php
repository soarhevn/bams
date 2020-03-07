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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $updateSQL = sprintf("UPDATE transactions SET accountID=%1\$s, debit=%2\$s, credit=%3\$s  WHERE id=%4\$s",
                       GetSQLValueString($MySQL_Union, $_POST['accountID1'], "int"),	// 1
                       GetSQLValueString($MySQL_Union, $_POST['debit'], "double"),	// 2
                       GetSQLValueString($MySQL_Union, $_POST['credit'], "double"),	// 3
					   GetSQLValueString($MySQL_Union, $_POST['idTrans1'], "int"));	// 4

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

  $updateSQL2 = sprintf("UPDATE transactions SET accountID=%1\$s, debit=%3\$s, credit=%2\$s  WHERE id=%4\$s",
                       GetSQLValueString($MySQL_Union, $_POST['accountID2'], "int"),
					   GetSQLValueString($MySQL_Union, $_POST['debit'], "double"),
                       GetSQLValueString($MySQL_Union, $_POST['credit'], "double"),
                       GetSQLValueString($MySQL_Union, $_POST['idTrans2'], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result2 = mysqli_query($MySQL_Union, $updateSQL2) or die(mysqli_error($MySQL_Union));
  
  $updateSQLmaster = sprintf("UPDATE transactionsMaster SET transDate=REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), ((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), notes=%2\$s, changeDate=NOW(), memIdNum=%4\$s WHERE idMaster=%3\$s",
                       GetSQLValueString($MySQL_Union, $_POST['transDate'], "date"),	// 1
                       GetSQLValueString($MySQL_Union, $_POST['notes'], "text"),		// 2
					   GetSQLValueString($MySQL_Union, $_POST['idMaster'], "int"),	// 3
					   GetSQLValueString($MySQL_Union, $_POST['memIdNum'], "text"));	// 4

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result3 = mysqli_query($MySQL_Union, $updateSQLmaster) or die(mysqli_error($MySQL_Union));

  switch ($_GET['refer']) {
  case "memberDETAIL":
  	$updateGoTo = "memberDETAIL.php";
	break;
  case "assetMASTER":
  	$updateGoTo = "assetMASTER.php";
	break;
  default:
  	$updateGoTo = "transactionMASTER.php";
  }
 /*  $updateGoTo = (isset($_GET['refer'])) ? "assetMASTER.php": "transactionMASTER.php"; */
if (isset($_SERVER['QUERY_STRING'])) {
    $currURL = $_SERVER['QUERY_STRING'];
	$fieldName = "recordID";
	parse_str($currURL,$params);
	unset($params[$fieldName]);  //Removes the parameter
	$newURL = http_build_query($params);
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $newURL;
  }

  header(sprintf("Location: %s", $updateGoTo));
}

$colname_rsTransactionGet = "1";
if (isset($_GET['recordID'])) {
  $colname_rsTransactionGet = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsTransactionGet = sprintf("
SELECT idMaster, 
TRIM(LEADING '0' FROM (DATE_SUB(transDate, INTERVAL 1911 YEAR))) AS transDate, 
TRIM(LEADING '0' FROM (DATE_SUB(changeDate, INTERVAL 1911 YEAR))) AS  changeDate, 
memIdNum, notes, t1.id AS id1, t1.accountID AS accountID1, 
IF(t1.debit > 0, TRUNCATE(t1.debit, 0), NULL) AS debit, 
IF(t1.credit > 0, TRUNCATE(t1.credit, 0), NULL) AS credit, 
t2.id AS id2, t2.accountID AS accountID2 
FROM transactionsMaster 
LEFT JOIN transactions t1 USING (idMaster) 
LEFT JOIN transactions t2 USING (idMaster) 
WHERE t1.accountID != t2.accountID 
AND idMaster = %s
ORDER BY id1", 
		$colname_rsTransactionGet);
$rsTransactionGet = mysqli_query($MySQL_Union, $query_rsTransactionGet) or die(mysqli_error($MySQL_Union));
$row_rsTransactionGet = mysqli_fetch_assoc($rsTransactionGet);
$totalRows_rsTransactionGet = mysqli_num_rows($rsTransactionGet);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountNames = "SELECT accountID, CONCAT(acctCODE, ' ', accountName, '- ', atName) AS accountName, xAccountID 
FROM accountNames 
LEFT JOIN accountType ON accountType = acctTypeID 
WHERE inactive < 1 
AND accountType >= 0
ORDER BY accountName ASC";
$rsAccountNames = mysqli_query($MySQL_Union, $query_rsAccountNames) or die(mysqli_error($MySQL_Union));
$row_rsAccountNames = mysqli_fetch_assoc($rsAccountNames);
$totalRows_rsAccountNames = mysqli_num_rows($rsAccountNames);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountTypes = "SELECT acctTypeID, atName, atNameEn, IF(debit, '增量 (+)', '減少 (-)') AS debit, IF(debit, '減少 (-)', '增量 (+)') AS credit FROM accountType ORDER BY acctTypeID ASC";
$rsAccountTypes = mysqli_query($MySQL_Union, $query_rsAccountTypes) or die(mysqli_error($MySQL_Union));
$row_rsAccountTypes = mysqli_fetch_assoc($rsAccountTypes);
$totalRows_rsAccountTypes = mysqli_num_rows($rsAccountTypes);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 編輯帳目 - Edit Transaction</title>
<script type="text/javascript">
<!--
<!-- Begin
function cancelGoToURL() { window.location = "<?php 
	switch ($_GET['refer']) {
    case "memberDETAIL":
  		$cancelGoTo = "memberDETAIL.php?";
		break;
    case "assetMASTER":
  	 	$cancelGoTo = "assetMASTER.php?";
	 	break;
    default:
  		$cancelGoTo = "transactionMASTER.php?";
  }
	$currURL = $_SERVER['QUERY_STRING'];
	$fieldName = "recordID";
	parse_str($currURL,$params);
	unset($params[$fieldName]);  //Removes the parameter
	$newURL = http_build_query($params);
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $newURL;
	echo $cancelGoTo . $newURL; ?>"; }
//  End -->

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
<!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable --><!-- InstanceParam name="NavBarLeft" type="boolean" value="false" -->
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->編輯帳目<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" onsubmit="MM_validateForm('debit','借','NisNum','credit','貸','NisNum','notes','摘要','R');return document.MM_returnValue">
      <table class="tableForm">
        <tr>
          <th class="tableForm">日期:</th>
          <td colspan="3" class="tableForm"><input name="transDate" type="text" value="<?php echo $row_rsTransactionGet['transDate']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">科目 / 抵消科目:</th>
          <td colspan="2" class="tableForm"><select name="accountID1" id="accountID1">
              <?php 
do {  
?>
              <option value="<?php echo $row_rsAccountNames['accountID']?>" <?php if (!(strcmp($row_rsAccountNames['accountID'], $row_rsTransactionGet['accountID1']))) {echo "SELECTED";} ?>><?php echo $row_rsAccountNames['accountName']?></option>
              <?php
} while ($row_rsAccountNames = mysqli_fetch_assoc($rsAccountNames));
?>
            </select>
          </td>
          <td class="tableForm"><select name="accountID2" id="accountID2">
              <?php mysqli_data_seek ($rsAccountNames, 0);
do {  
?>
              <option value="<?php echo $row_rsAccountNames['accountID']?>"<?php if (!(strcmp($row_rsAccountNames['accountID'], $row_rsTransactionGet['accountID2']))) {echo "SELECTED";} ?>><?php echo $row_rsAccountNames['accountName']?></option>
              <?php
} while ($row_rsAccountNames = mysqli_fetch_assoc($rsAccountNames));
?>
            </select></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <th class="tableForm"><div align="left">借 - Debit</div></th>
          <th class="tableForm"><div align="left">貸 - Credit</div></th>
          <th class="tableForm">&nbsp;</th>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input name="debit" type="text" value="<?php echo $row_rsTransactionGet['debit']; ?>" size="19" maxlength="19" /></td>
          <td class="tableForm"><input name="credit" type="text" id="credit" value="<?php echo $row_rsTransactionGet['credit']; ?>" size="19" maxlength="19" /></td>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">摘要:</th>
          <td colspan="3" class="tableForm"><input name="notes" type="text" value="<?php echo $row_rsTransactionGet['notes']; ?>" size="45" maxlength="100" /></td>
        </tr>
        <tr>
          <th class="tableForm">身分證字號:</th>
          <td colspan="3" class="tableForm"><?php echo $row_rsTransactionGet['idNumber']; ?></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="2" nowrap="nowrap" class="tableForm"><input type="submit" value="儲存更新" />
            <input name="cancel" type="button" id="cancel" onclick="cancelGoToURL()" value="取消更改" />
          </td>
          <td nowrap="nowrap" class="tableForm right">記錄更改日期: <?php echo $row_rsTransactionGet['changeDate']; ?></td>
        </tr>
      </table>
      <input type="hidden" name="idTrans1" value="<?php echo $row_rsTransactionGet['id1']; ?>" />
      <input type="hidden" name="MM_update" value="form1" />
      <input type="hidden" name="idMaster" value="<?php echo $row_rsTransactionGet['idMaster']; ?>" />
      <input type="hidden" name="idTrans2" value="<?php echo $row_rsTransactionGet['id2']; ?>" />
      <input type="hidden" name="memIdNum" value="<?php echo $row_rsTransactionGet['memIdNum']; ?>" />
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
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsTransactionGet);

mysqli_free_result($rsAccountNames);

mysqli_free_result($rsAccountTypes);
?>

<?php require_once('Connections/MySQL_Union.php'); ?>
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
$MM_authorizedUsers = "";
$MM_donotCheckaccess = "true";

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
    if (($strUsers == "") && true) { 
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
<?php
if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($MySQL_Union, $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;

  $theValue = mysqli_real_escape_string($MySQL_Union, $theValue);

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
}

$colname_rsAccounts = "-1";
if (isset($_GET['recordID'])) {
  $colname_rsAccounts = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccounts = sprintf("SELECT accountNames.accountName, accountNames.description, accountType.atName FROM accountNames LEFT JOIN accountGroupXref ON accountGroupXref.accName_id = accountNames.accountID LEFT JOIN accountType ON accountType.acctTypeID = accountNames.accountType WHERE accountGroupXref.accGrp_id = %s ORDER BY accountNames.accountName ASC", GetSQLValueString($MySQL_Union, $colname_rsAccounts, "int"));
$rsAccounts = mysqli_query($MySQL_Union, $query_rsAccounts) or die(mysqli_error($MySQL_Union));
$row_rsAccounts = mysqli_fetch_assoc($rsAccounts);
$totalRows_rsAccounts = mysqli_num_rows($rsAccounts);

$colname_rsGroupName = "-1";
if (isset($_GET['recordID'])) {
  $colname_rsGroupName = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsGroupName = sprintf("SELECT accGrpName FROM accountGroups WHERE accGrpID = %s", GetSQLValueString($MySQL_Union, $colname_rsGroupName, "int"));
$rsGroupName = mysqli_query($MySQL_Union, $query_rsGroupName) or die(mysqli_error($MySQL_Union));
$row_rsGroupName = mysqli_fetch_assoc($rsGroupName);
$totalRows_rsGroupName = mysqli_num_rows($rsGroupName);
 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 科目群組 - Accounts in Group</title>
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
    <!-- InstanceBeginEditable name="Breadcrumbs" --><a href="memberSEARCH.php">首頁
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> / <a href="accountNamesDETAIL.php">帳目名稱</a> / <a href="accountGroupsDETAIL.php">帳目組</a> /<!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->科目群組: <?php echo $row_rsGroupName['accGrpName']; ?><!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <table class="data">
      <thead class="data">
        <tr class="data">
          <td class="data">科目名稱</td>
          <td class="data">備註</td>
          <td class="data">科目類別</td>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
		$class = ($class == 'dataOdd') ? 'data' : 'dataOdd';?>
          <tr  class="<?php echo $class; ?>">
            <td class="data"><?php echo $row_rsAccounts['accountName']; ?></td>
            <td class="data"><?php echo $row_rsAccounts['description']; ?></td>
            <td class="data"><?php echo $row_rsAccounts['atName']; ?></td>
          </tr>
          <?php } while ($row_rsAccounts = mysqli_fetch_assoc($rsAccounts)); ?>
      </tbody>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsAccounts);

mysqli_free_result($rsGroupName);
?>

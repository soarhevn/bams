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

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAssetAccounts = "
SELECT accountID, accountName 
FROM accountNames 
WHERE accountType IN (2, 3) 
AND accountID > 0 
ORDER BY accountName ASC";
$rsAssetAccounts = mysqli_query($MySQL_Union, $query_rsAssetAccounts) or die(mysqli_error($MySQL_Union));
$row_rsAssetAccounts = mysqli_fetch_assoc($rsAssetAccounts);
$totalRows_rsAssetAccounts = mysqli_num_rows($rsAssetAccounts);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAssetBalances = "
SELECT accountName, 
FORMAT(IFNULL(SUM(debit),0) - IFNULL(SUM(credit),0),0) AS balance 
FROM transactions 
LEFT JOIN accountNames USING (accountID) 
WHERE accountNames.accountType IN (2, 3) 
AND accountID > 0 
GROUP BY accountID 
ORDER BY accountName";
$rsAssetBalances = mysqli_query($MySQL_Union, $query_rsAssetBalances) or die(mysqli_error($MySQL_Union));
$row_rsAssetBalances = mysqli_fetch_assoc($rsAssetBalances);
$totalRows_rsAssetBalances = mysqli_num_rows($rsAssetBalances);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 資產與負債 - Asset &amp; Liability Search</title>
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
    - 會員搜尋</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->資產和負債<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="assetMASTER.php" method="get" id="assetSEARCH">
      <table class="tableForm">
        <tr>
          <td>資產與負債
            <select name="accountID" id="accountID">
              <?php
do {  
?>
              <option value="<?php echo $row_rsAssetAccounts['accountID']?>"><?php echo $row_rsAssetAccounts['accountName']?></option>
              <?php
} while ($row_rsAssetAccounts = mysqli_fetch_assoc($rsAssetAccounts));
  $rows = mysqli_num_rows($rsAssetAccounts);
  if($rows > 0) {
      mysqli_data_seek($rsAssetAccounts, 0);
	  $row_rsAssetAccounts = mysqli_fetch_assoc($rsAssetAccounts);
  }
?>
          </select></td>
          <td>&nbsp;</td>
          <td>從
            <input name="dateFrom" type="text" id="dateFrom" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-", time() + 3600*($timezone+date("I")));  echo '01'; ?>" size="10" maxlength="10" />
            到
            <input name="dateTo" type="text" id="dateTo" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td class="right"><input type="submit" name="Submit" value="搜尋" /></td>
        </tr>
      </table>
    </form>
    <h2>資產結餘</h2>
    <table class="data">
      <thead class="data">
        <tr class="data">
          <th class="data">資產與負債</th>
          <th class="data">結餘</th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { $class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
        <tr class="<?php echo $class; ?>">
          <td class="data"><?php echo $row_rsAssetBalances['accountName']; ?></td>
          <td class="data right"><?php echo $row_rsAssetBalances['balance']; ?></td>
        </tr>
        <?php } while ($row_rsAssetBalances = mysqli_fetch_assoc($rsAssetBalances)); ?>
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
mysqli_free_result($rsAssetAccounts);

mysqli_free_result($rsAssetBalances);
?>

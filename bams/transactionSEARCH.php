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
?>
<?php require_once('Connections/MySQL_Union.php'); ?>
<?php
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsaccountNames = "
	SELECT * 
	FROM accountNames 
	WHERE accountID > 0 
	AND accountType >= 0
	ORDER BY acctCODE ASC";
$rsaccountNames = mysqli_query($MySQL_Union, $query_rsaccountNames) or die(mysqli_error($MySQL_Union));
$row_rsaccountNames = mysqli_fetch_assoc($rsaccountNames);
$totalRows_rsaccountNames = mysqli_num_rows($rsaccountNames);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountGroups = "SELECT * FROM accountGroups WHERE inactive = 0 ORDER BY accGrpName ASC";
$rsAccountGroups = mysqli_query($MySQL_Union, $query_rsAccountGroups) or die(mysqli_error($MySQL_Union));
$row_rsAccountGroups = mysqli_fetch_assoc($rsAccountGroups);
$totalRows_rsAccountGroups = mysqli_num_rows($rsAccountGroups);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 收入與支出 - Transaction Search</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<style type="text/css">
<!--
.style2 {
	width: 20px;
}
-->
</style>
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("dateFrom"));

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
    <!-- InstanceBeginEditable name="Breadcrumbs" --> <a href="memberSEARCH.php">首頁
    - 會員搜尋</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->收入與支出搜索<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <p><a href="accountsMASTER.php?dateFrom=<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m", time() + 3600*($timezone+date("I"))); ?>-02&amp;dateTo=<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>&amp;accountID=%25&amp;textFree=&amp;Submit=Submit">本月份</a> </p>
    <form action="transactionMASTER.php" method="get" id="accountSEARCH">
      <table class="tableForm">
        <thead class="data">
          <tr class="data">
            <th colspan="4" class="data">其他條件搜尋 </th>
          </tr>
        </thead>
        <tr>
          <td rowspan="4" class="tableForm">科目<br />
            <select name="accountID[]" size="15" multiple="multiple" id="accountID">
              <option value="%" selected="selected">全部</option>
              <?php do {  ?>
              <option value="<?php echo $row_rsaccountNames['accountID']?>"> <?php echo $row_rsaccountNames['acctCODE'] . " " . $row_rsaccountNames['accountName']?></option>
              <?php
} while ($row_rsaccountNames = mysqli_fetch_assoc($rsaccountNames));
  $rows = mysqli_num_rows($rsaccountNames);
  if($rows > 0) {
      mysqli_data_seek($rsaccountNames, 0);
	  $row_rsaccountNames = mysqli_fetch_assoc($rsaccountNames);
  }
?>
            </select></td>
          <td rowspan="4" class="tableForm style2">&nbsp;</td>
          <td colspan="2" class="tableForm">從
            <input name="dateFrom" type="text" id="dateFrom" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-", time() + 3600*($timezone+date("I")));  echo '01'; ?>" size="10" maxlength="10" />
            到
            <input name="dateTo" type="text" id="dateTo" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <td colspan="2" class="tableForm">摘要關鍵字
            <input name="textFree" type="text" id="textFree" size="32" maxlength="100" /></td>
        </tr>
        <tr>
          <td rowspan="2" class="tableForm">帳目組<br />
            <select name="accGrpID[]" size="11" multiple="multiple" id="accGrpID">
              <option value="%" selected="selected">全部</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsAccountGroups['accGrpID']?>"><?php echo $row_rsAccountGroups['accGrpName']?></option>
              <?php
} while ($row_rsAccountGroups = mysqli_fetch_assoc($rsAccountGroups));
  $rows = mysqli_num_rows($rsAccountGroups);
  if($rows > 0) {
      mysqli_data_seek($rsAccountGroups, 0);
	  $row_rsAccountGroups = mysqli_fetch_assoc($rsAccountGroups);
  }
?>
            </select></td>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm"><input type="submit" name="Submit" value="搜尋" /></th>
        </tr>
      </table>
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
  <div id="sectionLinks">
    <ul>
      <li><a href="transactionINSERT.php">增加筆數</a></li>
      <li><a href="accountNamesDETAIL.php">帳目名稱</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>報表</h3>
    <ul>
      <li><a href="transactionWIRE.php">匯款繳費</a></li>
      <li><a href="reportCHOOSER.php">報表</a></li>
      <li><a href="budgetPnL.php">收支決算</a></li>
    </ul>
  </div>
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($rsaccountNames);

mysqli_free_result($rsAccountGroups);
?>

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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $updateSQL = sprintf("UPDATE accountGroups SET accGrpName=%s, description=%s, inactive=%s WHERE accGrpID=%s",
                       GetSQLValueString($MySQL_Union, $_POST['accGrpName'], "text"),
					   GetSQLValueString($MySQL_Union, $_POST['description'], "text"),
                       GetSQLValueString($MySQL_Union, isset($_POST['checkbox']) ? "true" : "", "defined","1","0"),
                       GetSQLValueString($MySQL_Union, $_POST['accGrpID'], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

  $updateGoTo = "accountGroupsDETAIL.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}

$colname_rsAccountGroupName = "-1";
if (isset($_GET['recordID'])) {
  $colname_rsAccountGroupName = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountGroupName = sprintf("SELECT * FROM accountGroups WHERE accGrpID = %s ORDER BY accGrpName ASC", GetSQLValueString($MySQL_Union, $colname_rsAccountGroupName, "int"));
$rsAccountGroupName = mysqli_query($MySQL_Union, $query_rsAccountGroupName) or die(mysqli_error($MySQL_Union));
$row_rsAccountGroupName = mysqli_fetch_assoc($rsAccountGroupName);
$totalRows_rsAccountGroupName = mysqli_num_rows($rsAccountGroupName);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_AccountNames = sprintf("
SELECT accountNames.acctCODE, accountNames.accountName, accountType.atName, accountNames.description
FROM accountGroupXref 
LEFT JOIN accountGroups ON accountGroupXref.accGrp_id = accountGroups.accGrpID
LEFT JOIN accountNames ON accountGroupXref.accName_id = accountNames.accountID
LEFT JOIN accountType ON accountNames.accountType = accountType.acctTypeID
WHERE accountGroupXref.accGrp_id = %s 
ORDER BY accountNames.acctCODE ASC", GetSQLValueString($MySQL_Union, $colname_rsAccountGroupName, "int"));
$rs_AccountNames = mysqli_query($MySQL_Union, $query_rs_AccountNames) or die(mysqli_error($MySQL_Union));
$row_rs_AccountNames = mysqli_fetch_assoc($rs_AccountNames);
$totalRows_rs_AccountNames = mysqli_num_rows($rs_AccountNames);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 編輯帳目組 - Edit Account Group Name</title>
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> / <a href="accountNamesDETAIL.php">帳目名稱</a> / <a href="accountGroupsDETAIL.php">帳目組</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->編輯帳目組<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" name="form1" id="form1" onsubmit="MM_validateForm('accGrpName','帳目組','R','description','備註','R');return document.MM_returnValue">
      <table class="tableForm">
        <tr>
          <th class="tableForm">帳目組:</th>
          <td colspan="2" class="tableForm"><input name="accGrpName" type="text" value="<?php echo $row_rsAccountGroupName['accGrpName']; ?>" size="20" maxlength="20" /></td>
        </tr>
        <tr>
          <th class="tableForm">備註:</th>
          <td colspan="2" class="tableForm"><input name="description" type="text" id="description" size="50" maxlength="100" value="<?php echo $row_rsAccountGroupName['description']; ?>" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input type="submit" value="更改確定" /></td>
          <td class="tableForm right">帳目終止
            <input <?php if (!(strcmp($row_rsAccountGroupName['inactive'],1))) {echo "checked=\"checked\"";} ?> type="checkbox" name="checkbox" value="checkbox" /></td>
        </tr>
      </table>
      <input type="hidden" name="accGrpID" value="<?php echo $row_rsAccountGroupName['accGrpID']; ?>" />
      <input type="hidden" name="MM_update" value="form1" />
    </form>
	<br />
    <table class="data">
      <thead class="data">
        <tr class="data">
          <th class="data"><span class="tableForm">編碼</span></th>
          <th class="data">科目名稱</th>
          <th class="data">科目類別</th>
          <th class="data">備註</th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
	  			$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
          <tr class="<?php echo $class; ?>">
            <td class="data"><?php echo $row_rs_AccountNames['acctCODE']; ?></td>
            <td class="data"><?php echo $row_rs_AccountNames['accountName']; ?></td>
            <td class="data center"><?php echo $row_rs_AccountNames['atName']; ?></td>
            <td class="data"><?php echo $row_rs_AccountNames['description']; ?></td>
          </tr>
          <?php } while ($row_rs_AccountNames = mysqli_fetch_assoc($rs_AccountNames)); ?>
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
mysqli_free_result($rsAccountGroupName);

mysqli_free_result($rs_AccountNames);
?>

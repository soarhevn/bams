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
<?php require_once('Connections/MySQL_Union.php'); 

$colname_rsDependents = "1";
if (isset($_GET['recordID'])) {
  $colname_rsDependents = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsDependents = sprintf("
SELECT m.name AS nameMem, m.cardNum AS cardNumMem, d.ID, d.idNumber, d.idParent, d.name, d.cardNum, TRIM(LEADING '0' FROM (DATE_SUB(d.birthday, INTERVAL 1911 YEAR))) AS birthday, d.handicap, TRIM(LEADING '0' FROM (DATE_SUB(d.insureDate, INTERVAL 1911 YEAR))) AS insureDate, TRIM(LEADING '0' FROM (DATE_SUB(d.inactive, INTERVAL 1911 YEAR))) AS inactive, TRIM(LEADING '0' FROM (DATE_SUB(d.memberDate, INTERVAL 1911 YEAR))) AS memberDate 
FROM dependents d, members m 
WHERE d.ID = '%s' AND m.idNumber = d.idParent", $colname_rsDependents);
$rsDependents = mysqli_query($MySQL_Union, $query_rsDependents) or die(mysqli_error($MySQL_Union));
$row_rsDependents = mysqli_fetch_assoc($rsDependents);
$totalRows_rsDependents = mysqli_num_rows($rsDependents);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsHandicap = "SELECT * FROM handicapArray";
$rsHandicap = mysqli_query($MySQL_Union, $query_rsHandicap) or die(mysqli_error($MySQL_Union));
$row_rsHandicap = mysqli_fetch_assoc($rsHandicap);
$totalRows_rsHandicap = mysqli_num_rows($rsHandicap);

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
  $updateSQL = sprintf("UPDATE dependents SET idNumber=%1\$s, name=%2\$s, cardNum=%3\$s, birthday=REPLACE(%4\$s, (SUBSTRING_INDEX(%4\$s, '-', 1)), ((SUBSTRING_INDEX(%4\$s, '-', 1)) + 1911)), handicap=%5\$s, insureDate=REPLACE(%6\$s, (SUBSTRING_INDEX(%6\$s, '-', 1)), ((SUBSTRING_INDEX(%6\$s, '-', 1)) + 1911)), inactive=REPLACE(%7\$s, (SUBSTRING_INDEX(%7\$s, '-', 1)), ((SUBSTRING_INDEX(%7\$s, '-', 1)) + 1911)), memberDate=REPLACE(%8\$s, (SUBSTRING_INDEX(%8\$s, '-', 1)), ((SUBSTRING_INDEX(%8\$s, '-', 1)) + 1911)) WHERE ID=%9\$s",
                       GetSQLValueString($MySQL_Union, $_POST['idNumber'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['name'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['cardNum'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['birthday'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['handicap'], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['insureDate'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['inactive'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['memberDate'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['ID'], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

  $updateGoTo = "memberDETAIL.php?idNumber=" . $row_rsDependents['idParent'] . "";
  header(sprintf("Location: %s", $updateGoTo));
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 屬資料編輯 - Edit Dependent</title>
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
    - 會員搜尋</a> / 成員搜索結果 / <a href="memberDETAIL.php?recordID=<?php echo $row_rsDependents['idParent']; ?>">會員資料總表</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->眷屬資料編輯<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" onsubmit="MM_validateForm('idNumber','身分證字號','R','name','姓名','R');return document.MM_returnValue">
      <table class="tableForm">
        <thead class="data">
          <tr class="data">
            <th colspan="2" class="data"><?php echo $row_rsDependents['nameMem']; ?> / <?php echo $row_rsDependents['cardNumMem']; ?> / <a href="memberDETAIL.php?recordID=<?php echo $row_rsDependents['idParent']; ?>"> <?php echo $row_rsDependents['idParent']; ?></a></th>
          </tr>
        </thead>
        <tr>
          <th class="tableForm">身分證字號:</th>
          <td class="tableForm"><input type="text" name="idNumber" value="<?php echo $row_rsDependents['idNumber']; ?>" size="32" /></td>
        </tr>
        <tr>
          <th class="tableForm">姓名:</th>
          <td class="tableForm"><input type="text" name="name" value="<?php echo $row_rsDependents['name']; ?>" size="32" /></td>
        </tr>
        <tr>
          <th class="tableForm">卡號:</th>
          <td class="tableForm"><input type="text" name="cardNum" value="<?php echo $row_rsDependents['cardNum']; ?>" size="32" /></td>
        </tr>
        <tr>
          <th class="tableForm">出生日:</th>
          <td class="tableForm"><input name="birthday" type="text" value="<?php echo $row_rsDependents['birthday']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">殘障:</th>
          <td class="tableForm"><select name="handicap">
              <?php 
do {  
?>
              <option value="<?php echo $row_rsHandicap['handicap']?>" <?php if (!(strcmp($row_rsHandicap['handicap'], $row_rsDependents['handicap']))) {echo "SELECTED";} ?>><?php echo $row_rsHandicap['handiName']?></option>
              <?php
} while ($row_rsHandicap = mysqli_fetch_assoc($rsHandicap));
?>
            </select>
          </td>
        </tr>
        <tr>
          <th class="tableForm">加保日:</th>
          <td class="tableForm"><input name="insureDate" type="text" value="<?php echo $row_rsDependents['insureDate']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">入會日:</th>
          <td class="tableForm"><input name="memberDate" type="text" value="<?php echo $row_rsDependents['memberDate']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">退會日:</th>
          <td class="tableForm"><input name="inactive" type="text" value="<?php echo $row_rsDependents['inactive']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input type="submit" value="儲存更新" /></td>
        </tr>
      </table>
      <input type="hidden" name="MM_update" value="form1" />
      <input type="hidden" name="ID" value="<?php echo $row_rsDependents['ID']; ?>" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsDependents);

mysqli_free_result($rsHandicap);
?>

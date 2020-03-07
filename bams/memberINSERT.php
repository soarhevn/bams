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
$colname_rs_referrer = "-1";
if (isset($_GET['referrer'])) {
  $colname_rs_referrer = (get_magic_quotes_gpc()) ? $_GET['referrer'] : addslashes($_GET['referrer']);
}

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
$query_rs_referrer = sprintf("SELECT idNumber, name FROM members WHERE idNumber = %s", GetSQLValueString($MySQL_Union, $colname_rs_referrer, "text"));
$rs_referrer = mysqli_query($MySQL_Union, $query_rs_referrer) or die(mysqli_error($MySQL_Union));
$row_rs_referrer = mysqli_fetch_assoc($rs_referrer);
$totalRows_rs_referrer = mysqli_num_rows($rs_referrer);


$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "formMemberDetail")) {
  $insertSQL = sprintf("INSERT INTO members (idNumber, name, cardNum, salary, birthday, handicap, memberDate, insureHealth, insureDateHealth, address, homePhone, workPhone, mblPhone, email, occupation, insureLabor, insureDateLabor, changeDateSal, monthlyBill, referrer, salaryIncrease) 
  VALUES (UPPER(%1\$s), %2\$s, %3\$s, %4\$s, REPLACE(%5\$s, (SUBSTRING_INDEX(%5\$s, '-', 1)), ((SUBSTRING_INDEX(%5\$s, '-', 1)) + 1911)), %6\$s, REPLACE(%7\$s, (SUBSTRING_INDEX(%7\$s, '-', 1)), ((SUBSTRING_INDEX(%7\$s, '-', 1)) + 1911)), %8\$s, REPLACE(%9\$s, (SUBSTRING_INDEX(%9\$s, '-', 1)), ((SUBSTRING_INDEX(%9\$s, '-', 1)) + 1911)), %10\$s, %11\$s, %12\$s, %13\$s, %14\$s, %15\$s, %16\$s, REPLACE(%17\$s, (SUBSTRING_INDEX(%17\$s, '-', 1)), ((SUBSTRING_INDEX(%17\$s, '-', 1)) + 1911)), REPLACE(%17\$s, (SUBSTRING_INDEX(%17\$s, '-', 1)), ((SUBSTRING_INDEX(%17\$s, '-', 1)) + 1911)), %18\$s, %19\$s, %20\$s)",
                       GetSQLValueString($MySQL_Union, $_POST['idNumber'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['name'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['cardNum'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['salary'], "double"),
                       GetSQLValueString($MySQL_Union, $_POST['birthday'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['handicap'], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['memberDate'], "date"),
                       GetSQLValueString($MySQL_Union, isset($_POST['insureHealth']) ? "true" : "", "defined","1","0"),
                       GetSQLValueString($MySQL_Union, $_POST['insureDateHealth'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['address'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['homePhone'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['workPhone'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['mblPhone'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['email'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['occupation'], "text"),
					   GetSQLValueString($MySQL_Union, isset($_POST['insureLabor']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($MySQL_Union, $_POST['insureDateLabor'], "date"),
					   GetSQLValueString($MySQL_Union, isset($_POST['monthlyBill']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($MySQL_Union, $_POST['referrer'], "text"),
					   GetSQLValueString($MySQL_Union, isset($_POST['salaryIncrease']) ? "true" : "", "defined","1","0"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));

  $insertGoTo = "memberDETAIL.php?memberID=" . $_POST['idNumber'] . "";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsSalaryINFO = "SELECT salary, salDisplay FROM unionRatesCurrent ORDER BY salary ASC";
$rsSalaryINFO = mysqli_query($MySQL_Union, $query_rsSalaryINFO) or die(mysqli_error($MySQL_Union));
$row_rsSalaryINFO = mysqli_fetch_assoc($rsSalaryINFO);
$totalRows_rsSalaryINFO = mysqli_num_rows($rsSalaryINFO);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsHandicap = "SELECT * FROM handicapArray";
$rsHandicap = mysqli_query($MySQL_Union, $query_rsHandicap) or die(mysqli_error($MySQL_Union));
$row_rsHandicap = mysqli_fetch_assoc($rsHandicap);
$totalRows_rsHandicap = mysqli_num_rows($rsHandicap);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 增加新會員 - New Member</title>
<script type="text/javascript">
<!--
function doubleCheck(){
   if (document.formMemberDetail.idNumber.value != document.formMemberDetail.idNumber2.value)
		  {
          alert('兩身分證字號必須符合.');
		  document.formMemberDetail.idNumber.focus();
		  return(false);
   }
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_validateForm() { //v4.0
  doubleCheck();
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
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("cardNum"));

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
    - 會員搜尋</a> / 成員搜索結果 / 添加新建成員 / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->增加新會員<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" name="formMemberDetail" id="formMemberDetail" onsubmit="MM_validateForm('cardNum','卡號','R','name','姓名','R','idNumber','身分證字號','R','idNumber2','重複身分證字號','R');return document.MM_returnValue">
      <table class="tableForm">
        <tr>
          <th class="tableForm">卡號:</th>
          <td class="tableForm"><input name="cardNum" id="cardNum" type="text" size="6" maxlength="6" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">姓名:</th>
          <td class="tableForm"><input name="name" type="text" size="15" maxlength="50" /></td>
        </tr>
        <tr>
          <th class="tableForm">身分證字號:</th>
          <td class="tableForm"><input name="idNumber" type="text" size="10" maxlength="10" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">重複身分證字號:</th>
          <td class="tableForm"><input name="idNumber2" type="text" size="10" maxlength="10" onblur="doubleCheck();" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"> 出生日: </th>
          <td class="tableForm"><input name="birthday" type="text" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"></th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm"> 住家電話:</th>
          <td class="tableForm"><input name="homePhone" type="text" size="15" maxlength="50" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"> 工作電話:</th>
          <td class="tableForm"><input name="workPhone" type="text" size="15" maxlength="50" /></td>
        </tr>
        <tr>
          <th class="tableForm"> 行動電話:</th>
          <td class="tableForm"><input name="mblPhone" type="text" size="15" maxlength="50" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"></th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm"> 電子郵件:</th>
          <td colspan="4" class="tableForm"><input name="email" type="text" size="50" maxlength="100" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"></th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm"> 住址:</th>
          <td colspan="4" class="tableForm"><input name="address" type="text" value="" size="50" maxlength="255" /></td>
        </tr>
        <tr>
          <th height="19" class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">投保薪資:</th>
          <td class="tableForm"><select name="salary">
              <?php 
do {  
?>
              <option value="<?php echo $row_rsSalaryINFO['salary']?>" >$<?php echo $row_rsSalaryINFO['salDisplay'] ?></option>
              <?php
} while ($row_rsSalaryINFO = mysqli_fetch_assoc($rsSalaryINFO));
?>
            </select></td>
          <td class="tableForm">&nbsp;</td>
          <th align="right" class="tableForm">殘障:</th>
          <td class="tableForm"><select name="handicap">
              <?php 
do {  
?>
              <option value="<?php echo $row_rsHandicap['handicap']?>" ><?php echo $row_rsHandicap['handiName']?></option>
              <?php
} while ($row_rsHandicap = mysqli_fetch_assoc($rsHandicap));
?>
            </select></td>
        </tr>
        <tr>
          <th class="tableForm">提高投保薪資</th>
          <td class="tableForm"><input name="salaryIncrease" type="checkbox" value="1" checked="checked" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"> 職別:</th>
          <td class="tableForm"><input name="occupation" type="text" value="" size="32" maxlength="50" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"></th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm"> 健保:</th>
          <td class="tableForm"><input name="insureHealth" type="checkbox" value="" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm"> 勞保:</th>
          <td class="tableForm"><input name="insureLabor" type="checkbox" value="" /></td>
        </tr>
        <tr>
          <th class="tableForm">健保日:</th>
          <td class="tableForm"><input name="insureDateHealth" type="text" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
          <td class="tableForm">&nbsp;</td>
          <th align="right" class="tableForm">勞保日:</th>
          <td class="tableForm"><input name="insureDateLabor" type="text" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">採每月繳費:</th>
          <td class="tableForm"><input name="monthlyBill" type="checkbox" value="" /></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">入會日:</th>
          <td class="tableForm"><input name="memberDate" type="text" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日 </td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">保證人:</th>
          <td class="tableForm"><?php echo $row_rs_referrer['name']; ?></td>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <td colspan="5" align="right" class="tableForm"><input type="submit" value="添加新建成員" /></td>
        </tr>
      </table>
      <input name="referrer" type="hidden" value="<?php echo $row_rs_referrer['idNumber']; ?>" />
      <input type="hidden" name="MM_insert" value="formMemberDetail" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsSalaryINFO);

mysqli_free_result($rsHandicap);

mysqli_free_result($rs_referrer);
?>

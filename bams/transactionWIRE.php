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
require_once('Connections/MySQL_Union.php');

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsWireTransferAmounts = "
SELECT 
ROUND(w.unionDues - p.unionDues) AS unionDues,
ROUND(w.laborIns - p.laborIns) AS laborIns,
ROUND(w.medIns - p.medIns) AS medIns,
ROUND(w.newMemDues - p.newMemDues) AS newMemDues
FROM (
/* Get amounts that need to be xfer'd because of wire (w/o xfer'd already) */
SELECT SUM(unionDues) AS unionDues, SUM(laborIns) AS laborIns, SUM(medIns) AS medIns, SUM(newMemDues) AS newMemDues
FROM ( (
SELECT wire, -(IFNULL(SUM(laborIns),0) + IFNULL(SUM(medIns),0) + IFNULL(SUM(newMemDues),0)) AS unionDues, IFNULL(SUM(laborIns),0) AS laborIns, 
IFNULL(SUM(medIns),0) AS medIns, IFNULL(SUM(newMemDues),0) AS newMemDues
FROM income
WHERE wire = 40
AND YEAR(paidDate) >= 2007
GROUP BY wire
) UNION (
SELECT wire, IFNULL(SUM(unionDues),0) + IFNULL(SUM(newMemDues2),0) AS unionDues, 
-(IFNULL(SUM(unionDues),0) + IFNULL(SUM(newMemDues2),0) + IFNULL(SUM(medIns),0) + IFNULL(SUM(newMemDues),0)) AS laborIns, 
IFNULL(SUM(medIns),0) AS medIns, IFNULL(SUM(newMemDues),0) AS newMemDues
FROM income
WHERE wire = 42
AND YEAR(paidDate) >= 2007
GROUP BY wire
) UNION (
SELECT wire, IFNULL(SUM(unionDues),0) + IFNULL(SUM(newMemDues2),0) AS unionDues, IFNULL(SUM(laborIns),0) AS laborIns, 
-(IFNULL(SUM(unionDues),0) + IFNULL(SUM(newMemDues2),0) + IFNULL(SUM(laborIns),0) + IFNULL(SUM(newMemDues),0)) AS medIns, 
IFNULL(SUM(newMemDues),0) AS newMemDues
FROM income
WHERE wire = 43
AND YEAR(paidDate) >= 2007
GROUP BY wire
) UNION (
SELECT wire, IFNULL(SUM(unionDues),0) + IFNULL(SUM(newMemDues2),0) AS unionDues, IFNULL(SUM(laborIns),0) AS laborIns, 
IFNULL(SUM(medIns),0) AS medIns, 
-(IFNULL(SUM(unionDues),0) + IFNULL(SUM(newMemDues2),0) + IFNULL(SUM(laborIns),0) + IFNULL(SUM(medIns),0)) AS newMemDues
FROM income
WHERE wire = 44
AND YEAR(paidDate) >= 2007
GROUP BY wire
) ) accts ) w,
(
/* Get previous wire settlements */
SELECT 
IFNULL(SUM(CASE accountID WHEN 40 THEN previousXfer ELSE 0 END),0) AS unionDues,
IFNULL(SUM(CASE accountID WHEN 42 THEN previousXfer ELSE 0 END),0) AS laborIns,
IFNULL(SUM(CASE accountID WHEN 43 THEN previousXfer ELSE 0 END),0) AS medIns,
IFNULL(SUM(CASE accountID WHEN 44 THEN previousXfer ELSE 0 END),0) AS newMemDues
FROM (
SELECT DISTINCT t1.accountID AS accountID, 
( IFNULL(SUM(t1.debit),0) - IFNULL(SUM(t1.credit),0) ) AS previousXfer
FROM transactionsMaster
LEFT JOIN transactions t1 USING (idMaster)
LEFT JOIN transactions t2 USING (idMaster)
WHERE t1.accountID != t2.accountID
AND t1.accountID IN (40, 42, 43, 44)
AND marker = -1
AND YEAR(transDate) >= 2007
GROUP BY t1.accountID
) v ) p
";
$rsWireTransferAmounts = mysqli_query($MySQL_Union, $query_rsWireTransferAmounts) or die(mysqli_error($MySQL_Union));
$row_rsWireTransferAmounts = mysqli_fetch_assoc($rsWireTransferAmounts);
$totalRows_rsWireTransferAmounts = mysqli_num_rows($rsWireTransferAmounts);


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

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  
  $unionDues = $_POST['unionDues'];
  $laborIns = $_POST['laborIns'];
  $medIns = $_POST['medIns'];
  $newMemDues = $_POST['newMemDues'];
  
  // take any amounts < 0 in 42-44 and debit 40
  if ($laborIns < 0) {
	$laborInsABS = abs($laborIns);
	insertTransaction("40", "42", $laborInsABS, "");
	$laborIns = 0;
  }
  if ($medIns < 0) {
	$medInsABS = abs($medIns);
	insertTransaction("40", "43", $medInsABS, "");
	$medIns = 0;
  }
  if ($newMemDues < 0) {
	$newMemDuesABS = abs($newMemDues);
	insertTransaction("40", "44", $newMemDuesABS, "");
	$newMemDues = 0;
  }
  
  // call insertTrans for 42-44 > 0 credit 40
  if ($laborIns > 0) {
	insertTransaction("40", "42", "", $laborIns);
  }
  if ($medIns > 0) {
	insertTransaction("40", "43", "", $medIns);
  }
  if ($newMemDues > 0) {
	insertTransaction("40", "44", "", $newMemDues);
  }
  
  // end of insert transactions
    
  $updateGoTo = "transactionSEARCH.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}

function insertTransaction($acctID1, $acctID2, $debit, $credit)
	// insert tranactions to transMaster, get last insert ID
{
	$marker = -1;
	$wireText = "匯款繳費";
	global $database_MySQL_Union, $MySQL_Union;
	$insertSQLmaster = sprintf("
		INSERT INTO transactionsMaster 
		(transDate, changeDate, notes, marker) 
		VALUES 
		(REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), ((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), 
			NOW(), %2\$s, %3\$s)",
				GetSQLValueString($MySQL_Union, $_POST['transDate'], "date"), 	// 1
				GetSQLValueString($MySQL_Union, $wireText, "text"),				// 2
				GetSQLValueString($MySQL_Union, $marker, "int"));					// 3
		mysqli_select_db($MySQL_Union, $database_MySQL_Union);
		$ResultSQLmaster = mysqli_query($MySQL_Union, $insertSQLmaster) or die(mysqli_error($MySQL_Union));

	// get last inserted ID
	$transMasterID = mysqli_insert_id($MySQL_Union);
		
	// insert tranaction
	$insertSQLtrans = sprintf("INSERT INTO transactions 
		(idMaster, accountID, debit, credit) 
		VALUES 
		(%1\$s, %2\$s, %4\$s, %5\$s),	
		(%1\$s, %3\$s, %5\$s, %4\$s)",
				GetSQLValueString($MySQL_Union, $transMasterID, "int"), 			// 1
				GetSQLValueString($MySQL_Union, $acctID1, "int"),					// 2
				GetSQLValueString($MySQL_Union, $acctID2, "int"),					// 3
				GetSQLValueString($MySQL_Union, $debit, "double"),				// 4
				GetSQLValueString($MySQL_Union, $credit, "double")				// 5
				);
        mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  		$ResultSQLtrans = mysqli_query($MySQL_Union, $insertSQLtrans) or die(mysqli_error($MySQL_Union));
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 匯款繳費 - Wire</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript">
<!--
function checksum() { // check to sum of values = 0
  var total=eval(document.forms['form1'].elements['unionDues'].value);
  total +=eval(document.forms['form1'].elements['laborIns'].value);
  total +=eval(document.forms['form1'].elements['medIns'].value);
  total +=eval(document.forms['form1'].elements['newMemDues'].value);
  if(total!=0) { 
  	alert("要讓匯款轉帳正確,此四筆款項總何必須是零!");
  	return false
  }
  else {return true}
}

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
  if (errors == "") { checksumError = checksum(); }
  document.MM_returnValue = (errors == '') && checksumError;
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出搜索</a> /<!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->匯款繳費<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <h2>請先列印此頁!</h2>
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" onsubmit="MM_validateForm('unionDues','需從經常會費帳戶','RisNum','laborIns','轉入勞保費帳戶','RisNum','medIns','轉入健保費帳戶','RisNum','newMemDues','互助金保費帳戶','RisNum');return document.MM_returnValue">
      <table class="tableForm">
        <tr>
          <th class="tableForm">轉帳日期:</th>
          <td class="tableForm"><input name="transDate" type="text" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" maxlength="10" /></td>
        </tr>
        <tr>
          <th class="tableForm">需從經常會費帳戶:</th>
          <td class="tableForm"><input name="unionDues" type="text" value="<?php echo $row_rsWireTransferAmounts['unionDues']; ?>" size="19" maxlength="19" /></td>
        </tr>
        <tr>
          <th class="tableForm">轉入勞保費帳戶:</th>
          <td class="tableForm"><input name="laborIns" type="text" value="<?php echo $row_rsWireTransferAmounts['laborIns']; ?>" size="19" maxlength="19" /></td>
        </tr>
        <tr>
          <th class="tableForm">轉入健保費帳戶:</th>
          <td class="tableForm"><input name="medIns" type="text" value="<?php echo $row_rsWireTransferAmounts['medIns']; ?>" size="19" maxlength="19" /></td>
        </tr>
        <tr>
          <th class="tableForm">互助金保費帳戶:</th>
          <td class="tableForm"><input name="newMemDues" type="text" value="<?php echo $row_rsWireTransferAmounts['newMemDues']; ?>" size="19" maxlength="19" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input type="submit" value="記錄轉帳" /></td>
        </tr>
      </table>
      <input type="hidden" name="MM_insert" value="form1" />
    </form>
    <p>請在按下&quot;記錄轉帳&quot;之前,先列印此頁.<br />
      當按下&quot;記錄轉帳&quot;的按鍵時,將會同時加入這三筆資料於收入與支出的帳目中,一筆是需要從經常會費轉出之金額,一筆是轉入勞保費之帳目,另一筆是轉入健保費之帳目.</p>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsWireTransferAmounts);
?>

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

// Array comparison fuction
function standard_array_compare($op1, $op2)
{
    if (count($op1) < count($op2)) {
        return -1; // $op1 < $op2
    } elseif (count($op1) > count($op2)) {
        return 1; // $op1 > $op2
    }
    foreach ($op1 as $key => $val) {
        if (!array_key_exists($key, $op2)) {
            return null; // uncomparable
        } elseif ($val < $op2[$key]) {
            return -1;
        } elseif ($val > $op2[$key]) {
            return 1;
        }
    }
    return 0; // $op1 == $op2
}

// form submit logic
if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {

// create arrays out of each column of current and submitted data in order to compare
	$currentRowRates = [
          "unionDues" => $_POST['c_unionDues'],
          "laborIns" => $_POST['c_laborIns'],
          "medIns" => $_POST['c_medIns'],
          ];
    
    $currentRowDates = [
          "unionDues_dateEffective" => $_POST['c_unionDues_dateEffective'],
          "laborIns_dateEffective" => $_POST['c_laborIns_dateEffective'],
          "medIns_dateEffective" => $_POST['c_medIns_dateEffective'],
          ];
          
	$formRowRates = [
          "unionDues" => $_POST['unionDues'],
          "laborIns" => $_POST['laborIns'],
          "medIns" => $_POST['medIns'],
          ];
          
	$formRowDates = [
          "unionDues_dateEffective" => $_POST['unionDues_dateEffective'],
          "laborIns_dateEffective" => $_POST['laborIns_dateEffective'],
          "medIns_dateEffective" => $_POST['medIns_dateEffective'],
          ];

// 	start if statements:
//  if only the rate OR the date (but not both) changed, we will assume user wants
//		to update the current rate with the same effective date, or update the effective
//		date with the same rate (maybe they made a mistake)
//	if the rate & date changes, then we will insert a new row for a new rate + date
//	if no change was made and the user still submited, no SQL submit
	if ((standard_array_compare($currentRowRates, $formRowRates) == 0) XOR (standard_array_compare($currentRowDates, $formRowDates) == 0)) {
	
  $updateSQL = sprintf("UPDATE unionRates SET salDisplay=%1\$s, unionDues=%2\$s, unionDues_dateEffective=REPLACE(%3\$s, (SUBSTRING_INDEX(%3\$s, '-', 1)), ((SUBSTRING_INDEX(%3\$s, '-', 1)) + 1911)), laborIns=%4\$s, laborIns_dateEffective=REPLACE(%5\$s, (SUBSTRING_INDEX(%5\$s, '-', 1)), ((SUBSTRING_INDEX(%5\$s, '-', 1)) + 1911)), medIns=%6\$s, medIns_dateEffective=REPLACE(%7\$s, (SUBSTRING_INDEX(%7\$s, '-', 1)), ((SUBSTRING_INDEX(%7\$s, '-', 1)) + 1911)) WHERE ID=%8\$s",
                       GetSQLValueString($MySQL_Union, $_POST['salDisplay'], "text"),				//1
                       GetSQLValueString($MySQL_Union, $_POST['unionDues'], "double"),			//2
                       GetSQLValueString($MySQL_Union, $_POST['unionDues_dateEffective'], "date"),//3
                       GetSQLValueString($MySQL_Union, $_POST['laborIns'], "double"),				//4
                       GetSQLValueString($MySQL_Union, $_POST['laborIns_dateEffective'], "date"),	//5
                       GetSQLValueString($MySQL_Union, $_POST['medIns'], "double"),				//6
                       GetSQLValueString($MySQL_Union, $_POST['medIns_dateEffective'], "date"),	//7
                       GetSQLValueString($MySQL_Union, $_POST['ID'], "int"));						//8

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

  $updateGoTo = "billingRatesDETAIL.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
    }
  header(sprintf("Location: %s", $updateGoTo));

	} elseif ((standard_array_compare($currentRowRates, $formRowRates) != 0) && (standard_array_compare($currentRowDates, $formRowDates) != 0)) {
	
	$insertSQL = sprintf("INSERT INTO unionRates (salDisplay, unionDues, unionDues_dateEffective, laborIns, laborIns_dateEffective, medIns, medIns_dateEffective, salary) VALUES (%1\$s, %2\$s, REPLACE(%3\$s, (SUBSTRING_INDEX(%3\$s, '-', 1)), ((SUBSTRING_INDEX(%3\$s, '-', 1)) + 1911)), %4\$s, REPLACE(%5\$s, (SUBSTRING_INDEX(%5\$s, '-', 1)), ((SUBSTRING_INDEX(%5\$s, '-', 1)) + 1911)), %6\$s, REPLACE(%7\$s, (SUBSTRING_INDEX(%7\$s, '-', 1)), ((SUBSTRING_INDEX(%7\$s, '-', 1)) + 1911)), %8\$s)",
                       GetSQLValueString($MySQL_Union, $_POST['salDisplay'], "text"),				//1
                       GetSQLValueString($MySQL_Union, $_POST['unionDues'], "double"),			//2
                       GetSQLValueString($MySQL_Union, $_POST['unionDues_dateEffective'], "date"),//3
                       GetSQLValueString($MySQL_Union, $_POST['laborIns'], "double"),				//4
                       GetSQLValueString($MySQL_Union, $_POST['laborIns_dateEffective'], "date"),	//5
                       GetSQLValueString($MySQL_Union, $_POST['medIns'], "double"),				//6
                       GetSQLValueString($MySQL_Union, $_POST['medIns_dateEffective'], "date"),	//7
                       GetSQLValueString($MySQL_Union, $_POST['salary'], "double"));				//8

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));

  $insertGoTo = "billingRatesDETAIL.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));

	} else { header("Location:billingRatesDETAIL.php"); }
}

$colname_rsunionRates = "1";
if (isset($_GET['recordID'])) {
  $colname_rsunionRates = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsunionRates = sprintf("
SELECT ID, salDisplay, salary, 
unionDues, TRIM(LEADING '0' FROM (DATE_SUB(unionDues_dateEffective, INTERVAL 1911 YEAR))) AS unionDues_dateEffective, 
laborIns, TRIM(LEADING '0' FROM (DATE_SUB(laborIns_dateEffective, INTERVAL 1911 YEAR))) AS laborIns_dateEffective, 
medIns, TRIM(LEADING '0' FROM (DATE_SUB(medIns_dateEffective, INTERVAL 1911 YEAR))) AS medIns_dateEffective 
FROM unionRates WHERE ID = %s", $colname_rsunionRates);
$rsunionRates = mysqli_query($MySQL_Union, $query_rsunionRates) or die(mysqli_error($MySQL_Union));
$row_rsunionRates = mysqli_fetch_assoc($rsunionRates);
$totalRows_rsunionRates = mysqli_num_rows($rsunionRates);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
	<meta name="generator" content="HTML Tidy for Mac OS X (vers 31 October 2006 - Apple Inc. build 15.15), see www.w3.org" />
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!-- InstanceBeginEditable name="doctitle" -->
	<title>音樂工會: 薪金和費率矩陣 - Edit Union Rates</title>

<!-- InstanceEndEditable -->
	<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
	<script type="text/javascript">
//<![CDATA[
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
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' is required.\n'; }
  } if (errors) alert('以下錯誤發生了:\n'+errors);
  document.MM_returnValue = (errors == '');
  }
  //-->
  //]]>
	</script>
<!-- InstanceEndEditable -->
<!-- InstanceParam name="NavBarLeft" type="boolean" value="false" -->
	<script type="text/javascript">
//<![CDATA[
  <!--
  function openHelpWindow(winName,features) { //v2.0
  var sPath = window.location.pathname;
  var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);
  sPage = sPage.replace(/php/,"html");
  theURL = "help/" + sPage;
  window.open(theURL,winName,features);
  }
  //-->
  //]]>
	</script>
</head>
<!-- The structure of this file is exactly the same as 2col_rightNav.html;
     the only difference between the two is the stylesheet they use -->
<body>
<div id="masthead">
	<div id="logout">
<!-- InstanceBeginEditable name="logout" -->
		<a href="%3C?php%20echo%20$logoutAction%20?%3E" class="red">登出</a> 
<!-- InstanceEndEditable -->
	</div>
	<h1 id="siteName">
		音樂工會 
	</h1>
	<div id="globalNav">
		<a href="memberSEARCH.php">首頁 - 會員搜尋</a> | <a href="transactionSEARCH.php">收入與支出</a> | <a href="assetSEARCH.php">資產與負債</a> | <a href="billingUnpaidDUES.php">繳費作業</a> 
	</div>
</div>

<!-- end masthead -->
<div id="content">
	<div id="breadCrumb">
		<div id="help">
			<a href="#" onclick="openHelpWindow('help','scrollbars=yes,resizable=yes,width=400,height=400')">輔助說明</a> 
		</div>

<!-- InstanceBeginEditable name="Breadcrumbs" -->
		<a href="memberSEARCH.php">首頁 - 會員搜尋</a> / <a href="billingRatesDETAIL.php">薪金和費率矩陣</a> / 
<!-- InstanceEndEditable -->
	</div>
	<h2 id="pageName">
<!-- InstanceBeginEditable name="PageName" -->
		薪金和費率矩陣 
<!-- InstanceEndEditable -->
	</h2>
	<div class="mainSection">
<!-- InstanceBeginEditable name="MainSectionBody" -->
		<form action="<?php echo $editFormAction; ?>" method="post" id="form1" name="form1" onsubmit="MM_validateForm('salDisplay','勞/健保投保薪資','R','unionDues','經常會費','RisNum','laborIns','勞保費','RisNum','medIns','健保費','RisNum');return document.MM_returnValue">
			<table class="tableForm">
				<tr>
					<th class="tableForm">投保薪資:$</th>
					<td class="tableForm">&nbsp; 
					<?php echo number_format($row_rsunionRates['salary'],0); ?>
					<input type="hidden" name="salary" value="<?php echo $row_rsunionRates['salary']; ?>" />
					</td>
					<td class="tableForm">&nbsp;</td>
				</tr>
				<tr>
					<th class="tableForm">勞/健保投保薪資:$</th>
					<td colspan="2" class="tableForm"> 
					<input name="salDisplay" type="text" value="<?php echo $row_rsunionRates['salDisplay']; ?>" size="20" maxlength="50" />
					</td>
				</tr>
				<tr>
					<th colspan="3" class="tableForm">&nbsp;</th>
				</tr>
				<tr>
					<th class="tableForm">&nbsp;</th>
					<td class="tableForm">&nbsp;費率</td>
					<td class="tableForm">&nbsp;生效日</td>
				</tr>
				<tr>
					<th class="tableForm">經常會費:$</th>
					<td class="tableForm"> 
					<input name="unionDues" type="text" value="<?php echo $row_rsunionRates['unionDues']; ?>" size="10" maxlength="10" />
					</td>
					<td class="tableForm"> 
					<input name="unionDues_dateEffective" type="text" value="<?php echo $row_rsunionRates['unionDues_dateEffective']; ?>" size="10" maxlength="10" />
					年 ﹣月 ﹣日</td>
				</tr>
				<tr>
					<th class="tableForm">勞保費:$</th>
					<td class="tableForm"> 
					<input name="laborIns" type="text" value="<?php echo $row_rsunionRates['laborIns']; ?>" size="10" maxlength="10" />
					</td>
					<td class="tableForm"> 
					<input name="laborIns_dateEffective" type="text" value="<?php echo $row_rsunionRates['laborIns_dateEffective']; ?>" size="10" maxlength="10" />
					年 ﹣月 ﹣日</td>
				</tr>
				<tr>
					<th class="tableForm">健保費:$</th>
					<td class="tableForm"> 
					<input name="medIns" type="text" value="<?php echo $row_rsunionRates['medIns']; ?>" size="10" maxlength="10" />
					</td>
					<td class="tableForm"> 
					<input name="medIns_dateEffective" type="text" value="<?php echo $row_rsunionRates['medIns_dateEffective']; ?>" size="10" maxlength="10" />
					年 ﹣月 ﹣日</td>
				</tr>
				<tr>
					<th class="tableForm">&nbsp;</th>
					<td class="tableForm"> 
					<input type="submit" value="儲存更新" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="c_unionDues" value="<?php echo $row_rsunionRates['unionDues']; ?>" />
			<input type="hidden" name="c_laborIns" value="<?php echo $row_rsunionRates['laborIns']; ?>" />
			<input type="hidden" name="c_medIns" value="<?php echo $row_rsunionRates['medIns']; ?>" />
			<input type="hidden" name="c_unionDues_dateEffective" value="<?php echo $row_rsunionRates['unionDues_dateEffective']; ?>" />
			<input type="hidden" name="c_laborIns_dateEffective" value="<?php echo $row_rsunionRates['laborIns_dateEffective']; ?>" />
			<input type="hidden" name="c_medIns_dateEffective" value="<?php echo $row_rsunionRates['medIns_dateEffective']; ?>" />
			<input type="hidden" name="MM_update" value="form1" />
			<input type="hidden" name="ID" value="<?php echo $row_rsunionRates['ID']; ?>" />
		</form>
		</br>
<!-- InstanceEndEditable -->
	</div>
</div>
<!--end content -->
<!--end navbar -->
<br />
<!-- InstanceEnd -->
<?php
          mysqli_free_result($rsunionRates);
          ?>
</body>
</html>

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
$MM_authorizedUsers = "admin";
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
$query_rsAccountType = "SELECT * FROM accountType";
$rsAccountType = mysqli_query($MySQL_Union, $query_rsAccountType) or die(mysqli_error($MySQL_Union));
$row_rsAccountType = mysqli_fetch_assoc($rsAccountType);
$totalRows_rsAccountType = mysqli_num_rows($rsAccountType);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsXAccountNames = "SELECT accountID, accountName, atName 
FROM accountNames 
LEFT JOIN accountType ON accountNames.accountType = accountType.acctTypeID
WHERE inactive < 1";
$rsXAccountNames = mysqli_query($MySQL_Union, $query_rsXAccountNames) or die(mysqli_error($MySQL_Union));
$row_rsXAccountNames = mysqli_fetch_assoc($rsXAccountNames);
$totalRows_rsXAccountNames = mysqli_num_rows($rsXAccountNames);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountGroups = "SELECT * FROM accountGroups WHERE inactive = 0 ORDER BY accGrpName ASC";
$rsAccountGroups = mysqli_query($MySQL_Union, $query_rsAccountGroups) or die(mysqli_error($MySQL_Union));
$row_rsAccountGroups = mysqli_fetch_assoc($rsAccountGroups);
$totalRows_rsAccountGroups = mysqli_num_rows($rsAccountGroups);

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

function padCode ($code) {
  foreach ($code as &$value) {
    if($key > 0) { $value = str_pad($value, 2, "0", STR_PAD_LEFT); }
	else { $value = strtoupper($value); }
  }
  $dot_separated = implode(".", $code);
  return $dot_separated;
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  $acctCODE = padCode($_POST['acctCODE']);
  $insertSQL = sprintf("INSERT INTO accountNames 
  (acctCODE, accountName, accountType, xAccountID, description) 
  VALUES (%s, %s, %s, %s, %s)",
                       GetSQLValueString($MySQL_Union, $acctCODE, "text"),
					   GetSQLValueString($MySQL_Union, $_POST['accountName'], "text"),
					   GetSQLValueString($MySQL_Union, $_POST['accountType'], "int"),
					   GetSQLValueString($MySQL_Union, $_POST['xAccount'], "int"),
					   GetSQLValueString($MySQL_Union, $_POST['description'], "text"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));
  
  // get the accountID for the just inserted record
  $accName_id = mysqli_insert_id($MySQL_Union);
  
  // ** start insert values for groups selection **
  $accGrpID = $_POST['accGrpID'];
  $selectarray = $_POST['selected'];
  $tablename = "accountGroupXref";

  // now rotate the array into something usable for the update
  for ($i = 0; $i < count($accGrpID); $i++) {
  	$fieldarray[$i]['accGrp_ID'] = $accGrpID[$i];
	$fieldarray[$i]['accName_id'] = $accName_id;
  }
  
  // I begin by looping through each row that was displayed in the form and initialise the string var:
  foreach ($fieldarray as $rownum => $rowdata) {
   $insert = NULL;
  
  // Each row provides me with the names and values for the primary key, so I can move their details 
  // into the string variable.
  foreach ($rowdata as $fieldname => $fieldvalue) {
      $insert .= "$fieldname='$fieldvalue',";
  } // foreach
  
  // When there are no more fields left I can trim the unwanted ','.
  $insert = rtrim($insert, ',');
  
  // Now I examine the contents of the checkbox in $selectarray and construct the SQL query to  
  // create the entry if the checkbox is ON:
  if (isset($selectarray[$rownum])) {
      $mysql_queryGrp = "INSERT INTO $tablename SET $insert";
    
  // Finally I execute the query and check for errors.
    mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $result = mysqli_query($MySQL_Union, $mysql_queryGrp) or die(mysqli_error($MySQL_Union));
  echo $mysql_queryGrp . " " . $accName_id;
  } // if
  } // foreach
  
  // ** end groups selection insert **
  
  // ** start insert initial asset transaction if asset account add **
    $insertAssetSQL = sprintf("INSERT INTO transactionsMaster (transDate, changeDate, notes) 
VALUES (REPLACE(%1\$s, (SUBSTRING_INDEX(%1\$s, '-', 1)), ((SUBSTRING_INDEX(%1\$s, '-', 1)) + 1911)), NOW(), '撥前期剩餘')",
                       GetSQLValueString($MySQL_Union, $_POST['assetDate'], "date"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $ResultAssetMaster = mysqli_query($MySQL_Union, $insertAssetSQL) or die(mysqli_error($MySQL_Union));
  
  $master_ID = mysqli_insert_id($MySQL_Union);
  
  $insertSQL2 = sprintf("INSERT INTO transactions (idMaster, accountID, debit) 
values (%3\$s, %1\$s, %2\$s), (%3\$s, '0', %2\$s)",
                       GetSQLValueString($MySQL_Union, $accName_id, "int"),	// 1
					   GetSQLValueString($MySQL_Union, $_POST['assetBalance'], "double"),			// 2
					   GetSQLValueString($MySQL_Union, $master_ID, "int"));					// 3
  
  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result2 = mysqli_query($MySQL_Union, $insertSQL2) or die(mysqli_error($MySQL_Union));

  // ** end of initial asset insert **

  // *** start Branch check and insertion ***
  
  $mysql_branchInsert = "INSERT INTO accountNames (acctCODE, accountName, accountType)
	SELECT branchName, branchName, -1 AS accountType
	FROM (
	 (
	 SELECT DISTINCT LEFT(acctCODE,7) AS branchName
	 FROM accountNames
	 WHERE accountType >= 0
	 AND acctCODE IS NOT NULL
	 ) UNION (
	 SELECT DISTINCT LEFT(acctCODE,4)
	 FROM accountNames
	 WHERE accountType >= 0
	 AND acctCODE IS NOT NULL
	 ) UNION (
	 SELECT DISTINCT LEFT(acctCODE,1)
	 FROM accountNames
	 WHERE accountType >= 0
	 AND acctCODE IS NOT NULL
	 )) leaf 
	LEFT JOIN accountNames branch ON leaf.branchName = branch.acctCODE
	WHERE branch.acctCODE IS NULL";
	
  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $result_branchInsert = mysqli_query($MySQL_Union, $mysql_branchInsert) or die(mysqli_error($MySQL_Union));
  
  // *** end Branch check and insertion ***

  $insertGoTo = "accountNamesDETAIL.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 增加新的帳目名稱 - Insert New Account Name</title>
<script type="text/javascript">
<!--
function FuncShowHide(){
      
      if (document.getElementById('accountType').selectedIndex == 3) {
         document.getElementById('hideShow').style.display = "table-row";
      } else {
         document.getElementById('hideShow').style.display = "none";
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
	focusField(document.getElementById("accountName"));

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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> / <a href="accountNamesDETAIL.php">帳目名稱</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->增加新的帳目名稱<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" onsubmit="MM_validateForm('accountName','科目名稱','R');return document.MM_returnValue">
      <table class="tableForm">
        <tr>
          <th class="tableForm">編碼:</th>
          <td colspan="4" class="tableForm"><input name="acctCODE[]" type="text" size="1" maxlength="1" />
            .
            <input name="acctCODE[]" type="text" size="2" maxlength="2" />
            .
            <input name="acctCODE[]" type="text" size="2" maxlength="2" />
            .
            <input name="acctCODE[]" type="text" size="2" maxlength="2" />
            款.項.目 </td>
        </tr>
        <tr>
          <th class="tableForm">科目名稱:</th>
          <td colspan="4" class="tableForm"><input name="accountName" id="accountName" type="text" value="" size="20" maxlength="20" /></td>
        </tr>
        <tr>
          <th class="tableForm">備註:</th>
          <td colspan="4" class="tableForm"><input name="description" type="text" id="description" size="50" maxlength="100" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="3" class="tableForm">科目類別
            <select name="accountType" id="accountType" onchange="FuncShowHide()">
              <option selected="selected" value="">-</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsAccountType['acctTypeID']?>"><?php echo $row_rsAccountType['atName'] . ' - ' . $row_rsAccountType['atNameEn']?></option>
              <?php
} while ($row_rsAccountType = mysqli_fetch_assoc($rsAccountType));
  $rows = mysqli_num_rows($rsAccountType);
  if($rows > 0) {
      mysqli_data_seek($rsAccountType, 0);
	  $row_rsAccountType = mysqli_fetch_assoc($rsAccountType);
  }
?>
            </select></td>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="4" class="tableForm">預設之交叉抵消帳戶
            <select name="xAccount" id="xAccount">
              <option value="">-</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsXAccountNames['accountID']?>"><?php echo $row_rsXAccountNames['accountName'] . ' - ' . $row_rsXAccountNames['atName']?></option>
              <?php
} while ($row_rsXAccountNames = mysqli_fetch_assoc($rsXAccountNames));
  $rows = mysqli_num_rows($rsXAccountNames);
  if($rows > 0) {
      mysqli_data_seek($rsXAccountNames, 0);
	  $row_rsXAccountNames = mysqli_fetch_assoc($rsXAccountNames);
  }
?>
            </select></td>
        </tr>
        <tr id="hideShow" style="display:none">
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm, right">撥前期剩餘:</td>
          <td class="tableForm">$
            <input name="assetBalance" type="text" id="assetBalance" size="19" maxlength="19" /></td>
          <td class="tableForm, right">日期:</td>
          <td class="tableForm"><input name="assetDate" type="text" value="<?php $timezone = +8; echo gmdate('Y', time() + 3600*($timezone+date("I"))) - 1911; echo gmdate("-m-d", time() + 3600*($timezone+date("I"))); ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="4" class="tableForm"><input type="submit" value="儲存新的帳目名稱" /></td>
        </tr>
      </table>
      <br />
      <table class="data">
        <thead class="data">
          <tr class="data">
            <th class="data">帳目組</th>
            <th class="data">備註</th>
            <th class="data">&nbsp;</th>
          </tr>
        </thead>
        <tbody class="data">
          <?php $i=0;
		  	do { 
	  			$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
            <tr class="<?php echo $class; ?>">
              <td class="data"><input name="accGrpID[]" type="hidden" value="<?php echo $row_rsAccountGroups['accGrpID']; ?>" />
                <?php echo $row_rsAccountGroups['accGrpName']; ?></td>
              <td class="data"><?php echo $row_rsAccountGroups['description']; ?></td>
              <td class="data"><input name="selected[<?php echo $i; ?>]" id="selectedID" type="checkbox" value="checkbox" /></td>
            </tr>
            <?php $i++;
				} while ($row_rsAccountGroups = mysqli_fetch_assoc($rsAccountGroups)); ?>
        </tbody>
      </table>
      <input type="hidden" name="MM_insert" value="form1" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsAccountType);

mysqli_free_result($rsXAccountNames);

mysqli_free_result($rsAccountGroups);
?>

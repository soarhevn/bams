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
  foreach ($code as $key => &$value) {
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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $acctCODE = padCode($_POST['acctCODE']);
  $updateSQL = sprintf("UPDATE accountNames 
  SET acctCODE=%s, accountName=%s, accountType=%s, xAccountID=%s, inactive=%s, description=%s
  WHERE accountID=%s",
                       GetSQLValueString($MySQL_Union, $acctCODE, "text"),
					   GetSQLValueString($MySQL_Union, $_POST['accountName'], "text"),
					   GetSQLValueString($MySQL_Union, $_POST['accountType'], "int"),
					   GetSQLValueString($MySQL_Union, $_POST['xAccount'], "int"),
					   GetSQLValueString($MySQL_Union, isset($_POST['inactive']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($MySQL_Union, $_POST['description'], "text"),
					   GetSQLValueString($MySQL_Union, $_POST['accountID'], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));
  
  // *** start of update for Groups selection ***
  
  // $fieldarray which has an entry for each row displayed in the form, and for each row it contains 
  // the names and values for the primary key required for table 'X'.
  
  // $selectarray which has an entry for the checkbox on each of the rows. Note that this will only 
  // contain entries where the checkbox is ON. If it has been checked OFF then the $_POST array will 
  // not contain an entry for that row.
  
  $accGrpID = $_POST['accGrpID'];
  $selectarray = $_POST['selected'];
  $tablename = "accountGroupXref";
  
  	// now rotate the array into something usable for the update
  for ($i = 0; $i < count($accGrpID); $i++) {
  	$fieldarray[$i]['accGrp_ID'] = $accGrpID[$i];
	$fieldarray[$i]['accName_id'] = GetSQLValueString($MySQL_Union, $_POST['accountID'], "int");
  }
  
  // I begin by looping through each row that was displayed in the form and initialise two string vars:
  foreach ($fieldarray as $rownum => $rowdata) {
   $insert = NULL;
   $delete = NULL;

  	// Each row provides me with the names and values for the primary key, so I can move their details 
	// into the two string variables.
  	foreach ($rowdata as $fieldname => $fieldvalue) {
      $insert .= "$fieldname='$fieldvalue',";
      $delete .= "$fieldname='$fieldvalue' AND ";
   	} // foreach
  
  	// When there are no more fields left I can trim the unwanted ',' and ' AND '.
  	$insert = rtrim($insert, ',');
  	$delete = rtrim($delete, ' AND ');
  
  	// Now I examine the contents of the checkbox in $selectarray and construct the SQL query to either 
  	// create the entry if the checkbox is ON or delete the entry if the checkbox is OFF:
  	if (isset($selectarray[$rownum])) {
      $mysql_queryGrp = "INSERT INTO $tablename SET $insert";
   	} else {
      $mysql_queryGrp = "DELETE FROM $tablename WHERE $delete";
   	} // if
   
   // Finally I execute the query and check for errors. Note that I ignore errors concerning duplicate 
   // entries. This is caused by a checkbox being ON originally and not being changed to OFF by the user:
   mysqli_select_db($MySQL_Union, $database_MySQL_Union);
   $result = mysqli_query($MySQL_Union, $mysql_queryGrp);
   if (mysqli_errno() <> 0) {
      if (mysqli_errno() == 1062) {
         // ignore duplicate entry
      } else {
       	 echo mysqli_error($MySQL_Union)();
		 echo $mysql_queryGrp . " select array is " . $selectarray[$rownum];
      } // if
   } // if
  } // foreach
    
  // *** end Groups selection update ***
  
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
  
  $updateGoTo = "accountNamesDETAIL.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}

$colname_rsaccountNames = "1";
if (isset($_GET['recordID'])) {
  $colname_rsaccountNames = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsaccountNames = sprintf("SELECT * FROM accountNames WHERE accountID = %s", $colname_rsaccountNames);
$rsaccountNames = mysqli_query($MySQL_Union, $query_rsaccountNames) or die(mysqli_error($MySQL_Union));
$row_rsaccountNames = mysqli_fetch_assoc($rsaccountNames);
$totalRows_rsaccountNames = mysqli_num_rows($rsaccountNames);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountType = "SELECT * FROM accountType";
$rsAccountType = mysqli_query($MySQL_Union, $query_rsAccountType) or die(mysqli_error($MySQL_Union));
$row_rsAccountType = mysqli_fetch_assoc($rsAccountType);
$totalRows_rsAccountType = mysqli_num_rows($rsAccountType);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsXAccountNames = "SELECT accountID, accountName, atName  FROM accountNames  LEFT JOIN accountType ON accountNames.accountType = accountType.acctTypeID WHERE inactive < 1";
$rsXAccountNames = mysqli_query($MySQL_Union, $query_rsXAccountNames) or die(mysqli_error($MySQL_Union));
$row_rsXAccountNames = mysqli_fetch_assoc($rsXAccountNames);
$totalRows_rsXAccountNames = mysqli_num_rows($rsXAccountNames);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountGroups = sprintf("SELECT accountNames.accountID, accountGroups.accGrpID, accountGroups.accGrpName, accountGroups.description, CASE WHEN accountGroupXref.accGrp_id IS NULL THEN '0' ELSE '1' END AS selected FROM accountNames CROSS JOIN accountGroups LEFT JOIN accountGroupXref ON (accountGroupXref.accName_id = accountNames.accountID     AND accountGroupXref.accGrp_id = accountGroups.accGrpID) WHERE accountGroups.inactive = 0 AND accountNames.accountID = %s ORDER BY accountGroups.accGrpName ASC", $colname_rsaccountNames);
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
<title>音樂工會: 編輯更改帳戶名稱 - Edit Account Name</title>
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> / <a href="accountNamesDETAIL.php">帳目名稱</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->編輯更改帳戶名稱<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" onsubmit="MM_validateForm('accountName','科目名稱','R');return document.MM_returnValue">
      <table class="tableForm">
        <tr>
          <th class="tableForm">編碼:</th>
          <td colspan="2" class="tableForm"><?php $code = explode(".", $row_rsaccountNames['acctCODE']); ?>
            <input name="acctCODE[]" type="text" value="<?php echo $code[0]; ?>" size="1" maxlength="1" />
            .
            <input name="acctCODE[]" type="text" value="<?php echo $code[1]; ?>" size="2" maxlength="2" />
            .
            <input name="acctCODE[]" type="text" value="<?php echo $code[2]; ?>" size="2" maxlength="2" />
            .
            <input name="acctCODE[]" type="text" value="<?php echo $code[3]; ?>" size="2" maxlength="2" />
            款.項.目 </td>
        </tr>
        <tr>
          <th class="tableForm">科目名稱:</th>
          <td colspan="2" class="tableForm"><input name="accountName" type="text" value="<?php echo $row_rsaccountNames['accountName']; ?>" size="20" maxlength="20" /></td>
        </tr>
        <tr>
          <th class="tableForm">備註:</th>
          <td colspan="2" class="tableForm"><input name="description" type="text" id="description" value="<?php echo $row_rsaccountNames['description']; ?>" size="50" maxlength="100" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="2" class="tableForm">科目類別
            <select name="accountType" id="accountType">
              <option value="" <?php if (!(strcmp("", $row_rsaccountNames['accountType']))) {echo "SELECTED";} ?>>-</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsAccountType['acctTypeID']?>"<?php if (!(strcmp($row_rsAccountType['acctTypeID'], $row_rsaccountNames['accountType']))) {echo "SELECTED";} ?>><?php echo $row_rsAccountType['atName'] . ' - ' . $row_rsAccountType['atNameEn']?></option>
              <?php
} while ($row_rsAccountType = mysqli_fetch_assoc($rsAccountType));
  $rows = mysqli_num_rows($rsAccountType);
  if($rows > 0) {
      mysqli_data_seek($rsAccountType, 0);
	  $row_rsAccountType = mysqli_fetch_assoc($rsAccountType);
  }
?>
            </select></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td colspan="2" class="tableForm">預設之交叉抵消帳戶
            <select name="xAccount" id="xAccount">
              <option value="" <?php if (!(strcmp("", $row_rsaccountNames['xAccountID']))) {echo "SELECTED";} ?>>-</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsXAccountNames['accountID']?>"<?php if (!(strcmp($row_rsXAccountNames['accountID'], $row_rsaccountNames['xAccountID']))) {echo "SELECTED";} ?>><?php echo $row_rsXAccountNames['accountName'] . ' - ' . $row_rsXAccountNames['atName']?></option>
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
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input type="submit" value="更改確定" /></td>
          <td class="tableForm">帳目終止
            <input <?php if (!(strcmp($row_rsaccountNames['inactive'],1))) {echo "checked";} ?> name="inactive" type="checkbox" id="inactive" value="checkbox" /></td>
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
            <td class="data"><input <?php if (!(strcmp($row_rsAccountGroups['selected'],1))) {echo "checked";} ?> name="selected[<?php echo $i; ?>]" id="selectedID" type="checkbox" value="checkbox" /></td>
          </tr>
          <?php $i++;
				} while ($row_rsAccountGroups = mysqli_fetch_assoc($rsAccountGroups)); ?>
        </tbody>
      </table>
      <input type="hidden" name="MM_update" value="form1" />
      <input type="hidden" name="accountID" value="<?php echo $row_rsaccountNames['accountID']; ?>" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!--end navbar -->
<br />
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($rsaccountNames);

mysqli_free_result($rsAccountType);

mysqli_free_result($rsXAccountNames);

mysqli_free_result($rsAccountGroups);
?>

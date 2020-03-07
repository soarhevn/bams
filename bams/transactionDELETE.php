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

if ((isset($_POST['hiddenID'])) && ($_POST['hiddenID'] != "")) {
  $deleteSQL = sprintf("DELETE t, tM
		FROM transactions t, transactionsMaster tM
		WHERE t.idMaster = %1\$s
		AND tM.idMaster = %1\$s",
                       GetSQLValueString($MySQL_Union, $_POST['hiddenID'], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $deleteSQL) or die(mysqli_error($MySQL_Union));

  switch ($_GET['refer']) {
  case "memberDETAIL":
  	$deleteGoTo = "memberDETAIL.php?";
	$deleteGoTo .= strstr($_SERVER['QUERY_STRING'], 'idNumber');
	break;
  default:
  	$deleteGoTo = "transactionMASTER.php?";
	$deleteGoTo .= strstr($_SERVER['QUERY_STRING'], 'accountID');
  }
  header(sprintf("Location: %s", $deleteGoTo));
}

$colname_rsAccounts = "1";
if (isset($_GET['recordID'])) {
  $colname_rsAccounts = (get_magic_quotes_gpc()) ? $_GET['recordID'] : addslashes($_GET['recordID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsTransaction = sprintf("SELECT idMaster, TRIM(LEADING '0' FROM (DATE_SUB(transDate, INTERVAL 1911 YEAR))) AS transDate, accountID1, CONCAT(aN1.accountName, ' - ', aT1.atName) AS accountName1, IF(debit1 > 0, FORMAT(debit1, 0), NULL) AS debit1, IF(credit1 > 0, FORMAT(credit1, 0), NULL) AS credit1, accountID2, CONCAT(aN2.accountName, ' - ', aT2.atName) AS accountName2, IF(debit2 > 0, FORMAT(debit2, 0), NULL) AS debit2, IF(credit2 > 0, FORMAT(credit2, 0), NULL) AS credit2, notes, changeDate, marker
FROM 
( /* get transactions out and match to transMaster for single line with both debit and credit */
SELECT DISTINCT idMaster, transDate, changeDate, memIdNum, notes, t1.id AS id1, 
t1.accountID AS accountID1, t1.debit AS debit1, t1.credit AS credit1, t2.id AS id2, 
t2.accountID AS accountID2,
t2.debit AS debit2, t2.credit AS credit2, marker
FROM transactionsMaster
LEFT JOIN transactions t1 USING (idMaster)
LEFT JOIN transactions t2 USING (idMaster)
LEFT JOIN accountGroupXref gX ON (t1.accountID = gX.accName_id OR t2.accountID = gX.accName_id)
WHERE t1.accountID != t2.accountID
AND t1.debit > 0
AND idMaster = '%s' 
) trans 
  /* end tranactions section */
  /* start joins for account names + account types */
LEFT JOIN accountNames aN1 ON accountID1 = aN1.accountID
LEFT JOIN accountNames aN2 ON accountID2 = aN2.accountID
LEFT JOIN accountType aT1 ON aN1.accountType = aT1.acctTypeID
LEFT JOIN accountType aT2 ON aN2.accountType = aT2.acctTypeID
ORDER BY transdate, idMaster ASC", 
		$colname_rsAccounts);
$rsTransaction = mysqli_query($MySQL_Union, $query_rsTransaction) or die(mysqli_error($MySQL_Union));
$row_rsTransaction = mysqli_fetch_assoc($rsTransaction);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 刪除帳目 - Delete Transaction</title>
<script type="text/javascript">
<!-- Begin
function cancelGoToURL() { window.location = "<?php 
	switch ($_GET['refer']) {
    case "memberDETAIL":
  		$cancelGoTo = "memberDETAIL.php?";
		$cancelGoTo .= strstr($_SERVER['QUERY_STRING'], 'idNumber');
		break;
    case "assetMASTER":
  	 	$cancelGoTo = "assetMASTER.php?";
	 	break;
    default:
  		$cancelGoTo = "transactionMASTER.php?";
  }

	echo $cancelGoTo . strstr($_SERVER['QUERY_STRING'], 'accountID'); ?>"; }
//  End -->
</script>
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出搜索</a> / <a href="transactionMASTER.php">收入與支出結果</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->刪除帳目<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="" method="post" id="form1">
      <input name="hiddenID" type="hidden" id="hiddenID" value="<?php echo $row_rsTransaction['idMaster']; ?>" />
      <p class="deleteText">確定要刪除嗎?</p>
      <table class="data">
        <thead class="data">
          <tr class="data">
            <th class="data">日期</th>
            <th class="data">科目</th>
            <th class="data">借 - Debit</th>
            <th class="data">貸 - Credit</th>
            <th class="data">摘要</th>
            <th class="data">記錄更改日期</th>
          </tr>
        </thead>
        <tbody class="data">
          <?php do {  ?>
          <tr class="data">
            <td rowspan="2" class="data"><a href="<?php 
				  	if ($row_rsTransaction['marker'] > 0) {
						echo "incomeEDIT.php?refer=transactionMASTER&incomeID=" . $row_rsTransaction['marker'];
					} else {
				  		echo "transactionEDIT.php?recordID=" . $row_rsTransaction['idMaster'];
					}
					echo "&" . $_SERVER['QUERY_STRING']; ?>"> <?php echo $row_rsTransaction['transDate']; ?></a> </td>
            <td class="data"><?php echo $row_rsTransaction['accountName1']; ?></td>
            <td class="data right"><?php echo $row_rsTransaction['debit1']; ?></td>
            <td class="data right"><?php echo $row_rsTransaction['credit1']; ?></td>
            <td rowspan="2" class="data"><?php echo $row_rsTransaction['notes']; ?></td>
            <td rowspan="2" class="data"><?php echo $row_rsTransaction['changeDate']; ?></td>
          </tr>
          <tr>
            <td class="data"><?php echo $row_rsTransaction['accountName2']; ?></td>
            <td class="data right"><?php echo $row_rsTransaction['debit2']; ?></td>
            <td class="data right"><?php echo $row_rsTransaction['credit2']; ?></td>
          </tr>
          <tr>
            <td colspan="7"></td>
          </tr>
          <?php } while ($row_rsTransaction = mysqli_fetch_assoc($rsTransaction)); ?>
        </tbody>
      </table>
      <input type="submit" name="Submit" value="刪除帳目" />
      <input name="cancel" type="button" id="cancel" onclick="cancelGoToURL()" value="取消更改" />
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>

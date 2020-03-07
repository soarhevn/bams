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
/* set the allowed order by columns */
$default_sort = 'acctCODE';
$allowed_order = array ('acctCOD','accountName','atName','xAccountName', 'inactive');

/* if order is not set, or it is not in the allowed
 * list, then set it to a default value. Otherwise, 
 * set it to what was passed in. */
if (!isset ($_GET['order']) || 
    !in_array ($_GET['order'], $allowed_order)) {
    $order = $default_sort;
} else {
    $order = $_GET['order'];
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsaccountNames = "
SELECT aN.accountID, aN.acctCODE, aN.accountName, aT.atName, 
  xaN.accountName AS xAccountName, aN.inactive, aN.description, aN.accountType
FROM accountNames aN 
INNER JOIN accountType aT ON aN.accountType = aT.acctTypeID
LEFT JOIN accountNames xaN ON aN.xAccountID = xaN.accountID
WHERE aN.accountID > 0
ORDER BY $order";
$rsaccountNames = mysqli_query($MySQL_Union, $query_rsaccountNames) or die(mysqli_error($MySQL_Union));
$row_rsaccountNames = mysqli_fetch_assoc($rsaccountNames);
$totalRows_rsaccountNames = mysqli_num_rows($rsaccountNames);

// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLoc = "__report=bamsreports/";
// Chart of Accounts report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$reportName = "Chart_of_Accounts.rptdesign";
$acctChartURL = $baseURL . $birtOpMode . $reportLoc;
$acctChartURL .= urlencode($reportName);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 帳目名稱 - Account Names</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script src="assets/javascript/jquery-2.1.0.min.js"></script>
<script src="assets/javascript/underscore-1.5.2.min.js"></script>
<script src="assets/src/jquery.floatThead.min.js"></script>
<script type="text/javascript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->帳目名稱<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <table class="data" id="accounts">
      <thead class="data">
        <tr class="data">
          <th class="data"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=acctCODE">編碼</a></th>
          <!-- accountName -->
          <th class="data"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=accountName">科目名稱</a></th>
          <!-- accountType / atName -->
          <th class="data"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=atName">科目類別</a></th>
          <!-- accGrpName -->
          <!-- xAccountName -->
          <th class="data"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=xAccountName">Default X Account</a> </th>
          <!-- inactive -->
          <th class="data"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=inactive">帳目終止</a></th>
          <th class="data">備註</th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
	  			$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
        <tr class="<?php echo $class; ?>">
          <td class="data"><?php echo $row_rsaccountNames['acctCODE']; ?></td>
          <td class="data"><a href="<?php 
			$gotoID = $row_rsaccountNames['accountID'];
			if ($row_rsaccountNames['accountType'] == -1) {
				echo "accountNamesBranchEDIT.php?recordID=" . $gotoID;
			} else {
				echo "accountNamesEDIT.php?recordID=" . $gotoID;
			}
			?>" title="Edit Account Name"><?php echo $row_rsaccountNames['accountName']; ?></a> </td>
          <td class="data center"><?php echo $row_rsaccountNames['atName']; ?></td>
          <td class="data"><?php echo $row_rsaccountNames['xAccountName']; ?></td>
          <td class="data"><?php if($row_rsaccountNames['inactive']): ?>
            帳目終止
            <?php endif; ?></td>
          <td class="data"><?php echo $row_rsaccountNames['description']; ?></td>
        </tr>
        <?php } while ($row_rsaccountNames = mysqli_fetch_assoc($rsaccountNames)); ?>
      </tbody>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
  <div id="sectionLinks">
    <ul>
      <li><a href="#" onclick="MM_openBrWindow('<?php echo $acctChartURL; ?>','會計科目圖表列印','scrollbars=yes,resizable=yes')">會計科目圖表列印</a></li>
      <li><a href="accountNamesINSERT.php">增加新的帳目名稱</a></li>
      <li><a href="accountGroupsDETAIL.php">帳目組</a></li>
    </ul>
  </div>
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
<script type="text/javascript">
$('#accounts').floatThead();
</script>
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($rsaccountNames);
?>

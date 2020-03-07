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
/* $currentPage = $_SERVER["PHP_SELF"];

$maxRows_rsAssetTransactions = 20;
$pageNum_rsAssetTransactions = 0;
if (isset($_GET['pageNum_rsAssetTransactions'])) {
  $pageNum_rsAssetTransactions = $_GET['pageNum_rsAssetTransactions'];
}
$startRow_rsAssetTransactions = $pageNum_rsAssetTransactions * $maxRows_rsAssetTransactions;
*/
 
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

$colname_rsAccountName = "-1";
if (isset($_GET['accountID'])) {
  $colname_rsAccountName = (get_magic_quotes_gpc()) ? $_GET['accountID'] : addslashes($_GET['accountID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAccountName = sprintf("SELECT accountID, accountName FROM accountNames WHERE accountID = %s", GetSQLValueString($MySQL_Union, $colname_rsAccountName, "int"));
$rsAccountName = mysqli_query($MySQL_Union, $query_rsAccountName) or die(mysqli_error($MySQL_Union));
$row_rsAccountName = mysqli_fetch_assoc($rsAccountName);
$totalRows_rsAccountName = mysqli_num_rows($rsAccountName);

$colname_rsAssetBalance = "-1";
if (isset($_GET['accountID'])) {
  $colname_rsAssetBalance = (get_magic_quotes_gpc()) ? $_GET['accountID'] : addslashes($_GET['accountID']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAssetBalance = sprintf("SELECT FORMAT(IFNULL(SUM(debit),0) - IFNULL(SUM(credit),0),0) AS balance FROM transactions WHERE accountID = %s", GetSQLValueString($MySQL_Union, $colname_rsAssetBalance, "int"));
$rsAssetBalance = mysqli_query($MySQL_Union, $query_rsAssetBalance) or die(mysqli_error($MySQL_Union));
$row_rsAssetBalance = mysqli_fetch_assoc($rsAssetBalance);
$totalRows_rsAssetBalance = mysqli_num_rows($rsAssetBalance);

$col_accountID_rsAssetTransactions = "-1";
if (isset($_GET['accountID'])) {
  $col_accountID_rsAssetTransactions = (get_magic_quotes_gpc()) ? $_GET['accountID'] : addslashes($_GET['accountID']);
}

$col_dateFrom_rsAssetTransactions = "90-01-01";
if (isset($_GET['dateFrom'])) {
  $col_dateFrom_rsAssetTransactions = (get_magic_quotes_gpc()) ? $_GET['dateFrom'] : addslashes($_GET['dateFrom']);
}

$col_dateTo_rsAssetTransactions = "200-12-31";
if (isset($_GET['dateTo'])) {
  $col_dateTo_rsAssetTransactions = (get_magic_quotes_gpc()) ? $_GET['dateTo'] : addslashes($_GET['dateTo']);
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsSetRunTotal = "SET @runTotal = 0";
$rsSetRunTotal = mysqli_query($MySQL_Union, $query_rsSetRunTotal) or die(mysqli_error($MySQL_Union));

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsAssetTransactions = sprintf("
SELECT idMaster, TRIM(LEADING '0' FROM (DATE_SUB(transDate, INTERVAL 1911 YEAR))) AS transDate, 
IF(trans.debit > 0, FORMAT(trans.debit, 0), NULL) AS debit, 
IF(trans.credit > 0, FORMAT(trans.credit, 0), NULL) AS credit, 
FORMAT(@runTotal := @runTotal + linetotal, 0) AS runTotal,
accountID2, IF(idMaster = -1, '結餘', CONCAT(aN2.accountName, ' - ', aT2.atName)) AS accountName2, 
notes, changeDate, marker, dailyTransCount, code
FROM 
( /* get transactions out and match to transMaster for single line with both debit and credit */

( /* get total of debits and credit for previous trans */
SELECT -1 AS idMaster, NULL AS transDate, NULL AS medIdNum, 
CONCAT('結餘 到', SUBSTRING((%2\$s - INTERVAL 1 DAY),3)) AS notes, 
IFNULL(SUM(debit),0) AS debit, IFNULL(SUM(credit),0) AS credit, 
IFNULL(SUM(debit),0) - IFNULL(SUM(credit),0) AS lineTotal, 
NULL AS id1, NULL AS id2, NULL AS accountID2, NULL AS marker, 
DATE_SUB(REPLACE(%2\$s, (SUBSTRING_INDEX(%2\$s, '-', 1)), ((SUBSTRING_INDEX(%2\$s, '-', 1)) + 1911)), INTERVAL 1 DAY) AS changeDate, NULL as dailyTransCount, NULL AS code
FROM transactionsMaster
LEFT JOIN transactions USING (idMaster)
WHERE accountID = %1\$s
AND transdate < REPLACE(%2\$s, (SUBSTRING_INDEX(%2\$s, '-', 1)), ((SUBSTRING_INDEX(%2\$s, '-', 1)) + 1911))
)
UNION
( /* trans + line totals for searched trans */
SELECT DISTINCT idMaster, transDate, memIdNum, notes, 
t1.debit, t1.credit, ( IFNULL(t1.debit,0) - IFNULL(t1.credit,0) ) AS lineTotal,
t1.id AS id1, t2.id AS id2, t2.accountID AS accountID2, marker, changeDate, NULL AS dailyTransCount, code
FROM transactionsMaster
LEFT JOIN transactions t1 USING (idMaster)
LEFT JOIN transactions t2 USING (idMaster)
WHERE t1.accountID != t2.accountID
AND t1.accountID = %1\$s
AND transDate BETWEEN REPLACE(%2\$s, (SUBSTRING_INDEX(%2\$s, '-', 1)), ((SUBSTRING_INDEX(%2\$s, '-', 1)) + 1911)) AND REPLACE(%3\$s, (SUBSTRING_INDEX(%3\$s, '-', 1)), ((SUBSTRING_INDEX(%3\$s, '-', 1)) + 1911))
)
UNION
( /* get daily subtotals */
SELECT DISTINCT NULL AS idMaster, transDate, NULL AS memIdNum, NULL AS notes,
IFNULL(SUM(t1.debit),0) AS debitDailyTotal, IFNULL(SUM(t1.credit),0) AS creditDailyTotal, 
0 AS dailyTotal,
NULL AS id1, NULL AS id2, NULL AS accountID2, NULL AS marker, NULL AS changeDate, 
COUNT(idMaster) AS dailyTransCount, NULL AS code
FROM transactionsMaster
LEFT JOIN transactions t1 USING (idMaster)
LEFT JOIN transactions t2 USING (idMaster)
WHERE t1.accountID != t2.accountID
AND t1.accountID = %1\$s
AND transDate BETWEEN REPLACE(%2\$s, (SUBSTRING_INDEX(%2\$s, '-', 1)), ((SUBSTRING_INDEX(%2\$s, '-', 1)) + 1911)) AND REPLACE(%3\$s, (SUBSTRING_INDEX(%3\$s, '-', 1)), ((SUBSTRING_INDEX(%3\$s, '-', 1)) + 1911))
GROUP BY transDate
)
) trans 
  /* end tranactions section */
  /* start joins for account names + account types */
LEFT JOIN accountNames aN2 ON accountID2 = aN2.accountID
LEFT JOIN accountType aT2 ON aN2.accountType = aT2.acctTypeID 
ORDER BY BINARY transDate, dailyTransCount, idMaster ASC", 
			GetSQLValueString($MySQL_Union, $col_accountID_rsAssetTransactions, "int"),	// 1
			GetSQLValueString($MySQL_Union, $col_dateFrom_rsAssetTransactions, "date"),	// 2
			GetSQLValueString($MySQL_Union, $col_dateTo_rsAssetTransactions, "date"));	// 3
/*$query_limit_rsAssetTransactions = sprintf("%s LIMIT %d, %d", $query_rsAssetTransactions, $startRow_rsAssetTransactions, $maxRows_rsAssetTransactions);
*/
$rsAssetTransactions = mysqli_query($MySQL_Union, $query_rsAssetTransactions) or die(mysqli_error($MySQL_Union));
$row_rsAssetTransactions = mysqli_fetch_assoc($rsAssetTransactions);

/*if (isset($_GET['totalRows_rsAssetTransactions'])) {
  $totalRows_rsAssetTransactions = $_GET['totalRows_rsAssetTransactions'];
} else {
  $all_rsAssetTransactions = mysqli_query($MySQL_Union, $query_rsAssetTransactions);
  $totalRows_rsAssetTransactions = mysqli_num_rows($all_rsAssetTransactions);
}
$totalPages_rsAssetTransactions = ceil($totalRows_rsAssetTransactions/$maxRows_rsAssetTransactions)-1;

$queryString_rsAssetTransactions = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_rsAssetTransactions") == false && 
        stristr($param, "totalRows_rsAssetTransactions") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_rsAssetTransactions = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_rsAssetTransactions = sprintf("&totalRows_rsAssetTransactions=%d%s", $totalRows_rsAssetTransactions, $queryString_rsAssetTransactions);
*/

// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLocation = "__report=bamsreports/";
$reportName = "asset_report.rptdesign";
// memberList report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$birtURL = $baseURL . $birtOpMode . $reportLocation;
$birtURL .= urlencode($reportName);
$birtURL .= "&reportDate=" . urlencode($col_dateFrom_rsAssetTransactions);
$birtURL .= "&accountID=" . urlencode($col_accountID_rsAssetTransactions);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會:<?php echo $row_rsAccountName['accountName']; ?>- Asset Account Results</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script src="assets/javascript/jquery-2.1.0.min.js"></script>
<script src="assets/javascript/underscore-1.5.2.min.js"></script>
<script src="assets/src/jquery.floatThead.min.js"></script>
<script type="text/JavaScript">
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
    - 會員搜尋</a> / <a href="assetSEARCH.php">資產與負債</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" --><?php echo $row_rsAccountName['accountName'] . '從' . $_GET['dateFrom'] . '到' . $_GET['dateTo']; ?><!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <h4>當前餘額 $<?php echo $row_rsAssetBalance['balance']; ?></h4>
    <br />
    <table>
      <tr>
        <td><table class="data" id="assetTable">
            <thead class="data">
              <tr class="data">
                <th class="data">日期</th>
                <th class="data">交易記錄</th>
                <th class="data">科目 / 摘要</th>
                <th class="data">借 - Debit</th>
                <th class="data">貸 - Credit</th>
                <th class="data">結餘</th>
                <th class="data">記錄更改日期</th>
              </tr>
            </thead>
            <tbody class="data">
              <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
                <tr class="<?php echo $class; ?>">
                  
                  <td class="data">
                  <?php
                  if (strpos($row_rsAssetTransactions['code'], 'r') !== false) { 
                  echo "<a href=\"incomeRefundEDIT.php?refer=assetMASTER&incomeID=" . 
                  	$row_rsAssetTransactions['marker']; }
                  elseif ($row_rsAssetTransactions['marker'] > 0) {
                  echo "<a href=\"incomeEDIT.php?refer=assetMASTER&incomeID=" . 
                  	$row_rsAssetTransactions['marker']; }
				  else {
				  echo "<a href=\"transactionEDIT.php?refer=assetMASTER&recordID=" . 
				  	$row_rsAssetTransactions['idMaster']; }
				  echo "&" . $_SERVER['QUERY_STRING'] ."\">" . 
				  	$row_rsAssetTransactions['transDate']; ?></a>
				  </td>
                  
                  <td class="data right"><?php 
				  $idMaster = $row_rsAssetTransactions['idMaster'];
				  $dailyTransCount = $row_rsAssetTransactions['dailyTransCount'];
				  if ( $idMaster > 0 ) { echo $idMaster; } ?></td>
                  <td class="data"><?php if ( $dailyTransCount > 0 ) { echo '<strong><em>日小記</em></strong>'; } 
				  	else { echo $row_rsAssetTransactions['accountName2']; } ?><br />
                    <?php echo $row_rsAssetTransactions['notes']; ?></td>
                  <td class="data right"><?php if ( $dailyTransCount > 0 ) 
				  	{ echo '<strong><em>' . $row_rsAssetTransactions['debit'] . '</em></strong>'; }
					else { echo $row_rsAssetTransactions['debit']; } ?></td>
                  <td class="data right"><?php if ( $dailyTransCount > 0 ) 
				  	{ echo '<strong><em>' . $row_rsAssetTransactions['credit'] . '</em></strong>'; }
					else { echo $row_rsAssetTransactions['credit']; } ?></td>
                  <td class="data right"><?php if (IS_NULL($dailyTransCount)) 
				  	echo $row_rsAssetTransactions['runTotal']; ?></td>
                  <td class="data"><?php echo $row_rsAssetTransactions['changeDate']; ?></td>
                </tr>
                <tr>
                  <td colspan="7"></td>
                </tr>
                <?php } while ($row_rsAssetTransactions = mysqli_fetch_assoc($rsAssetTransactions)); ?>
            </tbody>
          </table></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
      <!--  <?php /*?>    <tr>
        <td><table class="dataNav">
            <tr>
              <td><?php if ($pageNum_rsAssetTransactions > 0) { // Show if not first page ?>
                  <a href="<?php printf("%s?pageNum_rsAssetTransactions=%d%s", $currentPage, 0, $queryString_rsAssetTransactions); ?>">第一頁</a>
                  <?php } // Show if not first page ?>
              </td>
              <td><?php if ($pageNum_rsAssetTransactions > 0) { // Show if not first page ?>
                  <a href="<?php printf("%s?pageNum_rsAssetTransactions=%d%s", $currentPage, max(0, $pageNum_rsAssetTransactions - 1), $queryString_rsAssetTransactions); ?>">上一頁</a>
                  <?php } // Show if not first page ?>
              </td>
              <td><?php if ($pageNum_rsAssetTransactions < $totalPages_rsAssetTransactions) { // Show if not last page ?>
                  <a href="<?php printf("%s?pageNum_rsAssetTransactions=%d%s", $currentPage, min($totalPages_rsAssetTransactions, $pageNum_rsAssetTransactions + 1), $queryString_rsAssetTransactions); ?>">下一頁</a>
                  <?php } // Show if not last page ?>
              </td>
              <td><?php if ($pageNum_rsAssetTransactions < $totalPages_rsAssetTransactions) { // Show if not last page ?>
                  <a href="<?php printf("%s?pageNum_rsAssetTransactions=%d%s", $currentPage, $totalPages_rsAssetTransactions, $queryString_rsAssetTransactions); ?>">最末頁</a>
                  <?php } // Show if not last page ?>
              </td>
              <td class="right"><?php echo ($startRow_rsAssetTransactions + 1) ?> 到 <?php echo min($startRow_rsAssetTransactions + $maxRows_rsAssetTransactions, $totalRows_rsAssetTransactions) ?> 總筆數<?php echo $totalRows_rsAssetTransactions ?>筆 </td>
            </tr>
          </table></td>
      </tr><?php */?>
-->
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
  <div id="sectionLinks">
    <ul>
      <li><a href="#" onclick="MM_openBrWindow('<?php echo $birtURL; ?>','資產報表','scrollbars=yes,resizable=yes')"><?php 
	  $monthR = explode("-", $col_dateFrom_rsAssetTransactions);
	  $monthR = ltrim($monthR[1], "0");
	  echo $monthR . "月" . $row_rsAccountName['accountName']; ?>報表</a></li>
      <li><a href="reportCHOOSER.php">報表</a></li>
    </ul>
  </div>
<!--  <div class="relatedLinks">
    <h3>Related Link Category</h3>
    <ul>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>Related Link Category</h3>
    <ul>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
      <li><a href="#">Related Link</a></li>
    </ul>
  </div>-->
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
<script type="text/javascript">
$('#assetTable').floatThead();
</script>
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsAccountName);

mysqli_free_result($rsAssetBalance);

mysqli_free_result($rsAssetTransactions);
?>

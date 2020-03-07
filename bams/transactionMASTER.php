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
$currentPage = $_SERVER["PHP_SELF"];

$maxRows_rsTransactions = 20;
$pageNum_rsTransactions = 0;
if (isset($_GET['pageNum_rsTransactions'])) {
  $pageNum_rsTransactions = $_GET['pageNum_rsTransactions'];
}
$startRow_rsTransactions = $pageNum_rsTransactions * $maxRows_rsTransactions;

$varDateFrom_rsTransactions = "%";
if (isset($_GET['dateFrom']) && $_GET['dateFrom']) {
  $varDateFrom_rsTransactions = (get_magic_quotes_gpc()) ? $_GET['dateFrom'] : addslashes($_GET['dateFrom']);
}
$varDateTo_rsTransactions = "%";
if (isset($_GET['dateTo']) && $_GET['dateTo']) {
  $varDateTo_rsTransactions = (get_magic_quotes_gpc()) ? $_GET['dateTo'] : addslashes($_GET['dateTo']);
}
$varAccountID_rsTransactions = "";
if ($_GET['accountID'][0] != '%') { 
   $accountIDs = $_GET["accountID"];
   $varAccountID_rsTransactions = "AND ( t1.accountID IN (" . implode(',',$accountIDs) . ") OR t2.accountID IN (" . implode(',',$accountIDs) . ") ) ";
 }
$varTextFree_rsTransactions = "%";
if (isset($_GET['textFree'])) {
  $varTextFree_rsTransactions = (get_magic_quotes_gpc()) ? $_GET['textFree'] : addslashes($_GET['textFree']);
}
$varAccountGroupID_rsTranactions = "";
if ($_GET['accGrpID'][0] != '%') { 
   $accGrpID = $_GET["accGrpID"];
   $varAccountGroupID_rsTranactions = 'AND gX.accGrp_id IN (' . implode(',',$accGrpID) . ') ';
 }
 
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsTransactions = sprintf("
SELECT idMaster, TRIM(LEADING '0' FROM (DATE_SUB(transDate, INTERVAL 1911 YEAR))) AS transDate, accountID1, CONCAT(aN1.accountName, ' - ', aT1.atName) AS accountName1, IF(debit1 > 0, FORMAT(debit1, 0), NULL) AS debit1, IF(credit1 > 0, FORMAT(credit1, 0), NULL) AS credit1, accountID2, CONCAT(aN2.accountName, ' - ', aT2.atName) AS accountName2, IF(debit2 > 0, FORMAT(debit2, 0), NULL) AS debit2, IF(credit2 > 0, FORMAT(credit2, 0), NULL) AS credit2, notes, changeDate, marker, memIdNum, code
FROM 
( /* get transactions out and match to transMaster for single line with both debit and credit */
SELECT DISTINCT idMaster, transDate, changeDate, memIdNum, notes, t1.id AS id1, 
t1.accountID AS accountID1, t1.debit AS debit1, t1.credit AS credit1, t2.id AS id2, 
t2.accountID AS accountID2,
t2.debit AS debit2, t2.credit AS credit2, marker, code
FROM transactionsMaster
LEFT JOIN transactions t1 USING (idMaster)
LEFT JOIN transactions t2 USING (idMaster)
LEFT JOIN accountGroupXref gX ON (t1.accountID = gX.accName_id OR t2.accountID = gX.accName_id)
WHERE t1.accountID != t2.accountID
AND t1.debit > 0
AND notes LIKE '%%%2\$s%%' 
%1\$s %5\$s 
AND transDate BETWEEN REPLACE('%3\$s', (SUBSTRING_INDEX('%3\$s', '-', 1)), ((SUBSTRING_INDEX('%3\$s', '-', 1)) + 1911)) 
AND REPLACE('%4\$s', (SUBSTRING_INDEX('%4\$s', '-', 1)), ((SUBSTRING_INDEX('%4\$s', '-', 1)) + 1911)) 
) trans 
  /* end tranactions section */
  /* start joins for account names + account types */
LEFT JOIN accountNames aN1 ON accountID1 = aN1.accountID
LEFT JOIN accountNames aN2 ON accountID2 = aN2.accountID
LEFT JOIN accountType aT1 ON aN1.accountType = aT1.acctTypeID
LEFT JOIN accountType aT2 ON aN2.accountType = aT2.acctTypeID
ORDER BY BINARY transdate, idMaster ASC", 
		$varAccountID_rsTransactions,	// 1
		$varTextFree_rsTransactions,	// 2
		$varDateFrom_rsTransactions,	// 3
		$varDateTo_rsTransactions,		// 4
		$varAccountGroupID_rsTranactions); //5
$query_limit_rsTransactions = sprintf("%s LIMIT %d, %d", $query_rsTransactions, $startRow_rsTransactions, $maxRows_rsTransactions);
$rsTransactions = mysqli_query($MySQL_Union, $query_limit_rsTransactions) or die(mysqli_error($MySQL_Union));
$row_rsTransactions = mysqli_fetch_assoc($rsTransactions);

if (isset($_GET['totalRows_rsTransactions'])) {
  $totalRows_rsTransactions = $_GET['totalRows_rsTransactions'];
} else {
  $all_rsTransactions = mysqli_query($MySQL_Union, $query_rsTransactions);
  $totalRows_rsTransactions = mysqli_num_rows($all_rsTransactions);
}
$totalPages_rsTransactions = ceil($totalRows_rsTransactions/$maxRows_rsTransactions)-1;

$queryString_rsTransactions = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_rsTransactions") == false && 
        stristr($param, "totalRows_rsTransactions") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_rsTransactions = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_rsTransactions = sprintf("&totalRows_rsTransactions=%d%s", $totalRows_rsTransactions, $queryString_rsTransactions);

// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLoc = "__report=bamsreports/";
// expense_revenue_byDate_report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$reportName = "expense_revenue_byDate_report.rptdesign";
$exp_rev_byDateURL = $baseURL . $birtOpMode . $reportLoc;
$exp_rev_byDateURL .= urlencode($reportName);
$exp_rev_byDateURL .= "&accountID=" . urlencode($accountIDs[0]);
$exp_rev_byDateURL .= "&dateFrom=" . urlencode($varDateFrom_rsTransactions);
$exp_rev_byDateURL .= "&dateTo=" . urlencode($varDateTo_rsTransactions);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 收入與支出 - Transaction Listing</title>
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
    - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出搜索</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->收入與支出結果<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <table>
      <tr>
        <td><table class="data" id="trans">
            <thead class="data">
              <tr class="data">
                <th class="data">日期</th>
                <th class="data">交易記錄</th>
                <th class="data">科目</th>
                <th class="data">借 - Debit</th>
                <th class="data">貸 - Credit</th>
                <th class="data">摘要</th>
                <th class="data">記錄更改日期</th>
                <th class="data">&nbsp;</th>
              </tr>
            </thead>
            <tbody class="data">
              <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
                <tr class="<?php echo $class; ?>">
                  <td rowspan="2" class="data">
                  <?php
                  if (strpos($row_rsTransactions['code'], 'r') !== false) { 
                  echo "<a href=\"incomeRefundEDIT.php?refer=transactionMASTER&incomeID="
                  	. $row_rsTransactions['marker']; }
                  elseif ($row_rsTransactions['marker'] > 0) {
                  echo "<a href=\"incomeEDIT.php?refer=transactionMASTER&incomeID=" . 
                  	$row_rsTransactions['marker']; }
				  else {
				  echo "<a href=\"transactionEDIT.php?refer=transactionMASTER&recordID=" . 
				  	$row_rsTransactions['idMaster']; }
				  echo "&" . strstr($_SERVER['QUERY_STRING'],'accountID') ."\">" . 
				  	$row_rsTransactions['transDate']; ?></a>				
				  </td>
                  <td rowspan="2" class="data right"><?php echo $row_rsTransactions['idMaster']; ?></td>
                  <td class="data"><?php echo $row_rsTransactions['accountName1']; ?></td>
                  <td class="data right"><?php echo $row_rsTransactions['debit1']; ?></td>
                  <td class="data right"><?php echo $row_rsTransactions['credit1']; ?></td>
                  <td rowspan="2" class="data"><?php 
					if (empty($row_rsTransactions['memIdNum'])) {
				  		echo $row_rsTransactions['notes'];
					} else {
						echo "<a href=\"memberDETAIL.php?idNumber=" . $row_rsTransactions['memIdNum'] . 
						"\">" . $row_rsTransactions['notes'] . "</a>";
					} ?></td>
                  <td rowspan="2" class="data"><?php echo $row_rsTransactions['changeDate']; ?></td>
                  <td rowspan="2" class="data"><?php if (is_null($row_rsTransactions['marker'])) : ?>
                    <a href="transactionDELETE.php?recordID=<?php echo $row_rsTransactions['idMaster'] . "&" . $_SERVER['QUERY_STRING']; ?>" title="Delete Entry">刪除</a>
                    <?php endif; ?></td>
                </tr>
                <tr>
                  <td class="data"><?php echo $row_rsTransactions['accountName2']; ?></td>
                  <td class="data right"><?php echo $row_rsTransactions['debit2']; ?></td>
                  <td class="data right"><?php echo $row_rsTransactions['credit2']; ?></td>
                </tr>
                <tr>
                  <td colspan="8"></td>
                </tr>
                <?php } while ($row_rsTransactions = mysqli_fetch_assoc($rsTransactions)); ?>
            </tbody>
          </table></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td><table class="dataNav">
            <tr>
              <td><?php if ($pageNum_rsTransactions > 0) { // Show if not first page ?>
                  <a href="<?php printf("%s?pageNum_rsTransactions=%d%s", $currentPage, 0, $queryString_rsTransactions); ?>">第一頁</a>
                  <?php } // Show if not first page ?>
              </td>
              <td><?php if ($pageNum_rsTransactions > 0) { // Show if not first page ?>
                  <a href="<?php printf("%s?pageNum_rsTransactions=%d%s", $currentPage, max(0, $pageNum_rsTransactions - 1), $queryString_rsTransactions); ?>">上一頁</a>
                  <?php } // Show if not first page ?>
              </td>
              <td><?php if ($pageNum_rsTransactions < $totalPages_rsTransactions) { // Show if not last page ?>
                  <a href="<?php printf("%s?pageNum_rsTransactions=%d%s", $currentPage, min($totalPages_rsTransactions, $pageNum_rsTransactions + 1), $queryString_rsTransactions); ?>">下一頁</a>
                  <?php } // Show if not last page ?>
              </td>
              <td><?php if ($pageNum_rsTransactions < $totalPages_rsTransactions) { // Show if not last page ?>
                  <a href="<?php printf("%s?pageNum_rsTransactions=%d%s", $currentPage, $totalPages_rsTransactions, $queryString_rsTransactions); ?>">最末頁</a>
                  <?php } // Show if not last page ?>
              </td>
              <td class="right"><?php echo ($startRow_rsTransactions + 1) ?> 到 <?php echo min($startRow_rsTransactions + $maxRows_rsTransactions, $totalRows_rsTransactions) ?> 總筆數<?php echo $totalRows_rsTransactions ?>筆 </td>
            </tr>
          </table></td>
      </tr>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
  <div id="sectionLinks">
    <ul> <!-- below link for report will not show if user did not pick an account, just don't have the code on the reports for this -->
      <li <?php if (is_null($accountIDs[0])) echo "style=\"display:none\""; ?>> 
      	<a href="#" onclick="MM_openBrWindow('<?php echo $exp_rev_byDateURL; ?>','繳款通知單','scrollbars=yes,resizable=yes')">分類帳冊</a></li>
      <li><a href="transactionDOWNLOAD/transactionDOWNLOAD.php?<?php echo $_SERVER['QUERY_STRING']; ?>">下載成Excel</a></li>    
      <li><a href="transactionINSERT.php?refer=transactionMASTER&<?php 
	  	echo strstr($_SERVER['QUERY_STRING'],'accountID'); ?>">增加筆數</a></li>
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
  </div>
-->
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
<script type="text/javascript">
$('#trans').floatThead();
</script>
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsTransactions);
?>

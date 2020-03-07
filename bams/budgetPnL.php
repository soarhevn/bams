<?php require_once('Connections/MySQL_Union.php'); 
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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form_budgetData")) {
  $array_budgetID = $_POST['accountID'];
  foreach ($array_budgetID as $key => $value) {
	$updateSQL = sprintf("INSERT INTO budget 
  		SET accountID=%s, budgetYear=%s, budgetAmount=IFNULL(%s,0), budgetNotes=IFNULL(%s,''), budgetID=%s
		ON DUPLICATE KEY UPDATE accountID=VALUES(accountID), budgetYear=VALUES(budgetYear),
		budgetAmount=VALUES(budgetAmount), budgetNotes=VALUES(budgetNotes)",
                       GetSQLValueString($MySQL_Union, $_POST['accountID'][$key], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['budgetYear'], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['budgetAmount'][$key], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['budgetNotes'][$key], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['budgetID'][$key], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));
  }
  unset($key, $value);
}

$varBudgetYearSelect = getdate();
$varBudgetYearSelect = $varBudgetYearSelect['year'];
if (isset($_GET['budgetYear']) && $_GET['budgetYear']) {
  $varBudgetYearSelect = (get_magic_quotes_gpc()) ? $_GET['budgetYear'] : addslashes($_GET['budgetYear']);
}
$varBudgetEntitySelect = "A";
if (isset($_GET['budgetEntity']) && $_GET['budgetEntity']) {
  $varBudgetEntitySelect = (get_magic_quotes_gpc()) ? $_GET['budgetEntity'] : addslashes($_GET['budgetEntity']);
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsYearsPossible = "
(SELECT DISTINCT YEAR(transDate) AS transDateYear FROM transactionsMaster)
UNION DISTINCT
(SELECT '2010')
ORDER BY transDateYear DESC";
$rsYearsPossible = mysqli_query($MySQL_Union, $query_rsYearsPossible) or die(mysqli_error($MySQL_Union));
$row_rsYearsPossible = mysqli_fetch_assoc($rsYearsPossible);
$totalRows_rsYearsPossible = mysqli_num_rows($rsYearsPossible);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsBudgetEntity = "SELECT acctCODE, accountName FROM accountNames WHERE CHAR_LENGTH(acctCODE) = '1' ORDER BY acctCODE ASC";
$rsBudgetEntity = mysqli_query($MySQL_Union, $query_rsBudgetEntity) or die(mysqli_error($MySQL_Union));
$row_rsBudgetEntity = mysqli_fetch_assoc($rsBudgetEntity);
$totalRows_rsBudgetEntity = mysqli_num_rows($rsBudgetEntity);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_budgetData = sprintf("
SELECT mergeDex, accountType, agg1, agg2, 
  NULLIF(NULLIF(acctCODE, agg1), agg2) AS acctCODE,
  accountID, 
  CASE
    WHEN accountType IS NULL THEN '合計'
    WHEN acctCODE IS NULL THEN accountType.atName
    ELSE accountName
    END AS accountName, 
  ROUND(yearTotal) AS actual, ROUND(yearTotal - budgetThis) AS varianceD, 
  ROUND(((yearTotal - budgetThis) / budgetThis * 100), 2) AS varianceP,
  budgetIDthis, budgetThis, budgetNotesThis, budgetIDnext, budgetNext, budgetNotesNext
FROM ((
SELECT acctCODE AS mergeDex, accountType, NULL AS agg1, NULL AS agg2, acctCODE, accountID, accountName,
  budgetIDthis, budgetThis, budgetNotesNext, budgetIDnext, budgetNext, budgetNotesThis
FROM accountNames
LEFT JOIN(
    SELECT accountID, budgetID AS budgetIDthis, budgetAmount AS budgetThis, budgetNotes AS budgetNotesThis
    FROM budget 
    WHERE budgetYear = %1\$s    /* User input year */
  ) thisY USING (accountID)
LEFT JOIN (
    SELECT accountID, budgetID AS budgetIDnext, budgetAmount AS budgetNext, budgetNotes AS budgetNotesNext
    FROM budget 
    WHERE budgetYear = %1\$s + 1    /* User input year */
  ) nextY USING (accountID)
WHERE LEFT(acctCODE, 1) = %2\$s     /* User choice to select which account set */
AND accountType IN (0,1)            /* Only want revenue and expense */
) UNION (
SELECT COALESCE(thisYear.agg2, thisYear.agg1, thisYear.accountType, -10) AS mergeDex,
  thisYear.accountType, IF(thisYear.agg2 IS NULL, thisYear.agg1, NULL) AS agg1, thisYear.agg2, accountNames.acctCODE, 
  accountNames.accountID, accountName, NULL AS budgetIDthis, budgetThis, 
  NULL as budgetNotesThis, NULL AS budgetIDnext, budgetNext, NULL as budgetNotesNext
FROM (
  SELECT accountType, LEFT(acctCODE,4) AS agg1, LEFT(acctCODE,7) AS agg2, SUM(budgetAmount) AS budgetThis
  FROM accountNames
  LEFT JOIN budget USING (accountID)
  WHERE ( budgetYear IS NULL OR budgetYear = %1\$s )   /* User input year */
  AND LEFT(acctCODE, 1) = %2\$s                        /* User choice to select which account set */
  AND accountType IN (0,1)                             /* Only want revenue and expense */
  GROUP BY accountType DESC, agg1, agg2 WITH ROLLUP
) thisYear
LEFT JOIN (
  SELECT accountType, LEFT(acctCODE,4) AS agg1, LEFT(acctCODE,7) AS agg2, SUM(budgetAmount) AS budgetNext
  FROM accountNames
  LEFT JOIN budget USING (accountID)
  WHERE ( budgetYear IS NULL OR budgetYear = %1\$s + 1)   /* User input year */
  AND LEFT(acctCODE, 1) = %2\$s                           /* User choice to select which account set */
  AND accountType IN (0,1)                                /* Only want revenue and expense */
  GROUP BY accountType DESC, agg1, agg2 WITH ROLLUP
) nextYear ON COALESCE(thisYear.agg2, thisYear.agg1, thisYear.accountType, -10) = 
              COALESCE(nextYear.agg2, nextYear.agg1, nextYear.accountType, -10)
LEFT JOIN accountNames ON accountNames.acctCODE = COALESCE(thisYear.agg2, thisYear.agg1)
)) budgetPivot
LEFT JOIN (
SELECT COALESCE(actualsROLLUP.acctCODE, agg2, agg1, actualsROLLUP.accountType, -10) AS mergeDex, yearTotal
FROM (
  SELECT accountType, LEFT(acctCODE,4) AS agg1, LEFT(acctCODE,7) AS agg2, acctCODE, accountID,
    IFNULL(SUM(credit),0) - IFNULL(SUM(debit),0) AS yearTotal
  FROM transactionsMaster
  LEFT JOIN transactions USING (idMaster)
  LEFT JOIN accountNames USING (accountID)
  WHERE YEAR(transDate) = %1\$s     /* User input year */
  AND LEFT(acctCODE, 1) = %2\$s     /* User choice to select which account set */
  AND accountType IN (0,1)          /* Only want rev and exp */
  GROUP BY accountType DESC, agg1, agg2, acctCODE WITH ROLLUP
) actualsROLLUP ) actuals USING (mergeDex)
LEFT JOIN accountType ON acctTypeID = mergeDex
ORDER BY accountType, mergeDex
", 
	GetSQLValueString($MySQL_Union, $varBudgetYearSelect, "int"),		// 1
	GetSQLValueString($MySQL_Union, $varBudgetEntitySelect, "text")	// 2
	);
$rs_budgetData = mysqli_query($MySQL_Union, $query_rs_budgetData) or die(mysqli_error($MySQL_Union));
$row_rs_budgetData = mysqli_fetch_assoc($rs_budgetData);
$totalRows_rs_budgetData = mysqli_num_rows($rs_budgetData);

$varBudgetYearNext = $varBudgetYearSelect + 1;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 收支決算 Revenue &amp; Expenses</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<!-- InstanceEndEditable -->
<!-- InstanceParam name="NavBarLeft" type="boolean" value="false" -->
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
    <!-- InstanceBeginEditable name="Breadcrumbs" --> <a href="memberSEARCH.php">首頁 - 會員搜尋</a> / <a href="transactionSEARCH.php">收入與支出搜索</a> / 報表 / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->收支決算<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    請選擇年度和會計核算單位,以瀏覽年度決算數和預算數.<br />
    <form id="form_yearEntity" method="get" action="budgetPnL.php">
      年
      <select name="budgetYear" id="budgetYear">
        <?php
do {  
?>
        <option value="<?php echo $row_rsYearsPossible['transDateYear']?>"<?php if (!(strcmp($row_rsYearsPossible['transDateYear'], $varBudgetYearSelect))) {echo "selected=\"selected\"";} ?>>
		<?php echo $row_rsYearsPossible['transDateYear']?></option>
        <?php
} while ($row_rsYearsPossible = mysqli_fetch_assoc($rsYearsPossible));
  $rows = mysqli_num_rows($rsYearsPossible);
  if($rows > 0) {
      mysqli_data_seek($rsYearsPossible, 0);
	  $row_rsYearsPossible = mysqli_fetch_assoc($rsYearsPossible);
  }
?>
      </select>
      &nbsp;會計核算單位
      <select name="budgetEntity" id="budgetEntity">
        <?php
do {  
?>
        <option value="<?php echo $row_rsBudgetEntity['acctCODE']?>"<?php if (!(strcmp($row_rsBudgetEntity['acctCODE'], $varBudgetEntitySelect))) {echo "selected=\"selected\"";} ?>><?php echo $row_rsBudgetEntity['accountName']?></option>
        <?php
} while ($row_rsBudgetEntity = mysqli_fetch_assoc($rsBudgetEntity));
  $rows = mysqli_num_rows($rsBudgetEntity);
  if($rows > 0) {
      mysqli_data_seek($rsBudgetEntity, 0);
	  $row_rsBudgetEntity = mysqli_fetch_assoc($rsBudgetEntity);
  }
?>
      </select>
      &nbsp;
      <input type="submit" name="Submit" id="Submit" value="得出預算數" />
    </form>
    <form action="" method="post" id="form_budgetData">
      <table class="data">
        <thead class="data">
          <tr class="data">
            <th class="data">款</th>
            <th class="data">項</th>
            <th class="data">目</th>
            <th class="data">名稱</th>
            <th class="data">預算數<?php echo $varBudgetYearSelect; ?></th>
            <th class="data">決算數</th>
            <th class="data">餘絀數$</th>
            <th class="data">餘絀數%</th>
            <th class="data">備註<?php echo $varBudgetYearSelect; ?></th>
            <th class="data">預算數<?php echo $varBudgetYearNext; ?></th>
            <th class="data">備註<?php echo $varBudgetYearNext; ?></th>
          </tr>
        </thead>
        <tbody class="data">
          <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd';
					$accountID = $row_rs_budgetData['accountID'];
					$acctCODE = $row_rs_budgetData['acctCODE'];
					$budgetIDthis = $row_rs_budgetData['budgetIDthis'];
					$budgetThis = $row_rs_budgetData['budgetThis'];
					$budgetNotesThis = $row_rs_budgetData['budgetNotesThis'];
					$budgetIDnext = $row_rs_budgetData['budgetIDnext'];
					$budgetNext = $row_rs_budgetData['budgetNext'];
					$budgetNotesNext = $row_rs_budgetData['budgetNotesNext']; ?>
          <tr class="<?php echo $class; ?>">
            <td class="data"><?php echo $row_rs_budgetData['agg1']; ?></td>
            <td class="data"><?php echo $row_rs_budgetData['agg2']; ?></td>
            <td class="data"><?php echo $acctCODE; ?></td>
            <td class="data"><?php echo $row_rs_budgetData['accountName']; ?></td>
            <td class="data right"><?php echo number_format($budgetThis); ?></td>
            <td class="data right"><?php echo number_format($row_rs_budgetData['actual']); ?></td>
            <td class="data right"><?php echo number_format($row_rs_budgetData['varianceD']); ?></td>
            <td class="data right"><?php echo number_format($row_rs_budgetData['varianceP'],2); ?>%</td>
            <td class="data"><?php echo $budgetNotesThis; ?></td>
            <td class="data right"><?php if (isset($acctCODE)) { echo "
				<input name=\"accountID[]\" type=\"hidden\" value=\"$accountID\" />
				<input name=\"budgetID[]\" type=\"hidden\" value=\"$budgetIDnext\" />
				<input name=\"budgetAmount[]\" type=\"text\" value=\"$budgetNext\" size=\"10\" maxlength=\"20\" />"; }
		  	else { echo number_format($budgetNext); } ?></td>
            <td class="data"><?php if (isset($acctCODE)) { echo "<input name=\"budgetNotes[]\" type=\"text\" value=\"$budgetNotesNext\" size=\"30\" maxlength=\"100\" />"; }
		  	else { echo $budgetNotesNext; } ?></td>
          </tr>
          <?php } while ($row_rs_budgetData = mysqli_fetch_assoc($rs_budgetData)); ?>
        </tbody>
      </table>
      <input name="budgetYear" type="hidden" value="<?php echo $varBudgetYearNext; ?>" />
      <input type="hidden" name="MM_update" value="form_budgetData" />
      <input name="Submit" type="submit" id="Submit" value="儲存更新" />
    </form>
    <p>&nbsp;</p>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!--end navbar -->
<br />
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($rsYearsPossible);

mysqli_free_result($rsBudgetEntity);

mysqli_free_result($rs_budgetData);
?>

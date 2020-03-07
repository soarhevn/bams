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
$MM_authorizedUsers = "";
$MM_donotCheckaccess = "true";

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
    if (($strUsers == "") && true) { 
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
<?php require_once('Connections/MySQL_Union.php');
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
// get current rate matrix
$query_rsunionRates = " SELECT * FROM unionRatesCurrent";

// $query_rsunionRates = "
// SELECT d1.ID, d1.salDisplay, d1.salary, d1.unionDues, d1.laborIns, TRIM(LEADING '0' FROM (DATE_SUB(d1.laborIns_dateEffective, INTERVAL 1911 YEAR))) AS laborDateEff, d1.medIns, TRIM(LEADING '0' FROM (DATE_SUB(d1.medIns_dateEffective, INTERVAL 1911 YEAR))) AS medDateEff
// FROM unionRates d1
// LEFT OUTER JOIN unionRates d2
// ON (d1.salary = d2.salary AND (d1.laborIns_dateEffective < d2.laborIns_dateEffective OR d1.medIns_dateEffective < d2.medIns_dateEffective))
// WHERE d2.salary IS NULL
// ORDER BY salary
// ";
$rsunionRates = mysqli_query($MySQL_Union, $query_rsunionRates) or die(mysqli_error($MySQL_Union));
$row_rsunionRates = mysqli_fetch_assoc($rsunionRates);
$totalRows_rsunionRates = mysqli_num_rows($rsunionRates);

// get rate change history
$query_rsunionRatesHistory = "
SELECT * FROM (
	(
	SELECT uR.ID, uR.laborIns_dateEffective AS dateEffective, uR.salEndDate,
		uR.salDisplay, uR.salary, '勞保' AS rateType, 'labor' AS rateType_en, uR.laborIns AS rate
	FROM unionRates uR 

	) UNION (

	SELECT uR.ID, uR.medIns_dateEffective AS dateEffective, uR.salEndDate,
		uR.salDisplay, uR.salary, '健保' AS rateType, 'med' AS rateType_en, uR.medIns AS rate
	FROM unionRates uR
	)
	) detail
ORDER BY rateType_en, salary ASC, dateEffective DESC
";
$rsunionRatesHistory = mysqli_query($MySQL_Union, $query_rsunionRatesHistory) or die(mysqli_error($MySQL_Union));
$row_rsunionRatesHistory = mysqli_fetch_assoc($rsunionRatesHistory);
$totalRows_rsunionRatesHistory = mysqli_num_rows($rsunionRatesHistory);

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
	<title>音樂工會: 薪金和費率矩陣 - Union Rates</title>

<!-- InstanceEndEditable -->
	<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<!-- InstanceEndEditable -->
<!-- InstanceParam name="NavBarLeft" type="boolean" value="true" -->
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
		<a href="memberSEARCH.php">首頁 - 會員搜尋</a> / <a href="billingRatesDETAIL.php">繳費作業</a> / 
<!-- InstanceEndEditable -->
	</div>
	<h2 id="pageName">
<!-- InstanceBeginEditable name="PageName" -->
		薪金和費率矩陣 
<!-- InstanceEndEditable -->
	</h2>
	<div class="mainSection">
<!-- InstanceBeginEditable name="MainSectionBody" -->
		<h3>
			現行費率
		</h3>
		<table class="data">
			<thead class="data">
				<tr class="data">
					<td class="data">勞/健保投保薪資</td>
					<td class="data">投保薪資</td>
					<td class="data">經常會費</td>
					<td class="data">勞保費</td>
					<td class="data">生效日</td>
					<td class="data">健保費</td>
					<td class="data">生效日</td>
					<td class="data"><a href="billingRatesINSERT.php">增加繳款等級</a></td>
				</tr>
			</thead>
			<tbody class="data">

<?php do {
                                  $class = ($class == 'dataOdd') ? 'data' : 'dataOdd';  ?>
				<tr class="<?php echo $class; ?>">
					<td class="data">$ 
<?php echo $row_rsunionRates['salDisplay']; ?>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo number_format($row_rsunionRates['salary'],0); ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo number_format($row_rsunionRates['unionDues'],0); ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo number_format($row_rsunionRates['laborIns'],0); ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo $row_rsunionRates['laborIns_dateEffective']; ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo number_format($row_rsunionRates['medIns'],0); ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo $row_rsunionRates['medIns_dateEffective']; ?>
					</div>
					</td>
					<td align="center" class="data"><?php echo sprintf('<a href="billingRatesEDIT.php?recordID=%s">編輯</a>', $row_rsunionRates['ID']); ?>
					</td>
				</tr>
<?php } while ($row_rsunionRates = mysqli_fetch_assoc($rsunionRates)); ?>
			</tbody>
		</table>
		<h3>
			費率歷史
		</h3>
		<table id="history" class="data">
			<thead class="data">
				<tr class="data">
					<td class="data">生效日</td>
					<td class="data">截止日</td>
					<td class="data">勞/健保投保薪資</td>
					<td class="data">投保薪資</td>
					<td class="data">費率</td>
					<td class="data"></td>
				</tr>
			</thead>
			<tbody class="data">

<?php do {
                                  $class = ($class == 'dataOdd') ? 'data' : 'dataOdd';  ?>
				<tr class="<?php echo $class; ?>">
					<td class="data"> 
					<div align="right">

<?php echo $row_rsunionRatesHistory['dateEffective']; ?>
					</div>
					</td>
					<td class="data">
<?php echo $row_rsunionRatesHistory['salEndDate']; ?>
					</div>
					</td>
					<td class="data">$ 
<?php echo $row_rsunionRatesHistory['salDisplay']; ?>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo number_format($row_rsunionRatesHistory['salary'],0); ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo $row_rsunionRatesHistory['rateType']; ?>
					</div>
					</td>
					<td class="data"> 
					<div align="right">

<?php echo number_format($row_rsunionRatesHistory['rate'],0); ?>
					</div>
					</td>
				</tr>
<?php } while ($row_rsunionRatesHistory = mysqli_fetch_assoc($rsunionRatesHistory)); ?>
			</tbody>
		</table>
<!-- InstanceEndEditable -->
	</div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
	<div id="sectionLinks">
		<ul>
			<li><a href="billingRatesDETAIL.php">薪金和費率矩陣</a></li>
			<li><a href="billingUnpaidDUES.php">尚未繳費名單</a></li>
		</ul>
	</div>
	<div class="relatedLinks">
		<h3>
			創建收費帳單作業<br />
			(半年度) 
		</h3>
		<ul>
			<li>第一: <a href="billingRatesDETAIL.php">薪金和費率矩陣</a></li>
			<li>第二: <a href="billingIncomeBUMP.php">提高投保薪資</a></li>
			<li>第三: <a href="billingBillPeriodINSERT.php">插入新建費率</a></li>
			<li>第四: <a href="billingUnpaidDUES.php">尚未繳費名單</a></li>
			<li><a href="billingUnpaidDiffDUES.php">提高薪資尚未繳費名單</a></li>
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
 -->
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
<!-- InstanceEnd -->
<?php
  mysqli_free_result($rsunionRates);
  ?>
</body>
</html>

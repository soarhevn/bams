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

<?php require_once('Connections/MySQL_Union.php'); ?>

<?php
$currentPage = $_SERVER["PHP_SELF"];

$maxRows_rsMembers = 10;
$pageNum_rsMembers = 0;
if (isset($_GET['pageNum_rsMembers'])) {
  $pageNum_rsMembers = $_GET['pageNum_rsMembers'];
}
$startRow_rsMembers = $pageNum_rsMembers * $maxRows_rsMembers;

$varID_rsMembers = "%";
if (isset($_GET['idNumberSearch']) && $_GET['idNumberSearch']) {
  $varID_rsMembers = (get_magic_quotes_gpc()) ? $_GET['idNumberSearch'] : addslashes($_GET['idNumberSearch']);
}
$varName_rsMembers = "%";
if (isset($_GET['name'])) {
  $varName_rsMembers = (get_magic_quotes_gpc()) ? $_GET['name'] : addslashes($_GET['name']);
}
$varSal_rsMembers = "%";
if (isset($_GET['salary'])) {
  $varSal_rsMembers = (get_magic_quotes_gpc()) ? $_GET['salary'] : addslashes($_GET['salary']);
}
$varInsH_rsMembers = "%";
if (isset($_GET['insureHealth'])) {
  $varInsH_rsMembers = (get_magic_quotes_gpc()) ? $_GET['insureHealth'] : addslashes($_GET['insureHealth']);
}
$varInsL_rsMembers = "%";
if (isset($_GET['insureLabor'])) {
  $varInsL_rsMembers = (get_magic_quotes_gpc()) ? $_GET['insureLabor'] : addslashes($_GET['insureLabor']);
}
$varSalMonIncr_rsMembers = "";
if ($_GET['salMonthIncrease'] > 0) {
   $varSalMonIncr_rsMembers = (get_magic_quotes_gpc()) ? $_GET['salMonthIncrease'] : addslashes($_GET['salMonthIncrease']);
   $varSalMonIncr_SQL = "AND changeDateSal < IF(
      CONCAT(YEAR(NOW()),'-', %1\$s + 1, '-01') > LAST_DAY(NOW()),
      CONCAT(YEAR(NOW()) - 1,'-', %1\$s + 1, '-01'),
      CONCAT(YEAR(NOW()),'-', %1\$s + 1, '-01'))";
   $varSalMonIncr_rsMembers = sprintf($varSalMonIncr_SQL, $varSalMonIncr_rsMembers);
}
$varRepOrBoard_rsMembers = "";
if (isset($_GET['representative']) && $_GET['boardMember']) {
  $varRepOrBoard_rsMembers = "AND (members.representative = '1' OR 
  	members.boardMember = '1')";
} elseif (isset($_GET['representative'])) {
  $varRepOrBoard_rsMembers = "AND members.representative = '1'";
} elseif (isset($_GET['boardMember'])) {
  $varRepOrBoard_rsMembers = "AND members.boardMember = '1'";
}
$varInactive_rsMembers = "AND members.inactive IS NULL";
if (isset($_GET['inactive'])) {
  $varInactive_rsMembers = "AND members.inactive IS NOT NULL";
}
$varCardNum_rsMembers = "AND (members.cardNum LIKE '%' OR members.cardNum IS NULL)";
if (isset($_GET['cardNum']) && $_GET['cardNum']) {
  $varCardNum_rsMembers = (get_magic_quotes_gpc()) ? $_GET['cardNum'] : addslashes($_GET['cardNum']);
  $varCardNum_rsMembers = "AND members.cardNum LIKE '" . $varCardNum_rsMembers . "'";
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsMembers = sprintf("SELECT members.cardNum, members.idNumber, members.name, TRIM(LEADING '0' FROM (DATE_SUB(members.changeDateSal, INTERVAL 1911 YEAR))) AS changeDateSal, salDisplay, homePhone, mblPhone, 
IF(members.insureHealth='1', '是', '不') AS insureHealth, 
IF(members.insureLabor='1', '是', '不') AS insureLabor,
IF(members.salaryIncrease='1', '是', '不') AS salaryIncrease
FROM members
LEFT JOIN unionRatesCurrent uR ON members.salary = uR.salary
WHERE members.idNumber LIKE '%s' 
AND members.name LIKE '%%%s%%' 
AND members.salary LIKE '%s' 
AND members.insureHealth LIKE '%s' 
AND members.insureLabor LIKE '%s' %s %s %s %s 
ORDER BY members.cardNum ASC", 
	$varID_rsMembers,
	$varName_rsMembers,
	$varSal_rsMembers,
	$varInsH_rsMembers,
	$varInsL_rsMembers,
	$varSalMonIncr_rsMembers,
	$varRepOrBoard_rsMembers,
  $varInactive_rsMembers,
  $varCardNum_rsMembers);
$query_limit_rsMembers = sprintf("%s LIMIT %d, %d", $query_rsMembers, $startRow_rsMembers, $maxRows_rsMembers);
$rsMembers = mysqli_query($MySQL_Union, $query_limit_rsMembers) or die(mysqli_error($MySQL_Union));
$row_rsMembers = mysqli_fetch_assoc($rsMembers);

if (isset($_GET['totalRows_rsMembers'])) {
  $totalRows_rsMembers = $_GET['totalRows_rsMembers'];
} else {
  $all_rsMembers = mysqli_query($MySQL_Union, $query_rsMembers);
  $totalRows_rsMembers = mysqli_num_rows($all_rsMembers);
}
$totalPages_rsMembers = ceil($totalRows_rsMembers/$maxRows_rsMembers)-1;

$queryString_rsMembers = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_rsMembers") == false && 
        stristr($param, "totalRows_rsMembers") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_rsMembers = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_rsMembers = sprintf("&totalRows_rsMembers=%d%s", $totalRows_rsMembers, $queryString_rsMembers);

// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLoc = "__report=bamsreports/";
// memberList report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$reportName = "Member_List.rptdesign";
$memberListURL = $baseURL . $birtOpMode . $reportLoc;
$memberListURL .= urlencode($reportName);
// $memberListURL .= "&cardNum=" . urlencode($varCardNum_rsMembers . "%");
$memberListURL .= "&idNumber=" . urlencode($varID_rsMembers);
$memberListURL .= "&name=" . urlencode("%" . $varName_rsMembers . "%");
$memberListURL .= "&salary=" . urlencode($varSal_rsMembers);
$memberListURL .= "&insureHealth=" . urlencode($varInsH_rsMembers);
$memberListURL .= "&insureLabor=" . urlencode($varInsL_rsMembers);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 成員搜索結果 - Member Search Results</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
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
    - 會員搜尋</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->成員搜索結果<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->

  <!-- Show SQL Query statement -->
  <!-- <p><?php echo $query_rsMembers ?></p> -->

    <table>
      <tr>
        <td><table class="data">
            <thead class="data">
              <tr class="data">
                <th class="data">卡號</th>
                <th class="data">姓名</th>
                <th class="data">身分證字號</th>
                <th class="data">住家電話</th>
                <th class="data">行動電話</th>
                <th class="data">勞/健保投保薪資</th>
                <th class="data">薪資調整日期</th>
                <th class="data">提高</th>
                <th class="data">健保</th>
                <th class="data">勞保</th>
              </tr>
            </thead>
            <tbody class="data">
              <?php do { 
			  		$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
                <tr class="<?php echo $class; ?>">
                  <td class="data"><?php echo $row_rsMembers['cardNum']; ?></td>
                  <td class="data"><?php echo $row_rsMembers['name']; ?></td>
                  <td class="data"><a href="memberDETAIL.php?idNumber=<?php 
			  	echo $row_rsMembers['idNumber'] . "&" . strstr($_SERVER['QUERY_STRING'], 'pageNum_rsMembers='); ?>"> <?php echo $row_rsMembers['idNumber']; ?></a> </td>
                  <td class="data"><?php echo $row_rsMembers['homePhone']; ?></td>
                  <td class="data"><?php echo $row_rsMembers['mblPhone']; ?></td>
                  <td class="data right">$ <?php echo $row_rsMembers['salDisplay']; ?></td>
                  <td class="data center"><?php echo $row_rsMembers['changeDateSal']; ?></td>
                  <td class="data center"><?php echo $row_rsMembers['salaryIncrease']; ?></td>
                  <td class="data center"><?php echo $row_rsMembers['insureHealth']; ?></td>
                  <td class="data center"><?php echo $row_rsMembers['insureLabor']; ?></td>
                </tr>
                <?php } while ($row_rsMembers = mysqli_fetch_assoc($rsMembers)); ?>
            </tbody>
          </table></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td><table class="dataNav">
            <tr>
              <td><?php if ($pageNum_rsMembers > 0) { // Show if not first page ?>
                  <a href="<?php printf("%s?pageNum_rsMembers=%d%s", $currentPage, 0, $queryString_rsMembers); ?>">第一頁</a>
                  <?php } // Show if not first page ?>
              </td>
              <td><?php if ($pageNum_rsMembers > 0) { // Show if not first page ?>
                  <a href="<?php printf("%s?pageNum_rsMembers=%d%s", $currentPage, max(0, $pageNum_rsMembers - 1), $queryString_rsMembers); ?>">上一頁</a>
                  <?php } // Show if not first page ?>
              </td>
              <td><?php if ($pageNum_rsMembers < $totalPages_rsMembers) { // Show if not last page ?>
                  <a href="<?php printf("%s?pageNum_rsMembers=%d%s", $currentPage, min($totalPages_rsMembers, $pageNum_rsMembers + 1), $queryString_rsMembers); ?>">下一頁</a>
                  <?php } // Show if not last page ?>
              </td>
              <td><?php if ($pageNum_rsMembers < $totalPages_rsMembers) { // Show if not last page ?>
                  <a href="<?php printf("%s?pageNum_rsMembers=%d%s", $currentPage, $totalPages_rsMembers, $queryString_rsMembers); ?>">最末頁</a>
                  <?php } // Show if not last page ?>
              </td>
              <td class="right">資料筆數 <?php echo ($startRow_rsMembers + 1) ?> 到 <?php echo min($startRow_rsMembers + $maxRows_rsMembers, $totalRows_rsMembers) ?> 的 <?php echo $totalRows_rsMembers ?></td>
            </tr>
          </table></td>
      </tr>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
<!--  <div id="sectionLinks">
    <ul>
      <li><a href="memberINSERT.php">增加新會員</a></li>
    </ul>
  </div>
-->  <div class="relatedLinks">
    <h3>地址之列印</h3>
    <ul>
      <li><form name="labelCSVdownload" action="labels/labelCsvDOWNLOAD.php" 
      		method="POST">
		<input type="hidden" name="sqlStatement" value="<?php echo $query_rsMembers; ?>">
		<a href='javascript: document.forms["labelCSVdownload"].submit();'>郵寄名單地址之列印數據資料合併</a>
		</form></li>
      <li><a href="labels/Label_AE(2x10).doc">郵寄名單地址之列印 (2x10張)</a></li>
      <li><a href="help/UMS-Word_Merge_Instructions.pdf" onclick="window.open('help/UMS-Word_Merge_Instructions.pdf', '_blank', ''); return false;">合併列印操作說明</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>會員表</h3>
    <ul>
      <li><a href="#" onclick="MM_openBrWindow('<?php echo $memberListURL; ?>','會員表','scrollbars=yes,resizable=yes')">會員表</a></li>
      </ul>
  </div>
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsMembers);
?>

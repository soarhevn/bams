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
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
mysqli_query($MySQL_Union, 'select @row_id := 0');
$query_rsMembers = "
SELECT @row_id := @row_id + 1 AS rowNum, members.idNumber, members.name, members.cardNum, members.address, members.homePhone, members.workPhone, members.mblPhone, income.duesHalfName, income.duesYear - 1911 AS duesYear, income.laborIns, income.medIns, income.total 
FROM members 
JOIN ( 
	SELECT idNumber, duesYear, duesHalfName, monthNum, paidDate, income.laborIns, income.medIns,
	(unionDues + laborIns + medIns + newMemDues + newMemDues2) AS total  
	FROM income 
	LEFT JOIN duesHalfName 
	ON duesHalfName.duesHalf = income.duesHalf
	WHERE income.paidDate IS NULL
	AND income.changeDate IN (CURDATE(), DATE_SUB(CURDATE(), INTERVAL 1 DAY))
	AND income.monthNum = 0
	AND income.unionDues = 0
	AND income.newMemDues = 0
	AND income.newMemDues2 = 0
) AS income 
ON members.idNumber = income.idNumber 
WHERE members.salaryIncrease = 1
ORDER BY cardNum ASC
";
$rsMembers = mysqli_query($MySQL_Union, $query_rsMembers) or die(mysqli_error($MySQL_Union));
$row_rsMembers = mysqli_fetch_assoc($rsMembers);
$totalRows_rsMembers = mysqli_num_rows($rsMembers);

// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLoc = "__report=bamsreports/";
// bills report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$reportName = "Dues_Bill-Diff.rptdesign";
$billMemberURL = $baseURL . $birtOpMode . $reportLoc;
$billMemberURL .= urlencode($reportName);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 提高薪資尚未繳費名單 - Unpaid Dues for Income Bumped Members</title>
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
    <!-- InstanceBeginEditable name="Breadcrumbs" --><a href="memberSEARCH.php">首頁
    - 會員搜尋</a> / <a href="billingRatesDETAIL.php"> 繳費作業</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->提高薪資尚未繳費名單<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" --> 總筆數<?php echo $totalRows_rsMembers ?>筆
    <table class="data">
      <thead class="data">
        <tr class="data">
          <th class="data">#</th>
          <th class="data">身分證字號</th>
          <th class="data">姓名</th>
          <th class="data">卡號</th>
          <th class="data">住家電話</th>
          <th class="data">工作電話</th>
          <th class="data">行動電話</th>
          <th class="data">上下半年</th>
          <th class="data">年</th>
          <th class="data">勞保費</th>
          <th class="data">健保費</th>
          <th class="data">合計</th>
          <th class="data">住址</th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do {
	  		$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
        <tr class="<?php echo $class; ?>">
          <td class="data"><?php echo $row_rsMembers['rowNum']; ?></td>
          <td class="data"><a href="memberDETAIL.php?idNumber=<?php echo $row_rsMembers['idNumber']; ?>"> <?php echo $row_rsMembers['idNumber']; ?></a> </td>
          <td class="data"><?php echo $row_rsMembers['name']; ?></td>
          <td class="data"><?php echo $row_rsMembers['cardNum']; ?></td>
          <td class="data"><?php echo $row_rsMembers['homePhone']; ?></td>
          <td class="data"><?php echo $row_rsMembers['workPhone']; ?></td>
          <td class="data"><?php echo $row_rsMembers['mblPhone']; ?></td>
          <td class="data"><div align="center"><?php echo $row_rsMembers['duesHalfName']; ?></div></td>
          <td class="data"><?php echo $row_rsMembers['duesYear']; ?></td>
          <td class="data"><div align="right"><?php echo '$', number_format($row_rsMembers['laborIns']); ?></div></td>
          <td class="data"><div align="right"><?php echo '$', number_format($row_rsMembers['medIns']); ?></div></td>
          <td class="data"><div align="right"><?php echo '$', number_format($row_rsMembers['total']); ?></div></td>
          <td class="data"><?php echo $row_rsMembers['address']; ?></td>
        </tr>
        <?php } while ($row_rsMembers = mysqli_fetch_assoc($rsMembers)); ?>
      </tbody>
    </table>
    <!-- InstanceEndEditable --></div>
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
    <h3>創建收費帳單作業<br />
      (半年度)</h3>
    <ul>
      <li>第一: <a href="billingRatesDETAIL.php">薪金和費率矩陣</a></li>
      <li>第二: <a href="billingIncomeBUMP.php">提高投保薪資</a></li>
      <li>第三: <a href="billingBillPeriodINSERT.php">插入新建費率</a></li>
      <li>第四: <a href="billingUnpaidDUES.php">尚未繳費名單</a></li>
      <li><a href="billingUnpaidDiffDUES.php">提高薪資尚未繳費名單</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>列印</h3>
    <ul>
      <li><a href="#" onclick="MM_openBrWindow('<?php echo $billMemberURL; ?>','','scrollbars=yes,resizable=yes')">提高薪資繳款通知單</a></li>
    </ul>
  </div>
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($rsMembers);
?>

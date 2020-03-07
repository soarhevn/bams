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

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_memberIncomeBump = "
SELECT cardNum, idNumber, name, 
TRIM(LEADING '0' FROM (DATE_SUB(birthday, INTERVAL 1911 YEAR))) AS birthday,
TRIM(LEADING '0' FROM (DATE_SUB(insureDateLabor, INTERVAL 1911 YEAR))) AS insureDateLabor, 
TRIM(LEADING '0' FROM (DATE_SUB(changeDateSal, INTERVAL 1911 YEAR))) AS changeDateSal, 
mem.salary, IFNULL(MAX(uR.salary),0) AS salaryBump
FROM unionRates uR, members mem
WHERE uR.salary <= (mem.salary * 1.15)
AND uR.salary > mem.salary
AND inactive IS NULL
AND salaryIncrease = 1
AND insureLabor = 1
AND (changeDateSal < DATE_SUB(LAST_DAY(CURDATE()), INTERVAL 364 DAY)
	OR changeDateSal IS NULL )
GROUP BY mem.idNumber
ORDER BY cardNum
";
$rs_memberIncomeBump = mysqli_query($MySQL_Union, $query_rs_memberIncomeBump) or die(mysqli_error($MySQL_Union));
$row_rs_memberIncomeBump = mysqli_fetch_assoc($rs_memberIncomeBump);
$totalRows_rs_memberIncomeBump = mysqli_num_rows($rs_memberIncomeBump);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_changeDateSalNEW = "SELECT (DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)) AS changeDateSalNEW";
$rs_changeDateSalNEW = mysqli_query($MySQL_Union, $query_rs_changeDateSalNEW) or die(mysqli_error($MySQL_Union));
$row_rs_changeDateSalNEW = mysqli_fetch_assoc($rs_changeDateSalNEW);
$totalRows_rs_changeDateSalNEW = mysqli_num_rows($rs_changeDateSalNEW);
$changeDateSalNEW = $row_rs_changeDateSalNEW['changeDateSalNEW'];

?>
<?php
$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}
?>
<?php
if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "formIncomeBump")) {
  $updateSQL = sprintf("
UPDATE members AS mem, (
	SELECT mem.idNumber, IFNULL(MAX(uR.salary),0) AS salaryBump
	FROM unionRates uR, members mem
	WHERE uR.salary <= (mem.salary * 1.15)
	AND uR.salary > mem.salary
	AND inactive IS NULL
	AND salaryIncrease = 1
	AND insureLabor = 1
	AND (changeDateSal < DATE_SUB(LAST_DAY(CURDATE()), INTERVAL 364 DAY)
		OR changeDateSal IS NULL )
	GROUP BY mem.idNumber
	) sB
SET mem.salary = sB.salaryBump, changeDateSal = %1\$s, changeDate = CURDATE()
WHERE mem.idNumber = sB.idNumber
	",
					   GetSQLValueString($MySQL_Union, $_POST['changeDateSalNEW'], "text"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

  $updateGoTo = "billingBillPeriodINSERT.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 提高投保薪資 - Salary Bump</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable --><!-- InstanceParam name="NavBarLeft" type="boolean" value="true" -->
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
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->符合提高投保薪資之會員<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <p>檢查以下會員是否確定合乎資格以提高薪資. 若是發現不符資格者,請點選該會員之身分證字號, 視窗會轉到該會員之資料表進行修改, 也就是將該會員之&quot;自動提高薪資&quot;的欄位從&quot;是&quot;改成&quot;不&quot;. 修改完畢後再回到&quot;繳費作業&quot;的作業,然後在點&quot;提高投保薪資&quot;, 回到此畫面繼續作業.</p></br>
    <form action="<?php echo $editFormAction; ?>" method="post" id="formIncomeBump">
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td><span class="strong">要提高投保薪資總人數</span>: <?php echo $totalRows_rs_memberIncomeBump; ?></td>
          <td>&nbsp;</td>
          <td><span class="strong">系統將自動更新以下會員之新的&quot;薪資調整日期&quot;為</span>
            <?php echo $changeDateSalNEW; ?>
            <input type="hidden" name="changeDateSalNEW" value="<?php echo $changeDateSalNEW; ?>" />
            </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      </table>
      <!--Show table data; embed id and bumped salary amount into hidden form for update -->
      <table class="data">
        <thead class="data">
          <tr class="data">
            <th class="data">卡號</th>
            <th class="data">姓名</th>
            <th class="data">身分證字號</th>
            <th class="data">出生日</th>
            <th class="data">勞保日</th>
            <th class="data">薪資調整日期</th>
            <th class="data">勞/健保投保薪資</th>
            <th class="data">新勞/健保投保薪資</th>
          </tr>
        </thead>
        <tbody class="data">
          <?php do {
		  	$idR = $row_rs_memberIncomeBump['idNumber'];
			$salaryR = $row_rs_memberIncomeBump['salaryBump'];
	  		$class = ($class == 'dataOdd') ? 'data' : 'dataOdd';?>
          <tr class="<?php echo $class; ?>">
            <td class="data"><?php echo $row_rs_memberIncomeBump['cardNum']; ?></td>
            <td class="data"><?php echo $row_rs_memberIncomeBump['name']; ?></td>
            <td class="data"><a href="memberDETAIL.php?idNumber=<?php echo $idR; ?>"> <?php echo $idR; ?></a></td>
            <td class="data center"><?php echo $row_rs_memberIncomeBump['birthday']; ?></td>
            <td class="data center"><?php echo $row_rs_memberIncomeBump['insureDateLabor']; ?></td>
            <td class="data center"><?php echo $row_rs_memberIncomeBump['changeDateSal']; ?></td>
            <td class="data right">$ <?php echo number_format($row_rs_memberIncomeBump['salary']); ?></td>
            <td class="data right">$ <?php echo number_format($salaryR); ?></td>
          </tr>
          <?php } while ($row_rs_memberIncomeBump = mysqli_fetch_assoc($rs_memberIncomeBump)); ?>
        </tbody>
      </table>
      <p><strong>列印</strong>－－請到此畫面左上工具列之&quot;檔案&quot;，點選其中的&quot;列印&quot;即可將需要提高薪資之會員名單列印出來，以便申請作業。</p>
      <input type="submit" name="submitFormButton" id="submitFormButton" value="提高投保薪資確定" />
      <input type="hidden" name="MM_update" value="formIncomeBump" />
    </form>
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
</body>
<!-- InstanceEnd -->
</html>
<?php
mysqli_free_result($rs_memberIncomeBump);

mysqli_free_result($rs_changeDateSalNEW);
?>

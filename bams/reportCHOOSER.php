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
<?php
require_once('Connections/MySQL_Union.php');

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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "formSaveReportParameters")) {
  $rowCount = $_POST['rowCount'];
  for($i = 0; $i < $rowCount; $i++) {
  $updateSQL = sprintf("UPDATE reporting SET name=%s, typeR=%s, groupID=%s, yearR=%s, monthR=%s WHERE id=%s",
                       GetSQLValueString($MySQL_Union, $_POST['name'][$i], "text"),
					   GetSQLValueString($MySQL_Union, $_POST['typeR'][$i], "int"),
					   GetSQLValueString($MySQL_Union, $_POST['groupID'][$i], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['yearR'][$i], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['monthR'][$i], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['id'][$i], "int"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));
  }

  $updateGoTo = "reportCHOOSER.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form_addReportRow")) {
  $insertSQL = "INSERT INTO reporting (name) VALUES (\"report name\")";

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $addReportRow = mysqli_query($MySQL_Union, $insertSQL) or die(mysqli_error($MySQL_Union));	
}

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_reporting = "SELECT * FROM reporting ORDER BY id ASC";
$rs_reporting = mysqli_query($MySQL_Union, $query_rs_reporting) or die(mysqli_error($MySQL_Union));
$row_rs_reporting = mysqli_fetch_assoc($rs_reporting);
$totalRows_rs_reporting = mysqli_num_rows($rs_reporting);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_GroupNames = "SELECT accGrpID, accGrpName FROM accountGroups WHERE inactive = 0 ORDER BY accGrpName ASC";
$rs_GroupNames = mysqli_query($MySQL_Union, $query_rs_GroupNames) or die(mysqli_error($MySQL_Union));
$row_rs_GroupNames = mysqli_fetch_assoc($rs_GroupNames);
$totalRows_rs_GroupNames = mysqli_num_rows($rs_GroupNames);

function birt($reportName, $params) {
// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLoc = "__report=bamsreports/";
// memberList report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$birtURL = $baseURL . $birtOpMode . $reportLoc;
$birtURL .= urlencode($reportName);
$memberListURL .= "&cardNum=" . urlencode($varCardNum_rsMembers . "%");
$memberListURL .= "&idNumber=" . urlencode($varID_rsMembers);
$memberListURL .= "&name=" . urlencode("%" . $varName_rsMembers . "%");
$memberListURL .= "&salary=" . urlencode($varID_rsMembers);
$memberListURL .= "&insureHealth=" . urlencode($varInsH_rsMembers);
$memberListURL .= "&insureLabor=" . urlencode($varInsL_rsMembers);
return $birtURL;
}
?>
<?php
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rs_textBlocks = "SELECT id, descript_zh FROM textBlocks WHERE textFlag = 1 ORDER BY id ASC";
$rs_textBlocks = mysqli_query($MySQL_Union, $query_rs_textBlocks) or die(mysqli_error($MySQL_Union));
$row_rs_textBlocks = mysqli_fetch_assoc($rs_textBlocks);
$totalRows_rs_textBlocks = mysqli_num_rows($rs_textBlocks);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 報表 - Reports</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}

function openReportWindow(typeR,idR,winName,features) { 
  // birt reports code
  var baseURL = "https://" + "<?php echo $_SERVER['SERVER_NAME'] ?>" + "/birt/";
  var reportLoc = "__report=bamsreports/";
  var birtOpMode = "run?__format=pdf&";  // frameset or run
  var birtURL = baseURL + birtOpMode + reportLoc;
  var reportName;
  switch(typeR) {
  	case "0":
		reportName = "expense_revenue_report";
		break;
	case "1":
		reportName = "asset_liability_report";
		break;
	case "4":
		reportName = "asset_liability_report-tall";
		break;
	case "2":
		reportName = "expense_revenue_report_gov";
		break;
	case "3":
		reportName = "expense_revenue_report_gov-rev"
  }
  birtURL += encodeURI(reportName + ".rptdesign");
  birtURL += "&reporting_id=" + idR;
  
  window.open(birtURL,winName,features);
}

function openMonthlyReport(winName,features) {
	// get monthly report from birt
	var monthR = eval(document.forms['monthly_report'].elements['monthR'].value);
	var yearR = eval(document.forms['monthly_report'].elements['yearR'].value);
	var dateR = yearR + 1911;
	dateR = dateR + '-' + monthR + '-1';
    var baseURL = "https://" + "<?php echo $_SERVER['SERVER_NAME'] ?>" + "/birt/";
    var reportLoc = "__report=bamsreports/";
	var birtOpMode = "run?__format=pdf&";  // frameset or run
	var reportName = "monthly_report";
	var birtURL = baseURL + birtOpMode + reportLoc;
	birtURL += encodeURI(reportName + ".rptdesign");
	birtURL += "&monthR=" + monthR;
	birtURL += "&yearR=" + yearR;
	birtURL += "&dateR=" + dateR;
	
	window.open(birtURL,winName,features);
}

function openQuarterlyReport(winName,features) {
	// get quarterly report from birt
	var yearR = eval(document.forms['quarterly_report'].elements['yearQ'].value);
	yearR += 1911;
	var quarterR = eval(document.forms['quarterly_report'].elements['quarterR'].value);
	var monthR = quarterR * 3 - 2;
	var dateR = yearR.toString() + "-" + monthR.toString() + "-" + "01";
	var baseURL = "https://" + "<?php echo $_SERVER['SERVER_NAME'] ?>" + "/birt/";
    var reportLoc = "__report=bamsreports/";
	var birtOpMode = "run?__format=pdf&";  // frameset or run?__format=pdf&
	var reportType = eval(document.forms['quarterly_report'].elements['reportType'].value);
	switch (reportType) {
	case 1: 
		var reportName = "quarterly_report";
		break
	case 2:
		var reportName = "quarterly_cashFlow_report";
		break
	case 3:
		var reportName = "memberInOut_Quarter_report";
		break
	default:
		var reportName = "quarterly_report_combined";
	}
	var birtURL = baseURL + birtOpMode + reportLoc;
	birtURL += encodeURI(reportName + ".rptdesign");
	birtURL += "&quarterR=" + quarterR;
	birtURL += "&yearR=" + yearR;
	birtURL += "&dateR=" + dateR;
	birtURL += "&titleR=" + reportType;

	/*alert(reportName);*/
	window.open(birtURL,winName,features);
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
    - 會員搜尋</a> / <a href="transactionSEARCH">收入與支出搜索</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->報表<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="formSaveReportParameters">
      <table class="data">
        <thead class="data">
          <tr class="data">
            <th class="data">&nbsp;</th>
            <th class="data">報表</th>
            <th class="data">型</th>
            <th class="data">帳目組</th>
            <th class="data">年</th>
            <th class="data">月</th>
            <th class="data">&nbsp;</th>
            <th class="data">&nbsp;</th>
          </tr>
        </thead>
        <tbody class="data">
          <?php do { 
		  	$idR = $row_rs_reporting['id'];
			$group = $row_rs_reporting['groupID'];
			$typeR = $row_rs_reporting['typeR']; ?>
            <tr class="data">
              <td class="data"><a href="reportDELETE.php?report_id=<?php echo $idR; ?>">刪除</a></td>
              <td class="data"><input name="id[]" type="hidden" value="<?php echo $idR; ?>" />
                <input name="name[]" type="text" value="<?php echo $row_rs_reporting['name']; ?>" /></td>
              <td class="data"><select name="typeR[]">
                  <option value="0" <?php if (!(strcmp(0, $typeR))) {echo "selected=\"selected\"";} ?>>支出/收入</option>
                  <option value="2" <?php if (!(strcmp(2, $typeR))) {echo "selected=\"selected\"";} ?>>支出 - 傳票</option>
                  <option value="3" <?php if (!(strcmp(3, $typeR))) {echo "selected=\"selected\"";} ?>>收入 - 傳票</option>
                  <option value="1" <?php if (!(strcmp(1, $typeR))) {echo "selected=\"selected\"";} ?>>資產/負債</option>
                  <option value="4" <?php if (!(strcmp(4, $typeR))) {echo "selected=\"selected\"";} ?>>資產/負債 - 高格</option>

                </select>
              </td>
              <td class="data"><select name="groupID[]">
                  <?php 
					$group = $row_rs_reporting['groupID'];
do {  
?>
                  <option value="<?php echo $row_rs_GroupNames['accGrpID']?>" <?php if (!(strcmp($row_rs_GroupNames['accGrpID'], $group))) {echo "SELECTED";} ?>><?php echo $row_rs_GroupNames['accGrpName']?></option>
                  <?php
} while ($row_rs_GroupNames = mysqli_fetch_assoc($rs_GroupNames));
			mysqli_data_seek($rs_GroupNames,0);
?>
                </select>
              </td>
              <td class="data"><input name="yearR[]" type="text" value="<?php echo $row_rs_reporting['yearR']; ?>" size="4" maxlength="4" /></td>
              <td class="data"><input name="monthR[]" type="text" value="<?php echo $row_rs_reporting['monthR']; ?>" size="2" maxlength="2" /></td>
              <td class="data"><input type="submit" value="儲存參數" /></td>
              <td valign="middle" class="data"><a href="#" onclick="openReportWindow('<?php echo $typeR; ?>','<?php echo $idR; ?>','報表','scrollbars=yes,resizable=yes')">打開報表</a></td>
            </tr>
            <?php } while ($row_rs_reporting = mysqli_fetch_assoc($rs_reporting)); ?>
        </tbody>
      </table>
      <input type="hidden" name="MM_update" value="formSaveReportParameters" />
      <input name="rowCount" type="hidden" value="<?php echo $totalRows_rs_reporting; ?>" />
    </form>
    <br />
    <form action="<?php echo $editFormAction; ?>" method="post" id="form_addReportRow">
      <input name="Submit" type="submit" value="添加報表" />
      <input type="hidden" name="MM_update" value="form_addReportRow" />
    </form>
    <br />
    <table>
      <tr>
        <td><form action="" method="get" id="monthly_report">
            <table class="data">
              <thead class="data">
                <tr class="data">
                  <th colspan="3" class="data, left">提存明細</th>
                </tr>
                <tr class="data">
                  <th class="data">年</th>
                  <th class="data">月</th>
                  <th class="data">&nbsp;</th>
                </tr>
              </thead>
              <tbody class="data">
                <tr class="data">
                  <td class="data"><input name="yearR" type="text" size="3" maxlength="3" value="<?php echo date('Y') - 1911; ?>" /></td>
                  <td class="data"><input name="monthR" type="text" size="2" maxlength="2" value="<?php echo date('n'); ?>" /></td>
                  <td class="data"><a href="#" onclick="openMonthlyReport('報表','scrollbars=yes,resizable=yes')">打開報表</a></td>
                </tr>
              </tbody>
            </table>
          </form></td>
        <td>&nbsp;</td>
        <td><form action="" method="get" id="quarterly_report">
            <table class="data">
              <thead class="data">
                <tr class="data">
                  <th colspan="4" class="data, left">季明細表</th>
                </tr>
                <tr class="data">
                  <th class="data">年</th>
                  <th class="data">季</th>
                  <th class="data">型</th>
                  <th class="data">&nbsp;</th>
                </tr>
              </thead>
              <tbody class="data">
                <tr class="data">
                  <td class="data"><input name="yearQ" type="text" size="3" maxlength="3" value="<?php echo date('Y') - 1911; ?>" /></td>
                  <td class="data"><select name="quarterR">
                      <option value="1" <?php if (!(strcmp(1, ceil(date('n')/3)))) {echo "selected=\"selected\"";} ?>>一月至三月</option>
                      <option value="2" <?php if (!(strcmp(2, ceil(date('n')/3)))) {echo "selected=\"selected\"";} ?>>四月至六月</option>
                      <option value="3" <?php if (!(strcmp(3, ceil(date('n')/3)))) {echo "selected=\"selected\"";} ?>>七月至九月</option>
                      <option value="4" <?php if (!(strcmp(4, ceil(date('n')/3)))) {echo "selected=\"selected\"";} ?>>十月至十二月</option>
                    </select>
                  </td>
                  <td class="data"><select name="reportType">
                      <option value="4">帳冊&amp;現金流動-存摺(理事會議議程)</option>
                      <option value="5">帳冊&amp;現金流動-存摺(監事會議議程)</option>
                      <option value="6">帳冊&amp;現金流動-存摺(理事會議記錄)</option>
                      <option value="7">帳冊&amp;現金流動-存摺(監事會議記錄)</option>
                      <option value="1">帳冊</option>
                      <option value="2">現金流動-存摺</option>
                      <option value="3">度會員入會退會記錄</option>
                    </select>
                  </td>
                  <td class="data"><a href="#" onclick="openQuarterlyReport('報表','scrollbars=yes,resizable=yes')">打開報表</a></td>
                </tr>
              </tbody>
            </table>
          </form></td>
      </tr>
    </table>
    <br />
    <table class="data">
      <thead class="data">
        <tr class="data">
          <th class="data, left">文件編輯</th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
			$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
          <tr class="<?php echo $class; ?>">
            <td class="data"><a href="reportTextEDIT.php?id=<?php echo $row_rs_textBlocks['id']; ?>"><?php echo $row_rs_textBlocks['descript_zh']; ?></a></td>
          </tr>
          <?php } while ($row_rs_textBlocks = mysqli_fetch_assoc($rs_textBlocks)); ?>
      </tbody>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rs_reporting);

mysqli_free_result($rs_GroupNames);

mysqli_free_result($rs_textBlocks);
?>

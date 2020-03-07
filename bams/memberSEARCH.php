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
$query_rsSalaryDynamicMenuFromunionRates = "SELECT salary, salDisplay FROM unionRatesCurrent ORDER BY salary ASC";
$rsSalaryDynamicMenuFromunionRates = mysqli_query($MySQL_Union, $query_rsSalaryDynamicMenuFromunionRates) or die(mysqli_error($MySQL_Union));
$row_rsSalaryDynamicMenuFromunionRates = mysqli_fetch_assoc($rsSalaryDynamicMenuFromunionRates);
$totalRows_rsSalaryDynamicMenuFromunionRates = mysqli_num_rows($rsSalaryDynamicMenuFromunionRates);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_gonghueStats = "SELECT * FROM stats";
$gonghueStats = mysqli_query($MySQL_Union, $query_gonghueStats) or die(mysqli_error($MySQL_Union));
$row_gonghueStats = mysqli_fetch_assoc($gonghueStats);
$totalRows_gonghueStats = mysqli_num_rows($gonghueStats);

// Set up month menu vars for salary bump elgibility search
$currentMonth = getdate();
$currentMonth = $currentMonth['mon'];
$tYear = "今年";
$nYear = "明年";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 會員搜尋 - Member Search</title>
<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("cardNum"));

	return true;
}
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
    <!-- InstanceBeginEditable name="Breadcrumbs" --><!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->會員搜尋<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="memberMASTER.php" method="get" id="searchMember">
      <table class="tableForm">
        <tr>
          <td align="right" scope="col"><label>卡號</label>
            <input name="cardNum" type="text" id="cardNum" size="12" maxlength="6" />
          </td>
          <td>&nbsp;</td>
          <td align="left" scope="col"><label>勞/健保投保薪資 $</label>
            <select id="salary" name="salary">
              <option value="%">全部</option>
              <?php
do {  
?>
              <option value="<?php echo $row_rsSalaryDynamicMenuFromunionRates['salary']?>">
              <?php 
		echo $row_rsSalaryDynamicMenuFromunionRates['salDisplay'] ?>
              </option>
              <?php
} while ($row_rsSalaryDynamicMenuFromunionRates = mysqli_fetch_assoc($rsSalaryDynamicMenuFromunionRates));
  $rows = mysqli_num_rows($rsSalaryDynamicMenuFromunionRates);
  if($rows > 0) {
      mysqli_data_seek($rsSalaryDynamicMenuFromunionRates, 0);
	  $row_rsSalaryDynamicMenuFromunionRates = mysqli_fetch_assoc($rsSalaryDynamicMenuFromunionRates);
  }
?>
            </select></td>
        </tr>
        <tr>
          <td align="right"><label>身分證字號</label>
            <input name="idNumberSearch" type="text" id="idNumber" size="12" maxlength="10" /></td>
          <td>&nbsp;</td>
          <td align="left"><label>健保</label>
            <select name="insureHealth" id="insureHealth">
              <option value="%" selected="selected">全部(包括有加健保及沒加健保)</option>
              <option value="1">有加健保的會員</option>
              <option value="0">未加健保的會員</option>
            </select>
          </td>
        </tr>
        <tr>
          <td align="right"><label>姓名</label>
            <input name="name" type="text" id="name" size="12" maxlength="50" />
          </td>
          <td>&nbsp;</td>
          <td align='left'><label>勞保</label>
            <select name="insureLabor" id="insureLabor">
              <option value="%" selected="selected">全部(包括有加勞保及沒加勞保)</option>
              <option value="1">有加勞保的會員</option>
              <option value="0">未加勞保的會員</option>
            </select></td>
        </tr>
        <tr>
          <td align='right'> <input name="boardMember" type="checkbox" id="boardMember" value=" " /><label>理,監事</label>&nbsp;&nbsp;<input name="representative" type="checkbox" id="representative" value=" " /><label>代表</lable></td>
          <td>&nbsp;</td>
          <td align='left'><label>符合提高投保薪資</label>
            <select name="salMonthIncrease" id="salMonthIncrease">
              <option value="0" selected="selected">無</option>
              <option value="1"><?php if ( 1 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 一月</option>
              <option value="2"><?php if ( 2 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 二月</option>
              <option value="3"><?php if ( 3 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 三月</option>
              <option value="4"><?php if ( 4 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 四月</option>
              <option value="5"><?php if ( 5 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 五月</option>
              <option value="6"><?php if ( 6 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 六月</option>
              <option value="7"><?php if ( 7 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 七月</option>
              <option value="8"><?php if ( 8 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 八月</option>
              <option value="9"><?php if ( 9 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 九月</option>
              <option value="10"><?php if ( 10 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 十月</option>
              <option value="11"><?php if ( 11 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 十一月</option>
              <option value="12"><?php if ( 12 < $currentMonth) { echo $nYear; } else { echo $tYear; } ?> 十二月</option>
            </select>
          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td><input name="inactive" type="checkbox" id="inactive" value=" " />
            <label>已退會之會員</label></td>
        </tr>
        <tr>
          <td colspan="3" align="right"><input type="submit" name="Submit" value="搜尋" /></td>
        </tr>
      </table>
    </form>
    <h2>會員統計</h2>
    Ｑ:季 | Q1(1-3月), Q2(4-6月), Q3(7-9月), Q4(10-12月)
    <table class="data">
      <thead class="data">
        <tr class="data">
          <td class="data">性質</td>
          <td align="center" class="data">年季</td>
          <td align="center" class="data">男</td>
          <td align="center" class="data">女</td>
          <td align="center" class="data">合計</td>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
			$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
        <tr class="<?php echo $class; ?>">
          <td class="data"><?php echo $row_gonghueStats['in_outZH']; ?></td>
          <td align="center" class="data"><?php echo $row_gonghueStats['year_quarter']; ?></td>
          <td align="right" class="data"><?php echo $row_gonghueStats['male']; ?></td>
          <td align="right" class="data"><?php echo $row_gonghueStats['female']; ?></td>
          <td align="right" class="data"><?php echo $row_gonghueStats['total']; ?></td>
        </tr>
        <?php } while ($row_gonghueStats = mysqli_fetch_assoc($gonghueStats)); ?>
      </tbody>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>

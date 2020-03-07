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
$colname_rsMemberEDIT = "1";
if (isset($_GET['idNumber'])) {
  $colname_rsMemberEDIT = (get_magic_quotes_gpc()) ? $_GET['idNumber'] : addslashes($_GET['idNumber']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsMemberEDIT = sprintf("SELECT idNumber, name, cardNum, TRIM(LEADING '0' FROM (DATE_SUB(inactive, INTERVAL 1911 YEAR))) AS inactive , salary, TRIM(LEADING '0' FROM (DATE_SUB(birthday, INTERVAL 1911 YEAR))) AS birthday, handicap, TRIM(LEADING '0' FROM (DATE_SUB(memberDate, INTERVAL 1911 YEAR))) AS memberDate, insureHealth, insureLabor, TRIM(LEADING '0' FROM (DATE_SUB(insureDateHealth, INTERVAL 1911 YEAR))) AS insureDateHealth, TRIM(LEADING '0' FROM (DATE_SUB(insureDateLabor, INTERVAL 1911 YEAR))) AS insureDateLabor, address, homePhone, workPhone, mblPhone, email, occupation, TRIM(LEADING '0' FROM (DATE_SUB(changeDate, INTERVAL 1911 YEAR))) AS changeDate, TRIM(LEADING '0' FROM (DATE_SUB(changeDateSal, INTERVAL 1911 YEAR))) AS changeDateSal, monthlyBill, referrer, salaryIncrease, boardMember, representative 
FROM members WHERE idNumber = '%s'", $colname_rsMemberEDIT);
$rsMemberEDIT = mysqli_query($MySQL_Union, $query_rsMemberEDIT) or die(mysqli_error($MySQL_Union));
$row_rsMemberEDIT = mysqli_fetch_assoc($rsMemberEDIT);
$totalRows_rsMemberEDIT = mysqli_num_rows($rsMemberEDIT);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsHandicap = "SELECT * FROM handicapArray ORDER BY handicap ASC";
$rsHandicap = mysqli_query($MySQL_Union, $query_rsHandicap) or die(mysqli_error($MySQL_Union));
$row_rsHandicap = mysqli_fetch_assoc($rsHandicap);
$totalRows_rsHandicap = mysqli_num_rows($rsHandicap);

mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsSalaryInfo = "SELECT salary, salDisplay FROM unionRatesCurrent ORDER BY salary ASC";
$rsSalaryInfo = mysqli_query($MySQL_Union, $query_rsSalaryInfo) or die(mysqli_error($MySQL_Union));
$row_rsSalaryInfo = mysqli_fetch_assoc($rsSalaryInfo);
$totalRows_rsSalaryInfo = mysqli_num_rows($rsSalaryInfo);

function GetSQLValueString($MySQL_Union, $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $updateSQL = sprintf("UPDATE members SET name=%1\$s, cardNum=%2\$s, inactive=REPLACE(%3\$s, (SUBSTRING_INDEX(%3\$s, '-', 1)), ((SUBSTRING_INDEX(%3\$s, '-', 1)) + 1911)), salary=%4\$s, birthday=REPLACE(%5\$s, (SUBSTRING_INDEX(%5\$s, '-', 1)), ((SUBSTRING_INDEX(%5\$s, '-', 1)) + 1911)), handicap=%6\$s, memberDate=REPLACE(%7\$s, (SUBSTRING_INDEX(%7\$s, '-', 1)), ((SUBSTRING_INDEX(%7\$s, '-', 1)) + 1911)), insureHealth=%8\$s, insureDateHealth=REPLACE(%9\$s, (SUBSTRING_INDEX(%9\$s, '-', 1)), ((SUBSTRING_INDEX(%9\$s, '-', 1)) + 1911)), address=%10\$s, homePhone=%11\$s, workPhone=%12\$s, mblPhone=%13\$s, email=%14\$s, occupation=%15\$s, changeDateSal=REPLACE(%16\$s, (SUBSTRING_INDEX(%16\$s, '-', 1)), ((SUBSTRING_INDEX(%16\$s, '-', 1)) + 1911)), changeDate=NOW(), insureLabor=%17\$s, insureDateLabor=REPLACE(%18\$s, (SUBSTRING_INDEX(%18\$s, '-', 1)), ((SUBSTRING_INDEX(%18\$s, '-', 1)) + 1911)), monthlyBill=%19\$s, referrer=%20\$s, salaryIncrease=%21\$s, boardMember=%22\$s, representative=%23\$s WHERE idNumber=%24\$s",
                       GetSQLValueString($MySQL_Union, $_POST['name'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['cardNum'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['inactive'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['salary'], "double"),
                       GetSQLValueString($MySQL_Union, $_POST['birthday'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['handicap'], "int"),
                       GetSQLValueString($MySQL_Union, $_POST['memberDate'], "date"),
                       GetSQLValueString($MySQL_Union, isset($_POST['insureHealth']) ? "true" : "", "defined","1","0"),
                       GetSQLValueString($MySQL_Union, $_POST['insureDateHealth'], "date"),
                       GetSQLValueString($MySQL_Union, $_POST['address'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['homePhone'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['workPhone'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['mblPhone'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['email'], "text"),
                       GetSQLValueString($MySQL_Union, $_POST['occupation'], "text"),
					   GetSQLValueString($MySQL_Union, $_POST['changeDateSal'], "date"),
					   GetSQLValueString($MySQL_Union, isset($_POST['insureLabor']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($MySQL_Union, $_POST['insureDateLabor'], "date"),
					   GetSQLValueString($MySQL_Union, isset($_POST['monthlyBill']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($MySQL_Union, $_POST['referrer'], "text"),
					   GetSQLValueString($MySQL_Union, isset($_POST['salaryIncrease']) ? "true" : "", "defined","1","0"),
                       GetSQLValueString($MySQL_Union, isset($_POST['boardMember']) ? "true" : "", "defined","1","0"),
                       GetSQLValueString($MySQL_Union, isset($_POST['representative']) ? "true" : "", "defined","1","0"),
                       GetSQLValueString($MySQL_Union, $_POST['idNumber'], "text"));

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $updateSQL) or die(mysqli_error($MySQL_Union));

  $updateGoTo = "memberDETAIL.php?idNumber=" . $row_rsMemberEDIT['idNumber'] . "";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $updateGoTo));
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 會員資料編輯 - Edit Member</title>
<!-- InstanceEndEditable -->
<link rel="stylesheet" href="assets/css/2col_leftNav.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript">
<!--
function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_validateForm() { //v4.0
  var i,p,q,nm,test,num,min,max,errors='',args=MM_validateForm.arguments;
  for (i=0; i<(args.length-2); i+=3) { test=args[i+2]; val=MM_findObj(args[i]);
    if (val) { nm=args[i+1]; if ((val=val.value)!="") {
      if (test.indexOf('isEmail')!=-1) { p=val.indexOf('@');
        if (p<1 || p==(val.length-1)) errors+='- '+nm+' must contain an e-mail address.\n';
      } else if (test!='R') { num = parseFloat(val);
        if (isNaN(val)) errors+='- '+nm+' 必須包含編號.\n';
        if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
          min=test.substring(8,p); max=test.substring(p+1);
          if (num<min || max<num) errors+='- '+nm+' 必須包含一個編號在 '+min+' 和 '+max+' 之間.\n';
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' is required.\n'; }
  } if (errors) alert('以下錯誤發生了:\n'+errors);
  document.MM_returnValue = (errors == '');
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
    - 會員搜尋</a> / 成員搜索結果 / <a href="memberDETAIL.php?recordID=<?php echo $row_rsMemberEDIT['idNumber']; ?>">會員資料總表</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->會員資料編輯<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <form action="<?php echo $editFormAction; ?>" method="post" id="form1" name="form1" onsubmit="MM_validateForm('name','姓名','R');return document.MM_returnValue">
      <table>
        <tr>
          <th class="tableForm">卡號:</th>
          <td class="tableForm"><input name="cardNum" type="text" value="<?php echo $row_rsMemberEDIT['cardNum']; ?>" size="6" maxlength="6" /></td>
          <th class="tableForm">姓名:</th>
          <td class="tableForm"><input name="name" type="text" value="<?php echo $row_rsMemberEDIT['name']; ?>" size="15" maxlength="50" /></td>
        </tr>
        <tr>
          <th class="tableForm" >身分證字號:</th>
          <td class="tableForm"><?php echo $row_rsMemberEDIT['idNumber']; ?></td>
          <th class="tableForm" >出生日:</th>
          <td class="tableForm"><input name="birthday" type="text" value="<?php echo $row_rsMemberEDIT['birthday']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <td colspan="4" >&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">住家電話:</th>
          <td class="tableForm"><input name="homePhone" type="text" value="<?php echo $row_rsMemberEDIT['homePhone']; ?>" size="15" maxlength="50" /></td>
          <th class="tableForm">工作電話:</th>
          <td class="tableForm"><input name="workPhone" type="text" value="<?php echo $row_rsMemberEDIT['workPhone']; ?>" size="15" maxlength="50" /></td>
        </tr>
        <tr>
          <th class="tableForm"></th>
          <td class="tableForm"></td>
          <th class="tableForm"></th>
          <td class="tableForm"></td>
        </tr>
        <tr>
          <th class="tableForm">行動電話:</th>
          <td class="tableForm"><input name="mblPhone" type="text" value="<?php echo $row_rsMemberEDIT['mblPhone']; ?>" size="15" maxlength="50" /></td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">電子郵件:</th>
          <td colspan="3" class="tableForm"><input name="email" type="text" value="<?php echo $row_rsMemberEDIT['email']; ?>" size="50" maxlength="100" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">住址:</th>
          <td colspan="3" class="tableForm"><input name="address" type="text" value="<?php echo $row_rsMemberEDIT['address']; ?>" size="50" maxlength="255" /></td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">勞/健保投保薪資:</th>
          <td class="tableForm"><select name="salary" id="salary">
              <?php
do {  
?>
              <option value="<?php echo $row_rsSalaryInfo['salary']?>"<?php if (!(strcmp($row_rsSalaryInfo['salary'], $row_rsMemberEDIT['salary']))) {echo "SELECTED";} ?>>$<?php echo $row_rsSalaryInfo['salDisplay']; ?></option>
              <?php
} while ($row_rsSalaryInfo = mysqli_fetch_assoc($rsSalaryInfo));
  $rows = mysqli_num_rows($rsSalaryInfo);
  if($rows > 0) {
      mysqli_data_seek($rsSalaryInfo, 0);
	  $row_rsSalaryInfo = mysqli_fetch_assoc($rsSalaryInfo);
  }
?>
            </select></td>
          <th class="tableForm">殘障:</th>
          <td class="tableForm"><select name="handicap">
              <?php
do {  
?>
              <option value="<?php echo $row_rsHandicap['handicap']?>"<?php if (!(strcmp($row_rsHandicap['handicap'], $row_rsMemberEDIT['handicap']))) {echo "SELECTED";} ?>><?php echo $row_rsHandicap['handiName']?></option>
              <?php
} while ($row_rsHandicap = mysqli_fetch_assoc($rsHandicap));
  $rows = mysqli_num_rows($rsHandicap);
  if($rows > 0) {
      mysqli_data_seek($rsHandicap, 0);
	  $row_rsHandicap = mysqli_fetch_assoc($rsHandicap);
  }
?>
            </select></td>
        </tr>
        <tr>
          <th class="tableForm">薪資調整日期:</th>
          <td class="tableForm"><input name="changeDateSal" type="text" value="<?php echo $row_rsMemberEDIT['changeDateSal']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
          <th class="tableForm">職別:</th>
          <td class="tableForm"><input name="occupation" type="text" value="<?php echo $row_rsMemberEDIT['occupation']; ?>" size="32" maxlength="50" /></td>
        </tr>
        <tr>
          <th class="tableForm">提高投保薪資</th>
          <td class="tableForm"><input name="salaryIncrease" type="checkbox" value="1" <?php if (!(strcmp($row_rsMemberEDIT['salaryIncrease'],1))) {echo "checked";} ?> /></td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">健保:</th>
          <td class="tableForm"><input name="insureHealth" type="checkbox" value="1" <?php if (!(strcmp($row_rsMemberEDIT['insureHealth'],1))) {echo "checked";} ?> /></td>
          <th class="tableForm">勞保:</th>
          <td class="tableForm"><input name="insureLabor" type="checkbox" value="1" <?php if (!(strcmp($row_rsMemberEDIT['insureLabor'],1))) {echo "checked";} ?> /></td>
        </tr>
        <tr>
          <th class="tableForm">健保日:</th>
          <td class="tableForm"><input name="insureDateHealth" type="text" value="<?php echo $row_rsMemberEDIT['insureDateHealth']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
          <th class="tableForm">勞保日:</th>
          <td class="tableForm"><input name="insureDateLabor" type="text" value="<?php echo $row_rsMemberEDIT['insureDateLabor']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">採每月繳費:</th>
          <td class="tableForm"><input name="monthlyBill" type="checkbox" value="1" <?php if (!(strcmp($row_rsMemberEDIT['monthlyBill'],1))) {echo "checked";} ?> /></td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">入會日:</th>
          <td class="tableForm"><input name="memberDate" type="text" value="<?php echo $row_rsMemberEDIT['memberDate']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
          <th class="tableForm">退會日:</th>
          <td class="tableForm"><input name="inactive" type="text" value="<?php echo $row_rsMemberEDIT['inactive']; ?>" size="10" maxlength="10" />
            年 ﹣月 ﹣日</td>
        </tr>
        <tr>
          <th class="tableForm">保證人:</th>
          <td class="tableForm"><input name="referrer" type="text" value="<?php echo $row_rsMemberEDIT['referrer']; ?>" size="10" maxlength="10" /></td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm">&nbsp;</td>
        </tr>
        <tr>
          <td colspan="4" >&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">理,監事:</th>
          <td class="tableForm"><input name="boardMember" type="checkbox" value="1" <?php if (!(strcmp($row_rsMemberEDIT['boardMember'],1))) {echo "checked";} ?> /></td>
          <th class="tableForm">代表:</th>
          <td class="tableForm"><input name="representative" type="checkbox" value="1" <?php if (!(strcmp($row_rsMemberEDIT['representative'],1))) {echo "checked";} ?> /></td>
        </tr>
        <tr>
          <td colspan="4" >&nbsp;</td>
        </tr>
        <tr>
          <th class="tableForm">最新更改會員資料日期:</th>
          <td class="tableForm"><?php echo $row_rsMemberEDIT['changeDate']; ?></td>
          <th class="tableForm">&nbsp;</th>
          <td class="tableForm"><input type="submit" value="儲存更新" /></td>
        </tr>
      </table>
      <p>
        <!--     <input type="hidden" name="changeDate" value="<?php echo $row_rsMemberEDIT['changeDate']; ?>" size="32">
 -->
        <input type="hidden" name="MM_update" value="form1" />
        <input type="hidden" name="idNumber" value="<?php echo $row_rsMemberEDIT['idNumber']; ?>" />
      </p>
    </form>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->

<!--end navbar -->
<br />
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsMemberEDIT);

mysqli_free_result($rsHandicap);

mysqli_free_result($rsSalaryInfo);
?>

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

require_once('Connections/MySQL_Union.php');

$colname_idNumber = "1";
if (isset($_GET['idNumber'])) {
  $colname_idNumber = (get_magic_quotes_gpc()) ? $_GET['idNumber'] : addslashes($_GET['idNumber']);
}

// Get member details
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_DetailRS1 = sprintf("
SELECT mem.cardNum, mem.idNumber, mem.name,  
TRIM(LEADING '0' FROM (DATE_SUB(mem.birthday, INTERVAL 1911 YEAR))) AS birthday,   
handicapArray.handiName, 
IF(mem.insureHealth='1', '是', '不') AS insureHealth, 
IF(mem.insureLabor='1', '是', '不') AS insureLabor, 
TRIM(LEADING '0' FROM (DATE_SUB(mem.insureDateHealth, INTERVAL 1911 YEAR))) AS insureDateHealth,  
TRIM(LEADING '0' FROM (DATE_SUB(mem.insureDateLabor, INTERVAL 1911 YEAR))) AS insureDateLabor,  
uR.salDisplay, 
TRIM(LEADING '0' FROM (DATE_SUB(mem . memberDate, INTERVAL 1911 YEAR))) AS memberDate,  
mem . occupation, mem . homePhone ,  mem . address , 
TRIM(LEADING '0' FROM (DATE_SUB(mem.inactive, INTERVAL 1911 YEAR))) AS inactive, 
mem.workPhone, mem.mblPhone, mem.email, 
TRIM(LEADING '0' FROM (DATE_SUB(mem.changeDate, INTERVAL 1911 YEAR))) AS changeDate, 
TRIM(LEADING '0' FROM (DATE_SUB(mem.changeDateSal, INTERVAL 1911 YEAR))) AS changeDateSal,
if(mem.monthlyBill='1', '是', '不') AS monthlyBill, refMem.name AS referrerName, mem.referrer,
IF(mem.salaryIncrease='1', '是', '不') AS salaryIncrease,
IF(mem.boardMember='1', '是', '不') AS boardMember,
IF(mem.representative='1', '是', '不') AS representative
FROM members mem
LEFT JOIN handicapArray USING (handicap)
LEFT JOIN members refMem ON mem.referrer = refMem.idNumber
LEFT JOIN unionRatesCurrent uR ON mem.salary = uR.salary
WHERE mem.idNumber = '%s'", $colname_idNumber);
$DetailRS1 = mysqli_query($MySQL_Union, $query_DetailRS1) or die(mysqli_error($MySQL_Union));
$row_DetailRS1 = mysqli_fetch_assoc($DetailRS1);

// Get referral members
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsReferrals = sprintf("
SELECT idNumber, name
FROM members
WHERE referrer = '%s'", $colname_idNumber);
$rsReferrals = mysqli_query($MySQL_Union, $query_rsReferrals) or die(mysqli_error($MySQL_Union));
$row_rsReferrals = mysqli_fetch_assoc($rsReferrals);
$totalRows_rsReferrals = mysqli_num_rows($rsReferrals);

// Get dependents table info
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsDetailDependents = sprintf("SELECT ID, idNumber, idParent, name, cardNum, TRIM(LEADING '0' FROM (DATE_SUB(birthday, INTERVAL 1911 YEAR))) AS birthday, handiName, TRIM(LEADING '0' FROM (DATE_SUB(insureDate, INTERVAL 1911 YEAR))) AS insureDate, TRIM(LEADING '0' FROM (DATE_SUB(inactive, INTERVAL 1911 YEAR))) AS inactive, TRIM(LEADING '0' FROM (DATE_SUB(memberDate, INTERVAL 1911 YEAR))) AS memberDate FROM dependents, handicapArray WHERE dependents.handicap = handicapArray.handicap AND idParent = '%s'", $colname_idNumber);
$rsDetailDependents = mysqli_query($MySQL_Union, $query_rsDetailDependents) or die(mysqli_error($MySQL_Union));
$row_rsDetailDependents = mysqli_fetch_assoc($rsDetailDependents);
$totalRows_rsDetailDependents = mysqli_num_rows($rsDetailDependents);

// Get dues table info
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsDetailIncome = sprintf("SELECT ID, idNumber, duesYear - 1911 AS duesYear, monthNum, duesHalfName, unionDues, laborIns, medIns, TRIM(LEADING '0' FROM (DATE_SUB(paidDate, INTERVAL 1911 YEAR))) AS paidDate, newMemDues, newMemDues2, unionDues + laborIns + medIns + newMemDues + newMemDues2 AS incomeTotal, IF(IFNULL(wire,0), '是', '不') AS wire, billType
FROM income LEFT JOIN duesHalfName ON duesHalfName.duesHalf = income.duesHalf
WHERE idNumber = '%s' ORDER BY duesYear DESC, monthNum DESC, income.duesHalf DESC, income.paidDate DESC", $colname_idNumber);
$rsDetailIncome = mysqli_query($MySQL_Union, $query_rsDetailIncome) or die(mysqli_error($MySQL_Union));
$row_rsDetailIncome = mysqli_fetch_assoc($rsDetailIncome);
$totalRows_rsDetailIncome = mysqli_num_rows($rsDetailIncome);

// Get other transactions
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsMemberTransactions = sprintf("SELECT idMaster, TRIM(LEADING '0' FROM (DATE_SUB(transDate, INTERVAL 1911 YEAR))) AS transDate, accountID1, CONCAT(aN1.accountName, ' - ', aT1.atName) AS accountName1, IF(debit1 > 0, FORMAT(debit1, 0), NULL) AS debit1, IF(credit1 > 0, FORMAT(credit1, 0), NULL) AS credit1, accountID2, CONCAT(aN2.accountName, ' - ', aT2.atName) AS accountName2, IF(debit2 > 0, FORMAT(debit2, 0), NULL) AS debit2, IF(credit2 > 0, FORMAT(credit2, 0), NULL) AS credit2, notes, changeDate, marker
FROM 
( /* get transactions out and match to transMaster for single line with both debit and credit */
SELECT DISTINCT idMaster, transDate, changeDate, memIdNum, notes, t1.id AS id1, 
t1.accountID AS accountID1, t1.debit AS debit1, t1.credit AS credit1, t2.id AS id2, 
t2.accountID AS accountID2,
t2.debit AS debit2, t2.credit AS credit2, marker
FROM transactionsMaster
LEFT JOIN transactions t1 USING (idMaster)
LEFT JOIN transactions t2 USING (idMaster)
LEFT JOIN accountGroupXref gX ON (t1.accountID = gX.accName_id OR t2.accountID = gX.accName_id)
WHERE t1.accountID != t2.accountID
AND t1.debit > 0
AND memIdNum LIKE '%s'
AND marker IS NULL 
) trans 
  /* end tranactions section */
  /* start joins for account names + account types */
LEFT JOIN accountNames aN1 ON accountID1 = aN1.accountID
LEFT JOIN accountNames aN2 ON accountID2 = aN2.accountID
LEFT JOIN accountType aT1 ON aN1.accountType = aT1.acctTypeID
LEFT JOIN accountType aT2 ON aN2.accountType = aT2.acctTypeID
ORDER BY BINARY transdate, idMaster ASC", $colname_idNumber);
$rsMemberTransactions = mysqli_query($MySQL_Union, $query_rsMemberTransactions) or die(mysqli_error($MySQL_Union));
$row_rsMemberTransactions = mysqli_fetch_assoc($rsMemberTransactions);
$totalRows_rsMemberTransactions = mysqli_num_rows($rsMemberTransactions);

// Get subsidy table info
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query_rsSubsidy = sprintf("
SELECT subsidies.id, subsidyTypeName, TRIM(LEADING '0' FROM (DATE_SUB(applicationDate, INTERVAL 1911 YEAR))) AS applicationDate, TRIM(LEADING '0' FROM (DATE_SUB(grantDate, INTERVAL 1911 YEAR))) AS grantDate, FORMAT(subsidyAmount, 0) AS subsidyAmount, grantID 
FROM subsidies, subsidyType 
WHERE subsidies.subsidyType = subsidyType.id AND idNumber = '%s'
ORDER BY subsidies.id DESC", $colname_idNumber);
$rsSubsidy = mysqli_query($MySQL_Union, $query_rsSubsidy) or die(mysqli_error($MySQL_Union));
$row_rsSubsidy = mysqli_fetch_assoc($rsSubsidy);
$totalRows_rsSubsidy = mysqli_num_rows($rsSubsidy);

// birt reports code
$baseURL = "https://" . $_SERVER['SERVER_NAME'] . "/birt/";
$reportLoc = "__report=bamsreports/";
// memberList report
$birtOpMode = "run?__format=pdf&";  // frameset or run
$reportName = "Dues_Bill-Member.rptdesign";
$billMemberURL = $baseURL . $birtOpMode . $reportLoc;
$billMemberURL .= urlencode($reportName);
$billMemberURL .= "&idNumber=" . urlencode($row_DetailRS1['idNumber']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/standardTemplate.dwt.php" codeOutsideHTMLIsLocked="false" -->
<!-- DW6 -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>音樂工會: 會員資料總表 - Member Detail</title>
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

function openHelpWindow(winName,features) { //v2.0
  var sPath = window.location.pathname;
  var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);
  sPage = sPage.replace(/php/,"html");
  theURL = "help/" + sPage;
  window.open(theURL,winName,features);
}

function openReceipt(winName,features) {
	// get receipt report from birt
	var JS_yearR = eval(document.forms['receipt_report'].elements['yearR'].value);
	JS_yearR = JS_yearR + 1911;
	var JS_idNumber = document.forms['receipt_report'].elements['idNumberR'].value;
	var baseURL = "https://" + "<?php echo $_SERVER['SERVER_NAME'] ?>" + "/birt/";			
	var reportLoc = "__report=bamsreports/";
	var birtOpMode = "run?__format=pdf&";  // frameset or run
	var reportName = "dues_Receipt";
	var birtURL = baseURL + birtOpMode + reportLoc;
	birtURL += encodeURI(reportName + ".rptdesign");
	birtURL += "&yearR=" + JS_yearR;
	birtURL += "&idNumberR=" + JS_idNumber;
	window.open(birtURL,winName,features);
}
//-->
</script>
<!-- InstanceEndEditable --><!-- InstanceParam name="NavBarLeft" type="boolean" value="true" -->
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
    - 會員搜尋 </a> / <a href="memberMASTER.php?<?php echo strstr($_SERVER['QUERY_STRING'], 'pageNum_rsMembers='); ?>">成員搜索結果</a> / <!-- InstanceEndEditable --></div>
  <h2 id="pageName"><!-- InstanceBeginEditable name="PageName" -->會員資料總表<!-- InstanceEndEditable --></h2>
  <div class="mainSection"><!-- InstanceBeginEditable name="MainSectionBody" -->
    <table class="tableForm">
      <tr valign="top">
        <td><table class="data">
            <thead class="data">
              <tr align="left" class="data">
                <th colspan="4" class="data">會員資料總表</th>
                <th align="right" class="data" ><a href="memberEDIT.php?idNumber=<?php echo $row_DetailRS1['idNumber'] . "&" . strstr($_SERVER['QUERY_STRING'], 'pageNum_rsMembers='); ?>">更改會員資料</a></th>
              </tr>
            </thead>
            <tbody class="dataNoBorder">
              <tr>
                <th align="right" class="data">卡號</th>
                <td class="data"><?php echo $row_DetailRS1['cardNum']; ?> </td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">姓名</th>
                <td class="data"><?php echo $row_DetailRS1['name']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">身分證字號</th>
                <td class="data"><?php echo $row_DetailRS1['idNumber']; ?> </td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">出生日</th>
                <td class="data"><?php echo $row_DetailRS1['birthday']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">住家電話</th>
                <td class="data"><?php echo $row_DetailRS1['homePhone']; ?> </td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">工作電話</th>
                <td class="data"><?php echo $row_DetailRS1['workPhone']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">行動電話</th>
                <td class="data"><?php echo $row_DetailRS1['mblPhone']; ?> </td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">電子郵件</th>
                <td colspan="4" class="data"><a href="mailto:<?php echo $row_DetailRS1['email']; ?>"><?php echo $row_DetailRS1['email']; ?></a></td>
              </tr>
              <tr>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">住址</th>
                <td colspan="4" class="data"><?php echo $row_DetailRS1['address']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">勞/健保投保薪資</th>
                <td class="data">$ <?php echo $row_DetailRS1['salDisplay']; ?> </td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">殘障</th>
                <td class="data"><?php echo $row_DetailRS1['handiName']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">薪資調整日期</th>
                <td class="data"><?php echo $row_DetailRS1['changeDateSal']; ?> </td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">職別</th>
                <td class="data"><?php echo $row_DetailRS1['occupation']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">提高投保薪資</th>
                <td class="data"><?php echo $row_DetailRS1['salaryIncrease']; ?></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">健保</th>
                <td class="data"><?php echo $row_DetailRS1['insureHealth']; ?></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">勞保</th>
                <td class="data"><?php echo $row_DetailRS1['insureLabor']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">健保日</th>
                <td class="data"><?php echo $row_DetailRS1['insureDateHealth']; ?></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">勞保日</th>
                <td class="data"><?php echo $row_DetailRS1['insureDateLabor']; ?></td>
              </tr>
              <tr>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">採每月繳費</th>
                <td class="data"><?php echo $row_DetailRS1['monthlyBill']; ?></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">入會日</th>
                <td class="data"><?php echo $row_DetailRS1['memberDate']; ?></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">退會日</th>
                <td class="data">
                	<?php if (is_null($row_DetailRS1['inactive'])) {
                	echo '<a href="incomeRefundINSERT.php?idNumber=' . 
                	$row_DetailRS1['idNumber'] . '&' . 
                	strstr($_SERVER['QUERY_STRING'], 'cardNum=') . '">會員退會與退費</a>';
                	} else {
                	echo $row_DetailRS1['inactive'];
                	} ?>
            	</td>
              </tr>
              <tr>
                <th align="right" class="data">保證人</th>
                <td class="data"><a href="memberDETAIL.php?idNumber=<?php echo $row_DetailRS1['referrer'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>"><?php echo $row_DetailRS1['referrerName']; ?></a></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">&nbsp;</th>
                <td class="data">&nbsp;</td>
              </tr>
			  <tr>
                <td colspan="5" >&nbsp;</td>
              </tr>
              <tr>
                <th align="right" class="data">理,監事</th>
                <td class="data"><?php echo $row_DetailRS1['boardMember']; ?></td>
                <td class="data">&nbsp;</td>
                <th align="right" class="data">代表</th>
                <td class="data"><?php echo $row_DetailRS1['representative']; ?></td>             
              </tr>
              <tr>
                <td colspan="5" >&nbsp;</td>
              </tr>
              <tr>
                <th colspan="4" align="right" class="data">最新更改會員資料日期</th>
                <td class="data"><?php echo $row_DetailRS1['changeDate']; ?></td>
              </tr>
            </tbody>
        </table></td>
        <td>&nbsp;&nbsp;</td>
        <td>
        	<div class="wrapper" style="overflow: auto; height: 380px" >
        	<table class="data" id="referrals" >
            <thead class="data">
              <tr class="data">
                <th class="data, left">被推薦人</th>
              </tr>
            </thead>
            <tbody class="data">
              <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
                <tr class="data">
                  <td class="data"><a href="memberDETAIL.php?idNumber=<?php echo $row_rsReferrals['idNumber'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>"><?php echo $row_rsReferrals['name']; ?></a></td>
                </tr>
                <?php } while ($row_rsReferrals = mysqli_fetch_assoc($rsReferrals)); ?>
            </tbody>
          </table></div></td>
      </tr>
    </table>
    <br />
    <table class="data" >
      <thead class="data">
        <tr class="data">
          <th colspan="10" class="data, left">眷屬資料</th>
        </tr>
        <tr class="data">
          <th class="data">身分證字號</th>
          <th class="data">姓名</th>
          <th class="data">卡號</th>
          <th class="data">出生日</th>
          <th class="data">殘障</th>
          <th class="data">加保日</th>
          <th class="data">入會日</th>
          <th class="data">退會日</th>
          <th colspan="2" class="data"><a href="dependentINSERT.php?recordID=<?php 
			  echo $row_DetailRS1['idNumber'] . "&recordCardNum=" . $row_DetailRS1['cardNum'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>">增加眷屬</a></th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
          <tr class="data">
            <td class="data"><?php echo $row_rsDetailDependents['idNumber']; ?></td>
            <td class="data"><?php echo $row_rsDetailDependents['name']; ?></td>
            <td class="data"><?php echo $row_rsDetailDependents['cardNum']; ?></td>
            <td class="data"><?php echo $row_rsDetailDependents['birthday']; ?></td>
            <td class="data"><?php echo $row_rsDetailDependents['handiName']; ?></td>
            <td class="data"><?php echo $row_rsDetailDependents['insureDate']; ?></td>
            <td class="data"><?php echo $row_rsDetailDependents['memberDate']; ?></td>
            <td class="data"><?php 
            	if (isset($row_rsDetailDependents['idNumber'])) {
            	if (is_null($row_rsDetailDependents['inactive'])) {
                	echo '<a href="incomeRefundDepINSERT.php?recordID=' . 
                	$row_rsDetailDependents['ID'] . '&' . 
                	strstr($_SERVER['QUERY_STRING'], 'cardNum=') . '">眷屬退費</a>';
                	} else {
                	echo $row_rsDetailDependents['inactive'];
                	}
                }?>
                </td>
            <td class="data"><?php if (isset($row_rsDetailDependents['ID'])) { ?>
                <a href="dependentEDIT.php?recordID=<?php 
				echo $row_rsDetailDependents['ID'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>">編輯</a>
                <?php } ?></td>
            <td class="data"><?php if (isset($row_rsDetailDependents['ID'])) { ?>
                <a href="dependentDELETE.php?recordID=<?php 
				echo $row_rsDetailDependents['ID'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>">刪除</a>
                <?php } ?></td>
          </tr>
          <?php } while ($row_rsDetailDependents = mysqli_fetch_assoc($rsDetailDependents)); ?>
      </tbody>
    </table>
    <br />
    <div class="wrapper" style="overflow: auto; height: 250px; display: inline-block;" >
    <table class="data" id="dues" >
      <thead class="data">
        <tr class="data, left" >
          <th colspan="13" class="data">收入</th>
        </tr>
        <tr class="data" >
          <th class="data">年</th>
          <th class="data">月</th>
          <th class="data">上下半年</th>
          <th class="data">經常會費</th>
          <th class="data">勞保費</th>
          <th class="data">健保費</th>
          <th class="data">互助金</th>
          <th class="data">入會費</th>
          <th class="data">合計</th>
          <th class="data">繳款日期</th>
          <th class="data">匯款</th>
          <th colspan="2" class="data"><a href="incomeINSERT.php?idNumber=<?php 
			  	echo $row_DetailRS1['idNumber'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>">增加繳款記錄</a></th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
          <tr class="data">
            <td class="data"><?php echo $row_rsDetailIncome['duesYear']; ?></td>
            <td class="data"><?php echo $row_rsDetailIncome['monthNum']; 
			  			if ($row_rsDetailIncome['monthNum'] > 0): ?>月
              <?php endif; ?></td>
            <td class="data"><?php echo $row_rsDetailIncome['duesHalfName']; ?></td>
            <td align="right" class="data">$<?php echo number_format($row_rsDetailIncome['unionDues'],0); ?></td>
            <td align="right" class="data">$<?php echo number_format($row_rsDetailIncome['laborIns'],0); ?></td>
            <td align="right" class="data">$<?php echo number_format($row_rsDetailIncome['medIns'],0); ?></td>
            <td align="right" class="data">$<?php echo number_format($row_rsDetailIncome['newMemDues'],0); ?></td>
            <td align="right" class="data">$<?php echo number_format($row_rsDetailIncome['newMemDues2'],0); ?></td>
            <td align="right" class="data">$<?php echo number_format($row_rsDetailIncome['incomeTotal'],0); ?></td>
            <td class="data"><?php echo $row_rsDetailIncome['paidDate']; ?></td>
            <td align="center" class="data"><?php echo $row_rsDetailIncome['wire']; ?></td>
            <td class="data"><?php 
            	if ($row_rsDetailIncome['billType'] == 5): ?>
                <a href="incomeRefundEDIT.php?incomeID=<?php 
				echo $row_rsDetailIncome['ID'] . "&" .
				 strstr($_SERVER['QUERY_STRING'],"idNumber="); ?>">編輯</a>
                <?php else: ?>
                <a href="incomeEDIT.php?incomeID=<?php 
				echo $row_rsDetailIncome['ID'] . "&" . 
				strstr($_SERVER['QUERY_STRING'],"idNumber="); ?>">編輯</a>
                <?php endif; ?>
            </td>
            <td class="data"><?php if ((isset($row_rsDetailIncome['ID'])) && !(isset($row_rsDetailIncome['paidDate']))) {?>
              <a href="incomeDELETE.php?recordID=<?php 
				echo $row_rsDetailIncome['ID'] . "&" . $_SERVER['QUERY_STRING']; ?>">刪除</a>
              <?php } ?>
          </td>
          
          </tr>
          
          <?php } while ($row_rsDetailIncome = mysqli_fetch_assoc($rsDetailIncome)); ?>
      </tbody>
    </table>
    </div>
    <br />
    <table class="data">
      <thead class="data">
        <tr class="data">
          <th colspan="4" class="data, left">其它事務處理</th>
          <th colspan="3" class="data, right"><a href="transactionINSERT.php?refer=memberDETAIL&idNumber=<?php echo $row_DetailRS1['idNumber']; ?>">增加筆數</a></th>
        </tr>
        <tr class="data">
          <th class="data">日期</th>
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
            <td rowspan="2" class="data"><a href="<?php 
				  	echo "transactionEDIT.php?refer=memberDETAIL&recordID=" . $row_rsMemberTransactions['idMaster'] . "&" . $_SERVER['QUERY_STRING']; ?>"> <?php echo $row_rsMemberTransactions['transDate']; ?></a> </td>
            <td class="data"><?php echo $row_rsMemberTransactions['accountName1']; ?></td>
            <td class="data right"><?php echo $row_rsMemberTransactions['debit1']; ?></td>
            <td class="data right"><?php echo $row_rsMemberTransactions['credit1']; ?></td>
            <td rowspan="2" class="data"><?php echo $row_rsMemberTransactions['notes']; ?></td>
            <td rowspan="2" class="data"><?php echo $row_rsMemberTransactions['changeDate']; ?></td>
            <td rowspan="2" class="data"><?php if (is_null($row_rsMemberTransactions['marker'])) : ?>
              <a href="transactionDELETE.php?refer=memberDETAIL&recordID=<?php echo $row_rsMemberTransactions['idMaster'] . "&" . $_SERVER['QUERY_STRING']; ?>" title="Delete Entry">刪除</a>
              <?php endif; ?></td>
          </tr>
          <tr>
            <td class="data"><?php echo $row_rsMemberTransactions['accountName2']; ?></td>
            <td class="data right"><?php echo $row_rsMemberTransactions['debit2']; ?></td>
            <td class="data right"><?php echo $row_rsMemberTransactions['credit2']; ?></td>
          </tr>
          <tr>
            <td colspan="7"></td>
          </tr>
          <?php } while ($row_rsMemberTransactions = mysqli_fetch_assoc($rsMemberTransactions)); ?>
      </tbody>
    </table>
    <br />
    <table class="data" id="subsidy">
      <thead class="data">
        <tr class="data">
          <th colspan="3" class="data, left">給付</th>
          <th colspan="3" class="data, right"><a href="subsidyINSERT.php?recordID=<?php 
			  echo $row_DetailRS1['idNumber'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>">申請給付</a></th>
        </tr>
        <tr class="data">
          <th class="data">給付種類</th> <!-- subsidyTypeName -->
          <th class="data">申請日期</th> <!-- applicationDate -->
          <th class="data">核付日期</th> <!-- grantDate -->
          <th class="data">給付金額</th> <!-- subsidyAmount -->
          <th class="data">受理編號</th> <!-- grantID -->
		  <th class="data">&nbsp;</th>
        </tr>
      </thead>
      <tbody class="data">
        <?php do { 
					$class = ($class == 'dataOdd') ? 'data' : 'dataOdd'; ?>
          <tr class="<?php echo $class; ?>">
            <td class="data"><?php echo $row_rsSubsidy['subsidyTypeName']; ?></td>
            <td class="data"><?php echo $row_rsSubsidy['applicationDate']; ?></td>
            <td class="data"><?php echo $row_rsSubsidy['grantDate']; ?></td>
            <td class="data right">$<?php echo $row_rsSubsidy['subsidyAmount']; ?></td>
            <td class="data"><?php echo $row_rsSubsidy['grantID']; ?></td>
            <td class="data"><?php if (isset($row_rsSubsidy['id'])) { ?>
                <a href="subsidyEDIT.php?recordID=<?php 
				echo $row_rsSubsidy['id'] . "&" . strstr($_SERVER['QUERY_STRING'], 'cardNum='); ?>">編輯</a>
                <?php } ?></td>
          </tr>
          <?php } while ($row_rsSubsidy = mysqli_fetch_assoc($rsSubsidy)); ?>
      </tbody>
    </table>
    <!-- InstanceEndEditable --></div>
</div>
<!--end content -->
<!-- InstanceBeginEditable name="EditRegion3" -->
<div id="navBar">
<div id="sectionLinks">
    <ul>
      <li><a href="memberINSERT.php?referrer=<?php echo $row_DetailRS1['idNumber']; ?>">增加新會員</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>繳款通知單</h3>
    <ul>
      <li><a href="#" onclick="MM_openBrWindow('<?php echo $billMemberURL; ?>','繳款通知單','scrollbars=yes,resizable=yes')">繳款通知單</a></li>
      <li><form action="" method="get" name="receipt_report">
      	年<input id="yearR" type="text" size="3" maxlength="3" value="<?php echo date('Y') - 1912; ?>" />
      	<input id="idNumberR" type="hidden" value="<?php echo $row_DetailRS1['idNumber']; ?>" />
      	<a href="#" onclick="openReceipt('繳費證明單','scrollbars=yes,resizable=yes')">繳費證明單</a>
      	</form>
      </li>
      <li><a href="#" onclick="openHelpWindow('help','scrollbars=yes,resizable=yes,width=400,height=400')">輔助說明</a></li>
    </ul>
  </div>
  <div class="relatedLinks">
    <h3>郵寄名單地址之列印</h3>
    <ul>
      <li><a href="labels/labelMemberDOWNLOAD.php?recordID=<?php echo $row_DetailRS1['idNumber']; ?>">郵寄名單地址之列印數據資料合併</a></li>
      <li><a href="labels/Label_AE(2x10).doc">郵寄名單地址之列印 (2x10張)</a></li>
      <li><a href="help/UMS-Word_Merge_Instructions.pdf">合併列印操作說明</a></li>
    </ul>
  </div>
</div>
<!-- InstanceEndEditable -->
<!--end navbar -->
<br />
<script type="text/javascript">
var $table = $('#dues');
$table.floatThead({
    scrollContainer: function($table){
		return $table.closest('.wrapper');
	}
});

var $tableR = $('#referrals');
$tableR.floatThead({
    scrollContainer: function($tableR){
		return $tableR.closest('.wrapper');
	}
});
</script>
</body>
<!-- InstanceEnd --></html>
<?php
mysqli_free_result($rsDetailIncome);

mysqli_free_result($rsDetailDependents);

mysqli_free_result($DetailRS1);

mysqli_free_result($rsMemberTransactions);
?>

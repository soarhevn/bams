<?php require_once('../Connections/MySQL_Union.php'); ?>
<?php
//Written by Dan Zarrella. Some additional tweaks provided by JP Honeywell
//pear excel package has support for fonts and formulas etc.. more complicated
//this is good for quick table dumps (deliverables)

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
   $varAccountID_rsTransactions = 'AND ( t1.accountID IN (' . implode(',',$accountIDs) . ') OR t2.accountID IN (' . implode(',',$accountIDs) . ') ) ';
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
$query = sprintf("
SELECT idMaster, TRIM(LEADING '0' FROM (DATE_SUB(transDate, INTERVAL 1911 YEAR))) AS transDate,  CONCAT(aN1.accountName, ' - ', aT1.atName) AS accountName1, IF(debit1 > 0, FORMAT(debit1, 0), NULL) AS debit1, IF(credit1 > 0, FORMAT(credit1, 0), NULL) AS credit1, CONCAT(aN2.accountName, ' - ', aT2.atName) AS accountName2, notes
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
ORDER BY transdate, idMaster ASC", 
		$varAccountID_rsTransactions,	// 1
		$varTextFree_rsTransactions,	// 2
		$varDateFrom_rsTransactions,	// 3
		$varDateTo_rsTransactions,		// 4
		$varAccountGroupID_rsTranactions); //5
$result = mysqli_query($MySQL_Union, $query);

$count = mysqli_field_count($MySQL_Union);

for ($i = 0; $i < $count; $i++){
    $fieldInfo = mysqli_fetch_field_direct($result, $i);
    $header .= $fieldInfo->name."\t";
}

while($row = mysqli_fetch_row($result)){
  $line = '';
  foreach($row as $value){
    if(!isset($value) || $value == ""){
      $value = "\t";
    }else{
# important to escape any quotes to preserve them in the data.
      $value = str_replace('"', '""', $value);
# needed to encapsulate data in quotes because some data might be multi line.
# the good news is that numbers remain numbers in Excel even though quoted.
      $value = '"' . $value . '"' . "\t";
    }
    $line .= $value;
  }
  $data .= trim($line)."\n";
}
# this line is needed because returns embedded in the data have "\r"
# and this looks like a "box character" in Excel
  $data = str_replace("\r", "", $data);

# Nice to let someone know that the search came up empty.
# Otherwise only the column name headers will be output to Excel.
if ($data == "") {
  $data = "\nno matching records found\n";
}

# Now some coding to pass to Excel so it will do column totals.
/*for ($i = 0; $i < $count; $i++){
    if (
	$fieldInfo = mysqli_fetch_field_direct($result, $i);
	$totals .= $fieldInfo->name."\t";
	}
}
*/
# This line will stream the file to the user rather than spray it across the screen
header("Content-type: application/ms-excel;charset=utf-8");

# replace excelfile.xls with whatever you want the filename to default to
header("Content-Disposition: attachment; filename=Transactions.xls");
header("Pragma: no-cache");
header("Expires: 0");

# This line will place a BOM for utf-8 in the file
# $unicode_str_for_Excel = chr(239).chr(187).chr(191);

# This line will convert from utf-8 to little-endian utf-16le that MS Excel wants
$unicode_str_for_Excel = chr(255).chr(254).iconv("UTF-8", "UTF-16LE", $header."\n".$data);

echo $unicode_str_for_Excel
?>
<?php
mysqli_free_result($result);
?>

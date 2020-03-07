<?php require_once('../Connections/MySQL_Union.php'); ?>
<?php
//Written by Dan Zarrella. Some additional tweaks provided by JP Honeywell
//pear excel package has support for fonts and formulas etc.. more complicated
//this is good for quick table dumps (deliverables)

$varCardNum_rsMembers = "%";
if (isset($_GET['cardNum']) && $_GET['cardNum']) {
  $varCardNum_rsMembers = (get_magic_quotes_gpc()) ? $_GET['cardNum'] : addslashes($_GET['cardNum']);
}
$varID_rsMembers = "%";
if (isset($_GET['idNumber']) && $_GET['idNumber']) {
  $varID_rsMembers = (get_magic_quotes_gpc()) ? $_GET['idNumber'] : addslashes($_GET['idNumber']);
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
$varInactive_rsMembers = "AND members.inactive IS NULL";
if (isset($_GET['inactive'])) {
  $varInactive_rsMembers = (get_magic_quotes_gpc()) ? $_GET['inactive'] : addslashes($_GET['inactive']);
}
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query = sprintf("SELECT members.name, members.cardNum, members.address 
FROM members 
WHERE members.cardNum LIKE '%s%%' AND members.idNumber LIKE '%s' AND members.name LIKE '%%%s%%' AND members.salary LIKE '%s' AND members.insureHealth LIKE '%s' AND members.insureLabor LIKE '%s' %s ORDER BY members.cardNum ASC", 
	$varCardNum_rsMembers,
	$varID_rsMembers,
	$varName_rsMembers,
	$varSal_rsMembers,
	$varInsH_rsMembers,
	$varInsL_rsMembers,
	$varInactive_rsMembers);
$result = mysqli_query($MySQL_Union, $query);

$count = mysqli_field_count($MySQL_Union);

for ($i = 0; $i < $count; $i++){
    $fieldInfo = mysqli_fetch_field_direct($result, $i);
    $header .= $fieldInfo->name.",";
}

while($row = mysqli_fetch_row($result)){
  $line = '';
  foreach($row as $value){
    if(!isset($value) || $value == ""){
      $value = ",";
    }else{
# important to escape any quotes to preserve them in the data.
      $value = str_replace('"', '""', $value);
# needed to encapsulate data in quotes because some data might be multi line.
# the good news is that numbers remain numbers in Excel even though quoted.
      $value = '"' . $value . '"' . ",";
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

# This line will stream the file to the user rather than spray it across the screen
//header("Content-type: application/octet-stream;charset=utf-8");
header("Content-type: application/ms-excel;charset=utf-8");

# replace excelfile.xls with whatever you want the filename to default to
header("Content-Disposition: attachment; filename=labelMergeFile.txt");
header("Pragma: no-cache");
header("Expires: 0");

# This line will place a BOM for utf-8 in the file
# $unicode_str_for_Excel = chr(239).chr(187).chr(191);

# This line will convert from utf-8 to little-endian utf-16le that MS Excel wants
#$unicode_str_for_Excel = chr(255).chr(254).mb_convert_encoding( $header."\n".$data, 'UTF-16LE', 'UTF-8');
$unicode_str_for_Excel = chr(255).chr(254).iconv("UTF-8", "UTF-16LE", $header."\n".$data);

//echo $header."\n".$data; 
echo $unicode_str_for_Excel
?>
<?php
mysqli_free_result($result);
?>

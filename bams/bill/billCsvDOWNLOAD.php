<?php require_once('../Connections/MySQL_Union.php'); ?>
<?php
//Written by Dan Zarrella. Some additional tweaks provided by JP Honeywell
//pear excel package has support for fonts and formulas etc.. more complicated
//this is good for quick table dumps (deliverables)

//include('DB_connection.php');
mysqli_select_db($MySQL_Union, $database_MySQL_Union);
$query = sprintf("SELECT  members.idNumber, members.cardNum, members.name, duesYear - 1911 AS duesYearGH, IF(monthNum > 0, CONCAT(monthNum, '月'), NULL) AS monthNum, duesHalfName.duesHalfName, income.unionDues, income.laborIns, income.medIns, income.newMemDues, 
FORMAT( income.unionDues + income.laborIns + income.medIns + income.newMemDues, 0 ) AS total, IFNULL(d.numDeps,0)+1 AS numDeps, members.salary  
FROM  (income, members, duesHalfName)
LEFT JOIN 
(
SELECT idParent, COUNT(*) numDeps
FROM dependents
WHERE inactive IS NULL
GROUP BY idParent
) d
ON members.idNumber = d.idParent
WHERE (income.idNumber =  members.idNumber  
AND  duesHalfName.duesHalf =  income.duesHalf ) 
AND ( ( income.paidDate  IS NULL ) ) 
ORDER BY members.cardNum, income.duesHalf, income.monthNum ASC");
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
header("Content-Disposition: attachment; filename=billMergeFile.csv");
header("Pragma: no-cache");
header("Expires: 0");

# This line will place a BOM for utf-8 in the file
# $unicode_str_for_Excel = chr(239).chr(187).chr(191);

# This line will convert from utf-8 to little-endian utf-16le that MS Excel wants
$unicode_str_for_Excel = chr(255).chr(254).mb_convert_encoding( $header."\n".$data, 'UTF-16LE', 'UTF-8');

//echo $header."\n".$data; 
echo $unicode_str_for_Excel
?>
<?php
mysqli_free_result($result);
?>

<?php require_once('Connections/MySQL_Union.php'); ?>
<?php
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


$deleteSQL = sprintf("DELETE i
FROM income i
WHERE i.duesYear = (SELECT duesYear
FROM (
 SELECT COUNT(duesYear) AS duesYearCnt, duesYear, duesHalf FROM income WHERE paidDate IS NULL
 GROUP BY duesYear, duesHalf) getYear
WHERE duesYearCnt = (SELECT MAX(duesYearCnt)
 FROM (
 SELECT COUNT(duesYear) AS duesYearCnt, duesYear, duesHalf FROM income WHERE paidDate IS NULL
 GROUP BY duesYear, duesHalf) getCount)
)
AND i.duesHalf = (SELECT duesHalf
FROM (
 SELECT COUNT(duesYear) AS duesYearCnt, duesYear, duesHalf FROM income WHERE paidDate IS NULL
 GROUP BY duesYear, duesHalf) getYear
WHERE duesYearCnt = (SELECT MAX(duesYearCnt)
 FROM (
 SELECT COUNT(duesYear) AS duesYearCnt, duesYear, duesHalf FROM income WHERE paidDate IS NULL
 GROUP BY duesYear, duesHalf) getCount)
)
AND paidDate IS NULL");

  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
  $Result1 = mysqli_query($MySQL_Union, $deleteSQL) or die(mysqli_error($MySQL_Union));

  $deleteGoTo = "ratesINSERT.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $deleteGoTo .= (strpos($deleteGoTo, '?')) ? "&" : "?";
    $deleteGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $deleteGoTo));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Delete Rates</title>
</head>
<body>
</body>
</html>

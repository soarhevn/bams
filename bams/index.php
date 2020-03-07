<?php require_once('Connections/MySQL_Union.php'); 
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

// replaces deprecated mysql_result
function mysqli_result($res,$row=0,$col=0){ 
  $numrows = mysqli_num_rows($res); 
  if ($numrows && $row <= ($numrows-1) && $row >=0){
      mysqli_data_seek($res,$row);
      $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
      if (isset($resrow[$col])){
          return $resrow[$col];
      }
  }
  return false;
}

// *** Validate request to login to this site.
if (!isset($_SESSION)) {
  session_start();
}

$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}

if (isset($_POST['username'])) {
  $loginUsername=$_POST['username'];
  $password=$_POST['password'];
  $MM_fldUserAuthorization = "access";
  $MM_redirectLoginSuccess = "memberSEARCH.php";
  $MM_redirectLoginFailed = "index.php";
  $MM_redirecttoReferrer = true;
  mysqli_select_db($MySQL_Union, $database_MySQL_Union);
        
  $LoginRS__query=sprintf("SELECT username, passwd, access FROM userAuth WHERE username=%s AND passwd=%s",
  GetSQLValueString($MySQL_Union, $loginUsername, "text"), GetSQLValueString($MySQL_Union, $password, "text")); 
   
  $LoginRS = mysqli_query($MySQL_Union, $LoginRS__query) or die(mysqli_error($MySQL_Union));
  $loginFoundUser = mysqli_num_rows($LoginRS);
  if ($loginFoundUser) {
    
    $loginStrGroup  = mysqli_result($LoginRS,0,'access');
    
    //declare two session variables and assign them
    $_SESSION['MM_Username'] = $loginUsername;
    $_SESSION['MM_UserGroup'] = $loginStrGroup;       

    if (isset($_SESSION['PrevUrl']) && true) {
      $MM_redirectLoginSuccess = $_SESSION['PrevUrl'];  
    }
    header("Location: " . $MM_redirectLoginSuccess );
  }
  else {
    header("Location: ". $MM_redirectLoginFailed );
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta name="generator" content=
  "HTML Tidy for Mac OS X (vers 31 October 2006 - Apple Inc. build 15.3.6), see www.w3.org" />

  <title>工會系統登入 - Union Member System Login</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link href="assets/css/2col_leftNav.css" rel="stylesheet" type="text/css" />
  <style type="text/css">
/*<![CDATA[*/
  table{background-color:#CCFFFF; width:100%; border: 1px dotted #CCCCCC; border-collapse: collapse;}
  td{
        border: 1px dotted #CCCCCC;
        vertical-align: top;
        white-space: nowrap;
  }
  /*]]>*/
  </style>
  <script type="text/javascript" src="assets/javascript/focus_field.js">
</script>
  <script type="text/javascript">
//<![CDATA[
  window.onload = initFormFieldFocus;
  function initFormFieldFocus()
  {
        focusField(document.getElementById("username"));

        return true;
  }
  //]]>
  </script>
</head>

<body>
  <div id="masthead">
    <h1 id="siteName">音樂工會</h1>
  </div><br />

  <form action="<?php echo $editFormAction; ?>" method="post" id="userAuthForm" name=
  "userAuthForm">
    <table>
      <tr>
        <td>
          <h2>登入</h2>
        </td>

        <td>
          <h2>Sign In</h2>
        </td>
      </tr>

      <tr>
        <td>
          <label for="username"><strong>使用者名稱</strong></label> &nbsp;<br />
          <input id="username" name="username" type="text" size="25" /><br />
          <label for="password"><strong>密碼</strong></label> &nbsp;<br />
          <input id="password" name="password" type="password" size="25" />

          <p><input type="submit" name="ButtonName" value="登入" /></p>
        </td>

        <td>
          <h4>指令說明:</h4>請放入你的使用者名稱和密碼以便登入.
        </td>
      </tr>
    </table>
  </form>

</body>
</html>

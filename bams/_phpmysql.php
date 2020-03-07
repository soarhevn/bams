<?php
$con = mysqli_connect("db","bams","bamspassword");
if (!$con)
{
 die('Could not connect: ' . mysqli_error($MySQL_Union)());
}
else
{
 echo "Congrats! connection established successfully";
}
mysqli_close($con);
?>
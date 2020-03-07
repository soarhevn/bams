<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
# $hostname_MySQL_Union = ":/var/run/mysqld/mysqld.sock";
$hostname_MySQL_Union = "db";
$database_MySQL_Union = "bams";
$username_MySQL_Union = "bams";
$password_MySQL_Union = "bamspassword";
$MySQL_Union = mysqli_connect($hostname_MySQL_Union, $username_MySQL_Union, $password_MySQL_Union, $database_MySQL_Union) or trigger_error(mysqli_error($MySQL_Union)(),E_USER_ERROR);
mysqli_query($MySQL_Union, 'SET NAMES utf8');
$MySQL_Enviro = "prod"; 
?>
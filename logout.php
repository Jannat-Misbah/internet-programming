<?php
require_once 'functions.php';
session_unset();  //تفريغ محتويات الجلسة
session_destroy(); 
redirect('index.php');
?>
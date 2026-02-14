<?php
// logout.php
require_once 'config/session.php';

Session::start();
Session::destroy();

header("Location: index.php");
exit();
?>
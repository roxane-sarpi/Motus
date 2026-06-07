<?php
require_once 'Security.php';

startSecureSession();
destroySessionAndCookie();

header("Location: login.php");
exit();

<?php
// File: clear_session.php
session_start();
session_destroy();
echo "Session cleared! <a href='/views/auth/login.php'>Login again</a>";
?>
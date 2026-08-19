<?php
session_start();
session_unset();
session_destroy();
header('Location: /Gestion_scolaire/frontend/login.php');
exit;

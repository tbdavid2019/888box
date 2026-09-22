<?php
session_start();

if (empty($_SESSION['loggedin'])) {
    require 'login.php';
    exit;
}

// Seal controls now live beside each asset's password controls.
header('Location: /admin/index.php?notice=seal_controls_moved');
exit;

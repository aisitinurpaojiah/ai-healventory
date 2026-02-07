<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}

switch ($_SESSION['user_role']) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;

    case 'manager':
        header("Location: manager/dashboard_manager.php");
        break;

    case 'staff':
        header("Location: staff/dashboard_staff.php");
        break;

    default:
        session_destroy();
        header("Location: login.php");
        break;
}

exit;

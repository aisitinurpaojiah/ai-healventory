<?php

$basePath = (strpos($_SERVER['REQUEST_URI'], '/public/admin') !== false ||
    strpos($_SERVER['REQUEST_URI'], '/public/manager') !== false ||
    strpos($_SERVER['REQUEST_URI'], '/public/staff') !== false)
    ? '../'
    : '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healventory</title>

    <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
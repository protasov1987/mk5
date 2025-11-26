<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Трекер маршрутных карт ТСЗП</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<header>
    <div class="header-left">
        <h1>Трекер маршрутных карт ТСЗП</h1>
        <div id="realtime-clock" class="clock-display" aria-live="polite"></div>
    </div>
    <nav>
        <button class="nav-btn active" data-target="dashboard">Дашборд</button>
        <button class="nav-btn" data-target="cards">Тех. карты</button>
        <button class="nav-btn" data-target="workorders">Трекер</button>
        <button class="nav-btn" data-target="archive">Архив</button>
    </nav>
</header>
<div id="server-status" class="status-banner status-info hidden" role="status" aria-live="polite"></div>
<main>

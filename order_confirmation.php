<?php
session_start();

// Получаем ID заказа
$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header("Location: catalog.php");
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=fooddelivery', 'root', 'a25d0c');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получаем заказ из базы данных
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: catalog.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение заказа</title>
    <link rel="stylesheet" href="css/order_confirmation.css">
</head>
<body>
    <h2>Ваш заказ №<?= htmlspecialchars($order['id']) ?> оформлен</h2>
    <p>Спасибо за покупку! Мы скоро свяжемся с вами для подтверждения.</p>

    <p><strong>Сумма заказа: <?= htmlspecialchars($order['total_price']) ?> руб.</strong></p>
    <p><a href="catalog.php">Перейти в каталог</a></p>
</body>
</html>

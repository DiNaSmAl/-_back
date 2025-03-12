<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'vendor/autoload.php'; // Подключаем PHPMailer и PhpSpreadsheet

$message = '';

session_start();
$pdo = new PDO('mysql:host=localhost;dbname=fooddelivery', 'root', 'a25d0c');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Проверка, что корзина не пуста
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: catalog.php");
    exit();
}

// Проверка, что пользователь авторизован
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Перенаправление на страницу авторизации
    exit();
}

// Инициализация переменной для ошибок
$errors = [];
$totalPrice = 0; // Переменная для расчета общей суммы

// Получаем информацию о товарах из корзины для расчета общей стоимости
foreach ($_SESSION['cart'] as $productId => $quantity) {
    // Получаем цену товара из базы данных
    $stmt = $pdo->prepare("SELECT price FROM menu WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Умножаем цену товара на количество и добавляем к общей сумме
        $totalPrice += $product['price'] * $quantity;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Валидация данных формы
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $userMail = trim($_POST['mail'] ?? '');

    if (empty($name)) {
        $errors[] = "Введите ваше имя.";
    }
    if (empty($phone)) {
        $errors[] = "Введите ваш телефон.";
    }
    if (empty($address)) {
        $errors[] = "Введите адрес доставки.";
    }
    if (empty($userMail)) {
        $errors[] = "Введите почту.";
    }

    // Если ошибок нет, сохраняем заказ
    if (empty($errors)) {
        try {
            // Вставляем заказ в таблицу orders
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_date, status, total_price) VALUES (?, NOW(), ?, ?)");
            $stmt->execute([$_SESSION['user'], 'new', $totalPrice]);

            // Получаем ID нового заказа
            $orderId = $pdo->lastInsertId();

            // Добавляем товары в заказ (в таблицу order_items)
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                // Получаем цену товара
                $stmt = $pdo->prepare("SELECT price FROM menu WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$orderId, $productId, $quantity, $product['price']]);
                }
            }

            // Очистить корзину после оформления заказа
            unset($_SESSION['cart']);

            // Создаем CSV-файл с данными заказа
            $filename = "order_$orderId.csv";
            $file = fopen($filename, 'w');

            // Устанавливаем кодировку UTF-8 с BOM для корректного отображения в Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Записываем заголовки
            fputcsv($file, ['Номер заказа', 'Имя', 'Телефон', 'Адрес', 'Почта', 'Общая сумма'], ';');

            // Записываем данные заказа
            fputcsv($file, [$orderId, $name, $phone, $address, $userMail, $totalPrice], ';');

            fclose($file);

            // Перенаправить на страницу подтверждения заказа
            header("Location: order_confirmation.php?order_id=$orderId");
            exit();
        } catch (PDOException $e) {
            // Если ошибка подключения или запроса, выводим сообщение
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        } catch (Exception $e) {
            // Если ошибка при создании CSV-файла
            $errors[] = 'Ошибка при создании CSV-файла: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
    <link rel="stylesheet" href="css/order.css">
</head>
<body>
    <h2>Оформление заказа</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Имя:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>

        <label for="phone">Телефон:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required>
        
        <label for="mail">Почта:</label>
        <input type="text" id="mail" name="mail" value="<?= htmlspecialchars($userMail ?? '') ?>" required>

        <label for="address">Адрес доставки:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($address ?? '') ?>" required>

        <button type="submit">Оформить заказ</button>
    </form>

    <p><strong>Сумма заказа: <?= number_format($totalPrice, 2, '.', ' ') ?> руб.</strong></p>

    <p><a href="catalog.php">Вернуться в каталог</a></p>
</body>
</html>
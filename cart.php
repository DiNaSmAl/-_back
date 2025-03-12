<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=fooddelivery', 'root', 'a25d0c');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Логика обработки POST-запросов (добавление товара, удаление и очистка корзины)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_id'])) {
    $productId = (int) $_POST['product_id'];
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }
    header("Location: catalog.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove'])) {
    $productId = (int) $_POST['remove'];
    unset($_SESSION['cart'][$productId]);
    header("Location: cart.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clear'])) {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}

// Если корзина пуста
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<h2>Корзина пуста</h2>";
    echo '<a href="catalog.php">Вернуться в каталог</a>';
    exit();
}

// Получаем список товаров из корзины
$productIds = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$query = "SELECT * FROM menu WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($query);
$stmt->execute($productIds);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Рассчитываем общую сумму
$totalPrice = 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <h2>Ваша корзина</h2>
    <table border="1">
        <tr>
            <th>Изображение</th>
            <th>Название</th>
            <th>Цена</th>
            <th>Количество</th>
            <th>Удалить</th>
        </tr>
        <?php
        foreach ($products as $product):
            $productId = $product['id'];
            $quantity = $_SESSION['cart'][$productId];
            $totalPrice += $product['price'] * $quantity;
            ?>
            <tr>
                <td>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" width="50">
                </td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= htmlspecialchars($product['price']) ?> руб.</td>
                <td><?= $quantity ?></td>
                <td>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="remove" value="<?= $productId ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>Общая сумма: <?= $totalPrice ?> руб.</h3>

    <form method="POST" action="cart.php">
        <input type="hidden" name="clear" value="1">
        <button type="submit">Очистить корзину</button>
    </form>

    <!-- Добавляем кнопку для перехода к оформлению заказа -->
    <form method="GET" action="order.php">
        <button type="submit">Оформить заказ</button>
    </form>

    <a href="catalog.php">Вернуться в каталог</a>
</body>
</html>
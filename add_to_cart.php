<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_GET['product_id'];

// Cek apakah produk adalah bagian dari flash sale
$current_time = date('Y-m-d H:i:s');
$flash_sale_sql = "SELECT id, stock FROM flash_sales WHERE product_id = ? AND start_time <= ? AND end_time >= ? AND stock > 0";
$flash_sale_stmt = $conn->prepare($flash_sale_sql);
$flash_sale_stmt->bind_param("iss", $product_id, $current_time, $current_time);
$flash_sale_stmt->execute();
$flash_sale_result = $flash_sale_stmt->get_result();

if ($flash_sale_result->num_rows > 0) {
    // Produk adalah bagian dari flash sale
    $flash_sale = $flash_sale_result->fetch_assoc();
    $flash_sale_id = $flash_sale['id'];
    $stock = $flash_sale['stock'];

    if ($stock <= 0) {
        // Stok habis, redirect kembali dengan pesan error
        header("Location: index.php?error=stock_habis");
        exit();
    }

    // Kurangi stok
    $update_stock_sql = "UPDATE flash_sales SET stock = stock - 1 WHERE id = ?";
    $update_stock_stmt = $conn->prepare($update_stock_sql);
    $update_stock_stmt->bind_param("i", $flash_sale_id);
    $update_stock_stmt->execute();
    $update_stock_stmt->close();
}

// Tambahkan produk ke keranjang
$check_cart_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
$check_cart_stmt = $conn->prepare($check_cart_sql);
$check_cart_stmt->bind_param("ii", $user_id, $product_id);
$check_cart_stmt->execute();
$check_cart_result = $check_cart_stmt->get_result();

if ($check_cart_result->num_rows > 0) {
    // Jika produk sudah ada di keranjang, tambahkan kuantitas
    $update_cart_sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
    $update_cart_stmt = $conn->prepare($update_cart_sql);
    $update_cart_stmt->bind_param("ii", $user_id, $product_id);
    $update_cart_stmt->execute();
    $update_cart_stmt->close();
} else {
    // Jika produk belum ada di keranjang, tambahkan baru
    $insert_cart_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
    $insert_cart_stmt = $conn->prepare($insert_cart_sql);
    $insert_cart_stmt->bind_param("ii", $user_id, $product_id);
    $insert_cart_stmt->execute();
    $insert_cart_stmt->close();
}

$flash_sale_stmt->close();
header("Location: index.php");
exit();
?>
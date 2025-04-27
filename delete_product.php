<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

$product_id = $_GET['id'];
$seller_id = $_SESSION['user_id'];

// Hapus foto produk
$delete_images_sql = "DELETE FROM product_images WHERE product_id=$product_id";
mysqli_query($conn, $delete_images_sql);

// Hapus produk
$delete_product_sql = "DELETE FROM products WHERE id=$product_id AND seller_id=$seller_id";
if (mysqli_query($conn, $delete_product_sql)) {
    header("Location: seller.php");
    exit();
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
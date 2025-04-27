<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Hapus item dari keranjang
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $delete_sql = "DELETE FROM cart WHERE id=$cart_id AND user_id=$user_id";
    mysqli_query($conn, $delete_sql);
    header("Location: cart.php");
    exit();
}

// Tambah jumlah di keranjang
if (isset($_GET['increase'])) {
    $cart_id = $_GET['increase'];
    $update_sql = "UPDATE cart SET quantity = quantity + 1 WHERE id=$cart_id AND user_id=$user_id";
    mysqli_query($conn, $update_sql);
    header("Location: cart.php");
    exit();
}

// Kurangi jumlah di keranjang
if (isset($_GET['decrease'])) {
    $cart_id = $_GET['decrease'];
    $update_sql = "UPDATE cart SET quantity = quantity - 1 WHERE id=$cart_id AND user_id=$user_id AND quantity > 1";
    mysqli_query($conn, $update_sql);
    header("Location: cart.php");
    exit();
}

// Checkout
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    // Hitung total
    $total_sql = "SELECT SUM(p.price * c.quantity) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id=$user_id";
    $total_result = mysqli_query($conn, $total_sql);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_amount = $total_row['total'];

    // Buat pesanan
    $order_sql = "INSERT INTO orders (user_id, name, address, phone, payment_method, total_amount) VALUES ($user_id, '$name', '$address', '$phone', '$payment_method', $total_amount)";
    if (mysqli_query($conn, $order_sql)) {
        $order_id = mysqli_insert_id($conn);

        // Tambah detail pesanan
        $cart_sql = "SELECT c.*, p.price, p.seller_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id=$user_id";
        $cart_result = mysqli_query($conn, $cart_sql);
        while ($cart_row = mysqli_fetch_assoc($cart_result)) {
            $product_id = $cart_row['product_id'];
            $quantity = $cart_row['quantity'];
            $price = $cart_row['price'];
            $seller_id = $cart_row['seller_id'];
            $detail_sql = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES ($order_id, $product_id, $quantity, $price)";
            mysqli_query($conn, $detail_sql);

            // Tambah notifikasi untuk penjual
            $seller_message = mysqli_real_escape_string($conn, "Your product with ID $product_id has been purchased by a buyer (Order ID: $order_id).");
            $seller_notify_sql = "INSERT INTO notifications (user_id, message) VALUES ($seller_id, '$seller_message')";
            if (!mysqli_query($conn, $seller_notify_sql)) {
                echo '<div class="alert alert-danger">Error creating seller notification: ' . mysqli_error($conn) . '</div>';
            }
        }

        // Tambah notifikasi untuk pembeli
        $buyer_message = mysqli_real_escape_string($conn, "Your order (Order ID: $order_id) has been placed successfully.");
        $buyer_notify_sql = "INSERT INTO notifications (user_id, message) VALUES ($user_id, '$buyer_message')";
        if (!mysqli_query($conn, $buyer_notify_sql)) {
            echo '<div class="alert alert-danger">Error creating buyer notification: ' . mysqli_error($conn) . '</div>';
        }

        // Kosongkan keranjang
        $clear_sql = "DELETE FROM cart WHERE user_id=$user_id";
        mysqli_query($conn, $clear_sql);

        header("Location: buyer.php");
        exit();
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

$cart_sql = "SELECT c.*, p.name, p.price, pi.image_path FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN product_images pi ON p.id = pi.product_id WHERE c.user_id=$user_id GROUP BY c.id";
$cart_result = mysqli_query($conn, $cart_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Gaming Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="buyer.php">Gaming Store</a>
            <div class="ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?> (Buyer)</span>
                <a href="buyer.php" class="btn btn-outline-light me-2">Continue Shopping</a>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Your Cart</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grand_total = 0;
                if (mysqli_num_rows($cart_result) > 0) {
                    while ($row = mysqli_fetch_assoc($cart_result)) {
                        $image = $row['image_path'] && file_exists("uploads/" . $row['image_path']) ? 'uploads/' . $row['image_path'] : 'https://via.placeholder.com/50';
                        $total = $row['price'] * $row['quantity'];
                        $grand_total += $total;
                        echo '<tr>';
                        echo '<td><img src="' . $image . '" alt="' . $row['name'] . '" style="width: 50px; height: 50px; object-fit: cover;"></td>';
                        echo '<td>' . $row['name'] . '</td>';
                        echo '<td>Rp ' . number_format($row['price'], 2) . '</td>';
                        echo '<td>';
                        echo '<a href="cart.php?decrease=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">-</a> ';
                        echo $row['quantity'];
                        echo ' <a href="cart.php?increase=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">+</a>';
                        echo '</td>';
                        echo '<td>Rp ' . number_format($total, 2) . '</td>';
                        echo '<td><a href="cart.php?remove=' . $row['id'] . '" class="btn btn-danger btn-sm">Remove</a></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">Your cart is empty.</td></tr>';
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                    <td><strong>Rp <?php echo number_format($grand_total, 2); ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <?php if (mysqli_num_rows($cart_result) > 0) { ?>
            <h3 class="mt-4">Checkout</h3>
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone" id="phone" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="address" rows="3" required></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" id="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="cod">Cash on Delivery</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary w-100">Place Order</button>
                    </div>
                </div>
            </form>
        <?php } ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
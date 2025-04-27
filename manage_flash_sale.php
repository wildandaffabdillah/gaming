<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = (int)$_POST['product_id'];
    $discount = isset($_POST['discount']) ? (int)$_POST['discount'] : 0;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0; // Tambahkan stok
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Validasi bahwa product_id ada di tabel products
    $check_product_sql = "SELECT id FROM products WHERE id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_product_sql);
    $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows === 0) {
        echo '<div class="alert alert-danger">Invalid product selected.</div>';
    }
    // Validasi bahwa end_time lebih besar dari start_time
    elseif (strtotime($end_time) <= strtotime($start_time)) {
        echo '<div class="alert alert-danger">End time must be after start time.</div>';
    }
    // Validasi bahwa discount berada dalam rentang 0-100
    elseif ($discount < 0 || $discount > 100) {
        echo '<div class="alert alert-danger">Discount must be between 0 and 100.</div>';
    }
    // Validasi bahwa stock tidak negatif
    elseif ($stock < 0) {
        echo '<div class="alert alert-danger">Stock cannot be negative.</div>';
    }
    else {
        $sql = "INSERT INTO flash_sales (product_id, discount, stock, start_time, end_time) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo '<div class="alert alert-danger">Prepare failed: ' . $conn->error . '</div>';
            exit();
        }
        $stmt->bind_param("iiiss", $product_id, $discount, $stock, $start_time, $end_time);
        if ($stmt->execute()) {
            header("Location: manage_flash_sale.php");
            exit();
        } else {
            echo '<div class="alert alert-danger">Execute failed: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}

$products_sql = "SELECT * FROM products WHERE seller_id={$_SESSION['user_id']}";
$products_result = mysqli_query($conn, $products_sql);
if (!$products_result) {
    echo '<div class="alert alert-danger">Error fetching products: ' . $conn->error . '</div>';
    exit();
}

$flash_sales_sql = "SELECT fs.*, p.name FROM flash_sales fs JOIN products p ON fs.product_id = p.id";
$flash_sales_result = mysqli_query($conn, $flash_sales_sql);
if (!$flash_sales_result) {
    echo '<div class="alert alert-danger">Error fetching flash sales: ' . $conn->error . '</div>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flash Sale - Gaming Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="seller.php">Gaming Store</a>
            <div class="ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?> (Seller)</span>
                <a href="seller.php" class="btn btn-outline-light me-2">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Flash Sale</h2>
        <form method="post" action="" class="mb-4">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="product_id" class="form-label">Product</label>
                    <select class="form-select" name="product_id" id="product_id" required>
                        <option value="">Select Product</option>
                        <?php while ($product = mysqli_fetch_assoc($products_result)) { ?>
                            <option value="<?php echo $product['id']; ?>"><?php echo $product['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="discount" class="form-label">Discount (%)</label>
                    <input type="number" class="form-control" name="discount" id="discount" min="0" max="100" value="0" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" class="form-control" name="stock" id="stock" min="0" value="0" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="datetime-local" class="form-control" name="start_time" id="start_time" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="datetime-local" class="form-control" name="end_time" id="end_time" required>
                </div>
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Add Flash Sale</button>
                </div>
            </div>
        </form>

        <h3>Active Flash Sales</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Discount</th>
                    <th>Stock</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($flash_sale = mysqli_fetch_assoc($flash_sales_result)) { ?>
                    <tr>
                        <td><?php echo $flash_sale['name']; ?></td>
                        <td><?php echo $flash_sale['discount']; ?>%</td>
                        <td><?php echo $flash_sale['stock']; ?></td>
                        <td><?php echo $flash_sale['start_time']; ?></td>
                        <td><?php echo $flash_sale['end_time']; ?></td>
                        <td>
                            <a href="delete_flash_sale.php?id=<?php echo $flash_sale['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
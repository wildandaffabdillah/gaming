<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            echo '<div class="alert alert-danger">Only JPG, JPEG, PNG, and GIF files are allowed.</div>';
            exit();
        }

        $new_file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO ads (product_id, image_path) VALUES ($product_id, '$new_file_name')";
            if (mysqli_query($conn, $sql)) {
                header("Location: manage_ads.php");
                exit();
            } else {
                echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Error uploading file.</div>';
        }
    }
}

$products_sql = "SELECT * FROM products WHERE seller_id={$_SESSION['user_id']}";
$products_result = mysqli_query($conn, $products_sql);

$ads_sql = "SELECT a.*, p.name FROM ads a JOIN products p ON a.product_id = p.id";
$ads_result = mysqli_query($conn, $ads_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ads - Gaming Store</title>
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
        <h2>Manage Ads</h2>
        <form method="post" action="" enctype="multipart/form-data" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="product_id" class="form-label">Product</label>
                    <select class="form-select" name="product_id" id="product_id" required>
                        <option value="">Select Product</option>
                        <?php while ($product = mysqli_fetch_assoc($products_result)) { ?>
                            <option value="<?php echo $product['id']; ?>"><?php echo $product['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="image" class="form-label">Ad Image</label>
                    <input type="file" class="form-control" name="image" id="image" accept="image/*" required>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Add Ad</button>
                </div>
            </div>
        </form>

        <h3>Active Ads</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ad = mysqli_fetch_assoc($ads_result)) { ?>
                    <tr>
                        <td><?php echo $ad['name']; ?></td>
                        <td><img src="uploads/<?php echo $ad['image_path']; ?>" alt="Ad Image" style="max-width: 100px;"></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $seller_id = $_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = $_POST['price'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $discount = isset($_POST['discount']) ? (int)$_POST['discount'] : 0;

    // Simpan data produk ke tabel products
    $sql = "INSERT INTO products (seller_id, name, category, price, description, discount) VALUES ('$seller_id', '$name', '$category', '$price', '$description', $discount)";

    if (mysqli_query($conn, $sql)) {
        $product_id = mysqli_insert_id($conn);

        // Proses upload foto
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
                $image_sql = "INSERT INTO product_images (product_id, image_path) VALUES ($product_id, '$new_file_name')";
                if (!mysqli_query($conn, $image_sql)) {
                    echo '<div class="alert alert-danger">Error saving image path: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                echo '<div class="alert alert-danger">Error uploading file. Check folder permissions or file size.</div>';
            }
        }

        header("Location: seller.php");
        exit();
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Gaming Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Add New Product</h2>
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="computer">Computer</option>
                                    <option value="gaming_chair">Gaming Chair</option>
                                    <option value="vga">VGA</option>
                                    <option value="cpu">CPU</option>
                                    <option value="laptop">Laptop</option>
                                    <option value="monitor">Monitor</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (Rp)</label>
                                <input type="number" class="form-control" name="price" id="price" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount (%)</label>
                                <input type="number" class="form-control" name="discount" id="discount" min="0" max="100" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="4"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="image" id="image" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add Product</button>
                        </form>
                        <p class="text-center mt-3"><a href="seller.php">Back to Seller Dashboard</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
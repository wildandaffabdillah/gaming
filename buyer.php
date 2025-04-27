<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data flash sale yang aktif
$current_time = date('Y-m-d H:i:s');
$flash_sales_sql = "SELECT fs.*, p.name, p.price, p.discount as product_discount, pi.image_path 
                    FROM flash_sales fs 
                    JOIN products p ON fs.product_id = p.id 
                    LEFT JOIN product_images pi ON p.id = pi.product_id 
                    WHERE fs.end_time >= '$current_time'";
$flash_sales_result = mysqli_query($conn, $flash_sales_sql);

// Ambil data produk dengan filter harga
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000000;

// Debug: Tampilkan nilai min_price dan max_price
echo "<p>Debug - Min Price: $min_price, Max Price: $max_price</p>";

$sql = "SELECT p.*, pi.image_path 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id 
        WHERE p.price >= $min_price AND p.price <= $max_price";
$result = mysqli_query($conn, $sql);

// Debug: Hitung jumlah produk yang diambil
$product_count = mysqli_num_rows($result);
echo "<p>Debug - Total Products Fetched: $product_count</p>";

// Hitung jumlah item di wishlist
$wishlist_count_sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id=$user_id";
$wishlist_count_result = mysqli_query($conn, $wishlist_count_sql);
$wishlist_count = mysqli_fetch_assoc($wishlist_count_result)['count'];

// Hitung jumlah item di cart
$cart_count_sql = "SELECT COUNT(*) as count FROM cart WHERE user_id=$user_id";
$cart_count_result = mysqli_query($conn, $cart_count_sql);
$cart_count = mysqli_fetch_assoc($cart_count_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer - Gaming Store</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Slider CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/11.0.2/css/bootstrap-slider.min.css">
    <!-- Custom CSS -->
    <style>
        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
        }

        .hero-section p {
            font-size: 1.2rem;
        }

        /* Card Styling */
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .product-card img {
            height: 200px;
            object-fit: cover;
        }

        .product-card .card-body {
            padding: 15px;
        }

        .product-card .card-title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .product-card .price {
            font-size: 1.1rem;
            color: #dc3545;
            font-weight: bold;
        }

        .product-card .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Flash Sale Section */
        .flash-sale-section {
            background-color: #ffe5e5;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }

        /* Filter Section */
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        /* Footer */
        .footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="buyer.php">Gaming Store</a>
            <div class="ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?> (Buyer)</span>
                <a href="wishlist.php" class="btn btn-outline-light me-2 position-relative">
                    <i class="fas fa-heart"></i> Wishlist
                    <?php if ($wishlist_count > 0) { ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $wishlist_count; ?>
                        </span>
                    <?php } ?>
                </a>
                <a href="cart.php" class="btn btn-outline-light me-2 position-relative">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if ($cart_count > 0) { ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php } ?>
                </a>
                <a href="order_history.php" class="btn btn-outline-light me-2">Order History</a>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>Welcome to Gaming Store</h1>
            <p>Discover the best gaming products at unbeatable prices!</p>
        </div>
    </div>

    <div class="container">
        <!-- Flash Sale Section -->
        <?php if (mysqli_num_rows($flash_sales_result) > 0) { ?>
            <div class="flash-sale-section">
                <h2 class="mb-4">Flash Sale</h2>
                <div class="row">
                    <?php while ($flash_sale = mysqli_fetch_assoc($flash_sales_result)) { 
                        $image = $flash_sale['image_path'] && file_exists("uploads/" . $flash_sale['image_path']) ? 'uploads/' . $flash_sale['image_path'] : 'https://via.placeholder.com/150';
                        $original_price = $flash_sale['price'];
                        $discount = max($flash_sale['product_discount'], $flash_sale['discount']);
                        $discounted_price = $original_price * (1 - $discount / 100);
                        $end_time = $flash_sale['end_time'];
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="product-card shadow">
                                <a href="product_detail.php?id=<?php echo $flash_sale['product_id']; ?>">
                                    <img src="<?php echo $image; ?>" class="card-img-top" alt="<?php echo $flash_sale['name']; ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title"><a href="product_detail.php?id=<?php echo $flash_sale['product_id']; ?>" class="text-decoration-none"><?php echo $flash_sale['name']; ?></a></h5>
                                    <p class="card-text">
                                        <span class="original-price">Rp <?php echo number_format($original_price, 2); ?></span>
                                        <span class="price">Rp <?php echo number_format($discounted_price, 2); ?> (<?php echo $discount; ?>% OFF)</span>
                                    </p>
                                    <p class="countdown" data-end-time="<?php echo $end_time; ?>">Ends in: <span class="time-remaining"></span></p>
                                    <a href="add_to_cart.php?product_id=<?php echo $flash_sale['product_id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart"></i> Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3>Filter by Price</h3>
            <form method="get" action="">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <input type="text" id="priceRange" name="priceRange" class="form-range" 
                               data-slider-min="0" 
                               data-slider-max="10000000" 
                               data-slider-step="10000" 
                               data-slider-value="[<?php echo $min_price; ?>,<?php echo $max_price; ?>]">
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="minPrice" name="min_price" value="<?php echo $min_price; ?>" readonly>
                            <span class="input-group-text">-</span>
                            <input type="number" class="form-control" id="maxPrice" name="max_price" value="<?php echo $max_price; ?>" readonly>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </form>
        </div>

        <!-- Products Section -->
        <h2 class="mb-4">Our Products</h2>
        <div class="row">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $image = $row['image_path'] && file_exists("uploads/" . $row['image_path']) ? 'uploads/' . $row['image_path'] : 'https://via.placeholder.com/150';
                    $original_price = $row['price'];
                    $discount = $row['discount'];
                    $discounted_price = $original_price * (1 - $discount / 100);
                    $is_in_wishlist = false;
                    $wishlist_check_sql = "SELECT * FROM wishlist WHERE user_id=$user_id AND product_id={$row['id']}";
                    $wishlist_check_result = mysqli_query($conn, $wishlist_check_sql);
                    if (mysqli_num_rows($wishlist_check_result) > 0) {
                        $is_in_wishlist = true;
                    }
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="product-card shadow">';
                    echo '<a href="product_detail.php?id=' . $row['id'] . '"><img src="' . $image . '" class="card-img-top" alt="' . $row['name'] . '"></a>';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title"><a href="product_detail.php?id=' . $row['id'] . '" class="text-decoration-none">' . $row['name'] . '</a></h5>';
                    echo '<p class="card-text">Category: ' . ucfirst($row['category']) . '</p>';
                    if ($discount > 0) {
                        echo '<p class="card-text">';
                        echo '<span class="original-price">Rp ' . number_format($original_price, 2) . '</span>';
                        echo '<span class="price">Rp ' . number_format($discounted_price, 2) . ' (' . $discount . '% OFF)</span>';
                        echo '</p>';
                    } else {
                        echo '<p class="price">Rp ' . number_format($original_price, 2) . '</p>';
                    }
                    echo '<div class="d-flex justify-content-between">';
                    echo '<a href="add_to_cart.php?product_id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart"></i> Add to Cart</a>';
                    echo '<a href="' . ($is_in_wishlist ? 'remove_from_wishlist.php' : 'add_to_wishlist.php') . '?product_id=' . $row['id'] . '" class="btn btn-outline-danger btn-sm">';
                    echo '<i class="fas fa-heart ' . ($is_in_wishlist ? 'text-danger' : '') . '"></i> ' . ($is_in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist');
                    echo '</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No products available in this price range.</p>';
            }

            mysqli_close($conn);
            ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© 2025 Gaming Store. All rights reserved.</p>
            <p>Follow us on <a href="#" class="text-white"><i class="fab fa-facebook"></i></a> <a href="#" class="text-white"><i class="fab fa-twitter"></i></a> <a href="#" class="text-white"><i class="fab fa-instagram"></i></a></p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Slider JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/11.0.2/bootstrap-slider.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Inisialisasi Bootstrap Slider untuk filter harga
        $("#priceRange").slider({
            tooltip: 'hide'
        }).on('slide', function(slideEvt) {
            $("#minPrice").val(slideEvt.value[0]);
            $("#maxPrice").val(slideEvt.value[1]);
        });

        // Countdown timer untuk flash sale
        document.querySelectorAll('.countdown').forEach(function(element) {
            const endTime = new Date(element.getAttribute('data-end-time')).getTime();
            const timer = element.querySelector('.time-remaining');

            function updateTimer() {
                const now = new Date().getTime();
                const distance = endTime - now;

                if (distance < 0) {
                    timer.innerHTML = "Expired";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timer.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        });
    </script>
</body>
</html>
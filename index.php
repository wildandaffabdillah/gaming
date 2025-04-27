<?php
include 'connection.php';

// Ambil data iklan
$ads_sql = "SELECT a.*, p.name FROM ads a JOIN products p ON a.product_id = p.id";
$ads_result = mysqli_query($conn, $ads_sql);

// Ambil data flash sale (termasuk yang belum dimulai dan sedang berlangsung)
$current_time = date('Y-m-d H:i:s');
$flash_sales_sql = "SELECT fs.*, p.name, p.price, p.discount as product_discount, pi.image_path 
                    FROM flash_sales fs 
                    JOIN products p ON fs.product_id = p.id 
                    LEFT JOIN product_images pi ON p.id = pi.product_id 
                    WHERE fs.end_time >= '$current_time'";
$flash_sales_result = mysqli_query($conn, $flash_sales_sql);

// Cek pesan error (jika stok habis)
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Store</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .stock-info {
            font-size: 1rem;
            color: #6c757d;
        }

        .stock-habis {
            font-size: 1rem;
            font-weight: bold;
            color: #dc3545;
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
            <a class="navbar-brand" href="index.php">Gaming Store</a>
            <div class="ms-auto">
                <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                <a href="register.php" class="btn btn-outline-light">Register</a>
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
        <!-- Pesan Error (Jika Stok Habis) -->
        <?php if ($error == 'stock_habis') { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Stok barang untuk flash sale telah habis!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <!-- Carousel untuk Iklan -->
        <?php if (mysqli_num_rows($ads_result) > 0) { ?>
            <div id="adCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php $first = true; while ($ad = mysqli_fetch_assoc($ads_result)) { ?>
                        <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                            <a href="product_detail.php?id=<?php echo $ad['product_id']; ?>">
                                <img src="uploads/<?php echo $ad['image_path']; ?>" class="d-block w-100" alt="<?php echo $ad['name']; ?>" style="height: 300px; object-fit: cover;">
                            </a>
                        </div>
                        <?php $first = false; } ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#adCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#adCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        <?php } ?>

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
                        $stock = $flash_sale['stock'];
                        $is_out_of_stock = $stock <= 0;
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
                                    <?php if ($is_out_of_stock) { ?>
                                        <p class="stock-habis">Stok Habis</p>
                                    <?php } else { ?>
                                        <p class="stock-info">Stok Tersisa: <?php echo $stock; ?></p>
                                        <a href="add_to_cart.php?product_id=<?php echo $flash_sale['product_id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart"></i> Add to Cart</a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <!-- Products Section -->
        <h2 class="mb-4">Our Products</h2>
        <div class="row">
            <?php
            $sql = "SELECT p.*, pi.image_path FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id GROUP BY p.id";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $image = $row['image_path'] && file_exists("uploads/" . $row['image_path']) ? 'uploads/' . $row['image_path'] : 'https://via.placeholder.com/150';
                    $original_price = $row['price'];
                    $discount = $row['discount'];
                    $discounted_price = $original_price * (1 - $discount / 100);
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
                    echo '<a href="login.php" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart"></i> Login to Buy</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No products available.</p>';
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
    <!-- Font Awesome JS (opsional, jika diperlukan) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- Custom JS -->
    <script>
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
<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8639623010963658"
     crossorigin="anonymous"></script>
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Restaurant",
  "name": "N'S Bliss Lounge",
  "image": "https://nsblisslounge.com/assets/images/logo.jpg",
  "url": "https://nsblisslounge.com/",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "KK504 ST",
    "addressLocality": "Kigali",
    "addressCountry": "RW"
  },
  "telephone": "+250795569628"
}
</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="8pcK5t6cBdNIgvoNwnikEY5ls_nk1G-tJd3_LnaTchs" />
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="1 day">
    <meta name="author" content="HIT">
    <meta name="keywords" content="N'SBLISS Lounge, Kigali restaurant, Kigali lounge, fresh food Kigali, fast food Kigali, self-ordering restaurant, KK504 Street restaurant, bar and grill Kigali, Kigali food, Kigali dining, best restaurants Kigali, order food Kigali, lounge near Kicukiro,lounge kigali,snacks kigali,burgers kigali,pizza,burgers,bar kigali,meat,chicken">
    <meta name="description" content="Explore NS Bliss Lounge's exquisite food and drink menu. Enjoy cocktails, fine dining, and live entertainment in Kigali's best lounge experience.">
    <title>N'S Bliss Lounge | Kigali's Premium Dining & Drinks</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- ICO fallback (for older browsers and Google) -->
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<!-- PNG for modern browsers -->
<link rel="icon" href="/favicon.png" type="image/png">

<link rel="canonical" href="https://nsblisslounge.com/" />
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>    <!-- Desktop Navigation Bar -->
    <nav class="navbar navbar-dark bg-dark sticky-top desktop-nav">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="N'S Bliss Lounge" height="40">
            </a>
            <ul class="navbar-nav ms-auto d-none d-lg-flex flex-row">
                <li class="nav-item mx-2">
                    <a class="nav-link active" href="<?= BASE_URL ?>">Home</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="<?= BASE_URL ?>/client.php">Menu</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="<?= BASE_URL ?>/about.php">About</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-lg-none">
        <a href="<?= BASE_URL ?>" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_URL ?>/client.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : '' ?>">
            <i class="fas fa-utensils"></i>
            <span>Menu</span>
        </a>
        <a href="<?= BASE_URL ?>/about.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : '' ?>">
            <i class="fas fa-info-circle"></i>
            <span>About</span>
        </a>
    </nav>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="cart-sidebar">
        <div class="cart-header">
            <h4>Your Order</h4>
            <button type="button" class="btn-close" id="closeCart"></button>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be dynamically inserted here -->
        </div>
        <div class="cart-footer">
            <div class="d-flex justify-content-between mb-2">
                <span>Total:</span>
                <span id="cartTotal">RWF0.00</span>
            </div>
            <div class="table-number mb-2" style="display: none;">
                <label for="tableNumber">Table Number:</label>
                <input type="number" class="form-control" id="tableNumber" min="1" required>
            </div>
            <button class="btn btn-primary w-100" id="checkout">Proceed to Checkout</button>
        </div>
    </div>

    <!-- Full-Screen Image Slider -->
    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="assets/images/slider1.jpg" class="d-block w-100" alt="..." loading="lazy">
            </div>
            <div class="carousel-item">
                <img src="assets/images/slider2.jpeg" class="d-block w-100" alt="..." loading="lazy">
            </div>
            <div class="carousel-item">
                <img src="assets/images/slider3.jpeg" class="d-block w-100" alt="..." loading="lazy">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- Features Section -->
    <section class="features py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Us</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-utensils fa-3x mb-3"></i>
                        <h3>Fresh Food</h3>
                        <p>We use only the freshest ingredients in all our dishes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-clock fa-3x mb-3"></i>
                        <h3>Quick Service</h3>
                        <p>Order and get served without waiting in line</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                        <h3>Easy Ordering</h3>
                        <p>Simple and intuitive self-ordering system</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p><?= SITE_NAME ?> is committed to providing the best dining experience through our innovative self-ordering system.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= BASE_URL ?>/client.php">Menu</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt"></i> N'SBLISS LOUNGE KK504 ST, Kigali</li>
                        <li><i class="fas fa-phone"></i> (250)795569628</li>
                        <li><i class="fas fa-envelope"></i> nsblisslounge@gmail.com</li>
                    </ul>
                </div>
                <div class="col-md-3">
                <h5>Follow Us</h5>
                <ul class="list-unstyled d-flex gap-3">
                    <li>
                        <a href="https://www.facebook.com/nsblisslounge" target="_blank" class="text-light">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.instagram.com/ns_bliss_lounge/" target="_blank" class="text-light">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://wa.me/250795569628" target="_blank" class="text-light">
                            <i class="fab fa-whatsapp fa-lg"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://twitter.com/nsblisslounge" target="_blank" class="text-light">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                    </li>
                </ul>
            </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

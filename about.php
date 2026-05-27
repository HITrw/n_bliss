<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="8pcK5t6cBdNIgvoNwnikEY5ls_nk1G-tJd3_LnaTchs" />
    <meta name="description" content="English">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="1 day">
    <meta name="author" content="HIT">
    <meta name="keywords" content="N'SBLISS Lounge, Kigali restaurant, Kigali lounge, fresh food Kigali, fast food Kigali, self-ordering restaurant, KK504 Street restaurant, bar and grill Kigali, Kigali food, Kigali dining, best restaurants Kigali, order food Kigali, lounge near Kicukiro,lounge kigali,snacks kigali,burgers kigali,pizza,burgers,bar kigali,meat,chicken">
    <meta name="description" content="Discover N'SBLISS Lounge – Kigali’s top destination for fresh food, fast service, and a seamless self-ordering experience. Browse our digital menu, order at your table, and enjoy a modern dining experience designed for your comfort and convenience.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8639623010963658"
     crossorigin="anonymous"></script>
    <title>About Us - <?= SITE_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/favicon.png" type="image/png">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>    <!-- Desktop Navigation -->
    <nav class="navbar navbar-dark bg-dark sticky-top desktop-nav">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="<?= SITE_NAME ?>" height="40">
            </a>
            <ul class="navbar-nav ms-auto d-none d-lg-flex flex-row">
                <li class="nav-item mx-2">
                    <a class="nav-link" href="<?= BASE_URL ?>">Home</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="<?= BASE_URL ?>/client.php">Menu</a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link active" href="<?= BASE_URL ?>/about.php">About</a>
                </li>
                <!-- <li class="nav-item mx-2">
                    <a class="nav-link cart-link" href="#" id="cartToggle">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count badge bg-primary">0</span>
                    </a>
                </li> -->
            </ul>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-lg-none">
        <a href="<?= BASE_URL ?>" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_URL ?>/client.php" class="mobile-nav-item">
            <i class="fas fa-utensils"></i>
            <span>Menu</span>
        </a>
        <a href="<?= BASE_URL ?>/about.php" class="mobile-nav-item active">
            <i class="fas fa-info-circle"></i>
            <span>About</span>
        </a>
    </nav>

    <!-- About Hero Section -->
    <section class="about-hero py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4">Welcome to <?= SITE_NAME ?></h1>
                    <p class="lead">Where tradition meets innovation in dining experience</p>
                </div>
                <div class="col-md-6">
                    <img src="<?= BASE_URL ?>/assets/images/slider1.jpg" alt="Restaurant Interior" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="our-story py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mb-4">Our Story</h2>
                    <p>Founded in 2025, <?= SITE_NAME ?> has been at the forefront of combining traditional dining with modern technology. Our journey began with a simple idea: to create a dining experience that's both personal and efficient.</p>
                    <p>What sets us apart is our innovative self-ordering system, allowing you to browse our menu and place orders at your own pace while maintaining the warm, personal service that defines fine dining.</p>
                </div>
                <div class="col-md-6">
                    <div class="ratio ratio-16x9">
                        <img src="<?= BASE_URL ?>/assets/images/slider1.jpg" alt="Our Story" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="our-values py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Our Values</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-heart fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Quality</h3>
                            <p class="card-text">We source only the finest ingredients and prepare them with care and attention to detail.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Community</h3>
                            <p class="card-text">We believe in creating a welcoming environment where everyone feels like family.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-leaf fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Sustainability</h3>
                            <p class="card-text">We're committed to environmentally conscious practices in all aspects of our operation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team Section -->
    <!--<section class="our-team py-5">-->
    <!--    <div class="container">-->
    <!--        <h2 class="text-center mb-5">Meet Our Team</h2>-->
    <!--        <div class="row g-4">-->
    <!--            <div class="col-md-3">-->
    <!--                <div class="card team-member">-->
    <!--                    <img src="<?= BASE_URL ?>/assets/images/slider1.jpg" class="card-img-top" alt="Head Chef">-->
    <!--                    <div class="card-body text-center">-->
    <!--                        <h5 class="card-title">John Doe</h5>-->
    <!--                        <p class="card-text">Head Chef</p>-->
    <!--                    </div>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--            <div class="col-md-3">-->
    <!--                <div class="card team-member">-->
    <!--                    <img src="<?= BASE_URL ?>/assets/images/slider1.jpg" class="card-img-top" alt="Sous Chef">-->
    <!--                    <div class="card-body text-center">-->
    <!--                        <h5 class="card-title">Jane Smith</h5>-->
    <!--                        <p class="card-text">Sous Chef</p>-->
    <!--                    </div>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--            <div class="col-md-3">-->
    <!--                <div class="card team-member">-->
    <!--                    <img src="<?= BASE_URL ?>/assets/images/slider1.jpg" class="card-img-top" alt="Restaurant Manager">-->
    <!--                    <div class="card-body text-center">-->
    <!--                        <h5 class="card-title">Mike Johnson</h5>-->
    <!--                        <p class="card-text">Restaurant Manager</p>-->
    <!--                    </div>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--            <div class="col-md-3">-->
    <!--                <div class="card team-member">-->
    <!--                    <img src="<?= BASE_URL ?>/assets/images/slider1.jpg" class="card-img-top" alt="Service Manager">-->
    <!--                    <div class="card-body text-center">-->
    <!--                        <h5 class="card-title">Sarah Williams</h5>-->
    <!--                        <p class="card-text">Service Manager</p>-->
    <!--                    </div>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</section>-->

    <!-- Contact Section -->
    <section class="contact py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Visit Us</h2>
            <div class="row">
                <div class="col-md-6">
                    <h3>Location</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Kicukiro Zinia KK504 St, Kigali</p>
                    <h3>Contact</h3>
                    <p>
                        <i class="fas fa-phone"></i> (250)795569628<br>
                        <i class="fas fa-envelope"></i> nsblisslounge@gmail.com
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="ratio ratio-4x3">
                        <iframe
  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.1234567890123!2d30.1014917!3d-1.9717977!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f17.0!3m3!1m2!1s0x19dca7001209c0e7%3A0x56035e6b58366c38!2sN%E2%80%99s+Bliss+Lounge!5e0!3m2!1sen!2srw!4v171xxxxxxx!5m2!1sen!2srw"
  style="border:0;"
  allowfullscreen=""
  loading="lazy">
</iframe>

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

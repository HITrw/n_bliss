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
    <title>N'S Bliss Menu Tabs</title>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8639623010963658"
     crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.png" type="image/png">
    
</head>
<body style="margin:0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color:#f9f9f9; padding-bottom: 80px;">

    <!-- Header -->
    <header style="text-align: center; padding: 2rem 1rem 1rem 1rem; background-color: #343a40; color: white;">
        <h1 style="margin: 0; font-size: 2rem;">Our Menu</h1>
        <p style="margin: 0.5rem 0 0 0;">Choose a category below</p>
    </header>

    <!-- Content Tabs -->
    <main style="display: flex; justify-content: center; padding: 2rem 1rem;">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap; justify-content: center;">
            <button onclick="location.href='drinks.pdf'" style="width: 140px; height: 140px; background: #00bcd4; border: none; border-radius: 20px; color: white; font-size: 1.2rem; font-weight: bold; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: 0.3s;">
                <i class="fas fa-cocktail" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                Drinks
            </button>
            <button onclick="location.href='New Menu Bliss.pdf'" style="width: 140px; height: 140px; background: #ff7043; border: none; border-radius: 20px; color: white; font-size: 1.2rem; font-weight: bold; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: 0.3s;">
                <i class="fas fa-hamburger" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                Foods
            </button>
        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-lg-none" style="position: fixed; bottom: 0; left: 0; right: 0; background: #1f1f1f; display: flex; justify-content: space-around; align-items: center; padding: 0.7rem 0; z-index: 1000; box-shadow: 0 -2px 10px rgba(0,0,0,0.2); border-top-left-radius: 15px; border-top-right-radius: 15px;">
        <a href="<?= BASE_URL ?>" style="color: #ccc; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 0.75rem;">
            <i class="fas fa-home" style="font-size: 1.4rem;"></i>
            <span>Home</span>
        </a>
        <a href="drinks.pdf" style="color: #00bcd4; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 0.75rem;">
            <i class="fas fa-cocktail" style="font-size: 1.4rem;"></i>
            <span>Drinks</span>
        </a>
        <a href="New Menu Bliss.pdf" style="color: #ff7043; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 0.75rem;">
            <i class="fas fa-hamburger" style="font-size: 1.4rem;"></i>
            <span>Foods</span>
        </a>
        <a href="<?= BASE_URL ?>/about.php" style="color: #ccc; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 0.75rem;">
            <i class="fas fa-info-circle" style="font-size: 1.4rem;"></i>
            <span>About</span>
        </a>
    </nav>

</body>
</html>

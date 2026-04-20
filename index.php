<?php
// Persistent session cookie (30 days)
$lifetime = 30 * 24 * 60 * 60;
session_set_cookie_params($lifetime, '/', '', false, true);
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>College Event Hub | Discover & Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.5;
        }
        /* Navbar - glassmorphic */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(79,70,229,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 16px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .logo h2 {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(120deg, #4f46e5, #0d9488);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            transition: 0.2s;
            font-size: 0.95rem;
        }
        .nav-links a:hover {
            color: #4f46e5;
        }
        .btn-outline-light {
            background: transparent;
            border: 1px solid #cbd5e1;
            padding: 6px 16px;
            border-radius: 40px;
        }
        .btn-primary-small {
            background: linear-gradient(95deg, #4f46e5, #06b6d4);
            padding: 6px 18px;
            border-radius: 40px;
            color: white !important;
        }
        /* Hero Section */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 40px;
            padding: 80px 40px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0f7fa 100%);
        }
        .hero-content {
            flex: 1;
            min-width: 280px;
        }
        .hero-content h1 {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.2;
            background: linear-gradient(120deg, #1e293b, #334155);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 20px;
        }
        .hero-content p {
            font-size: 1.1rem;
            color: #475569;
            margin-bottom: 30px;
            max-width: 500px;
        }
        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }
        .btn-primary {
            background: linear-gradient(95deg, #4f46e5, #06b6d4);
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.25s;
            box-shadow: 0 4px 10px rgba(79,70,229,0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
        }
        .btn-secondary {
            background: white;
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            color: #4f46e5;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #cbd5e1;
            transition: 0.25s;
        }
        .btn-secondary:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }
        .hero-image {
            flex: 1;
            min-width: 280px;
            max-width: 500px;
            height: 350px;
            overflow: hidden;
            border-radius: 32px;
            margin: auto;
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.15);
        }
        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.4s ease-in-out;
        }
        /* Features Section */
        .features {
            padding: 80px 40px;
            text-align: center;
        }
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #0f172a;
        }
        .section-subtitle {
            color: #475569;
            max-width: 600px;
            margin: 0 auto 50px auto;
            font-size: 1rem;
        }
        .feature-grid {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
        }
        .feature-card {
            background: white;
            border-radius: 28px;
            padding: 32px 24px;
            width: 280px;
            transition: all 0.3s ease;
            box-shadow: 0 12px 24px -12px rgba(0,0,0,0.08);
            border: 1px solid #eef2ff;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 35px -16px rgba(79,70,229,0.25);
            border-color: #c7d2fe;
        }
        .feature-icon {
            font-size: 2.8rem;
            background: linear-gradient(145deg, #4f46e5, #14b8a6);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 20px;
        }
        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .feature-card p {
            color: #5b6e8c;
            font-size: 0.9rem;
        }
        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #4f46e5, #0d9488);
            margin: 40px 40px 60px;
            border-radius: 48px;
            padding: 60px 40px;
            text-align: center;
            color: white;
        }
        .cta h2 {
            font-size: 2rem;
            margin-bottom: 16px;
        }
        .cta p {
            margin-bottom: 30px;
            opacity: 0.9;
        }
        .btn-cta {
            background: white;
            color: #4f46e5;
            padding: 12px 32px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-cta:hover {
            transform: scale(1.02);
            background: #f1f5f9;
        }
        /* Footer */
        footer {
            background: #0f172a;
            color: #cbd5e1;
            text-align: center;
            padding: 28px;
            font-size: 0.85rem;
        }
        /* Responsive */
        @media (max-width: 768px) {
            nav {
                padding: 16px 20px;
            }
            .hero {
                padding: 50px 20px;
                flex-direction: column;
                text-align: center;
            }
            .hero-content h1 {
                font-size: 2.2rem;
            }
            .hero-buttons {
                justify-content: center;
            }
            .features {
                padding: 50px 20px;
            }
            .cta {
                margin: 30px 20px;
                padding: 40px 20px;
            }
            .cta h2 {
                font-size: 1.6rem;
            }
        }
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .hero, .features, .cta {
            animation: fadeUp 0.6s ease forwards;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav>
    <div class="logo">
        <img src="logo.png" alt="College Logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2245%22 fill=%22%234f46e5%22/%3E%3Ctext x=%2250%22 y=%2267%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2248%22%3EE%3C/text%3E%3C/svg%3E'">
        <h2>EventHub</h2>
    </div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_events.php">Events</a>
        <?php if(isset($_SESSION['admin'])): ?>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn-outline-light">Logout</a>
        <?php elseif(isset($_SESSION['student_roll'])): ?>
            <a href="student_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn-outline-light">Logout</a>
        <?php else: ?>
            <a href="student_login.php" class="btn-outline-light">Student Login</a>
            <a href="admin_login.php" class="btn-primary-small">Admin</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero Section with fixed image slider -->
<div class="hero">
    <div class="hero-content">
        <h1>Your Gateway to<br>College Events</h1>
        <p>Discover hackathons, workshops, fests, and sports — all in one place. Register in seconds and never miss an opportunity.</p>
        <div class="hero-buttons">
            <?php if(!isset($_SESSION['student_roll']) && !isset($_SESSION['admin'])): ?>
                <a href="student_register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Get Started</a>
                <a href="view_events.php" class="btn-secondary"><i class="fas fa-calendar-alt"></i> Explore Events</a>
            <?php elseif(isset($_SESSION['student_roll'])): ?>
                <a href="student_dashboard.php" class="btn-primary"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="view_events.php" class="btn-secondary"><i class="fas fa-calendar-alt"></i> Browse Events</a>
            <?php elseif(isset($_SESSION['admin'])): ?>
                <a href="admin_dashboard.php" class="btn-primary"><i class="fas fa-chalkboard"></i> Admin Panel</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-image">
        <img id="slider" src="images/photo8.jpeg" alt="College Event" onerror="this.src='images/fallback.jpg'">
    </div>
</div>

<!-- Features -->
<div class="features">
    <h2 class="section-title">Why Choose EventHub?</h2>
    <p class="section-subtitle">Designed to make event discovery and registration effortless for every student.</p>
    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-search"></i></div>
            <h3>Easy Discovery</h3>
            <p>Find all events — from tech fests to cultural nights — in one centralized dashboard.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bolt"></i></div>
            <h3>Instant Registration</h3>
            <p>Sign up for events with a single click. No paperwork, no hassle.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bell"></i></div>
            <h3>Real‑time Alerts</h3>
            <p>Get notified about deadlines, approvals, and new events right on your dashboard.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-certificate"></i></div>
            <h3>Digital Certificates</h3>
            <p>Download participation certificates instantly after event completion.</p>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="cta">
    <h2>Ready to make your college life unforgettable?</h2>
    <p>Join thousands of students who use EventHub to stay ahead.</p>
    <?php if(!isset($_SESSION['student_roll'])): ?>
        <a href="student_register.php" class="btn-cta"><i class="fas fa-arrow-right"></i> Register Now</a>
    <?php else: ?>
        <a href="view_events.php" class="btn-cta"><i class="fas fa-calendar-alt"></i> Explore Events</a>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer>
    <p>© 2026 College Event Hub | Empowering student engagement</p>
</footer>

<!-- Image Slider Script -->
<script>
    const images = [
        "images/photo1.jpeg",
        "images/photo2.jpeg",
        "images/photo3.jpeg",
        "images/photo4.jpeg",
        "images/photo6.jpeg",
        "images/photo7.jpeg",
        "images/photo8.jpeg",
        "images/photo10.jpeg",
        "images/photo11.jpeg",
        "images/photo12.jpeg",
        "images/photo13.jpeg",
        "images/photo15.jpeg",
        "images/photo16.jpeg"
    ];

    // Preload images
    const preloadImages = [];
    images.forEach(src => {
        const img = new Image();
        img.src = src;
        preloadImages.push(img);
    });

    let currentIndex = 0;
    const slider = document.getElementById("slider");
    slider.src = images[0];

    function changeImage() {
        slider.style.opacity = "0";
        setTimeout(() => {
            currentIndex = (currentIndex + 1) % images.length;
            slider.src = images[currentIndex];
            slider.style.opacity = "1";
        }, 400);
    }
    setInterval(changeImage, 4000);

    slider.onerror = function() {
        this.src = "images/fallback.jpg";
    };
</script>

</body>
</html>
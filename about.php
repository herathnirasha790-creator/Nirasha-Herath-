<?php
session_start();

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? '';

// ✅ Avatar Logic: Check uploaded image first, then UI Avatars
$user_avatar_session = $_SESSION['user_avatar'] ?? '';
$user_avatar = '';
if (!empty($user_avatar_session) && file_exists($user_avatar_session)) {
    $user_avatar = $user_avatar_session;
} else {
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Royal Estate - Luxury Hotel & Events Kurunegala</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== YOUR ORIGINAL STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #fffef8; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c5a263, #8b691f); border-radius: 10px; }

        /* ========== UNIFIED NAVIGATION ========== */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 5%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar.scrolled {
            padding: 0.8rem 5%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 30px rgba(0,0,0,0.1);
        }
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #2c1810, #c5a263);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
        }
        .logo p {
            font-size: 0.7rem;
            color: #c5a263;
            letter-spacing: 3px;
            margin-top: -5px;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: #2c1810;
            font-weight: 500;
            transition: 0.3s;
            position: relative;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0%;
            height: 2px;
            background: linear-gradient(90deg, #c5a263, #8b691f);
            transition: 0.3s;
        }
        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }
        .nav-links a:hover {
            color: #c5a263;
        }
        .signin-btn {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white !important;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }
        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* ✅ CLICK-BASED DROPDOWN */
        .user-dropdown {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: default;
        }
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #c5a263;
            cursor: pointer;
        }
        .user-dropdown span {
            cursor: pointer;
            font-weight: 500;
            color: #2c1810;
        }
        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 220px;
            z-index: 100;
            display: none;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .dropdown-menu.show {
            display: block !important;
            opacity: 1;
            transform: translateY(0);
        }
        .dropdown-menu a {
            display: block;
            padding: 12px 16px;
            color: #2c1810;
            text-decoration: none;
            border-bottom: 1px solid #f5f0eb;
        }
        .dropdown-menu a:last-child { border-bottom: none; }
        .dropdown-menu a:hover {
            background: #f5f0eb;
            color: #c5a263;
        }
        .user-dropdown .fa-chevron-down {
            transition: transform 0.3s ease;
            cursor: pointer;
            font-size: 0.8rem;
            color: #888;
        }
        .user-dropdown .fa-chevron-down.rotate {
            transform: rotate(180deg);
        }

        /* ========== PAGE HEADER ========== */
        .page-header {
            height: 50vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 70px;
        }
        .page-header h1 { font-size: 4rem; animation: fadeInUp 0.8s ease; }
        .page-header p { font-size: 1.2rem; animation: fadeInUp 0.8s ease 0.2s both; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container { max-width: 1400px; margin: 0 auto; }

        /* ========== ABOUT CONTENT ========== */
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            padding: 5rem 5%;
        }
        .about-text h3 { font-size: 2rem; color: #2c1810; margin-bottom: 1.5rem; }
        .about-text p { color: #666; line-height: 1.8; margin-bottom: 1.5rem; }
        .about-text .signature { font-family: 'Playfair Display'; font-size: 1.5rem; color: #c5a263; margin-top: 2rem; }
        .about-image { position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .about-image img { width: 100%; transition: 0.5s; }
        .about-image:hover img { transform: scale(1.05); }
        .about-image .experience-badge {
            position: absolute;
            bottom: 30px;
            right: -20px;
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
        }

        /* ========== STATS ========== */
        .stats {
            background: linear-gradient(135deg, #2c1810, #1a0f0a);
            color: white;
            padding: 4rem 5%;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        .stat-item h3 { font-size: 3rem; color: #c5a263; margin-bottom: 0.5rem; }
        .stat-item p { font-size: 1.1rem; }

        /* ========== MISSION & VISION ========== */
        .mission-vision-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 4rem 5%;
        }
        .mv-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            transition: 0.3s;
            border: 1px solid #f0e5d8;
        }
        .mv-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .mv-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #c5a26320, #8b691f20);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .mv-icon i { font-size: 2.5rem; color: #c5a263; }
        .mv-card h3 { font-size: 1.5rem; margin-bottom: 1rem; color: #2c1810; }
        .mv-card p { color: #666; line-height: 1.6; }

        /* ========== CORE VALUES ========== */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 4rem 5%;
            background: #f9f5f0;
        }
        .value-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            transition: 0.3s;
        }
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #c5a26320, #8b691f20);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .value-icon i { font-size: 2.5rem; color: #c5a263; }
        .value-card h4 { font-size: 1.2rem; color: #2c1810; margin-bottom: 0.5rem; }
        .value-card p { color: #666; }

        /* ============================================================
           ✅ TEAM SECTION - STYLISH AVATAR WITH REAL IMAGES
           ============================================================ */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            padding: 4rem 5%;
        }
        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            text-align: center;
            transition: 0.4s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 2rem 1.5rem;
        }
        .team-card:hover { 
            transform: translateY(-12px);
            box-shadow: 0 20px 50px rgba(197, 162, 99, 0.2);
        }

        /* ✅ Stylish Avatar Container - Gradient Border + Glow */
        .team-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            margin: 0 auto 1.2rem;
            padding: 5px;
            background: linear-gradient(135deg, #c5a263, #8b691f, #f0d5a8);
            background-size: 200% 200%;
            animation: gradientBorder 4s ease infinite;
            box-shadow: 0 8px 30px rgba(197, 162, 99, 0.35);
            transition: all 0.4s ease;
            overflow: hidden;
        }
        @keyframes gradientBorder {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .team-card:hover .team-avatar {
            transform: scale(1.08) rotate(-3deg);
            box-shadow: 0 12px 45px rgba(197, 162, 99, 0.55);
        }
        .team-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
            border: 3px solid white;
            background: #f5f0eb;
        }
        /* Fallback if image doesn't load */
        .team-avatar .avatar-fallback {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            font-weight: 600;
            border: 3px solid white;
        }

        .team-card h3 {
            font-size: 1.3rem;
            color: #2c1810;
            margin-bottom: 0.3rem;
        }
        .team-card p {
            color: #c5a263;
            font-weight: 500;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }
        .team-social {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .team-social a {
            width: 38px;
            height: 38px;
            background: #f5f0eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c1810;
            transition: 0.3s;
            text-decoration: none;
        }
        .team-social a:hover { 
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white; 
            transform: translateY(-4px) scale(1.1);
            box-shadow: 0 5px 15px rgba(197, 162, 99, 0.3);
        }

        /* ========== FOOTER ========== */
        footer {
            background: #1a0f0a;
            color: #999;
            padding: 3rem 5% 1rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        .footer-col h4 { color: white; margin-bottom: 1rem; font-size: 1.2rem; }
        .footer-col a { display: block; color: #999; text-decoration: none; margin-bottom: 0.5rem; transition: 0.3s; }
        .footer-col a:hover { color: #c5a263; transform: translateX(5px); }
        .social-links { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-links a {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        .social-links a:hover { background: #c5a263; transform: translateY(-3px); }
        .copyright { text-align: center; padding-top: 2rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .nav-links {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                background: white;
                flex-direction: column;
                padding: 2rem;
                transition: 0.3s;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            .nav-links.active { left: 0; }
            .about-grid, .mission-vision-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 2.5rem; }
            .about-image .experience-badge { position: static; margin-top: 1rem; display: inline-block; }
            .team-avatar {
                width: 100px;
                height: 100px;
            }
        }

        /* ========== MODAL STYLES ========== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 30px;
            width: 90%;
            max-width: 480px;
            padding: 2rem 1.8rem;
            position: relative;
            animation: fadeInUp 0.3s;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover { color: #c5a263; }
        .modal-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
        }
        .tab-btn {
            flex: 1;
            text-align: center;
            background: none;
            border: none;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            color: #666;
        }
        .tab-btn.active {
            color: #c5a263;
            border-bottom: 2px solid #c5a263;
        }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .form-group { margin-bottom: 1.2rem; position: relative; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #2c1810; }
        .form-group input { width: 100%; padding: 12px; padding-right: 40px; border: 1px solid #e0d5cc; border-radius: 12px; }
        .password-toggle { position: absolute; right: 12px; top: 38px; cursor: pointer; color: #999; }
        .auth-btn { background: linear-gradient(135deg,#c5a263,#8b691f); color: white; width: 100%; padding: 12px; border: none; border-radius: 30px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 10px; margin-bottom: 1rem; display: none; }
        .register-link { text-align: center; margin-top: 1rem; font-size: 0.85rem; }
        .register-link a { color: #c5a263; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- ✅ UNIFIED NAVIGATION -->
<!-- ============================================================ -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo">
            <h1>ROYAL ESTATE</h1>
            <p>LUXURY & ELEGANCE</p>
        </div>
        <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php">Packages</a>
            <a href="about.php" class="active">About</a>
            <a href="contact.php">Contact</a>
            <a href="reviews.php">Reviews</a>

            <?php if ($is_logged_in): ?>
                <div class="user-dropdown">
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="user-avatar-small" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=c5a263&color=fff&size=40&font-size=0.4&bold=true'">
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown-menu">
                        <?php if ($user_role === 'admin' || $user_role === 'owner'): ?>
                            <a href="admin/index.php">Admin Dashboard</a>
                        <?php else: ?>
                            <a href="user/dashboard.php">My Dashboard</a>
                        <?php endif; ?>
                        <a href="user/profile.php">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="signin-btn" id="openLoginBtn">
                    <i class="fas fa-user"></i> Sign In
                </button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ============================================================ -->
<!-- PAGE HEADER -->
<!-- ============================================================ -->
<section class="page-header">
    <div>
        <h1>About Royal Estate</h1>
        <p>Discover our story of luxury, elegance, and unforgettable experiences</p>
    </div>
</section>

<!-- ============================================================ -->
<!-- ABOUT CONTENT -->
<!-- ============================================================ -->
<section>
    <div class="container">
        <div class="about-grid">
            <div class="about-text">
                <h3>Our Story</h3>
                <p>Founded in 2010, Royal Estate has been the epitome of luxury hospitality in Kurunegala, Sri Lanka. What started as a vision to create a sanctuary of elegance has grown into one of the region's most prestigious destinations for both accommodation and celebrations.</p>
                <p>Our commitment to excellence, attention to detail, and passion for creating memorable experiences have made us the preferred choice for discerning travelers and event planners alike. Every guest who walks through our doors becomes part of our extended family.</p>
                <p>At Royal Estate, we believe that true luxury lies in the details. From our meticulously designed rooms to our grand event spaces, every element is crafted to provide an unparalleled experience of comfort and sophistication.</p>
                <div class="signature"><i class="fas fa-star"></i> Where Every Moment Becomes a Memory</div>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=600" alt="Royal Estate Hotel">
                <div class="experience-badge"><i class="fas fa-calendar-alt"></i> 14+ Years of Excellence</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- STATS -->
<!-- ============================================================ -->
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item"><h3>5000+</h3><p>Happy Guests</p></div>
            <div class="stat-item"><h3>250+</h3><p>Events Hosted</p></div>
            <div class="stat-item"><h3>50+</h3><p>Luxury Rooms</p></div>
            <div class="stat-item"><h3>100+</h3><p>Team Members</p></div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- MISSION & VISION -->
<!-- ============================================================ -->
<section>
    <div class="container">
        <div class="mission-vision-grid">
            <div class="mv-card">
                <div class="mv-icon"><i class="fas fa-bullseye"></i></div>
                <h3>Our Mission</h3>
                <p>To provide exceptional hospitality experiences that exceed expectations, creating lasting memories for every guest through personalized service, attention to detail, and unwavering commitment to quality.</p>
            </div>
            <div class="mv-card">
                <div class="mv-icon"><i class="fas fa-eye"></i></div>
                <h3>Our Vision</h3>
                <p>To be Sri Lanka's premier destination for luxury accommodations and events, setting new standards of excellence in hospitality while preserving our rich cultural heritage.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- CORE VALUES -->
<!-- ============================================================ -->
<section style="background: #f9f5f0;">
    <div class="container">
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-heart"></i></div>
                <h4>Hospitality</h4>
                <p>Warm, genuine care for every guest</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-gem"></i></div>
                <h4>Excellence</h4>
                <p>Uncompromising quality in everything</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-handshake"></i></div>
                <h4>Integrity</h4>
                <p>Honesty and transparency always</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-lightbulb"></i></div>
                <h4>Innovation</h4>
                <p>Embracing new ideas and technology</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- ✅ TEAM SECTION - REAL AVATAR IMAGES (Stylish Design) -->
<!-- ============================================================ -->
<section>
    <div class="container">
        <div class="team-grid">
            <!-- Team Member 1 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Chaminda Perera" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'avatar-fallback\'><i class=\'fas fa-user-tie\'></i></div>'">
                </div>
                <h3>Mr. Chaminda Perera</h3>
                <p>General Manager</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <!-- Team Member 2 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Amali Fernando" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'avatar-fallback\'><i class=\'fas fa-user-tie\'></i></div>'">
                </div>
                <h3>Ms. Amali Fernando</h3>
                <p>Events Manager</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <!-- Team Member 3 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Ruwan Silva" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'avatar-fallback\'><i class=\'fas fa-user-tie\'></i></div>'">
                </div>
                <h3>Chef Ruwan Silva</h3>
                <p>Executive Chef</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <!-- Team Member 4 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://randomuser.me/api/portraits/women/23.jpg" alt="Nadeeka Rajapaksha" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'avatar-fallback\'><i class=\'fas fa-user-tie\'></i></div>'">
                </div>
                <h3>Mrs. Nadeeka Rajapaksha</h3>
                <p>Operations Manager</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- FOOTER -->
<!-- ============================================================ -->
<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>Royal Estate</h4>
            <p>Experience luxury and elegance in Kurunegala, Sri Lanka.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="about.php">About Us</a>
            <a href="rooms.php">Rooms & Suites</a>
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php">Packages</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="footer-col">
            <h4>Contact Info</h4>
            <a href="#"><i class="fas fa-phone"></i> +94 37 222 1234</a>
            <a href="#"><i class="fas fa-envelope"></i> info@royalestate.lk</a>
            <a href="#"><i class="fas fa-location-dot"></i> Kurunegala, Sri Lanka</a>
        </div>
        <div class="footer-col">
            <h4>Newsletter</h4>
            <p>Subscribe for exclusive offers</p>
            <form style="display:flex; gap:0.5rem; margin-top:1rem;">
                <input type="email" placeholder="Your Email" style="padding:0.5rem; border-radius:5px; border:none; flex:1;">
                <button style="background:#c5a263; border:none; padding:0.5rem 1rem; border-radius:5px;"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; 2026 Royal Estate. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #c5a263;"></i> for luxury experiences</p>
    </div>
</footer>

<!-- ============================================================ -->
<!-- LOGIN MODAL -->
<!-- ============================================================ -->
<div id="authModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Royal Estate</h2>
        <div class="modal-sub">Sign in to your account</div>
        <div class="modal-tabs">
            <button class="tab-btn active" data-tab="login">Sign In</button>
            <button class="tab-btn" data-tab="register">Register</button>
        </div>
        <div id="loginPane" class="tab-pane active">
            <div id="loginError" class="error-msg"></div>
            <form id="loginForm">
                <div class="form-group"><label>Email Address</label><input type="email" id="loginEmail" placeholder="Enter your email" required></div>
                <div class="form-group"><label>Password</label><input type="password" id="loginPassword" placeholder="Enter your password" required><i class="fas fa-eye-slash password-toggle" id="toggleLoginPwd"></i></div>
                <button type="submit" class="auth-btn">Sign In →</button>
            </form>
            <div class="register-link">New user? <a href="#" id="switchToRegister">Register</a></div>
        </div>
        <div id="registerPane" class="tab-pane">
            <div id="registerError" class="error-msg"></div>
            <form id="registerForm">
                <div class="form-group"><label>Full Name</label><input type="text" id="regName" placeholder="Your full name" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" id="regEmail" placeholder="your@email.com" required></div>
                <div class="form-group"><label>Password</label><input type="password" id="regPassword" placeholder="Create a password" required><i class="fas fa-eye-slash password-toggle" id="toggleRegPwd"></i></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" id="regConfirm" placeholder="Confirm your password" required><i class="fas fa-eye-slash password-toggle" id="toggleRegConfirm"></i></div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>
            <div class="register-link">Already have an account? <a href="#" id="switchToLogin">Sign In</a></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================================ -->
<script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
        document.querySelectorAll('.nav-links a, .nav-links button').forEach(function(link) {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
            });
        });
    }

    // ================================================================
    // ✅ CLICK-BASED USER DROPDOWN
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdown = document.querySelector('.user-dropdown');
        if (!userDropdown) return;
        
        const dropdownMenu = userDropdown.querySelector('.dropdown-menu');
        const dropdownArrow = userDropdown.querySelector('.fa-chevron-down');
        const userNameSpan = userDropdown.querySelector('span');
        const avatarImg = userDropdown.querySelector('.user-avatar-small');

        if (!dropdownMenu) return;

        function toggleDropdown(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            if (dropdownArrow) {
                dropdownArrow.classList.toggle('rotate');
            }
        }

        if (dropdownArrow) {
            dropdownArrow.style.cursor = 'pointer';
            dropdownArrow.addEventListener('click', toggleDropdown);
        }
        if (userNameSpan) {
            userNameSpan.style.cursor = 'pointer';
            userNameSpan.addEventListener('click', toggleDropdown);
        }
        if (avatarImg) {
            avatarImg.style.cursor = 'pointer';
            avatarImg.addEventListener('click', toggleDropdown);
        }

        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                if (dropdownArrow) {
                    dropdownArrow.classList.remove('rotate');
                }
            }
        });

        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // ================================================================
    // ✅ LOGIN MODAL
    // ================================================================
    const modal = document.getElementById('authModal');
    const openLoginBtn = document.getElementById('openLoginBtn');
    const closeModal = document.querySelector('.close-modal');
    const tabs = document.querySelectorAll('.tab-btn');
    const loginPane = document.getElementById('loginPane');
    const registerPane = document.getElementById('registerPane');
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');

    function switchTab(tab) {
        if (tab === 'login') {
            loginPane.classList.add('active');
            registerPane.classList.remove('active');
            if (tabs[0]) tabs[0].classList.add('active');
            if (tabs[1]) tabs[1].classList.remove('active');
        } else {
            registerPane.classList.add('active');
            loginPane.classList.remove('active');
            if (tabs[1]) tabs[1].classList.add('active');
            if (tabs[0]) tabs[0].classList.remove('active');
        }
    }

    if (openLoginBtn) {
        openLoginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('active');
            switchTab('login');
        });
    }
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            modal.classList.remove('active');
        });
    }
    window.addEventListener('click', function(e) {
        if (e.target === modal) modal.classList.remove('active');
    });
    if (tabs[0]) tabs[0].addEventListener('click', function() { switchTab('login'); });
    if (tabs[1]) tabs[1].addEventListener('click', function() { switchTab('register'); });
    if (switchToRegister) switchToRegister.addEventListener('click', function(e) { e.preventDefault(); switchTab('register'); });
    if (switchToLogin) switchToLogin.addEventListener('click', function(e) { e.preventDefault(); switchTab('login'); });

    // Password toggles
    function togglePassword(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);
        if (input && toggle) {
            toggle.addEventListener('click', function() {
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('fa-eye');
            });
        }
    }
    togglePassword('loginPassword', 'toggleLoginPwd');
    togglePassword('regPassword', 'toggleRegPwd');
    togglePassword('regConfirm', 'toggleRegConfirm');

    // Login AJAX
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const errDiv = document.getElementById('loginError');
            errDiv.style.display = 'none';
            const res = await fetch('login-process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            });
            const data = await res.json();
            if (data.success) {
                location.href = data.redirect;
            } else {
                errDiv.innerText = data.error;
                errDiv.style.display = 'block';
            }
        });
    }

    // Register AJAX
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const pass = document.getElementById('regPassword').value;
            const confirm = document.getElementById('regConfirm').value;
            const errDiv = document.getElementById('registerError');
            errDiv.style.display = 'none';
            if (pass !== confirm) {
                errDiv.innerText = 'Passwords do not match';
                errDiv.style.display = 'block';
                return;
            }
            const res = await fetch('register-process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(pass)}`
            });
            const data = await res.json();
            if (data.success) {
                alert('Registration successful! Please login.');
                switchTab('login');
                document.getElementById('loginEmail').value = email;
            } else {
                errDiv.innerText = data.error;
                errDiv.style.display = 'block';
            }
        });
    }
</script>
</body>
</html>
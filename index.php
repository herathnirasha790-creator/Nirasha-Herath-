<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Only redirect admin/owner to admin panel on page load, UNLESS they clicked "View Website"
if (isset($_SESSION['logged_in']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'owner')) {
    if (!isset($_GET['view']) || $_GET['view'] !== 'website') {
        header('Location: admin/index.php');
        exit();
    }
}

// Load home page content from JSON
$page_data_file = __DIR__ . '/data/pages/home.json';
if (file_exists($page_data_file)) {
    $page_data = json_decode(file_get_contents($page_data_file), true);
} else {
    $page_data = [
        'title' => 'Royal Estate',
        'description' => 'Experience luxury and elegance in Kurunegala, Sri Lanka. Your perfect destination for unforgettable stays and special cultural events.',
        'images' => [],
        'videos' => []
    ];
}

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

// Hero video source
$default_video_path = 'video/hero.mp4';
$hero_video_source = !empty($page_data['videos']) ? $page_data['videos'][0] : $default_video_path;

require_once 'config/db_connection.php';

// FETCH ACTIVE ROOMS (Limit 3)
$rooms = [];
$result = $conn->query("SELECT * FROM rooms WHERE status = 'active' ORDER BY id DESC LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $imgs = json_decode($row['images'] ?? '[]', true);
        if (empty($imgs) && !empty($row['image'])) {
            $imgs = [$row['image']];
        }
        $row['images'] = $imgs;
        $row['features'] = json_decode($row['features'] ?? '[]', true) ?: [];
        $rooms[] = $row;
    }
}

// FETCH ACTIVE EVENT HALLS (Limit 3)
$event_halls = [];
$result_halls = $conn->query("SELECT * FROM event_halls WHERE status = 'active' ORDER BY id DESC LIMIT 3");
if ($result_halls) {
    while ($row = $result_halls->fetch_assoc()) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $row['amenities'] = json_decode($row['amenities'] ?? '[]', true) ?: [];
        $event_halls[] = $row;
    }
}

// FETCH ACTIVE PACKAGES (Limit 3) and ALL HALLS for dropdown
$packages = [];
$result_pkgs = $conn->query("SELECT * FROM packages WHERE status = 'active' ORDER BY id DESC LIMIT 3");
if ($result_pkgs) {
    while ($row = $result_pkgs->fetch_assoc()) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $packages[] = $row;
    }
}

// FETCH ALL HALLS FOR PACKAGE MODAL DROPDOWN
$all_halls = [];
$halls_all = $conn->query("SELECT * FROM event_halls WHERE status = 'active' ORDER BY name");
if ($halls_all) {
    while ($row = $halls_all->fetch_assoc()) {
        $all_halls[] = $row;
    }
}

// FETCH ALL PACKAGES FOR EVENT HALL MODAL DROPDOWN
$all_packages = [];
$pkg_all = $conn->query("SELECT id, name, price FROM packages WHERE status = 'active' ORDER BY category, price");
if ($pkg_all) {
    while ($row = $pkg_all->fetch_assoc()) {
        $all_packages[] = $row;
    }
}

// ============================================================
// ✅ FETCH LATEST 3 APPROVED REVIEWS FOR HOME PAGE (DESC - අලුත්ම උඩින්)
// ============================================================
$latest_reviews = [];
$review_result = $conn->query("
    SELECT user_name, user_avatar, rating, title, comment, created_at 
    FROM reviews 
    WHERE status = 'approved' 
    ORDER BY created_at DESC
    LIMIT 3
");
if ($review_result) {
    while ($row = $review_result->fetch_assoc()) {
        $row['user_avatar'] = !empty($row['user_avatar']) && file_exists($row['user_avatar']) 
            ? $row['user_avatar'] 
            : 'https://ui-avatars.com/api/?name=' . urlencode($row['user_name']) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
        $latest_reviews[] = $row;
    }
}

// If no reviews yet, use default hardcoded ones
if (empty($latest_reviews)) {
    $latest_reviews = [
        [
            'user_name' => 'Nuwan Perera', 
            'rating' => 5, 
            'comment' => 'Amazing experience! The rooms were luxurious and the staff was incredibly helpful. Best hotel in Kurunegala!', 
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'user_name' => 'Amali Silva', 
            'rating' => 5, 
            'comment' => 'We had our wedding at Royal Estate. The event team made everything perfect. Highly recommended!', 
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'user_name' => 'Chaminda Rajapaksha', 
            'rating' => 5, 
            'comment' => 'Great place for corporate events. Professional service and excellent facilities.', 
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_data['title']); ?> | Luxury Hotel & Events</title>
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars(strip_tags($page_data['description'])); ?>">
    <meta name="keywords" content="Luxury Hotel, Event Halls, Sri Lanka, Kurunegala, Royal Estate, Room Booking, Wedding Halls, Banquet Halls">
    <meta name="author" content="Royal Estate">
    <meta name="robots" content="index, follow">
    <!-- Open Graph (Facebook, WhatsApp sharing) -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_data['title']); ?> | Luxury Hotel & Events">
    <meta property="og:description" content="<?php echo htmlspecialchars(strip_tags($page_data['description'])); ?>">
    <meta property="og:image" content="https://royalestate.wuaze.com/assets/images/favicon.jpg">
    <meta property="og:url" content="https://royalestate.wuaze.com/">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/jpeg" href="assets/images/favicon.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== ALL EXISTING STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #fffef8; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c5a263, #8b691f); border-radius: 10px; }
        .navbar {
            position: fixed; top: 0; width: 100%; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
            z-index: 1000; padding: 1rem 5%; transition: 0.3s; box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar.scrolled { padding: 0.8rem 5%; background: rgba(255,255,255,0.98); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; width: 100%; }
        .logo h1 { font-size: 1.8rem; background: linear-gradient(135deg,#2c1810,#c5a263); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 1px; }
        .logo p { font-size: 0.7rem; color: #c5a263; letter-spacing: 3px; margin-top: -5px; }
        .nav-links { display: flex; gap: 2rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; color: #2c1810; font-weight: 500; transition: 0.3s; position: relative; }
        .nav-links a::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 0%; height: 2px; background: linear-gradient(90deg,#c5a263,#8b691f); transition: 0.3s; }
        .nav-links a:hover::after, .nav-links a.active::after { width: 100%; }
        .nav-links a:hover { color: #c5a263; }
        .signin-btn { background: linear-gradient(135deg,#c5a263,#8b691f); color: white !important; padding: 0.6rem 1.5rem; border-radius: 50px; border: none; font-weight: 600; cursor: pointer; }
        .menu-toggle { width: 20px; margin-right: 8px; color: #c5a263; display: none; font-size: 1.5rem; cursor: pointer; }

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
            min-width: 180px;
            z-index: 1000;
            margin-top: 5px;
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
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            width: 100%;
            height: 10px;
            background: transparent;
        }
        .dropdown-menu a {
            display: block;
            padding: 12px 16px;
            color: #2c1810;
            text-decoration: none;
            transition: 0.2s;
            border-bottom: 1px solid #f5f0eb;
        }
        .dropdown-menu a:last-child { border-bottom: none; }
        .dropdown-menu a:hover {
            background: #f5f0eb;
            color: #c5a263;
        }
        .dropdown-menu a i {
            width: 20px;
            margin-right: 8px;
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

        .hero { height: 100vh; margin-top: 70px; margin-bottom: 60px; position: relative; display: flex; align-items: center; justify-content: center; text-align: center; color: white; }
        .hero-bg-video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .hero-bg-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: -1; }
        .hero-content { max-width: 800px; padding: 2rem; animation: fadeInUp 1s ease; z-index: 2; }
        .hero-content h1 { font-size: 4rem; margin-bottom: 1rem; letter-spacing: 2px; }
        .hero-content p { font-size: 1.2rem; margin-bottom: 2rem; }
        .hero-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-primary, .btn-secondary { padding: 1rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(197,162,99,0.4); }
        .btn-secondary { background: transparent; color: white; border: 2px solid white; }
        .btn-secondary:hover { background: white; color: #2c1810; transform: translateY(-3px); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .booking-widget { position: absolute; bottom: -60px; left: 50%; transform: translateX(-50%); background: white; border-radius: 20px; padding: 1.5rem 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.15); width: 90%; max-width: 1200px; z-index: 10; }
        .widget-form { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }
        .widget-group { flex: 1; min-width: 150px; }
        .widget-group label { display: block; font-size: 0.8rem; color: #666; margin-bottom: 0.3rem; text-align: left; }
        .widget-group input, .widget-group select { width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 10px; font-family: 'Poppins', sans-serif; }
        .widget-btn { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; border: none; padding: 0 2rem; border-radius: 10px; cursor: pointer; font-weight: 600; }
        section { padding: 5rem 5%; }
        .section-title { text-align: center; margin-bottom: 3rem; }
        .section-title h2 { font-size: 2.5rem; color: #2c1810; margin-bottom: 0.5rem; }
        .section-title .divider { width: 80px; height: 3px; background: linear-gradient(90deg, #c5a263, #8b691f); margin: 1rem auto; }
        .section-title p { color: #666; }
        .rooms-grid, .events-grid, .packages-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; max-width: 1400px; margin: 0 auto; }
        .room-card, .event-card, .package-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.3s; cursor: pointer; }
        .room-card:hover, .event-card:hover, .package-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .card-image { height: 250px; overflow: hidden; position: relative; }
        .card-image img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .room-card:hover .card-image img { transform: scale(1.1); }
        .card-price { position: absolute; top: 1rem; right: 1rem; background: linear-gradient(135deg, #c5a263, #8b691f); color: white; padding: 0.3rem 1rem; border-radius: 20px; font-weight: 600; }
        .card-content { padding: 1.5rem; }
        .card-content h3 { font-size: 1.3rem; margin-bottom: 0.5rem; color: #2c1810; }
        .card-content p { color: #666; margin-bottom: 1rem; line-height: 1.5; }
        .card-features { display: flex; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem; color: #888; }
        .card-features i { color: #c5a263; }
        .book-now { display: inline-block; color: #c5a263; text-decoration: none; font-weight: 600; transition: 0.3s; border: none; background: none; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .book-now:hover { color: #8b691f; transform: translateX(5px); }
        .features { background: linear-gradient(135deg, #f9f5f0, #fff9f2); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto; }
        .feature-item { text-align: center; padding: 2rem; }
        .feature-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #c5a26320, #8b691f20); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
        .feature-icon i { font-size: 2rem; color: #c5a263; }
        .feature-item h4 { font-size: 1.2rem; margin-bottom: 0.5rem; color: #2c1810; }

        /* ✅ TESTIMONIALS SECTION - DYNAMIC REVIEWS */
        .testimonials { background: #2c1810; color: white; }
        .testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto; }
        .testimonial-card { 
            background: rgba(255,255,255,0.1); 
            padding: 2rem; 
            border-radius: 20px; 
            backdrop-filter: blur(10px);
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            overflow: hidden;
        }
        .testimonial-card i { font-size: 2rem; color: #c5a263; margin-bottom: 1rem; }
        .testimonial-card p { 
            font-style: italic; 
            margin-bottom: 1rem; 
            line-height: 1.6;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
        }
        .testimonial-card strong { color: #c5a263; }
        .testimonial-card .stars { color: #f5c518; font-size: 1.1rem; margin-top: 0.3rem; }

        footer { background: #1a0f0a; color: #999; padding: 3rem 5% 1rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 1400px; margin: 0 auto; }
        .footer-col h4 { color: white; margin-bottom: 1rem; font-size: 1.2rem; }
        .footer-col a { display: block; color: #999; text-decoration: none; margin-bottom: 0.5rem; transition: 0.3s; }
        .footer-col a:hover { color: #c5a263; transform: translateX(5px); }
        .social-links { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-links a { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .social-links a:hover { background: #c5a263; transform: translateY(-3px); }
        .copyright { text-align: center; padding-top: 2rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }

        /* Login Modal */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(8px);
            z-index: 3000; align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; border-radius: 30px; width: 90%; max-width: 480px;
            padding: 2rem 1.8rem; position: relative; animation: fadeInUp 0.3s;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #999; }
        .close-modal:hover { color: #c5a263; }
        .modal h2 { text-align: center; font-size: 1.8rem; margin-bottom: 0.5rem; color: #2c1810; }
        .modal-sub { text-align: center; color: #666; font-size: 0.85rem; margin-bottom: 1.5rem; }
        .modal-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; }
        .tab-btn { flex: 1; text-align: center; background: none; border: none; padding: 0.8rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: #666; }
        .tab-btn.active { color: #c5a263; border-bottom: 2px solid #c5a263; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .form-group { margin-bottom: 1.2rem; position: relative; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #2c1810; }
        .form-group input { width: 100%; padding: 12px; padding-right: 40px; border: 1px solid #e0d5cc; border-radius: 12px; }
        .password-toggle { position: absolute; right: 12px; top: 38px; cursor: pointer; color: #999; }
        .auth-btn { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; width: 100%; padding: 12px; border: none; border-radius: 30px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 10px; margin-bottom: 1rem; display: none; }
        .register-link { text-align: center; margin-top: 1rem; font-size: 0.85rem; }
        .register-link a { color: #c5a263; text-decoration: none; font-weight: 600; }

        /* ============================================================ */
        /* ✅ MODAL OVERLAY - MATCHING EVENT HALLS STYLE */
        /* ============================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active { display: flex; }
        .modal-container {
            background: white;
            border-radius: 24px;
            max-width: 950px;
            width: 100%;
            position: relative;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.3s;
            overflow: hidden;
            max-height: 95vh;
            overflow-y: auto;
        }
        .modal-body { display: flex; flex-wrap: wrap; }
        .modal-image { width: 40%; min-height: 420px; background: #2c1810; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; overflow: hidden; }
        .modal-image img { width: 100%; height: 100%; object-fit: cover; }
        .modal-content-wrapper { width: 60%; padding: 1.8rem; }
        .modal-close {
            position: absolute; top: 15px; right: 15px;
            background: rgba(0,0,0,0.1); border: none;
            border-radius: 50%; width: 35px; height: 35px;
            font-size: 1.2rem; cursor: pointer; z-index: 10;
            display: flex; align-items: center; justify-content: center;
            transition: 0.2s;
        }
        .modal-close:hover { background: #c5a263; color: white; }
        .modal-title { font-size: 1.6rem; color: #2c1810; font-family: 'Playfair Display', serif; margin-bottom: 0.2rem; }
        .modal-price { font-size: 1.4rem; color: #c5a263; font-weight: 700; margin-bottom: 0.5rem; }
        .modal-features-list { margin: 0.5rem 0 0.8rem 0; padding: 0.5rem 1rem; background: #f9f5f0; border-radius: 10px; }
        .modal-features-list li { list-style: none; font-size: 0.8rem; color: #555; padding: 2px 0; display: flex; align-items: center; gap: 0.5rem; }
        .modal-features-list li i { color: #c5a263; width: 18px; }

        /* Calendar - MATCHING EVENT HALLS */
        .calendar-container { margin: 0.8rem 0; background: #f9f5f0; border-radius: 16px; padding: 0.8rem; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .calendar-header h4 { font-size: 0.95rem; color: #2c1810; font-weight: 600; }
        .calendar-nav { background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #c5a263; padding: 0 0.5rem; }
        .calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 0.65rem; color: #888; margin-bottom: 0.3rem; }
        .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; min-height: 200px; }
        .calendar-day {
            padding: 6px 0;
            text-align: center;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: 0.2s;
            border: 1px solid transparent;
            cursor: pointer;
            background: #e8f5e9;
        }
        .calendar-day.available { background: #e8f5e9; }
        .calendar-day.available:hover:not(.booked):not(.past) { background: #c8e6c9; }
        .calendar-day.booked {
            background: #ffebee;
            color: #b71c1c;
            text-decoration: line-through;
            cursor: not-allowed;
            border-color: #ef9a9a;
        }
        .calendar-day.past {
            background: #f5f5f5;
            color: #bdbdbd;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        .calendar-day.today {
            border-color: #c5a263;
            font-weight: 700;
            position: relative;
        }
        .calendar-day.today::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: #c5a263;
            border-radius: 50%;
        }
        .calendar-day.selected {
            background: #c5a263 !important;
            color: white !important;
            border-color: #8b691f;
        }
        .calendar-legend { display: flex; gap: 1rem; justify-content: center; margin-top: 0.5rem; font-size: 0.65rem; color: #666; flex-wrap: wrap; }
        .calendar-legend span { display: flex; align-items: center; gap: 0.3rem; }
        .legend-dot { width: 14px; height: 14px; border-radius: 4px; display: inline-block; border: 1px solid #ccc; }
        .legend-dot.available { background: #e8f5e9; border-color: #a5d6a7; }
        .legend-dot.booked { background: #ffebee; border-color: #ef9a9a; }
        .legend-dot.selected { background: #c5a263; border-color: #8b691f; }
        .legend-dot.today { background: white; border: 2px solid #c5a263; }

        .date-display { display: flex; gap: 1rem; margin: 0.6rem 0; background: #f5f0eb; padding: 0.6rem 0.8rem; border-radius: 12px; flex-wrap: wrap; }
        .date-display-item { flex: 1; min-width: 80px; }
        .date-display-item label { font-size: 0.65rem; color: #888; display: block; }
        .date-display-item strong { font-size: 0.85rem; color: #2c1810; }
        .form-group-modal { margin-bottom: 0.6rem; }
        .form-group-modal label { display: block; font-weight: 500; font-size: 0.8rem; color: #2c1810; margin-bottom: 3px; }
        .form-group-modal input, .form-group-modal select, .form-group-modal textarea {
            width: 100%; padding: 8px 12px; border-radius: 10px; border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem;
        }
        .form-group-modal textarea { resize: vertical; min-height: 50px; }
        .btn-confirm-booking {
            background: linear-gradient(135deg,#c5a263,#8b691f);
            color: white; border: none; padding: 10px;
            border-radius: 50px; font-weight: 600; cursor: pointer;
            width: 100%; margin-top: 0.3rem; font-family: 'Poppins', sans-serif;
            transition: 0.2s; font-size: 0.95rem;
        }
        .btn-confirm-booking:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197,162,99,0.3); }
        .alert-error-modal { background: #f8d7da; color: #721c24; padding: 8px 12px; border-radius: 10px; margin-bottom: 0.8rem; border-left: 4px solid #dc3545; display: none; font-size: 0.85rem; }
        .alert-success-modal { background: #d4edda; color: #155724; padding: 8px 12px; border-radius: 10px; margin-bottom: 0.8rem; border-left: 4px solid #28a745; display: none; font-size: 0.85rem; }
        .hidden-input { display: none; }

        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .nav-links { position: fixed; top: 70px; left: -100%; width: 100%; background: white; flex-direction: column; padding: 2rem; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
            .nav-links.active { left: 0; }
            .hero-content h1 { font-size: 2.5rem; }
            .hero { padding-bottom: 0; margin-bottom: 100px; }
            .booking-widget { position: relative; bottom: auto; transform: none; margin: 40px auto 0; width: 95%; }
            .widget-form { flex-direction: column; }
            .widget-group { min-width: 100%; }
            .widget-btn { width: 100%; padding: 0.8rem; }
            .modal-body { flex-direction: column; }
            .modal-image { width: 100%; min-height: 200px; }
            .modal-content-wrapper { width: 100%; padding: 1.2rem; }
            .calendar-days { gap: 2px; }
            .calendar-day { padding: 4px 0; font-size: 0.7rem; }
            .date-display { flex-direction: column; gap: 0.3rem; }
            .testimonials-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo"><h1>ROYAL ESTATE</h1><p>LUXURY & ELEGANCE</p></div>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <div class="nav-links" id="navLinks">
            <a href="index.php" class="active">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php">Packages</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="reviews.php">Reviews</a>

            <?php if ($is_logged_in): ?>
                <div class="user-dropdown">
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="user-avatar-small" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=c5a263&color=fff&size=40&font-size=0.4&bold=true'">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown-menu">
                        <?php if ($user_role === 'admin' || $user_role === 'owner'): ?>
                            <a href="admin/index.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                        <?php else: ?>
                            <a href="user/dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
                        <?php endif; ?>
                        <a href="user/profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="signin-btn" id="openLoginBtn"><i class="fas fa-user"></i> Sign In</button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <video class="hero-bg-video" autoplay muted loop playsinline poster="video/hero-bg.jpg">
        <source src="<?php echo htmlspecialchars($hero_video_source); ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="hero-bg-overlay"></div>
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($page_data['title']); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($page_data['description'])); ?></p>
        <div class="hero-buttons">
            <button class="btn-primary" onclick="window.location.href='rooms.php'"><i class="fas fa-calendar-check"></i> Book a Room</button>
            <button class="btn-secondary" onclick="window.location.href='event-halls.php'"><i class="fas fa-glass-celebration"></i> Plan an Event</button>
        </div>
    </div>
    <?php if (!empty($page_data['images'])): ?>
        <div style="position:absolute; bottom:20px; left:20px; z-index:5; background:rgba(0,0,0,0.5); padding:5px 10px; border-radius:20px; display:flex; gap:5px;">
            <?php foreach ($page_data['images'] as $img): ?>
                <img src="<?php echo htmlspecialchars($img); ?>" style="height:40px; width:auto; margin:0 2px; border-radius:5px;">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="booking-widget">
        <form class="widget-form" action="search-availability.php" method="GET">
            <div class="widget-group"><label><i class="fas fa-calendar"></i> Check In</label><input type="date" name="checkin" required></div>
            <div class="widget-group"><label><i class="fas fa-calendar"></i> Check Out</label><input type="date" name="checkout" required></div>
            <div class="widget-group"><label><i class="fas fa-user"></i> Guests</label><select name="guests" required><option value="1">1 Guest</option><option value="2">2 Guests</option><option value="3">3 Guests</option><option value="4">4 Guests</option></select></div>
            <button type="submit" class="widget-btn"><i class="fas fa-search"></i> Check Availability</button>
        </form>
    </div>
</section>

<!-- ROOMS SECTION -->
<section>
    <div class="section-title">
        <h2>Luxury Rooms & Suites</h2>
        <div class="divider"></div>
        <p>Experience unparalleled comfort and elegance in our carefully designed rooms</p>
    </div>
    <div class="rooms-grid">
        <?php if (empty($rooms)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:20px; background:white; border-radius:20px;"><p>No rooms available at the moment.</p></div>
        <?php else: ?>
            <?php foreach($rooms as $room): 
                $img = 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=500';
                if (!empty($room['images']) && file_exists($room['images'][0])) $img = $room['images'][0];
                elseif (!empty($room['image']) && file_exists($room['image'])) $img = $room['image'];
                $features = [];
                if (!empty($room['features'])) $features = array_slice($room['features'], 0, 3);
                else $features = ['Free WiFi', 'Smart TV', 'AC'];
            ?>
            <div class="room-card">
                <div class="card-image">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    <div class="card-price">LKR <?php echo number_format($room['price']); ?> / night</div>
                </div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($room['description'], 0, 100)) . '...'; ?></p>
                    <div class="card-features">
                        <?php foreach($features as $feat): ?>
                            <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($feat); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($is_logged_in): ?>
                        <button class="book-now" onclick="openHomeRoomBookingModal(<?php echo $room['id']; ?>, '<?php echo addslashes($room['name']); ?>', <?php echo $room['price']; ?>, <?php echo $room['max_guests']; ?>, '<?php echo addslashes($img); ?>')">Book Now →</button>
                    <?php else: ?>
                        <button class="book-now" onclick="triggerLogin(event)">Book Now →</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- EVENT HALLS SECTION -->
<section style="background: #f9f5f0;">
    <div class="section-title">
        <h2>Grand Event Halls</h2>
        <div class="divider"></div>
        <p>Celebrate your special moments in our magnificent venues</p>
    </div>
    <div class="events-grid">
        <?php if (empty($event_halls)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:20px; background:white; border-radius:20px;"><p>No event halls available at the moment.</p></div>
        <?php else: ?>
            <?php foreach($event_halls as $hall): 
                $img = 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=500';
                if (!empty($hall['images']) && file_exists($hall['images'][0])) $img = $hall['images'][0];
                elseif (!empty($hall['image']) && file_exists($hall['image'])) $img = $hall['image'];
                $amenities = [];
                if (!empty($hall['amenities'])) $amenities = array_slice($hall['amenities'], 0, 2);
                else $amenities = ['Up to ' . $hall['capacity'] . ' Guests', 'Sound System'];
            ?>
            <div class="event-card">
                <div class="card-image">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($hall['name']); ?>">
                    <div class="card-price">Starting LKR <?php echo number_format($hall['base_price']); ?></div>
                </div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($hall['name']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($hall['description'] ?? '', 0, 80)) . '...'; ?></p>
                    <div class="card-features">
                        <?php foreach($amenities as $amenity): ?>
                            <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($is_logged_in): ?>
                        <button class="book-now" onclick="openHomeEventHallModal(<?php echo $hall['id']; ?>, '<?php echo addslashes($hall['name']); ?>', <?php echo $hall['capacity']; ?>, <?php echo $hall['base_price']; ?>, '<?php echo addslashes($img); ?>')">Book Now →</button>
                    <?php else: ?>
                        <button class="book-now" onclick="triggerLogin(event)">Book Now →</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- PACKAGES SECTION -->
<section>
    <div class="section-title">
        <h2>Special Event Packages</h2>
        <div class="divider"></div>
        <p>Curated packages for every occasion</p>
    </div>
    <div class="packages-grid">
        <?php if (empty($packages)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:20px; background:white; border-radius:20px;"><p>No packages available at the moment.</p></div>
        <?php else: ?>
            <?php foreach($packages as $pkg): 
                $img = 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=500';
                if (!empty($pkg['images']) && file_exists($pkg['images'][0])) $img = $pkg['images'][0];
                elseif (!empty($pkg['image']) && file_exists($pkg['image'])) $img = $pkg['image'];
                $category_icon = 'fa-gem';
                if ($pkg['category'] == 'wedding') $category_icon = 'fa-ring';
                elseif ($pkg['category'] == 'party') $category_icon = 'fa-glass-cheers';
                elseif ($pkg['category'] == 'corporate') $category_icon = 'fa-briefcase';
                $inclusions = [];
                if (!empty($pkg['inclusions'])) {
                    $lines = explode("\n", trim($pkg['inclusions']));
                    $inclusions = array_slice(array_filter(array_map('trim', $lines)), 0, 2);
                }
            ?>
            <div class="package-card">
                <div class="card-image"><img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($pkg['name']); ?>"></div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($pkg['description'] ?? $pkg['inclusions'] ?? '', 0, 80)) . '...'; ?></p>
                    <div class="card-features">
                        <span><i class="fas <?php echo $category_icon; ?>"></i> <?php echo ucfirst($pkg['category']); ?></span>
                        <span><i class="fas fa-tag"></i> LKR <?php echo number_format($pkg['price']); ?></span>
                        <?php if (!empty($inclusions)): ?>
                            <?php foreach($inclusions as $inc): ?>
                                <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($inc); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_logged_in): ?>
                        <button class="book-now" onclick="openHomePackageModal(<?php echo $pkg['id']; ?>, '<?php echo addslashes($pkg['name']); ?>', <?php echo $pkg['price']; ?>, '<?php echo addslashes($img); ?>')">Book Now →</button>
                    <?php else: ?>
                        <button class="book-now" onclick="triggerLogin(event)">Book Now →</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- FEATURES SECTION -->
<section class="features">
    <div class="section-title">
        <h2>Why Choose Royal Estate?</h2>
        <div class="divider"></div>
        <p>Experience the difference with our premium services</p>
    </div>
    <div class="features-grid">
        <div class="feature-item"><div class="feature-icon"><i class="fas fa-concierge-bell"></i></div><h4>24/7 Concierge</h4><p>Dedicated service for all your needs anytime</p></div>
        <div class="feature-item"><div class="feature-icon"><i class="fas fa-utensils"></i></div><h4>Fine Dining</h4><p>Exquisite culinary experiences</p></div>
        <div class="feature-item"><div class="feature-icon"><i class="fas fa-spa"></i></div><h4>Spa & Wellness</h4><p>Relax and rejuvenate with our spa</p></div>
        <div class="feature-item"><div class="feature-icon"><i class="fas fa-swimming-pool"></i></div><h4>Infinity Pool</h4><p>Stunning pool with panoramic views</p></div>
    </div>
</section>

<!-- ============================================================ -->
<!-- ✅ TESTIMONIALS SECTION - DYNAMIC REVIEWS FROM DATABASE -->
<!-- ============================================================ -->
<section class="testimonials">
    <div class="section-title">
        <h2 style="color: white;">What Our Guests Say</h2>
        <div class="divider"></div>
        <p style="color: #ccc;">Real experiences from our valued customers</p>
    </div>
    <div class="testimonials-grid">
        <?php foreach($latest_reviews as $review): ?>
        <div class="testimonial-card">
            <i class="fas fa-quote-left"></i>
            <p><?php echo htmlspecialchars($review['comment']); ?></p>
            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
            <div class="stars">
                <?php 
                $rating = $review['rating'] ?? 5;
                for($i=1; $i<=5; $i++): 
                    if ($i <= $rating) {
                        echo '<i class="fas fa-star"></i>';
                    } else {
                        echo '<i class="far fa-star"></i>';
                    }
                endfor; 
                ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

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
            <a href="rooms.php">Rooms & Suites</a>
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php">Packages</a>
            <a href="reviews.php">Reviews</a>
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
            <form action="subscribe.php" method="POST" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <input type="email" name="email" placeholder="Your Email" style="padding: 0.5rem; border-radius: 5px; border: none; flex: 1;" required>
                <button type="submit" style="background: #c5a263; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
    <div class="copyright"><p>&copy; 2024 Royal Estate. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #c5a263;"></i> for luxury experiences</p></div>
</footer>

<!-- ============================================================ -->
<!-- ✅ HOME ROOM BOOKING MODAL -->
<!-- ============================================================ -->
<div class="modal-overlay" id="homeRoomBookingModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeHomeRoomBookingModal()"><i class="fas fa-times"></i></button>
        <div class="modal-body">
            <div class="modal-image" id="hrModalImage"><i class="fas fa-hotel"></i></div>
            <div class="modal-content-wrapper">
                <h2 class="modal-title" id="hrRoomName">Room Name</h2>
                <div class="modal-price" id="hrPriceDisplay">$0 <small>/ night</small></div>
                <p id="hrMaxGuests" style="color:#666; margin-bottom:0.3rem; font-size:0.85rem;"><i class="fas fa-users"></i> Max <span id="hrMaxGuestsNum">2</span> Guests</p>
                <form id="homeRoomBookingForm">
                    <input type="hidden" name="room_id" id="hrRoomId">
                    <input type="hidden" name="check_in" id="hrCheckInHidden">
                    <input type="hidden" name="check_out" id="hrCheckOutHidden">
                    <input type="hidden" name="booking_type" value="room">
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" onclick="hrChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 id="hrCalendarTitle">January 2024</h4>
                            <button type="button" class="calendar-nav" onclick="hrChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="calendar-weekdays"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
                        <div class="calendar-days" id="hrCalendarDays"></div>
                        <div class="calendar-legend">
                            <span><span class="legend-dot available"></span> Available</span>
                            <span><span class="legend-dot booked"></span> Booked</span>
                            <span><span class="legend-dot selected"></span> Selected</span>
                            <span><span class="legend-dot today"></span> Today</span>
                        </div>
                    </div>
                    <div class="date-display">
                        <div class="date-display-item"><label>Check In</label><strong id="hrDisplayCheckIn">—</strong></div>
                        <div class="date-display-item"><label>Check Out</label><strong id="hrDisplayCheckOut">—</strong></div>
                        <div class="date-display-item"><label>Nights</label><strong id="hrDisplayNights">0</strong></div>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-user-friends"></i> Guests (max <span id="hrMaxGuestsNum2">2</span>)</label>
                        <input type="number" name="guests" id="hrGuests" min="1" value="1" required>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-pencil-alt"></i> Special Requests</label>
                        <textarea name="special_requests" id="hrSpecial" rows="2" placeholder="e.g., late check-in, extra pillows..."></textarea>
                    </div>
                    <div id="hrErrorMsg" class="alert-error-modal"></div>
                    <div id="hrSuccessMsg" class="alert-success-modal"></div>
                    <div id="hrFormContainer">
                        <button type="submit" class="btn-confirm-booking"><i class="fas fa-check-circle"></i> Confirm Booking</button>
                    </div>
                    <div id="hrSuccessContainer" style="display:none;">
                        <div id="hrSuccessDetails" style="background:#fef5e6; padding:1rem; border-radius:12px; margin-top:0.5rem;"></div>
                        <a href="user/my-room-bookings.php" style="display:inline-block; margin-top:0.5rem; color:#c5a263; font-weight:600;">View My Bookings →</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ✅ HOME EVENT HALL BOOKING MODAL -->
<!-- ============================================================ -->
<div class="modal-overlay" id="homeEventHallBookingModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeHomeEventHallModal()"><i class="fas fa-times"></i></button>
        <div class="modal-body">
            <div class="modal-image" id="heModalImage"><i class="fas fa-building"></i></div>
            <div class="modal-content-wrapper">
                <h2 class="modal-title" id="heHallName">Hall Name</h2>
                <div class="modal-price" id="hePriceDisplay">Starting LKR 0</div>
                <p id="heCapacity" style="color:#666; margin-bottom:0.3rem; font-size:0.85rem;"><i class="fas fa-users"></i> Capacity: <span id="heMaxGuestsLabel">0</span></p>
                <form id="homeEventHallForm">
                    <input type="hidden" name="hall_id" id="heHallId">
                    <input type="hidden" name="event_date" id="heEventDateHidden">
                    <input type="hidden" name="booking_type" value="event_hall">
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" onclick="heChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 id="heCalendarTitle">January 2024</h4>
                            <button type="button" class="calendar-nav" onclick="heChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="calendar-weekdays"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
                        <div class="calendar-days" id="heCalendarDays"></div>
                        <div class="calendar-legend">
                            <span><span class="legend-dot available"></span> Available</span>
                            <span><span class="legend-dot booked"></span> Booked</span>
                            <span><span class="legend-dot selected"></span> Selected</span>
                            <span><span class="legend-dot today"></span> Today</span>
                        </div>
                    </div>
                    <div class="date-display">
                        <div class="date-display-item"><label>Event Date</label><strong id="heDisplayDate">—</strong></div>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-gift"></i> Select Package</label>
                        <select name="package_id" id="hePackageId" required>
                            <option value="">Choose a package</option>
                            <?php foreach($all_packages as $pkg): ?>
                                <option value="<?php echo $pkg['id']; ?>" data-price="<?php echo $pkg['price']; ?>"><?php echo htmlspecialchars($pkg['name']); ?> - LKR <?php echo number_format($pkg['price']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-user-friends"></i> Guests (max <span id="heMaxGuests">0</span>)</label>
                        <input type="number" name="guests" id="heGuests" min="1" value="1" required>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-pencil-alt"></i> Special Requests</label>
                        <textarea name="special_requests" id="heSpecial" rows="2" placeholder="e.g., vegetarian meals, decoration preferences..."></textarea>
                    </div>
                    <div id="heErrorMsg" class="alert-error-modal"></div>
                    <div id="heSuccessMsg" class="alert-success-modal"></div>
                    <div id="heFormContainer">
                        <button type="submit" class="btn-confirm-booking"><i class="fas fa-check-circle"></i> Submit Event Request</button>
                    </div>
                    <div id="heSuccessContainer" style="display:none;">
                        <div id="heSuccessDetails" style="background:#fef5e6; padding:1rem; border-radius:12px; margin-top:0.5rem;"></div>
                        <a href="user/my-event-bookings.php" style="display:inline-block; margin-top:0.5rem; color:#c5a263; font-weight:600;">View My Events →</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ✅ HOME PACKAGE BOOKING MODAL (UPDATED - WITH HALL PRICE) -->
<!-- ============================================================ -->
<div class="modal-overlay" id="homePackageBookingModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeHomePackageModal()"><i class="fas fa-times"></i></button>
        <div class="modal-body">
            <div class="modal-image" id="hpModalImage"><i class="fas fa-gift"></i></div>
            <div class="modal-content-wrapper">
                <h2 class="modal-title" id="hpPackageName">Package Name</h2>
                <div class="modal-price" id="hpPriceDisplay">$0 <small>/ event</small></div>
                <div id="hpInclusionsList" class="modal-features-list"></div>
                <form id="homePackageForm">
                    <input type="hidden" name="package_id" id="hpPackageId">
                    <input type="hidden" name="event_date" id="hpEventDateHidden">
                    <input type="hidden" name="booking_type" value="package">
                    <div class="form-group-modal">
                        <label><i class="fas fa-building"></i> Select Event Hall</label>
                        <select name="hall_id" id="hpHallSelect" required>
                            <option value="">Choose a hall</option>
                            <?php foreach($all_halls as $hall): ?>
                                <option value="<?php echo $hall['id']; ?>" data-capacity="<?php echo $hall['capacity']; ?>" data-price="<?php echo $hall['base_price']; ?>">
                                    <?php echo htmlspecialchars($hall['name']); ?> (Capacity: <?php echo $hall['capacity']; ?>) - LKR <?php echo number_format($hall['base_price']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" onclick="hpChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 id="hpCalendarTitle">January 2024</h4>
                            <button type="button" class="calendar-nav" onclick="hpChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="calendar-weekdays"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
                        <div class="calendar-days" id="hpCalendarDays"></div>
                        <div class="calendar-legend">
                            <span><span class="legend-dot available"></span> Available</span>
                            <span><span class="legend-dot booked"></span> Booked</span>
                            <span><span class="legend-dot selected"></span> Selected</span>
                            <span><span class="legend-dot today"></span> Today</span>
                        </div>
                    </div>
                    <div class="date-display">
                        <div class="date-display-item"><label>Event Date</label><strong id="hpDisplayDate">—</strong></div>
                        <div class="date-display-item" id="hpCapacityDisplay"><label>Hall Capacity</label><strong>—</strong></div>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-user-friends"></i> Guests</label>
                        <input type="number" name="guests" id="hpGuests" min="1" value="1" required>
                    </div>
                    <div class="form-group-modal">
                        <label><i class="fas fa-pencil-alt"></i> Special Requests</label>
                        <textarea name="special_requests" id="hpSpecial" rows="2" placeholder="e.g., vegetarian meals, decoration preferences..."></textarea>
                    </div>
                    <div id="hpErrorMsg" class="alert-error-modal"></div>
                    <div id="hpSuccessMsg" class="alert-success-modal"></div>
                    <div id="hpFormContainer">
                        <button type="submit" class="btn-confirm-booking"><i class="fas fa-check-circle"></i> Submit Package Request</button>
                    </div>
                    <div id="hpSuccessContainer" style="display:none;">
                        <div id="hpSuccessDetails" style="background:#fef5e6; padding:1rem; border-radius:12px; margin-top:0.5rem;"></div>
                        <a href="user/my-package-bookings.php" style="display:inline-block; margin-top:0.5rem; color:#c5a263; font-weight:600;">View My Package Bookings →</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

<script>
// ================================================================
// ✅ NAVBAR, MOBILE MENU
// ================================================================
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
});
const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');
if(menuToggle) {
    menuToggle.onclick = () => navLinks.classList.toggle('active');
    document.querySelectorAll('.nav-links a, .nav-links button').forEach(link => link.addEventListener('click', () => navLinks.classList.remove('active')));
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
// ✅ HOME ROOM BOOKING MODAL (WITH PRICE CALCULATION - TOTAL FIRST)
// ================================================================
let hrStartDate = null, hrEndDate = null, hrBookedDates = [], hrCurrentMonth = new Date().getMonth(), hrCurrentYear = new Date().getFullYear();
let hrRoomPrice = 0;

function openHomeRoomBookingModal(roomId, roomName, price, maxGuests, imgUrl) {
    hrStartDate = null; hrEndDate = null; hrBookedDates = []; hrRoomPrice = price;
    document.getElementById('hrRoomId').value = roomId;
    document.getElementById('hrRoomName').textContent = roomName;
    document.getElementById('hrPriceDisplay').innerHTML = 'LKR ' + Number(price).toLocaleString() + ' <small>/ night</small>';
    document.getElementById('hrMaxGuestsNum').textContent = maxGuests;
    document.getElementById('hrMaxGuestsNum2').textContent = maxGuests;
    document.getElementById('hrGuests').max = maxGuests;
    document.getElementById('hrGuests').value = 1;
    if (imgUrl && imgUrl !== '') {
        document.getElementById('hrModalImage').innerHTML = `<img src="${imgUrl}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600';" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        document.getElementById('hrModalImage').innerHTML = '<i class="fas fa-hotel"></i>';
    }
    document.getElementById('hrDisplayCheckIn').textContent = '—';
    document.getElementById('hrDisplayCheckOut').textContent = '—';
    document.getElementById('hrDisplayNights').textContent = '0';
    document.getElementById('hrCheckInHidden').value = '';
    document.getElementById('hrCheckOutHidden').value = '';
    hrUpdateTotalPrice();
    document.getElementById('hrErrorMsg').style.display = 'none';
    document.getElementById('hrSuccessMsg').style.display = 'none';
    document.getElementById('hrFormContainer').style.display = 'block';
    document.getElementById('hrSuccessContainer').style.display = 'none';
    
    fetch(`get-booked-dates.php?type=room&id=${roomId}`).then(res => res.json()).then(data => {
        if (data.success) hrBookedDates = data.booked;
        hrCurrentMonth = new Date().getMonth(); hrCurrentYear = new Date().getFullYear(); hrRenderCalendar();
    });
    document.getElementById('homeRoomBookingModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeHomeRoomBookingModal() { document.getElementById('homeRoomBookingModal').classList.remove('active'); document.body.style.overflow = ''; }
document.getElementById('homeRoomBookingModal').addEventListener('click', function(e) { if (e.target === this) closeHomeRoomBookingModal(); });

function hrRenderCalendar() {
    const daysContainer = document.getElementById('hrCalendarDays');
    const title = document.getElementById('hrCalendarTitle');
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    title.textContent = monthNames[hrCurrentMonth] + ' ' + hrCurrentYear;
    const firstDay = new Date(hrCurrentYear, hrCurrentMonth, 1).getDay();
    const daysInMonth = new Date(hrCurrentYear, hrCurrentMonth + 1, 0).getDate();
    const today = new Date(); const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    today.setHours(0,0,0,0);
    let html = '';
    for (let i = 0; i < firstDay; i++) html += '<div class="calendar-day" style="background:transparent;cursor:default;"></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = hrCurrentYear + '-' + String(hrCurrentMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        const currentDate = new Date(hrCurrentYear, hrCurrentMonth, d);
        let classes = 'calendar-day';
        if (currentDate < today) classes += ' past';
        else if (hrBookedDates.includes(dateStr)) classes += ' booked';
        else classes += ' available';
        if (dateStr === todayStr) classes += ' today';
        if (hrStartDate && hrEndDate) {
            if (dateStr >= hrStartDate && dateStr <= hrEndDate) classes += ' in-range';
            if (dateStr === hrStartDate || dateStr === hrEndDate) classes += ' selected';
        } else if (hrStartDate && dateStr === hrStartDate) classes += ' selected';
        html += `<div class="${classes}" data-date="${dateStr}" onclick="hrSelectDate('${dateStr}')">${d}</div>`;
    }
    daysContainer.innerHTML = html;
}
function hrChangeMonth(delta) { hrCurrentMonth += delta; if (hrCurrentMonth > 11) { hrCurrentMonth = 0; hrCurrentYear++; } if (hrCurrentMonth < 0) { hrCurrentMonth = 11; hrCurrentYear--; } hrRenderCalendar(); }
function hrSelectDate(dateStr) {
    const today = new Date(); today.setHours(0,0,0,0);
    const selectedDate = new Date(dateStr);
    if (selectedDate < today) { alert('Past dates cannot be selected.'); return; }
    if (hrBookedDates.includes(dateStr)) { alert('This date is already booked.'); return; }
    if (!hrStartDate) { hrStartDate = dateStr; hrEndDate = null; }
    else if (!hrEndDate) {
        if (dateStr < hrStartDate) { hrEndDate = hrStartDate; hrStartDate = dateStr; }
        else if (dateStr === hrStartDate) { hrStartDate = null; hrEndDate = null; hrUpdateDateDisplay(); hrRenderCalendar(); return; }
        else { hrEndDate = dateStr; }
    } else { hrStartDate = dateStr; hrEndDate = null; }
    hrUpdateDateDisplay(); hrRenderCalendar();
}
function hrUpdateDateDisplay() {
    const checkInDisplay = document.getElementById('hrDisplayCheckIn'), checkOutDisplay = document.getElementById('hrDisplayCheckOut');
    const nightsDisplay = document.getElementById('hrDisplayNights'), hiddenIn = document.getElementById('hrCheckInHidden'), hiddenOut = document.getElementById('hrCheckOutHidden');
    if (hrStartDate) { checkInDisplay.textContent = hrStartDate; hiddenIn.value = hrStartDate; } else { checkInDisplay.textContent = '—'; hiddenIn.value = ''; }
    if (hrEndDate) { checkOutDisplay.textContent = hrEndDate; hiddenOut.value = hrEndDate;
        const diffDays = Math.ceil(Math.abs(new Date(hrEndDate) - new Date(hrStartDate)) / (1000 * 60 * 60 * 24));
        nightsDisplay.textContent = diffDays;
    } else { checkOutDisplay.textContent = '—'; hiddenOut.value = ''; nightsDisplay.textContent = '0'; }
    hrUpdateTotalPrice();
}
function hrUpdateTotalPrice() {
    const nights = parseInt(document.getElementById('hrDisplayNights').textContent) || 0;
    const totalPrice = nights * hrRoomPrice;
    const priceDisplay = document.getElementById('hrPriceDisplay');
    if (nights > 0) {
        priceDisplay.innerHTML = `
            <span style="color:#c5a263; font-size:1.4rem; font-weight:800; display:block;">Total: LKR ${totalPrice.toLocaleString()}</span>
            <span style="font-size:0.85rem; color:#888; display:block;">LKR ${hrRoomPrice.toLocaleString()} × ${nights} night${nights > 1 ? 's' : ''}</span>
        `;
    } else {
        priceDisplay.innerHTML = 'LKR ' + Number(hrRoomPrice).toLocaleString() + ' <small>/ night</small>';
    }
}

document.getElementById('homeRoomBookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errorDiv = document.getElementById('hrErrorMsg'), successDiv = document.getElementById('hrSuccessMsg');
    errorDiv.style.display = 'none'; successDiv.style.display = 'none';
    if (!document.getElementById('hrCheckInHidden').value || !document.getElementById('hrCheckOutHidden').value) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select both check-in and check-out dates.';
        errorDiv.style.display = 'block'; return;
    }
    startBookingPayment({
        form: this, bookingType: 'room', finalizeEndpoint: 'room-booking-process.php',
        onValidationError: function(msg) { errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg; errorDiv.style.display = 'block'; },
        onSuccess: function(data) {
            successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            successDiv.style.display = 'block';
            document.getElementById('hrFormContainer').style.display = 'none';
            document.getElementById('hrSuccessContainer').style.display = 'block';
            document.getElementById('hrSuccessDetails').innerHTML = data.details;
            setTimeout(function() { window.location.href = 'user/my-room-bookings.php'; }, 3000);
        }
    });
});

// ================================================================
// ✅ HOME EVENT HALL BOOKING MODAL (WITH PRICE CALCULATION - TOTAL FIRST)
// ================================================================
let heStartDate = null, heBookedDates = [], heCurrentMonth = new Date().getMonth(), heCurrentYear = new Date().getFullYear();
let heHallPrice = 0, hePackagePrice = 0;

function openHomeEventHallModal(hallId, hallName, capacity, price, imgUrl) {
    heStartDate = null; heBookedDates = []; heHallPrice = price; hePackagePrice = 0;
    document.getElementById('heHallId').value = hallId;
    document.getElementById('heHallName').textContent = hallName;
    document.getElementById('hePriceDisplay').textContent = 'Starting LKR ' + Number(price).toLocaleString();
    document.getElementById('heMaxGuestsLabel').textContent = capacity;
    document.getElementById('heMaxGuests').textContent = capacity;
    document.getElementById('heGuests').max = capacity; document.getElementById('heGuests').value = 1;
    if (imgUrl && imgUrl !== '') {
        document.getElementById('heModalImage').innerHTML = `<img src="${imgUrl}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600';" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        document.getElementById('heModalImage').innerHTML = '<i class="fas fa-building"></i>';
    }
    document.getElementById('heDisplayDate').textContent = '—';
    document.getElementById('heEventDateHidden').value = '';
    document.getElementById('heErrorMsg').style.display = 'none';
    document.getElementById('heSuccessMsg').style.display = 'none';
    document.getElementById('heFormContainer').style.display = 'block';
    document.getElementById('heSuccessContainer').style.display = 'none';
    heUpdateTotalPrice();
    
    // Package selection change event
    document.getElementById('hePackageId').onchange = function() {
        const selected = this.options[this.selectedIndex];
        hePackagePrice = parseFloat(selected.getAttribute('data-price')) || 0;
        heUpdateTotalPrice();
    };
    
    fetch(`get-booked-dates.php?type=hall&id=${hallId}`).then(res => res.json()).then(data => {
        if (data.success) heBookedDates = data.booked;
        heCurrentMonth = new Date().getMonth(); heCurrentYear = new Date().getFullYear(); heRenderCalendar();
    });
    document.getElementById('homeEventHallBookingModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeHomeEventHallModal() { document.getElementById('homeEventHallBookingModal').classList.remove('active'); document.body.style.overflow = ''; }
document.getElementById('homeEventHallBookingModal').addEventListener('click', function(e) { if (e.target === this) closeHomeEventHallModal(); });

function heRenderCalendar() {
    const daysContainer = document.getElementById('heCalendarDays');
    const title = document.getElementById('heCalendarTitle');
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    title.textContent = monthNames[heCurrentMonth] + ' ' + heCurrentYear;
    const firstDay = new Date(heCurrentYear, heCurrentMonth, 1).getDay();
    const daysInMonth = new Date(heCurrentYear, heCurrentMonth + 1, 0).getDate();
    const today = new Date(); const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    today.setHours(0,0,0,0);
    let html = '';
    for (let i = 0; i < firstDay; i++) html += '<div class="calendar-day" style="background:transparent;cursor:default;"></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = heCurrentYear + '-' + String(heCurrentMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        const currentDate = new Date(heCurrentYear, heCurrentMonth, d);
        let classes = 'calendar-day';
        if (currentDate < today) classes += ' past';
        else if (heBookedDates.includes(dateStr)) classes += ' booked';
        else classes += ' available';
        if (dateStr === todayStr) classes += ' today';
        if (heStartDate && dateStr === heStartDate) classes += ' selected';
        html += `<div class="${classes}" data-date="${dateStr}" onclick="heSelectDate('${dateStr}')">${d}</div>`;
    }
    daysContainer.innerHTML = html;
}
function heChangeMonth(delta) { heCurrentMonth += delta; if (heCurrentMonth > 11) { heCurrentMonth = 0; heCurrentYear++; } if (heCurrentMonth < 0) { heCurrentMonth = 11; heCurrentYear--; } heRenderCalendar(); }
function heSelectDate(dateStr) {
    const today = new Date(); today.setHours(0,0,0,0);
    const selectedDate = new Date(dateStr);
    if (selectedDate < today) { alert('Past dates cannot be selected.'); return; }
    if (heBookedDates.includes(dateStr)) { alert('This date is already booked.'); return; }
    heStartDate = dateStr;
    document.getElementById('heDisplayDate').textContent = dateStr;
    document.getElementById('heEventDateHidden').value = dateStr;
    heRenderCalendar();
}
function heUpdateTotalPrice() {
    const total = heHallPrice + hePackagePrice;
    const priceDisplay = document.getElementById('hePriceDisplay');
    if (hePackagePrice > 0) {
        priceDisplay.innerHTML = `
            <span style="color:#c5a263; font-size:1.4rem; font-weight:800; display:block;">Total: LKR ${total.toLocaleString()}</span>
            <span style="font-size:0.85rem; color:#888; display:block;">Hall: LKR ${heHallPrice.toLocaleString()} + Package: LKR ${hePackagePrice.toLocaleString()}</span>
        `;
    } else {
        priceDisplay.innerHTML = 'Starting LKR ' + Number(heHallPrice).toLocaleString();
    }
}

document.getElementById('homeEventHallForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errorDiv = document.getElementById('heErrorMsg'), successDiv = document.getElementById('heSuccessMsg');
    errorDiv.style.display = 'none'; successDiv.style.display = 'none';
    if (!document.getElementById('heEventDateHidden').value) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select an event date.';
        errorDiv.style.display = 'block'; return;
    }
    startBookingPayment({
        form: this, bookingType: 'event_hall', finalizeEndpoint: 'event-booking-process.php',
        onValidationError: function(msg) { errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg; errorDiv.style.display = 'block'; },
        onSuccess: function(data) {
            successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            successDiv.style.display = 'block';
            document.getElementById('heFormContainer').style.display = 'none';
            document.getElementById('heSuccessContainer').style.display = 'block';
            document.getElementById('heSuccessDetails').innerHTML = data.details;
            setTimeout(function() { window.location.href = 'user/my-event-bookings.php'; }, 3000);
        }
    });
});

// ================================================================
// ✅ HOME PACKAGE BOOKING MODAL (WITH PRICE CALCULATION - TOTAL FIRST)
// ================================================================
let hpStartDate = null, hpBookedDates = [], hpCurrentMonth = new Date().getMonth(), hpCurrentYear = new Date().getFullYear(), hpHallCapacity = 0;
let hpPackagePrice = 0;

function openHomePackageModal(packageId, packageName, price, imgUrl) {
    hpStartDate = null; hpBookedDates = []; hpHallCapacity = 0; hpPackagePrice = price;
    document.getElementById('hpPackageId').value = packageId;
    document.getElementById('hpPackageName').textContent = packageName;
    document.getElementById('hpPriceDisplay').innerHTML = 'LKR ' + Number(price).toLocaleString() + ' <small>/ event</small>';
    if (imgUrl && imgUrl !== '') {
        document.getElementById('hpModalImage').innerHTML = `<img src="${imgUrl}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=500';" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        document.getElementById('hpModalImage').innerHTML = '<i class="fas fa-gift"></i>';
    }
    document.getElementById('hpDisplayDate').textContent = '—';
    document.getElementById('hpEventDateHidden').value = '';
    document.getElementById('hpGuests').value = 1;
    document.getElementById('hpHallSelect').value = '';
    document.getElementById('hpCapacityDisplay').innerHTML = '<label>Hall Capacity</label><strong>—</strong>';
    document.getElementById('hpErrorMsg').style.display = 'none';
    document.getElementById('hpSuccessMsg').style.display = 'none';
    document.getElementById('hpFormContainer').style.display = 'block';
    document.getElementById('hpSuccessContainer').style.display = 'none';
    document.getElementById('hpInclusionsList').innerHTML = '<li><i class="fas fa-check-circle"></i> Package includes premium services</li><li><i class="fas fa-check-circle"></i> Customizable to your needs</li>';
    hpUpdateTotalPrice();
    
    document.getElementById('hpHallSelect').onchange = function() {
        const selected = this.options[this.selectedIndex];
        const capacity = selected.getAttribute('data-capacity');
        if (capacity) {
            hpHallCapacity = parseInt(capacity);
            document.getElementById('hpGuests').max = capacity;
            document.getElementById('hpCapacityDisplay').innerHTML = '<label>Hall Capacity</label><strong>' + capacity + ' guests</strong>';
            hpFetchBookedDates(selected.value);
        } else {
            document.getElementById('hpGuests').max = 999;
            document.getElementById('hpCapacityDisplay').innerHTML = '<label>Hall Capacity</label><strong>—</strong>';
            hpBookedDates = [];
            hpRenderCalendar();
        }
        hpUpdateTotalPrice();
    };
    window.hpFetchBookedDates = function(hallId) {
        if (!hallId) return;
        fetch(`get-booked-dates.php?type=hall&id=${hallId}`).then(res => res.json()).then(data => {
            if (data.success) hpBookedDates = data.booked; else hpBookedDates = [];
            hpCurrentMonth = new Date().getMonth(); hpCurrentYear = new Date().getFullYear(); hpRenderCalendar();
        });
    };
    hpCurrentMonth = new Date().getMonth(); hpCurrentYear = new Date().getFullYear(); hpRenderCalendar();
    document.getElementById('homePackageBookingModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeHomePackageModal() { document.getElementById('homePackageBookingModal').classList.remove('active'); document.body.style.overflow = ''; }
document.getElementById('homePackageBookingModal').addEventListener('click', function(e) { if (e.target === this) closeHomePackageModal(); });

function hpRenderCalendar() {
    const daysContainer = document.getElementById('hpCalendarDays');
    const title = document.getElementById('hpCalendarTitle');
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    title.textContent = monthNames[hpCurrentMonth] + ' ' + hpCurrentYear;
    const firstDay = new Date(hpCurrentYear, hpCurrentMonth, 1).getDay();
    const daysInMonth = new Date(hpCurrentYear, hpCurrentMonth + 1, 0).getDate();
    const today = new Date(); const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    today.setHours(0,0,0,0);
    let html = '';
    for (let i = 0; i < firstDay; i++) html += '<div class="calendar-day" style="background:transparent;cursor:default;"></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = hpCurrentYear + '-' + String(hpCurrentMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        const currentDate = new Date(hpCurrentYear, hpCurrentMonth, d);
        let classes = 'calendar-day';
        if (currentDate < today) classes += ' past';
        else if (hpBookedDates.includes(dateStr)) classes += ' booked';
        else classes += ' available';
        if (dateStr === todayStr) classes += ' today';
        if (hpStartDate && dateStr === hpStartDate) classes += ' selected';
        html += `<div class="${classes}" data-date="${dateStr}" onclick="hpSelectDate('${dateStr}')">${d}</div>`;
    }
    daysContainer.innerHTML = html;
}
function hpChangeMonth(delta) { hpCurrentMonth += delta; if (hpCurrentMonth > 11) { hpCurrentMonth = 0; hpCurrentYear++; } if (hpCurrentMonth < 0) { hpCurrentMonth = 11; hpCurrentYear--; } hpRenderCalendar(); }
function hpSelectDate(dateStr) {
    const today = new Date(); today.setHours(0,0,0,0);
    const selectedDate = new Date(dateStr);
    if (selectedDate < today) { alert('Past dates cannot be selected.'); return; }
    if (hpBookedDates.includes(dateStr)) { alert('This date is already booked.'); return; }
    hpStartDate = dateStr;
    document.getElementById('hpDisplayDate').textContent = dateStr;
    document.getElementById('hpEventDateHidden').value = dateStr;
    hpRenderCalendar();
}
function hpUpdateTotalPrice() {
    const priceDisplay = document.getElementById('hpPriceDisplay');
    const hallSelect = document.getElementById('hpHallSelect');
    const selectedHall = hallSelect.options[hallSelect.selectedIndex];
    const hallPrice = parseFloat(selectedHall.getAttribute('data-price')) || 0;
    const totalPrice = hpPackagePrice + hallPrice;
    
    if (hpStartDate && hpHallCapacity > 0) {
        if (hallPrice > 0) {
            priceDisplay.innerHTML = `
                <span style="color:#c5a263; font-size:1.4rem; font-weight:800; display:block;">Total: LKR ${totalPrice.toLocaleString()}</span>
                <span style="font-size:0.85rem; color:#888; display:block;">Package: LKR ${hpPackagePrice.toLocaleString()} + Hall: LKR ${hallPrice.toLocaleString()}</span>
            `;
        } else {
            priceDisplay.innerHTML = `
                <span style="color:#c5a263; font-size:1.4rem; font-weight:800; display:block;">Total: LKR ${totalPrice.toLocaleString()}</span>
                <span style="font-size:0.85rem; color:#888; display:block;">Package: LKR ${hpPackagePrice.toLocaleString()}</span>
            `;
        }
    } else {
        priceDisplay.innerHTML = 'LKR ' + Number(hpPackagePrice).toLocaleString() + ' <small>/ event</small>';
    }
}

document.getElementById('homePackageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errorDiv = document.getElementById('hpErrorMsg'), successDiv = document.getElementById('hpSuccessMsg');
    errorDiv.style.display = 'none'; successDiv.style.display = 'none';
    if (!document.getElementById('hpEventDateHidden').value) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select an event date.';
        errorDiv.style.display = 'block'; return;
    }
    if (!document.getElementById('hpHallSelect').value) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select an event hall.';
        errorDiv.style.display = 'block'; return;
    }
    const guests = parseInt(document.getElementById('hpGuests').value);
    if (hpHallCapacity > 0 && guests > hpHallCapacity) {
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Guests (${guests}) exceeds hall capacity (${hpHallCapacity}).`;
        errorDiv.style.display = 'block'; return;
    }
    startBookingPayment({
        form: this, bookingType: 'package', finalizeEndpoint: 'event-booking-process.php',
        onValidationError: function(msg) { errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg; errorDiv.style.display = 'block'; },
        onSuccess: function(data) {
            successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            successDiv.style.display = 'block';
            document.getElementById('hpFormContainer').style.display = 'none';
            document.getElementById('hpSuccessContainer').style.display = 'block';
            document.getElementById('hpSuccessDetails').innerHTML = data.details;
            setTimeout(function() { window.location.href = 'user/my-package-bookings.php'; }, 3000);
        }
    });
});

// ================================================================
// ✅ LOGIN MODAL
// ================================================================
function triggerLogin(e) {
    if (e) e.preventDefault();
    closeHomeRoomBookingModal(); closeHomeEventHallModal(); closeHomePackageModal();
    const authModal = document.getElementById('authModal');
    if (authModal) { authModal.classList.add('active'); switchAuthTab('login'); }
}
const authModal = document.getElementById('authModal');
const openLoginBtn = document.getElementById('openLoginBtn');
const closeModal = document.querySelector('.close-modal');
const tabs = document.querySelectorAll('.tab-btn');
const loginPane = document.getElementById('loginPane');
const registerPane = document.getElementById('registerPane');
const switchToRegister = document.getElementById('switchToRegister');
const switchToLogin = document.getElementById('switchToLogin');

function switchAuthTab(tab) {
    if(tab === 'login') { loginPane.classList.add('active'); registerPane.classList.remove('active'); tabs[0].classList.add('active'); tabs[1].classList.remove('active'); }
    else { registerPane.classList.add('active'); loginPane.classList.remove('active'); tabs[1].classList.add('active'); tabs[0].classList.remove('active'); }
}
if(openLoginBtn) openLoginBtn.onclick = (e) => { e.preventDefault(); authModal.classList.add('active'); switchAuthTab('login'); };
if(closeModal) closeModal.onclick = () => authModal.classList.remove('active');
window.onclick = (e) => { if(e.target === authModal) authModal.classList.remove('active'); };
tabs[0].onclick = () => switchAuthTab('login');
tabs[1].onclick = () => switchAuthTab('register');
if(switchToRegister) switchToRegister.onclick = (e) => { e.preventDefault(); switchAuthTab('register'); };
if(switchToLogin) switchToLogin.onclick = (e) => { e.preventDefault(); switchAuthTab('login'); };

function togglePassword(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    if(!input || !toggle) return;
    toggle.addEventListener('click', () => {
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        toggle.classList.toggle('fa-eye-slash');
        toggle.classList.toggle('fa-eye');
    });
}
togglePassword('loginPassword', 'toggleLoginPwd');
togglePassword('regPassword', 'toggleRegPwd');
togglePassword('regConfirm', 'toggleRegConfirm');

document.getElementById('loginForm').onsubmit = async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const errDiv = document.getElementById('loginError');
    errDiv.style.display = 'none';
    const res = await fetch('login-process.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    });
    const data = await res.json();
    if(data.success) { location.href = data.redirect; }
    else { errDiv.innerText = data.error; errDiv.style.display = 'block'; }
};

document.getElementById('registerForm').onsubmit = async (e) => {
    e.preventDefault();
    const name = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const pass = document.getElementById('regPassword').value;
    const confirm = document.getElementById('regConfirm').value;
    const errDiv = document.getElementById('registerError');
    errDiv.style.display = 'none';
    if(pass !== confirm) { errDiv.innerText = 'Passwords do not match'; errDiv.style.display = 'block'; return; }
    const res = await fetch('register-process.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(pass)}`
    });
    const data = await res.json();
    if(data.success) {
        alert('Registration successful! Please login.');
        switchAuthTab('login');
        document.getElementById('loginEmail').value = email;
    } else {
        errDiv.innerText = data.error;
        errDiv.style.display = 'block';
    }
};

function addslashes(str) { return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0'); }
</script>
<script src="assets/stripe-payment.js"></script>
</body>
</html>
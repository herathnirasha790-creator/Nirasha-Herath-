<?php
// ================================================================
// START SESSION
// ================================================================
session_start();

// ================================================================
// LOAD PAGE DATA FROM JSON
// ================================================================
$page_data_file = __DIR__ . '/data/pages/rooms.json';
if (file_exists($page_data_file)) {
    $page_data = json_decode(file_get_contents($page_data_file), true);
} else {
    $page_data = [
        'title' => 'Luxury Rooms & Suites',
        'description' => 'Experience unparalleled comfort and elegance in our carefully designed accommodations',
        'images' => [],
        'videos' => []
    ];
}

// ================================================================
// SESSION VARIABLES
// ================================================================
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_avatar = $_SESSION['user_avatar'] ?? 'https://randomuser.me/api/portraits/men/32.jpg';
$user_role = $_SESSION['user_role'] ?? '';

// ✅ Avatar Logic: Check uploaded image first, then UI Avatars
$user_avatar_session = $_SESSION['user_avatar'] ?? '';
$user_avatar = '';
if (!empty($user_avatar_session) && file_exists($user_avatar_session)) {
    $user_avatar = $user_avatar_session;
} else {
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
}

// ================================================================
// DATABASE CONNECTION
// ================================================================
require_once 'config/db_connection.php';

// ================================================================
// FETCH ALL ACTIVE ROOMS
// ================================================================
$rooms = [];
$result = $conn->query("SELECT * FROM rooms WHERE status = 'active' ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $row['features'] = json_decode($row['features'] ?? '[]', true) ?: [];
        $rooms[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_data['title']); ?> | Royal Estate</title>
    
    <!-- ============================================================
    FONTS & ICONS
    ============================================================ -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ============================================================
        RESET & BASE STYLES
        ============================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #fffef8; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c5a263, #8b691f); border-radius: 10px; }

        /* ============================================================
        NAVIGATION BAR
        ============================================================ */
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

        /* ============================================================
        PAGE HEADER
        ============================================================ */
        .page-header {
            height: 40vh;
            margin-top: 70px;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=1600');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        .page-header h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease;
        }
        .page-header p {
            font-size: 1.2rem;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
        BREADCRUMB
        ============================================================ */
        .breadcrumb {
            background: #f5f0eb;
            padding: 1rem 5%;
        }
        .breadcrumb-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .breadcrumb a {
            color: #2c1810;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            color: #c5a263;
        }

        /* ============================================================
        FILTER SECTION - ✅ DYNAMIC ROOM TYPES FROM DATABASE
        ============================================================ */
        .filter-section {
            padding: 2rem 5%;
            background: white;
            border-bottom: 1px solid #f0e5d8;
        }
        .filter-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .filter-title h3 {
            font-size: 1.5rem;
            color: #2c1810;
        }
        .filter-options {
            display: flex;
            gap: 1rem;
        }
        .filter-select {
            padding: 0.7rem 1.5rem;
            border: 1px solid #ddd;
            border-radius: 50px;
            background: white;
            cursor: pointer;
        }

        /* ============================================================
        ROOMS GRID
        ============================================================ */
        .rooms-section {
            padding: 4rem 5%;
        }
        .rooms-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
        }
        .room-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: 0.4s;
            cursor: pointer;
        }
        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        .room-image {
            height: 280px;
            overflow: hidden;
            position: relative;
        }
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.5s;
        }
        .room-card:hover .room-image img {
            transform: scale(1.1);
        }
        .room-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: #c5a263;
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .room-price {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        .room-price span {
            color: #c5a263;
            font-size: 1.2rem;
        }
        .room-content {
            padding: 1.5rem;
        }
        .room-content h3 {
            font-size: 1.4rem;
            color: #2c1810;
            margin-bottom: 0.5rem;
        }
        .room-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .room-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0e5d8;
        }
        .room-features span {
            font-size: 0.8rem;
            color: #555;
            background: #f5f0eb;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .room-features i {
            color: #c5a263;
            font-size: 0.8rem;
        }
        .room-buttons {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }
        .btn-view {
            background: transparent;
            border: 1.5px solid #c5a263;
            color: #c5a263;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-view:hover {
            background: #c5a263;
            color: white;
        }
        .btn-book {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            padding: 0.6rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .btn-book:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(197,162,99,0.3);
        }

        /* ============================================================
        AMENITIES SECTION
        ============================================================ */
        .amenities {
            background: #f9f5f0;
            padding: 4rem 5%;
        }
        .amenities-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-title h2 {
            font-size: 2.5rem;
            color: #2c1810;
            margin-bottom: 0.5rem;
        }
        .section-title .divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #c5a263, #8b691f);
            margin: 1rem auto;
        }
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        .amenity-item {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            transition: 0.3s;
        }
        .amenity-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .amenity-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #c5a26320, #8b691f20);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .amenity-icon i {
            font-size: 2rem;
            color: #c5a263;
        }

        /* ============================================================
        FOOTER
        ============================================================ */
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
        .footer-col h4 {
            color: white;
            margin-bottom: 1rem;
        }
        .footer-col a {
            display: block;
            color: #999;
            text-decoration: none;
            margin-bottom: 0.5rem;
        }
        .footer-col a:hover {
            color: #c5a263;
            transform: translateX(5px);
        }
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .social-links a:hover {
            background: #c5a263;
            transform: translateY(-3px);
        }
        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* ============================================================
        MODAL OVERLAY (Shared)
        ============================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active {
            display: flex;
        }
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
        .modal-body {
            display: flex;
            flex-wrap: wrap;
        }
        .modal-image {
            width: 40%;
            min-height: 420px;
            background: #2c1810;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            overflow: hidden;
        }
        .modal-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .modal-content-wrapper {
            width: 60%;
            padding: 1.8rem;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.1);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            color: #333;
        }
        .modal-close:hover {
            background: #c5a263;
            color: white;
            transform: rotate(90deg);
        }
        .modal-title {
            font-size: 1.6rem;
            color: #2c1810;
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.2rem;
        }
        .modal-price {
            font-size: 1.4rem;
            color: #c5a263;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .modal-features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f9f5f0;
            border-radius: 12px;
        }
        .modal-features-grid span {
            font-size: 0.85rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-features-grid i {
            color: #c5a263;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        .modal-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 0;
            border-top: 1px solid #f0e5d8;
        }
        .modal-meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .modal-meta-item i {
            color: #c5a263;
            font-size: 1rem;
        }
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        /* ============================================================
        BOOKING MODAL (CALENDAR + FORM)
        ============================================================ */
        .calendar-container {
            margin: 0.8rem 0;
            background: #f9f5f0;
            border-radius: 16px;
            padding: 0.8rem;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .calendar-header h4 {
            font-size: 0.95rem;
            color: #2c1810;
            font-weight: 600;
        }
        .calendar-nav {
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            color: #c5a263;
            padding: 0 0.5rem;
            transition: 0.2s;
        }
        .calendar-nav:hover {
            color: #8b691f;
            transform: scale(1.1);
        }
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-size: 0.65rem;
            color: #888;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
            min-height: 200px;
        }
        
        /* ============================================================
        ✅ CALENDAR DAY COLORS
        ============================================================ */
        .calendar-day {
            padding: 5px 0;
            text-align: center;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: 0.2s;
            background: white;
            border: 1px solid transparent;
            font-weight: 500;
        }
        .calendar-day.available {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        .calendar-day.available:hover:not(.booked):not(.disabled) {
            background: #c3e6cb;
        }
        .calendar-day.booked {
            background: #f8d7da !important;
            color: #721c24 !important;
            cursor: not-allowed;
            text-decoration: line-through;
            border-color: #dc3545 !important;
        }
        .calendar-day.past {
            background: #f5f5f5 !important;
            color: #bdbdbd !important;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        .calendar-day.today {
            border-color: #c5a263 !important;
            font-weight: 700;
            position: relative;
        }
        .calendar-day.today::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 5px;
            height: 5px;
            background: #c5a263;
            border-radius: 50%;
        }
        .calendar-day.selected {
            background: #c5a263 !important;
            color: white !important;
            border-color: #c5a263 !important;
        }
        .calendar-day.in-range {
            background: #f0e5d8;
            color: #2c1810;
        }
        .calendar-day.disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }
        
        /* ============================================================
        ✅ CALENDAR LEGEND COLORS
        ============================================================ */
        .calendar-legend {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
            margin-top: 0.5rem;
            font-size: 0.65rem;
            color: #666;
            flex-wrap: wrap;
        }
        .calendar-legend span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            display: inline-block;
            border: 1px solid #ddd;
        }
        .legend-dot.available {
            background: #d4edda;
            border-color: #28a745;
        }
        .legend-dot.booked {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .legend-dot.selected {
            background: #c5a263;
            border-color: #c5a263;
        }
        .legend-dot.today {
            background: white;
            border: 2px solid #c5a263;
        }

        .date-display {
            display: flex;
            gap: 1rem;
            margin: 0.6rem 0;
            background: #f5f0eb;
            padding: 0.6rem 0.8rem;
            border-radius: 12px;
            flex-wrap: wrap;
        }
        .date-display-item {
            flex: 1;
            min-width: 80px;
        }
        .date-display-item label {
            font-size: 0.65rem;
            color: #888;
            display: block;
        }
        .date-display-item strong {
            font-size: 0.85rem;
            color: #2c1810;
        }
        .form-group-modal {
            margin-bottom: 0.6rem;
        }
        .form-group-modal label {
            display: block;
            font-weight: 500;
            font-size: 0.8rem;
            color: #2c1810;
            margin-bottom: 3px;
        }
        .form-group-modal input,
        .form-group-modal select,
        .form-group-modal textarea {
            width: 100%;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .form-group-modal input:focus,
        .form-group-modal select:focus,
        .form-group-modal textarea:focus {
            border-color: #c5a263;
            outline: none;
            box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .form-group-modal textarea {
            resize: vertical;
            min-height: 50px;
        }
        .btn-confirm-booking {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 0.3rem;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
            font-size: 0.95rem;
        }
        .btn-confirm-booking:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197,162,99,0.3);
        }
        .alert-error-modal {
            background: #f8d7da;
            color: #721c24;
            padding: 8px 12px;
            border-radius: 10px;
            margin-bottom: 0.8rem;
            border-left: 4px solid #dc3545;
            display: none;
            font-size: 0.85rem;
        }
        .alert-success-modal {
            background: #d4edda;
            color: #155724;
            padding: 8px 12px;
            border-radius: 10px;
            margin-bottom: 0.8rem;
            border-left: 4px solid #28a745;
            display: none;
            font-size: 0.85rem;
        }
        .hidden-input {
            display: none;
        }

        /* ============================================================
        LOGIN MODAL
        ============================================================ */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
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
        .close-modal:hover {
            color: #c5a263;
        }
        .modal h2 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #2c1810;
        }
        .modal-sub {
            text-align: center;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
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
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c1810;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            padding-right: 40px;
            border: 1px solid #e0d5cc;
            border-radius: 12px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: #999;
        }
        .auth-btn {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: none;
        }
        .register-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        .register-link a {
            color: #c5a263;
            text-decoration: none;
            font-weight: 600;
        }

        /* ============================================================
        RESPONSIVE
        ============================================================ */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
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
            .nav-links.active {
                left: 0;
            }
            .page-header h1 {
                font-size: 2.5rem;
            }
            .rooms-grid {
                grid-template-columns: 1fr;
            }
            .filter-container {
                flex-direction: column;
                text-align: center;
            }
            .modal-body {
                flex-direction: column;
            }
            .modal-image {
                width: 100%;
                min-height: 200px;
                max-height: 250px;
            }
            .modal-content-wrapper {
                width: 100%;
                padding: 1.2rem;
            }
            .calendar-days {
                gap: 2px;
            }
            .calendar-day {
                padding: 4px 0;
                font-size: 0.7rem;
            }
            .date-display {
                flex-direction: column;
                gap: 0.3rem;
            }
            .modal-features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- ============================================================
NAVIGATION
============================================================ -->
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
            <a href="rooms.php" class="active">Rooms</a>
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php">Packages</a>
            <a href="about.php">About</a>
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

<!-- ============================================================
PAGE HEADER
============================================================ -->
<section class="page-header">
    <div>
        <h1><?php echo htmlspecialchars($page_data['title']); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($page_data['description'])); ?></p>
    </div>
</section>

<!-- ============================================================
BREADCRUMB
============================================================ -->
<div class="breadcrumb">
    <div class="breadcrumb-container">
        <a href="index.php">Home</a> / <span>Rooms & Suites</span>
    </div>
</div>

<!-- ============================================================
✅ FILTER SECTION - DYNAMIC ROOM TYPES FROM DATABASE
============================================================ -->
<section class="filter-section">
    <div class="filter-container">
        <div class="filter-title">
            <h3>Our Premium Collection</h3>
            <p><?php echo count($rooms); ?> luxurious rooms and suites available</p>
        </div>
        <div class="filter-options">
            <select class="filter-select" id="roomType">
                <option value="all">All Rooms</option>
                <?php
                // ✅ Get all unique badges from database
                $unique_badges = [];
                foreach ($rooms as $room) {
                    $badge = $room['badge'] ?? 'Premium';
                    if (!in_array($badge, $unique_badges)) {
                        $unique_badges[] = $badge;
                    }
                }
                sort($unique_badges);
                foreach ($unique_badges as $badge):
                ?>
                <option value="<?php echo strtolower($badge); ?>">
                    <?php echo htmlspecialchars($badge); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select class="filter-select" id="sortBy">
                <option value="default">Default</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
            </select>
        </div>
    </div>
</section>

<!-- ============================================================
ROOMS GRID
============================================================ -->
<section class="rooms-section">
    <div class="rooms-container">
        <div class="rooms-grid" id="roomsGrid">
            <?php if (empty($rooms)): ?>
                <div style="grid-column: 1/-1; text-align:center; padding:40px; background:white; border-radius:20px;">
                    <i class="fas fa-bed" style="font-size:3rem; color:#c5a263;"></i>
                    <h3>No Rooms Available</h3>
                    <p>Please check back later for our luxurious accommodations.</p>
                </div>
            <?php else: ?>
                <?php foreach($rooms as $room): 
                    $img = 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600';
                    if (!empty($room['images']) && file_exists($room['images'][0])) {
                        $img = $room['images'][0];
                    } elseif (!empty($room['image']) && file_exists($room['image'])) {
                        $img = $room['image'];
                    }
                    $badge = !empty($room['badge']) ? $room['badge'] : 'Premium';
                    // ✅ Use badge as the data-type for filtering
                    $type = strtolower($badge);
                ?>
                <div class="room-card" data-type="<?php echo $type; ?>" data-price="<?php echo $room['price']; ?>" data-id="<?php echo $room['id']; ?>">
                    <div class="room-image">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                        <div class="room-badge"><?php echo htmlspecialchars($badge); ?></div>
                        <div class="room-price"><span>LKR <?php echo number_format($room['price']); ?></span> / night</div>
                    </div>
                    <div class="room-content">
                        <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                        <p class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 80)) . (strlen($room['description']) > 80 ? '...' : ''); ?></p>
                        <div class="room-features">
                            <?php 
                            if (!empty($room['features']) && is_array($room['features'])): 
                                $feature_count = 0;
                                foreach($room['features'] as $feature):
                                    if ($feature_count >= 3) break;
                                    $feature_count++;
                            ?>
                                    <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($feature); ?></span>
                                <?php endforeach; 
                                if (count($room['features']) > 3): ?>
                                    <span><i class="fas fa-plus-circle"></i> +<?php echo count($room['features']) - 3; ?> more</span>
                                <?php endif;
                            else: ?>
                                <span><i class="fas fa-wifi"></i> Free WiFi</span>
                                <span><i class="fas fa-tv"></i> Smart TV</span>
                                <span><i class="fas fa-wind"></i> AC</span>
                            <?php endif; ?>
                        </div>
                        <div class="room-buttons">
                            <button type="button" class="btn-view" onclick="openRoomModal(<?php echo $room['id']; ?>)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            
                            <?php if ($is_logged_in): ?>
                                <button type="button" class="btn-book" 
                                    data-room-id="<?php echo $room['id']; ?>" 
                                    data-room-name="<?php echo addslashes($room['name']); ?>" 
                                    data-price="<?php echo $room['price']; ?>" 
                                    data-max-guests="<?php echo $room['max_guests']; ?>" 
                                    data-img="<?php echo addslashes($img); ?>">
                                    Book Now →
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-book" onclick="triggerLogin(event)">Book Now →</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================================
AMENITIES SECTION
============================================================ -->
<section class="amenities">
    <div class="amenities-container">
        <div class="section-title">
            <h2>In-Room Amenities</h2>
            <div class="divider"></div>
            <p>Everything you need for a comfortable stay</p>
        </div>
        <div class="amenities-grid">
            <div class="amenity-item">
                <div class="amenity-icon"><i class="fas fa-wifi"></i></div>
                <h4>High-Speed WiFi</h4>
                <p>Free high-speed internet access</p>
            </div>
            <div class="amenity-item">
                <div class="amenity-icon"><i class="fas fa-tv"></i></div>
                <h4>Smart TV</h4>
                <p>55" LED with Netflix</p>
            </div>
            <div class="amenity-item">
                <div class="amenity-icon"><i class="fas fa-coffee"></i></div>
                <h4>Tea & Coffee</h4>
                <p>Complimentary refreshments</p>
            </div>
            <div class="amenity-item">
                <div class="amenity-icon"><i class="fas fa-snowflake"></i></div>
                <h4>AC</h4>
                <p>Individual climate control</p>
            </div>
            <div class="amenity-item">
                <div class="amenity-icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Safe Box</h4>
                <p>In-room digital safe</p>
            </div>
            <div class="amenity-item">
                <div class="amenity-icon"><i class="fas fa-concierge-bell"></i></div>
                <h4>24/7 Room Service</h4>
                <p>Round-the-clock service</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
FOOTER
============================================================ -->
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
            <form action="subscribe.php" method="POST" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <input type="email" name="email" placeholder="Your Email" style="padding: 0.5rem; border-radius: 5px; border: none; flex: 1;" required>
                <button type="submit" style="background: #c5a263; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; 2026 Royal Estate. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #c5a263;"></i> for luxury experiences</p>
    </div>
</footer>

<!-- ============================================================
ROOM DETAILS MODAL
============================================================ -->
<div class="modal-overlay" id="roomModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeRoomModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-body" id="roomModalBody"></div>
    </div>
</div>

<!-- ============================================================
ROOM BOOKING MODAL (WITH CALENDAR)
============================================================ -->
<div class="modal-overlay" id="roomBookingModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeRoomBookingModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-body">
            <div class="modal-image" id="rbModalImage">
                <i class="fas fa-hotel"></i>
            </div>
            <div class="modal-content-wrapper">
                <h2 class="modal-title" id="rbRoomName">Room Name</h2>
                <div class="modal-price" id="rbPriceDisplay">$0 <small>/ night</small></div>
                <p id="rbMaxGuests" style="color:#666; margin-bottom:0.3rem; font-size:0.85rem;">
                    <i class="fas fa-users"></i> Max <span id="rbMaxGuestsNum">2</span> Guests
                </p>

                <form id="roomBookingForm">
                    <input type="hidden" name="room_id" id="rbRoomId">
                    <input type="hidden" name="check_in" id="rbCheckInHidden">
                    <input type="hidden" name="check_out" id="rbCheckOutHidden">
                    <input type="hidden" name="booking_type" value="room">

                    <!-- ============================================================
                    CALENDAR
                    ============================================================ -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" onclick="rbChangeMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h4 id="rbCalendarTitle">January 2024</h4>
                            <button type="button" class="calendar-nav" onclick="rbChangeMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="calendar-weekdays">
                            <span>Sun</span>
                            <span>Mon</span>
                            <span>Tue</span>
                            <span>Wed</span>
                            <span>Thu</span>
                            <span>Fri</span>
                            <span>Sat</span>
                        </div>
                        <div class="calendar-days" id="rbCalendarDays"></div>
                        <div class="calendar-legend">
                            <span><span class="legend-dot available"></span> Available</span>
                            <span><span class="legend-dot booked"></span> Booked</span>
                            <span><span class="legend-dot selected"></span> Selected</span>
                            <span><span class="legend-dot today"></span> Today</span>
                        </div>
                    </div>

                    <!-- ============================================================
                    DATE DISPLAY
                    ============================================================ -->
                    <div class="date-display">
                        <div class="date-display-item">
                            <label>Check In</label>
                            <strong id="rbDisplayCheckIn">—</strong>
                        </div>
                        <div class="date-display-item">
                            <label>Check Out</label>
                            <strong id="rbDisplayCheckOut">—</strong>
                        </div>
                        <div class="date-display-item">
                            <label>Nights</label>
                            <strong id="rbDisplayNights">0</strong>
                        </div>
                    </div>

                    <!-- ============================================================
                    GUESTS
                    ============================================================ -->
                    <div class="form-group-modal">
                        <label><i class="fas fa-user-friends"></i> Guests (max <span id="rbMaxGuestsNum2">2</span>)</label>
                        <input type="number" name="guests" id="rbGuests" min="1" value="1" required>
                    </div>

                    <!-- ============================================================
                    SPECIAL REQUESTS
                    ============================================================ -->
                    <div class="form-group-modal">
                        <label><i class="fas fa-pencil-alt"></i> Special Requests</label>
                        <textarea name="special_requests" id="rbSpecial" rows="2" placeholder="e.g., late check-in, extra pillows..."></textarea>
                    </div>

                    <!-- ============================================================
                    ERROR / SUCCESS MESSAGES
                    ============================================================ -->
                    <div id="rbErrorMsg" class="alert-error-modal"></div>
                    <div id="rbSuccessMsg" class="alert-success-modal"></div>

                    <!-- ============================================================
                    ✅ FORM CONTAINER (Payment Flow)
                    ============================================================ -->
                    <div id="rbFormContainer">
                        <button type="submit" class="btn-confirm-booking">
                            <i class="fas fa-check-circle"></i> Confirm Booking
                        </button>
                    </div>

                    <!-- ============================================================
                    SUCCESS CONTAINER
                    ============================================================ -->
                    <div id="rbSuccessContainer" style="display:none;">
                        <div id="rbSuccessDetails" style="background:#fef5e6; padding:1rem; border-radius:12px; margin-top:0.5rem;"></div>
                        <a href="user/my-room-bookings.php" style="display:inline-block; margin-top:0.5rem; color:#c5a263; font-weight:600;">
                            View My Bookings →
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
LOGIN MODAL
============================================================ -->
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
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="loginEmail" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="loginPassword" placeholder="Enter your password" required>
                    <i class="fas fa-eye-slash password-toggle" id="toggleLoginPwd"></i>
                </div>
                <button type="submit" class="auth-btn">Sign In →</button>
            </form>
            <div class="register-link">New user? <a href="#" id="switchToRegister">Register</a></div>
        </div>
        <div id="registerPane" class="tab-pane">
            <div id="registerError" class="error-msg"></div>
            <form id="registerForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="regName" placeholder="Your full name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="regEmail" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="regPassword" placeholder="Create a password" required>
                    <i class="fas fa-eye-slash password-toggle" id="toggleRegPwd"></i>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" id="regConfirm" placeholder="Confirm your password" required>
                    <i class="fas fa-eye-slash password-toggle" id="toggleRegConfirm"></i>
                </div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>
            <div class="register-link">Already have an account? <a href="#" id="switchToLogin">Sign In</a></div>
        </div>
    </div>
</div>

<!-- ============================================================
JAVASCRIPT
============================================================ -->
<script>
    // ================================================================
    // 1. NAVBAR SCROLL EFFECT
    // ================================================================
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        }
    });

    // ================================================================
    // 2. MOBILE MENU TOGGLE
    // ================================================================
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
    // ✅ 3. CLICK-BASED USER DROPDOWN
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
    // 4. FILTER & SORT - ✅ DYNAMIC FILTER USING DATA-TYPE
    // ================================================================
    let originalRooms = [];
    function initOriginalRooms() {
        const roomsGrid = document.getElementById('roomsGrid');
        if (roomsGrid) {
            originalRooms = Array.from(roomsGrid.querySelectorAll('.room-card'));
        }
    }
    const roomTypeSelect = document.getElementById('roomType');
    const sortBySelect = document.getElementById('sortBy');
    const roomsGrid = document.getElementById('roomsGrid');

    function filterAndSortRooms() {
        if (!roomsGrid) return;
        let rooms = Array.from(document.querySelectorAll('.room-card'));
        if (roomTypeSelect && roomTypeSelect.value !== 'all') {
            const selectedType = roomTypeSelect.value.toLowerCase();
            rooms = rooms.filter(room => {
                const roomType = (room.getAttribute('data-type') || 'premium').toLowerCase();
                return roomType === selectedType;
            });
        }
        if (sortBySelect && sortBySelect.value === 'price-low') {
            rooms.sort((a, b) => parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price')));
        } else if (sortBySelect && sortBySelect.value === 'price-high') {
            rooms.sort((a, b) => parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price')));
        } else {
            rooms = [...originalRooms];
        }
        
        roomsGrid.innerHTML = '';
        
        if (rooms.length === 0) {
            const noResult = document.createElement('div');
            noResult.style.cssText = 'grid-column: 1/-1; text-align:center; padding:60px 20px; background:white; border-radius:20px;';
            noResult.innerHTML = `
                <i class="fas fa-search" style="font-size:3rem; color:#c5a263; margin-bottom:1rem; display:block;"></i>
                <h3 style="color:#2c1810;">No rooms found</h3>
                <p style="color:#666;">Try changing your filter or view all rooms.</p>
            `;
            roomsGrid.appendChild(noResult);
        } else {
            rooms.forEach(room => roomsGrid.appendChild(room));
        }
    }

    window.addEventListener('DOMContentLoaded', function() {
        initOriginalRooms();
        if (roomTypeSelect) {
            roomTypeSelect.addEventListener('change', filterAndSortRooms);
        }
        if (sortBySelect) {
            sortBySelect.addEventListener('change', filterAndSortRooms);
        }
        filterAndSortRooms();
    });

    // ================================================================
    // 5. BOOK NOW BUTTON - EVENT DELEGATION
    // ================================================================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-book[data-room-id]');
        if (!btn) return;
        e.preventDefault();
        
        const roomId = btn.getAttribute('data-room-id');
        const roomName = btn.getAttribute('data-room-name');
        const price = parseInt(btn.getAttribute('data-price'));
        const maxGuests = parseInt(btn.getAttribute('data-max-guests'));
        const img = btn.getAttribute('data-img');
        
        openRoomBookingModal(roomId, roomName, price, maxGuests, img);
    });

    // ================================================================
    // 6. ROOM DETAILS MODAL
    // ================================================================
    const roomsData = <?php echo json_encode($rooms); ?>;

    function openRoomModal(roomId) {
        const modal = document.getElementById('roomModal');
        const modalBody = document.getElementById('roomModalBody');
        const room = roomsData.find(r => r.id == roomId);
        if (!room) return;

        let img = 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600';
        if (room.images && room.images.length > 0) {
            img = room.images[0];
        } else if (room.image) {
            img = room.image;
        }

        let featuresHtml = '';
        if (room.features && room.features.length > 0) {
            room.features.forEach(f => {
                featuresHtml += `<span><i class="fas fa-check-circle"></i> ${escapeHtml(f)}</span>`;
            });
        } else {
            featuresHtml = `
                <span><i class="fas fa-wifi"></i> Free WiFi</span>
                <span><i class="fas fa-tv"></i> Smart TV</span>
                <span><i class="fas fa-wind"></i> Air Conditioning</span>
                <span><i class="fas fa-coffee"></i> Mini Bar</span>
            `;
        }

        const maxGuests = room.max_guests ? room.max_guests : 2;
        let bookButtonHtml = '';

        <?php if ($is_logged_in): ?>
            bookButtonHtml = `
                <button type="button" class="btn-book" onclick="closeRoomModal(); openRoomBookingModal(${room.id}, '${addslashes(room.name)}', ${room.price}, ${maxGuests}, '${addslashes(img)}')">
                    <i class="fas fa-calendar-check"></i> Book Now
                </button>
            `;
        <?php else: ?>
            bookButtonHtml = `
                <button type="button" class="btn-book" onclick="triggerLogin(event)">
                    <i class="fas fa-user-lock"></i> Login to Book
                </button>
            `;
        <?php endif; ?>

        modalBody.innerHTML = `
            <div class="modal-image">
                <img src="${img}" 
                     onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600';" 
                     style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div class="modal-content-wrapper">
                <span class="modal-badge" style="background:#c5a263;color:white;padding:0.2rem 1rem;border-radius:20px;font-size:0.75rem;display:inline-block;">
                    ${escapeHtml(room.badge || 'Premium')}
                </span>
                <h2 class="modal-title">${escapeHtml(room.name)}</h2>
                <div class="modal-price">LKR ${room.price.toLocaleString()} <small>/ night</small></div>
                <p class="modal-description">${escapeHtml(room.description || '')}</p>
                <div class="modal-features-grid">${featuresHtml}</div>
                <div class="modal-meta">
                    <div class="modal-meta-item"><i class="fas fa-users"></i> ${maxGuests} Guests</div>
                    <div class="modal-meta-item"><i class="fas fa-bed"></i> ${maxGuests > 2 ? 'Queen Bed' : 'King Bed'}</div>
                    <div class="modal-meta-item"><i class="fas fa-bath"></i> Luxury Bath</div>
                </div>
                <div class="modal-actions">${bookButtonHtml}</div>
            </div>
        `;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeRoomModal() {
        document.getElementById('roomModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('roomModal').addEventListener('click', function(e) {
        if (e.target === this) closeRoomModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeRoomModal();
    });

    // ================================================================
    // 7. ROOM BOOKING MODAL - CALENDAR WITH PRICE CALCULATION
    // ================================================================
    let rbStartDate = null;
    let rbEndDate = null;
    let rbBookedDates = [];
    let rbCurrentMonth = new Date().getMonth();
    let rbCurrentYear = new Date().getFullYear();
    let rbToday = new Date();
    let rbTodayStr = rbToday.getFullYear() + '-' + 
        String(rbToday.getMonth() + 1).padStart(2, '0') + '-' + 
        String(rbToday.getDate()).padStart(2, '0');

    // ✅ Store room price for calculation
    let rbRoomPrice = 0;

    function openRoomBookingModal(roomId, roomName, price, maxGuests, imgUrl) {
        rbStartDate = null;
        rbEndDate = null;
        rbBookedDates = [];
        rbRoomPrice = price; // ✅ Store price

        document.getElementById('rbRoomId').value = roomId;
        document.getElementById('rbRoomName').textContent = roomName;
        document.getElementById('rbPriceDisplay').innerHTML = 'LKR ' + Number(price).toLocaleString() + ' <small>/ night</small>';
        
        document.getElementById('rbMaxGuestsNum').textContent = maxGuests;
        document.getElementById('rbMaxGuestsNum2').textContent = maxGuests;
        
        document.getElementById('rbGuests').max = maxGuests;
        document.getElementById('rbGuests').value = 1;

        if (imgUrl && imgUrl !== '') {
            document.getElementById('rbModalImage').innerHTML = `
                <img src="${imgUrl}" 
                     onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600';" 
                     style="width:100%;height:100%;object-fit:cover;">
            `;
        } else {
            document.getElementById('rbModalImage').innerHTML = '<i class="fas fa-hotel"></i>';
        }

        document.getElementById('rbDisplayCheckIn').textContent = '—';
        document.getElementById('rbDisplayCheckOut').textContent = '—';
        document.getElementById('rbDisplayNights').textContent = '0';
        document.getElementById('rbCheckInHidden').value = '';
        document.getElementById('rbCheckOutHidden').value = '';

        // ✅ Reset total price display
        updateRbTotalPrice();

        document.getElementById('rbErrorMsg').style.display = 'none';
        document.getElementById('rbSuccessMsg').style.display = 'none';
        document.getElementById('rbFormContainer').style.display = 'block';
        document.getElementById('rbSuccessContainer').style.display = 'none';

        fetch(`get-booked-dates.php?type=room&id=${roomId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    rbBookedDates = data.booked;
                }
                rbCurrentMonth = new Date().getMonth();
                rbCurrentYear = new Date().getFullYear();
                rbRenderCalendar();
            });

        document.getElementById('roomBookingModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeRoomBookingModal() {
        document.getElementById('roomBookingModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('roomBookingModal').addEventListener('click', function(e) {
        if (e.target === this) closeRoomBookingModal();
    });

    // ================================================================
    // 7.1. RENDER CALENDAR
    // ================================================================
    function rbRenderCalendar() {
        const daysContainer = document.getElementById('rbCalendarDays');
        const title = document.getElementById('rbCalendarTitle');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                            'July', 'August', 'September', 'October', 'November', 'December'];
        title.textContent = monthNames[rbCurrentMonth] + ' ' + rbCurrentYear;

        const firstDay = new Date(rbCurrentYear, rbCurrentMonth, 1).getDay();
        const daysInMonth = new Date(rbCurrentYear, rbCurrentMonth + 1, 0).getDate();

        let html = '';
        
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day disabled"></div>';
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = rbCurrentYear + '-' + 
                String(rbCurrentMonth + 1).padStart(2, '0') + '-' + 
                String(d).padStart(2, '0');

            let classes = 'calendar-day';
            
            if (dateStr < rbTodayStr) {
                classes += ' past';
                if (rbBookedDates.includes(dateStr)) {
                    classes += ' booked';
                }
            } else {
                if (rbBookedDates.includes(dateStr)) {
                    classes += ' booked';
                } else {
                    classes += ' available';
                }
                
                if (dateStr === rbTodayStr) {
                    classes += ' today';
                }
                
                if (rbStartDate && rbEndDate) {
                    if (dateStr >= rbStartDate && dateStr <= rbEndDate) {
                        classes += ' in-range';
                    }
                    if (dateStr === rbStartDate || dateStr === rbEndDate) {
                        classes += ' selected';
                    }
                } else if (rbStartDate && dateStr === rbStartDate) {
                    classes += ' selected';
                }
            }

            html += `<div class="${classes}" data-date="${dateStr}" onclick="rbSelectDate('${dateStr}')">${d}</div>`;
        }

        daysContainer.innerHTML = html;
    }

    // ================================================================
    // 7.2. MONTH NAVIGATION
    // ================================================================
    function rbChangeMonth(delta) {
        rbCurrentMonth += delta;
        if (rbCurrentMonth > 11) {
            rbCurrentMonth = 0;
            rbCurrentYear++;
        }
        if (rbCurrentMonth < 0) {
            rbCurrentMonth = 11;
            rbCurrentYear--;
        }
        rbRenderCalendar();
    }

    // ================================================================
    // 7.3. DATE SELECTION WITH PRICE UPDATE
    // ================================================================
    function rbSelectDate(dateStr) {
        if (rbBookedDates.includes(dateStr)) {
            alert('This date is already booked. Please select another date.');
            return;
        }

        if (dateStr < rbTodayStr) {
            alert('Please select a future date.');
            return;
        }

        if (!rbStartDate) {
            rbStartDate = dateStr;
            rbEndDate = null;
        } else if (!rbEndDate) {
            if (dateStr < rbStartDate) {
                rbEndDate = rbStartDate;
                rbStartDate = dateStr;
            } else if (dateStr === rbStartDate) {
                rbStartDate = null;
                rbEndDate = null;
                updateRbDateDisplay();
                rbRenderCalendar();
                return;
            } else {
                rbEndDate = dateStr;
            }
        } else {
            rbStartDate = dateStr;
            rbEndDate = null;
        }
        updateRbDateDisplay();
        rbRenderCalendar();
    }

    // ================================================================
    // 7.4. UPDATE DATE DISPLAY WITH TOTAL PRICE
    // ================================================================
    function updateRbDateDisplay() {
        const checkInDisplay = document.getElementById('rbDisplayCheckIn');
        const checkOutDisplay = document.getElementById('rbDisplayCheckOut');
        const nightsDisplay = document.getElementById('rbDisplayNights');
        const hiddenIn = document.getElementById('rbCheckInHidden');
        const hiddenOut = document.getElementById('rbCheckOutHidden');

        if (rbStartDate) {
            checkInDisplay.textContent = rbStartDate;
            hiddenIn.value = rbStartDate;
        } else {
            checkInDisplay.textContent = '—';
            hiddenIn.value = '';
        }

        if (rbEndDate) {
            checkOutDisplay.textContent = rbEndDate;
            hiddenOut.value = rbEndDate;
            const start = new Date(rbStartDate);
            const end = new Date(rbEndDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            nightsDisplay.textContent = diffDays;
        } else {
            checkOutDisplay.textContent = '—';
            hiddenOut.value = '';
            nightsDisplay.textContent = '0';
        }
        
        // ✅ Update total price every time dates change
        updateRbTotalPrice();
    }

    // ================================================================
    // 7.5. UPDATE TOTAL PRICE DISPLAY
    // ================================================================
    function updateRbTotalPrice() {
        const nightsDisplay = document.getElementById('rbDisplayNights');
        const nights = parseInt(nightsDisplay.textContent) || 0;
        const totalPrice = nights * rbRoomPrice;
        
        // Update the price display in the modal
        const priceDisplay = document.getElementById('rbPriceDisplay');
        if (nights > 0) {
            priceDisplay.innerHTML = `
                <span style="font-size:0.9rem; color:#888; font-weight:normal;">
                    LKR ${rbRoomPrice.toLocaleString()} × ${nights} night${nights > 1 ? 's' : ''}
                </span><br>
                <span style="color:#c5a263; font-size:1.6rem; font-weight:800;">
                    Total: LKR ${totalPrice.toLocaleString()}
                </span>
            `;
        } else {
            priceDisplay.innerHTML = 'LKR ' + Number(rbRoomPrice).toLocaleString() + ' <small>/ night</small>';
        }
    }

    // ================================================================
    // 7.6. ROOM BOOKING FORM SUBMIT
    // ================================================================
    document.getElementById('roomBookingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const errorDiv = document.getElementById('rbErrorMsg');
        const successDiv = document.getElementById('rbSuccessMsg');
        const formContainer = document.getElementById('rbFormContainer');

        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        const checkIn = document.getElementById('rbCheckInHidden').value;
        const checkOut = document.getElementById('rbCheckOutHidden').value;

        if (!checkIn || !checkOut) {
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select both check-in and check-out dates from the calendar.';
            errorDiv.style.display = 'block';
            return;
        }

        startBookingPayment({
            form: this,
            bookingType: 'room',
            finalizeEndpoint: 'room-booking-process.php',
            onValidationError: function(msg) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg;
                errorDiv.style.display = 'block';
            },
            onSuccess: function(data) {
                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                successDiv.style.display = 'block';
                document.getElementById('rbFormContainer').style.display = 'none';
                document.getElementById('rbSuccessContainer').style.display = 'block';
                document.getElementById('rbSuccessDetails').innerHTML = data.details;
                
                setTimeout(function() {
                    window.location.href = 'user/my-room-bookings.php';
                }, 3000);
            }
        });
    });

    // ================================================================
    // 9. LOGIN MODAL FUNCTIONS
    // ================================================================
    function triggerLogin(e) {
        if (e) e.preventDefault();
        closeRoomModal();
        closeRoomBookingModal();
        const authModal = document.getElementById('authModal');
        if (authModal) {
            authModal.classList.add('active');
            switchAuthTab('login');
        }
    }

    const authModal = document.getElementById('authModal');
    const openLoginBtn = document.getElementById('openLoginBtn');
    const closeAuthModal = document.querySelector('#authModal .close-modal');
    const tabs = document.querySelectorAll('#authModal .modal-tabs .tab-btn');
    const loginPane = document.getElementById('loginPane');
    const registerPane = document.getElementById('registerPane');
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');

    function switchAuthTab(tab) {
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
            authModal.classList.add('active');
            switchAuthTab('login');
        });
    }
    if (closeAuthModal) {
        closeAuthModal.addEventListener('click', function() {
            authModal.classList.remove('active');
        });
    }
    window.addEventListener('click', function(e) {
        if (e.target === authModal) authModal.classList.remove('active');
    });
    if (tabs[0]) {
        tabs[0].addEventListener('click', function() {
            switchAuthTab('login');
        });
    }
    if (tabs[1]) {
        tabs[1].addEventListener('click', function() {
            switchAuthTab('register');
        });
    }
    if (switchToRegister) {
        switchToRegister.addEventListener('click', function(e) {
            e.preventDefault();
            switchAuthTab('register');
        });
    }
    if (switchToLogin) {
        switchToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            switchAuthTab('login');
        });
    }

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
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
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
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(pass)}`
            });
            const data = await res.json();
            if (data.success) {
                alert('Registration successful! Please login.');
                switchAuthTab('login');
                document.getElementById('loginEmail').value = email;
            } else {
                errDiv.innerText = data.error;
                errDiv.style.display = 'block';
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function addslashes(str) {
        return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }
</script>
<!-- ============================================================
✅ STRIPE PAYMENT JS
============================================================ -->
<script src="assets/stripe-payment.js"></script>
</body>
</html>
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

require_once 'config/db_connection.php';

// FETCH ALL ACTIVE HALLS
$halls = [];
$result = $conn->query("SELECT * FROM event_halls WHERE status = 'active' ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $row['amenities'] = json_decode($row['amenities'] ?? '[]', true) ?: [];
        $halls[] = $row;
    }
}

// FETCH PACKAGES FOR DROPDOWN
$packages = [];
$pkg_result = $conn->query("SELECT id, name, price FROM packages WHERE status = 'active' ORDER BY category, price");
if ($pkg_result) {
    while ($row = $pkg_result->fetch_assoc()) {
        $packages[] = $row;
    }
}

function getAmenityIcon($text) {
    $text = strtolower($text);
    if (strpos($text, 'guest') !== false || strpos($text, 'capacity') !== false) return 'fa-users';
    if (strpos($text, 'sound') !== false || strpos($text, 'music') !== false) return 'fa-music';
    if (strpos($text, 'led') !== false || strpos($text, 'screen') !== false) return 'fa-video';
    if (strpos($text, 'parking') !== false) return 'fa-parking';
    if (strpos($text, 'garden') !== false || strpos($text, 'view') !== false) return 'fa-tree';
    if (strpos($text, 'projector') !== false) return 'fa-projector';
    if (strpos($text, 'microphone') !== false) return 'fa-microphone';
    if (strpos($text, 'bar') !== false || strpos($text, 'cocktail') !== false) return 'fa-cocktail';
    if (strpos($text, 'stage') !== false || strpos($text, 'lighting') !== false) return 'fa-lightbulb';
    if (strpos($text, 'pool') !== false) return 'fa-swimming-pool';
    if (strpos($text, 'dj') !== false) return 'fa-headphones';
    if (strpos($text, 'whiteboard') !== false || strpos($text, 'board') !== false) return 'fa-chalkboard';
    if (strpos($text, 'print') !== false) return 'fa-print';
    if (strpos($text, 'butler') !== false || strpos($text, 'service') !== false) return 'fa-concierge-bell';
    if (strpos($text, 'wifi') !== false) return 'fa-wifi';
    if (strpos($text, 'coffee') !== false || strpos($text, 'refreshment') !== false) return 'fa-coffee';
    return 'fa-check-circle';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Halls | Royal Estate - Grand Wedding & Party Venues</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== ORIGINAL STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; overflow-x: hidden; background: #fffef8; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c5a263, #8b691f); border-radius: 10px; }

        .navbar {
            position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px); z-index: 1000; padding: 1rem 5%;
            transition: all 0.3s ease; box-shadow: 0 2px 20px rgba(0,0,0,0.05);
        }
        .navbar.scrolled { padding: 0.8rem 5%; background: rgba(255, 255, 255, 0.98); box-shadow: 0 4px 30px rgba(0,0,0,0.1); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .logo h1 { font-size: 1.8rem; font-weight: 700; background: linear-gradient(135deg, #2c1810, #c5a263); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 1px; }
        .logo p { font-size: 0.7rem; color: #c5a263; letter-spacing: 3px; margin-top: -5px; }
        .nav-links { display: flex; gap: 2rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; color: #2c1810; font-weight: 500; transition: 0.3s; position: relative; }
        .nav-links a::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 0%; height: 2px; background: linear-gradient(90deg, #c5a263, #8b691f); transition: 0.3s; }
        .nav-links a:hover::after, .nav-links a.active::after { width: 100%; }
        .nav-links a:hover { color: #c5a263; }
        .signin-btn { background: linear-gradient(135deg, #c5a263, #8b691f); color: white !important; padding: 0.6rem 1.5rem; border-radius: 50px; border: none; font-weight: 600; cursor: pointer; }
        .menu-toggle { display: none; font-size: 1.5rem; cursor: pointer; }

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

        .page-header {
            height: 45vh; background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=1600');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; align-items: center; justify-content: center; text-align: center; color: white; margin-top: 70px;
        }
        .page-header h1 { font-size: 4rem; margin-bottom: 1rem; animation: fadeInUp 0.8s ease; }
        .page-header p { font-size: 1.2rem; animation: fadeInUp 0.8s ease 0.2s both; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .breadcrumb { background: #f5f0eb; padding: 1rem 5%; }
        .breadcrumb-container { max-width: 1400px; margin: 0 auto; }
        .breadcrumb a { color: #2c1810; text-decoration: none; transition: 0.3s; }
        .breadcrumb a:hover { color: #c5a263; }
        .breadcrumb span { color: #c5a263; }

        .filter-section { padding: 2rem 5%; background: white; border-bottom: 1px solid #f0e5d8; }
        .filter-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .filter-title h3 { font-size: 1.5rem; color: #2c1810; }
        .filter-title p { color: #666; font-size: 0.9rem; }
        .filter-options { display: flex; gap: 1rem; flex-wrap: wrap; }
        .filter-select { padding: 0.7rem 1.5rem; border: 1px solid #ddd; border-radius: 50px; background: white; font-family: 'Poppins', sans-serif; cursor: pointer; transition: 0.3s; }
        .filter-select:hover { border-color: #c5a263; }

        .halls-section { padding: 4rem 5%; }
        .halls-container { max-width: 1400px; margin: 0 auto; }
        .halls-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 2rem; }

        .hall-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.4s; cursor: pointer; }
        .hall-card:hover { transform: translateY(-10px); box-shadow: 0 25px 50px rgba(0,0,0,0.15); }
        .hall-image { height: 280px; overflow: hidden; position: relative; }
        .hall-image img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .hall-card:hover .hall-image img { transform: scale(1.1); }
        .hall-badge { position: absolute; top: 1rem; left: 1rem; background: #c5a263; color: white; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .hall-price { position: absolute; bottom: 1rem; right: 1rem; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem 1rem; border-radius: 10px; font-weight: 600; }
        .hall-price span { color: #c5a263; font-size: 1.2rem; }
        .hall-content { padding: 1.5rem; }
        .hall-content h3 { font-size: 1.4rem; color: #2c1810; margin-bottom: 0.5rem; }
        .hall-description { color: #666; line-height: 1.6; margin-bottom: 1rem; }
        .hall-features { display: flex; flex-wrap: wrap; gap: 0.8rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f0e5d8; }
        .hall-features span { font-size: 0.8rem; color: #555; background: #f5f0eb; padding: 0.3rem 0.8rem; border-radius: 20px; display: inline-flex; align-items: center; gap: 0.3rem; }
        .hall-features i { color: #c5a263; font-size: 0.8rem; }
        .hall-buttons { display: flex; gap: 1rem; justify-content: space-between; align-items: center; }
        .btn-view { background: transparent; border: 1.5px solid #c5a263; color: #c5a263; padding: 0.6rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
        .btn-view:hover { background: #c5a263; color: white; }
        .btn-plan { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; padding: 0.6rem 1.8rem; border-radius: 50px; text-decoration: none; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .btn-plan:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(197,162,99,0.3); }

        .why-choose { background: linear-gradient(135deg, #f9f5f0, #fff9f2); padding: 4rem 5%; }
        .why-container { max-width: 1400px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 3rem; }
        .section-title h2 { font-size: 2.5rem; color: #2c1810; margin-bottom: 0.5rem; }
        .section-title .divider { width: 80px; height: 3px; background: linear-gradient(90deg, #c5a263, #8b691f); margin: 1rem auto; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .feature-card { background: white; padding: 2rem; border-radius: 20px; text-align: center; transition: 0.3s; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .feature-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #c5a26320, #8b691f20); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
        .feature-icon i { font-size: 2.5rem; color: #c5a263; }
        .feature-card h4 { font-size: 1.2rem; color: #2c1810; margin-bottom: 0.5rem; }
        .feature-card p { color: #666; font-size: 0.9rem; }

        .cta-section { background: linear-gradient(135deg, #2c1810, #1a0f0a); color: white; padding: 4rem 5%; text-align: center; }
        .cta-container { max-width: 800px; margin: 0 auto; }
        .cta-section h2 { font-size: 2.5rem; margin-bottom: 1rem; }
        .cta-section p { font-size: 1.1rem; margin-bottom: 2rem; opacity: 0.9; }
        .cta-btn { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: 0.3s; display: inline-block; }
        .cta-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(197,162,99,0.4); }

        footer { background: #1a0f0a; color: #999; padding: 3rem 5% 1rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 1400px; margin: 0 auto; }
        .footer-col h4 { color: white; margin-bottom: 1rem; font-size: 1.2rem; }
        .footer-col a { display: block; color: #999; text-decoration: none; margin-bottom: 0.5rem; transition: 0.3s; }
        .footer-col a:hover { color: #c5a263; transform: translateX(5px); }
        .social-links { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-links a { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .social-links a:hover { background: #c5a263; transform: translateY(-3px); }
        .copyright { text-align: center; padding-top: 2rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }

        .menu-toggle { display: none; font-size: 1.5rem; cursor: pointer; }
        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .nav-links { position: fixed; top: 70px; left: -100%; width: 100%; background: white; flex-direction: column; padding: 2rem; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
            .nav-links.active { left: 0; }
            .page-header h1 { font-size: 2.5rem; }
            .halls-grid { grid-template-columns: 1fr; }
            .filter-container { flex-direction: column; text-align: center; }
            .cta-section h2 { font-size: 1.8rem; }
        }

        /* ============================================================ */
        /* ✅ UPDATED CALENDAR & MODAL STYLES */
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
            .modal-body { flex-direction: column; }
            .modal-image { width: 100%; min-height: 200px; }
            .modal-content-wrapper { width: 100%; padding: 1.2rem; }
            .calendar-days { gap: 2px; }
            .calendar-day { padding: 4px 0; font-size: 0.7rem; }
            .date-display { flex-direction: column; gap: 0.3rem; }
        }

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
        .modal-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #eee; }
        .tab-btn { flex: 1; text-align: center; background: none; border: none; padding: 0.8rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: #666; }
        .tab-btn.active { color: #c5a263; border-bottom: 2px solid #c5a263; }
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

<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo"><h1>ROYAL ESTATE</h1><p>LUXURY & ELEGANCE</p></div>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="event-halls.php" class="active">Event Halls</a>
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
                <button class="signin-btn" id="openLoginBtn"><i class="fas fa-user"></i> Sign In</button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Page Header -->
<section class="page-header">
    <div>
        <h1>Grand Event Halls</h1>
        <p>Celebrate your special moments in our magnificent venues</p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="breadcrumb-container">
        <a href="index.php">Home</a> / <span>Event Halls</span>
    </div>
</div>

<!-- Filter Section -->
<section class="filter-section">
    <div class="filter-container">
        <div class="filter-title">
            <h3>Our Premium Venues</h3>
            <p><span id="hallCountDisplay"><?php echo count($halls); ?></span> magnificent event halls for every occasion</p>
        </div>
        <div class="filter-options">
            <select class="filter-select" id="hallType">
                <option value="all">All Halls</option>
                <option value="wedding">Wedding Halls</option>
                <option value="party">Party Halls</option>
                <option value="conference">Conference Halls</option>
            </select>
            <select class="filter-select" id="capacitySort">
                <option value="default">Default</option>
                <option value="capacity-low">Capacity: Low to High</option>
                <option value="capacity-high">Capacity: High to Low</option>
            </select>
        </div>
    </div>
</section>

<!-- Halls Grid -->
<section class="halls-section">
    <div class="halls-container">
        <div class="halls-grid" id="hallsGrid">
            <?php if (empty($halls)): ?>
                <div style="grid-column: 1/-1; text-align:center; padding:40px; background:white; border-radius:20px;">
                    <i class="fas fa-building" style="font-size:3rem; color:#c5a263;"></i>
                    <h3>No Event Halls Available</h3>
                    <p>Please check back later for our magnificent venues.</p>
                </div>
            <?php else: ?>
                <!-- No Results Message (Hidden by default) -->
                <div id="noHallsMessageEl" style="display:none; grid-column: 1/-1; text-align:center; padding:60px 20px; background:white; border-radius:20px;">
                    <i class="fas fa-search" style="font-size:3rem; color:#c5a263; margin-bottom:1rem; display:block;"></i>
                    <h3 style="color:#2c1810;">No halls found</h3>
                    <p style="color:#666;">Try changing your filter or view all halls.</p>
                </div>

                <?php foreach($halls as $index => $hall): 
                    $img = 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600';
                    if (!empty($hall['images']) && is_array($hall['images']) && file_exists($hall['images'][0])) {
                        $img = $hall['images'][0];
                    } elseif (!empty($hall['image']) && file_exists($hall['image'])) {
                        $img = $hall['image'];
                    }
                    $badge = !empty($hall['badge']) ? $hall['badge'] : 'Premium';
                    $type = $hall['category'] ?? 'wedding';
                    if ($type == 'conference') $type = 'conference';
                    elseif ($type == 'party') $type = 'party';
                    else $type = 'wedding';
                ?>
                <div class="hall-card" data-id="<?php echo $index; ?>" data-type="<?php echo $type; ?>" data-capacity="<?php echo $hall['capacity']; ?>">
                    <div class="hall-image">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($hall['name']); ?>">
                        <div class="hall-badge"><?php echo htmlspecialchars($badge); ?></div>
                        <div class="hall-price"><span>Starting LKR <?php echo number_format($hall['base_price']); ?></span></div>
                    </div>
                    <div class="hall-content">
                        <h3><?php echo htmlspecialchars($hall['name']); ?></h3>
                        <p class="hall-description"><?php echo htmlspecialchars(substr($hall['description'], 0, 80)) . (strlen($hall['description']) > 80 ? '...' : ''); ?></p>
                        <div class="hall-features">
                            <?php 
                            if (!empty($hall['amenities']) && is_array($hall['amenities'])): 
                                $feature_count = 0;
                                foreach($hall['amenities'] as $amenity): 
                                    if ($feature_count >= 3) break;
                                    $feature_count++;
                                ?>
                                    <span><i class="fas <?php echo getAmenityIcon($amenity); ?>"></i> <?php echo htmlspecialchars($amenity); ?></span>
                                <?php endforeach; 
                                if (count($hall['amenities']) > 3): ?>
                                    <span><i class="fas fa-plus-circle"></i> +<?php echo count($hall['amenities']) - 3; ?> more</span>
                                <?php endif;
                            else: ?>
                                <span><i class="fas fa-users"></i> Up to <?php echo $hall['capacity']; ?> Guests</span>
                                <span><i class="fas fa-music"></i> Sound System</span>
                                <span><i class="fas fa-video"></i> LED Screens</span>
                            <?php endif; ?>
                        </div>
                        <div class="hall-buttons">
                            <button type="button" class="btn-view" onclick="openHallModal('<?php echo $hall['id']; ?>')">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <?php if ($is_logged_in): ?>
                                <!-- ✅ Event Delegation: Plan Event Button -->
                                <button type="button" class="btn-plan" 
                                    data-hall-id="<?php echo $hall['id']; ?>" 
                                    data-hall-name="<?php echo addslashes($hall['name']); ?>" 
                                    data-capacity="<?php echo $hall['capacity']; ?>" 
                                    data-price="<?php echo $hall['base_price']; ?>" 
                                    data-img="<?php echo addslashes($img); ?>">
                                    Book Now →
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-plan" onclick="triggerLogin(event)">Plan Event →</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="why-choose">
    <div class="why-container">
        <div class="section-title">
            <h2>Why Choose Our Venues?</h2>
            <div class="divider"></div>
            <p>Experience excellence with our premium event services</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                <h4>Easy Booking</h4>
                <p>Simple online booking with instant confirmation</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                <h4>Premium Catering</h4>
                <p>Exquisite food options from top chefs</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-microphone"></i></div>
                <h4>AV Equipment</h4>
                <p>State-of-the-art sound & visual systems</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-parking"></i></div>
                <h4>Free Parking</h4>
                <p>Ample parking space for all guests</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-container">
        <h2>Plan Your Dream Event Today</h2>
        <p>Contact our event planning team for personalized packages and special offers</p>
        <a href="contact.php" class="cta-btn">Contact Event Manager →</a>
    </div>
</section>

<!-- Footer -->
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

<!-- ============================================================ -->
<!-- ✅ EVENT HALL DETAILS MODAL -->
<!-- ============================================================ -->
<div class="modal-overlay" id="hallModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeHallModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-body" id="hallModalBody"></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ✅ EVENT HALL BOOKING MODAL WITH CALENDAR -->
<!-- ============================================================ -->
<div class="modal-overlay" id="eventHallBookingModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closeEventHallModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-body">
            <div class="modal-image" id="ehModalImage">
                <i class="fas fa-building"></i>
            </div>
            <div class="modal-content-wrapper">
                <h2 class="modal-title" id="ehHallName">Hall Name</h2>
                <div class="modal-price" id="ehPriceDisplay">Starting LKR 0</div>
                <p id="ehCapacity" style="color:#666; margin-bottom:0.3rem; font-size:0.85rem;"><i class="fas fa-users"></i> Capacity: <span id="ehMaxGuestsLabel">0</span></p>

                <form id="eventHallForm">
                    <input type="hidden" name="hall_id" id="ehHallId">
                    <input type="hidden" name="event_date" id="ehEventDateHidden">
                    <input type="hidden" name="booking_type" value="event_hall">
                    <!-- ✅ Added hidden input to pass total price to backend/stripe -->
                    <input type="hidden" name="calculated_total_price" id="ehCalculatedTotal" value="0">

                    <!-- Calendar -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" onclick="ehChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 id="ehCalendarTitle">January 2024</h4>
                            <button type="button" class="calendar-nav" onclick="ehChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="calendar-weekdays">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div class="calendar-days" id="ehCalendarDays"></div>
                        <div class="calendar-legend">
                            <span><span class="legend-dot available"></span> Available</span>
                            <span><span class="legend-dot booked"></span> Booked</span>
                            <span><span class="legend-dot selected"></span> Selected</span>
                            <span><span class="legend-dot today"></span> Today</span>
                        </div>
                    </div>

                    <!-- Date Display -->
                    <div class="date-display">
                        <div class="date-display-item">
                            <label>Event Date</label>
                            <strong id="ehDisplayDate">—</strong>
                        </div>
                    </div>

                    <!-- ✅ Added data-price attributes to options -->
                    <div class="form-group-modal">
                        <label><i class="fas fa-gift"></i> Select Package</label>
                        <select name="package_id" id="ehPackageId" required>
                            <option value="" data-price="0">Choose a package</option>
                            <?php foreach($packages as $pkg): ?>
                                <option value="<?php echo $pkg['id']; ?>" data-price="<?php echo $pkg['price']; ?>">
                                    <?php echo htmlspecialchars($pkg['name']); ?> - LKR <?php echo number_format($pkg['price']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-modal">
                        <label><i class="fas fa-user-friends"></i> Guests (max <span id="ehMaxGuests">0</span>)</label>
                        <input type="number" name="guests" id="ehGuests" min="1" value="1" required>
                    </div>

                    <div class="form-group-modal">
                        <label><i class="fas fa-pencil-alt"></i> Special Requests</label>
                        <textarea name="special_requests" id="ehSpecial" rows="2" placeholder="e.g., vegetarian meals, decoration preferences..."></textarea>
                    </div>

                    <div id="ehErrorMsg" class="alert-error-modal"></div>
                    <div id="ehSuccessMsg" class="alert-success-modal"></div>

                    <div id="ehFormContainer">
                        <button type="submit" class="btn-confirm-booking">
                            <i class="fas fa-check-circle"></i> Submit Event Request
                        </button>
                    </div>
                    <div id="ehSuccessContainer" style="display:none;">
                        <div id="ehSuccessDetails" style="background:#fef5e6; padding:1rem; border-radius:12px; margin-top:0.5rem;"></div>
                        <a href="user/my-event-bookings.php" style="display:inline-block; margin-top:0.5rem; color:#c5a263; font-weight:600;">View My Events →</a>
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
    // ✅ EXISTING SCRIPTS (Navbar, Filter, Hall Details)
    // ================================================================
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

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

    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() { navLinks.classList.toggle('active'); });
        document.querySelectorAll('.nav-links a, .nav-links button').forEach(function(link) {
            link.addEventListener('click', function() { navLinks.classList.remove('active'); });
        });
    }

    // ================================================================
    // ✅ UPDATED FILTER & SORTING (Using CSS Display instead of HTML replacement)
    // ================================================================
    function filterAndSortHalls() {
        const typeFilter = document.getElementById('hallType') ? document.getElementById('hallType').value : 'all';
        const sortFilter = document.getElementById('capacitySort') ? document.getElementById('capacitySort').value : 'default';
        const hallsGrid = document.getElementById('hallsGrid');
        const noHallsMsg = document.getElementById('noHallsMessageEl');
        const countDisplay = document.getElementById('hallCountDisplay');
        
        if (!hallsGrid) return;
        
        let halls = Array.from(hallsGrid.querySelectorAll('.hall-card'));
        let visibleCount = 0;

        // Step 1: Filter using CSS Display to preserve events
        halls.forEach(hall => {
            let matchesType = (typeFilter === 'all' || hall.getAttribute('data-type') === typeFilter);
            if (matchesType) {
                hall.style.display = 'block';
                visibleCount++;
            } else {
                hall.style.display = 'none';
            }
        });

        // Toggle No Results Message
        if (noHallsMsg) {
            noHallsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
        if (countDisplay) {
            countDisplay.innerText = visibleCount;
        }

        // Step 2: Sort the DOM elements that are visible
        if (visibleCount > 0) {
            let visibleHalls = halls.filter(h => h.style.display === 'block');
            
            if (sortFilter === 'capacity-low') {
                visibleHalls.sort((a,b) => parseInt(a.getAttribute('data-capacity')) - parseInt(b.getAttribute('data-capacity')));
            } else if (sortFilter === 'capacity-high') {
                visibleHalls.sort((a,b) => parseInt(b.getAttribute('data-capacity')) - parseInt(a.getAttribute('data-capacity')));
            } else {
                // Default: Sort by original DOM index (data-id)
                visibleHalls.sort((a,b) => parseInt(a.getAttribute('data-id')) - parseInt(b.getAttribute('data-id')));
            }
            
            // Re-append to order them visually
            visibleHalls.forEach(hall => hallsGrid.appendChild(hall));
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const hallTypeSelect = document.getElementById('hallType');
        const capacitySortSelect = document.getElementById('capacitySort');
        if (hallTypeSelect) hallTypeSelect.addEventListener('change', filterAndSortHalls);
        if (capacitySortSelect) capacitySortSelect.addEventListener('change', filterAndSortHalls);
    });

    // ================================================================
    // ✅ EVENT DELEGATION FOR PLAN EVENT BUTTON
    // ================================================================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-plan[data-hall-id]');
        if (!btn) return;
        e.preventDefault();
        
        const hallId = btn.getAttribute('data-hall-id');
        const hallName = btn.getAttribute('data-hall-name');
        const capacity = parseInt(btn.getAttribute('data-capacity'));
        const price = parseFloat(btn.getAttribute('data-price'));
        const img = btn.getAttribute('data-img');
        
        openEventHallModal(hallId, hallName, capacity, price, img);
    });

    // Hall Details Modal
    const hallsData = <?php echo json_encode($halls); ?>;
    function openHallModal(hallId) {
        const modal = document.getElementById('hallModal');
        const modalBody = document.getElementById('hallModalBody');
        const hall = hallsData.find(h => h.id == hallId);
        if (!hall) return;
        let img = 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600';
        if (hall.images && hall.images.length > 0) img = hall.images[0];
        else if (hall.image) img = hall.image;
        let featuresHtml = '';
        if (hall.amenities && hall.amenities.length > 0) {
            hall.amenities.forEach(a => featuresHtml += `<span><i class="fas ${getAmenityIconJS(a)}"></i> ${escapeHtml(a)}</span>`);
        } else {
            featuresHtml = `<span><i class="fas fa-users"></i> Up to ${hall.capacity} Guests</span><span><i class="fas fa-music"></i> Sound System</span><span><i class="fas fa-video"></i> LED Screens</span><span><i class="fas fa-parking"></i> Free Parking</span>`;
        }
        const categoryName = escapeHtml(hall.category ? hall.category : 'Wedding').charAt(0).toUpperCase() + escapeHtml(hall.category ? hall.category : 'wedding').slice(1);
        let planActionHtml = '';
        if (<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
            planActionHtml = `<button type="button" class="btn-plan" onclick="closeHallModal(); openEventHallModal(${hall.id}, '${addslashes(hall.name)}', ${hall.capacity}, ${hall.base_price}, '${addslashes(img)}')"><i class="fas fa-calendar-check"></i> Plan Event</button>`;
        } else {
            planActionHtml = `<button type="button" class="btn-plan" onclick="triggerLogin(event)"><i class="fas fa-user-lock"></i> Login to Plan</button>`;
        }
        modalBody.innerHTML = `
            <div class="modal-image"><img src="${img}" style="width:100%;height:100%;object-fit:cover;"></div>
            <div class="modal-content-wrapper">
                <span class="modal-badge" style="background:#c5a263;color:white;padding:0.2rem 1rem;border-radius:20px;font-size:0.75rem;display:inline-block;">${escapeHtml(hall.badge || 'Premium')}</span>
                <h2 class="modal-title">${escapeHtml(hall.name)}</h2>
                <div class="modal-price">Starting LKR ${hall.base_price.toLocaleString()}</div>
                <p class="modal-description">${escapeHtml(hall.description || '')}</p>
                <div class="modal-features-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;margin-bottom:1rem;padding:1rem;background:#f9f5f0;border-radius:12px;">${featuresHtml}</div>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;padding:1rem 0;border-top:1px solid #f0e5d8;">
                    <div style="display:flex;align-items:center;gap:0.4rem;color:#666;font-size:0.85rem;"><i class="fas fa-users" style="color:#c5a263;"></i> ${hall.capacity} Capacity</div>
                    <div style="display:flex;align-items:center;gap:0.4rem;color:#666;font-size:0.85rem;"><i class="fas fa-building" style="color:#c5a263;"></i> ${categoryName} Hall</div>
                </div>
                <div style="display:flex;gap:1rem;margin-top:0.5rem;">${planActionHtml}</div>
            </div>
        `;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeHallModal() {
        document.getElementById('hallModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    document.getElementById('hallModal').addEventListener('click', function(e) { if (e.target === this) closeHallModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeHallModal(); });

    // ================================================================
    // ✅ EVENT HALL BOOKING MODAL WITH DYNAMIC PRICE & CALENDAR
    // ================================================================
    let ehStartDate = null;
    let ehBookedDates = [];
    let ehCurrentMonth = new Date().getMonth();
    let ehCurrentYear = new Date().getFullYear();
    let ehHallId = 0;
    let ehMaxCapacity = 0;
    
    // Variables for price calculation
    let ehBasePrice = 0;
    let ehCurrentPackagePrice = 0;

    function openEventHallModal(hallId, hallName, capacity, price, imgUrl) {
        ehHallId = hallId; 
        ehMaxCapacity = capacity; 
        ehStartDate = null; 
        ehBookedDates = [];
        
        // Reset and assign prices
        ehBasePrice = parseFloat(price) || 0;
        ehCurrentPackagePrice = 0;
        
        document.getElementById('ehHallId').value = hallId;
        document.getElementById('ehHallName').textContent = hallName;
        
        document.getElementById('ehMaxGuestsLabel').textContent = capacity;
        document.getElementById('ehMaxGuests').textContent = capacity;

        document.getElementById('ehGuests').max = capacity; 
        document.getElementById('ehGuests').value = 1;
        document.getElementById('ehPackageId').value = "";
        
        updateEhTotalPriceDisplay();

        if (imgUrl && imgUrl !== '') {
            document.getElementById('ehModalImage').innerHTML = `<img src="${imgUrl}" style="width:100%;height:100%;object-fit:cover;">`;
        } else {
            document.getElementById('ehModalImage').innerHTML = '<i class="fas fa-building"></i>';
        }
        
        document.getElementById('ehDisplayDate').textContent = '—';
        document.getElementById('ehEventDateHidden').value = '';
        document.getElementById('ehErrorMsg').style.display = 'none';
        document.getElementById('ehSuccessMsg').style.display = 'none';
        document.getElementById('ehFormContainer').style.display = 'block';
        document.getElementById('ehSuccessContainer').style.display = 'none';

        // Attempt to fetch booked dates if the script exists
        fetch(`get-booked-dates.php?type=hall&id=${hallId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) ehBookedDates = data.booked;
                ehCurrentMonth = new Date().getMonth(); 
                ehCurrentYear = new Date().getFullYear(); 
                ehRenderCalendar();
            })
            .catch(() => {
                ehCurrentMonth = new Date().getMonth(); 
                ehCurrentYear = new Date().getFullYear(); 
                ehRenderCalendar();
            });
            
        document.getElementById('eventHallBookingModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Function to calculate and update UI price display
    function updateEhTotalPriceDisplay() {
        const total = ehBasePrice + ehCurrentPackagePrice;
        const priceDisplay = document.getElementById('ehPriceDisplay');
        const hiddenTotalInput = document.getElementById('ehCalculatedTotal');
        
        if (ehCurrentPackagePrice > 0) {
            priceDisplay.innerHTML = `
                <span style="font-size:0.95rem; color:#888; font-weight:normal;">Hall Base: LKR ${ehBasePrice.toLocaleString()} + Package: LKR ${ehCurrentPackagePrice.toLocaleString()}</span><br>
                <span style="color:#c5a263; font-size:1.6rem; font-weight:800;">Total: LKR ${total.toLocaleString()}</span>
            `;
        } else {
            priceDisplay.innerHTML = `Starting LKR ${ehBasePrice.toLocaleString()}`;
        }
        
        if (hiddenTotalInput) {
            hiddenTotalInput.value = total;
        }
    }

    // Listen for package selection changes
    document.getElementById('ehPackageId').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        ehCurrentPackagePrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        updateEhTotalPriceDisplay();
    });

    function closeEventHallModal() {
        document.getElementById('eventHallBookingModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    document.getElementById('eventHallBookingModal').addEventListener('click', function(e) {
        if (e.target === this) closeEventHallModal();
    });

    function ehRenderCalendar() {
        const daysContainer = document.getElementById('ehCalendarDays');
        const title = document.getElementById('ehCalendarTitle');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        title.textContent = monthNames[ehCurrentMonth] + ' ' + ehCurrentYear;
        const firstDay = new Date(ehCurrentYear, ehCurrentMonth, 1).getDay();
        const daysInMonth = new Date(ehCurrentYear, ehCurrentMonth + 1, 0).getDate();
        const today = new Date(); const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        today.setHours(0,0,0,0);
        let html = '';
        for (let i = 0; i < firstDay; i++) html += '<div class="calendar-day" style="background:transparent;cursor:default;"></div>';
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = ehCurrentYear + '-' + String(ehCurrentMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            const currentDate = new Date(ehCurrentYear, ehCurrentMonth, d);
            let classes = 'calendar-day';
            if (currentDate < today) classes += ' past';
            else if (ehBookedDates.includes(dateStr)) classes += ' booked';
            else classes += ' available';
            if (dateStr === todayStr) classes += ' today';
            if (ehStartDate && dateStr === ehStartDate) classes += ' selected';
            html += `<div class="${classes}" data-date="${dateStr}" onclick="ehSelectDate('${dateStr}')">${d}</div>`;
        }
        daysContainer.innerHTML = html;
    }

    function ehChangeMonth(delta) { 
        ehCurrentMonth += delta; 
        if (ehCurrentMonth > 11) { ehCurrentMonth = 0; ehCurrentYear++; } 
        if (ehCurrentMonth < 0) { ehCurrentMonth = 11; ehCurrentYear--; } 
        ehRenderCalendar(); 
    }

    function ehSelectDate(dateStr) {
        const today = new Date(); today.setHours(0,0,0,0);
        const selectedDate = new Date(dateStr);
        if (selectedDate < today) { alert('Past dates cannot be selected.'); return; }
        if (ehBookedDates.includes(dateStr)) { alert('This date is already booked.'); return; }
        ehStartDate = dateStr;
        document.getElementById('ehDisplayDate').textContent = dateStr;
        document.getElementById('ehEventDateHidden').value = dateStr;
        ehRenderCalendar();
    }

    document.getElementById('eventHallForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const errorDiv = document.getElementById('ehErrorMsg');
        const successDiv = document.getElementById('ehSuccessMsg');
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        const eventDate = document.getElementById('ehEventDateHidden').value;
        if (!eventDate) {
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select an event date from the calendar.';
            errorDiv.style.display = 'block';
            return;
        }

        // Using your external stripe payment module
        if (typeof startBookingPayment === 'function') {
            startBookingPayment({
                form: this,
                bookingType: 'event_hall',
                finalizeEndpoint: 'event-booking-process.php',
                onValidationError: function(msg) {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg;
                    errorDiv.style.display = 'block';
                },
                onSuccess: function(data) {
                    successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    successDiv.style.display = 'block';
                    document.getElementById('ehFormContainer').style.display = 'none';
                    document.getElementById('ehSuccessContainer').style.display = 'block';
                    document.getElementById('ehSuccessDetails').innerHTML = data.details;
                    setTimeout(function() {
                        window.location.href = 'user/my-event-bookings.php';
                    }, 3000);
                }
            });
        } else {
             alert('Booking payment module is loading... Please try again. Total value: $' + document.getElementById('ehCalculatedTotal').value);
        }
    });

    // ================================================================
    // ✅ HELPERS & LOGIN
    // ================================================================
    function getAmenityIconJS(text) {
        text = text.toLowerCase();
        if (text.includes('guest') || text.includes('capacity')) return 'fa-users';
        if (text.includes('sound') || text.includes('music')) return 'fa-music';
        if (text.includes('led') || text.includes('screen')) return 'fa-video';
        if (text.includes('parking')) return 'fa-parking';
        if (text.includes('garden') || text.includes('view')) return 'fa-tree';
        if (text.includes('projector')) return 'fa-projector';
        if (text.includes('microphone')) return 'fa-microphone';
        if (text.includes('bar') || text.includes('cocktail')) return 'fa-cocktail';
        if (text.includes('stage') || text.includes('lighting')) return 'fa-lightbulb';
        if (text.includes('pool')) return 'fa-swimming-pool';
        if (text.includes('dj')) return 'fa-headphones';
        if (text.includes('whiteboard') || text.includes('board')) return 'fa-chalkboard';
        if (text.includes('print')) return 'fa-print';
        if (text.includes('butler') || text.includes('service')) return 'fa-concierge-bell';
        if (text.includes('wifi')) return 'fa-wifi';
        if (text.includes('coffee') || text.includes('refreshment')) return 'fa-coffee';
        return 'fa-check-circle';
    }

    function triggerLogin(e) {
        if (e) e.preventDefault();
        closeHallModal();
        closeEventHallModal();
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
        closeAuthModal.addEventListener('click', function() { authModal.classList.remove('active'); });
    }
    window.addEventListener('click', function(e) { if (e.target === authModal) authModal.classList.remove('active'); });
    if (tabs[0]) tabs[0].addEventListener('click', function() { switchAuthTab('login'); });
    if (tabs[1]) tabs[1].addEventListener('click', function() { switchAuthTab('register'); });
    if (switchToRegister) switchToRegister.addEventListener('click', function(e) { e.preventDefault(); switchAuthTab('register'); });
    if (switchToLogin) switchToLogin.addEventListener('click', function(e) { e.preventDefault(); switchAuthTab('login'); });

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
<script src="assets/stripe-payment.js"></script>
</body>
</html>
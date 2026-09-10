<?php
session_start();

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? '';

// ✅ UPDATED: Avatar logic - Check uploaded image first, then UI Avatars (initials)
$user_avatar_session = $_SESSION['user_avatar'] ?? '';
$user_avatar = '';
if (!empty($user_avatar_session) && file_exists($user_avatar_session)) {
    $user_avatar = $user_avatar_session;
} else {
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
}

require_once 'config/db_connection.php';

// FETCH ALL ACTIVE PACKAGES
$packages = [];
$result = $conn->query("SELECT * FROM packages WHERE status = 'active' ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $packages[] = $row;
    }
}

// FETCH ALL ACTIVE HALLS FOR DROPDOWN
$halls = [];
$halls_result = $conn->query("SELECT * FROM event_halls WHERE status = 'active' ORDER BY name");
if ($halls_result) {
    while ($row = $halls_result->fetch_assoc()) {
        $halls[] = $row;
    }
}

function parseInclusions($inclusions) {
    if (empty($inclusions)) return [];
    $items = explode("\n", trim($inclusions));
    $result = [];
    foreach ($items as $item) {
        $item = trim($item);
        if (!empty($item)) {
            if (strpos($item, '✓') === 0 || strpos($item, '✔') === 0) {
                $result[] = ['text' => trim(substr($item, 1)), 'included' => true];
            } elseif (strpos($item, '✗') === 0 || strpos($item, '✘') === 0) {
                $result[] = ['text' => trim(substr($item, 1)), 'included' => false];
            } else {
                $result[] = ['text' => $item, 'included' => true];
            }
        }
    }
    return $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages | Royal Estate - Wedding & Event Packages</title>
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
            position: fixed; top: 0; width: 100%; background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px); z-index: 1000; padding: 1rem 5%;
            transition: 0.3s; box-shadow: 0 2px 20px rgba(0,0,0,0.05);
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
            height: 45vh; margin-top: 70px;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=1600');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; align-items: center; justify-content: center; text-align: center; color: white;
        }
        .page-header h1 { font-size: 4rem; margin-bottom: 1rem; animation: fadeInUp 0.8s ease; }
        .page-header p { font-size: 1.2rem; animation: fadeInUp 0.8s ease 0.2s both; }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }

        .breadcrumb { background: #f5f0eb; padding: 1rem 5%; }
        .breadcrumb-container { max-width: 1400px; margin: 0 auto; }
        .breadcrumb a { color: #2c1810; text-decoration: none; }
        .breadcrumb a:hover { color: #c5a263; }
        .breadcrumb span { color: #c5a263; }

        .category-tabs { padding: 2rem 5% 0; background: white; }
        .tabs-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; border-bottom: 2px solid #f0e5d8; }
        .tab-btn { padding: 1rem 2rem; background: none; border: none; font-size: 1rem; font-weight: 600; color: #666; cursor: pointer; transition: 0.3s; font-family: 'Poppins', sans-serif; position: relative; }
        .tab-btn:hover { color: #c5a263; }
        .tab-btn.active { color: #c5a263; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, #c5a263, #8b691f); }

        .packages-section { padding: 4rem 5%; }
        .packages-container { max-width: 1400px; margin: 0 auto; }
        .packages-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }

        .package-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.4s; position: relative; cursor: pointer; }
        .package-card:hover { transform: translateY(-10px); box-shadow: 0 25px 50px rgba(0,0,0,0.15); }
        .package-popular { position: absolute; top: 20px; right: -30px; background: linear-gradient(135deg, #c5a263, #8b691f); color: white; padding: 0.5rem 2rem; transform: rotate(45deg); font-size: 0.8rem; font-weight: 600; width: 120px; text-align: center; z-index: 10; }
        .package-image { height: 220px; overflow: hidden; }
        .package-image img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .package-card:hover .package-image img { transform: scale(1.1); }
        .package-content { padding: 1.8rem; }
        .package-title { font-size: 1.6rem; color: #2c1810; margin-bottom: 0.5rem; }
        .package-price { font-size: 1.8rem; font-weight: 700; color: #c5a263; margin-bottom: 0.5rem; }
        .package-price small { font-size: 0.9rem; color: #666; font-weight: normal; }
        .package-description { color: #666; line-height: 1.6; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .package-features { list-style: none; margin-bottom: 1.5rem; }
        .package-features li { padding: 0.5rem 0; color: #555; display: flex; align-items: center; gap: 0.8rem; font-size: 0.9rem; border-bottom: 1px solid #f9f5f0; }
        .package-features li:last-child { border-bottom: none; }
        .package-features li i { width: 20px; font-size: 1rem; flex-shrink: 0; }
        .package-features li .fa-check-circle { color: #28a745; }
        .package-features li .fa-times-circle { color: #dc3545; }

        .btn-select {
            display: block; text-align: center; background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white; padding: 0.8rem; border-radius: 50px; text-decoration: none;
            font-weight: 600; transition: 0.3s; font-size: 1rem; width: 100%;
            border: none; cursor: pointer; font-family: 'Poppins', sans-serif;
        }
        .btn-select:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197,162,99,0.3); }

        .addons-section { background: #f9f5f0; padding: 4rem 5%; }
        .addons-container { max-width: 1400px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 3rem; }
        .section-title h2 { font-size: 2.5rem; color: #2c1810; margin-bottom: 0.5rem; }
        .section-title .divider { width: 80px; height: 3px; background: linear-gradient(90deg, #c5a263, #8b691f); margin: 1rem auto; }
        .section-title p { color: #666; }
        .addons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .addon-card { background: white; padding: 2rem; border-radius: 20px; text-align: center; transition: 0.3s; cursor: pointer; }
        .addon-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .addon-icon { width: 80px; height: 80px; background: linear-gradient(135deg,#c5a26320,#8b691f20); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
        .addon-icon i { font-size: 2.5rem; color: #c5a263; }
        .addon-card h4 { font-size: 1.3rem; color: #2c1810; margin-bottom: 0.5rem; }
        .addon-price { font-size: 1.2rem; color: #c5a263; font-weight: 600; margin-bottom: 0.5rem; }
        .addon-card p { color: #666; font-size: 0.9rem; }

        .custom-section { padding: 4rem 5%; background: linear-gradient(135deg, #2c1810, #1a0f0a); color: white; }
        .custom-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem; }
        .custom-content { flex: 1; }
        .custom-content h2 { font-size: 2rem; margin-bottom: 1rem; }
        .custom-content p { opacity: 0.9; margin-bottom: 1.5rem; }
        .custom-btn { background: #c5a263; color: #2c1810; padding: 0.8rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 600; transition: 0.3s; display: inline-block; }
        .custom-btn:hover { background: white; transform: translateX(5px); }
        .custom-icon { flex: 0.5; text-align: center; }
        .custom-icon i { font-size: 6rem; color: #c5a263; }

        footer { background: #1a0f0a; color: #999; padding: 3rem 5% 1rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap: 2rem; max-width: 1400px; margin: 0 auto; }
        .footer-col h4 { color: white; margin-bottom: 1rem; font-size: 1.2rem; }
        .footer-col a { display: block; color: #999; text-decoration: none; margin-bottom: 0.5rem; transition: 0.3s; }
        .footer-col a:hover { color: #c5a263; transform: translateX(5px); }
        .social-links { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-links a { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .social-links a:hover { background: #c5a263; transform: translateY(-3px); }
        .copyright { text-align: center; padding-top: 2rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }

        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .nav-links { position: fixed; top: 70px; left: -100%; width: 100%; background: white; flex-direction: column; padding: 2rem; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
            .nav-links.active { left: 0; }
            .page-header h1 { font-size: 2.5rem; }
            .packages-grid { grid-template-columns: 1fr; }
            .custom-container { text-align: center; flex-direction: column; }
        }

        /* ============================================================ */
        /* ✅ BOOKING MODAL - MATCHING EVENT HALLS STYLE */
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
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php" class="active">Packages</a>
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
        <h1>Event Packages</h1>
        <p>Discover our curated packages for every special occasion</p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="breadcrumb-container">
        <a href="index.php">Home</a> / <span>Packages</span>
    </div>
</div>

<!-- Category Tabs -->
<div class="category-tabs">
    <div class="tabs-container">
        <button class="tab-btn active" data-category="wedding">💍 Wedding Packages</button>
        <button class="tab-btn" data-category="party">🎉 Party Packages</button>
        <button class="tab-btn" data-category="corporate">💼 Corporate Packages</button>
    </div>
</div>

<!-- Packages Grid -->
<section class="packages-section">
    <div class="packages-container">
        <div class="packages-grid" id="packagesGrid">
            <?php if (empty($packages)): ?>
                <div style="grid-column: 1/-1; text-align:center; padding:40px; background:white; border-radius:20px;">
                    <i class="fas fa-gift" style="font-size:3rem; color:#c5a263;"></i>
                    <h3>No Packages Available</h3>
                    <p>Please check back later for our curated packages.</p>
                </div>
            <?php else: ?>
                <?php foreach($packages as $pkg): 
                    $img = 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=500';
                    if (!empty($pkg['images']) && is_array($pkg['images']) && file_exists($pkg['images'][0])) {
                        $img = $pkg['images'][0];
                    } elseif (!empty($pkg['image']) && file_exists($pkg['image'])) {
                        $img = $pkg['image'];
                    }
                    $inclusions = parseInclusions($pkg['inclusions'] ?? '');
                    $is_popular = in_array($pkg['name'], ['Silver Wedding Package', 'Gold Wedding Package']);
                ?>
                <div class="package-card" data-category="<?php echo $pkg['category']; ?>">
                    <?php if ($is_popular): ?>
                        <div class="package-popular">Most Popular</div>
                    <?php endif; ?>
                    <div class="package-image">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($pkg['name']); ?>">
                    </div>
                    <div class="package-content">
                        <h3 class="package-title"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                        <div class="package-price">LKR <?php echo number_format($pkg['price']); ?> <small>/ event</small></div>
                        <?php if (!empty($pkg['description'])): ?>
                            <p class="package-description"><?php echo htmlspecialchars($pkg['description']); ?></p>
                        <?php else: ?>
                            <p class="package-description"><?php echo htmlspecialchars(substr($pkg['inclusions'] ?? '', 0, 120)) . (strlen($pkg['inclusions'] ?? '') > 120 ? '...' : ''); ?></p>
                        <?php endif; ?>
                        <ul class="package-features">
                            <?php if (!empty($inclusions)): ?>
                                <?php foreach($inclusions as $item): ?>
                                    <li>
                                        <i class="fas <?php echo $item['included'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo htmlspecialchars($item['text']); ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><i class="fas fa-check-circle"></i> Custom package available</li>
                            <?php endif; ?>
                        </ul>
                        <?php if ($is_logged_in): ?>
                            <button type="button" class="btn-select" onclick="openPackageBookingModal(<?php echo $pkg['id']; ?>, '<?php echo addslashes($pkg['name']); ?>', <?php echo $pkg['price']; ?>, '<?php echo addslashes($img); ?>')">
                                Book Now →
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-select" onclick="triggerLogin(event)">Book Now →</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Add-ons Section -->
<section class="addons-section">
    <div class="addons-container">
        <div class="section-title">
            <h2>Customize Your Event</h2>
            <div class="divider"></div>
            <p>Add these special services to make your event unforgettable</p>
        </div>
        <div class="addons-grid">
            <div class="addon-card"><div class="addon-icon"><i class="fas fa-camera"></i></div><h4>Professional Photography</h4><div class="addon-price">$300 - $800</div><p>Professional photographers to capture every moment</p></div>
            <div class="addon-card"><div class="addon-icon"><i class="fas fa-video"></i></div><h4>Videography</h4><div class="addon-price">$400 - $1,000</div><p>Cinematic wedding/event videos</p></div>
            <div class="addon-card"><div class="addon-icon"><i class="fas fa-music"></i></div><h4>DJ Entertainment</h4><div class="addon-price">$250 - $600</div><p>Professional DJ with sound system</p></div>
            <div class="addon-card"><div class="addon-icon"><i class="fas fa-microphone-alt"></i></div><h4>Live Band</h4><div class="addon-price">$500 - $1,200</div><p>Live music performances</p></div>
            <div class="addon-card"><div class="addon-icon"><i class="fas fa-palette"></i></div><h4>Floral Decorations</h4><div class="addon-price">$200 - $800</div><p>Custom floral arrangements</p></div>
            <div class="addon-card"><div class="addon-icon"><i class="fas fa-champagne-glasses"></i></div><h4>Premium Bar</h4><div class="addon-price">$15 / person</div><p>Open bar with premium beverages</p></div>
        </div>
    </div>
</section>

<!-- Custom Package Section -->
<section class="custom-section">
    <div class="custom-container">
        <div class="custom-content">
            <h2>Need a Custom Package?</h2>
            <p>Can't find what you're looking for? Our event planning team will create a personalized package just for you. Tell us your requirements and we'll make it happen.</p>
            <a href="contact.php" class="custom-btn">Request Custom Package →</a>
        </div>
        <div class="custom-icon"><i class="fas fa-gem"></i></div>
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
<!-- ✅ PACKAGE BOOKING MODAL - WITH PRICE CALCULATION -->
<!-- ============================================================ -->
<div class="modal-overlay" id="packageBookingModal">
    <div class="modal-container">
        <button type="button" class="modal-close" onclick="closePackageBookingModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-body">
            <div class="modal-image" id="pkModalImage">
                <i class="fas fa-gift"></i>
            </div>
            <div class="modal-content-wrapper">
                <h2 class="modal-title" id="pkPackageName">Package Name</h2>
                <div class="modal-price" id="pkPriceDisplay">$0 <small>/ event</small></div>
                <div id="pkInclusionsList" class="modal-features-list"></div>

                <form id="packageBookingForm">
                    <input type="hidden" name="package_id" id="pkPackageId">
                    <input type="hidden" name="event_date" id="pkEventDateHidden">
                    <input type="hidden" name="booking_type" value="package">
                    <input type="hidden" name="calculated_total_price" id="pkCalculatedTotal" value="0">

                    <!-- ✅ Hall Select with data-price attribute -->
                    <div class="form-group-modal">
                        <label><i class="fas fa-building"></i> Select Event Hall</label>
                        <select name="hall_id" id="pkHallSelect" required>
                            <option value="">Choose a hall</option>
                            <?php foreach($halls as $hall): ?>
                                <option value="<?php echo $hall['id']; ?>" data-capacity="<?php echo $hall['capacity']; ?>" data-price="<?php echo $hall['base_price']; ?>">
                                    <?php echo htmlspecialchars($hall['name']); ?> (Capacity: <?php echo $hall['capacity']; ?>) - LKR <?php echo number_format($hall['base_price']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" onclick="pkChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 id="pkCalendarTitle">January 2024</h4>
                            <button type="button" class="calendar-nav" onclick="pkChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="calendar-weekdays">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div class="calendar-days" id="pkCalendarDays"></div>
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
                            <strong id="pkDisplayDate">—</strong>
                        </div>
                        <div class="date-display-item" id="pkCapacityDisplay">
                            <label>Hall Capacity</label>
                            <strong>—</strong>
                        </div>
                    </div>

                    <div class="form-group-modal">
                        <label><i class="fas fa-user-friends"></i> Guests</label>
                        <input type="number" name="guests" id="pkGuests" min="1" value="1" required>
                    </div>

                    <div class="form-group-modal">
                        <label><i class="fas fa-pencil-alt"></i> Special Requests</label>
                        <textarea name="special_requests" id="pkSpecial" rows="2" placeholder="e.g., vegetarian meals, decoration preferences..."></textarea>
                    </div>

                    <div id="pkErrorMsg" class="alert-error-modal"></div>
                    <div id="pkSuccessMsg" class="alert-success-modal"></div>

                    <div id="pkFormContainer">
                        <button type="submit" class="btn-confirm-booking">
                            <i class="fas fa-check-circle"></i> Submit Package Request
                        </button>
                    </div>
                    <div id="pkSuccessContainer" style="display:none;">
                        <div id="pkSuccessDetails" style="background:#fef5e6; padding:1rem; border-radius:12px; margin-top:0.5rem;"></div>
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
    // ✅ EXISTING SCRIPTS (Navbar, Tabs)
    // ================================================================
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 50);
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

    // Category Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const packageCards = document.querySelectorAll('.package-card');
    function filterPackages(category) {
        packageCards.forEach(function(card) {
            if (category === 'all') { card.style.display = 'block'; }
            else if (card.getAttribute('data-category') === category) { card.style.display = 'block'; }
            else { card.style.display = 'none'; }
        });
    }
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabBtns.forEach(function(tab) { tab.classList.remove('active'); });
            this.classList.add('active');
            filterPackages(this.getAttribute('data-category'));
        });
    });
    filterPackages('wedding');

    // ================================================================
    // ✅ PACKAGE BOOKING MODAL - WITH PRICE CALCULATION
    // ================================================================
    let pkStartDate = null;
    let pkBookedDates = [];
    let pkCurrentMonth = new Date().getMonth();
    let pkCurrentYear = new Date().getFullYear();
    let pkHallCapacity = 0;
    let pkPackagePrice = 0;
    let pkHallPrice = 0;

    function openPackageBookingModal(packageId, packageName, price, imgUrl) {
        pkStartDate = null;
        pkBookedDates = [];
        pkHallCapacity = 0;
        pkPackagePrice = price;
        pkHallPrice = 0;

        document.getElementById('pkPackageId').value = packageId;
        document.getElementById('pkPackageName').textContent = packageName;
        document.getElementById('pkPriceDisplay').innerHTML = 'LKR ' + Number(price).toLocaleString() + ' <small>/ event</small>';

        if (imgUrl && imgUrl !== '') {
            document.getElementById('pkModalImage').innerHTML = `<img src="${imgUrl}" style="width:100%;height:100%;object-fit:cover;">`;
        } else {
            document.getElementById('pkModalImage').innerHTML = '<i class="fas fa-gift"></i>';
        }

        document.getElementById('pkDisplayDate').textContent = '—';
        document.getElementById('pkEventDateHidden').value = '';
        document.getElementById('pkGuests').value = 1;
        document.getElementById('pkHallSelect').value = '';
        document.getElementById('pkCapacityDisplay').innerHTML = '<label>Hall Capacity</label><strong>—</strong>';
        document.getElementById('pkErrorMsg').style.display = 'none';
        document.getElementById('pkSuccessMsg').style.display = 'none';
        document.getElementById('pkFormContainer').style.display = 'block';
        document.getElementById('pkSuccessContainer').style.display = 'none';
        document.getElementById('pkInclusionsList').innerHTML = '<li><i class="fas fa-check-circle"></i> Package includes premium services</li><li><i class="fas fa-check-circle"></i> Customizable to your needs</li>';

        // ✅ Reset price display
        pkUpdateTotalPrice();

        document.getElementById('pkHallSelect').onchange = function() {
            const selected = this.options[this.selectedIndex];
            const capacity = selected.getAttribute('data-capacity');
            const price = parseFloat(selected.getAttribute('data-price')) || 0;
            pkHallPrice = price;
            
            if (capacity) {
                pkHallCapacity = parseInt(capacity);
                document.getElementById('pkGuests').max = capacity;
                document.getElementById('pkCapacityDisplay').innerHTML = '<label>Hall Capacity</label><strong>' + capacity + ' guests</strong>';
                pkFetchBookedDates(selected.value);
            } else {
                document.getElementById('pkGuests').max = 999;
                document.getElementById('pkCapacityDisplay').innerHTML = '<label>Hall Capacity</label><strong>—</strong>';
                pkBookedDates = [];
                pkRenderCalendar();
            }
            pkUpdateTotalPrice();
        };

        window.pkFetchBookedDates = function(hallId) {
            if (!hallId) return;
            fetch(`get-booked-dates.php?type=hall&id=${hallId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) { pkBookedDates = data.booked; } else { pkBookedDates = []; }
                    pkCurrentMonth = new Date().getMonth();
                    pkCurrentYear = new Date().getFullYear();
                    pkRenderCalendar();
                });
        };

        pkCurrentMonth = new Date().getMonth();
        pkCurrentYear = new Date().getFullYear();
        pkRenderCalendar();

        document.getElementById('packageBookingModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closePackageBookingModal() {
        document.getElementById('packageBookingModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    document.getElementById('packageBookingModal').addEventListener('click', function(e) {
        if (e.target === this) closePackageBookingModal();
    });

    function pkRenderCalendar() {
        const daysContainer = document.getElementById('pkCalendarDays');
        const title = document.getElementById('pkCalendarTitle');
        const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        title.textContent = monthNames[pkCurrentMonth] + ' ' + pkCurrentYear;
        const firstDay = new Date(pkCurrentYear, pkCurrentMonth, 1).getDay();
        const daysInMonth = new Date(pkCurrentYear, pkCurrentMonth + 1, 0).getDate();
        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        today.setHours(0,0,0,0);

        let html = '';
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day" style="background:transparent;cursor:default;"></div>';
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = pkCurrentYear + '-' + String(pkCurrentMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            const currentDate = new Date(pkCurrentYear, pkCurrentMonth, d);
            let classes = 'calendar-day';
            if (currentDate < today) classes += ' past';
            else if (pkBookedDates.includes(dateStr)) classes += ' booked';
            else classes += ' available';
            if (dateStr === todayStr) classes += ' today';
            if (pkStartDate && dateStr === pkStartDate) classes += ' selected';
            html += `<div class="${classes}" data-date="${dateStr}" onclick="pkSelectDate('${dateStr}')">${d}</div>`;
        }
        daysContainer.innerHTML = html;
    }

    function pkChangeMonth(delta) {
        pkCurrentMonth += delta;
        if (pkCurrentMonth > 11) { pkCurrentMonth = 0; pkCurrentYear++; }
        if (pkCurrentMonth < 0) { pkCurrentMonth = 11; pkCurrentYear--; }
        pkRenderCalendar();
    }

    function pkSelectDate(dateStr) {
        const today = new Date();
        today.setHours(0,0,0,0);
        const selectedDate = new Date(dateStr);
        if (selectedDate < today) { alert('Past dates cannot be selected.'); return; }
        if (pkBookedDates.includes(dateStr)) { alert('This date is already booked.'); return; }
        pkStartDate = dateStr;
        document.getElementById('pkDisplayDate').textContent = dateStr;
        document.getElementById('pkEventDateHidden').value = dateStr;
        pkRenderCalendar();
        pkUpdateTotalPrice();
    }

    // ================================================================
    // ✅ UPDATE TOTAL PRICE - Hall Price + Package Price
    // ================================================================
    function pkUpdateTotalPrice() {
        const priceDisplay = document.getElementById('pkPriceDisplay');
        const totalPrice = pkPackagePrice + pkHallPrice;
        const hiddenTotal = document.getElementById('pkCalculatedTotal');
        
        if (pkHallPrice > 0 && pkStartDate) {
            priceDisplay.innerHTML = `
                <span style="color:#c5a263; font-size:1.4rem; font-weight:800; display:block;">Total: LKR ${totalPrice.toLocaleString()}</span>
                <span style="font-size:0.85rem; color:#888; display:block;">Package: LKR ${pkPackagePrice.toLocaleString()} + Hall: LKR ${pkHallPrice.toLocaleString()}</span>
            `;
        } else if (pkHallPrice > 0) {
            priceDisplay.innerHTML = `
                <span style="color:#c5a263; font-size:1.4rem; font-weight:800; display:block;">Total: LKR ${totalPrice.toLocaleString()}</span>
                <span style="font-size:0.85rem; color:#888; display:block;">Package: LKR ${pkPackagePrice.toLocaleString()} + Hall: LKR ${pkHallPrice.toLocaleString()}</span>
            `;
        } else {
            priceDisplay.innerHTML = 'LKR ' + Number(pkPackagePrice).toLocaleString() + ' <small>/ event</small>';
        }
        
        if (hiddenTotal) {
            hiddenTotal.value = totalPrice;
        }
    }

    // ================================================================
    // ✅ PACKAGE FORM SUBMIT - WITH AUTO REDIRECT
    // ================================================================
    document.getElementById('packageBookingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const errorDiv = document.getElementById('pkErrorMsg');
        const successDiv = document.getElementById('pkSuccessMsg');
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        const eventDate = document.getElementById('pkEventDateHidden').value;
        if (!eventDate) {
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select an event date from the calendar.';
            errorDiv.style.display = 'block';
            return;
        }

        const hallId = document.getElementById('pkHallSelect').value;
        if (!hallId) {
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select an event hall.';
            errorDiv.style.display = 'block';
            return;
        }

        const guests = parseInt(document.getElementById('pkGuests').value);
        if (pkHallCapacity > 0 && guests > pkHallCapacity) {
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Number of guests (${guests}) exceeds hall capacity (${pkHallCapacity}).`;
            errorDiv.style.display = 'block';
            return;
        }

        // ✅ Use startBookingPayment with booking_type = 'package'
        startBookingPayment({
            form: this,
            bookingType: 'package',
            finalizeEndpoint: 'event-booking-process.php',
            onValidationError: function(msg) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg;
                errorDiv.style.display = 'block';
            },
            onSuccess: function(data) {
                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                successDiv.style.display = 'block';
                document.getElementById('pkFormContainer').style.display = 'none';
                document.getElementById('pkSuccessContainer').style.display = 'block';
                document.getElementById('pkSuccessDetails').innerHTML = data.details;
                
                setTimeout(function() {
                    window.location.href = 'user/my-package-bookings.php';
                }, 3000);
            }
        });
    });

    // ================================================================
    // ✅ LOGIN MODAL
    // ================================================================
    function triggerLogin(e) {
        if (e) e.preventDefault();
        closePackageBookingModal();
        const authModal = document.getElementById('authModal');
        if (authModal) {
            authModal.classList.add('active');
            switchAuthTab('login');
        }
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
        if(tab === 'login') {
            loginPane.classList.add('active');
            registerPane.classList.remove('active');
            tabs[0].classList.add('active');
            tabs[1].classList.remove('active');
        } else {
            registerPane.classList.add('active');
            loginPane.classList.remove('active');
            tabs[1].classList.add('active');
            tabs[0].classList.remove('active');
        }
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
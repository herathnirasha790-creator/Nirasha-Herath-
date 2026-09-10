<?php
session_start();

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? '';

// ✅ Avatar Logic
$user_avatar_session = $_SESSION['user_avatar'] ?? '';
$user_avatar = '';
if (!empty($user_avatar_session) && file_exists($user_avatar_session)) {
    $user_avatar = $user_avatar_session;
} else {
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
}

require_once 'config/db_connection.php';

// ✅ Handle Review Submission (AUTO-APPROVED)
$review_message = '';
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$is_logged_in) {
        $review_error = 'Please login to submit a review.';
    } else {
        $rating = intval($_POST['rating'] ?? 5);
        $title = trim($_POST['title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'];
        $user_avatar = $_SESSION['user_avatar'] ?? '';

        if (empty($comment)) {
            $review_error = 'Please write your review.';
        } elseif ($rating < 1 || $rating > 5) {
            $review_error = 'Please select a valid rating.';
        } else {
            // ✅ AUTO-APPROVE (status = 'approved')
            $stmt = $conn->prepare("
                INSERT INTO reviews (user_id, user_name, user_avatar, rating, title, comment, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved')
            ");
            $stmt->bind_param("ississ", $user_id, $user_name, $user_avatar, $rating, $title, $comment);
            if ($stmt->execute()) {
                $review_message = '<div class="alert-success"><i class="fas fa-check-circle"></i> ✅ Thank you! Your review has been published instantly!</div>';
            } else {
                $review_error = 'Failed to submit review. Please try again.';
            }
        }
    }
}

// ✅ FETCH ALL APPROVED REVIEWS (DESC - අලුත්ම උඩින්)
$reviews = [];
$result = $conn->query("
    SELECT id, user_name, user_avatar, rating, title, comment, created_at 
    FROM reviews 
    WHERE status = 'approved' 
    ORDER BY created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['user_avatar'] = !empty($row['user_avatar']) && file_exists($row['user_avatar']) 
            ? $row['user_avatar'] 
            : 'https://ui-avatars.com/api/?name=' . urlencode($row['user_name']) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
        $reviews[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews | Royal Estate - Guest Reviews & Ratings</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== BASE STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #fffef8; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #c5a263, #8b691f); border-radius: 10px; }

        /* ========== NAVIGATION ========== */
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

        /* ========== PAGE HEADER ========== */
        .page-header {
            height: 35vh; margin-top: 70px;
            background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1600');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; align-items: center; justify-content: center; text-align: center; color: white;
        }
        .page-header h1 { font-size: 3.5rem; margin-bottom: 0.5rem; animation: fadeInUp 0.8s ease; }
        .page-header p { font-size: 1.2rem; animation: fadeInUp 0.8s ease 0.2s both; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* ========== BREADCRUMB ========== */
        .breadcrumb { background: #f5f0eb; padding: 1rem 5%; }
        .breadcrumb-container { max-width: 1400px; margin: 0 auto; }
        .breadcrumb a { color: #2c1810; text-decoration: none; }
        .breadcrumb a:hover { color: #c5a263; }
        .breadcrumb span { color: #c5a263; }

        /* ========== REVIEWS SECTION ========== */
        .reviews-section { padding: 4rem 5%; }
        .reviews-container { max-width: 1400px; margin: 0 auto; }

        /* ========== REVIEW FORM ========== */
        .review-form-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .review-form-container h2 { color: #2c1810; margin-bottom: 0.5rem; border-left: 4px solid #c5a263; padding-left: 1rem; }
        .review-form-container p { color: #666; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; color: #2c1810; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 10px;
            font-family: 'Poppins', sans-serif; transition: 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #c5a263; outline: none; box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }

        /* Rating Stars */
        .rating-group { display: flex; gap: 0.3rem; align-items: center; margin-bottom: 1rem; }
        .rating-group label { font-weight: 500; color: #2c1810; margin-right: 0.5rem; }
        .star-input { display: none; }
        .star-label { font-size: 1.8rem; cursor: pointer; color: #ddd; transition: 0.2s; }
        .star-label:hover, .star-label:hover ~ .star-label { color: #f5c518; }
        .star-input:checked ~ .star-label { color: #f5c518; }
        .star-group { display: flex; flex-direction: row-reverse; gap: 0.1rem; }

        .btn-submit-review {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white; border: none; padding: 12px 30px; border-radius: 50px;
            font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 1rem;
        }
        .btn-submit-review:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197,162,99,0.3); }

        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 10px; margin-bottom: 1rem; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 10px; margin-bottom: 1rem; border-left: 4px solid #dc3545; }

        /* ========== REVIEWS LIST ========== */
        .reviews-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
        .review-card {
            background: white; border-radius: 20px; padding: 1.8rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.3s;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
            overflow: hidden;
        }
        .review-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .review-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.8rem; }
        .review-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #c5a263; flex-shrink: 0; }
        .review-user { font-weight: 600; color: #2c1810; }
        .review-date { font-size: 0.75rem; color: #999; }
        .review-rating { color: #f5c518; font-size: 1rem; margin: 0.3rem 0; }
        .review-title { font-size: 1.1rem; color: #2c1810; font-weight: 600; margin-bottom: 0.3rem; }
        .review-comment { color: #555; line-height: 1.6; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; max-width: 100%; }
        .review-avatar-fallback {
            width: 50px; height: 50px; border-radius: 50%;
            background: #c5a263; color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 1.2rem;
            flex-shrink: 0;
        }
        .no-reviews { text-align: center; padding: 3rem; background: white; border-radius: 20px; }
        .no-reviews i { font-size: 3rem; color: #c5a263; margin-bottom: 1rem; display: block; }

        /* ========== FOOTER ========== */
        footer { background: #1a0f0a; color: #999; padding: 3rem 5% 1rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap: 2rem; max-width: 1400px; margin: 0 auto; }
        .footer-col h4 { color: white; margin-bottom: 1rem; font-size: 1.2rem; }
        .footer-col a { display: block; color: #999; text-decoration: none; margin-bottom: 0.5rem; transition: 0.3s; }
        .footer-col a:hover { color: #c5a263; transform: translateX(5px); }
        .social-links { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-links a { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .social-links a:hover { background: #c5a263; transform: translateY(-3px); }
        .copyright { text-align: center; padding-top: 2rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }

        /* ========== MODAL ========== */
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

        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .nav-links { position: fixed; top: 70px; left: -100%; width: 100%; background: white; flex-direction: column; padding: 2rem; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
            .nav-links.active { left: 0; }
            .page-header h1 { font-size: 2.5rem; }
            .reviews-grid { grid-template-columns: 1fr; }
            .review-form-container { padding: 1.2rem; }
        }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- ✅ NAVIGATION -->
<!-- ============================================================ -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo"><h1>ROYAL ESTATE</h1><p>LUXURY & ELEGANCE</p></div>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="event-halls.php">Event Halls</a>
            <a href="packages.php">Packages</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="reviews.php" class="active">Reviews</a>

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

<!-- ============================================================ -->
<!-- PAGE HEADER -->
<!-- ============================================================ -->
<section class="page-header">
    <div>
        <h1>Guest Reviews</h1>
        <p>What our guests say about their experience at Royal Estate</p>
    </div>
</section>

<!-- ============================================================ -->
<!-- BREADCRUMB -->
<!-- ============================================================ -->
<div class="breadcrumb">
    <div class="breadcrumb-container">
        <a href="index.php">Home</a> / <span>Reviews</span>
    </div>
</div>

<!-- ============================================================ -->
<!-- REVIEWS SECTION -->
<!-- ============================================================ -->
<section class="reviews-section">
    <div class="reviews-container">

        <!-- ✅ Review Form (Logged-in Users) -->
        <?php if ($is_logged_in): ?>
            <div class="review-form-container">
                <h2>✍️ Write a Review</h2>
                <p>Share your experience at Royal Estate with other guests. (Published instantly!)</p>

                <?php if ($review_message): echo $review_message; endif; ?>
                <?php if ($review_error): echo '<div class="alert-error">' . $review_error . '</div>'; endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="rating-group">
                            <div class="star-group">
                                <input type="radio" name="rating" value="5" id="star5" class="star-input" checked>
                                <label for="star5" class="star-label"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="4" id="star4" class="star-input">
                                <label for="star4" class="star-label"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="3" id="star3" class="star-input">
                                <label for="star3" class="star-label"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="2" id="star2" class="star-input">
                                <label for="star2" class="star-label"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="1" id="star1" class="star-input">
                                <label for="star1" class="star-label"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Review Title</label>
                        <input type="text" name="title" placeholder="e.g., Amazing Stay!" maxlength="200">
                    </div>

                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="comment" rows="4" placeholder="Share your experience..." required></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="btn-submit-review">
                        <i class="fas fa-paper-plane"></i> Submit Review (Auto-Published)
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-form-container" style="text-align:center; padding:2.5rem;">
                <i class="fas fa-user-lock" style="font-size:3rem; color:#c5a263; margin-bottom:1rem; display:block;"></i>
                <h3 style="color:#2c1810;">Login to Write a Review</h3>
                <p style="color:#666; margin-bottom:1.5rem;">Sign in to share your experience with our community.</p>
                <button class="btn-submit-review" onclick="openLoginModal()">
                    <i class="fas fa-sign-in-alt"></i> Login / Register
                </button>
            </div>
        <?php endif; ?>

        <!-- ✅ Reviews List -->
        <h2 style="color:#2c1810; margin-bottom:1.5rem; border-left:4px solid #c5a263; padding-left:1rem;">
            <i class="fas fa-star" style="color:#f5c518;"></i> What Our Guests Say
            <span style="font-size:0.9rem; color:#666; font-weight:400; display:block; margin-top:0.2rem;">
                <?php echo count($reviews); ?> reviews from our valued guests
            </span>
        </h2>

        <div class="reviews-grid">
            <?php if (empty($reviews)): ?>
                <div class="no-reviews" style="grid-column:1/-1;">
                    <i class="fas fa-comment-dots"></i>
                    <h3>No Reviews Yet</h3>
                    <p>Be the first to share your experience at Royal Estate!</p>
                </div>
            <?php else: ?>
                <?php foreach($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <?php if (!empty($review['user_avatar']) && file_exists($review['user_avatar'])): ?>
                            <img src="<?php echo $review['user_avatar']; ?>" class="review-avatar" alt="Avatar">
                        <?php else: ?>
                            <div class="review-avatar-fallback">
                                <?php echo strtoupper(substr($review['user_name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="review-user"><?php echo htmlspecialchars($review['user_name']); ?></div>
                            <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="review-rating">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <?php if($i <= $review['rating']): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span style="color:#666; font-size:0.8rem;">(<?php echo $review['rating']; ?>/5)</span>
                    </div>
                    <?php if(!empty($review['title'])): ?>
                        <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                    <?php endif; ?>
                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            <form style="display:flex; gap:0.5rem; margin-top:1rem;">
                <input type="email" placeholder="Your Email" style="padding:0.5rem; border-radius:5px; border:none; flex:1;">
                <button style="background:#c5a263; border:none; padding:0.5rem 1rem; border-radius:5px;"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; 2024 Royal Estate. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #c5a263;"></i> for luxury experiences</p>
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
    // ================================================================
    // ✅ NAVBAR SCROLL
    // ================================================================
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // ================================================================
    // ✅ MOBILE MENU
    // ================================================================
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

    // ================================================================
    // ✅ OPEN LOGIN MODAL
    // ================================================================
    function openLoginModal() {
        const modal = document.getElementById('authModal');
        if (modal) {
            modal.classList.add('active');
            switchTab('login');
        }
    }

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
        closeModal.addEventListener('click', function() { modal.classList.remove('active'); });
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
                location.href = 'reviews.php';
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
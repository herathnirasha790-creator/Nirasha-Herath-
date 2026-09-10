<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    header('Location: ../index.php');
    exit();
}
$admin_name = $_SESSION['user_name'];

$admin_avatar = '../assets/images/admin-avatar.jpg';
$default_avatar = 'https://randomuser.me/api/portraits/men/2.jpg';

require_once '../config/db_connection.php';

function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

$base_upload_dir = '../uploads/packages/';
if (!file_exists($base_upload_dir)) {
    mkdir($base_upload_dir, 0777, true);
}

$message = '';
$edit_package = null;

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.','..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? deleteDir("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
}

// ============================================================
// 1. ADD PACKAGE (CREATE)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'] ?? 'wedding';
    $inclusions = trim($_POST['inclusions'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $slug = createSlug($name);
    $package_folder = $base_upload_dir . $slug . '/';
    
    if (!file_exists($package_folder)) {
        mkdir($package_folder, 0777, true);
    }
    
    $uploaded_images = [];
    if (isset($_FILES['package_images']) && !empty($_FILES['package_images']['name'][0])) {
        $files = $_FILES['package_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target = $package_folder . $filename;
                if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                    $uploaded_images[] = 'uploads/packages/' . $slug . '/' . $filename;
                }
            }
        }
    }
    
    $images_json = json_encode($uploaded_images);
    $first_image = !empty($uploaded_images) ? $uploaded_images[0] : '';
    
    $stmt = $conn->prepare("INSERT INTO packages (name, price, category, description, inclusions, image, images, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sdsssss", $name, $price, $category, $description, $inclusions, $first_image, $images_json);
    
    if ($stmt->execute()) {
        $message = '<div class="alert-success">✅ Package added successfully with ' . count($uploaded_images) . ' images!</div>';
    } else {
        $message = '<div class="alert-danger">❌ Database error: ' . $conn->error . '</div>';
    }
}

// ============================================================
// 2. FETCH EDIT PACKAGE (READ)
// ============================================================
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_package = $result->fetch_assoc();
        $edit_package['images'] = json_decode($edit_package['images'] ?? '[]', true) ?: [];
        $edit_package['slug'] = createSlug($edit_package['name']);
    }
}

// ============================================================
// 3. UPDATE PACKAGE (UPDATE)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'] ?? 'wedding';
    $inclusions = trim($_POST['inclusions'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $old_slug = $_POST['old_slug'];
    $new_slug = createSlug($name);
    $old_folder = $base_upload_dir . $old_slug . '/';
    $new_folder = $base_upload_dir . $new_slug . '/';
    
    if ($old_slug !== $new_slug && file_exists($old_folder)) {
        rename($old_folder, $new_folder);
    }
    
    // Get existing images from DB
    $stmt = $conn->prepare("SELECT image, images FROM packages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $existing_images = json_decode($row['images'] ?? '[]', true) ?: [];
    $first_image = $row['image'] ?? '';
    
    // Handle new image uploads
    if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
        if (!file_exists($new_folder)) mkdir($new_folder, 0777, true);
        $files = $_FILES['new_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target = $new_folder . $filename;
                if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                    $existing_images[] = 'uploads/packages/' . $new_slug . '/' . $filename;
                }
            }
        }
        if (!empty($existing_images)) {
            $first_image = $existing_images[0];
        }
    }
    
    // Handle image deletions
    if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
        $to_delete = json_decode($_POST['delete_images'], true);
        if (is_array($to_delete)) {
            foreach ($to_delete as $img_path) {
                $full_path = '../' . $img_path;
                if (file_exists($full_path)) unlink($full_path);
                $key = array_search($img_path, $existing_images);
                if ($key !== false) unset($existing_images[$key]);
            }
            $existing_images = array_values($existing_images);
            $first_image = !empty($existing_images) ? $existing_images[0] : '';
        }
    }
    
    $images_json = json_encode($existing_images);
    
    $stmt = $conn->prepare("UPDATE packages SET name = ?, price = ?, category = ?, description = ?, inclusions = ?, image = ?, images = ? WHERE id = ?");
    $stmt->bind_param("sdsssssi", $name, $price, $category, $description, $inclusions, $first_image, $images_json, $id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert-success">✏️ Package updated successfully!</div>';
        $edit_package = null;
    } else {
        $message = '<div class="alert-danger">❌ Update failed: ' . $conn->error . '</div>';
    }
}

// ============================================================
// 4. DELETE PACKAGE (DELETE)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $slug = $_POST['slug'];
    $package_folder = $base_upload_dir . $slug . '/';
    if (file_exists($package_folder)) {
        deleteDir($package_folder);
    }
    $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="alert-success">🗑️ Package and all images deleted successfully!</div>';
    } else {
        $message = '<div class="alert-danger">❌ Failed to delete package.</div>';
    }
}

// ============================================================
// 5. FETCH ALL PACKAGES (LIST)
// ============================================================
$packages = [];
$result = $conn->query("SELECT * FROM packages WHERE status = 'active' ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $row['slug'] = createSlug($row['name']);
        $packages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Packages | Royal Estate Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c1810 0%, #1a0f0a 100%);
            color: white;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-header { padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #c5a263; margin-bottom: 1rem; object-fit: cover; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; color: #c5a263; font-size: 1.5rem; }
        .sidebar-nav { margin-top: 2rem; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-nav a i { width: 24px; }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(197,162,99,0.2);
            color: #c5a263;
            border-left-color: #c5a263;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .top-bar-right { display: flex; align-items: center; gap: 1rem; }
        .top-bar-right img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #c5a263; }
        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .card h3 {
            margin-bottom: 1.2rem;
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 1rem;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .form-row input, .form-row select {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        button, .btn-edit, .btn-delete {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 0.85rem;
        }
        button:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn-edit { background: #28a745; }
        .btn-delete { background: #dc3545; }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            align-items: center;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .data-table th {
            background: #f8f4ef;
            font-weight: 600;
            color: #2c1810;
        }
        .package-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc3545;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 700px;
            padding: 2rem;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        .image-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .image-item {
            position: relative;
            width: 80px;
        }
        .image-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .image-item .remove-img {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .help-text {
            font-size: 0.8rem;
            color: #888;
            margin-top: 4px;
        }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .data-table, .data-table thead, .data-table tbody, .data-table th, .data-table td, .data-table tr {
                display: block;
            }
            .data-table thead { display: none; }
            .data-table tr { border: 1px solid #eee; margin-bottom: 1rem; border-radius: 12px; padding: 8px; }
            .data-table td { display: flex; justify-content: space-between; align-items: center; padding: 8px; border: none; }
            .data-table td::before { content: attr(data-label); font-weight: 600; margin-right: 1rem; }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo htmlspecialchars($admin_avatar); ?>" alt="Admin Avatar" onerror="this.src='<?php echo $default_avatar; ?>'">
            <h2>ROYAL ESTATE</h2>
            <p style="font-size:0.7rem;">Admin Panel</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage-rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a>
            <a href="manage-halls.php"><i class="fas fa-building"></i> Event Halls</a>
            <a href="manage-packages.php" class="active"><i class="fas fa-gift"></i> Packages</a>
            <a href="room-bookings.php"><i class="fas fa-bed"></i> Room Bookings</a>
            <a href="event-bookings.php"><i class="fas fa-calendar-alt"></i> Event Bookings</a>
            <a href="package-bookings.php"><i class="fas fa-gift"></i> Package Bookings</a>
            <a href="manage-messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Packages</h2>
            <div class="top-bar-right">
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" alt="Profile" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php" style="color:#c5a263;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="card">
            <h3>➕ Add New Package</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Package Name" required>
                    <input type="number" step="0.01" name="price" placeholder="Price (LKR)" required>
                    <select name="category" required>
                        <option value="wedding">💍 Wedding</option>
                        <option value="party">🎉 Party</option>
                        <option value="corporate">💼 Corporate</option>
                    </select>
                </div>
                <textarea name="description" placeholder="Package Description (e.g., Perfect for intimate weddings...)" rows="2"></textarea>
                <textarea name="inclusions" placeholder="Inclusions (one per line)&#10;e.g.:&#10;Welcome Drink for all guests&#10;Premium Dessert Counter&#10;Food Plates (5 varieties)" rows="4"></textarea>
                <p class="help-text">📝 Enter each inclusion on a new line. They will appear with ✔ checkmarks on the frontend.</p>
                <div class="form-row">
                    <input type="file" name="package_images[]" accept="image/*" multiple>
                    <span style="color:#888;">Select multiple images (JPG, PNG) – optional</span>
                </div>
                <button type="submit" name="add">Add Package</button>
            </form>
        </div>

        <div class="card">
            <h3>📋 Existing Packages</h3>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($packages)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:20px;">No packages found. Add your first package above!</td></tr>
                        <?php else: ?>
                            <?php foreach($packages as $pkg): ?>
                            <tr>
                                <td data-label="Image">
                                    <?php 
                                    $img_path = !empty($pkg['images']) ? $pkg['images'][0] : (!empty($pkg['image']) ? $pkg['image'] : '');
                                    if (!empty($img_path) && file_exists('../' . $img_path)): ?>
                                        <img src="<?php echo $img_path; ?>" class="package-thumb" alt="Package">
                                    <?php else: ?>
                                        <div class="package-thumb" style="background:#eee; display:flex; align-items:center; justify-content:center;">📷</div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="ID"><?php echo $pkg['id']; ?></td>
                                <td data-label="Name"><?php echo htmlspecialchars($pkg['name']); ?></td>
                                <td data-label="Price">LKR <?php echo number_format($pkg['price']); ?></td>
                                <td data-label="Category"><span style="background:#c5a263; color:white; padding:2px 10px; border-radius:12px; font-size:0.7rem;"><?php echo ucfirst($pkg['category']); ?></span></td>
                                <td data-label="Images"><?php echo count($pkg['images']); ?> image(s)</td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <a href="?edit_id=<?php echo $pkg['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this package and all images?');">
                                            <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                            <input type="hidden" name="slug" value="<?php echo $pkg['slug']; ?>">
                                            <button type="submit" name="delete" class="btn-delete"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="modal <?php echo ($edit_package) ? 'active' : ''; ?>">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h3 style="margin-bottom: 1rem;">✏️ Edit Package</h3>
        <?php if ($edit_package): ?>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="id" value="<?php echo $edit_package['id']; ?>">
            <input type="hidden" name="old_slug" value="<?php echo $edit_package['slug']; ?>">
            <input type="hidden" name="existing_images" id="existingImages" value='<?php echo json_encode($edit_package['images']); ?>'>
            <div class="form-row">
                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_package['name']); ?>" required>
                <input type="number" step="0.01" name="price" value="<?php echo $edit_package['price']; ?>" required>
                <select name="category" required>
                    <option value="wedding" <?php echo ($edit_package['category'] ?? '') == 'wedding' ? 'selected' : ''; ?>>💍 Wedding</option>
                    <option value="party" <?php echo ($edit_package['category'] ?? '') == 'party' ? 'selected' : ''; ?>>🎉 Party</option>
                    <option value="corporate" <?php echo ($edit_package['category'] ?? '') == 'corporate' ? 'selected' : ''; ?>>💼 Corporate</option>
                </select>
            </div>
            <textarea name="description" rows="2"><?php echo htmlspecialchars($edit_package['description'] ?? ''); ?></textarea>
            <textarea name="inclusions" rows="4"><?php echo htmlspecialchars($edit_package['inclusions'] ?? ''); ?></textarea>
            
            <div class="form-group">
                <label>Current Images (click ✕ to remove)</label>
                <div id="existingImagesPreview" class="image-list">
                    <?php foreach($edit_package['images'] ?? [] as $img): ?>
                        <div class="image-item" data-img="<?php echo htmlspecialchars($img); ?>">
                            <img src="<?php echo $img; ?>" alt="Package Image">
                            <span class="remove-img" onclick="removeImage('<?php echo htmlspecialchars($img); ?>')">✕</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-row">
                <input type="file" name="new_images[]" accept="image/*" multiple>
                <span style="color:#888;">Add new images (optional)</span>
            </div>
            
            <button type="submit" name="update">Update Package</button>
            <a href="manage-packages.php" style="margin-left: 10px; color:#c5a263;">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
    let imagesToDelete = [];
    
    function removeImage(imgPath) {
        imagesToDelete.push(imgPath);
        const item = document.querySelector(`.image-item[data-img="${imgPath.replace(/"/g, '\\"')}"]`);
        if (item) item.remove();
    }
    
    function closeModal() {
        document.getElementById('editModal').classList.remove('active');
        window.location.href = 'manage-packages.php';
    }
    
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
        if (imagesToDelete.length > 0) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_images';
            input.value = JSON.stringify(imagesToDelete);
            this.appendChild(input);
        }
        const remainingImages = [];
        document.querySelectorAll('#existingImagesPreview .image-item').forEach(item => {
            remainingImages.push(item.getAttribute('data-img'));
        });
        document.getElementById('existingImages').value = JSON.stringify(remainingImages);
    });
    
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            modal.classList.remove('active');
            window.location.href = 'manage-packages.php';
        }
    }
</script>
</body>
</html>
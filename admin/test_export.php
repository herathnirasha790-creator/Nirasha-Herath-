<?php
// Simple Test Form
if (isset($_POST['test_submit'])) {
    echo "<h2 style='color:green;'>✅ Form Submitted Successfully!</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "<h3>Report Generated!</h3>";
    echo "<p>This is a test response.</p>";
    echo '<a href="test_export.php">Try Again</a>';
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Export</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f0eb; padding: 40px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; }
        button { background: #c5a263; color: white; border: none; padding: 12px 30px; border-radius: 30px; cursor: pointer; font-size: 16px; }
        button:hover { background: #8b691f; }
        .info { background: #fef5e6; padding: 15px; border-radius: 10px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Export Form</h1>
        <p>Click the button below to test form submission:</p>
        
        <form method="POST" action="">
            <div class="info">
                <p><strong>Report Type:</strong> Revenue Report</p>
                <p><strong>Format:</strong> PDF</p>
            </div>
            <button type="submit" name="test_submit">▶ Generate Test Report</button>
        </form>
        
        <p style="margin-top:20px; color:#666; font-size:14px;">
            <i class="fas fa-info-circle"></i> If you see "Form Submitted Successfully", the problem is with the main reports.php file.
        </p>
    </div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/db_connection.php';

// ✅ Get POST data
$report_type = $_POST['report_type'] ?? 'revenue';
$from = $_POST['from_date'] ?? '';
$to = $_POST['to_date'] ?? '';
$format = $_POST['file_format'] ?? 'pdf';

// ✅ Build WHERE clause - CORRECTED with proper aliases
function buildWhereClause($from, $to, $alias) {
    $where = "";
    if (!empty($from) && !empty($to)) {
        $where .= " AND DATE($alias.created_at) >= '$from' AND DATE($alias.created_at) <= '$to'";
    }
    return $where;
}

// ✅ Get Report Data - FIXED with correct aliases
function getReportData($conn, $report_type, $from, $to) {
    $data = [];
    
    if ($report_type === 'revenue') {
        // Revenue doesn't need date filter with alias
        $where = "";
        if (!empty($from) && !empty($to)) {
            $where .= " AND DATE(created_at) >= '$from' AND DATE(created_at) <= '$to'";
        }
        
        $room_rev = $conn->query("SELECT COALESCE(SUM(total_price), 0) as rev FROM room_bookings WHERE status IN ('confirmed','completed') $where")->fetch_assoc()['rev'] ?? 0;
        $event_rev = $conn->query("SELECT COALESCE(SUM(final_price), 0) as rev FROM event_bookings WHERE status IN ('approved','completed') $where")->fetch_assoc()['rev'] ?? 0;
        $total = $room_rev + $event_rev;
        
        $data = [
            'title' => 'Revenue Report',
            'headers' => ['Type', 'Amount'],
            'rows' => [
                ['Room Revenue', 'LKR ' . number_format($room_rev)],
                ['Event Revenue', 'LKR ' . number_format($event_rev)],
                ['Total Revenue', 'LKR ' . number_format($total)]
            ],
            'summary' => [
                ['Total Revenue', 'LKR ' . number_format($total)]
            ]
        ];
    } elseif ($report_type === 'room_bookings') {
        // ✅ Room Bookings - alias is 'rb'
        $where = buildWhereClause($from, $to, 'rb');
        $bookings = $conn->query("
            SELECT rb.id, u.name as customer, r.name as room, rb.check_in, rb.check_out, rb.total_price, rb.status, rb.payment_status 
            FROM room_bookings rb 
            JOIN users u ON rb.user_id = u.id 
            JOIN rooms r ON rb.room_id = r.id 
            WHERE 1=1 $where
            ORDER BY rb.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        $data = [
            'title' => 'Room Bookings Report',
            'headers' => ['ID', 'Customer', 'Room', 'Check In', 'Check Out', 'Total', 'Status'],
            'rows' => array_map(function($b) {
                $pay_status = ($b['payment_status'] ?? '') === 'paid' ? 'Paid' : ucfirst($b['payment_status'] ?? $b['status']);
                return [$b['id'], $b['customer'], $b['room'], $b['check_in'], $b['check_out'], 'LKR ' . number_format($b['total_price']), $pay_status];
            }, $bookings),
            'summary' => [
                ['Total Bookings', count($bookings)]
            ]
        ];
    } elseif ($report_type === 'event_bookings') {
        // ✅ Event Bookings - alias is 'eb'
        $where = buildWhereClause($from, $to, 'eb');
        $bookings = $conn->query("
            SELECT eb.id, u.name as customer, h.name as hall, eb.event_date, p.name as package, eb.guests, eb.estimated_price, eb.status, eb.payment_status 
            FROM event_bookings eb 
            JOIN users u ON eb.user_id = u.id 
            JOIN event_halls h ON eb.hall_id = h.id 
            JOIN packages p ON eb.package_id = p.id 
            WHERE 1=1 $where AND eb.booking_type = 'event_hall'
            ORDER BY eb.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        $data = [
            'title' => 'Event Bookings Report',
            'headers' => ['ID', 'Customer', 'Hall', 'Event Date', 'Package', 'Guests', 'Total', 'Status'],
            'rows' => array_map(function($b) {
                $price = $b['final_price'] ?? $b['estimated_price'];
                $pay_status = ($b['payment_status'] ?? '') === 'paid' ? 'Paid' : ucfirst($b['payment_status'] ?? $b['status']);
                return [$b['id'], $b['customer'], $b['hall'], $b['event_date'], $b['package'], $b['guests'], 'LKR ' . number_format($price), $pay_status];
            }, $bookings),
            'summary' => [
                ['Total Bookings', count($bookings)]
            ]
        ];
    } elseif ($report_type === 'package_bookings') {
        // ✅ Package Bookings - alias is 'eb'
        $where = buildWhereClause($from, $to, 'eb');
        $bookings = $conn->query("
            SELECT eb.id, u.name as customer, h.name as hall, eb.event_date, p.name as package, eb.guests, eb.estimated_price, eb.status, eb.payment_status 
            FROM event_bookings eb 
            JOIN users u ON eb.user_id = u.id 
            JOIN event_halls h ON eb.hall_id = h.id 
            JOIN packages p ON eb.package_id = p.id 
            WHERE 1=1 $where AND eb.booking_type = 'package'
            ORDER BY eb.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        $data = [
            'title' => 'Package Bookings Report',
            'headers' => ['ID', 'Customer', 'Hall', 'Event Date', 'Package', 'Guests', 'Total', 'Status'],
            'rows' => array_map(function($b) {
                $price = $b['final_price'] ?? $b['estimated_price'];
                $pay_status = ($b['payment_status'] ?? '') === 'paid' ? 'Paid' : ucfirst($b['payment_status'] ?? $b['status']);
                return [$b['id'], $b['customer'], $b['hall'], $b['event_date'], $b['package'], $b['guests'], 'LKR ' . number_format($price), $pay_status];
            }, $bookings),
            'summary' => [
                ['Total Bookings', count($bookings)]
            ]
        ];
    }
    
    return $data;
}

// ✅ Generate PDF
function generatePDFReport($data, $from, $to) {
    require_once '../vendor/autoload.php';
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);
    
    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; padding: 20px; }
            h1 { color: #2c1810; border-bottom: 3px solid #c5a263; padding-bottom: 10px; }
            .subtitle { color: #666; font-size: 14px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
            th { background: #f8f4ef; font-weight: 600; }
            .summary { margin-top: 30px; border-top: 2px solid #c5a263; padding-top: 15px; }
            .summary td { font-weight: bold; }
            .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; color: #999; font-size: 12px; text-align: center; }
        </style>
    </head>
    <body>
        <h1>📊 Royal Estate - ' . $data['title'] . '</h1>
        <div class="subtitle">Generated: ' . date('Y-m-d H:i:s') . 
        (!empty($from) ? ' | From: ' . $from : '') . (!empty($to) ? ' To: ' . $to : '') . '</div>
        
        <table>
            <thead><tr>';
    foreach ($data['headers'] as $header) {
        $html .= '<th>' . $header . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($data['rows'] as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . $cell . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    
    if (!empty($data['summary'])) {
        $html .= '<div class="summary"><table>';
        foreach ($data['summary'] as $item) {
            $html .= '<tr><td><strong>' . $item[0] . '</strong></td><td><strong>' . $item[1] . '</strong></td></tr>';
        }
        $html .= '</table></div>';
    }
    
    $html .= '
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Royal Estate. All rights reserved.</p>
        </div>
    </body>
    </html>';
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('Royal_Estate_' . $data['title'] . '_' . date('Y-m-d') . '.pdf');
    exit();
}

// ✅ Generate Excel
function generateExcelReport($data, $from, $to) {
    require_once '../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'Royal Estate - ' . $data['title']);
    $sheet->mergeCells('A1:' . chr(65 + count($data['headers']) - 1) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    
    $sheet->setCellValue('A2', 'Generated: ' . date('Y-m-d H:i:s') . (!empty($from) ? ' | From: ' . $from : '') . (!empty($to) ? ' To: ' . $to : ''));
    $sheet->mergeCells('A2:' . chr(65 + count($data['headers']) - 1) . '2');
    
    $row = 4;
    $col = 'A';
    foreach ($data['headers'] as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f8f4ef');
        $col++;
    }
    $row++;
    
    foreach ($data['rows'] as $rowData) {
        $col = 'A';
        foreach ($rowData as $cell) {
            $sheet->setCellValue($col . $row, $cell);
            $col++;
        }
        $row++;
    }
    
    if (!empty($data['summary'])) {
        $row += 2;
        foreach ($data['summary'] as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $row++;
        }
    }
    
    foreach (range('A', chr(65 + count($data['headers']) - 1)) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'cccccc'],
            ],
        ],
    ];
    $lastRow = $row - 1;
    $lastCol = chr(65 + count($data['headers']) - 1);
    $sheet->getStyle('A4:' . $lastCol . $lastRow)->applyFromArray($styleArray);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Royal_Estate_' . $data['title'] . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// ✅ Generate Report
$data = getReportData($conn, $report_type, $from, $to);

if ($format === 'pdf') {
    generatePDFReport($data, $from, $to);
} else {
    generateExcelReport($data, $from, $to);
}
?>
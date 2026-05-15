<?php
session_start();
require_once 'config/db.php';

// Language Selection Logic
$current_lang = $_SESSION['lang'] ?? 'la';
$lang_file = "lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include "lang/la.php";
}

// Check if accessing directly or via Walk-in
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$nights = isset($_GET['nights']) ? (int)$_GET['nights'] : 1;

if ($room_id > 0) {
    // Check if room is still available
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'Available'");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        $_SESSION['error'] = $lang['room_not_available_msg'] ?? "ຂໍອະໄພ! ຫ້ອງນີ້ບໍ່ຫວ່າງແລ້ວ ຫຼື ຖືກຈອງໄປແລ້ວ.";
        header("Location: walkin.php");
        exit();
    }
} else {
    // If accessed directly from menu, just show list of available rooms to select for check-in
    header("Location: walkin.php");
    exit();
}

$total_price = $room['price'] * $nights;
$check_in_date = date('Y-m-d');
$check_out_date = date('Y-m-d', strtotime("+$nights days"));

// Fetch Tax Percent
$stmtTax = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percent'");
$tax_percent = (float)($stmtTax->fetchColumn() ?: 0);
$tax_amount = round($total_price * ($tax_percent / 100));
$grand_total = $total_price + $tax_amount;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkin'])) {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $passport_number = trim($_POST['passport_number']);
    $address = trim($_POST['address']);
    $guest_count = (int)$_POST['guest_count'];
    $deposit_amount = (float)str_replace(',', '', $_POST['deposit_amount']);
    
    $payment_method = $_POST['payment_method'];
    
    // Generate Bill Number: YYYYNNNN (e.g. 20260001)
    $year = date('Y');
    $stmtLast = $pdo->prepare("SELECT bill_number FROM bookings WHERE bill_number LIKE ? AND bill_number REGEXP '^[0-9]+$' ORDER BY bill_number DESC LIMIT 1");
    $stmtLast->execute([$year . '%']);
    $lastBill = $stmtLast->fetchColumn();

    if ($lastBill) {
        $lastNum = (int)substr($lastBill, 4);
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }
    $bill_number = $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

    // Save to bookings table
    $stmt = $pdo->prepare("INSERT INTO bookings (room_id, customer_name, customer_phone, passport_number, address, guest_count, check_in_date, check_out_date, total_price, deposit_amount, payment_method, status, bill_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Occupied', ?)");
    
    if ($stmt->execute([$room_id, $customer_name, $customer_phone, $passport_number, $address, $guest_count, $check_in_date, $check_out_date, $total_price, $deposit_amount, $payment_method, $bill_number])) {
        $booking_id = $pdo->lastInsertId();
        // Update room status to Occupied
        $updateRoom = $pdo->prepare("UPDATE rooms SET status = 'Occupied' WHERE id = ?");
        $updateRoom->execute([$room_id]);
        
        $_SESSION['success'] = $lang['checkin_success'];
        $_SESSION['print_booking'] = $booking_id;
        
        logActivity($pdo, "Check-in ເຂົ້າພັກ", "ລູກຄ້າ: $customer_name, ຫ້ອງ: " . $room['room_number']);
        
        header("Location: walkin.php");
        exit();
    } else {
        $error = "ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຂໍ້ມູນ!";
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['check_in']; ?></title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            h2 { font-size: 1.3rem !important; }
            h4 { font-size: 1rem !important; }
            .card-title { font-size: 1rem !important; }
            .card-body { padding: 10px; }
            .form-group label { font-size: 0.9rem; }
            .form-control { font-size: 0.9rem; height: calc(2rem + 2px); }
            .btn { font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Room Info Column -->
        <div class="col-md-4">
            <div class="card card-info card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bed"></i> <?php echo $lang['room_details'] ?? 'ຂໍ້ມູນຫ້ອງພັກ'; ?></h3>
                </div>
                <div class="card-body box-profile text-center">
                    <div class="display-3 text-info mb-3">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3 class="profile-username text-center"><?php echo $lang['room']; ?> <?php echo htmlspecialchars($room['room_number']); ?></h3>
                    <p class="text-muted text-center"><?php echo htmlspecialchars($room['room_type']); ?> (<?php echo htmlspecialchars($room['bed_type']); ?>)</p>
                    
                    <ul class="list-group list-group-unbordered mb-3 text-left">
                        <li class="list-group-item">
                            <b><?php echo $lang['price']; ?> / <?php echo $lang['nights_count']; ?>:</b> <a class="float-right text-dark"><?php echo number_format($room['price']); ?> ₭</a>
                        </li>
                        <li class="list-group-item">
                            <b><?php echo $lang['nights']; ?>:</b> <a class="float-right text-dark"><?php echo $nights; ?> <?php echo $lang['nights_count']; ?></a>
                        </li>
                        <li class="list-group-item">
                            <b><?php echo $lang['checkin_date']; ?>:</b> <a class="float-right text-success"><?php echo date('d/m/Y', strtotime($check_in_date)); ?></a>
                        </li>
                        <li class="list-group-item">
                            <b><?php echo $lang['checkout_date']; ?>:</b> <a class="float-right text-danger"><?php echo date('d/m/Y', strtotime($check_out_date)); ?></a>
                        </li>
                        <li class="list-group-item bg-light">
                            <b><?php echo $lang['subtotal']; ?>:</b> <a class="float-right text-dark"><?php echo number_format($total_price); ?> ₭</a>
                        </li>
                        <?php if($tax_percent > 0): ?>
                        <li class="list-group-item">
                            <b><?php echo $lang['tax_percent'] ?? 'Tax'; ?> (<?php echo $tax_percent; ?>%):</b> <a class="float-right text-info"><?php echo number_format($tax_amount); ?> ₭</a>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item bg-dark">
                            <b><?php echo $lang['grand_total']; ?>:</b> <a class="float-right text-warning font-weight-bold" style="font-size: 1.1rem;"><?php echo number_format($grand_total); ?> ₭</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Customer Form Column -->
        <div class="col-md-8">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-edit"></i> <?php echo $lang['booking_info']; ?> (Check-in)</h3>
                </div>
                <form action="" method="post" id="checkinForm">
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo $lang['full_name']; ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="<?php echo $lang['enter_name']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo $lang['phone']; ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="<?php echo $lang['enter_phone']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo $lang['passport']; ?></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="text" name="passport_number" class="form-control" placeholder="<?php echo $lang['enter_passport']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo $lang['guests']; ?></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-users"></i></span>
                                        </div>
                                        <input type="number" name="guest_count" class="form-control" value="1" min="1">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label><?php echo $lang['address']; ?></label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="<?php echo $lang['enter_address']; ?>"></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mt-2">
                                <h5 class="text-info border-bottom pb-2"><i class="fas fa-money-bill-wave"></i> <?php echo $lang['payment_info']; ?></h5>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo $lang['payment_method_label']; ?> <span class="text-danger">*</span></label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="Cash"><?php echo $lang['cash'] ?? 'Cash'; ?></option>
                                        <option value="Transfer"><?php echo $lang['transfer'] ?? 'Transfer'; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo $lang['grand_total']; ?> (₭) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">₭</span>
                                        </div>
                                        <input type="text" name="deposit_amount" class="form-control number-format" value="<?php echo number_format($grand_total); ?>" required readonly>
                                    </div>
                                    <small class="text-muted">ລວມພາສີອາກອນແລ້ວ (<?php echo $nights; ?> ຄືນ)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right bg-white border-top">
                        <a href="walkin.php" class="btn btn-default"><i class="fas fa-times"></i> <?php echo $lang['cancel']; ?></a>
                        <button type="submit" name="checkin" class="btn btn-success ml-2" style="padding-left: 30px; padding-right: 30px;">
                            <i class="fas fa-check"></i> <?php echo $lang['confirm_checkin']; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Number formatting
    $('.number-format').on('input', function(e) {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== '') {
            $(this).val(parseInt(value, 10).toLocaleString('en-US'));
        } else {
            $(this).val('0');
        }
    });

    // Form validation
    $('#checkinForm').on('submit', function(e) {
        var name = $('#customer_name').val().trim();
        var phone = $('#customer_phone').val().trim();
        
        if (name === '' || phone === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: '<?php echo $lang['warning_label'] ?? 'ຂໍ້ມູນບໍ່ຄົບຖ້ວນ'; ?>',
                text: '<?php echo $lang['enter_name_phone_msg'] ?? 'ກະລຸນາປ້ອນຊື່ ແລະ ເບີໂທລູກຄ້າໃຫ້ຄົບຖ້ວນ!'; ?>',
                confirmButtonText: '<?php echo $lang['ok']; ?>'
            });
            return false;
        }
    });
});
</script>
</body>
</html>

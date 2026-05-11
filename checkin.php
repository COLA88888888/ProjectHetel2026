<?php
session_start();
require_once 'config/db.php';

// Check if accessing directly or via Walk-in
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$nights = isset($_GET['nights']) ? (int)$_GET['nights'] : 1;

if ($room_id > 0) {
    // Check if room is still available
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'Available'");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        $_SESSION['error'] = "ຂໍອະໄພ! ຫ້ອງນີ້ບໍ່ຫວ່າງແລ້ວ ຫຼື ຖືກຈອງໄປແລ້ວ.";
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkin'])) {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $passport_number = trim($_POST['passport_number']);
    $address = trim($_POST['address']);
    $guest_count = (int)$_POST['guest_count'];
    $deposit_amount = (float)str_replace(',', '', $_POST['deposit_amount']);
    
    // Save to bookings table
    $stmt = $pdo->prepare("INSERT INTO bookings (room_id, customer_name, customer_phone, passport_number, address, guest_count, check_in_date, check_out_date, total_price, deposit_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Occupied')");
    
    if ($stmt->execute([$room_id, $customer_name, $customer_phone, $passport_number, $address, $guest_count, $check_in_date, $check_out_date, $total_price, $deposit_amount])) {
        $booking_id = $pdo->lastInsertId();
        // Update room status to Occupied
        $updateRoom = $pdo->prepare("UPDATE rooms SET status = 'Occupied' WHERE id = ?");
        $updateRoom->execute([$room_id]);
        
        $_SESSION['success'] = "ດຳເນີນການ Check-in ເຂົ້າພັກສຳເລັດແລ້ວ!";
        $_SESSION['print_booking'] = $booking_id;
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
    <title>Check-in ເຂົ້າພັກ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Room Info Column -->
        <div class="col-md-4">
            <div class="card card-info card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bed"></i> ຂໍ້ມູນຫ້ອງພັກ</h3>
                </div>
                <div class="card-body box-profile text-center">
                    <div class="display-3 text-info mb-3">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3 class="profile-username text-center">ຫ້ອງ <?php echo htmlspecialchars($room['room_number']); ?></h3>
                    <p class="text-muted text-center"><?php echo htmlspecialchars($room['room_type']); ?> (<?php echo htmlspecialchars($room['bed_type']); ?>)</p>
                    
                    <ul class="list-group list-group-unbordered mb-3 text-left">
                        <li class="list-group-item">
                            <b>ລາຄາ / ຄືນ:</b> <a class="float-right text-dark"><?php echo number_format($room['price']); ?> ກີບ</a>
                        </li>
                        <li class="list-group-item">
                            <b>ຈຳນວນຄືນ (Nights):</b> <a class="float-right text-dark"><?php echo $nights; ?> ຄືນ</a>
                        </li>
                        <li class="list-group-item">
                            <b>ວັນທີເຂົ້າພັກ:</b> <a class="float-right text-success"><?php echo date('d/m/Y', strtotime($check_in_date)); ?></a>
                        </li>
                        <li class="list-group-item">
                            <b>ວັນທີອອກ:</b> <a class="float-right text-danger"><?php echo date('d/m/Y', strtotime($check_out_date)); ?></a>
                        </li>
                        <li class="list-group-item bg-light">
                            <b>ຍອດລວມ (Total):</b> <a class="float-right text-info font-weight-bold" style="font-size: 1.1rem;"><?php echo number_format($total_price); ?> ກີບ</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Customer Form Column -->
        <div class="col-md-8">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-edit"></i> ຂໍ້ມູນລູກຄ້າ (Check-in)</h3>
                </div>
                <form action="" method="post" id="checkinForm">
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ຊື່ ແລະ ນາມສະກຸນ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ລູກຄ້າ">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ເບີໂທຕິດຕໍ່ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="ກະລຸນາປ້ອນເບີໂທ">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ເລກບັດປະຈຳຕົວ / ພາສປອດ (ທາງເລືອກ)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="text" name="passport_number" class="form-control" placeholder="ເອກະສານຢັ້ງຢືນ">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ຈຳນວນຜູ້ເຂົ້າພັກ</label>
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
                                    <label>ທີ່ຢູ່ລູກຄ້າ (ທາງເລືອກ)</label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="ບ້ານ, ເມືອງ, ແຂວງ..."></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mt-2">
                                <h5 class="text-info border-bottom pb-2"><i class="fas fa-money-bill-wave"></i> ການຊຳລະເງິນ</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ເງິນມັດຈຳ / ຈ່າຍກ່ອນ (ກີບ)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">₭</span>
                                        </div>
                                        <input type="text" name="deposit_amount" class="form-control number-format" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right bg-white border-top">
                        <a href="walkin.php" class="btn btn-default"><i class="fas fa-times"></i> ຍົກເລີກ</a>
                        <button type="submit" name="checkin" class="btn btn-success ml-2" style="padding-left: 30px; padding-right: 30px;">
                            <i class="fas fa-check"></i> ຍືນຍັນການເຂົ້າພັກ (Check-in)
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
                title: 'ຂໍ້ມູນບໍ່ຄົບຖ້ວນ',
                text: 'ກະລຸນາປ້ອນຊື່ ແລະ ເບີໂທລູກຄ້າໃຫ້ຄົບຖ້ວນ!',
                confirmButtonText: 'ຕົກລົງ'
            });
            return false;
        }
    });
});
</script>
</body>
</html>

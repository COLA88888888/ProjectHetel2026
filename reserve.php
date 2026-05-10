<?php
session_start();
require_once 'config/db.php';

// Fetch room types
$stmtTypes = $pdo->query("SELECT * FROM room_types ORDER BY id DESC");
$room_types = $stmtTypes->fetchAll();

// Handle cancel reservation
if (isset($_GET['cancel_booking'])) {
    $bookingId = (int)$_GET['cancel_booking'];
    $roomId = (int)$_GET['room_id'];
    $pdo->prepare("DELETE FROM bookings WHERE id = ? AND status = 'Booked'")->execute([$bookingId]);
    $pdo->prepare("UPDATE rooms SET status = 'Available' WHERE id = ?")->execute([$roomId]);
    $_SESSION['success'] = "ຍົກເລີກການຈອງສຳເລັດ!";
    header("Location: reserve.php");
    exit();
}

$available_rooms = [];
$nights = 1;
$selected_type = '';
$reserve_date = date('Y-m-d', strtotime('+1 day'));

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $nights = (int)$_POST['nights'];
    $selected_type = $_POST['room_type'];
    $reserve_date = $_POST['reserve_date'];

    if ($selected_type === 'all' || empty($selected_type)) {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE status = 'Available' AND (housekeeping_status = 'ພ້ອມໃຊ້' OR housekeeping_status = 'Ready')");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_type = ? AND status = 'Available' AND (housekeeping_status = 'ພ້ອມໃຊ້' OR housekeeping_status = 'Ready')");
        $stmt->execute([$selected_type]);
    }
    $available_rooms = $stmt->fetchAll();
}

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reserve'])) {
    $room_id = (int)$_POST['room_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $passport_number = trim($_POST['passport_number']);
    $address = trim($_POST['address']);
    $guest_count = (int)$_POST['guest_count'];
    $check_in_date = $_POST['check_in_date'];
    $nights_res = (int)$_POST['nights_count'];
    $check_out_date = date('Y-m-d', strtotime($check_in_date . " +$nights_res days"));
    $deposit_amount = (float)str_replace(',', '', $_POST['deposit_amount']);

    // Get room price
    $stmtRoom = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'Available'");
    $stmtRoom->execute([$room_id]);
    $room = $stmtRoom->fetch();

    if ($room) {
        $total_price = $room['price'] * $nights_res;
        $stmt = $pdo->prepare("INSERT INTO bookings (room_id, customer_name, customer_phone, passport_number, address, guest_count, check_in_date, check_out_date, total_price, deposit_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Booked')");
        
        if ($stmt->execute([$room_id, $customer_name, $customer_phone, $passport_number, $address, $guest_count, $check_in_date, $check_out_date, $total_price, $deposit_amount])) {
            // Update room status to Booked
            $updateRoom = $pdo->prepare("UPDATE rooms SET status = 'Booked' WHERE id = ?");
            $updateRoom->execute([$room_id]);
            
            $_SESSION['success'] = "ຈອງຫ້ອງລ່ວງໜ້າສຳເລັດ! ຫ້ອງ " . $room['room_number'] . " ວັນທີ " . date('d/m/Y', strtotime($check_in_date));
            header("Location: reserve.php");
            exit();
        } else {
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ!";
        }
    } else {
        $_SESSION['error'] = "ຫ້ອງນີ້ບໍ່ຫວ່າງແລ້ວ!";
    }
    header("Location: reserve.php");
    exit();
}

// Get list of current reservations
$stmtReserved = $pdo->query("
    SELECT b.*, r.room_number, r.room_type 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = 'Booked' 
    ORDER BY b.check_in_date ASC
");
$reservations = $stmtReserved->fetchAll();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈອງຫ້ອງລ່ວງໜ້າ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 10px; }
        .room-card { transition: transform 0.2s; border-radius: 10px; }
        .room-card:hover { transform: scale(1.02); cursor: pointer; border-color: #f39c12; }
        .room-price { font-size: 1.1rem; font-weight: 600; color: #f39c12; }
        @media (max-width: 768px) {
            h2 { font-size: 1.2rem; }
            .room-card .display-4 { font-size: 2rem; }
            .room-card h4 { font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if(isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'success', title: 'ສຳເລັດ', text: '<?php echo $_SESSION['success']; ?>', showConfirmButton: false, timer: 2500 });
            });
        </script>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: '<?php echo $_SESSION['error']; ?>', confirmButtonText: 'ຕົກລົງ' });
            });
        </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-calendar-check text-warning"></i> ຈອງຫ້ອງລ່ວງໜ້າ (Reservation)</h2>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card card-warning card-outline shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-search"></i> ຄົ້ນຫາຫ້ອງຫວ່າງ</h3></div>
        <form action="" method="post">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="form-group">
                            <label>ວັນທີເຂົ້າພັກ</label>
                            <input type="date" name="reserve_date" class="form-control" value="<?php echo $reserve_date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="form-group">
                            <label>ຈຳນວນຄືນ</label>
                            <input type="number" name="nights" class="form-control" value="<?php echo $nights; ?>" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="form-group">
                            <label>ປະເພດຫ້ອງ</label>
                            <select name="room_type" class="form-control">
                                <option value="all">-- ທຸກປະເພດ --</option>
                                <?php foreach($room_types as $rt): ?>
                                    <option value="<?php echo htmlspecialchars($rt['room_type_name']); ?>" <?php echo ($selected_type == $rt['room_type_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rt['room_type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 col-12 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" name="search" class="btn btn-warning btn-block text-white">
                                <i class="fas fa-search"></i> ຄົ້ນຫາ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Available Rooms Results -->
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])): ?>
    <div class="card card-outline card-success shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-door-open"></i> ຫ້ອງຫວ່າງ (<?php echo count($available_rooms); ?> ຫ້ອງ)</h3></div>
        <div class="card-body bg-light">
            <?php if (count($available_rooms) > 0): ?>
                <div class="row">
                    <?php foreach($available_rooms as $room): ?>
                        <div class="col-lg-3 col-md-4 col-6 mb-3">
                            <div class="card room-card shadow-sm border-warning">
                                <div class="card-body text-center p-3">
                                    <div class="display-4 text-warning mb-2"><i class="fas fa-door-closed"></i></div>
                                    <h4 class="font-weight-bold">ຫ້ອງ <?php echo htmlspecialchars($room['room_number']); ?></h4>
                                    <p class="text-muted mb-1 small"><?php echo htmlspecialchars($room['room_type']); ?> (<?php echo htmlspecialchars($room['bed_type']); ?>)</p>
                                    <hr class="my-2">
                                    <p class="room-price mb-0"><?php echo number_format($room['price']); ?> ກີບ / ຄືນ</p>
                                    <?php if($nights > 1): ?>
                                        <p class="text-muted small mb-0">ລວມ <?php echo $nights; ?> ຄືນ: <strong><?php echo number_format($room['price'] * $nights); ?> ກີບ</strong></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer p-0">
                                    <button class="btn btn-warning btn-block rounded-0 text-white btn-reserve"
                                        data-room-id="<?php echo $room['id']; ?>"
                                        data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>"
                                        data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>"
                                        data-price="<?php echo $room['price']; ?>"
                                        data-nights="<?php echo $nights; ?>"
                                        data-total="<?php echo $room['price'] * $nights; ?>"
                                        data-checkin="<?php echo $reserve_date; ?>">
                                        <i class="fas fa-calendar-plus"></i> ຈອງລ່ວງໜ້າ
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-times-circle text-danger fa-3x mb-3 d-block"></i>
                    <h5 class="text-danger">ບໍ່ມີຫ້ອງຫວ່າງ</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Current Reservations List -->
    <?php if (count($reservations) > 0): ?>
    <div class="card card-outline card-info shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-list"></i> ລາຍການຈອງລ່ວງໜ້າ (<?php echo count($reservations); ?>)</h3></div>
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
            <table class="table table-bordered table-striped text-center mb-0" style="min-width: 600px;">
                <thead class="bg-warning text-white">
                    <tr>
                        <th>ຫ້ອງ</th>
                        <th>ລູກຄ້າ</th>
                        <th>ເບີໂທ</th>
                        <th>ວັນເຂົ້າ</th>
                        <th>ວັນອອກ</th>
                        <th>ຍອດລວມ</th>
                        <th>ມັດຈຳ</th>
                        <th>ຈັດການ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservations as $res): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($res['room_number']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($res['room_type']); ?></small></td>
                        <td class="text-left"><?php echo htmlspecialchars($res['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($res['customer_phone']); ?></td>
                        <td class="text-success font-weight-bold"><?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?></td>
                        <td class="text-danger"><?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?></td>
                        <td class="text-right"><?php echo number_format($res['total_price']); ?> ₭</td>
                        <td class="text-right text-info"><?php echo number_format($res['deposit_amount']); ?> ₭</td>
                        <td class="align-middle">
                            <a href="checkin_reserved.php?booking_id=<?php echo $res['id']; ?>" class="btn btn-sm btn-success mb-1" title="ເຂົ້າພັກ">
                                <i class="fas fa-sign-in-alt"></i> ເຂົ້າພັກ
                            </a>
                            <a href="#" class="btn btn-sm btn-danger btn-cancel-reserve mb-1" data-id="<?php echo $res['id']; ?>" data-room-id="<?php echo $res['room_id']; ?>" title="ຍົກເລີກ">
                                <i class="fas fa-times-circle"></i> ຍົກເລີກ
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Reserve Modal -->
<div class="modal fade" id="reserveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="post" id="reserveForm">
                <input type="hidden" name="room_id" id="modal_room_id">
                <input type="hidden" name="check_in_date" id="modal_checkin">
                <input type="hidden" name="nights_count" id="modal_nights">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-check"></i> ຈອງຫ້ອງ <span id="modal_room_label"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <strong><i class="fas fa-info-circle"></i> ຂໍ້ມູນການຈອງ:</strong> 
                        ຫ້ອງ <span id="info_room" class="font-weight-bold"></span> | 
                        ວັນທີ: <span id="info_date" class="text-success"></span> | 
                        <span id="info_nights"></span> ຄືນ | 
                        ລວມ: <span id="info_total" class="text-danger font-weight-bold"></span> ກີບ
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ຊື່ ແລະ ນາມສະກຸນ <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" id="res_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ລູກຄ້າ" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ເບີໂທຕິດຕໍ່ <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" id="res_phone" class="form-control" placeholder="020 XXXXXXXX" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ເລກບັດ / ພາສປອດ</label>
                                <input type="text" name="passport_number" class="form-control" placeholder="ທາງເລືອກ">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ຈຳນວນຜູ້ເຂົ້າພັກ</label>
                                <input type="number" name="guest_count" class="form-control" value="1" min="1">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>ທີ່ຢູ່</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="ບ້ານ, ເມືອງ, ແຂວງ..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ເງິນມັດຈຳ (ກີບ)</label>
                                <input type="text" name="deposit_amount" class="form-control number-format" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fas fa-times"></i> ຍົກເລີກ</button>
                    <button type="submit" name="reserve" class="btn btn-warning text-white px-4"><i class="fas fa-calendar-check"></i> ຢືນຢັນການຈອງ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Open reserve modal
    $('.btn-reserve').on('click', function() {
        var btn = $(this);
        $('#modal_room_id').val(btn.data('room-id'));
        $('#modal_checkin').val(btn.data('checkin'));
        $('#modal_nights').val(btn.data('nights'));
        $('#modal_room_label').text('ຫ້ອງ ' + btn.data('room-number'));
        $('#info_room').text(btn.data('room-number'));
        $('#info_date').text(btn.data('checkin'));
        $('#info_nights').text(btn.data('nights'));
        $('#info_total').text(Number(btn.data('total')).toLocaleString('en-US'));
        $('#reserveModal').modal('show');
    });

    // Form validation
    $('#reserveForm').on('submit', function(e) {
        if ($('#res_name').val().trim() === '' || $('#res_phone').val().trim() === '') {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'ຂໍ້ມູນບໍ່ຄົບ', text: 'ກະລຸນາປ້ອນຊື່ ແລະ ເບີໂທລູກຄ້າ!', confirmButtonText: 'ຕົກລົງ' });
            return false;
        }
    });

    // Cancel reservation
    $('.btn-cancel-reserve').on('click', function(e) {
        e.preventDefault();
        var bookingId = $(this).data('id');
        var roomId = $(this).data('room-id');
        Swal.fire({
            title: 'ຍົກເລີກການຈອງ?',
            text: 'ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການຍົກເລີກ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ຍົກເລີກການຈອງ',
            cancelButtonText: 'ປິດ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?cancel_booking=' + bookingId + '&room_id=' + roomId;
            }
        });
    });

    // Number formatting
    $('.number-format').on('input', function() {
        var val = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(val !== '' ? parseInt(val, 10).toLocaleString('en-US') : '0');
    });
});
</script>
</body>
</html>

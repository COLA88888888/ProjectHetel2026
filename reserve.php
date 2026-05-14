<?php
session_start();
require_once 'config/session_check.php';
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
$check_in_search = $_POST['check_in_search'] ?? date('Y-m-d');
$check_out_search = $_POST['check_out_search'] ?? date('Y-m-d', strtotime('+1 day'));
$selected_type = $_POST['room_type'] ?? 'all';

// Calculate nights for display and pricing
$d1 = new DateTime($check_in_search);
$d2 = new DateTime($check_out_search);
$nights_count = $d1->diff($d2)->days;
if($nights_count < 1) $nights_count = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    // Variables are already set above safely
    // Core Logic: Find rooms that are NOT booked between search dates
    // And also ensure the room isn't currently Occupied if searching for today
    $query = "
        SELECT r.* FROM rooms r 
        WHERE (r.housekeeping_status = 'ພ້ອມໃຊ້' OR r.housekeeping_status = 'Ready')
        AND r.status != 'Maintenance'
        AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status IN ('Booked', 'Occupied', 'Checked In') 
            AND check_in_date < ? 
            AND check_out_date > ?
        )
    ";
    
    // If search includes Today, also exclude rooms currently Occupied/Checked In
    if ($check_in_search == date('Y-m-d')) {
        $query .= " AND r.status NOT IN ('Occupied', 'Checked In')";
    }
    
    if ($selected_type !== 'all') {
        $query .= " AND r.room_type = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$check_out_search, $check_in_search, $selected_type]);
    } else {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$check_out_search, $check_in_search]);
    }
    $available_rooms = $stmt->fetchAll();

    // Alert if rooms are full
    if (count($available_rooms) == 0) {
        $_SESSION['info_msg'] = "ຂໍອະໄພ, ຫ້ອງພັກທຸກຫ້ອງແມ່ນເຕັມໝົດແລ້ວໃນຊ່ວງວັນທີທີ່ທ່ານເລືອກ!";
    }
}

// Handle Update Reservation (Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_reserve'])) {
    $booking_id = (int)$_POST['booking_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $guest_count = (int)$_POST['guest_count'];
    $check_in = $_POST['check_in_date'];
    $check_out = $_POST['check_out_date'];
    $deposit = (float)str_replace(',', '', $_POST['deposit_amount']);
    
    // Overlap Check for Update
    $stmtRoomId = $pdo->prepare("SELECT room_id FROM bookings WHERE id = ?");
    $stmtRoomId->execute([$booking_id]);
    $current_room_id = $stmtRoomId->fetchColumn();

    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE room_id = ? 
        AND status IN ('Booked', 'Occupied', 'Checked In') 
        AND id != ?
        AND check_in_date < ? 
        AND check_out_date > ?
    ");
    $stmtCheck->execute([$current_room_id, $booking_id, $check_out, $check_in]);
    $is_occupied = $stmtCheck->fetchColumn() > 0;

    if ($is_occupied) {
        $_SESSION['error'] = "ຂໍອະໄພ, ວັນທີທີ່ທ່ານປ່ຽນໃໝ່ມີຄົນຈອງຫ້ອງນີ້ແລ້ວ!";
        header("Location: reserve.php");
        exit();
    }

    // Get room price to recalculate total
    $stmtPrice = $pdo->prepare("SELECT r.price FROM rooms r JOIN bookings b ON r.id = b.room_id WHERE b.id = ?");
    $stmtPrice->execute([$booking_id]);
    $room_price = $stmtPrice->fetchColumn() ?: 0;
    
    $d1 = new DateTime($check_in);
    $d2 = new DateTime($check_out);
    $nights = $d1->diff($d2)->days;
    if($nights < 1) $nights = 1;
    $total_price = $room_price * $nights;

    $stmt = $pdo->prepare("UPDATE bookings SET customer_name = ?, customer_phone = ?, guest_count = ?, check_in_date = ?, check_out_date = ?, total_price = ?, deposit_amount = ? WHERE id = ?");
    if ($stmt->execute([$customer_name, $customer_phone, $guest_count, $check_in, $check_out, $total_price, $deposit, $booking_id])) {
        $_SESSION['success'] = "ແກ້ໄຂການຈອງສຳເລັດ! ຍອດໃໝ່: " . number_format($total_price) . " ກີບ";
    } else {
        $_SESSION['error'] = "ບໍ່ສາມາດແກ້ໄຂຂໍ້ມູນໄດ້!";
    }
    header("Location: reserve.php");
    exit();
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

        // Final Overlap Check before inserting
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE room_id = ? 
            AND status IN ('Booked', 'Occupied', 'Checked In') 
            AND check_in_date < ? 
            AND check_out_date > ?
        ");
        $stmtCheck->execute([$room_id, $check_out_date, $check_in_date]);
        $is_occupied = $stmtCheck->fetchColumn() > 0;

        if (!$is_occupied) {
            // Get room price
            $stmtRoom = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmtRoom->execute([$room_id]);
            $room = $stmtRoom->fetch();

            if ($room) {
                $total_price = $room['price'] * $nights_res;
                $stmt = $pdo->prepare("INSERT INTO bookings (room_id, customer_name, customer_phone, passport_number, address, guest_count, check_in_date, check_out_date, total_price, deposit_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Booked')");
                
                if ($stmt->execute([$room_id, $customer_name, $customer_phone, $passport_number, $address, $guest_count, $check_in_date, $check_out_date, $total_price, $deposit_amount])) {
                    $_SESSION['success'] = "ຈອງຫ້ອງລ່ວງໜ້າສຳເລັດ! ຫ້ອງ " . $room['room_number'] . " ວັນທີ " . date('d/m/Y', strtotime($check_in_date));
                    header("Location: reserve.php");
                    exit();
                }
            }
        } else {
            $_SESSION['error'] = "ຂໍອະໄພ, ຫ້ອງນີ້ມີຄົນຈອງໃນຊ່ວງວັນທີນີ້ແລ້ວ!";
        }
        header("Location: reserve.php");
        exit();
    }

// Pagination Logic
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$stmtCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Booked'");
$total_records = $stmtCount->fetchColumn();
$total_pages = ceil($total_records / $limit);

$stmtReserved = $pdo->prepare("
    SELECT b.*, r.room_number, r.room_type,
    (SELECT COUNT(*) FROM bookings b2 
     WHERE ((b2.customer_phone = b.customer_phone AND b.customer_phone != '' AND b.customer_phone != '-') 
            OR (b2.customer_name = b.customer_name AND b2.customer_phone = b.customer_phone))
     AND b2.status IN ('Booked', 'Occupied') 
     AND b2.id != b.id
     AND b2.check_in_date < b.check_out_date 
     AND b2.check_out_date > b.check_in_date) as other_bookings
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = 'Booked' 
    ORDER BY b.check_in_date ASC
    LIMIT :limit OFFSET :offset
");
$stmtReserved->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmtReserved->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtReserved->execute();
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *:not(.fas):not(.far):not(.fab):not(.fa) { font-family: 'Noto Sans Lao Looped', sans-serif !important; }
        .fas, .far, .fab, .fa { font-family: "Font Awesome 5 Free" !important; font-weight: 900 !important; }
        body { background-color: #f4f6f9; padding: 10px; }
        .room-card { transition: transform 0.2s; border-radius: 10px; }
        .room-card:hover { transform: scale(1.02); cursor: pointer; border-color: #f39c12; }
        .room-price { font-size: 1.1rem; font-weight: 600; color: #f39c12; }
        @media (max-width: 768px) {
            h2 { font-size: 1.2rem; }
            .room-card .display-4 { font-size: 2rem; }
            .room-card h4 { font-size: 1rem; }
        }
    </style>
    <script>
        if (window.top === window.self) { window.location.href = 'menu_admin.php'; }
    </script>
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

    <?php if(isset($_SESSION['info_msg'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'info', title: 'ຫ້ອງເຕັມ', text: '<?php echo $_SESSION['info_msg']; ?>', confirmButtonText: 'ຕົກລົງ' });
            });
        </script>
    <?php unset($_SESSION['info_msg']); endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-calendar-check text-warning"></i> ຈອງຫ້ອງລ່ວງໜ້າ</h2>
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
                            <label><i class="fas fa-calendar-alt text-success"></i> ວັນທີເຂົ້າພັກ</label>
                            <input type="date" name="check_in_search" id="search_checkin" class="form-control" value="<?php echo $check_in_search; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="form-group">
                            <label><i class="fas fa-moon text-warning"></i> ຈຳນວນຄືນ</label>
                            <input type="number" id="search_nights" class="form-control" value="<?php echo $nights_count; ?>" min="1">
                        </div>
                    </div>
                    <div class="col-md-3 col-12">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check text-danger"></i> ວັນທີອອກ</label>
                            <input type="date" name="check_out_search" id="search_checkout" class="form-control" value="<?php echo $check_out_search; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2 col-12">
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
                    <div class="col-md-2 col-12 d-flex align-items-end">
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
                                    <p class="room-price mb-0 text-orange font-weight-bold" style="font-size: 1.2rem;"><?php echo number_format($room['price']); ?> ກີບ / ຄືນ</p>
                                    <div class="mt-3 p-2 border-success rounded shadow-sm" style="background-color: #e8f5e9; border: 1px dashed #28a745;">
                                        <div class="text-success small font-weight-bold mb-1"><i class="fas fa-check-circle"></i> ຫວ່າງວັນທີ:</div>
                                        <div class="h6 mb-0 font-weight-bold text-dark">
                                            <?php echo date('d/m/Y', strtotime($check_in_search)); ?> 
                                            <span class="text-muted mx-1">ຫາ</span> 
                                            <?php echo date('d/m/Y', strtotime($check_out_search)); ?>
                                        </div>
                                    </div>
                                    <?php if($nights_count > 1): ?>
                                        <div class="mt-2 text-primary font-weight-bold">
                                            <i class="fas fa-info-circle"></i> ລວມ <?php echo $nights_count; ?> ຄືນ: <?php echo number_format($room['price'] * $nights_count); ?> ກີບ
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer p-0">
                                    <button class="btn btn-warning btn-block rounded-0 text-white btn-reserve"
                                        data-room-id="<?php echo $room['id']; ?>"
                                        data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>"
                                        data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>"
                                        data-price="<?php echo $room['price']; ?>"
                                        data-nights="<?php echo $nights_count; ?>"
                                        data-total="<?php echo $room['price'] * $nights_count; ?>"
                                        data-checkin="<?php echo $check_in_search; ?>">
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
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title"><i class="fas fa-list"></i> ລາຍການຈອງລ່ວງໜ້າ (<?php echo count($reservations); ?>)</h3>
            <div class="card-tools ml-auto">
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" id="res_search_input" class="form-control" placeholder="ຄົ້ນຫາຊື່ ຫຼື ເບີໂທ...">
                    <div class="input-group-append">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-warning"></i></span>
                    </div>
                </div>
            </div>
        </div>
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
                        <th>ຄືນ</th>
                        <th>ຍອດລວມ</th>
                        <th>ມັດຈຳ</th>
                        <th>ຈັດການ</th>
                    </tr>
                </thead>
                <tbody id="res_table_body">
                    <?php foreach($reservations as $res): ?>
                    <tr class="res-row">
                        <td><strong><?php echo htmlspecialchars($res['room_number']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($res['room_type']); ?></small></td>
                        <td class="text-left">
                            <span class="customer-name-text"><?php echo htmlspecialchars($res['customer_name']); ?></span>
                            <?php if($res['other_bookings'] > 0): ?>
                                <br><span class="badge badge-danger" title="ລູກຄ້ານີ້ມີການຈອງ ຫຼື ເຂົ້າພັກຫ້ອງອື່ນອີກ"><i class="fas fa-users"></i> ຈອງ <?php echo $res['other_bookings'] + 1; ?> ຫ້ອງ</span>
                            <?php endif; ?>
                        </td>
                        <td class="customer-phone-text"><?php echo htmlspecialchars($res['customer_phone']); ?></td>
                        <td class="text-success font-weight-bold"><?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?></td>
                        <td class="text-danger"><?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?></td>
                        <td>
                            <?php 
                                $diff = date_diff(date_create($res['check_in_date']), date_create($res['check_out_date']));
                                echo $diff->format("%a"); 
                            ?>
                        </td>
                        <td class="text-right"><?php echo number_format($res['total_price']); ?> ₭</td>
                        <td class="text-right text-info"><?php echo number_format($res['deposit_amount']); ?> ₭</td>
                        <td class="align-middle text-center">
                            <a href="checkin_reserved.php?booking_id=<?php echo $res['id']; ?>" class="btn btn-xs btn-success mb-1" title="ເຂົ້າພັກ">
                                <i class="fas fa-sign-in-alt"></i> ເຂົ້າພັກ
                            </a>
                            <button class="btn btn-xs btn-primary mb-1 btn-view-reserve" 
                                data-id="<?php echo $res['id']; ?>"
                                data-room="<?php echo htmlspecialchars($res['room_number']); ?>"
                                data-type="<?php echo htmlspecialchars($res['room_type']); ?>"
                                data-name="<?php echo htmlspecialchars($res['customer_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($res['customer_phone']); ?>"
                                data-passport="<?php echo htmlspecialchars($res['passport_number'] ?? '-'); ?>"
                                data-address="<?php echo htmlspecialchars($res['address'] ?? '-'); ?>"
                                data-guests="<?php echo $res['guest_count']; ?>"
                                data-checkin="<?php echo date('d/m/Y', strtotime($res['check_in_date'])); ?>"
                                data-checkout="<?php echo date('d/m/Y', strtotime($res['check_out_date'])); ?>"
                                data-total="<?php echo number_format($res['total_price']); ?>"
                                data-deposit="<?php echo number_format($res['deposit_amount']); ?>"
                                title="ເບິ່ງລາຍລະອຽດ">
                                <i class="fas fa-eye"></i> ລາຍລະອຽດ
                            </button>
                            <button class="btn btn-xs btn-info mb-1 btn-edit-reserve" 
                                data-id="<?php echo $res['id']; ?>"
                                data-name="<?php echo htmlspecialchars($res['customer_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($res['customer_phone']); ?>"
                                data-guests="<?php echo $res['guest_count']; ?>"
                                data-checkin="<?php echo $res['check_in_date']; ?>"
                                data-checkout="<?php echo $res['check_out_date']; ?>"
                                data-deposit="<?php echo $res['deposit_amount']; ?>"
                                title="ແກ້ໄຂ">
                                <i class="fas fa-edit"></i> ແກ້ໄຂ
                            </button>
                            <a href="#" class="btn btn-xs btn-danger mb-1 btn-cancel-reserve" data-id="<?php echo $res['id']; ?>" data-room-id="<?php echo $res['room_id']; ?>" title="ຍົກເລີກ">
                                <i class="fas fa-times-circle"></i> ຍົກເລີກ
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <!-- Pagination UI -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    ສະແດງ <?php echo $offset + 1; ?> ຫາ <?php echo min($offset + $limit, $total_records); ?> ຈາກທັງໝົດ <?php echo $total_records; ?> ລາຍການ
                </div>
                <nav>
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-item page-link" href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
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
                                <label>ເລກບັດ / ພາສປອດ <span class="text-danger">*</span></label>
                                <input type="text" name="passport_number" class="form-control" placeholder="ກະລຸນາກອກເລກບັດ" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ຈຳນວນຜູ້ເຂົ້າພັກ <span class="text-danger">*</span></label>
                                <input type="number" name="guest_count" class="form-control" value="1" min="1" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>ທີ່ຢູ່ປັດຈຸບັນ <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="2" placeholder="ບ້ານ, ເມືອງ, ແຂວງ..." required></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ເງິນມັດຈຳ (ກີບ)</label>
                                <input type="text" name="deposit_amount" id="modal_deposit" class="form-control number-format" value="0">
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

<!-- View Reserve Modal -->
<div class="modal fade" id="viewReserveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> ລາຍລະອຽດການຈອງ</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="display-4 text-primary"><i class="fas fa-address-card"></i></div>
                    <h4 class="mt-2 font-weight-bold" id="v_name"></h4>
                    <span class="badge badge-info" id="v_room_info"></span>
                </div>
                <table class="table table-sm table-borderless">
                    <tr><td width="40%" class="text-muted">ເບີໂທຕິດຕໍ່:</td><td class="font-weight-bold" id="v_phone"></td></tr>
                    <tr><td class="text-muted">ເລກບັດ/ພາສປອດ:</td><td class="font-weight-bold" id="v_passport"></td></tr>
                    <tr><td class="text-muted">ທີ່ຢູ່:</td><td id="v_address"></td></tr>
                    <tr><td class="text-muted">ຈຳນວນແຂກ:</td><td class="font-weight-bold"><span id="v_guests"></span> ຄົນ</td></tr>
                    <tr><td colspan="2"><hr class="my-1"></td></tr>
                    <tr><td class="text-muted">ວັນທີເຂົ້າພັກ:</td><td class="text-success font-weight-bold" id="v_checkin"></td></tr>
                    <tr><td class="text-muted">ວັນທີອອກ:</td><td class="text-danger font-weight-bold" id="v_checkout"></td></tr>
                    <tr><td class="text-muted">ຍອດລວມທັງໝົດ:</td><td class="font-weight-bold text-primary" id="v_total"></td></tr>
                    <tr><td class="text-muted">ມັດຈຳແລ້ວ:</td><td class="font-weight-bold text-info" id="v_deposit"></td></tr>
                </table>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Reserve Modal -->
<div class="modal fade" id="editReserveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <input type="hidden" name="booking_id" id="edit_booking_id">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> ແກ້ໄຂຂໍ້ມູນການຈອງ</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>ຊື່ລູກຄ້າ</label>
                        <input type="text" name="customer_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>ເບີໂທ</label>
                        <input type="text" name="customer_phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>ຈຳນວນແຂກ</label>
                        <input type="number" name="guest_count" id="edit_guests" class="form-control" min="1" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>ວັນທີເຂົ້າ (Check-in)</label>
                                <input type="date" name="check_in_date" id="edit_checkin" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>ວັນທີອອກ (Check-out)</label>
                                <input type="date" name="check_out_date" id="edit_checkout" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ເງິນມັດຈຳ</label>
                        <input type="text" name="deposit_amount" id="edit_deposit" class="form-control number-format">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">ປິດ</button>
                    <button type="submit" name="update_reserve" class="btn btn-info">ບັນທຶກການແກ້ໄຂ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    *:not(.fas):not(.far):not(.fab):not(.fa) { font-family: 'Noto Sans Lao Looped', sans-serif !important; }
</style>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Helper to calculate date difference
    function getDaysBetween(d1, d2) {
        return Math.ceil((new Date(d2) - new Date(d1)) / (1000 * 60 * 60 * 24));
    }

    // Search Form: Auto-update Checkout when Check-in or Nights change
    function updateSearchDates(source) {
        let cin = $('#search_checkin').val();
        let nights = parseInt($('#search_nights').val()) || 1;
        let cout = $('#search_checkout').val();

        if (source === 'cin' || source === 'nights') {
            if (cin) {
                let d = new Date(cin);
                d.setDate(d.getDate() + nights);
                $('#search_checkout').val(d.toISOString().split('T')[0]);
            }
        } else if (source === 'cout') {
            if (cin && cout) {
                let diff = getDaysBetween(cin, cout);
                if (diff < 1) diff = 1;
                $('#search_nights').val(diff);
            }
        }
    }
    $('#search_checkin, #search_nights').on('change input', function() { updateSearchDates('cin'); });
    $('#search_checkout').on('change', function() { updateSearchDates('cout'); });

    // Edit Modal logic
    $('#edit_checkin, #edit_checkout').on('change', function() {
        let cin = $('#edit_checkin').val();
        let cout = $('#edit_checkout').val();
        if(cin && cout && new Date(cin) >= new Date(cout)) {
            let d = new Date(cin);
            d.setDate(d.getDate() + 1);
            $('#edit_checkout').val(d.toISOString().split('T')[0]);
        }
    });

    // Open reserve modal
    $('.btn-reserve').on('click', function() {
        var btn = $(this);
        var roomId = btn.data('room-id');
        var roomNum = btn.data('room-number');
        var price = btn.data('price');
        
        var checkin = "<?php echo $check_in_search; ?>";
        var checkout = "<?php echo $check_out_search; ?>";
        
        var diff = getDaysBetween(checkin, checkout);
        if (diff < 1) diff = 1;
        
        var total = price * diff;

        $('#modal_room_id').val(roomId);
        $('#modal_checkin').val(checkin);
        $('#modal_nights').val(diff);
        $('#modal_room_label').text('ຫ້ອງ ' + roomNum);
        $('#info_room').text(roomNum);
        $('#info_date').text(checkin + ' ຫາ ' + checkout);
        $('#info_nights').text(diff);
        $('#info_total').text(new Intl.NumberFormat().format(total));
        
        // Auto-calculate 50% deposit
        var deposit = Math.round(total / 2);
        $('#modal_deposit').val(new Intl.NumberFormat().format(deposit));
        
        $('#reserveModal').modal('show');
    });

    // Open Edit Modal
    $('.btn-edit-reserve').on('click', function() {
        var btn = $(this);
        $('#edit_booking_id').val(btn.data('id'));
        $('#edit_name').val(btn.data('name'));
        $('#edit_phone').val(btn.data('phone'));
        $('#edit_guests').val(btn.data('guests'));
        $('#edit_checkin').val(btn.data('checkin'));
        $('#edit_checkout').val(btn.data('checkout'));
        $('#edit_deposit').val(new Intl.NumberFormat().format(btn.data('deposit')));
        $('#editReserveModal').modal('show');
    });

    // Open View Modal
    $('.btn-view-reserve').on('click', function() {
        var btn = $(this);
        $('#v_name').text(btn.data('name'));
        $('#v_room_info').text('ຫ້ອງ ' + btn.data('room') + ' (' + btn.data('type') + ')');
        $('#v_phone').text(btn.data('phone'));
        $('#v_passport').text(btn.data('passport'));
        $('#v_address').text(btn.data('address'));
        $('#v_guests').text(btn.data('guests'));
        $('#v_checkin').text(btn.data('checkin'));
        $('#v_checkout').text(btn.data('checkout'));
        $('#v_total').text(btn.data('total') + ' ກີບ');
        $('#v_deposit').text(btn.data('deposit') + ' ກີບ');
        $('#viewReserveModal').modal('show');
    });

    // Cancel reservation
    $('.btn-cancel-reserve').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var roomId = $(this).data('room-id');
        Swal.fire({
            title: 'ຢືນຢັນການຍົກເລີກ?',
            text: "ທ່ານຕ້ອງການຍົກເລີກການຈອງນີ້ແທ້ຫຼືບໍ່?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ຢືນຢັນ',
            cancelButtonText: 'ຍົກເລີກ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'reserve.php?cancel_booking=' + id + '&room_id=' + roomId;
            }
        });
    });

    // Auto format number
    $('.number-format').on('input', function() {
        var val = $(this).val().replace(/,/g, '');
        if(!isNaN(val) && val !== '') {
            $(this).val(new Intl.NumberFormat().format(val));
        }
    });

    // Reservation Table Live Search
    $('#res_search_input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var visibleRows = 0;
        
        $("#res_table_body tr.res-row").each(function() {
            var isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(isVisible);
            if (isVisible) visibleRows++;
        });

        // Show/Hide "No results" message
        $('#no_res_row').remove();
        if (visibleRows === 0) {
            $("#res_table_body").append('<tr id="no_res_row"><td colspan="8" class="py-4 text-center text-muted"><strong><i class="fas fa-search-minus"></i> ບໍ່ມີຂໍ້ມູນທີ່ທ່ານຄົ້ນຫາ!</strong></td></tr>');
        }
    });
});
</script>
</body>
</html>

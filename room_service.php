<?php
session_start();
require_once 'config/db.php';

// Get active bookings (Occupied rooms)
$stmt = $pdo->query("
    SELECT b.id as booking_id, r.room_number, b.customer_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = 'Occupied'
    ORDER BY r.room_number ASC
");
$active_bookings = $stmt->fetchAll();

$selected_booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (count($active_bookings) > 0 ? $active_bookings[0]['booking_id'] : 0);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $booking_id = (int)$_POST['booking_id'];
    $item_name = trim($_POST['item_name']);
    $price = (float)str_replace(',', '', $_POST['price']);
    $qty = (int)$_POST['qty'];
    $total_price = $price * $qty;

    $stmt = $pdo->prepare("INSERT INTO room_services (booking_id, item_name, price, qty, total_price) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$booking_id, $item_name, $price, $qty, $total_price])) {
        // Update food_charge in bookings
        $updateBooking = $pdo->prepare("UPDATE bookings SET food_charge = food_charge + ? WHERE id = ?");
        $updateBooking->execute([$total_price, $booking_id]);

        $_SESSION['success'] = "ບັນທຶກຄ່າໃຊ້ຈ່າຍເພີ່ມສຳເລັດ!";
        header("Location: room_service.php?booking_id=" . $booking_id);
        exit();
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $booking_id = (int)$_GET['booking_id'];
    
    // Get the total of this item to subtract from booking
    $stmt = $pdo->prepare("SELECT total_price FROM room_services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    
    if ($service) {
        $delStmt = $pdo->prepare("DELETE FROM room_services WHERE id = ?");
        if ($delStmt->execute([$id])) {
            $updateBooking = $pdo->prepare("UPDATE bookings SET food_charge = food_charge - ? WHERE id = ?");
            $updateBooking->execute([$service['total_price'], $booking_id]);
            $_SESSION['success'] = "ລົບລາຍການສຳເລັດ!";
        }
    }
    header("Location: room_service.php?booking_id=" . $booking_id);
    exit();
}

// Fetch services for selected booking
$services = [];
$total_accumulated = 0;
if ($selected_booking_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM room_services WHERE booking_id = ? ORDER BY id DESC");
    $stmt->execute([$selected_booking_id]);
    $services = $stmt->fetchAll();
    
    foreach ($services as $s) {
        $total_accumulated += $s['total_price'];
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບໍລິການເພີ່ມເຕີມລະຫວ່າງພັກ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if(isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ສຳເລັດ',
                    text: '<?php echo $_SESSION['success']; ?>',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        </script>
    <?php unset($_SESSION['success']); endif; ?>
    
    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-concierge-bell"></i> ບັນທຶກຄ່າໃຊ້ຈ່າຍເພີ່ມ (ລະຫວ່າງເຂົ້າພັກ)</h2>
        </div>
    </div>

    <?php if (count($active_bookings) == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> ປະຈຸບັນບໍ່ມີຫ້ອງໃດເຂົ້າພັກຢູ່ເລີຍ. ບໍ່ສາມາດບັນທຶກຄ່າໃຊ້ຈ່າຍໄດ້.
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Form Section -->
            <div class="col-md-4">
                <div class="card card-primary card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cart-plus"></i> ເພີ່ມລາຍການໃໝ່</h3>
                    </div>
                    <form action="" method="post" id="serviceForm">
                        <div class="card-body">
                            <div class="form-group">
                                <label>ເລືອກຫ້ອງພັກ</label>
                                <select name="booking_id" id="bookingSelect" class="form-control" onchange="window.location.href='?booking_id='+this.value">
                                    <?php foreach($active_bookings as $b): ?>
                                        <option value="<?php echo $b['booking_id']; ?>" <?php echo ($selected_booking_id == $b['booking_id']) ? 'selected' : ''; ?>>
                                            ຫ້ອງ <?php echo htmlspecialchars($b['room_number']); ?> (<?php echo htmlspecialchars($b['customer_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>ຊື່ລາຍການ (ເຊັ່ນ: ນ້ຳດື່ມ, ເບຍ, ອາຫານ)</label>
                                <input type="text" name="item_name" id="item_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ລາຍການ">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>ລາຄາ (ກີບ)</label>
                                        <input type="text" name="price" id="price" class="form-control number-format" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>ຈຳນວນ</label>
                                        <input type="number" name="qty" id="qty" class="form-control" value="1" min="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="add_service" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> ບັນທຶກລາຍການ (ສະສົມໄວ້)</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Section -->
            <div class="col-md-8">
                <div class="card card-info card-outline shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list"></i> ລາຍການທີ່ສັ່ງສຳລັບຫ້ອງທີ່ເລືອກ</h3>
                        <div class="ml-auto text-danger font-weight-bold" style="font-size: 1.2rem;">
                            ຍອດສະສົມທັງໝົດ: <?php echo number_format($total_accumulated); ?> ກີບ
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover text-center">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>ວັນເວລາ</th>
                                    <th>ຊື່ລາຍການ</th>
                                    <th>ລາຄາ</th>
                                    <th>ຈຳນວນ</th>
                                    <th>ລວມ</th>
                                    <th>ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($services) > 0): ?>
                                    <?php $i = 1; foreach ($services as $row): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td><?php echo number_format($row['price']); ?></td>
                                            <td><?php echo $row['qty']; ?></td>
                                            <td class="font-weight-bold text-success"><?php echo number_format($row['total_price']); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-danger btn-delete px-3" data-id="<?php echo $row['id']; ?>" data-booking="<?php echo $selected_booking_id; ?>"><i class="fas fa-trash"></i> ລຶບ</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-muted py-4">ຍັງບໍ່ມີລາຍການໃຊ້ຈ່າຍເພີ່ມເຕີມສຳລັບຫ້ອງນີ້.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
            $(this).val('');
        }
    });

    // Form validation
    $('#serviceForm').on('submit', function(e) {
        var name = $('#item_name').val().trim();
        var price = $('#price').val().trim();
        
        if (name === '' || price === '' || price === '0') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ຂໍ້ມູນບໍ່ຄົບຖ້ວນ',
                text: 'ກະລຸນາປ້ອນຊື່ລາຍການ ແລະ ລາຄາ!',
                confirmButtonText: 'ຕົກລົງ'
            });
            return false;
        }
    });

    // Delete Confirmation
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var booking = $(this).data('booking');
        Swal.fire({
            title: 'ຍືນຍັນການລົບ?',
            text: "ທ່ານຕ້ອງການລົບລາຍການນີ້ແທ້ບໍ່?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ລົບເລີຍ!',
            cancelButtonText: 'ຍົກເລີກ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?delete=" + id + "&booking_id=" + booking;
            }
        });
    });
});
</script>
</body>
</html>

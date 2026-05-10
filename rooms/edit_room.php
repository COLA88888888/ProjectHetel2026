<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: select_rooms.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: select_rooms.php");
    exit();
}

// Fetch room types for dropdown
$stmtTypes = $pdo->query("SELECT * FROM room_types ORDER BY id DESC");
$room_types = $stmtTypes->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $bed_type = $_POST['bed_type'];
    $price = str_replace(',', '', $_POST['price']); // Remove commas before saving
    $status = $_POST['status']; 
    $housekeeping_status = $_POST['housekeeping_status']; 

    $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type = ?, bed_type = ?, price = ?, status = ?, housekeeping_status = ? WHERE id = ?");
    if ($stmt->execute([$room_number, $room_type, $bed_type, $price, $status, $housekeeping_status, $id])) {
        $_SESSION['success'] = "ແກ້ໄຂຂໍ້ມູນຫ້ອງສຳເລັດ";
        header("Location: select_rooms.php");
        exit();
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການແກ້ໄຂ";
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແກ້ໄຂຂໍ້ມູນຫ້ອງ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit"></i> ແກ້ໄຂຂໍ້ມູນຫ້ອງ (<?php echo htmlspecialchars($room['room_number']); ?>)</h3>
                </div>
                <form action="" method="post" id="editRoomForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ເລກຫ້ອງ</label>
                            <input type="text" name="room_number" id="room_number" class="form-control" value="<?php echo htmlspecialchars($room['room_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label>ປະເພດຫ້ອງ</label>
                            <select name="room_type" id="room_type" class="form-control">
                                <?php foreach($room_types as $rt): ?>
                                    <option value="<?php echo htmlspecialchars($rt['room_type_name']); ?>" <?php echo ($rt['room_type_name'] == $room['room_type']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rt['room_type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ປະເພດຕຽງ</label>
                            <select name="bed_type" id="bed_type" class="form-control">
                                <option value="ຕຽງດ່ຽວ" <?php echo ($room['bed_type'] == 'ຕຽງດ່ຽວ') ? 'selected' : ''; ?>>ຕຽງດ່ຽວ (Single)</option>
                                <option value="ຕຽງຄູ່" <?php echo ($room['bed_type'] == 'ຕຽງຄູ່') ? 'selected' : ''; ?>>ຕຽງຄູ່ (Double)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ລາຄາຕໍ່ຄືນ (ກີບ)</label>
                            <input type="text" name="price" id="price" class="form-control number-format" value="<?php echo number_format((int)$room['price']); ?>">
                        </div>
                        <div class="form-group">
                            <label>ສະຖານະຫ້ອງ (ການຈອງ)</label>
                            <select name="status" id="status" class="form-control">
                                <option value="Available" <?php echo ($room['status'] == 'Available') ? 'selected' : ''; ?>>ຫວ່າງ (Available)</option>
                                <option value="Booked" <?php echo ($room['status'] == 'Booked') ? 'selected' : ''; ?>>ຖືກຈອງແລ້ວ (Booked)</option>
                                <option value="Occupied" <?php echo ($room['status'] == 'Occupied') ? 'selected' : ''; ?>>ມີແຂກພັກ (Occupied)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ສະຖານະຄວາມພ້ອມ (ແມ່ບ້ານ)</label>
                            <select name="housekeeping_status" id="housekeeping_status" class="form-control">
                                <option value="ພ້ອມໃຊ້" <?php echo ($room['housekeeping_status'] == 'ພ້ອມໃຊ້' || $room['housekeeping_status'] == 'Ready') ? 'selected' : ''; ?>>ຫ້ອງສະອາດ (ພ້ອມໃຊ້)</option>
                                <option value="Cleaning" <?php echo ($room['housekeeping_status'] == 'Cleaning') ? 'selected' : ''; ?>>ຫ້ອງກຳລັງທຳຄວາມສະອາດ (Cleaning)</option>
                                <option value="Maintenance" <?php echo ($room['housekeeping_status'] == 'Maintenance') ? 'selected' : ''; ?>>ຫ້ອງເສຍ (Maintenance)</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-save"></i> ບັນທຶກການແກ້ໄຂ</button>
                        <a href="select_rooms.php" class="btn btn-default"><i class="fas fa-arrow-left"></i> ກັບຄືນ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="../sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Number formatting with commas
    $('.number-format').on('input', function(e) {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== '') {
            $(this).val(parseInt(value, 10).toLocaleString('en-US'));
        } else {
            $(this).val('');
        }
    });

    $('#editRoomForm').on('submit', function(e) {
        var roomNumber = $('#room_number').val().trim();
        var roomType = $('#room_type').val();
        var bedType = $('#bed_type').val();
        var price = $('#price').val().trim();
        
        if (roomNumber === '' || roomType === '' || bedType === '' || price === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ແຈ້ງເຕືອນ',
                text: 'ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນທຸກຊ່ອງ!',
                confirmButtonText: 'ຕົກລົງ'
            });
            return false;
        }
    });
});
</script>

</body>
</html>

<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: form_room_types.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
$stmt->execute([$id]);
$room_type = $stmt->fetch();

if (!$room_type) {
    header("Location: form_room_types.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $room_type_name = $_POST['room_type_name'];
    $room_type_code = $_POST['room_type_code'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE room_types SET room_type_name = ?, room_type_code = ?, description = ? WHERE id = ?");
    if ($stmt->execute([$room_type_name, $room_type_code, $description, $id])) {
        $_SESSION['success'] = "ແກ້ໄຂຂໍ້ມູນສຳເລັດ";
        header("Location: form_room_types.php");
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
    <title>ແກ້ໄຂປະເພດຫ້ອງ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit"></i> ແກ້ໄຂປະເພດຫ້ອງ</h3>
                </div>
                <form action="" method="post" id="editRoomTypeForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ລະຫັດປະເພດຫ້ອງ</label>
                            <input type="text" name="room_type_code" id="room_type_code" class="form-control" value="<?php echo htmlspecialchars($room_type['room_type_code'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>ຊື່ປະເພດຫ້ອງ</label>
                            <input type="text" name="room_type_name" id="room_type_name" class="form-control" value="<?php echo htmlspecialchars($room_type['room_type_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>ລາຍລະອຽດ</label>
                            <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($room_type['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-save"></i> ແກ້ໄຂ</button>
                        <a href="form_room_types.php" class="btn btn-default"><i class="fas fa-arrow-left"></i> ກັບຄືນ</a>
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
    $('#editRoomTypeForm').on('submit', function(e) {
        var roomTypeName = $('#room_type_name').val().trim();
        var description = $('#description').val().trim();
        
        if (roomTypeName === '' && description === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ແຈ້ງເຕືອນ',
                text: 'ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ!',
                confirmButtonText: 'ຕົກລົງ'
            });
            return false;
        } else if (roomTypeName === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ແຈ້ງເຕືອນ',
                text: 'ກະລຸນາປ້ອນຊື່ປະເພດຫ້ອງ!',
                confirmButtonText: 'ຕົກລົງ'
            });
            return false;
        }
    });
});
</script>

</body>
</html>

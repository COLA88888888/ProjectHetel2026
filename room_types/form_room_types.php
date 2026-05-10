<?php
session_start();
require_once '../config/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $room_type_name = $_POST['room_type_name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO room_types (room_type_name, description) VALUES (?, ?)");
    if ($stmt->execute([$room_type_name, $description])) {
        $_SESSION['success'] = "ບັນທຶກຂໍ້ມູນສຳເລັດ";
        header("Location: form_room_types.php");
        exit();
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM room_types WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "ລົບຂໍ້ມູນສຳເລັດ";
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການລົບ";
    }
    header("Location: form_room_types.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM room_types ORDER BY id DESC");
$room_types = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການປະເພດຫ້ອງ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
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
    
    <?php if(isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ຜິດພາດ',
                    text: '<?php echo $_SESSION['error']; ?>',
                    confirmButtonText: 'ຕົກລົງ'
                });
            });
        </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="row">
        <!-- Form Section -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> ເພີ່ມປະເພດຫ້ອງ</h3>
                </div>
                <form action="" method="post" id="roomTypeForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ຊື່ປະເພດຫ້ອງ</label>
                            <input type="text" name="room_type_name" id="room_type_name" class="form-control" placeholder="ກະລຸນາປ້ອນປະເພດຫ້ອງ...">
                        </div>
                        <div class="form-group">
                            <label>ລາຍລະອຽດ</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="ລາຍລະອຽດ..."></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="save" class="btn btn-primary"><i class="fas fa-save"></i> ບັນທຶກ</button>
                        <button type="reset" class="btn btn-default"><i class="fas fa-times"></i> ຍົກເລີກ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="col-md-8">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> ລາຍການປະເພດຫ້ອງ</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="roomTypeTable" class="table table-bordered table-striped table-hover text-center">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>ຊື່ປະເພດຫ້ອງ</th>
                                <th>ລາຍລະອຽດ</th>
                                <th width="150" class="text-center">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($room_types) > 0): ?>
                                <?php $i = 1; foreach ($room_types as $row): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['room_type_name'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                                        <td class="text-center">
                                            <a href="edit_room_type.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> ແກ້ໄຂ</a>
                                            <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i> ລົບ</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<!-- SweetAlert2 -->
<script src="../sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#roomTypeTable').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
      "pageLength": 10,
      "language": {
          "search": "ຄົ້ນຫາ:",
          "info": "ສະແດງ _START_ ຫາ _END_ ຈາກທັງໝົດ _TOTAL_ ລາຍການ",
          "infoEmpty": "ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ",
          "zeroRecords": "ບໍ່ພົບຂໍ້ມູນທີ່ຄົ້ນຫາ",
          "paginate": {
              "first": "ໜ້າທຳອິດ",
              "last": "ໜ້າສຸດທ້າຍ",
              "next": "ຕໍ່ໄປ",
              "previous": "ກ່ອນໜ້າ"
          }
      }
    });

    $('#roomTypeForm').on('submit', function(e) {
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

    // Delete Confirmation
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: 'ຍືນຍັນການລົບ?',
            text: "ທ່ານຕ້ອງການລົບຂໍ້ມູນນີ້ແທ້ບໍ່? ຂໍ້ມູນທີ່ລົບແລ້ວບໍ່ສາມາດກູ້ຄືນໄດ້!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ລົບເລີຍ!',
            cancelButtonText: 'ຍົກເລີກ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?delete=" + id;
            }
        });
    });
});
</script>
</body>
</html>

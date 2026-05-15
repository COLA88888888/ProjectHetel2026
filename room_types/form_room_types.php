<?php
session_start();
require_once '../config/db.php';

// Language Selection Logic
$current_lang = $_SESSION['lang'] ?? 'la';
$lang_file = "../lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include "../lang/la.php";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $room_type_name_la = $_POST['room_type_name_la'];
    $room_type_name_en = $_POST['room_type_name_en'];
    $room_type_name_cn = $_POST['room_type_name_cn'];
    $room_type_code = $_POST['room_type_code'];
    $description_la = $_POST['description_la'];
    $description_en = $_POST['description_en'];
    $description_cn = $_POST['description_cn'];

    // Also update the original columns for backward compatibility
    $room_type_name = $room_type_name_la;
    $description = $description_la;

    $stmt = $pdo->prepare("INSERT INTO room_types (room_type_name, room_type_name_la, room_type_name_en, room_type_name_cn, room_type_code, description, description_la, description_en, description_cn) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$room_type_name, $room_type_name_la, $room_type_name_en, $room_type_name_cn, $room_type_code, $description, $description_la, $description_en, $description_cn])) {
        logActivity($pdo, "ເພີ່ມປະເພດຫ້ອງໃໝ່", "ຊື່: $room_type_name_la");
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
    
    // First, check if this room type is being used by any rooms
    $stmtCheck = $pdo->prepare("SELECT room_type_name FROM room_types WHERE id = ?");
    $stmtCheck->execute([$id]);
    $type = $stmtCheck->fetch();
    
    if ($type) {
        $typeName = $type['room_type_name'];
        $stmtRoom = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type = ?");
        $stmtRoom->execute([$typeName]);
        $count = $stmtRoom->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "ບໍ່ສາມາດລົບໄດ້! ເພາະມີຫ້ອງຈຳນວນ $count ຫ້ອງ ທີ່ກຳລັງໃຊ້ປະເພດນີ້ຢູ່.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM room_types WHERE id = ?");
            if ($stmt->execute([$id])) {
                logActivity($pdo, "ລົບປະເພດຫ້ອງ", "ປະເພດ: $typeName");
                $_SESSION['success'] = "ລົບຂໍ້ມູນສຳເລັດ";
            } else {
                $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການລົບ";
            }
        }
    }
    header("Location: form_room_types.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM room_types ORDER BY id DESC");
$room_types = $stmt->fetchAll();

$current_lang = $_SESSION['lang'] ?? 'la';
$name_col = "room_type_name_" . $current_lang;
$desc_col = "description_" . $current_lang;
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
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        .btn-warning { background: transparent !important; border: none !important; color: #ffc107 !important; font-size: 1.15rem; padding: 0 8px; box-shadow: none !important; }
        .btn-danger { background: transparent !important; border: none !important; color: #dc3545 !important; font-size: 1.15rem; padding: 0 8px; box-shadow: none !important; }
        .btn-warning:hover, .btn-danger:hover { opacity: 0.7; }
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
                            <label><?php echo $lang['room_type_code_label']; ?></label>
                            <input type="text" name="room_type_code" id="room_type_code" class="form-control" placeholder="Code...">
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang['room_type_label']; ?> (Lao) <span class="text-danger">*</span></label>
                            <input type="text" name="room_type_name_la" id="room_type_name_la" class="form-control" placeholder="ຊື່ພາສາລາວ..." required>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang['details']; ?> (Lao)</label>
                            <textarea name="description_la" id="description_la" class="form-control" rows="2" placeholder="ລາຍລະອຽດພາສາລາວ..."></textarea>
                        </div>

                        <!-- Advanced Multi-language Options -->
                        <!-- <div class="mt-3">
                            <a href="javascript:void(0)" class="text-primary small font-weight-bold" data-toggle="collapse" data-target="#advancedOptions">
                                <i class="fas fa-cog mr-1"></i> <?php echo $lang['advanced_options']; ?>
                            </a>
                        </div>

                        <div id="advancedOptions" class="collapse mt-3 border-top pt-3">
                            <div class="form-group">
                                <label>Room Type Name (English)</label>
                                <input type="text" name="room_type_name_en" class="form-control" placeholder="English name...">
                            </div>
                            <div class="form-group">
                                <label>客房类型名称 (Chinese)</label>
                                <input type="text" name="room_type_name_cn" class="form-control" placeholder="Chinese name...">
                            </div>
                            <div class="form-group">
                                <label>Description (English)</label>
                                <textarea name="description_en" class="form-control" rows="2" placeholder="English description..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>描述 (Chinese)</label>
                                <textarea name="description_cn" class="form-control" rows="2" placeholder="Chinese description..."></textarea>
                            </div>
                        </div> -->
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
                                <th>ລະຫັດ</th>
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
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($row['room_type_code'] ?? '-'); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($row[$name_col] ?: $row['room_type_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row[$desc_col] ?: $row['description']); ?></td>
                                        <td class="text-center">
                                            <a href="edit_room_type.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="ແກ້ໄຂ"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $row['id']; ?>" title="ລົບ"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນ</td>
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
// $(document).ready(function() {
//     // Initialize DataTable
//     $('#roomTypeTable').DataTable({
//       "paging": true,
//       "lengthChange": false,
//       "searching": true,
//       "ordering": true,
//       "info": true,
//       "autoWidth": false,
//       "responsive": true,
//       "pageLength": 10,
//       "language": {
//           "search": "ຄົ້ນຫາ:",
//           "info": "ສະແດງ _START_ ຫາ _END_ ຈາກທັງໝົດ _TOTAL_ ລາຍການ",
//           "infoEmpty": "ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ",
//           "zeroRecords": "ບໍ່ພົບຂໍ້ມູນທີ່ຄົ້ນຫາ",
//           "paginate": {
//               "first": "ໜ້າທຳອິດ",
//               "last": "ໜ້າສຸດທ້າຍ",
//               "next": "ຕໍ່ໄປ",
//               "previous": "ກ່ອນໜ້າ"
//           }
//       }
//     });

    $('#roomTypeForm').on('submit', function(e) {
        var roomTypeNameLa = $('#room_type_name_la').val().trim();
        
        if (roomTypeNameLa === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ແຈ້ງເຕືອນ',
                text: 'ກະລຸນາປ້ອນຊື່ປະເພດຫ້ອງ (ພາສາລາວ)!',
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

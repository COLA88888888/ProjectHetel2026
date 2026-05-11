<?php
session_start();
require_once '../config/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $bed_type = $_POST['bed_type'];
    $price = str_replace(',', '', $_POST['price']); // Remove commas before saving
    $status = 'Available'; // Default status
    $housekeeping_status = $_POST['housekeeping_status']; // Default 'ພ້ອມໃຊ້'
    
    // Convert Lao to system status if needed, or save directly. User specified:
    // ພ້ອມໃຊ້ (Clean), Maintenance (ຫ້ອງເສຍ), Cleaning (ຫ້ອງກຳລັງທຳຄວາມສະອາດ)
    
    $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, bed_type, price, status, housekeeping_status) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$room_number, $room_type, $bed_type, $price, $status, $housekeeping_status])) {
        $_SESSION['success'] = "ບັນທຶກຂໍ້ມູນຫ້ອງສຳເລັດ";
        header("Location: select_rooms.php");
        exit();
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "ລົບຂໍ້ມູນສຳເລັດ";
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການລົບ";
    }
    header("Location: select_rooms.php");
    exit();
}

// AJAX: Update housekeeping status
if (isset($_POST['update_housekeeping'])) {
    $id = (int)$_POST['room_id'];
    $hk_status = $_POST['hk_status'];
    $stmt = $pdo->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
    $ok = $stmt->execute([$hk_status, $id]);
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok]);
    exit();
}

$stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC");
$rooms = $stmt->fetchAll();

// Fetch room types for dropdown
$stmtTypes = $pdo->query("SELECT * FROM room_types ORDER BY id DESC");
$room_types = $stmtTypes->fetchAll();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍລະອຽດຫ້ອງ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        .badge-status { font-size: 14px; padding: 5px 10px; }
        .hk-select { border: 2px solid #ddd; border-radius: 6px; padding: 4px 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; outline: none; -webkit-appearance: none; -moz-appearance: none; appearance: auto; }
        .hk-select.hk-ready { border-color: #17a2b8; background: #e8f8fb; color: #117a8b; }
        .hk-select.hk-cleaning { border-color: #ffc107; background: #fff8e1; color: #856404; }
        .hk-select.hk-maintenance { border-color: #6c757d; background: #f0f0f0; color: #495057; }
        .hk-saving { opacity: 0.5; pointer-events: none; }
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
        <div class="col-md-3">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> ເພີ່ມຫ້ອງໃໝ່</h3>
                </div>
                <form action="" method="post" id="roomForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ເລກຫ້ອງ</label>
                            <input type="text" name="room_number" id="room_number" class="form-control" placeholder="ກະລຸນາປ້ອນເລກຫ້ອງ...">
                        </div>
                        <div class="form-group">
                            <label>ປະເພດຫ້ອງ</label>
                            <select name="room_type" id="room_type" class="form-control">
                                <option value="">-- ເລືອກປະເພດ --</option>
                                <?php foreach($room_types as $rt): ?>
                                    <option value="<?php echo htmlspecialchars($rt['room_type_name']); ?>"><?php echo htmlspecialchars($rt['room_type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ປະເພດຕຽງ</label>
                            <select name="bed_type" id="bed_type" class="form-control">
                                <option value="">-- ເລືອກຕຽງ --</option>
                                <option value="ຕຽງດ່ຽວ">ຕຽງດ່ຽວ</option>
                                <option value="ຕຽງຄູ່">ຕຽງຄູ່</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ລາຄາຕໍ່ຄືນ (ກີບ)</label>
                            <input type="text" name="price" id="price" class="form-control number-format" placeholder="ກະລຸນາປ້ອນລາຄາ...">
                        </div>
                        <div class="form-group">
                            <label>ສະຖານະຄວາມພ້ອມ</label>
                            <select name="housekeeping_status" id="housekeeping_status" class="form-control">
                                <option value="ພ້ອມໃຊ້">ຫ້ອງສະອາດ</option>
                                <option value="Cleaning">ຫ້ອງກຳລັງທຳຄວາມສະອາດ</option>
                                <option value="Maintenance">ຫ້ອງເສຍ</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="save" class="btn btn-primary btn-block"><i class="fas fa-save"></i> ບັນທຶກ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="col-md-9">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> ລາຍລະອຽດທຸກຫ້ອງ</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="roomTable" class="table table-bordered table-striped table-hover text-center">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>ເລກຫ້ອງ</th>
                                <th>ປະເພດຫ້ອງ</th>
                                <th>ປະເພດຕຽງ</th>
                                <th>ລາຄາ/ຄືນ</th>
                                <th>ສະຖານະ</th>
                                <th>ຄວາມພ້ອມ</th>
                                <th>ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rooms) > 0): ?>
                                <?php $i = 1; foreach ($rooms as $row): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['room_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['bed_type']); ?></td>
                                        <td><?php echo number_format($row['price']); ?> ກີບ</td>
                                        <td>
                                            <?php if($row['status'] == 'Available'): ?>
                                                <span class="badge badge-success badge-status"><i class="fas fa-check-circle"></i> ຫ້ອງຫວ່າງ</span>
                                            <?php elseif($row['status'] == 'Booked'): ?>
                                                <span class="badge badge-warning text-white badge-status"><i class="fas fa-calendar-check"></i> ຈອງລ່ວງໜ້າ</span>
                                            <?php elseif($row['status'] == 'Occupied'): ?>
                                                <span class="badge badge-danger badge-status"><i class="fas fa-user-check"></i> ເຂົ້າພັກແລ້ວ</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary badge-status"><?php echo htmlspecialchars($row['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $hk = $row['housekeeping_status'];
                                                $hk_class = 'hk-ready';
                                                if ($hk == 'Cleaning') $hk_class = 'hk-cleaning';
                                                elseif ($hk == 'Maintenance') $hk_class = 'hk-maintenance';
                                            ?>
                                            <select class="hk-select <?php echo $hk_class; ?>" data-room-id="<?php echo $row['id']; ?>">
                                                <option value="ພ້ອມໃຊ້" <?php echo ($hk == 'ພ້ອມໃຊ້' || $hk == 'Ready') ? 'selected' : ''; ?>>ພ້ອມໃຊ້</option>
                                                <option value="Cleaning" <?php echo ($hk == 'Cleaning') ? 'selected' : ''; ?>>ກຳລັງອະນາໄມ</option>
                                                <option value="Maintenance" <?php echo ($hk == 'Maintenance') ? 'selected' : ''; ?>>ຫ້ອງເສຍ</option>
                                            </select>
                                        </td>
                                        <td>
                                            <a href="edit_room.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> ແກ້ໄຂ</a>
                                            <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i> ລົບ</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-muted">ບໍ່ມີຂໍ້ມູນຫ້ອງ</td>
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
    $('#roomTable').DataTable({
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

    // Number formatting with commas
    $('.number-format').on('input', function(e) {
        // Remove non-numeric characters (except for maybe a period if decimal is needed, but we assume integers for Kip)
        var value = $(this).val().replace(/[^0-9]/g, '');
        // Format with commas
        if (value !== '') {
            $(this).val(parseInt(value, 10).toLocaleString('en-US'));
        } else {
            $(this).val('');
        }
    });

    $('#roomForm').on('submit', function(e) {
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

    // Delete Confirmation
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: 'ຍືນຍັນການລົບ?',
            text: "ທ່ານຕ້ອງການລົບຫ້ອງນີ້ແທ້ບໍ່?",
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

    // Housekeeping status update via AJAX
    $(document).on('change', '.hk-select', function() {
        var $sel = $(this);
        var roomId = $sel.data('room-id');
        var newStatus = $sel.val();
        
        $sel.addClass('hk-saving');
        
        $.post('select_rooms.php', {
            update_housekeeping: 1,
            room_id: roomId,
            hk_status: newStatus
        }, function(res) {
            $sel.removeClass('hk-saving');
            // Update color class
            $sel.removeClass('hk-ready hk-cleaning hk-maintenance');
            if (newStatus === 'ພ້ອມໃຊ້') $sel.addClass('hk-ready');
            else if (newStatus === 'Cleaning') $sel.addClass('hk-cleaning');
            else $sel.addClass('hk-maintenance');
            
            // Show toast
            Swal.fire({
                icon: 'success',
                title: 'ອັບເດດສຳເລັດ!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        }).fail(function() {
            $sel.removeClass('hk-saving');
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດອັບເດດໄດ້' });
        });
    });
});
</script>
</body>
</html>

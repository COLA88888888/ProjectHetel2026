<?php
session_start();
require_once 'config/db.php';

// Handle Add Unit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_unit'])) {
    $unit_name_la = trim($_POST['unit_name_la']);
    $unit_name_en = trim($_POST['unit_name_en']);
    $unit_name_cn = trim($_POST['unit_name_cn']);
    
    if (!empty($unit_name_la)) {
        $stmt = $pdo->prepare("INSERT INTO product_units (unit_name, unit_name_la, unit_name_en, unit_name_cn) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$unit_name_la, $unit_name_la, $unit_name_en, $unit_name_cn])) {
            $_SESSION['success'] = "ເພີ່ມຫົວໜ່ວຍສິນຄ້າສຳເລັດແລ້ວ!";
        } else {
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ ຫຼື ຂໍ້ມູນຊ້ຳກັນ!";
        }
    }
    header("Location: form_product_units.php");
    exit();
}

// Handle Edit Unit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_unit'])) {
    $id = (int)$_POST['id'];
    $name_la = trim($_POST['unit_name_la']);
    $name_en = trim($_POST['unit_name_en']);
    $name_cn = trim($_POST['unit_name_cn']);
    $old_name = trim($_POST['old_name']);
    
    if (!empty($name_la)) {
        $stmt = $pdo->prepare("UPDATE product_units SET unit_name = ?, unit_name_la = ?, unit_name_en = ?, unit_name_cn = ? WHERE id = ?");
        if ($stmt->execute([$name_la, $name_la, $name_en, $name_cn, $id])) {
            // Update all products that use this unit (matching by base name)
            $updateProducts = $pdo->prepare("UPDATE products SET unit = ? WHERE unit = ?");
            $updateProducts->execute([$name_la, $old_name]);
            
            $_SESSION['success'] = "ແກ້ໄຂຫົວໜ່ວຍສິນຄ້າສຳເລັດແລ້ວ!";
        } else {
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ!";
        }
    }
    header("Location: form_product_units.php");
    exit();
}

// Handle Delete Unit
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM product_units WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "ລຶບຫົວໜ່ວຍສຳເລັດແລ້ວ!";
    }
    header("Location: form_product_units.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM product_units ORDER BY id DESC");
$units = $stmt->fetchAll();

$current_lang = $_SESSION['lang'] ?? 'la';
$unit_name_col = "unit_name_" . $current_lang;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ຈັດການຫົວໜ່ວຍສິນຄ້າ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        .card { border-radius: 15px; overflow: hidden; }
        .card-header { border-bottom: 0; }
        .btn-edit { background: transparent !important; border: none !important; color: #ffc107 !important; font-size: 1.1rem; padding: 0 5px; }
        .btn-delete { background: transparent !important; border: none !important; color: #dc3545 !important; font-size: 1.1rem; padding: 0 5px; }
        .btn-edit:hover, .btn-delete:hover { opacity: 0.7; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            h2 { font-size: 1.25rem !important; }
            .card-title { font-size: 1rem !important; }
            .table { font-size: 0.85rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <?php if(isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'success', title: 'ສຳເລັດ', text: '<?php echo $_SESSION['success']; ?>', showConfirmButton: false, timer: 1500 });
            });
        </script>
    <?php unset($_SESSION['success']); endif; ?>

    <div class="row mb-3 align-items-center">
        <div class="col-sm-6">
            <h2><i class="fas fa-balance-scale"></i> ຈັດການຫົວໜ່ວຍສິນຄ້າ</h2>
        </div>
        <div class="col-sm-6 text-right">
            <a href="stock.php" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left"></i> ກັບຄືນໜ້າສະຕັອກ</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-info card-outline shadow-sm">
                <div class="card-header"><h3 class="card-title">ເພີ່ມຫົວໜ່ວຍໃໝ່</h3></div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ຊື່ຫົວໜ່ວຍ (Lao)</label>
                            <input type="text" name="unit_name_la" class="form-control" placeholder="ຕົວຢ່າງ: ປ໋ອງ, ແກ້ວ..." required>
                        </div>
                        <div class="form-group">
                            <label>Unit Name (English)</label>
                            <input type="text" name="unit_name_en" class="form-control" placeholder="e.g. Can, Bottle...">
                        </div>
                        <div class="form-group">
                            <label>单位名称 (Chinese)</label>
                            <input type="text" name="unit_name_cn" class="form-control" placeholder="例如：罐, 瓶...">
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0">
                        <button type="submit" name="add_unit" class="btn btn-info btn-block"><i class="fas fa-save"></i> ບັນທຶກຫົວໜ່ວຍ</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header"><h3 class="card-title">ລາຍຊື່ຫົວໜ່ວຍທັງໝົດ</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover text-center mb-0">
                        <thead class="bg-light text-muted uppercase">
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th class="text-left">ຊື່ຫົວໜ່ວຍ</th>
                                <th style="width: 200px;">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($units) > 0): ?>
                                <?php foreach($units as $index => $u): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="text-left font-weight-bold text-dark"><?php echo htmlspecialchars($u[$unit_name_col] ?: $u['unit_name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning text-white btn-edit" 
                                            data-id="<?php echo $u['id']; ?>" 
                                            data-name-la="<?php echo htmlspecialchars($u['unit_name_la'] ?: $u['unit_name']); ?>"
                                            data-name-en="<?php echo htmlspecialchars($u['unit_name_en'] ?? ''); ?>"
                                            data-name-cn="<?php echo htmlspecialchars($u['unit_name_cn'] ?? ''); ?>"
                                            title="ແກ້ໄຂ">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $u['id']; ?>" title="ລຶບ">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">ບໍ່ມີຂໍ້ມູນຫົວໜ່ວຍ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="fas fa-edit"></i> ແກ້ໄຂຫົວໜ່ວຍ</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="post">
          <div class="modal-body">
              <input type="hidden" name="id" id="edit_id">
              <input type="hidden" name="old_name" id="edit_old_name">
              <div class="form-group">
                  <label>ຊື່ຫົວໜ່ວຍ (Lao)</label>
                  <input type="text" name="unit_name_la" id="edit_name_la" class="form-control" required>
              </div>
              <div class="form-group">
                  <label>Unit Name (English)</label>
                  <input type="text" name="unit_name_en" id="edit_name_en" class="form-control">
              </div>
              <div class="form-group">
                  <label>单位名称 (Chinese)</label>
                  <input type="text" name="unit_name_cn" id="edit_name_cn" class="form-control">
              </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-light" data-dismiss="modal">ຍົກເລີກ</button>
            <button type="submit" name="edit_unit" class="btn btn-warning text-white shadow-sm">ບັນທຶກການແກ້ໄຂ</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>
<script>
$('.btn-edit').on('click', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name_la').val($(this).data('name-la'));
    $('#edit_name_en').val($(this).data('name-en'));
    $('#edit_name_cn').val($(this).data('name-cn'));
    $('#edit_old_name').val($(this).data('name-la'));
    
    $('#editUnitModal').modal('show');
});

$('.btn-delete').on('click', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    Swal.fire({
        title: 'ຍືນຍັນການລຶບ?',
        text: 'ຫາກລຶບຫົວໜ່ວຍນີ້, ສິນຄ້າທີ່ກ່ຽວຂ້ອງອາດຈະບໍ່ສະແດງຫົວໜ່ວຍ.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ລຶບເລີຍ!',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete=" + id;
        }
    });
});
</script>
</body>
</html>

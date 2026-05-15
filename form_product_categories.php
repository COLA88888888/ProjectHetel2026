<?php
session_start();
require_once 'config/db.php';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name_la = trim($_POST['name_la']);
    $name_en = trim($_POST['name_en']);
    $name_cn = trim($_POST['name_cn']);
    $category_code = trim($_POST['category_code']);
    
    // Original column for compatibility
    $name = $name_la;

    if (!empty($name_la)) {
        $stmt = $pdo->prepare("INSERT INTO product_categories (name, name_la, name_en, name_cn, category_code) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $name_la, $name_en, $name_cn, $category_code])) {
            $_SESSION['success'] = "ເພີ່ມປະເພດສິນຄ້າສຳເລັດແລ້ວ!";
        } else {
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ!";
        }
    }
    header("Location: form_product_categories.php");
    exit();
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name_la = trim($_POST['name_la']);
    $name_en = trim($_POST['name_en']);
    $name_cn = trim($_POST['name_cn']);
    $old_name = trim($_POST['old_name']);
    $category_code = trim($_POST['category_code']);
    
    // Original column for compatibility
    $name = $name_la;
    
    if (!empty($name_la)) {
        $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, name_la = ?, name_en = ?, name_cn = ?, category_code = ? WHERE id = ?");
        if ($stmt->execute([$name, $name_la, $name_en, $name_cn, $category_code, $id])) {
            // Update all products that use this category only if name changed
            if ($name !== $old_name) {
                $updateProducts = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
                $updateProducts->execute([$name, $old_name]);
            }
            $_SESSION['success'] = "ແກ້ໄຂປະເພດສິນຄ້າສຳເລັດແລ້ວ!";
        } else {
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ!";
        }
    }
    header("Location: form_product_categories.php");
    exit();
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM product_categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "ລຶບປະເພດສຳເລັດແລ້ວ!";
    }
    header("Location: form_product_categories.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM product_categories ORDER BY id DESC");
$categories = $stmt->fetchAll();

$current_lang = $_SESSION['lang'] ?? 'la';
$name_col = "name_" . $current_lang;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ຈັດການປະເພດສິນຄ້າ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        .btn-edit { background: transparent !important; border: none !important; color: #ffc107 !important; font-size: 1.1rem; padding: 0 5px; }
        .btn-delete { background: transparent !important; border: none !important; color: #dc3545 !important; font-size: 1.1rem; padding: 0 5px; }
        .btn-edit:hover, .btn-delete:hover { opacity: 0.7; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            h2 { font-size: 1.25rem !important; }
            .card-title { font-size: 1rem !important; }
            .table { font-size: 0.85rem; }
            .badge { font-size: 0.75rem; }
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

    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-tags"></i> ຈັດການປະເພດສິນຄ້າ</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header"><h3 class="card-title">ເພີ່ມປະເພດໃໝ່</h3></div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ລະຫັດປະເພດ</label>
                            <input type="text" name="category_code" class="form-control" placeholder="ກະລຸນາປ້ອນລະຫັດປະເພດ...">
                        </div>
                        <div class="form-group">
                            <label>ຊື່ປະເພດສິນຄ້າ (Lao)</label>
                            <input type="text" name="name_la" class="form-control" placeholder="ຊື່ພາສາລາວ..." required>
                        </div>
                        <div class="form-group">
                            <label>Category Name (English)</label>
                            <input type="text" name="name_en" class="form-control" placeholder="English name...">
                        </div>
                        <div class="form-group">
                            <label>类别名称 (Chinese)</label>
                            <input type="text" name="name_cn" class="form-control" placeholder="Chinese name...">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="add_category" class="btn btn-primary btn-block"><i class="fas fa-save"></i> ບັນທຶກ</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header"><h3 class="card-title">ລາຍຊື່ປະເພດສິນຄ້າທັງໝົດ</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover text-center">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ລະຫັດ</th>
                                <th class="text-left">ຊື່ປະເພດ</th>
                                <th>ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach($categories as $index => $c): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><span class="badge badge-secondary"><?php echo htmlspecialchars($c['category_code'] ?? '-'); ?></span></td>
                                    <td class="text-left font-weight-bold text-primary"><?php echo htmlspecialchars($c[$name_col] ?: $c['name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning text-white btn-edit" 
                                            data-id="<?php echo $c['id']; ?>" 
                                            data-name-la="<?php echo htmlspecialchars($c['name_la'] ?: $c['name']); ?>"
                                            data-name-en="<?php echo htmlspecialchars($c['name_en'] ?? ''); ?>"
                                            data-name-cn="<?php echo htmlspecialchars($c['name_cn'] ?? ''); ?>"
                                            data-code="<?php echo htmlspecialchars($c['category_code'] ?? ''); ?>"
                                            title="ແກ້ໄຂ">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $c['id']; ?>" title="ລຶບ"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="fas fa-edit"></i> ແກ້ໄຂ</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="post">
          <div class="modal-body">
              <input type="hidden" name="id" id="edit_id">
              <input type="hidden" name="old_name" id="edit_old_name">
              <div class="form-group">
                  <label>ລະຫັດປະເພດ</label>
                  <input type="text" name="category_code" id="edit_code" class="form-control">
              </div>
              <div class="form-group">
                  <label>ຊື່ປະເພດສິນຄ້າ (Lao)</label>
                  <input type="text" name="name_la" id="edit_name_la" class="form-control" required>
              </div>
              <div class="form-group">
                  <label>Category Name (English)</label>
                  <input type="text" name="name_en" id="edit_name_en" class="form-control">
              </div>
              <div class="form-group">
                  <label>类别名称 (Chinese)</label>
                  <input type="text" name="name_cn" id="edit_name_cn" class="form-control">
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ຍົກເລີກ</button>
            <button type="submit" name="edit_category" class="btn btn-warning text-white">ບັນທຶກການແກ້ໄຂ</button>
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
    var id = $(this).data('id');
    var nameLa = $(this).data('name-la');
    var nameEn = $(this).data('name-en');
    var nameCn = $(this).data('name-cn');
    var code = $(this).data('code');
    
    $('#edit_id').val(id);
    $('#edit_name_la').val(nameLa);
    $('#edit_name_en').val(nameEn);
    $('#edit_name_cn').val(nameCn);
    $('#edit_old_name').val(nameLa);
    $('#edit_code').val(code);
    
    $('#editCategoryModal').modal('show');
});

$('.btn-delete').on('click', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    Swal.fire({
        title: 'ຍືນຍັນການລຶບ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ລຶບເລີຍ!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete=" + id;
        }
    });
});
</script>
</body>
</html>

<?php
session_start();
require_once 'config/db.php';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $category_code = trim($_POST['category_code']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO product_categories (name, category_code) VALUES (?, ?)");
        if ($stmt->execute([$name, $category_code])) {
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
    $new_name = trim($_POST['name']);
    $old_name = trim($_POST['old_name']);
    
    if (!empty($new_name)) {
        $category_code = trim($_POST['category_code']);
        $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, category_code = ? WHERE id = ?");
        if ($stmt->execute([$new_name, $category_code, $id])) {
            // Update all products that use this category only if name changed
            if ($new_name !== $old_name) {
                $updateProducts = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
                $updateProducts->execute([$new_name, $old_name]);
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
                            <label>ຊື່ປະເພດສິນຄ້າ</label>
                            <input type="text" name="name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ..." required>
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
                                    <td class="text-left font-weight-bold text-primary"><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning text-white btn-edit" 
                                            data-id="<?php echo $c['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                            data-code="<?php echo htmlspecialchars($c['category_code'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> ແກ້ໄຂ
                                        </button>
                                        <a href="#" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $c['id']; ?>"><i class="fas fa-trash-alt"></i> ລຶບ</a>
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
                  <label>ຊື່ປະເພດສິນຄ້າ</label>
                  <input type="text" name="name" id="edit_name" class="form-control" required>
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
    var name = $(this).data('name');
    var code = $(this).data('code');
    
    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#edit_old_name').val(name);
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

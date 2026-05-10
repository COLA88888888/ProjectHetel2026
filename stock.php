<?php
session_start();
require_once 'config/db.php';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $prod_name = trim($_POST['prod_name']);
    $category = $_POST['category'];
    $qty = (int)$_POST['qty'];
    $bprice = (float)str_replace(',', '', $_POST['bprice']);
    $sprice = (float)str_replace(',', '', $_POST['sprice']);
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newname = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], 'assets/img/products/' . $newname)) {
                $image = $newname;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (prod_name, category, image, qty, bprice, sprice) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$prod_name, $category, $image, $qty, $bprice, $sprice])) {
        // Record Expense
        $expense_amount = $qty * $bprice;
        if ($expense_amount > 0) {
            $stmtExp = $pdo->prepare("INSERT INTO expenses (expense_type, description, amount, expense_date) VALUES ('Stock', ?, ?, CURDATE())");
            $stmtExp->execute(["ຊື້ສິນຄ້າໃໝ່: " . $prod_name, $expense_amount]);
        }
        
        $_SESSION['success'] = "ເພີ່ມສິນຄ້າສຳເລັດແລ້ວ!";
        header("Location: stock.php");
        exit();
    } else {
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດໃນການເພີ່ມຂໍ້ມູນ!";
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $prod_id = (int)$_POST['prod_id'];
    $prod_name = trim($_POST['prod_name']);
    $category = $_POST['category'];
    $bprice = (float)str_replace(',', '', $_POST['bprice']);
    $sprice = (float)str_replace(',', '', $_POST['sprice']);
    
    // Check if new image is uploaded
    $image_query = "";
    $params = [$prod_name, $category, $bprice, $sprice];
    
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['edit_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newname = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'], 'assets/img/products/' . $newname)) {
                
                // Get old image to delete
                $stmtOld = $pdo->prepare("SELECT image FROM products WHERE prod_id = ?");
                $stmtOld->execute([$prod_id]);
                $oldProd = $stmtOld->fetch();
                if ($oldProd && !empty($oldProd['image'])) {
                    $oldPath = 'assets/img/products/' . $oldProd['image'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $image_query = ", image = ?";
                $params[] = $newname;
            }
        }
    }
    
    $params[] = $prod_id;
    $stmt = $pdo->prepare("UPDATE products SET prod_name = ?, category = ?, bprice = ?, sprice = ? $image_query WHERE prod_id = ?");
    if ($stmt->execute($params)) {
        $_SESSION['success'] = "ແກ້ໄຂສິນຄ້າສຳເລັດແລ້ວ!";
    } else {
        $_SESSION['error'] = "ບໍ່ສາມາດແກ້ໄຂໄດ້!";
    }
    header("Location: stock.php");
    exit();
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete image file if exists
    $stmtImg = $pdo->prepare("SELECT image FROM products WHERE prod_id = ?");
    $stmtImg->execute([$id]);
    $prod = $stmtImg->fetch();
    if ($prod && !empty($prod['image'])) {
        $imgPath = 'assets/img/products/' . $prod['image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE prod_id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "ລຶບສິນຄ້າສຳເລັດແລ້ວ!";
    } else {
        $_SESSION['error'] = "ບໍ່ສາມາດລຶບໄດ້!";
    }
    header("Location: stock.php");
    exit();
}

// Handle Add Stock (Restock)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restock'])) {
    $prod_id = (int)$_POST['prod_id'];
    $add_qty = (int)$_POST['add_qty'];
    
    $stmt = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE prod_id = ?");
    if ($stmt->execute([$add_qty, $prod_id])) {
        
        // Record Expense for Restock
        $stmtProd = $pdo->prepare("SELECT prod_name, bprice FROM products WHERE prod_id = ?");
        $stmtProd->execute([$prod_id]);
        $prod = $stmtProd->fetch();
        if ($prod) {
            $expense_amount = $add_qty * $prod['bprice'];
            if ($expense_amount > 0) {
                $stmtExp = $pdo->prepare("INSERT INTO expenses (expense_type, description, amount, expense_date) VALUES ('Stock', ?, ?, CURDATE())");
                $stmtExp->execute(["ເຕີມສະຕັອກ: " . $prod['prod_name'], $expense_amount]);
            }
        }

        $_SESSION['success'] = "ເພີ່ມຈຳນວນເຂົ້າສະຕັອກສຳເລັດ!";
        header("Location: stock.php");
        exit();
    }
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY prod_id DESC");
$products = $stmt->fetchAll();

// Fetch all categories
$stmtCat = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

// Low stock report
$stmtLow = $pdo->query("SELECT COUNT(*) as low_stock_count FROM products WHERE qty <= 10");
$low_stock_count = $stmtLow->fetch()['low_stock_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສະຕັອກສິນຄ້າ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            h2 { font-size: 1.2rem; }
            .card-title { font-size: 1rem; }
            .alert { font-size: 0.85rem; padding: 0.5rem 0.75rem !important; }
            .table th, .table td { padding: 0.6rem 0.4rem !important; font-size: 0.8rem !important; }
            .btn-group-sm > .btn { padding: 0.25rem 0.4rem; font-size: 0.7rem; }
            .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { font-size: 0.75rem; text-align: center !important; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { text-align: left !important; margin-bottom: 10px; }
            .card-body { padding: 0.75rem; }
        }
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
        <div class="col-sm-6 col-12">
            <h2 class="mb-2"><i class="fas fa-boxes"></i> ຈັດການສະຕັອກສິນຄ້າ</h2>
        </div>
        <div class="col-sm-6 col-12 text-md-right">
            <?php if($low_stock_count > 0): ?>
                <div class="alert alert-danger d-inline-block py-2 px-3 mb-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle"></i> ມີສິນຄ້າໃກ້ໝົດສະຕັອກ <strong><?php echo $low_stock_count; ?></strong> ລາຍການ!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Add Product Form -->
        <div class="col-md-4 col-12 mb-4">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> ເພີ່ມສິນຄ້າໃໝ່</h3>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="form-group text-center">
                            <label>ຮູບພາບສິນຄ້າ</label>
                            <input type="file" name="image" id="image" class="form-control-file border p-2" accept="image/*" onchange="previewImage(this)">
                            <img id="preview" src="" alt="Preview" style="max-height: 100px; display: none; margin-top: 10px; border-radius: 4px;" class="shadow-sm">
                        </div>
                        <div class="form-group">
                            <label>ຊື່ສິນຄ້າ</label>
                            <input type="text" name="prod_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ສິນຄ້າ..." required>
                        </div>
                        <div class="form-group">
                            <label>ປະເພດສິນຄ້າ</label>
                            <select name="category" class="form-control" required>
                                <option value="">-- ເລືອກປະເພດ --</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ຈຳນວນຮັບເຂົ້າ</label>
                            <input type="number" name="qty" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>ຕົ້ນທຶນ</label>
                                    <input type="text" name="bprice" class="form-control number-format" placeholder="0">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>ລາຄາຂາຍ</label>
                                    <input type="text" name="sprice" class="form-control number-format" placeholder="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="add_product" class="btn btn-primary btn-block"><i class="fas fa-save"></i> ບັນທຶກ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products List -->
        <div class="col-md-8 col-12">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-list"></i> ລາຍງານສະຕັອກທັງໝົດ</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="stockTable" class="table table-bordered table-hover text-center w-100">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-left">ຊື່ສິນຄ້າ</th>
                                    <th>ປະເພດ</th>
                                    <th>ລາຄາຂາຍ</th>
                                    <th>ຍັງເຫຼືອ</th>
                                    <th>ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $index => $row): ?>
                                    <?php 
                                        $profit = $row['sprice'] - $row['bprice']; 
                                        $badgeClass = ($row['qty'] <= 10) ? 'badge-danger' : 'badge-success';
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td class="text-left">
                                            <div class="d-flex align-items-center">
                                                <?php if($row['image']): ?>
                                                    <img src="assets/img/products/<?php echo htmlspecialchars($row['image']); ?>" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;" class="mr-2 border shadow-sm">
                                                <?php endif; ?>
                                                <span class="font-weight-bold"><?php echo htmlspecialchars($row['prod_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($row['category'] ?? 'ອື່ນໆ'); ?></span></td>
                                        <td class="text-primary font-weight-bold"><?php echo number_format($row['sprice']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badgeClass; ?> p-2"><?php echo $row['qty']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-warning text-white btn-edit" 
                                                    data-id="<?php echo $row['prod_id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($row['prod_name']); ?>" 
                                                    data-cat="<?php echo htmlspecialchars($row['category']); ?>" 
                                                    data-bprice="<?php echo $row['bprice']; ?>" 
                                                    data-sprice="<?php echo $row['sprice']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-info btn-restock" data-id="<?php echo $row['prod_id']; ?>" data-name="<?php echo htmlspecialchars($row['prod_name']); ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <a href="#" class="btn btn-danger btn-delete" data-id="<?php echo $row['prod_id']; ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restock Modal -->
<div class="modal fade" id="restockModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">ເຕີມສິນຄ້າເຂົ້າສະຕັອກ</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="post">
          <div class="modal-body">
              <input type="hidden" name="prod_id" id="restock_prod_id">
              <p>ສິນຄ້າ: <strong id="restock_prod_name" class="text-primary"></strong></p>
              <div class="form-group">
                  <label>ຈຳນວນທີ່ຕ້ອງການເຕີມ</label>
                  <input type="number" name="add_qty" class="form-control" value="1" min="1" required>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ຍົກເລີກ</button>
            <button type="submit" name="restock" class="btn btn-info">ບັນທຶກການເຕີມ</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="fas fa-edit"></i> ແກ້ໄຂສິນຄ້າ</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="post" enctype="multipart/form-data">
          <div class="modal-body">
              <input type="hidden" name="prod_id" id="edit_prod_id">
              <div class="form-group text-center">
                  <label>ປ່ຽນຮູບພາບໃໝ່ (ຖ້າຕ້ອງການ)</label>
                  <input type="file" name="edit_image" id="edit_image" class="form-control-file border p-2" accept="image/*" onchange="previewEditImage(this)">
                  <img id="edit_preview" src="" style="max-height: 120px; display: none; margin-top: 10px; border-radius: 5px;" class="shadow-sm">
              </div>
              <div class="form-group">
                  <label>ຊື່ສິນຄ້າ</label>
                  <input type="text" name="prod_name" id="edit_prod_name" class="form-control" required>
              </div>
              <div class="form-group">
                  <label>ປະເພດສິນຄ້າ</label>
                  <select name="category" id="edit_category" class="form-control" required>
                      <option value="">-- ເລືອກປະເພດ --</option>
                      <?php foreach($categories as $cat): ?>
                          <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                      <?php endforeach; ?>
                  </select>
              </div>
              <div class="row">
                  <div class="col-6">
                      <div class="form-group">
                          <label>ຕົ້ນທຶນ</label>
                          <input type="text" name="bprice" id="edit_bprice" class="form-control number-format">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-group">
                          <label>ລາຄາຂາຍ</label>
                          <input type="text" name="sprice" id="edit_sprice" class="form-control number-format" required>
                      </div>
                  </div>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ຍົກເລີກ</button>
            <button type="submit" name="edit_product" class="btn btn-warning text-white">ບັນທຶກການແກ້ໄຂ</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    $('#stockTable').DataTable({
        "language": {
            "sLengthMenu":   "ສະແດງ _MENU_ ລາຍການ",
            "sZeroRecords":  "ບໍ່ມີຂໍ້ມູນ",
            "sInfo":         "ສະແດງ _START_ ຫາ _END_ ຈາກ _TOTAL_ ລາຍການ",
            "sSearch":       "ຄົ້ນຫາ:",
            "oPaginate": { "sPrevious": "ກ່ອນໜ້າ", "sNext": "ຖັດໄປ" }
        }
    });

    $('.number-format').on('input', function(e) {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== '') {
            $(this).val(parseInt(value, 10).toLocaleString('en-US'));
        } else {
            $(this).val('');
        }
    });

    $('.btn-restock').on('click', function() {
        $('#restock_prod_id').val($(this).data('id'));
        $('#restock_prod_name').text($(this).data('name'));
        $('#restockModal').modal('show');
    });

    $('.btn-edit').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var cat = $(this).data('cat');
        var bprice = parseInt($(this).data('bprice')).toLocaleString('en-US');
        var sprice = parseInt($(this).data('sprice')).toLocaleString('en-US');
        
        $('#edit_prod_id').val(id);
        $('#edit_prod_name').val(name);
        
        // Handle Category Selection Safely
        if ($("#edit_category option[value='" + cat + "']").length > 0) {
            $('#edit_category').val(cat);
        } else if (cat !== '') {
            $('#edit_category').append('<option value="'+cat+'">'+cat+'</option>');
            $('#edit_category').val(cat);
        } else {
            $('#edit_category').val('');
        }
        
        $('#edit_bprice').val(bprice !== 'NaN' ? bprice : '0');
        $('#edit_sprice').val(sprice !== 'NaN' ? sprice : '0');
        $('#edit_preview').hide();
        
        $('#editProductModal').modal('show');
    });

    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: 'ຍືນຍັນການລຶບ?',
            text: "ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບສິນຄ້ານີ້ອອກຈາກສະຕັອກ?",
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
});

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#preview').attr('src', e.target.result).show();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewEditImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#edit_preview').attr('src', e.target.result).show();
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>

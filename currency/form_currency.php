<?php
session_start();
require_once '../config/db.php';

// Add new currency
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_currency'])) {
    $code = trim($_POST['currency_code']);
    $name = trim($_POST['currency_name']);
    $rate = (float)str_replace(',', '', $_POST['exchange_rate']);
    $symbol = trim($_POST['symbol']);

    if (!empty($code) && !empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO currency (currency_code, currency_name, exchange_rate, symbol) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$code, $name, $rate, $symbol])) {
            $_SESSION['success'] = "ເພີ່ມສະກຸນເງິນສຳເລັດແລ້ວ!";
        } else {
            $_SESSION['error'] = "ບໍ່ສາມາດເພີ່ມໄດ້!";
        }
    }
    header("Location: form_currency.php");
    exit();
}

// Edit currency
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_currency'])) {
    $id = (int)$_POST['id'];
    $code = trim($_POST['currency_code']);
    $name = trim($_POST['currency_name']);
    $rate = (float)str_replace(',', '', $_POST['exchange_rate']);
    $symbol = trim($_POST['symbol']);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE currency SET currency_code = ?, currency_name = ?, exchange_rate = ?, symbol = ? WHERE id = ?");
        if ($stmt->execute([$code, $name, $rate, $symbol, $id])) {
            $_SESSION['success'] = "ແກ້ໄຂຂໍ້ມູນສຳເລັດແລ້ວ!";
        }
    }
    header("Location: form_currency.php");
    exit();
}

// Delete currency
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Prevent deleting default currency (LAK)
    $stmtCheck = $pdo->prepare("SELECT is_default FROM currency WHERE id = ?");
    $stmtCheck->execute([$id]);
    $curr = $stmtCheck->fetch();

    if ($curr && $curr['is_default'] == 1) {
        $_SESSION['error'] = "ບໍ່ສາມາດລຶບສະກຸນເງິນຫຼັກໄດ້!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM currency WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "ລຶບສະກຸນເງິນສຳເລັດ!";
        }
    }
    header("Location: form_currency.php");
    exit();
}

// Fetch all currencies
$stmt = $pdo->query("SELECT * FROM currency ORDER BY is_default DESC, id ASC");
$currencies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສະກຸນເງິນ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
        @media (max-width: 576px) {
            body { padding: 10px; }
            h2 { font-size: 1.25rem; }
            .card-title { font-size: 1rem; }
            .table th, .table td { padding: 0.5rem; font-size: 0.85rem; }
            .badge { font-size: 0.75rem !important; }
            .btn-sm { padding: 0.25rem 0.4rem; font-size: 0.75rem; }
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
                    timer: 1500
                });
            });
        </script>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: '<?php echo $_SESSION['error']; ?>' });
            });
        </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-coins text-warning"></i> ຈັດການອັດຕາແລກປ່ຽນ</h2>
        </div>
    </div>

    <div class="row">
        <!-- Add Form -->
        <div class="col-md-4">
            <div class="card card-primary card-outline shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title">ເພີ່ມສະກຸນເງິນໃໝ່</h3>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ຕົວຫຍໍ້ <small>(THB, USD)</small></label>
                            <input type="text" name="currency_code" class="form-control" required style="text-transform: uppercase;">
                        </div>
                        <div class="form-group">
                            <label>ຊື່ສະກຸນເງິນ</label>
                            <input type="text" name="currency_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>ອັດຕາແລກປ່ຽນ (1 = ? ກີບ)</label>
                            <input type="text" name="exchange_rate" class="form-control number-format" placeholder="ເຊັ່ນ: 650" required>
                        </div>
                        <div class="form-group">
                            <label>ສັນຍາລັກ</label>
                            <input type="text" name="symbol" class="form-control">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="add_currency" class="btn btn-primary btn-block"><i class="fas fa-save"></i> ບັນທຶກ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="col-md-8">
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">ລາຍຊື່ອັດຕາແລກປ່ຽນທັງໝົດ</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover text-center mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>ສະກຸນເງິນ</th>
                                    <th>ຕົວຫຍໍ້/ສັນຍາລັກ</th>
                                    <th>ອັດຕາແລກປ່ຽນ</th>
                                    <th>ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($currencies as $index => $c): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td class="text-left font-weight-bold"><?php echo htmlspecialchars($c['currency_name']); ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?php echo htmlspecialchars($c['currency_code']); ?></span>
                                            <div class="text-muted small">(<?php echo htmlspecialchars($c['symbol']); ?>)</div>
                                        </td>
                                        <td class="text-primary font-weight-bold text-right pr-2 pr-md-4">
                                            <?php if($c['is_default']): ?>
                                                <span class="badge badge-success px-2 py-1">ສະກຸນເງິນຫຼັກ</span>
                                            <?php else: ?>
                                                1 <?php echo htmlspecialchars($c['currency_code']); ?> = <?php echo number_format($c['exchange_rate']); ?> ₭
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-warning text-white btn-edit" 
                                                    data-id="<?php echo $c['id']; ?>"
                                                    data-code="<?php echo htmlspecialchars($c['currency_code']); ?>"
                                                    data-name="<?php echo htmlspecialchars($c['currency_name']); ?>"
                                                    data-rate="<?php echo number_format($c['exchange_rate']); ?>"
                                                    data-symbol="<?php echo htmlspecialchars($c['symbol']); ?>"
                                                    data-default="<?php echo $c['is_default']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if(!$c['is_default']): ?>
                                                    <button class="btn btn-danger btn-delete" data-id="<?php echo $c['id']; ?>"><i class="fas fa-trash-alt"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($currencies)): ?>
                                    <tr><td colspan="5" class="text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="fas fa-edit"></i> ແກ້ໄຂອັດຕາແລກປ່ຽນ</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="post">
          <div class="modal-body">
              <input type="hidden" name="id" id="edit_id">
              <div class="form-group">
                  <label>ຕົວຫຍໍ້ສະກຸນເງິນ</label>
                  <input type="text" name="currency_code" id="edit_code" class="form-control" required style="text-transform: uppercase;">
              </div>
              <div class="form-group">
                  <label>ຊື່ສະກຸນເງິນ</label>
                  <input type="text" name="currency_name" id="edit_name" class="form-control" required>
              </div>
              <div class="form-group" id="rate_group">
                  <label>ອັດຕາແລກປ່ຽນ (1 ສະກຸນເງິນ = ? ກີບ)</label>
                  <input type="text" name="exchange_rate" id="edit_rate" class="form-control number-format" required>
              </div>
              <div class="form-group">
                  <label>ສັນຍາລັກ</label>
                  <input type="text" name="symbol" id="edit_symbol" class="form-control">
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ຍົກເລີກ</button>
            <button type="submit" name="edit_currency" class="btn btn-warning text-white">ບັນທຶກການແກ້ໄຂ</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
<script>
$('.number-format').on('input', function() {
    var value = $(this).val().replace(/[^0-9]/g, '');
    if (value !== '') {
        $(this).val(parseInt(value, 10).toLocaleString('en-US'));
    } else {
        $(this).val('');
    }
});

$('.btn-edit').on('click', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_code').val($(this).data('code'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_rate').val($(this).data('rate'));
    $('#edit_symbol').val($(this).data('symbol'));
    
    // Disable rate editing for default currency (LAK)
    if ($(this).data('default') == 1) {
        $('#edit_rate').prop('readonly', true);
        $('#rate_group small').remove();
        $('#rate_group').append('<small class="text-danger d-block mt-1">ສະກຸນເງິນຫຼັກ ບໍ່ສາມາດປ່ຽນອັດຕາແລກປ່ຽນໄດ້.</small>');
    } else {
        $('#edit_rate').prop('readonly', false);
        $('#rate_group small').remove();
    }
    
    $('#editModal').modal('show');
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

<?php
session_start();
require_once '../config/db.php';

// Add new currency
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_currency'])) {
    $code = trim($_POST['currency_code']);
    $name_la = trim($_POST['currency_name_la']);
    $name_en = trim($_POST['currency_name_en']);
    $name_cn = trim($_POST['currency_name_cn']);
    $rate = (float)str_replace(',', '', $_POST['exchange_rate']);
    $symbol_la = trim($_POST['symbol_la']);
    $symbol_en = trim($_POST['symbol_en']);
    $symbol_cn = trim($_POST['symbol_cn']);
    
    // Original columns
    $name = $name_la;
    $symbol = $symbol_la;

    if (!empty($code) && !empty($name_la)) {
        $stmt = $pdo->prepare("INSERT INTO currency (currency_code, currency_name, currency_name_la, currency_name_en, currency_name_cn, exchange_rate, symbol, symbol_la, symbol_en, symbol_cn) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$code, $name, $name_la, $name_en, $name_cn, $rate, $symbol, $symbol_la, $symbol_en, $symbol_cn])) {
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
    $name_la = trim($_POST['currency_name_la']);
    $name_en = trim($_POST['currency_name_en']);
    $name_cn = trim($_POST['currency_name_cn']);
    $rate = (float)str_replace(',', '', $_POST['exchange_rate']);
    $symbol_la = trim($_POST['symbol_la']);
    $symbol_en = trim($_POST['symbol_en']);
    $symbol_cn = trim($_POST['symbol_cn']);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE currency SET currency_code = ?, currency_name = ?, currency_name_la = ?, currency_name_en = ?, currency_name_cn = ?, exchange_rate = ?, symbol = ?, symbol_la = ?, symbol_en = ?, symbol_cn = ? WHERE id = ?");
        if ($stmt->execute([$code, $name_la, $name_la, $name_en, $name_cn, $rate, $symbol_la, $symbol_la, $symbol_en, $symbol_cn, $id])) {
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

$current_lang = $_SESSION['lang'] ?? 'la';
$name_col = "currency_name_" . $current_lang;
$symbol_col = "symbol_" . $current_lang;
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
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        .btn-warning { background: transparent !important; border: none !important; color: #ffc107 !important; font-size: 1.15rem; padding: 0 8px; box-shadow: none !important; }
        .btn-danger { background: transparent !important; border: none !important; color: #dc3545 !important; font-size: 1.15rem; padding: 0 8px; box-shadow: none !important; }
        .btn-warning:hover, .btn-danger:hover { opacity: 0.7; }
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
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">ຕົວຫຍໍ້ <small>(LAK, USD)</small></label>
                                <input type="text" name="currency_code" class="form-control" placeholder="ກະລຸນາປ້ອນຕົວຫຍໍ້ສະກຸນເງິນ" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">ອັດຕາແລກປ່ຽນ <small>(1 = ? ກີບ)</small></label>
                                <input type="text" name="exchange_rate" class="form-control number-format" placeholder="ກະລຸນາປ້ອນອັດຕາແລກປ່ຽນ" required>
                            </div>
                            <div class="col-md-8 form-group">
                                <label class="font-weight-bold">ຊື່ສະກຸນເງິນ <small>(Lao)</small></label>
                                <input type="text" name="currency_name_la" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ສະກຸນເງິນ" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">ສັນຍາລັກ <small>(Symbol)</small></label>
                                <input type="text" name="symbol_la" class="form-control" placeholder="ກະລຸນາປ້ອນສັນຍາລັກສະກຸນເງິນ">
                            </div>
                        </div>

                        <!-- <button class="btn btn-link btn-sm p-0 mb-3 text-decoration-none" type="button" data-toggle="collapse" data-target="#moreOptions" aria-expanded="false">
                            <i class="fas fa-plus-circle mr-1"></i> ຕົວເລືອກພາສາອື່ນ (Advanced)
                        </button>

                        <div class="collapse" id="moreOptions">
                            <div class="card card-body bg-light border-0 p-3 mb-0">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">Currency Name (EN)</label>
                                        <input type="text" name="currency_name_en" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">币种名称 (CN)</label>
                                        <input type="text" name="currency_name_cn" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6 form-group mb-0">
                                        <label class="small font-weight-bold">Symbol (EN)</label>
                                        <input type="text" name="symbol_en" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6 form-group mb-0">
                                        <label class="small font-weight-bold">Symbol (CN)</label>
                                        <input type="text" name="symbol_cn" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div> -->
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
                                        <td class="text-left font-weight-bold"><?php echo htmlspecialchars($c[$name_col] ?: $c['currency_name']); ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?php echo htmlspecialchars($c['currency_code']); ?></span>
                                            <div class="text-muted small">(<?php echo htmlspecialchars($c[$symbol_col] ?: $c['symbol']); ?>)</div>
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
                                                    data-name-la="<?php echo htmlspecialchars($c['currency_name_la'] ?: $c['currency_name']); ?>"
                                                    data-name-en="<?php echo htmlspecialchars($c['currency_name_en'] ?? ''); ?>"
                                                    data-name-cn="<?php echo htmlspecialchars($c['currency_name_cn'] ?? ''); ?>"
                                                    data-rate="<?php echo number_format($c['exchange_rate']); ?>"
                                                    data-symbol-la="<?php echo htmlspecialchars($c['symbol_la'] ?: $c['symbol']); ?>"
                                                    data-symbol-en="<?php echo htmlspecialchars($c['symbol_en'] ?? ''); ?>"
                                                    data-symbol-cn="<?php echo htmlspecialchars($c['symbol_cn'] ?? ''); ?>"
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
              <div class="row">
                  <div class="col-md-6 form-group">
                      <label class="font-weight-bold">ຕົວຫຍໍ້</label>
                      <input type="text" name="currency_code" id="edit_code" class="form-control" required style="text-transform: uppercase;">
                  </div>
                  <div class="col-md-6 form-group" id="rate_group">
                      <label class="font-weight-bold">ອັດຕາແລກປ່ຽນ</label>
                      <input type="text" name="exchange_rate" id="edit_rate" class="form-control number-format" required>
                  </div>
                  <div class="col-md-8 form-group">
                      <label class="font-weight-bold">ຊື່ສະກຸນເງິນ (Lao)</label>
                      <input type="text" name="currency_name_la" id="edit_name_la" class="form-control" required>
                  </div>
                  <div class="col-md-4 form-group">
                      <label class="font-weight-bold">ສັນຍາລັກ</label>
                      <input type="text" name="symbol_la" id="edit_symbol_la" class="form-control">
                  </div>
              </div>

              <!-- <button class="btn btn-link btn-sm p-0 mb-3 text-decoration-none" type="button" data-toggle="collapse" data-target="#editMoreOptions">
                  <i class="fas fa-plus-circle mr-1"></i> ຕົວເລືອກພາສາອື່ນ (Advanced)
              </button>

              <div class="collapse" id="editMoreOptions">
                  <div class="card card-body bg-light border-0 p-3 mb-0">
                      <div class="row">
                          <div class="col-md-6 form-group">
                              <label class="small font-weight-bold">Currency Name (EN)</label>
                              <input type="text" name="currency_name_en" id="edit_name_en" class="form-control form-control-sm">
                          </div>
                          <div class="col-md-6 form-group">
                              <label class="small font-weight-bold">币种名称 (CN)</label>
                              <input type="text" name="currency_name_cn" id="edit_name_cn" class="form-control form-control-sm">
                          </div>
                          <div class="col-md-6 form-group mb-0">
                              <label class="small font-weight-bold">Symbol (EN)</label>
                              <input type="text" name="symbol_en" id="edit_symbol_en" class="form-control form-control-sm">
                          </div>
                          <div class="col-md-6 form-group mb-0">
                              <label class="small font-weight-bold">Symbol (CN)</label>
                              <input type="text" name="symbol_cn" id="edit_symbol_cn" class="form-control form-control-sm">
                          </div>
                      </div>
                  </div>
              </div> -->
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
    $('#edit_name_la').val($(this).data('name-la'));
    $('#edit_name_en').val($(this).data('name-en'));
    $('#edit_name_cn').val($(this).data('name-cn'));
    $('#edit_rate').val($(this).data('rate'));
    $('#edit_symbol_la').val($(this).data('symbol-la'));
    $('#edit_symbol_en').val($(this).data('symbol-en'));
    $('#edit_symbol_cn').val($(this).data('symbol-cn'));
    
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

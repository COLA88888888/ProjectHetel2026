<?php
session_start();
require_once 'config/db.php';

// Check if settings exist, if not create default keys
$default_keys = [
    'hotel_name' => 'ໂຮງແຮມ ຕົວຢ່າງ',
    'hotel_phone' => '020 00000000',
    'hotel_address' => 'ນະຄອນຫຼວງວຽງຈັນ',
    'receipt_footer' => 'ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ!',
    'hotel_logo' => '',
    'tax_percent' => '0',
    'hotel_qr' => ''
];

foreach ($default_keys as $key => $val) {
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->rowCount() == 0) {
        $stmtIns = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmtIns->execute([$key, $val]);
    }
}

// Fetch all settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $hotel_name = trim($_POST['hotel_name']);
    $hotel_phone = trim($_POST['hotel_phone']);
    $hotel_address = trim($_POST['hotel_address']);
    $receipt_footer = trim($_POST['receipt_footer']);
    $tax_percent = (float)$_POST['tax_percent'];
    $currency_id = (int)$_POST['currency_id'];

    // Update text settings
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_name'")->execute([$hotel_name]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_phone'")->execute([$hotel_phone]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_address'")->execute([$hotel_address]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'receipt_footer'")->execute([$receipt_footer]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'tax_percent'")->execute([$tax_percent]);

    // Update Default Currency
    $pdo->query("UPDATE currency SET is_default = 0");
    $pdo->prepare("UPDATE currency SET is_default = 1 WHERE id = ?")->execute([$currency_id]);

    // Handle Logo Upload
    if (isset($_FILES['hotel_logo']) && $_FILES['hotel_logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['hotel_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newname = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['hotel_logo']['tmp_name'], 'assets/img/logo/' . $newname)) {
                // Delete old logo if exists
                if (!empty($settings_data['hotel_logo'])) {
                    $oldPath = 'assets/img/logo/' . $settings_data['hotel_logo'];
                    if (file_exists($oldPath) && $settings_data['hotel_logo'] != 'admin-avatar.png') {
                        unlink($oldPath);
                    }
                }
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_logo'")->execute([$newname]);
            }
        }
    }
    // Handle QR Upload
    if (isset($_FILES['hotel_qr']) && $_FILES['hotel_qr']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['hotel_qr']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newname = 'qr_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['hotel_qr']['tmp_name'], 'assets/img/QR/' . $newname)) {
                // Delete old QR if exists
                if (!empty($settings_data['hotel_qr'])) {
                    $oldPath = 'assets/img/QR/' . $settings_data['hotel_qr'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_qr'")->execute([$newname]);
            }
        }
    }

    logActivity($pdo, "ອັບເດດການຕັ້ງຄ່າລະບົບ", "ໂຮງແຮມ: $hotel_name");

    $_SESSION['success'] = "ບັນທຶກການຕັ້ງຄ່າສຳເລັດແລ້ວ!";
    header("Location: settings.php");
    exit();
}

// Fetch all currencies
$stmtCur = $pdo->query("SELECT * FROM currency ORDER BY id ASC");
$currencies = $stmtCur->fetchAll();

// Get current default currency
$default_currency = null;
foreach($currencies as $c) {
    if($c['is_default'] == 1) $default_currency = $c;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຕັ້ງຄ່າລະບົບໂຮງແຮມ</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Noto Sans Lao Looped', sans-serif !important; 
            background-color: #f4f6f9; 
            padding: 15px; 
            font-size: 0.9rem;
        }
        h2 { font-size: 1.5rem; font-weight: 700; }
        .card-title { font-size: 1.1rem; font-weight: 600; }
        .logo-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px dashed #ccc;
            margin-bottom: 10px;
        }
        .form-group label { font-weight: 500; color: #555; }
        
        @media (max-width: 576px) {
            body { padding: 10px; font-size: 0.85rem; }
            h2 { font-size: 1.25rem; }
            .logo-preview { width: 100px; height: 100px; }
            .col-form-label { text-align: left !important; padding-bottom: 0; }
            .card-body { padding: 15px; }
            .btn { font-size: 0.85rem; }
            .alert h4 { font-size: 1rem !important; word-break: break-all; }
            .alert { padding: 10px; }
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
        <div class="col-12">
            <h2><i class="fas fa-cog"></i> ຕັ້ງຄ່າຂໍ້ມູນໂຮງແຮມ</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hotel"></i> ລາຍລະອຽດໂຮງແຮມ</h3>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        
                        <div class="row mb-4">
                            <div class="col-sm-6 text-center border-right">
                                <?php if(!empty($settings_data['hotel_logo'])): ?>
                                    <img id="previewLogo" src="assets/img/logo/<?php echo $settings_data['hotel_logo']; ?>" class="logo-preview shadow-sm">
                                <?php else: ?>
                                    <img id="previewLogo" src="https://via.placeholder.com/150?text=Logo" class="logo-preview shadow-sm">
                                <?php endif; ?>
                                <div>
                                    <label for="hotel_logo" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-upload"></i> ປ່ຽນໂລໂກ້
                                    </label>
                                    <input type="file" name="hotel_logo" id="hotel_logo" class="d-none" accept="image/*" onchange="previewImage(this, 'previewLogo')">
                                </div>
                                <small class="text-muted">ໂລໂກ້ໂຮງແຮມ</small>
                            </div>
                            <div class="col-sm-6 text-center">
                                <?php if(!empty($settings_data['hotel_qr'])): ?>
                                    <img id="previewQR" src="assets/img/QR/<?php echo $settings_data['hotel_qr']; ?>" class="logo-preview shadow-sm">
                                <?php else: ?>
                                    <img id="previewQR" src="https://via.placeholder.com/150?text=QR+Code" class="logo-preview shadow-sm">
                                <?php endif; ?>
                                <div>
                                    <label for="hotel_qr" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-qrcode"></i> ປ່ຽນ QR ສຳລັບໂອນ
                                    </label>
                                    <input type="file" name="hotel_qr" id="hotel_qr" class="d-none" accept="image/*" onchange="previewImage(this, 'previewQR')">
                                </div>
                                <small class="text-muted">QR Code ສຳລັບຊຳລະເງິນ</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label text-right">ຊື່ໂຮງແຮມ / ເຮືອນພັກ <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($settings_data['hotel_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label text-right">ເບີໂທລະສັບຕິດຕໍ່ <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="hotel_phone" class="form-control" value="<?php echo htmlspecialchars($settings_data['hotel_phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label text-right">ທີ່ຢູ່ / ສະຖານທີ່ຕັ້ງ</label>
                            <div class="col-sm-9">
                                <textarea name="hotel_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings_data['hotel_address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label text-right">ຂໍ້ຄວາມທ້າຍໃບຮັບເງິນ</label>
                            <div class="col-sm-9">
                                <input type="text" name="receipt_footer" class="form-control" value="<?php echo htmlspecialchars($settings_data['receipt_footer'] ?? ''); ?>" placeholder="ຕົວຢ່າງ: ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ ໂອກາດໜ້າເຊີນໃໝ່">
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3 text-info"><i class="fas fa-coins"></i> ຕັ້ງຄ່າການເງິນ ແລະ ພາສີ</h5>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label text-right">ສະກຸນເງິນຫຼັກ <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="currency_id" class="form-control" required>
                                    <?php foreach($currencies as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($c['is_default'] == 1) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['currency_name']); ?> (<?php echo htmlspecialchars($c['symbol']); ?>) - <?php echo $c['currency_code']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">ສະກຸນເງິນທີ່ທ່ານເລືອກຈະຖືກໃຊ້ສະແດງຜົນທັງລະບົບ.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label text-right">ອັດຕາພາສີ (%)</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="number" name="tax_percent" class="form-control" value="<?php echo htmlspecialchars($settings_data['tax_percent'] ?? '0'); ?>" step="0.01" min="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="text-muted">ພາສີຈະຖືກນຳໄປຄິດໄລ່ໃນໃບບິນ POS.</small>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer bg-light text-right">
                        <button type="submit" name="save_settings" class="btn btn-primary px-5"><i class="fas fa-save"></i> ບັນທຶກ</button>
                    </div>
                </form>
            </div>

            <!-- Network Access Info -->
            <div class="card card-info card-outline shadow-sm mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired"></i> ການເຂົ້າເຖິງລະບົບ (Network Access)</h3>
                </div>
                <div class="card-body">
                    <p>ທ່ານສາມາດໃຫ້ພະນັກງານຄົນອື່ນເຂົ້າໃຊ້ລະບົບຜ່ານ Network ດຽວກັນໄດ້ໂດຍການພິມ IP ນີ້ໃນ Browser:</p>
                    <div class="alert alert-info">
                        <h4 class="mb-0 text-center font-weight-bold">
                            http://<?php echo gethostbyname(gethostname()); ?>/ProjectHetel2026
                        </h4>
                    </div>
                    <small class="text-muted">* ໝາຍເຫດ: ເຄື່ອງອື່ນໆຕ້ອງເຊື່ອມຕໍ່ WiFi ຫຼື ວົງ LAN ດຽວກັນກັບເຄື່ອງ Server ນີ້.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#' + previewId).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>

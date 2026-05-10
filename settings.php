<?php
session_start();
require_once 'config/db.php';

// Check if settings exist, if not create default keys
$default_keys = [
    'hotel_name' => 'ໂຮງແຮມ ຕົວຢ່າງ',
    'hotel_phone' => '020 00000000',
    'hotel_address' => 'ນະຄອນຫຼວງວຽງຈັນ',
    'receipt_footer' => 'ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ!',
    'hotel_logo' => ''
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

    // Update text settings
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_name'")->execute([$hotel_name]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_phone'")->execute([$hotel_phone]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_address'")->execute([$hotel_address]);
    $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'receipt_footer'")->execute([$receipt_footer]);

    // Handle Logo Upload
    if (isset($_FILES['hotel_logo']) && $_FILES['hotel_logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['hotel_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newname = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['hotel_logo']['tmp_name'], 'assets/img/' . $newname)) {
                // Delete old logo if exists
                if (!empty($settings_data['hotel_logo'])) {
                    $oldPath = 'assets/img/' . $settings_data['hotel_logo'];
                    if (file_exists($oldPath) && $settings_data['hotel_logo'] != 'admin-avatar.png') {
                        unlink($oldPath);
                    }
                }
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hotel_logo'")->execute([$newname]);
            }
        }
    }

    $_SESSION['success'] = "ບັນທຶກການຕັ້ງຄ່າສຳເລັດແລ້ວ!";
    header("Location: settings.php");
    exit();
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
    <!-- Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f4f6f9; padding: 20px; }
        .logo-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px dashed #ccc;
            margin-bottom: 10px;
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
                        
                        <div class="text-center mb-4">
                            <?php if(!empty($settings_data['hotel_logo'])): ?>
                                <img id="previewLogo" src="assets/img/<?php echo $settings_data['hotel_logo']; ?>" class="logo-preview shadow-sm">
                            <?php else: ?>
                                <img id="previewLogo" src="https://via.placeholder.com/150?text=Logo" class="logo-preview shadow-sm">
                            <?php endif; ?>
                            <div>
                                <label for="hotel_logo" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-upload"></i> ປ່ຽນໂລໂກ້
                                </label>
                                <input type="file" name="hotel_logo" id="hotel_logo" class="d-none" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <small class="text-muted">ແນະນຳຂະໜາດ 500x500 ພິກເຊວ (ຮອງຮັບ JPG, PNG)</small>
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

                    </div>
                    <div class="card-footer bg-light text-right">
                        <button type="submit" name="save_settings" class="btn btn-primary px-5"><i class="fas fa-save"></i> ບັນທຶກການຕັ້ງຄ່າ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#previewLogo').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>

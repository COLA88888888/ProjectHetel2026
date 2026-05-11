<?php
session_start();
require_once 'config/db.php';

// Get active bookings (Occupied rooms)
$stmt = $pdo->query("
    SELECT b.id as booking_id, r.room_number, b.customer_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = 'Occupied'
    ORDER BY r.room_number ASC
");
$active_bookings = $stmt->fetchAll();

$selected_booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (count($active_bookings) > 0 ? $active_bookings[0]['booking_id'] : 0);

// Fetch categories for filtering
$stmtCate = $pdo->query("SELECT id, name FROM product_categories ORDER BY name ASC");
$categories = $stmtCate->fetchAll();

// Fetch available products for selection
$stmtProd = $pdo->query("SELECT * FROM products WHERE qty > 0 ORDER BY prod_name ASC");
$products_list = $stmtProd->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $booking_id = (int)$_POST['booking_id'];
    $item_name = trim($_POST['item_name']);
    $price = (float)str_replace(',', '', $_POST['price']);
    $qty = (int)$_POST['qty'];
    $total_price = $price * $qty;
    $prod_id = isset($_POST['prod_id']) && !empty($_POST['prod_id']) ? (int)$_POST['prod_id'] : null;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO room_services (booking_id, prod_id, item_name, price, qty, total_price) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$booking_id, $prod_id, $item_name, $price, $qty, $total_price]);

        // Update food_charge in bookings
        $updateBooking = $pdo->prepare("UPDATE bookings SET food_charge = food_charge + ? WHERE id = ?");
        $updateBooking->execute([$total_price, $booking_id]);

        // Reduce stock if it's a product
        if ($prod_id) {
            $updateStock = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE prod_id = ?");
            $updateStock->execute([$qty, $prod_id]);
        }

        $pdo->commit();
        $_SESSION['success'] = "ບັນທຶກຄ່າໃຊ້ຈ່າຍເພີ່ມສຳເລັດ!";
        header("Location: room_service.php?booking_id=" . $booking_id);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $booking_id = (int)$_GET['booking_id'];
    
    $pdo->beginTransaction();
    try {
        // Get service info to restore values
        $stmt = $pdo->prepare("SELECT total_price, prod_id, qty FROM room_services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        
        if ($service) {
            // Delete record
            $delStmt = $pdo->prepare("DELETE FROM room_services WHERE id = ?");
            $delStmt->execute([$id]);

            // Restore food_charge
            $updateBooking = $pdo->prepare("UPDATE bookings SET food_charge = food_charge - ? WHERE id = ?");
            $updateBooking->execute([$service['total_price'], $booking_id]);

            // Restore stock if it was a product
            if ($service['prod_id']) {
                $restoreStock = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE prod_id = ?");
                $restoreStock->execute([$service['qty'], $service['prod_id']]);
            }
        }
        $pdo->commit();
        $_SESSION['success'] = "ລົບລາຍການສຳເລັດ!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
    header("Location: room_service.php?booking_id=" . $booking_id);
    exit();
}

// Fetch services for selected booking
$services = [];
$total_accumulated = 0;
if ($selected_booking_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM room_services WHERE booking_id = ? ORDER BY id DESC");
    $stmt->execute([$selected_booking_id]);
    $services = $stmt->fetchAll();
    
    foreach ($services as $s) {
        $total_accumulated += $s['total_price'];
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບໍລິການເພີ່ມເຕີມລະຫວ່າງພັກ</title>
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
        *:not(.fas):not(.far):not(.fab):not(.fa) { font-family: 'Noto Sans Lao Looped', sans-serif !important; }
        .fas, .far, .fab, .fa { font-family: "Font Awesome 5 Free" !important; font-weight: 900 !important; }
        body { background-color: #f4f6f9; padding: 20px; }
        
        /* Category Styles */
        .cate-scroll { display: flex; overflow-x: auto; gap: 10px; padding: 10px 0; margin-bottom: 15px; scrollbar-width: thin; }
        .cate-btn { white-space: nowrap; border-radius: 20px; padding: 8px 20px; cursor: pointer; transition: 0.2s; border: 1px solid #007bff; background: #fff; color: #007bff; }
        .cate-btn.active { background: #007bff; color: #fff; }
        .cate-btn:hover { background: #e7f1ff; }

        /* Product Grid Styles */
        .prod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding: 5px; }
        .prod-item { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 10px; cursor: pointer; transition: 0.2s; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .prod-item:hover { border-color: #007bff; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .prod-name { font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.7rem; }
        .prod-price { color: #28a745; font-weight: bold; font-size: 0.85rem; }
        .prod-qty { font-size: 0.75rem; color: #777; }
        
        .sticky-form { position: sticky; top: 20px; }
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
            <h2><i class="fas fa-concierge-bell"></i> ບັນທຶກຄ່າໃຊ້ຈ່າຍເພີ່ມ (ລະຫວ່າງເຂົ້າພັກ)</h2>
        </div>
    </div>

    <?php if (count($active_bookings) == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> ປະຈຸບັນບໍ່ມີຫ້ອງໃດເຂົ້າພັກຢູ່ເລີຍ. ບໍ່ສາມາດບັນທຶກຄ່າໃຊ້ຈ່າຍໄດ້.
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Product Selection Section -->
            <div class="col-md-5">
                <div class="card card-outline card-primary shadow-sm sticky-form">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-search"></i> ເລືອກສິນຄ້າຈາກຮ້ານ</h3>
                    </div>
                    <div class="card-body">
                        <!-- Search Box -->
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" id="product_search_manual" class="form-control" placeholder="ຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ອາຫານ...">
                        </div>

                        <!-- Categories -->
                        <div class="cate-scroll">
                            <div class="cate-btn active" data-cate="all">ທັງໝົດ</div>
                            <?php foreach($categories as $c): ?>
                                <div class="cate-btn" data-cate="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Product Grid -->
                        <div class="prod-grid" id="productGrid">
                            <?php foreach($products_list as $p): ?>
                                <div class="prod-item" 
                                     data-id="<?php echo $p['prod_id']; ?>" 
                                     data-name="<?php echo htmlspecialchars($p['prod_name']); ?>" 
                                     data-price="<?php echo $p['sprice']; ?>"
                                     data-cate="<?php echo htmlspecialchars($p['category']); ?>">
                                    <div class="prod-name"><?php echo htmlspecialchars($p['prod_name']); ?></div>
                                    <div class="prod-price"><?php echo number_format($p['sprice']); ?> ກີບ</div>
                                    <div class="prod-qty">ຄົງເຫຼືອ: <?php echo $p['qty']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List Section -->
            <div class="col-md-7">
                <!-- Selection Details Form (Hidden until selected) -->
                <div id="selectionForm" class="card card-outline card-success shadow-sm mb-3" style="display: none;">
                    <div class="card-body">
                        <form action="" method="post" id="serviceForm">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label>ຫ້ອງພັກ</label>
                                    <select name="booking_id" class="form-control">
                                        <?php foreach($active_bookings as $b): ?>
                                            <option value="<?php echo $b['booking_id']; ?>" <?php echo ($selected_booking_id == $b['booking_id']) ? 'selected' : ''; ?>>
                                                ຫ້ອງ <?php echo htmlspecialchars($b['room_number']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>ຊື່ລາຍການ</label>
                                    <input type="text" name="item_name" id="selected_item_name" class="form-control" readonly>
                                    <input type="hidden" name="prod_id" id="selected_prod_id">
                                </div>
                                <div class="col-md-2">
                                    <label>ລາຄາ</label>
                                    <input type="text" name="price" id="selected_price" class="form-control number-format" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label>ຈຳນວນ</label>
                                    <input type="number" name="qty" id="selected_qty" class="form-control" value="1" min="1">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="add_service" class="btn btn-success btn-block"><i class="fas fa-plus"></i> ບັນທຶກ</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card card-info card-outline shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list"></i> ບໍລິການຂອງຫ້ອງ: 
                            <select onchange="window.location.href='?booking_id='+this.value" class="ml-2 border-0 bg-transparent font-weight-bold" style="outline: none;">
                                <?php foreach($active_bookings as $b): ?>
                                    <option value="<?php echo $b['booking_id']; ?>" <?php echo ($selected_booking_id == $b['booking_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['room_number']); ?> (<?php echo htmlspecialchars($b['customer_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </h3>
                        <div class="ml-auto text-danger font-weight-bold" style="font-size: 1.1rem;">
                            ລວມ: <?php echo number_format($total_accumulated); ?> ກີບ
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-striped table-hover text-center">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>ວັນເວລາ</th>
                                    <th>ຊື່ລາຍການ</th>
                                    <th>ລາຄາ</th>
                                    <th>ຈຳນວນ</th>
                                    <th>ລວມ</th>
                                    <th>ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($services) > 0): ?>
                                    <?php $i = 1; foreach ($services as $row): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td><?php echo number_format($row['price']); ?></td>
                                            <td><?php echo $row['qty']; ?></td>
                                            <td class="font-weight-bold text-success"><?php echo number_format($row['total_price']); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-danger btn-delete px-3" data-id="<?php echo $row['id']; ?>" data-booking="<?php echo $selected_booking_id; ?>"><i class="fas fa-trash"></i> ລຶບ</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-muted py-4">ຍັງບໍ່ມີລາຍການໃຊ້ຈ່າຍເພີ່ມເຕີມສຳລັບຫ້ອງນີ້.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Category Filtering
    $('.cate-btn').on('click', function() {
        $('.cate-btn').removeClass('active');
        $(this).addClass('active');
        
        var cate = $(this).data('cate');
        if (cate === 'all') {
            $('.prod-item').show();
        } else {
            $('.prod-item').hide();
            $('.prod-item[data-cate="' + cate + '"]').show();
        }
    });

    // Manual Search
    $('#product_search_manual').on('input', function() {
        var val = $(this).val().toLowerCase();
        $('.prod-item').filter(function() {
            $(this).toggle($(this).data('name').toLowerCase().indexOf(val) > -1);
        });
    });

    // Product Item Selection
    $('.prod-item').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var price = $(this).data('price');

        $('#selected_prod_id').val(id);
        $('#selected_item_name').val(name);
        $('#selected_price').val(parseInt(price).toLocaleString('en-US'));
        $('#selected_qty').val(1);
        
        $('#selectionForm').slideDown();
        $('#selected_qty').focus();
    });

    // Number formatting
    $('.number-format').on('input', function(e) {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== '') {
            $(this).val(parseInt(value, 10).toLocaleString('en-US'));
        } else {
            $(this).val('');
        }
    });

    // Form validation
    $('#serviceForm').on('submit', function(e) {
        var name = $('#item_name').val().trim();
        var price = $('#price').val().trim();
        
        if (name === '' || price === '' || price === '0') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ຂໍ້ມູນບໍ່ຄົບຖ້ວນ',
                text: 'ກະລຸນາປ້ອນຊື່ລາຍການ ແລະ ລາຄາ!',
                confirmButtonText: 'ຕົກລົງ'
            });
            return false;
        }
    });

    // Delete Confirmation
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var booking = $(this).data('booking');
        Swal.fire({
            title: 'ຍືນຍັນການລົບ?',
            text: "ທ່ານຕ້ອງການລົບລາຍການນີ້ແທ້ບໍ່?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ລົບເລີຍ!',
            cancelButtonText: 'ຍົກເລີກ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?delete=" + id + "&booking_id=" + booking;
            }
        });
    });
});
</script>
</body>
</html>

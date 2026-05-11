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
    $stmt = $pdo->prepare("
        SELECT rs.*, p.image as prod_image, p.category as prod_category 
        FROM room_services rs 
        LEFT JOIN products p ON rs.prod_id = p.prod_id 
        WHERE rs.booking_id = ? 
        ORDER BY rs.id DESC
    ");
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
    <title>Room Service POS</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free-5.15.3-web/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0099ffff 0%, #0066cc 100%);
            --glass-bg: rgba(255, 255, 255, 0.9);
        }
        *:not(.fas):not(.far):not(.fab):not(.fa) { font-family: 'Noto Sans Lao Looped', sans-serif !important; }
        .fas, .far, .fab, .fa { font-family: "Font Awesome 5 Free" !important; font-weight: 900 !important; }
        
        body { 
            background: #f0f2f5; 
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
        }

        .header-section {
            background: var(--primary-gradient);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .main-container {
            flex: 1;
            display: flex;
            padding: 15px;
            gap: 15px;
            overflow: hidden;
        }

        /* Desktop specific height */
        @media (min-width: 992px) {
            body { height: 100vh; overflow: hidden; }
            .main-container { height: calc(100vh - 70px); }
        }

        /* Left Column: Products */
        .product-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--glass-bg);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .search-area {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .category-bar {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 10px 15px;
            background: #f8f9fa;
            scrollbar-width: none;
        }
        .category-bar::-webkit-scrollbar { display: none; }
        .cate-pill {
            white-space: nowrap;
            padding: 8px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        .cate-pill.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
            box-shadow: 0 4px 10px rgba(0,123,255,0.3);
        }

        .product-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            align-content: start;
        }

        .product-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 15px;
            padding: 0;
            text-align: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .product-card:active {
            transform: scale(0.98);
            background: #f8f9fa;
        }
        
        .product-img-wrapper {
            width: 100%;
            height: 100px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-img-wrapper .icon-placeholder {
            font-size: 2rem;
            color: #ccc;
        }

        .product-card-body {
            padding: 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-name { 
            font-weight: bold; 
            font-size: 0.85rem; 
            margin-bottom: 5px; 
            min-height: 2.4rem; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
            color: #333;
            line-height: 1.2;
        }
        .product-price { color: #28a745; font-weight: 700; font-size: 0.95rem; }
        .product-stock { font-size: 0.7rem; color: #999; margin-top: 3px; }

        /* Right Column: Order */
        .order-column {
            width: 400px;
            background: white;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        @media (max-width: 991px) {
            .main-container {
                flex-direction: column;
                overflow: auto;
                height: auto;
                padding: 10px;
            }
            .order-column {
                width: 100%;
                order: -1; 
                max-height: 400px;
                margin-bottom: 10px;
            }
            .product-column {
                height: 500px; 
            }
            .product-scroll {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                padding: 10px;
            }
            .header-section h4 { font-size: 1.1rem; }
            .header-section .mr-3 { display: none; } /* Hide date on mobile to save space */
        }

        .room-selector-area {
            padding: 20px;
            background: #fff;
            border-bottom: 2px solid #f8f9fa;
        }

        .order-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border: 1px solid #eee;
            transition: 0.2s;
        }
        .order-item:hover { background: #fff; border-color: #007bff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .order-item-info { flex: 1; margin-left: 10px; }
        .order-item-name { font-weight: bold; font-size: 0.9rem; color: #333; }
        .order-item-price { font-size: 0.85rem; color: #666; }
        
        .order-item-thumb {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            background: #eee;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .order-item-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .order-item-thumb i { font-size: 1.2rem; color: #999; }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 15px;
        }
        .qty-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #555;
        }
        .qty-btn:hover { background: #007bff; color: white; border-color: #007bff; }
        .qty-val { font-weight: bold; width: 25px; text-align: center; }

        .order-footer {
            padding: 20px;
            background: #fff;
            border-top: 2px solid #f8f9fa;
        }
        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #fdf2f2;
            border-radius: 8px;
            color: #dc3545;
        }
        .total-label { font-weight: bold; font-size: 1.1rem; }
        .total-amount { font-weight: 800; font-size: 1.4rem; }

        .btn-confirm {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: bold;
            font-size: 1.1rem;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
            transition: 0.3s;
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
            color: white;
        }
        
        /* Floating Action */
        .delete-item { color: #dc3545; cursor: pointer; padding: 5px; opacity: 0.6; transition: 0.2s; }
        .delete-item:hover { opacity: 1; transform: scale(1.1); }
    </style>
</head>
<body>

<div class="header-section d-flex justify-content-between align-items-center">
    <h4 class="m-0"><i class="fas fa-concierge-bell mr-2"></i> ບໍລິການຂອງຫ້ອງ (Room Service)</h4>
    <div class="d-flex align-items-center">
        <span class="mr-3"><i class="fas fa-calendar-day mr-1"></i> <?php echo date('d/m/Y'); ?></span>
        <a href="Homepage.php" class="btn btn-light btn-sm rounded-pill px-3"><i class="fas fa-home"></i> ກັບໜ້າຫຼັກ</a>
    </div>
</div>

<div class="main-container">
    <!-- Products Panel -->
    <div class="product-column">
        <div class="search-area">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                </div>
                <input type="text" id="prodSearch" class="form-control border-left-0" placeholder="ຄົ້ນຫາສິນຄ້າ ຫຼື ອາຫານ...">
            </div>
        </div>

        <div class="category-bar">
            <div class="cate-pill active" data-cate="all">ທັງໝົດ</div>
            <?php foreach($categories as $c): ?>
                <div class="cate-pill" data-cate="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></div>
            <?php endforeach; ?>
        </div>

        <div class="product-scroll" id="prodGrid">
            <?php foreach($products_list as $p): ?>
                <div class="product-card" 
                     data-id="<?php echo $p['prod_id']; ?>" 
                     data-name="<?php echo htmlspecialchars($p['prod_name']); ?>" 
                     data-price="<?php echo $p['sprice']; ?>"
                     data-cate="<?php echo htmlspecialchars($p['category']); ?>">
                    
                    <div class="product-img-wrapper">
                        <?php if(!empty($p['image']) && file_exists('assets/img/products/'.$p['image'])): ?>
                            <img src="assets/img/products/<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['prod_name']); ?>">
                        <?php else: ?>
                            <div class="icon-placeholder">
                                <?php 
                                    $icon = 'fas fa-box';
                                    $c = strtolower($p['category']);
                                    if(strpos($c, 'ອາຫານ') !== false || strpos($c, 'food') !== false) $icon = 'fas fa-utensils';
                                    if(strpos($c, 'ເຄື່ອງດື່ມ') !== false || strpos($c, 'drink') !== false) $icon = 'fas fa-glass-whiskey';
                                    if(strpos($c, 'ເຂົ້າໜົມ') !== false || strpos($c, 'snack') !== false) $icon = 'fas fa-cookie';
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-card-body">
                        <div class="product-name"><?php echo htmlspecialchars($p['prod_name']); ?></div>
                        <div class="product-price"><?php echo number_format($p['sprice']); ?> ກີບ</div>
                        <div class="product-stock">ຄົງເຫຼືອ: <?php echo $p['qty']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Panel -->
    <div class="order-column">
        <div class="room-selector-area">
            <label class="text-muted small text-uppercase font-weight-bold mb-2 d-block">ເລືອກຫ້ອງທີ່ສັ່ງບໍລິການ</label>
            <select id="roomSelect" class="form-control form-control-lg border-primary" style="font-weight: bold; color: #007bff;">
                <?php foreach($active_bookings as $b): ?>
                    <option value="<?php echo $b['booking_id']; ?>" <?php echo ($selected_booking_id == $b['booking_id']) ? 'selected' : ''; ?>>
                        ຫ້ອງ <?php echo htmlspecialchars($b['room_number']); ?> - <?php echo htmlspecialchars($b['customer_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="order-list">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $row): ?>
                    <div class="order-item">
                        <div class="order-item-thumb">
                            <?php if(!empty($row['prod_image']) && file_exists('assets/img/products/'.$row['prod_image'])): ?>
                                <img src="assets/img/products/<?php echo $row['prod_image']; ?>" alt="">
                            <?php else: ?>
                                <?php 
                                    $icon = 'fas fa-box';
                                    $c = strtolower($row['prod_category'] ?? '');
                                    if(strpos($c, 'ອາຫານ') !== false || strpos($c, 'food') !== false) $icon = 'fas fa-utensils';
                                    if(strpos($c, 'ເຄື່ອງດື່ມ') !== false || strpos($c, 'drink') !== false) $icon = 'fas fa-glass-whiskey';
                                    if(strpos($c, 'ເຂົ້າໜົມ') !== false || strpos($c, 'snack') !== false) $icon = 'fas fa-cookie';
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="order-item-info">
                            <div class="order-item-name"><?php echo htmlspecialchars($row['item_name']); ?></div>
                            <div class="order-item-price text-success font-weight-bold"><?php echo number_format($row['price']); ?> ກີບ x <?php echo $row['qty']; ?></div>
                        </div>
                        <div class="text-right mr-3">
                            <div class="font-weight-bold"><?php echo number_format($row['total_price']); ?> ກີບ</div>
                        </div>
                        <div class="delete-item" onclick="confirmDelete(<?php echo $row['id']; ?>, <?php echo $selected_booking_id; ?>)">
                            <i class="fas fa-times-circle fa-lg"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-2"></i>
                    <p>ຍັງບໍ່ມີລາຍການສັ່ງເພີ່ມ</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-footer">
            <div class="total-box">
                <span class="total-label">ຍອດລວມທັງໝົດ:</span>
                <span class="total-amount"><?php echo number_format($total_accumulated); ?> ກີບ</span>
            </div>
            <p class="text-center text-muted small mt-2">ຄລິກທີ່ສິນຄ້າເພື່ອເພີ່ມລາຍການໃສ່ຫ້ອງທັນທີ</p>
        </div>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <input type="hidden" name="booking_id" id="modal_booking_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">ເພີ່ມລາຍການດ້ວຍຕົນເອງ</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>ຊື່ລາຍການ</label>
                        <input type="text" name="item_name" class="form-control" required placeholder="ເຊັ່ນ: ຄ່າຊັກລີດ, ຄ່າອາຫານນອກ...">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>ລາຄາ/ໜ່ວຍ</label>
                                <input type="text" name="price" class="form-control number-format" required value="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>ຈຳນວນ</label>
                                <input type="number" name="qty" class="form-control" value="1" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">ປິດ</button>
                    <button type="submit" name="add_service" class="btn btn-primary px-4">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Quick Add Form -->
<form id="quickAddForm" action="" method="post" style="display:none;">
    <input type="hidden" name="add_service" value="1">
    <input type="hidden" name="booking_id" id="quick_booking_id">
    <input type="hidden" name="prod_id" id="quick_prod_id">
    <input type="hidden" name="item_name" id="quick_item_name">
    <input type="hidden" name="price" id="quick_price">
    <input type="hidden" name="qty" value="1">
</form>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Room selection change
    $('#roomSelect').on('change', function() {
        window.location.href = '?booking_id=' + $(this).val();
    });

    // Category filtering
    $('.cate-pill').on('click', function() {
        $('.cate-pill').removeClass('active');
        $(this).addClass('active');
        var cate = $(this).data('cate');
        if(cate === 'all') {
            $('.product-card').fadeIn(200);
        } else {
            $('.product-card').hide();
            $('.product-card[data-cate="'+cate+'"]').fadeIn(200);
        }
    });

    // Search functionality
    $('#prodSearch').on('input', function() {
        var val = $(this).val().toLowerCase();
        $('.product-card').filter(function() {
            $(this).toggle($(this).data('name').toLowerCase().indexOf(val) > -1);
        });
    });

    // Quick Add on product card click
    $('.product-card').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var price = $(this).data('price');
        var bookingId = $('#roomSelect').val();

        if(!bookingId) {
            Swal.fire('ຜິດພາດ', 'ກະລຸນາເລືອກຫ້ອງກ່ອນ!', 'error');
            return;
        }

        $('#quick_booking_id').val(bookingId);
        $('#quick_prod_id').val(id);
        $('#quick_item_name').val(name);
        $('#quick_price').val(price);
        
        // Submit via AJAX or direct form
        $('#quickAddForm').submit();
    });

    // Manual Entry Modal
    $('#btnAddManually').on('click', function() {
        var bookingId = $('#roomSelect').val();
        if(!bookingId) {
            Swal.fire('ຜິດພາດ', 'ກະລຸນາເລືອກຫ້ອງກ່ອນ!', 'error');
            return;
        }
        $('#modal_booking_id').val(bookingId);
        $('#manualModal').modal('show');
    });

    // Number formatting
    $('.number-format').on('input', function() {
        var val = $(this).val().replace(/,/g, '');
        if(!isNaN(val) && val !== '') {
            $(this).val(new Intl.NumberFormat().format(val));
        }
    });
});

function confirmDelete(id, bookingId) {
    Swal.fire({
        title: 'ລົບລາຍການ?',
        text: "ທ່ານຕ້ອງການລົບລາຍການນີ້ແທ້ບໍ່?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ລົບເລີຍ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete=" + id + "&booking_id=" + bookingId;
        }
    });
}
</script>

</body>
</html>

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

    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == 1;

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

        if ($is_ajax) {
            echo json_encode(['status' => 'success', 'message' => 'ບັນທຶກສຳເລັດ!']);
            exit();
        }

        $_SESSION['success'] = "ບັນທຶກຄ່າໃຊ້ຈ່າຍເພີ່ມສຳເລັດ!";
        header("Location: room_service.php?booking_id=" . $booking_id);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        if ($is_ajax) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
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

// Handle Clear All
if (isset($_GET['clear_all'])) {
    $booking_id = (int)$_GET['clear_all'];
    
    $pdo->beginTransaction();
    try {
        // 1. Get all items to restore stock
        $stmt = $pdo->prepare("SELECT prod_id, qty FROM room_services WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $items = $stmt->fetchAll();
        
        foreach($items as $item) {
            if ($item['prod_id']) {
                $restoreStock = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE prod_id = ?");
                $restoreStock->execute([$item['qty'], $item['prod_id']]);
            }
        }

        // 2. Delete all records
        $delStmt = $pdo->prepare("DELETE FROM room_services WHERE booking_id = ?");
        $delStmt->execute([$booking_id]);

        // 3. Reset food_charge in bookings
        $resetBooking = $pdo->prepare("UPDATE bookings SET food_charge = 0 WHERE id = ?");
        $resetBooking->execute([$booking_id]);

        $pdo->commit();
        $_SESSION['success'] = "ຍົກເລີກລາຍການທັງໝົດສຳເລັດ!";
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
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Select2 -->
    <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
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
                padding: 8px;
                gap: 10px;
            }
            .order-column {
                width: 100%;
                order: -1; 
                max-height: none;
                margin-bottom: 0;
            }
            .room-selector-area { padding: 8px; }
            .room-selector-area .d-flex { flex-wrap: nowrap !important; }
            .room-selector-area label { font-size: 0.65rem !important; margin-bottom: 3px !important; }
            .select2-container--bootstrap4 .select2-selection--single { 
                height: 38px !important; 
                line-height: 38px !important; 
                font-size: 0.85rem !important; 
                padding-left: 8px !important;
            }
            .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
                line-height: 36px !important;
            }
            #btnShowRoomGrid { height: 38px; width: 38px; flex-shrink: 0; }
            
            .order-list { padding: 5px; max-height: 250px; }
            .order-item { padding: 8px; margin-bottom: 5px; }
            .order-item-name { font-size: 0.85rem; }
            .order-item-price { font-size: 0.75rem; }
            
            .total-box { padding: 10px; margin-bottom: 0; }
            .total-label { font-size: 0.9rem; }
            .total-amount { font-size: 1.2rem; }
            .order-footer p { font-size: 0.7rem; margin-top: 5px !important; }

            .product-column {
                height: auto; 
                flex: none;
            }
            .product-scroll {
                grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
                padding: 10px;
                gap: 10px;
            }
            .product-img-wrapper { height: 80px; }
            .product-name { font-size: 0.78rem; min-height: 1.8rem; -webkit-line-clamp: 2; }
            .product-price { font-size: 0.85rem; }
            
            .header-section { padding: 10px 15px; }
            .header-section h4 { font-size: 0.95rem; }
            .header-section .mr-3 { display: none; }
            .cate-pill { padding: 6px 15px; font-size: 0.85rem; }
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
        
        .delete-item:hover { opacity: 1; transform: scale(1.1); }

        /* Room Grid Styles */
        .room-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }
        .room-item-card {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .room-item-card:hover {
            border-color: #007bff;
            background: #f0f7ff;
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .room-item-card.active {
            border-color: #28a745;
            background: #e8f5e9;
            box-shadow: 0 4px 12px rgba(40,167,69,0.2);
        }
        .room-item-card.active::after {
            content: "\f058";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            top: -8px;
            right: -8px;
            background: white;
            color: #28a745;
            border-radius: 50%;
            font-size: 1.2rem;
        }
        .room-item-card .room-no { font-size: 1.3rem; font-weight: 800; color: #007bff; margin-bottom: 2px; }
        .room-item-card .cust-name { font-size: 0.75rem; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        /* Ensure Select2 height matches the grid button */
        .select2-container--bootstrap4 .select2-selection--single { 
            height: 48px !important; 
            line-height: 48px !important; 
            font-size: 1.1rem !important; 
            font-weight: 700 !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            padding-left: 12px !important;
        }
    </style>
</head>
<body>

<div class="header-section d-flex justify-content-between align-items-center">
    <h4 class="m-0"><i class="fas fa-concierge-bell mr-2"></i> ບໍລິການຂອງຫ້ອງ</h4>
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
    <div class="order-column" id="orderContainer">
        <div class="room-selector-area">
            <label class="text-muted small text-uppercase font-weight-bold mb-2 d-block"><i class="fas fa-search mr-1"></i> ເລືອກຫ້ອງທີ່ສັ່ງ (Select Room)</label>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <div style="flex: 1;">
                    <select id="roomSelect" class="form-control form-control-lg border-primary select2">
                        <?php if(empty($active_bookings)): ?>
                            <option value="">-- ບໍ່ມີຫ້ອງທີ່ເຂົ້າພັກ --</option>
                        <?php endif; ?>
                        <?php foreach($active_bookings as $b): ?>
                            <option value="<?php echo $b['booking_id']; ?>" <?php echo ($selected_booking_id == $b['booking_id']) ? 'selected' : ''; ?>>
                                ຫ້ອງ <?php echo htmlspecialchars($b['room_number']); ?> - <?php echo htmlspecialchars($b['customer_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center" id="btnShowRoomGrid" style="height: 48px; width: 48px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,123,255,0.2);" title="ເບິ່ງແບບຜັງຫ້ອງ">
                    <i class="fas fa-th fa-lg"></i>
                </button>
            </div>
            <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle text-info"></i> ຄລິກທີ່ປຸ່ມສີຟ້າເພື່ອເບິ່ງຫ້ອງທັງໝົດ</p>
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
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger btn-block rounded-pill" onclick="clearAllItems(<?php echo $selected_booking_id; ?>)">
                    <i class="fas fa-trash-alt mr-1"></i> ຍົກເລີກລາຍການທັງໝົດ
                </button>
            </div>
            <p class="text-center text-muted small mt-3 mb-0">ຄລິກທີ່ສິນຄ້າເພື່ອເພີ່ມລາຍການໃສ່ຫ້ອງທັນທີ</p>
        </div>
    </div>
</div>

<!-- Manual Entry Modal -->
<!-- <div class="modal fade" id="manualModal" tabindex="-1">
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
</div> -->

<!-- Hidden Quick Add Form -->
<form id="quickAddForm" action="" method="post" style="display:none;">
    <input type="hidden" name="add_service" value="1">
    <input type="hidden" name="booking_id" id="quick_booking_id">
    <input type="hidden" name="prod_id" id="quick_prod_id">
    <input type="hidden" name="item_name" id="quick_item_name">
    <input type="hidden" name="price" id="quick_price">
    <input type="hidden" name="qty" value="1">
</form>

<!-- Room Selection Modal (Visual Grid) -->
<div class="modal fade" id="roomGridModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-th-large mr-2"></i> ເລືອກຫ້ອງທີ່ສັ່ງ (Occupied Rooms)</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light">
                <div class="mb-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="gridRoomSearch" class="form-control" placeholder="ຄົ້ນຫາເບີຫ້ອງ ຫຼື ຊື່ລູກຄ້າ...">
                    </div>
                </div>
                <div class="room-grid-container" id="roomGridItems">
                    <?php foreach($active_bookings as $b): ?>
                        <div class="room-item-card <?php echo ($selected_booking_id == $b['booking_id']) ? 'active' : ''; ?>" 
                             data-booking-id="<?php echo $b['booking_id']; ?>"
                             data-room-no="<?php echo htmlspecialchars($b['room_number']); ?>"
                             data-cust-name="<?php echo htmlspecialchars($b['customer_name']); ?>">
                            <div class="room-no"><?php echo htmlspecialchars($b['room_number']); ?></div>
                            <div class="cust-name"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(function() {
    // 1. Initialize Select2
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: "ຄົ້ນຫາເບີຫ້ອງ...",
            minimumResultsForSearch: 0
        });
    }

    // 2. Room Selection Change (Using delegation for AJAX compatibility)
    $(document).on('change', '#roomSelect', function() {
        var bid = $(this).val();
        if(bid) {
            window.location.href = 'room_service.php?booking_id=' + bid;
        }
    });

    // 3. Room Grid Modal (Using delegation for AJAX compatibility)
    $(document).on('click', '#btnShowRoomGrid', function() {
        $('#roomGridModal').modal('show');
    });

    $('.room-item-card').on('click', function() {
        var bid = $(this).data('booking-id');
        window.location.href = 'room_service.php?booking_id=' + bid;
    });

    $('#gridRoomSearch').on('keyup', function() {
        var val = $(this).val().toLowerCase();
        $('.room-item-card').each(function() {
            var roomNo = String($(this).data('room-no')).toLowerCase();
            var custName = $(this).data('cust-name').toLowerCase();
            if (roomNo.indexOf(val) > -1 || custName.indexOf(val) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // 4. Product Search & Filter
    $('#prodSearch').on('keyup', function() {
        var val = $(this).val().toLowerCase();
        $('.product-card').each(function() {
            var name = $(this).data('name').toLowerCase();
            if (name.indexOf(val) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('.cate-pill').on('click', function() {
        $('.cate-pill').removeClass('active');
        $(this).addClass('active');
        var cate = $(this).data('cate');
        if (cate === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $('.product-card[data-cate="' + cate + '"]').show();
        }
    });

    // 5. Quick Add on product card click (Now using AJAX to prevent flicker)
    $('.product-card').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var price = $(this).data('price');
        var bookingId = $('#roomSelect').val();

        if(!bookingId) {
            Swal.fire('ຜິດພາດ', 'ກະລຸນາເລືອກຫ້ອງກ່ອນ!', 'error');
            return;
        }

        $.ajax({
            url: 'room_service.php',
            method: 'POST',
            data: {
                add_service: 1,
                ajax: 1,
                booking_id: bookingId,
                prod_id: id,
                item_name: name,
                price: price,
                qty: 1
            },
            success: function(response) {
                // Refresh only the order list part
                $('#orderContainer').load('room_service.php?booking_id=' + bookingId + ' #orderContainer > *', function() {
                    // Re-initialize Select2 if it was inside the refreshed area
                    initSelect2();
                });
                
                // Show a small toast notification
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'success',
                    title: 'ເພີ່ມ ' + name + ' ແລ້ວ'
                });
            }
        });
    });

    function initSelect2() {
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: "ຄົ້ນຫາເບີຫ້ອງ...",
                minimumResultsForSearch: 0
            });
        }
    }
});

function confirmDelete(id, booking_id) {
    Swal.fire({
        title: 'ຢືນຢັນການລົບ?',
        text: "ທ່ານຕ້ອງການລົບລາຍການນີ້ແທ້ຫຼືບໍ່?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'room_service.php?delete=' + id + '&booking_id=' + booking_id;
        }
    });
}

function clearAllItems(booking_id) {
    Swal.fire({
        title: 'ຢືນຢັນການຍົກເລີກທັງໝົດ?',
        text: "ລາຍການທັງໝົດໃນຫ້ອງນີ້ຈະຖືກລຶບອອກ ແລະ ຄືນສະຕັອກສິນຄ້າທັນທີ!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການຍົກເລີກ',
        cancelButtonText: 'ກັບຄືນ'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'room_service.php?clear_all=' + booking_id;
        }
    });
}
</script>

<!-- Hidden form for Quick Add -->
<form id="formAddService" action="" method="post" style="display:none;">
    <input type="hidden" name="add_service" value="1">
    <input type="hidden" name="booking_id" id="form_booking_id">
    <input type="hidden" name="prod_id" id="form_prod_id">
    <input type="hidden" name="item_name" id="form_item_name">
    <input type="hidden" name="price" id="form_price">
    <input type="hidden" name="qty" value="1">
</form>

</body>
</html>

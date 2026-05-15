<?php
session_start();
require_once 'config/session_check.php';
require_once 'config/db.php';

// Handle Checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['checkout_pos']) || isset($_POST['cart_prod_id']))) {
    if (!empty($_POST['cart_prod_id'])) {
        $prod_ids = $_POST['cart_prod_id'];
        $qtys = $_POST['cart_qty'];
        $prices = $_POST['cart_price'];
        
        $pdo->beginTransaction();
        try {
            // Get current tax percent
            $stmtTax = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percent'");
            $tax_p = (float)($stmtTax->fetchColumn() ?: 0);

            $payment_method = $_POST['payment_method'] ?? 'ເງິນສົດ';
            $received = (float)($_POST['received'] ?? 0);
            $change_amount = (float)($_POST['change_amount'] ?? 0);

            // Generate Bill ID: YYYYNNNN (e.g. 20260001)
            $year = date('Y');
            $stmtLast = $pdo->prepare("SELECT bill_id FROM orders WHERE bill_id LIKE ? AND bill_id REGEXP '^[0-9]+$' ORDER BY bill_id DESC LIMIT 1");
            $stmtLast->execute([$year . '%']);
            $lastBill = $stmtLast->fetchColumn();

            if ($lastBill) {
                $lastNum = (int)substr($lastBill, 4);
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }
            $bill_id = $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            for ($i = 0; $i < count($prod_ids); $i++) {
                $pid = (int)$prod_ids[$i];
                $q = (int)$qtys[$i];
                $p = (float)$prices[$i];
                $subtotal = $q * $p;
                $item_tax = round($subtotal * ($tax_p / 100));
                $total_with_tax = $subtotal + $item_tax;
                
                $stmt = $pdo->prepare("INSERT INTO orders (bill_id, prod_id, o_qty, amount, received, change_amount, payment_method, o_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$bill_id, $pid, $q, $total_with_tax, $received, $change_amount, $payment_method]);
                
                $upd = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE prod_id = ?");
                $upd->execute([$q, $pid]);
            }
            $pdo->commit();
            logActivity($pdo, "ຂາຍສິນຄ້າ (POS)", "ບິນເລກທີ: $bill_id");
            $_SESSION['print_bill'] = $bill_id;
            header("Location: pos.php?status=success");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
        }
        header("Location: pos.php");
        exit();
    }
}

// Fetch available products with localized names
$current_lang = $_SESSION['lang'] ?? 'la';
$prod_name_col = "prod_name_" . $current_lang;
$cat_name_col = "name_" . $current_lang;

$stmt = $pdo->query("SELECT p.*, pc.name_la as cat_la, pc.name_en as cat_en, pc.name_cn as cat_cn 
                     FROM products p 
                     LEFT JOIN product_categories pc ON p.category = pc.name 
                     WHERE p.qty > 0 
                     ORDER BY p.category ASC, p.prod_name ASC");
$products = $stmt->fetchAll();

// Fetch categories
$stmtCat = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

// Fetch settings
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
$tax_percent = (float)($settings['tax_percent'] ?? 0);

// Fetch default currency
$stmtCur = $pdo->query("SELECT * FROM currency WHERE is_default = 1 LIMIT 1");
$default_currency = $stmtCur->fetch();
$currency_symbol = $default_currency['symbol'] ?? '₭';

// Group products by category for counting
$catCounts = [];
foreach ($products as $p) {
    $cat = $p['category'] ?: 'ອື່ນໆ';
    $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS ຂາຍສິນຄ້າ</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="sweetalert/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        if (window.top === window.self) { window.location.href = 'menu_admin.php'; }
    </script>
    <style>
        *:not(.fas):not(.far):not(.fab):not(.fa) { font-family: 'Noto Sans Lao Looped', 'Phetsarath OT', 'Saysettha OT', sans-serif !important; }
        .fas, .far, .fab, .fa { font-family: "Font Awesome 5 Free" !important; font-weight: 900 !important; }
        body { background-color: #f8f9fa; padding: 10px; }
        
        /* Category Buttons */
        .cat-btn {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            background: #fff;
            color: #555;
            white-space: nowrap;
            transition: none;
        }
        .cat-btn.active {
            background: #3498DB;
            color: #fff;
            border-color: #3498DB;
        }
        .cat-btn .badge { font-size: 0.7rem; }
        .cat-scroll { display: flex; overflow-x: auto; gap: 8px; padding-bottom: 8px; scrollbar-width: thin; }
        .cat-scroll::-webkit-scrollbar { height: 4px; }
        .cat-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
        
        /* Product Cards */
        .product-card {
            cursor: pointer;
            border: none !important;
            border-radius: 12px !important;
            overflow: hidden;
            position: relative;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
            transition: all 0.2s ease;
        }
        .product-card:active { transform: scale(0.96); background: #f8f9fa; }
        .product-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-bottom: 1px solid #f0f0f0;
        }
        .product-placeholder {
            width: 100%;
            height: 120px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-name { font-size: 0.82rem; font-weight: 600; color: #444; line-height: 1.4; min-height: 2.8em; }
        .product-price { font-size: 0.92rem; font-weight: 700; color: #2ecc71; }
        .product-stock { font-size: 0.7rem; color: #aaa; margin-top: 4px; }
        .stock-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 0.6rem;
            padding: 2px 6px;
            border-radius: 2px;
        }
        .cat-label {
            position: absolute;
            top: 5px;
            left: 5px;
            font-size: 0.55rem;
            padding: 2px 6px;
            border-radius: 2px;
            background: rgba(0,0,0,0.5);
            color: #fff;
            z-index: 5;
        }
        .qty-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74c3c;
            color: white;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            z-index: 20;
            border-radius: 0 12px 0 15px;
            box-shadow: -2px 2px 8px rgba(0,0,0,0.15);
        }
        
        /* Cart */
        .cart-container { height: 55vh; overflow-y: auto; background: #fff; border-radius: 4px; }
        .cart-item { border-bottom: 1px solid #f8f8f8; padding: 12px 10px; }
        
        /* Mobile Adjustments */
        @media (max-width: 768px) {
            body { padding: 5px; }
            h2 { font-size: 1.25rem !important; }
            .product-name { font-size: 0.75rem !important; min-height: 2em !important; }
            .product-price { font-size: 0.85rem !important; }
            .product-stock { font-size: 0.65rem !important; }
            .product-img, .product-placeholder { height: 100px !important; }
            .cat-btn { padding: 6px 12px; font-size: 0.75rem; }
            .cart-container { height: 42vh; }
        }

        /* Barcode Input Styling */
        #barcodeInput {
            height: 45px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0 8px 8px 0 !important;
            transition: all 0.3s ease;
            background-color: #fff;
        }
        #barcodeInput:focus {
            background-color: #fff9db;
            border-color: #f1c40f;
            box-shadow: 0 0 10px rgba(241, 196, 15, 0.3);
        }
        .barcode-group .input-group-text {
            border-radius: 8px 0 0 8px !important;
            padding: 0 15px;
            font-size: 1.2rem;
            background: linear-gradient(135deg, #3498DB, #2980B9);
            border: none;
        }
        .barcode-group {
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #3498DB;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success' && isset($_SESSION['print_bill'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'ຊຳລະເງິນສຳເລັດ!',
                    text: 'ທ່ານຕ້ອງການພິມໃບບິນຫຼືບໍ່?',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-print"></i> ພິມໃບບິນ',
                    cancelButtonText: 'ປິດ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open('print_receipt.php?bill_id=<?php echo $_SESSION['print_bill']; ?>', '_blank');
                    }
                });
            });
        </script>
    <?php unset($_SESSION['print_bill']); endif; ?>

    <div class="row mb-3 align-items-center">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-cash-register text-primary"></i> ຈຸດຂາຍສິນຄ້າ (POS)</h2>
        </div>
    </div>

    <div class="row">
        <!-- Products Grid (Left) -->
        <div class="col-lg-8 col-md-7 mb-3">
            <!-- Search & Barcode Area -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="input-group barcode-group shadow-sm h-100">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-white"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="mainSearch" class="form-control" placeholder="ຄົ້ນຫາສິນຄ້າ ຫຼື ສະແກນບາໂຄ້ດ (Search or Scan Barcode)..." autofocus autocomplete="off" style="height: 50px; font-size: 1.1rem;">
                    </div>
                </div>
            </div>

            <!-- Category Filter Buttons -->
            <div class="mb-3">
                <div class="cat-scroll">
                    <button class="cat-btn active" data-cat="all">
                        <i class="fas fa-th-large mr-1"></i> ທັງໝົດ
                        <span class="badge badge-danger ml-1"><?php echo count($products); ?></span>
                    </button>
                    <?php 
                    // $catIcons = [
                    //     'ເຄື່ອງດື່ມ' => 'fa-glass-cheers',
                    //     'ອາຫານ' => 'fa-utensils',
                    //     'ຂະໜົມ' => 'fa-cookie-bite',
                    // ];
                    foreach($categories as $cat): 
                        $catName = $cat['name'];
                        // $icon = $catIcons[$catName] ?? 'fa-tag';
                        $count = $catCounts[$catName] ?? 0;
                        if ($count == 0) continue;
                    ?>
                        <button class="cat-btn" data-cat="<?php echo htmlspecialchars($catName); ?>">
                            <i class="fas <?php echo $icon; ?> mr-1"></i>
                            <?php echo htmlspecialchars($cat[$cat_name_col] ?: $catName); ?>
                            <span class="badge badge-danger ml-1"><?php echo $count; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="card border-0 shadow-sm" style="border-radius: 4px;">
                <div class="card-body p-2" style="max-height: 70vh; overflow-y: auto;" id="productGrid">
                    <div class="row" id="productList">
                        <div id="noResultsMsg" class="col-12 text-center py-5 text-muted" style="display: none;">
                            <i class="fas fa-search fa-3x mb-3 d-block" style="color: #ddd;"></i>
                            <h5>ບໍ່ມີສິນຄ້າທີ່ທ່ານຄົ້ນຫາ</h5>
                        </div>
                        <?php foreach($products as $p): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-6 mb-3 product-item" data-category="<?php echo htmlspecialchars($p['category']); ?>">
                                <div class="card product-card shadow-sm h-100" id="prod-card-<?php echo $p['prod_id']; ?>" onclick="addToCart(<?php echo $p['prod_id']; ?>, '<?php echo htmlspecialchars(addslashes($p[$prod_name_col] ?: $p['prod_name'])); ?>', <?php echo $p['sprice']; ?>, <?php echo $p['qty']; ?>, '<?php echo $p['image']; ?>', '<?php echo htmlspecialchars($p['prod_code']); ?>')">
                                    <span class="qty-badge" id="qty-badge-<?php echo $p['prod_id']; ?>" style="display: none;">0</span>
                                    <!-- Category Label -->
                                    <span class="cat-label"><?php echo htmlspecialchars($p['cat_'.$current_lang] ?? $p['category'] ?? 'ອື່ນໆ'); ?></span>
                                    <!-- Stock Badge -->
                                    <?php if($p['qty'] <= 10): ?>
                                        <span class="stock-badge badge badge-danger">ໃກ້ໝົດ</span>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($p['image'])): ?>
                                        <img src="assets/img/products/<?php echo htmlspecialchars($p['image']); ?>" class="product-img" alt="<?php echo htmlspecialchars($p['prod_name']); ?>">
                                    <?php else: ?>
                                        <div class="product-placeholder">
                                            <?php 
                                                $catIcon = $catIcons[$p['category']] ?? 'fa-box';
                                            ?>
                                            <i class="fas <?php echo $catIcon; ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body text-center">
                                        <div class="product-name text-truncate"><?php echo htmlspecialchars($p[$prod_name_col] ?: $p['prod_name']); ?></div>
                                        <div class="text-muted small mb-1"><?php echo htmlspecialchars($p['prod_code'] ?? '-'); ?></div>
                                        <div class="product-price mt-1"><?php echo number_format($p['sprice']); ?> <?php echo $currency_symbol; ?></div>
                                        <div class="product-stock">ເຫຼືອ: <?php echo number_format($p['qty']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($products)): ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                <h5>ບໍ່ມີສິນຄ້າພ້ອມຂາຍ</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart (Right) -->
        <div class="col-lg-4 col-md-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 4px; display: flex; flex-direction: column;">
                <div class="card-header bg-white d-flex align-items-center" style="border-radius: 4px 4px 0 0;">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-shopping-cart text-primary"></i> ກະຕ່າ</h5>
                    <button class="btn btn-xs btn-outline-danger ml-auto" onclick="clearCart()" style="font-size: 0.75rem;"><i class="fas fa-trash-alt"></i> ລ້າງທັງໝົດ</button>
                </div>
                <div class="card-body p-0 cart-container" id="cartItems" style="flex: 1;">
                    <div class="text-center text-muted py-5" id="emptyCartMsg">
                        <i class="fas fa-shopping-basket fa-3x mb-3 d-block" style="color: #ddd;"></i>
                        <p class="mb-0">ກົດສິນຄ້າເພື່ອເພີ່ມ</p>
                    </div>
                </div>
                <div class="card-footer bg-white border-top" style="border-radius: 0 0 4px 4px;">
                    <div class="px-2 mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">ລວມຍ່ອຍ:</span>
                            <span class="font-weight-bold"><span id="cartSubtotal">0</span> <?php echo $currency_symbol; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">ພາສີ (<?php echo $tax_percent; ?>%):</span>
                            <span class="font-weight-bold text-info"><span id="cartTax">0</span> <?php echo $currency_symbol; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">ລວມທັງໝົດ:</span>
                            <span class="font-weight-bold text-danger" style="font-size: 1.4rem;"><span id="cartTotal">0</span> <?php echo $currency_symbol; ?></span>
                        </div>
                    </div>
                    <form action="" method="post" id="posForm">
                        <div id="hiddenInputs"></div>
                        <button type="submit" name="checkout_pos" class="btn btn-success btn-lg btn-block" id="btnCheckout" disabled style="border-radius: 4px; font-size: 1rem;">
                            <i class="fas fa-money-bill-wave"></i> ຊຳລະເງິນ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
let cart = JSON.parse(localStorage.getItem('pos_cart')) || {};
const taxPercent = <?php echo $tax_percent; ?>;
const currencySymbol = '<?php echo $currency_symbol; ?>';
const currentLang = '<?php echo $current_lang; ?>';
const allProducts = <?php 
    // Prepare products with localized names for JS
    $jsProducts = [];
    foreach($products as $p) {
        $p['display_name'] = $p[$prod_name_col] ?: $p['prod_name'];
        $jsProducts[] = $p;
    }
    echo json_encode($jsProducts); 
?>;

$(document).ready(function() {
    renderCart(); // Restore cart from localStorage
    // Combined Search & Barcode Logic
    $('#mainSearch').focus();
    $(document).on('click', function() {
        if ($('.modal.show').length === 0 && !$(event.target).is('input, textarea, select')) {
            $('#mainSearch').focus();
        }
    });

    $('#mainSearch').on('input', function() {
        let val = $(this).val().trim();
        
        if (val === '') {
            $('.product-item').show();
            $('#noResultsMsg').hide();
            return;
        }

        // 1. Try barcode match first (Exact match)
        let product = allProducts.find(p => p.prod_code === val);
        if (product) {
            addToCart(product.prod_id, product.display_name, product.sprice, product.qty, product.image, product.prod_code);
            $(this).val(''); // Clear for next scan
            $('.product-item').show(); // Reset filter
            $('#noResultsMsg').hide();
            return;
        }

        // 2. Otherwise treat as name search filter
        var visibleCount = 0;
        var searchVal = val.toLowerCase();
        $('.product-item').each(function() {
            var name = $(this).find('.product-name').text().toLowerCase();
            if (name.indexOf(searchVal) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0) {
            $('#noResultsMsg').show();
        } else {
            $('#noResultsMsg').hide();
        }
    });

    // Also handle Enter key for barcode scanners that append Enter
    $('#mainSearch').on('keypress', function(e) {
        if (e.which === 13) {
            $(this).trigger('input');
        }
    });
});

// Category filter
$('.cat-btn').on('click', function() {
    $('.cat-btn').removeClass('active');
    $(this).addClass('active');
    var cat = $(this).data('cat');
    
    if (cat === 'all') {
        $('.product-item').show();
    } else {
        $('.product-item').hide();
        $('.product-item[data-category="' + cat + '"]').show();
    }
});

function addToCart(id, name, price, maxQty, image, code) {
    if (cart[id]) {
        if (cart[id].qty < maxQty) {
            cart[id].qty++;
        } else {
            Swal.fire({ icon: 'warning', title: 'ສິນຄ້າໝົດ', text: 'ມີໃນສະຕັອກພຽງ ' + maxQty + ' ຊິ້ນ', timer: 1500, showConfirmButton: false });
            return;
        }
    } else {
        if (maxQty > 0) {
            cart[id] = { name: name, price: price, qty: 1, maxQty: maxQty, image: image, code: code };
        }
    }
    // Mini animation toast
    Swal.fire({ icon: 'success', title: name, toast: true, position: 'top-end', showConfirmButton: false, timer: 800, timerProgressBar: true });
    renderCart();
}

function updateQty(id, delta) {
    if (cart[id]) {
        let newQty = cart[id].qty + delta;
        if (newQty <= 0) {
            delete cart[id];
        } else if (newQty > cart[id].maxQty) {
            Swal.fire({ icon: 'warning', title: 'ໝົດສະຕັອກ', timer: 1200, showConfirmButton: false });
        } else {
            cart[id].qty = newQty;
        }
        renderCart();
    }
}

function removeItem(id) {
    delete cart[id];
    renderCart();
}

function clearCart() {
    if (Object.keys(cart).length === 0) return;
    Swal.fire({
        title: 'ລ້າງກະຕ່າ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ລ້າງເລີຍ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) { cart = {}; localStorage.removeItem('pos_cart'); renderCart(); }
    });
}

function renderCart() {
    // Save to localStorage for persistence
    localStorage.setItem('pos_cart', JSON.stringify(cart));
    let html = '';
    let total = 0;
    let hasItems = false;
    let hiddenHtml = '';
    let itemCount = 0;

    // Reset all badges first
    $('.qty-badge').hide().text('0');

    for (let id in cart) {
        hasItems = true;
        itemCount++;
        let item = cart[id];
        let subtotal = item.price * item.qty;
        total += subtotal;

        // Update badge on product card
        $('#qty-badge-' + id).text(item.qty).show();

        let imageHtml = item.image 
            ? `<img src="assets/img/products/${item.image}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; margin-right: 10px;" class="border shadow-sm">`
            : `<div class="bg-light d-flex align-items-center justify-content-center border" style="width: 45px; height: 45px; border-radius: 6px; margin-right: 10px; color: #ccc;"><i class="fas fa-box fa-xs"></i></div>`;

        html += `
        <div class="cart-item px-2">
            <div class="d-flex align-items-center mb-2">
                ${imageHtml}
                <div style="flex:1;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong style="font-size: 0.82rem; color: #333;" class="text-truncate d-inline-block" style="max-width: 120px;">${item.name}</strong>
                            <div class="text-muted small" style="font-size: 0.65rem;">Code: ${item.code || '-'}</div>
                        </div>
                        <div class="text-success font-weight-bold" style="font-size: 0.85rem;">${subtotal.toLocaleString('en-US')} ${currencySymbol}</div>
                    </div>
                    <div class="text-muted d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
                        <span>${item.price.toLocaleString('en-US')} ${currencySymbol} × ${item.qty}</span>
                        <button class="btn btn-sm p-0 text-danger" onclick="removeItem(${id})" style="font-size: 0.7rem;"><i class="fas fa-times-circle"></i></button>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-danger px-2" onclick="updateQty(${id}, -1)"><i class="fas fa-minus"></i></button>
                    <button class="btn btn-light px-3 font-weight-bold" disabled>${item.qty}</button>
                    <button class="btn btn-outline-success px-2" onclick="updateQty(${id}, 1)"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </div>
        `;

        hiddenHtml += `
            <input type="hidden" name="cart_prod_id[]" value="${id}">
            <input type="hidden" name="cart_qty[]" value="${item.qty}">
            <input type="hidden" name="cart_price[]" value="${item.price}">
        `;
    }

    if (!hasItems) {
        $('#cartItems').html(`
            <div class="text-center text-muted py-5">
                <i class="fas fa-shopping-basket fa-3x mb-3 d-block" style="color: #ddd;"></i>
                <p class="mb-0">ກົດສິນຄ້າເພື່ອເພີ່ມ</p>
            </div>
        `);
        $('#btnCheckout').prop('disabled', true).html('<i class="fas fa-money-bill-wave"></i> ຊຳລະເງິນ');
    } else {
        $('#cartItems').html(html);
        $('#btnCheckout').prop('disabled', false).html('<i class="fas fa-money-bill-wave"></i> ຊຳລະເງິນ (' + itemCount + ' ລາຍການ)');
    }

    let taxAmount = Math.round(total * (taxPercent / 100));
    let grandTotal = total + taxAmount;

    $('#cartSubtotal').text(total.toLocaleString('en-US'));
    $('#cartTax').text(taxAmount.toLocaleString('en-US'));
    $('#cartTotal').text(grandTotal.toLocaleString('en-US'));
    $('#hiddenInputs').html(hiddenHtml);
}

// Payment confirmation
$('#posForm').on('submit', function(e) {
    e.preventDefault();
    var totalStr = $('#cartTotal').text();
    var totalVal = parseFloat(totalStr.replace(/,/g, '')) || 0;
    
    Swal.fire({
        title: 'ຊຳລະເງິນ',
        html: `
            <div class="text-left mb-3">
                <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                    <span class="font-weight-bold">ຍອດລວມທັງໝົດ:</span>
                    <strong class="text-danger" style="font-size: 1.5rem;">${totalStr} ${currencySymbol}</strong>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">ວິທີຊຳລະ</label>
                    <select id="swal_payment_method" class="form-control">
                        <option value="ເງິນສົດ">ເງິນສົດ</option>
                        <option value="ເງິນໂອນ">ເງິນໂອນ</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">ຮັບເງິນມາ</label>
                    <div class="input-group">
                        <input type="text" id="swal_received" class="form-control text-right font-weight-bold" placeholder="0">
                        <div class="input-group-append">
                            <button type="button" id="swal_btn_full" class="btn btn-primary btn-sm px-2">ຮັບເຕັມ</button>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold">ເງິນທອນ</label>
                    <input type="text" id="swal_change" class="form-control text-right text-danger font-weight-bold" value="0" readonly>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ຢືນຢັນການຂາຍ',
        cancelButtonText: 'ຍົກເລີກ',
        didOpen: () => {
            const popup = Swal.getPopup();
            const receivedInput = popup.querySelector('#swal_received');
            const changeInput = popup.querySelector('#swal_change');
            const methodSelect = popup.querySelector('#swal_payment_method');
            const fullBtn = popup.querySelector('#swal_btn_full');
            
            const calc = () => {
                let r = parseFloat(receivedInput.value.replace(/,/g, '')) || 0;
                let c = r - totalVal;
                if (c < 0) c = 0;
                changeInput.value = c.toLocaleString('en-US');
            };

            receivedInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/[^0-9]/g, '');
                if (val !== '') {
                    e.target.value = parseInt(val).toLocaleString('en-US');
                }
                calc();
            });

            methodSelect.addEventListener('change', (e) => {
                if (e.target.value === 'ເງິນໂອນ') {
                    receivedInput.value = totalVal.toLocaleString('en-US');
                    receivedInput.readOnly = true;
                    fullBtn.disabled = true;
                } else {
                    receivedInput.value = '';
                    receivedInput.readOnly = false;
                    fullBtn.disabled = false;
                }
                calc();
            });

            fullBtn.addEventListener('click', () => {
                receivedInput.value = totalVal.toLocaleString('en-US');
                calc();
            });

            receivedInput.focus();
        },
        preConfirm: () => {
            const popup = Swal.getPopup();
            const receivedStr = popup.querySelector('#swal_received').value;
            const received = parseFloat(receivedStr.replace(/,/g, '')) || 0;
            const method = popup.querySelector('#swal_payment_method').value;
            const change = parseFloat(popup.querySelector('#swal_change').value.replace(/,/g, '')) || 0;

            if (received < totalVal && method === 'ເງິນສົດ') {
                Swal.showValidationMessage('ຍອດເງິນບໍ່ພຽງພໍ!');
                return false;
            }
            return { received, method, change };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $('#hiddenInputs').append(`
                <input type="hidden" name="payment_method" value="${result.value.method}">
                <input type="hidden" name="received" value="${result.value.received}">
                <input type="hidden" name="change_amount" value="${result.value.change}">
                <input type="hidden" name="checkout_pos" value="1">
            `);
            localStorage.removeItem('pos_cart'); // Clear after success
            $('#posForm')[0].submit();
        }
    });
});

// Print trigger on success
<?php if(isset($_GET['status']) && $_GET['status'] == 'success' && isset($_SESSION['print_bill'])): ?>
    var printUrl = 'print_receipt.php?bill_id=<?php echo $_SESSION['print_bill']; ?>';
    
    // Create hidden iframe for printing
    var printFrame = document.createElement('iframe');
    printFrame.style.display = 'none';
    printFrame.src = printUrl;
    document.body.appendChild(printFrame);
    
    Swal.fire({
        title: 'ຂາຍສຳເລັດແລ້ວ!',
        text: 'ລະບົບກຳລັງສັ່ງພິມໃບບິນໃຫ້ທ່ານ...',
        confirmButtonColor: '#28a745',
        padding: '2rem',
        timer: 3000,
        showConfirmButton: false,
        customClass: {
            title: 'text-success font-weight-bold',
            popup: 'rounded-lg'
        }
    });
    <?php unset($_SESSION['print_bill']); ?>
<?php endif; ?>
</script>
</body>
</html>

<?php
session_start();
require_once 'config/db.php';

// Handle Checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout_pos'])) {
    if (!empty($_POST['cart_prod_id'])) {
        $prod_ids = $_POST['cart_prod_id'];
        $qtys = $_POST['cart_qty'];
        $prices = $_POST['cart_price'];
        
        $pdo->beginTransaction();
        try {
            for ($i = 0; $i < count($prod_ids); $i++) {
                $pid = (int)$prod_ids[$i];
                $q = (int)$qtys[$i];
                $p = (float)$prices[$i];
                $total_amount = $q * $p;
                
                $stmt = $pdo->prepare("INSERT INTO orders (prod_id, o_qty, amount, o_date) VALUES (?, ?, ?, CURDATE())");
                $stmt->execute([$pid, $q, $total_amount]);
                
                $upd = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE prod_id = ?");
                $upd->execute([$q, $pid]);
            }
            $pdo->commit();
            $_SESSION['success'] = "ຊຳລະເງິນສຳເລັດແລ້ວ!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
        }
        header("Location: pos.php");
        exit();
    }
}

// Fetch available products
$stmt = $pdo->query("SELECT * FROM products WHERE qty > 0 ORDER BY category ASC, prod_name ASC");
$products = $stmt->fetchAll();

// Fetch categories
$stmtCat = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; background-color: #f8f9fa; padding: 10px; }
        
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
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: none;
        }
        .product-card:active { background: #f0f0f0; }
        .product-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        .product-placeholder {
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #ccc;
            background: #fdfdfd;
        }
        .product-card .card-body { padding: 10px; }
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
        }
        
        /* Cart */
        .cart-container { height: 55vh; overflow-y: auto; background: #fff; border-radius: 4px; }
        .cart-item { border-bottom: 1px solid #f8f8f8; padding: 12px 10px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            h2 { font-size: 1.15rem; }
            .product-placeholder, .product-img { height: 85px; }
            .product-name { font-size: 0.75rem; }
            .product-price { font-size: 0.82rem; }
            .cat-btn { padding: 6px 12px; font-size: 0.78rem; }
            .cart-container { height: 42vh; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if(isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'success', title: 'ສຳເລັດ', text: '<?php echo $_SESSION['success']; ?>', showConfirmButton: false, timer: 2000 });
            });
        </script>
    <?php unset($_SESSION['success']); endif; ?>

    <div class="row mb-2">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-cash-register text-primary"></i> ຈຸດຂາຍສິນຄ້າ (POS)</h2>
        </div>
    </div>

    <div class="row">
        <!-- Products Grid (Left) -->
        <div class="col-lg-8 col-md-7 mb-3">
            <!-- Category Filter Buttons -->
            <div class="mb-3">
                <div class="cat-scroll">
                    <button class="cat-btn active" data-cat="all">
                        <i class="fas fa-th-large mr-1"></i> ທັງໝົດ
                        <span class="badge badge-light ml-1"><?php echo count($products); ?></span>
                    </button>
                    <?php 
                    $catIcons = [
                        'ເຄື່ອງດື່ມ' => 'fa-glass-cheers',
                        'ອາຫານ' => 'fa-utensils',
                        'ຂະໜົມ' => 'fa-cookie-bite',
                        'ເບຍ' => 'fa-beer',
                        'ນ້ຳ' => 'fa-tint',
                        'ຢາສູບ' => 'fa-smoking',
                    ];
                    foreach($categories as $cat): 
                        $catName = $cat['name'];
                        $icon = $catIcons[$catName] ?? 'fa-tag';
                        $count = $catCounts[$catName] ?? 0;
                        if ($count == 0) continue;
                    ?>
                        <button class="cat-btn" data-cat="<?php echo htmlspecialchars($catName); ?>">
                            <i class="fas <?php echo $icon; ?> mr-1"></i>
                            <?php echo htmlspecialchars($catName); ?>
                            <span class="badge badge-light ml-1"><?php echo $count; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="card border-0 shadow-sm" style="border-radius: 4px;">
                <div class="card-body p-2" style="max-height: 70vh; overflow-y: auto;" id="productGrid">
                    <div class="row" id="productList">
                        <?php foreach($products as $p): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-4 mb-3 product-item" data-category="<?php echo htmlspecialchars($p['category']); ?>">
                                <div class="card product-card shadow-sm h-100" onclick="addToCart(<?php echo $p['prod_id']; ?>, '<?php echo htmlspecialchars(addslashes($p['prod_name'])); ?>', <?php echo $p['sprice']; ?>, <?php echo $p['qty']; ?>)">
                                    <!-- Category Label -->
                                    <span class="cat-label"><?php echo htmlspecialchars($p['category'] ?: 'ອື່ນໆ'); ?></span>
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
                                        <div class="product-name"><?php echo htmlspecialchars($p['prod_name']); ?></div>
                                        <div class="product-price mt-1"><?php echo number_format($p['sprice']); ?> ₭</div>
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
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius: 4px 4px 0 0;">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-shopping-cart text-primary"></i> ກະຕ່າ</h5>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearCart()"><i class="fas fa-trash-alt"></i> ລ້າງ</button>
                </div>
                <div class="card-body p-0 cart-container" id="cartItems" style="flex: 1;">
                    <div class="text-center text-muted py-5" id="emptyCartMsg">
                        <i class="fas fa-shopping-basket fa-3x mb-3 d-block" style="color: #ddd;"></i>
                        <p class="mb-0">ກົດສິນຄ້າເພື່ອເພີ່ມ</p>
                    </div>
                </div>
                <div class="card-footer bg-white" style="border-radius: 0 0 4px 4px;">
                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <span class="font-weight-bold" style="font-size: 1rem;">ລວມທັງໝົດ:</span>
                        <span class="font-weight-bold text-danger" style="font-size: 1.3rem;"><span id="cartTotal">0</span> ₭</span>
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
let cart = {};

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

function addToCart(id, name, price, maxQty) {
    if (cart[id]) {
        if (cart[id].qty < maxQty) {
            cart[id].qty++;
        } else {
            Swal.fire({ icon: 'warning', title: 'ສິນຄ້າໝົດ', text: 'ມີໃນສະຕັອກພຽງ ' + maxQty + ' ຊິ້ນ', timer: 1500, showConfirmButton: false });
            return;
        }
    } else {
        if (maxQty > 0) {
            cart[id] = { name: name, price: price, qty: 1, maxQty: maxQty };
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
        if (result.isConfirmed) { cart = {}; renderCart(); }
    });
}

function renderCart() {
    let html = '';
    let total = 0;
    let hasItems = false;
    let hiddenHtml = '';
    let itemCount = 0;

    for (let id in cart) {
        hasItems = true;
        itemCount++;
        let item = cart[id];
        let subtotal = item.price * item.qty;
        total += subtotal;

        html += `
        <div class="cart-item px-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div style="flex:1;">
                    <strong style="font-size: 0.88rem;">${item.name}</strong>
                    <div class="text-muted" style="font-size: 0.75rem;">${item.price.toLocaleString('en-US')} ₭ × ${item.qty}</div>
                </div>
                <div class="text-right">
                    <div class="text-success font-weight-bold" style="font-size: 0.9rem;">${subtotal.toLocaleString('en-US')} ₭</div>
                    <button class="btn btn-sm p-0 text-danger" onclick="removeItem(${id})" style="font-size: 0.7rem;"><i class="fas fa-trash"></i></button>
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

    $('#cartTotal').text(total.toLocaleString('en-US'));
    $('#hiddenInputs').html(hiddenHtml);
}

// Payment confirmation
$('#posForm').on('submit', function(e) {
    e.preventDefault();
    var total = $('#cartTotal').text();
    Swal.fire({
        title: '<i class="fas fa-cash-register text-success"></i> ຮັບຊຳລະເງິນ?',
        html: '<div style="font-size:1.4rem; font-weight:700; color:#28a745;">' + total + ' ₭</div>',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: '<i class="fas fa-check"></i> ຢືນຢັນ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) { this.submit(); }
    });
});
</script>
</body>
</html>

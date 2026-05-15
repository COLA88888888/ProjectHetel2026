<?php
session_start();
require_once 'config/session_check.php';
require_once 'config/db.php';

// Language Selection Logic
$current_lang = $_SESSION['lang'] ?? 'la';
$lang_file = "lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include "lang/la.php";
}

// Handle Checkout Confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_checkout'])) {
    $booking_id = (int)$_POST['booking_id'];
    $room_id = (int)$_POST['room_id'];
    $payment_method = $_POST['payment_method'];
    $amount_received = (float)str_replace(',', '', $_POST['amount_received']);
    $change_amount = (float)str_replace(',', '', $_POST['change_amount']);
    
    // Update booking status and payment info
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Completed', payment_method = ?, amount_received = ?, change_amount = ? WHERE id = ?");
    if ($stmt->execute([$payment_method, $amount_received, $change_amount, $booking_id])) {
        // Update room status to Available and Needs Cleaning
        $pdo->prepare("UPDATE rooms SET status = 'Available', housekeeping_status = 'Cleaning' WHERE id = ?")->execute([$room_id]);
        
        $_SESSION['print_booking'] = $booking_id;
        
        logActivity($pdo, "Check-out ສຳເລັດ", "Booking ID: $booking_id, ວິທີຊຳລະ: $payment_method");
        
        header("Location: checkout.php?status=success");
        exit();
    } else {
        $_SESSION['error'] = $lang['checkout_error'] ?? "ເກີດຂໍ້ຜິດພາດໃນການ Check-out!";
    }
}

// Fetch active bookings for the list with localized room type
$current_lang = $_SESSION['lang'] ?? 'la';
$room_type_col = "room_type_name_" . $current_lang;

$stmt = $pdo->query("
    SELECT b.*, r.room_number, rt.$room_type_col as room_type_localized, rt.room_type_name as room_type_base
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN room_types rt ON r.room_type = rt.room_type_name
    WHERE b.status = 'Occupied'
    ORDER BY r.room_number ASC
");
$active_bookings = $stmt->fetchAll();

$selected_booking = null;
if (isset($_GET['booking_id'])) {
    $bid = (int)$_GET['booking_id'];
    foreach ($active_bookings as $b) {
        if ($b['id'] == $bid) {
            $selected_booking = $b;
            break;
        }
    }
    
    // If selected booking found, get detailed room services
    if ($selected_booking) {
        $svcStmt = $pdo->prepare("
            SELECT rs.item_name, rs.price, SUM(rs.qty) as qty, SUM(rs.total_price) as total_price,
                   p.prod_name_la, p.prod_name_en, p.prod_name_cn
            FROM room_services rs 
            LEFT JOIN products p ON rs.prod_id = p.prod_id
            WHERE rs.booking_id = ? 
            GROUP BY rs.prod_id, rs.item_name, rs.price 
            ORDER BY rs.id ASC
        ");
        $svcStmt->execute([$bid]);
        $room_services = $svcStmt->fetchAll();
        
        // Fetch Tax Percent
        $stmtTax = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percent'");
        $tax_percent = (float)($stmtTax->fetchColumn() ?: 0);

        // Current lang cols
        $prod_name_col = "prod_name_" . $current_lang;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['checkout_system_title']; ?></title>
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
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; padding: 20px; }
        .invoice-title { font-size: 1.5rem; font-weight: bold; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 20px; }
        .total-row { font-size: 1.25rem; font-weight: bold; background-color: #f8f9fa; }
        .grand-total { font-size: 1.5rem; font-weight: bold; color: #dc3545; }
        
        /* Modern Scrollbar for Room List */
        #room_list_container {
            max-height: calc(100vh - 230px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px;
        }
        
        #room_list_container::-webkit-scrollbar {
            width: 6px;
        }
        #room_list_container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        #room_list_container::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
            transition: background 0.3s;
        }
        #room_list_container::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        .room-item a {
            transition: all 0.2s;
            margin: 2px 5px;
            border-radius: 8px !important;
        }
        .room-item a:hover {
            background-color: #eef2f7 !important;
            transform: translateX(3px);
        }
        .room-item a.active {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
            transform: none !important;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            h2 { font-size: 1.3rem !important; }
            h4 { font-size: 1rem !important; }
            .invoice-title { font-size: 1.2rem; }
            .total-row { font-size: 1.1rem; }
            .grand-total { font-size: 1.3rem; }
            .card-title { font-size: 1rem !important; }
            .table-responsive { font-size: 0.85rem; }
            .btn-lg { padding: 8px 16px; font-size: 1rem; }
            #room_list_container { max-height: 400px; }
        }
    </style>
    <script>
        // Guard: If not in iframe, redirect to menu_admin
        if (window.top === window.self) {
            window.location.href = 'menu_admin.php';
        }
    </script>
</head>
<body>

<div class="container-fluid">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success' && isset($_SESSION['print_booking'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '<?php echo $lang['checkout_success'] ?? 'Check-out Success!'; ?>',
                    text: '<?php echo $lang['print_bill_question'] ?? 'Do you want to print the receipt?'; ?>',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-print"></i> <?php echo $lang['print_bill']; ?>',
                    cancelButtonText: '<?php echo $lang['close']; ?>'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var printUrl = 'print_room_receipt.php?booking_id=<?php echo $_SESSION['print_booking']; ?>';
                        var printFrame = document.createElement('iframe');
                        printFrame.style.display = 'none';
                        printFrame.src = printUrl;
                        document.body.appendChild(printFrame);
                    }
                });
            });
        </script>
    <?php unset($_SESSION['print_booking']); endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo $lang['error_label'] ?? 'ຜິດພາດ'; ?>',
                    text: '<?php echo $_SESSION['error']; ?>',
                    confirmButtonText: '<?php echo $lang['ok']; ?>'
                });
            });
        </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-sign-out-alt"></i> <?php echo $lang['checkout_system_title']; ?></h2>
        </div>
    </div>

    <div class="row">
        <!-- List of Occupied Rooms -->
        <div class="col-md-4">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header p-2">
                    <div class="input-group">
                        <input type="text" id="room_search_input" class="form-control form-control-sm" placeholder="<?php echo $lang['search_room_guest']; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search text-primary"></i></span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        <?php if (count($active_bookings) > 0): ?>
                            <div id="room_list_container">
                            <?php foreach($active_bookings as $b): ?>
                                <li class="nav-item room-item">
                                    <a href="?booking_id=<?php echo $b['id']; ?>" class="nav-link <?php echo (isset($_GET['booking_id']) && $_GET['booking_id'] == $b['id']) ? 'active bg-primary' : ''; ?>" style="border-bottom: 1px solid #eee;">
                                        <i class="fas fa-door-closed mr-2"></i> <?php echo $lang['room']; ?> <strong class="room-num"><?php echo htmlspecialchars($b['room_number']); ?></strong>
                                        <span class="float-right badge <?php echo (isset($_GET['booking_id']) && $_GET['booking_id'] == $b['id']) ? 'badge-light' : 'badge-primary'; ?>"><?php echo $lang['select'] ?? 'Select'; ?></span>
                                        <div class="small mt-1 text-muted <?php echo (isset($_GET['booking_id']) && $_GET['booking_id'] == $b['id']) ? 'text-white' : ''; ?>">
                                            <i class="fas fa-user"></i> <span class="guest-name"><?php echo htmlspecialchars($b['customer_name']); ?></span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <li class="nav-item p-3 text-center text-muted">
                                <?php echo $lang['no_occupied_rooms_msg']; ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Checkout Invoice Details -->
        <div class="col-md-8">
            <?php if ($selected_booking): ?>
                <div class="card shadow-sm border-success">
                    <div class="card-body p-4">
                        <div class="invoice-title text-success">
                            <i class="fas fa-file-invoice-dollar"></i> <?php echo $lang['invoice_title']; ?>
                            <span class="float-right font-weight-normal text-dark" style="font-size: 1rem;">
                                <?php echo $lang['date_label']; ?>: <?php echo date('d/m/Y'); ?>
                            </span>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h6 class="text-muted mb-1"><?php echo $lang['customer_info']; ?>:</h6>
                                <h5><strong><?php echo htmlspecialchars($selected_booking['customer_name']); ?></strong></h5>
                                <div><?php echo $lang['phone']; ?>: <?php echo htmlspecialchars($selected_booking['customer_phone']); ?></div>
                                <?php if($selected_booking['address']): ?>
                                    <div><?php echo $lang['address']; ?>: <?php echo htmlspecialchars($selected_booking['address']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-sm-6 text-right">
                                <h6 class="text-muted mb-1"><?php echo $lang['stay_info']; ?>:</h6>
                                <h5><strong><?php echo $lang['room']; ?> <?php echo htmlspecialchars($selected_booking['room_number']); ?></strong> (<?php echo htmlspecialchars($selected_booking['room_type_localized'] ?: $selected_booking['room_type_base']); ?>)</h5>
                                <div>Check-in: <span class="text-success"><?php echo date('d/m/Y', strtotime($selected_booking['check_in_date'])); ?></span></div>
                                <?php 
                                    $today = date('Y-m-d');
                                    $checkout_date = $selected_booking['check_out_date'];
                                    $date_warning = "";
                                    if ($today < $checkout_date) {
                                        $date_warning = '<span class="badge badge-warning ml-2"><i class="fas fa-exclamation-triangle"></i> ' . $lang['not_due_yet'] . '</span>';
                                    } elseif ($today > $checkout_date) {
                                        $date_warning = '<span class="badge badge-danger ml-2"><i class="fas fa-clock"></i> ' . $lang['overdue'] . '</span>';
                                    }
                                ?>
                                <div>Check-out: <span class="text-danger"><?php echo date('d/m/Y', strtotime($selected_booking['check_out_date'])); ?></span> <?php echo $date_warning; ?></div>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th><?php echo $lang['item_label']; ?></th>
                                        <th class="text-center"><?php echo $lang['total'] ?? 'Qty'; ?></th>
                                        <th class="text-right"><?php echo $lang['price']; ?></th>
                                        <th class="text-right"><?php echo $lang['total']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Room Charge -->
                                    <tr>
                                        <td><strong><?php echo $lang['room_charge']; ?></strong></td>
                                        <td class="text-center">-</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right font-weight-bold text-primary"><?php echo number_format($selected_booking['total_price']); ?> <?php echo $defCurr['currency_name']; ?></td>
                                    </tr>
                                    
                                    <!-- Food & Services -->
                                    <?php if(count($room_services) > 0): ?>
                                        <tr>
                                            <td colspan="4" class="bg-light text-info"><strong><i class="fas fa-utensils"></i> <?php echo $lang['additional_services']; ?>:</strong></td>
                                        </tr>
                                        <?php foreach($room_services as $svc): ?>
                                            <tr>
                                                <td class="pl-4"><?php echo htmlspecialchars($svc[$prod_name_col] ?: $svc['item_name']); ?></td>
                                                <td class="text-center"><?php echo $svc['qty']; ?></td>
                                                <td class="text-right"><?php echo number_format($svc['price']); ?></td>
                                                <td class="text-right text-info"><?php echo number_format($svc['total_price']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <!-- Subtotal Services -->
                                        <tr>
                                            <td colspan="3" class="text-right"><strong><?php echo $lang['total_additional_services']; ?>:</strong></td>
                                            <td class="text-right font-weight-bold text-info"><?php echo number_format($selected_booking['food_charge']); ?> <?php echo $defCurr['currency_name']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <!-- Deposit -->
                                    <?php if($selected_booking['deposit_amount'] > 0): ?>
                                        <tr class="text-success">
                                            <td colspan="3" class="text-right"><strong><?php echo $lang['deduct_deposit']; ?>:</strong></td>
                                            <td class="text-right font-weight-bold">- <?php echo number_format($selected_booking['deposit_amount']); ?> <?php echo $defCurr['currency_name']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php 
                                        $subtotal = $selected_booking['total_price'] + $selected_booking['food_charge'];
                                        $tax_amount = round($subtotal * ($tax_percent / 100));
                                        $total_after_tax = $subtotal + $tax_amount;
                                        $grand_total = $total_after_tax - $selected_booking['deposit_amount'];
                                    ?>
                                    
                                    <!-- Tax Row -->
                                    <?php if($tax_percent > 0): ?>
                                    <tr class="text-info">
                                        <td colspan="3" class="text-right"><strong><?php echo $lang['tax_percent'] ?? 'Tax'; ?> (<?php echo $tax_percent; ?>%):</strong></td>
                                        <td class="text-right font-weight-bold"><?php echo number_format($tax_amount); ?> <?php echo $defCurr['currency_name']; ?></td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr class="total-row">
                                        <td colspan="3" class="text-right"><?php echo $lang['total_due']; ?>:</td>
                                        <td class="text-right grand-total"><?php echo number_format($grand_total); ?> <?php echo $defCurr['currency_name']; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <form action="" method="post" id="checkoutForm">
                            <input type="hidden" name="confirm_checkout" value="1">
                            <input type="hidden" name="booking_id" value="<?php echo $selected_booking['id']; ?>">
                            <input type="hidden" name="room_id" value="<?php echo $selected_booking['room_id']; ?>">
                            <input type="hidden" id="grand_total_val" value="<?php echo $grand_total; ?>">
                            <input type="hidden" id="checkout_status_msg" value="<?php 
                                if ($today < $checkout_date) echo $lang['not_due_yet'] . "! ";
                                elseif ($today > $checkout_date) echo $lang['overdue'] . "! ";
                                else echo "";
                            ?>">
                            
                            <div class="row bg-light p-3 rounded mb-4">
                                <div class="col-md-12 mb-3 border-bottom pb-2">
                                    <h5 class="text-success"><i class="fas fa-hand-holding-usd"></i> <?php echo $lang['payment_info']; ?> (<?php echo $lang['receive'] ?? 'Receive'; ?> <?php echo $defCurr['currency_name']; ?>)</h5>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><?php echo $lang['payment_method_label']; ?></label>
                                        <select name="payment_method" id="payment_method" class="form-control">
                                            <option value="Cash"><?php echo $lang['cash']; ?></option>
                                            <option value="Transfer"><?php echo $lang['transfer']; ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><?php echo $lang['amount_received_label']; ?> (<?php echo $defCurr['currency_name']; ?>)</label>
                                        <div class="input-group">
                                            <input type="text" name="amount_received" id="amount_received" class="form-control number-format text-right font-weight-bold" placeholder="0">
                                            <div class="input-group-append">
                                                <button type="button" id="btn_full_pay" class="btn btn-primary btn-sm px-3" style="font-size: 0.8rem;"><?php echo $lang['full_pay_btn']; ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><?php echo $lang['change_amount_label']; ?> (<?php echo $defCurr['currency_name']; ?>)</label>
                                        <input type="text" name="change_amount" id="change_amount" class="form-control text-right text-danger font-weight-bold" value="0" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12 text-right">
                                    <a href="print_room_receipt.php?booking_id=<?php echo $selected_booking['id']; ?>" target="_blank" class="btn btn-default mr-2"><i class="fas fa-print"></i> <?php echo $lang['print_bill']; ?></a>
                                    <button type="submit" name="confirm_checkout" class="btn btn-success btn-lg">
                                        <i class="fas fa-check-double"></i> Check-out
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-hand-pointer text-muted fa-4x mb-3"></i>
                        <h4 class="text-muted"><?php echo $lang['checkout_prompt']; ?></h4>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="sweetalert/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    var grandTotal = parseFloat($('#grand_total_val').val()) || 0;

    // Full Payment Shortcut
    $('#btn_full_pay').on('click', function() {
        $('#amount_received').val(grandTotal.toLocaleString('en-US'));
        calculateChange();
        // Trigger checkout confirmation immediately
        $('#checkoutForm').submit();
    });

    // Number formatting
    $('.number-format').on('input', function(e) {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== '') {
            $(this).val(parseInt(value, 10).toLocaleString('en-US'));
        } else {
            $(this).val('');
        }
        calculateChange();
    });

    $('#payment_method').on('change', function() {
        var method = $(this).val();
        if (method === 'ເງິນໂອນ' || method === 'ບັດ') {
            $('#amount_received').val(grandTotal.toLocaleString('en-US'));
            $('#amount_received').prop('readonly', true);
            calculateChange();
        } else {
            $('#amount_received').val('');
            $('#amount_received').prop('readonly', false);
            calculateChange();
        }
    });

    function calculateChange() {
        var received = parseFloat($('#amount_received').val().replace(/,/g, '')) || 0;
        var change = received - grandTotal;
        if (change < 0) change = 0;
        $('#change_amount').val(change.toLocaleString('en-US'));
    }

    $('#checkoutForm').on('submit', function(e) {
        e.preventDefault();
        var received = parseFloat($('#amount_received').val().replace(/,/g, '')) || 0;
        var method = $('#payment_method').val();
        var statusMsg = $('#checkout_status_msg').val();

        if (received < grandTotal && method === 'ເງິນສົດ') {
            Swal.fire({
                icon: 'error',
                title: '<?php echo $lang['insufficient_balance']; ?>',
                text: '<?php echo $lang['insufficient_balance_msg']; ?>',
                confirmButtonText: '<?php echo $lang['ok']; ?>'
            });
            return false;
        }

        Swal.fire({
            title: statusMsg ? '<?php echo $lang['warning_label']; ?>: ' + statusMsg + ' <?php echo $lang['confirm_checkout_question']; ?>' : '<?php echo $lang['confirm_checkout_question']; ?>',
            text: statusMsg ? '<?php echo $lang['warning_label']; ?>: ' + statusMsg + ' <?php echo $lang['confirm_checkout_msg']; ?>' : '<?php echo $lang['confirm_checkout_msg']; ?>',
            icon: statusMsg ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Check-out',
            cancelButtonText: '<?php echo $lang['cancel']; ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#checkoutForm')[0].submit();
            }
        });
    });

    // Room List Live Search
    $('#room_search_input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var visibleCount = 0;
        
        $(".room-item").each(function() {
            var isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(isVisible);
            if (isVisible) visibleCount++;
        });

        $('#no_room_msg').remove();
        if (visibleCount === 0) {
            $('#room_list_container').append('<li id="no_room_msg" class="nav-item p-3 text-center text-muted"><?php echo $lang['no_data']; ?></li>');
        }
    });
});
</script>
</body>
</html>

<?php
// ==========================================
// ໄຟລ໌ຄົ້ນຫາ CSS Styling (absolute, rgba, badge) ໃນ room_service.php (CSS Styling Search Tool)
// ------------------------------------------
// ໜ້າທີ່: ເຮັດໜ້າທີ່ອ່ານທຸກແຖວໃນໄຟລ໌ room_service.php ແລ້ວຄົ້ນຫາຄຳສັບ 'absolute', 'rgba' ຫຼື 'badge'
// ເພື່ອກວດສອບ ແລະ ວິເຄາະຮູບແບບການຈັດຕຳແໜ່ງ (Positioning) ແລະ ສີສັນຂອງ Badge ພາຍໃນໄຟລ໌.
// ==========================================

// ໂຫຼດທຸກໆແຖວຂອງໄຟລ໌ room_service.php ມາເກັບໄວ້ເປັນ Array
$lines = file('room_service.php');

// Loop ກວດສອບແຕ່ລະແຖວ
foreach ($lines as $i => $line) {
    // ຫາກພົບເຫັນຄຳສັບ 'absolute', 'rgba' ຫຼື 'badge' ໃຫ້ສະແດງເລກແຖວ ແລະ ຂໍ້ຄວາມນັ້ນອອກມາ
    if (strpos($line, 'absolute') !== false || strpos($line, 'rgba') !== false || strpos($line, 'badge') !== false) {
        echo 'Line ' . ($i + 1) . ': ' . trim($line) . PHP_EOL;
    }
}

<?php
// ==========================================
// ໄຟລ໌ຄົ້ນຫາ CSS Classes ໃນ room_service.php (CSS Classes Search Tool)
// ------------------------------------------
// ໜ້າທີ່: ເຮັດໜ້າທີ່ອ່ານທຸກແຖວໃນໄຟລ໌ room_service.php ແລ້ວໃຊ້ Regular Expression (preg_match)
// ໃນການຄົ້ນຫາແຖວທີ່ເປັນການກຳນົດ CSS Class (ແຖວທີ່ຂຶ້ນຕົ້ນດ້ວຍເຄື່ອງໝາຍຈຸດ ".")
// ==========================================

// ໂຫຼດທຸກໆແຖວຂອງໄຟລ໌ room_service.php ມາເກັບໄວ້ເປັນ Array
$lines = file('room_service.php');

// Loop ກວດສອບແຕ່ລະແຖວ
foreach ($lines as $i => $line) {
    // ໃຊ້ Regular Expression ຄົ້ນຫາຮູບແບບ Class ທີ່ຂຶ້ນຕົ້ນດ້ວຍເຄື່ອງໝາຍ "." ຕິດຕາມດ້ວຍຕົວອັກສອນ
    if (preg_match('/^\s*\.[a-zA-Z0-9_-]+/i', $line, $matches)) {
        echo 'Line ' . ($i + 1) . ': ' . trim($line) . PHP_EOL;
    }
}

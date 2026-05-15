<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=db_hotel', 'root', '');
    
    // Helper to add if not exists
    function addSetting($p, $key, $val) {
        $st = $p->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $st->execute([$key]);
        if ($st->fetchColumn() == 0) {
            $p->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $val]);
        }
    }

    // Get current values
    $st = $p->query("SELECT setting_key, setting_value FROM settings");
    $s = [];
    foreach($st->fetchAll() as $r) $s[$r['setting_key']] = $r['setting_value'];

    addSetting($p, 'hotel_name_la', $s['hotel_name'] ?? 'ໂຮງແຮມ');
    addSetting($p, 'hotel_name_en', 'Luxury Hotel System');
    addSetting($p, 'hotel_name_cn', '酒店管理系统');
    
    addSetting($p, 'hotel_address_la', $s['hotel_address'] ?? '');
    addSetting($p, 'hotel_address_en', 'Vientiane, Laos');
    addSetting($p, 'hotel_address_cn', '老挝万象');

    addSetting($p, 'receipt_footer_la', $s['receipt_footer'] ?? 'ຂໍຂອບໃຈ!');
    addSetting($p, 'receipt_footer_en', 'Thank you for your visit!');
    addSetting($p, 'receipt_footer_cn', '谢谢光临！');

    echo "Success: Localized settings added.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

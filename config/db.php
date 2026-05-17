<?php
$host = 'localhost';
$db   = 'db_hotel';
$user = 'root';
$pass = '';
$charset = 'utf8';
require_once __DIR__ . '/logger.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);

     // Fetch Default Currency Global Variable
     // --- ສ່ວນດຶງຂໍ້ມູນສະກຸນເງິນຫຼັກ (LAK, USD, CNY...) ຈາກຖານຂໍ້ມູນ ---
     $stmtCur = $pdo->query("SELECT * FROM currency WHERE is_default = 1 LIMIT 1");
     $defCurr = $stmtCur->fetch();
     if(!$defCurr) {
         // ຫາກບໍ່ມີການຕັ້ງຄ່າສະກຸນເງິນຫຼັກໃນຖານຂໍ້ມູນ, ໃຫ້ໃຊ້ຄ່າກີບ (LAK) ເປັນຄ່າເລີ່ມຕົ້ນ (Fallback)
         $defCurr = ['currency_name' => 'ກີບ', 'symbol' => '₭', 'currency_code' => 'LAK', 'exchange_rate' => 1];
     }

     // ==========================================
     // ຟັງຊັນສຳລັບຈັດຮູບແບບ ແລະ ແປງສະກຸນເງິນທົ່ວລະບົບ (Global Currency Converter Engine)
     // ------------------------------------------
     // ໜ້າທີ່: ເຮັດໜ້າທີ່ຮັບຄ່າຈຳນວນເງິນກີບ (KIP) ຈາກຖານຂໍ້ມູນ, ທຳການຫານດ້ວຍອັດຕາແລກປ່ຽນ
     // ຂອງສະກຸນເງິນຫຼັກທີ່ເປີດໃຊ້ງານ, ແປພາສາຊື່ສະກຸນເງິນໃຫ້ກົງກັບພາສາທີ່ຜູ້ໃຊ້ກຳລັງເປີດ (Lao, English, Chinese)
     // ແລະ ຈັດຮູບແບບເລກທົດສະນິຍົມໃຫ້ຖືກຕ້ອງຕາມມາດຕະຖານສະກຸນເງິນນັ້ນໆ (ເຊັ່ນ: USD ຕ້ອງມີ .00).
     // ==========================================
     function formatCurrency($amount_kip) {
         global $defCurr;
         
         // 1. ການຄຳນວນອັດຕາແລກປ່ຽນ (Exchange Rate Division):
         // ດຶງອັດຕາແລກປ່ຽນຂອງສະກຸນເງິນຫຼັກມາເປັນຕົວຫານ, ຫາກບໍ່ມີໃຫ້ໃຊ້ 1 (ບໍ່ຫານ).
         $rate = (float)($defCurr['exchange_rate'] ?? 1);
         if ($rate <= 0) $rate = 1;
         $converted = $amount_kip / $rate;
         
         // 2. ການແປພາສາຊື່ສະກຸນເງິນ (Currency Name Translation):
         // ລະບົບຈະກວດສອບ Session Language ປັດຈຸບັນ ແລະ ດຶງຄໍລຳແປພາສາທີ່ກົງກັນ:
         //  - ຫາກເປັນພາສາອັງກິດ ('en') ➔ ໃຊ້ຄໍລຳ `currency_name_en` (ເຊັ່ນ: Kip, Dollar, Baht)
         //  - ຫາກເປັນພາສາຈີນ ('cn') ➔ ໃຊ້ຄໍລຳ `currency_name_cn` (ເຊັ່ນ: 基普, 美元, 泰铢)
         //  - ຫາກເປັນພາສາລາວ ('la') ➔ ໃຊ້ຄໍລຳ `currency_name_la` (ເຊັ່ນ: ກີບ, ໂດລາ, ບາດ)
         $session_lang = $_SESSION['lang'] ?? 'la';
         $currencyName = $defCurr['currency_name'] ?? 'ກີບ';
         if ($session_lang === 'en' && !empty($defCurr['currency_name_en'])) {
             $currencyName = $defCurr['currency_name_en'];
         } elseif ($session_lang === 'cn' && !empty($defCurr['currency_name_cn'])) {
             $currencyName = $defCurr['currency_name_cn'];
         } elseif ($session_lang === 'la' && !empty($defCurr['currency_name_la'])) {
             $currencyName = $defCurr['currency_name_la'];
         }
         
         // 3. ການຈັດຮູບແບບຕົວເລກ ແລະ ທົດສະນິຍົມ (Formatting Standards):
         //  - ຫາກເປັນສະກຸນເງິນຫຼັກສາກົນ (ເຊັ່ນ: USD, CNY, EUR) ➔ ຈັດຮູບແບບໃຫ້ມີທົດສະນິຍົມ 2 ຕຳແໜ່ງ (ເຊັ່ນ: 100.50 USD)
         //  - ຫາກເປັນສະກຸນເງິນທ້ອງຖິ່ນ (ເຊັ່ນ: LAK, THB) ➔ ຈັດຮູບແບບເປັນເລກຖ້ວນບໍ່ມີທົດສະນິຍົມ (ເຊັ່ນ: 1,500,000 Kip)
         if (in_array($defCurr['currency_code'] ?? 'LAK', ['USD', 'CNY', 'EUR'])) {
             return number_format($converted, 2) . ' ' . $currencyName;
         } else {
             return number_format($converted) . ' ' . $currencyName;
         }
     }
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>

<?php
session_start();
require_once 'config/db.php';

// Language Selection Logic
$current_lang = $_SESSION['lang'] ?? 'la';
$lang_file = "lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include "lang/la.php";
}

// Fetch logs with user names
$stmt = $pdo->query("
    SELECT l.*, u.fname, u.lname, u.status as user_role 
    FROM system_logs l 
    LEFT JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.created_at DESC 
    LIMIT 500
");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['system_history']; ?></title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- Noto Sans Lao Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif !important; background-color: #f4f6f9; }
        .log-action { font-weight: 700; color: #007bff; }
        .log-details { font-size: 0.9rem; color: #666; }
        .log-time { font-size: 0.85rem; color: #888; }
        .user-badge { font-size: 0.75rem; vertical-align: middle; }
    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header bg-white">
            <h3 class="card-title font-weight-bold">
                <i class="fas fa-history mr-2 text-primary"></i> <?php echo $lang['action_history']; ?>
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" onclick="window.location.reload()"><i class="fas fa-sync-alt"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="logTable" class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%"><?php echo $lang['date_time']; ?></th>
                            <th width="15%"><?php echo $lang['user']; ?></th>
                            <th width="20%"><?php echo $lang['action']; ?></th>
                            <th><?php echo $lang['details']; ?></th>
                            <th width="12%"><?php echo $lang['ip_address']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td class="log-time"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['fname'] . ' ' . $log['lname']); ?></strong>
                                    <br>
                                    <span class="badge badge-secondary user-badge"><?php echo htmlspecialchars($log['user_role'] ?? 'System'); ?></span>
                                </td>
                                <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                                <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#logTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": false,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "language": {
                "search": "<?php echo $lang['dt_search'] ?? $lang['search']; ?>:",
                "lengthMenu": "<?php echo $lang['dt_length']; ?>",
                "info": "<?php echo $lang['dt_info']; ?>",
                "infoEmpty": "<?php echo $lang['dt_info_empty'] ?? $lang['table_info_empty']; ?>",
                "zeroRecords": "<?php echo $lang['dt_zeroRecords']; ?>",
                "paginate": {
                    "next": "<?php echo $lang['dt_paginate_next'] ?? $lang['next']; ?>",
                    "previous": "<?php echo $lang['dt_paginate_previous'] ?? $lang['previous']; ?>"
                }
            }
        });
    });
</script>

</body>
</html>

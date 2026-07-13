<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';

// Fetch manuals for supervisors/executives
$stmt = $pdo->query("SELECT * FROM manuals WHERE role_target IN ('supervisor', 'executive', 'all') ORDER BY created_at DESC");
$manuals = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-book"></i> คู่มือการใช้งานสำหรับกรรมการนิเทศ</h2>
    <p class="text-muted">ดาวน์โหลดคู่มือและเอกสารประกอบการประเมินการจัดการเรียนการสอน</p>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 50%;">ชื่อคู่มือ</th>
                    <th style="width: 25%;">วันที่อัปโหลด</th>
                    <th style="width: 20%;">ดาวน์โหลด</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($manuals) > 0): ?>
                    <?php foreach ($manuals as $index => $m): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($m['title']); ?></td>
                            <td style="text-align: center;"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                            <td style="text-align: center;">
                                <a href="/nited/<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary" style="padding:5px 15px; text-decoration:none;">
                                    <i class="fas fa-download"></i> ดาวน์โหลด
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #999; padding: 20px;">ยังไม่มีคู่มือในระบบขณะนี้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

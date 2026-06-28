<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('executive');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get available academic years for filter
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year DESC, term DESC");
$years = $stmt->fetchAll();

$selected_year_id = isset($_GET['year_id']) ? intval($_GET['year_id']) : 0;
if ($selected_year_id === 0 && !empty($years)) {
    // defaults to active year or first available
    $active = array_filter($years, fn($y) => $y['is_active'] == 1);
    $selected_year_id = !empty($active) ? array_values($active)[0]['id'] : $years[0]['id'];
}

// Fetch report data: List of teachers and their average scores for the selected year
$report_data = [];
if ($selected_year_id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            u.id as teacher_id, 
            u.name as teacher_name, 
            COUNT(DISTINCT s.id) as total_evals,
            (
               SELECT SUM(r2.score) / SUM(i2.max_score) * 100
               FROM supervision_results r2
               JOIN criteria_items i2 ON r2.criteria_item_id = i2.id
               JOIN supervisions s2 ON r2.supervision_id = s2.id
               WHERE s2.teacher_id = u.id AND s2.academic_year_id = ? AND s2.status = 'completed'
            ) as avg_score_percent
        FROM users u
        LEFT JOIN supervisions s ON u.id = s.teacher_id AND s.academic_year_id = ? AND s.status = 'completed'
        WHERE u.role IN ('teacher', 'supervisor')
        GROUP BY u.id
        ORDER BY avg_score_percent DESC
    ");
    $stmt->execute([$selected_year_id, $selected_year_id]);
    $report_data = $stmt->fetchAll();
}
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-file-alt"></i> รายงานสรุปผลการนิเทศ</h2>
        <p class="text-muted">ตารางแสดงประวัติและคะแนนเฉลี่ยของครูแต่ละท่าน</p>
    </div>

    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <label for="year_id" style="font-weight: bold;">เลือกปีการศึกษา:</label>
        <select name="year_id" id="year_id" class="form-control" style="width: 200px;" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?php echo $y['id']; ?>" <?php echo ($selected_year_id == $y['id']) ? 'selected' : ''; ?>>
                    <?php echo $y['term'] . '/' . $y['year']; ?>
                    <?php echo ($y['is_active']) ? '(ปัจจุบัน)' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
        <h3 style="margin: 0;">ภาพรวมรายบุคคล</h3>
        <button onclick="window.print()" class="btn-gradient" style="padding: 5px 15px; font-size: 14px;"><i
                class="fas fa-print"></i> พิมพ์รายงาน</button>
    </div>

    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 12px; border-bottom: 2px solid #ddd;">ลำดับ</th>
                <th style="padding: 12px; border-bottom: 2px solid #ddd;">ชื่อ-สกุล (ครูผู้สอน)</th>
                <th style="padding: 12px; border-bottom: 2px solid #ddd; text-align: center;">จำนวนครั้งที่ถูกประเมิน
                </th>
                <th style="padding: 12px; border-bottom: 2px solid #ddd; text-align: center;">คะแนนเฉลี่ยคิดเป็นร้อยละ
                    (%)</th>
                <th style="padding: 12px; border-bottom: 2px solid #ddd; text-align: center;">เกณฑ์ประเมินเบื้องต้น</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $idx => $row): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">
                        <?php echo $idx + 1; ?>
                    </td>
                    <td style="padding: 12px; font-weight: bold;">
                        <?php echo htmlspecialchars($row['teacher_name']); ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <?php echo $row['total_evals']; ?> ครั้ง
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <?php if ($row['total_evals'] > 0): ?>
                            <span style="font-size: 16px; font-weight: bold; color: #198754;">
                                <?php echo round($row['avg_score_percent'], 2); ?>%
                            </span>
                        <?php else: ?>
                            <span style="color: #ccc;">ยังไม่มีการประเมิน</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <?php
                        if ($row['total_evals'] > 0) {
                            $pct = $row['avg_score_percent'];
                            if ($pct >= 80)
                                echo '<span style="color:#198754;"><i class="fas fa-star"></i> ดีมาก</span>';
                            elseif ($pct >= 60)
                                echo '<span style="color:#0d6efd;"><i class="fas fa-check"></i> ดี</span>';
                            elseif ($pct >= 50)
                                echo '<span style="color:#ffc107;"><i class="fas fa-minus"></i> พอใช้</span>';
                            else
                                echo '<span style="color:#dc3545;"><i class="fas fa-exclamation-triangle"></i> ควรปรับปรุง</span>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($report_data)): ?>
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #888;">ไม่พบข้อมูลครูในระบบ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    @media print {
        body {
            background: white;
            color: black;
        }

        .sidebar,
        .app-header,
        .btn-gradient,
        form {
            display: none !important;
        }

        .main-panel {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .content-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
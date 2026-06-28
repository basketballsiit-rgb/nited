<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('executive');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;
$year_txt = $active_year ? ($active_year['term'] . '/' . $active_year['year']) : 'ยังไม่ได้กำหนด';

// Overall Stats for active year
$stats = [
    'total_teachers_and_supervisors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('teacher', 'supervisor')")->fetchColumn(),
    'total_supervisions' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE academic_year_id = $year_id")->fetchColumn(),
    'completed_evals' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE academic_year_id = $year_id AND status = 'completed'")->fetchColumn()
];

// Average Score Data per Category (Overall)
$chart_labels = [];
$chart_data = [];

if ($year_id > 0) {
    $stmt = $pdo->prepare("
        SELECT c.title, AVG((r.score / i.max_score) * 100) as avg_percent
        FROM supervision_results r
        JOIN criteria_items i ON r.criteria_item_id = i.id
        JOIN criteria_categories c ON i.category_id = c.id
        JOIN supervisions s ON r.supervision_id = s.id
        WHERE s.academic_year_id = ? AND s.status = 'completed'
        GROUP BY c.id
        ORDER BY c.id ASC
    ");
    $stmt->execute([$year_id]);
    $cat_stats = $stmt->fetchAll();

    foreach ($cat_stats as $c) {
        $chart_labels[] = mb_substr($c['title'], 0, 20) . '...'; // truncate long titles
        $chart_data[] = round($c['avg_percent'], 2);
    }
}

// Fetch stats by department
$dept_stats = [];
if ($year_id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            u.department, 
            COUNT(DISTINCT u.id) as total_teachers,
            COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN u.id END) as supervised_count,
            COUNT(DISTINCT CASE WHEN lp.status IN ('approved', 'revision', 'rejected') THEN u.id END) as plan_evaluated_count
        FROM users u
        LEFT JOIN supervisions s ON u.id = s.teacher_id AND s.academic_year_id = ?
        LEFT JOIN lesson_plans lp ON u.id = lp.teacher_id AND lp.academic_year_id = ?
        WHERE u.role = 'teacher' 
          AND u.department IS NOT NULL 
          AND u.department != ''
        GROUP BY u.department
        ORDER BY u.department ASC
    ");
    $stmt->execute([$year_id, $year_id]);
    $dept_stats = $stmt->fetchAll();
}
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-chart-pie"></i> แดชบอร์ดผู้บริหาร</h2>
        <p class="text-muted">ภาพรวมผลการนิเทศการจัดการเรียนการสอน ปีการศึกษา
            <?php echo htmlspecialchars($year_txt); ?>
        </p>
    </div>
    <a href="reports.php" class="btn-gradient" style="text-decoration: none;"><i class="fas fa-file-alt"></i>
        ดูรายงานแบบละเอียด</a>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card bg-blue">
        <h3><i class="fas fa-chalkboard-teacher"></i> จำนวนผู้รับการนิเทศทั้งหมด</h3>
        <p class="value">
            <?php echo $stats['total_teachers_and_supervisors']; ?>
        </p>
        <i class="fas fa-users icon"></i>
    </div>
    <div class="stat-card bg-orange">
        <h3><i class="fas fa-calendar-check"></i> เป้าหมายการนิเทศ (ครั้ง)</h3>
        <p class="value">
            <?php echo $stats['total_supervisions']; ?>
        </p>
        <i class="fas fa-crosshairs icon"></i>
    </div>
    <div class="stat-card bg-green">
        <h3><i class="fas fa-clipboard-check"></i> นิเทศเสร็จสิ้น (ครั้ง)</h3>
        <p class="value">
            <?php echo $stats['completed_evals']; ?>
        </p>
        <div
            style="margin-top: 10px; background: rgba(255,255,255,0.2); border-radius: 10px; height: 8px; width: 100%;">
            <?php
            $percent_done = ($stats['total_supervisions'] > 0) ? ($stats['completed_evals'] / $stats['total_supervisions'] * 100) : 0;
            ?>
            <div style="background: #fff; height: 100%; border-radius: 10px; width: <?php echo $percent_done; ?>%;">
            </div>
        </div>
        <p style="font-size: 12px; margin-top: 5px; text-align: right;">ความคืบหน้า
            <?php echo round($percent_done, 1); ?>%
        </p>
        <i class="fas fa-chart-line icon"></i>
    </div>
</div>

<div class="content-header" style="margin-top: 40px; margin-bottom: 20px;">
    <h2><i class="fas fa-building"></i> ความคืบหน้าแยกตามสาขาวิชา</h2>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <?php foreach ($dept_stats as $ds): ?>
    <div style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid #4f46e5;">
        <h3 style="margin-top: 0; margin-bottom: 15px; color: #333; font-size: 1.1rem;"><?php echo htmlspecialchars($ds['department']); ?></h3>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
            <span style="color: #666;"><i class="fas fa-users"></i> จำนวนครูทั้งหมด</span>
            <span style="font-weight: bold;"><?php echo $ds['total_teachers']; ?> คน</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
            <span style="color: #666;"><i class="fas fa-chalkboard-teacher"></i> ได้รับการนิเทศแล้ว</span>
            <span style="font-weight: bold; color: <?php echo ($ds['supervised_count'] == $ds['total_teachers'] && $ds['total_teachers'] > 0) ? '#198754' : '#f59e0b'; ?>;">
                <?php echo $ds['supervised_count']; ?> / <?php echo $ds['total_teachers']; ?>
            </span>
        </div>
        
        <div style="display: flex; justify-content: space-between;">
            <span style="color: #666;"><i class="fas fa-file-signature"></i> ประเมินแผนการสอนแล้ว</span>
            <span style="font-weight: bold; color: <?php echo ($ds['plan_evaluated_count'] == $ds['total_teachers'] && $ds['total_teachers'] > 0) ? '#198754' : '#f59e0b'; ?>;">
                <?php echo $ds['plan_evaluated_count']; ?> / <?php echo $ds['total_teachers']; ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($dept_stats)): ?>
        <p style="grid-column: 1 / -1; text-align: center; color: #888; background: white; padding: 20px; border-radius: 10px;">ยังไม่มีข้อมูลสาขาวิชา</p>
    <?php endif; ?>
</div>

<div class="content-header" style="margin-top: 40px; margin-bottom: 20px;">
    <h2><i class="fas fa-chart-bar"></i> คะแนนเฉลี่ย (เปอร์เซ็นต์) แยกตามหมวดหมู่การประเมิน</h2>
</div>

<div class="content-card">
    <div style="height: 400px; width: 100%;">
        <canvas id="overallChart"></canvas>
    </div>
    <?php if (empty($chart_data)): ?>
        <p style="text-align: center; color: #888; margin-top: 20px;">ยังไม่มีข้อมูลการประเมินในเทอมนี้</p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('overallChart').getContext('2d');

        // Gradient for bars
        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(233, 64, 87, 0.8)');
        gradient.addColorStop(1, 'rgba(242, 113, 33, 0.8)');

        const data = {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'คะแนนเฉลี่ย (%)',
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: gradient,
                borderColor: '#E94057',
                borderWidth: 1,
                borderRadius: 5
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) { return value + '%' }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) { return context.parsed.y + '%'; }
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/config/db.php';

$criteria = [
    'ด้านที่ 1: การเตรียมการสอน (Lesson Planning)' => [
        'จัดทำแผนการจัดการเรียนรู้ที่สอดคล้องกับหลักสูตรและบริบทของผู้เรียน',
        'การเลือกใช้สื่อ นวัตกรรม และแหล่งเรียนรู้ที่เหมาะสมและหลากหลาย',
        'กำหนดวัตถุประสงค์การเรียนรู้ที่ชัดเจน ครอบคลุม K-P-A และวัดผลได้'
    ],
    'ด้านที่ 2: การจัดกิจกรรมการเรียนรู้ (Instructional Delivery)' => [
        'นำเข้าสู่บทเรียนได้น่าสนใจ กระตุ้นความอยากรู้อยากเห็นของผู้เรียน',
        'อธิบายเนื้อหาได้ถูกต้อง ชัดเจน เข้าใจง่าย และเชื่อมโยงกับชีวิตจริง',
        'จัดกิจกรรมที่เน้นผู้เรียนเป็นสำคัญ (Active Learning) เปิดโอกาสให้ผู้เรียนลงมือปฏิบัติ',
        'ใช้คำถามหรือเทคนิคกระตุ้นให้ผู้เรียนคิดวิเคราะห์ แก้ปัญหา และแสดงความคิดเห็น',
        'มีการสรุปบทเรียนและประเด็นสำคัญร่วมกับผู้เรียนอย่างมีประสิทธิภาพ'
    ],
    'ด้านที่ 3: การบริหารจัดการชั้นเรียน (Classroom Management)' => [
        'สร้างบรรยากาศเชิงบวกที่เอื้อต่อการเรียนรู้ เป็นมิตร สนับสนุนให้เกิดความเชื่อมั่น',
        'ควบคุมชั้นเรียนและจัดการกับพฤติกรรมที่ไม่เหมาะสมของผู้เรียนด้วยหลักจิตวิทยา',
        'บริหารจัดการเวลาในแต่ละกิจกรรมการเรียนการสอนได้อย่างเหมาะสม'
    ],
    'ด้านที่ 4: การใช้สื่อและเทคโนโลยี (Media and Technology)' => [
        'ใช้สื่อการสอน ดิจิทัลแพลตฟอร์ม หรือเทคโนโลยีที่ส่งเสริมความเข้าใจของผู้เรียน',
        'สื่อการสอนมีความถูกต้อง ทันสมัย สื่อความหมายชัดเจน และเหมาะสมกับวัย'
    ],
    'ด้านที่ 5: การวัดและประเมินผลการเรียนรู้ (Assessment and Evaluation)' => [
        'ใช้วิธีการและเครื่องมือวัดผลที่หลากหลาย สอดคล้องกับจุดประสงค์การเรียนรู้',
        'แจ้งเกณฑ์การประเมินให้ผู้เรียนทราบล่วงหน้าและประเมินตามสภาพจริง',
        'ให้ข้อมูลย้อนกลับ (Feedback) เชิงบวกเพื่อสร้างแรงจูงใจและพัฒนาผู้เรียน'
    ]
];

try {
    $pdo->beginTransaction();

    // Optionally clear existing criteria to avoid duplicates if asked to replace, 
    // but here we just add them. If they want to start fresh, uncomment below:
    $pdo->exec("DELETE FROM criteria_items");
    $pdo->exec("DELETE FROM criteria_categories");

    $weight = 20; // Example weight, total 100 for 5 categories
    foreach ($criteria as $category_title => $items) {
        $stmt_cat = $pdo->prepare("INSERT INTO criteria_categories (title, weight) VALUES (?, ?)");
        $stmt_cat->execute([$category_title, $weight]);
        $category_id = $pdo->lastInsertId();

        foreach ($items as $item_desc) {
            $stmt_item = $pdo->prepare("INSERT INTO criteria_items (category_id, description, max_score) VALUES (?, ?, 5)");
            $stmt_item->execute([$category_id, $item_desc]);
        }
    }

    $pdo->commit();
    echo "Criteria populated successfully!\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
// setup_db.php
// Run this script once to initialize the database and tables.
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS supervision_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE supervision_db");

    // 2. Create Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NULL,
            role ENUM('admin', 'teacher', 'supervisor', 'executive') NOT NULL,
            academic_standing VARCHAR(50) DEFAULT NULL,
            position VARCHAR(100) DEFAULT NULL,
            department VARCHAR(255) DEFAULT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Create academic_years table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS academic_years (
            id INT AUTO_INCREMENT PRIMARY KEY,
            year VARCHAR(4) NOT NULL,
            term VARCHAR(1) NOT NULL,
            is_active BOOLEAN DEFAULT FALSE,
            UNIQUE KEY(year, term)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Create supervisions table (Observation Booking)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS supervisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            supervisor_id INT NULL,
            academic_year_id INT NOT NULL,
            subject_code VARCHAR(50) NOT NULL,
            subject_name VARCHAR(255) NOT NULL,
            level VARCHAR(50) NOT NULL,
            scheduled_date DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
            photo_path VARCHAR(255) NULL,
            photo_path_2 VARCHAR(255) NULL,
            lesson_plan_file VARCHAR(255) NULL,
            signature_path TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 6. Create criteria_categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS criteria_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            weight DECIMAL(5,2) DEFAULT NULL,
            order_idx INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 7. Create criteria_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS criteria_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            description TEXT NOT NULL,
            max_score INT DEFAULT 5,
            order_idx INT DEFAULT 0,
            FOREIGN KEY (category_id) REFERENCES criteria_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 8. Create supervision_results table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS supervision_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supervision_id INT NOT NULL,
            criteria_item_id INT NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (supervision_id) REFERENCES supervisions(id) ON DELETE CASCADE,
            FOREIGN KEY (criteria_item_id) REFERENCES criteria_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 9. Create lesson_plans table (Full competency-based lesson plan submissions)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lesson_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            reviewer_id INT NULL,
            academic_year_id INT NOT NULL,
            subject_code VARCHAR(50) NOT NULL,
            subject_name VARCHAR(255) NOT NULL,
            level VARCHAR(50) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            status ENUM('pending', 'draft', 'approved', 'revision', 'rejected') DEFAULT 'pending',
            review_comment TEXT,
            optional_sections TEXT NULL,
            signature_path TEXT NULL,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME NULL,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 10. Create lp_criteria_categories table (Specific for Lesson Plans)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lp_criteria_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            weight DECIMAL(5,2) DEFAULT NULL,
            order_idx INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 11. Create lp_criteria_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lp_criteria_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            is_header TINYINT(1) DEFAULT 0,
            is_optional TINYINT(1) DEFAULT 0,
            description TEXT NOT NULL,
            indicator TEXT NULL,
            max_score INT DEFAULT 5,
            order_idx INT DEFAULT 0,
            FOREIGN KEY (category_id) REFERENCES lp_criteria_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 12. Create lesson_plan_results table (Scoring rubric for the full lesson plans)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lesson_plan_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_plan_id INT NOT NULL,
            criteria_item_id INT NOT NULL,
            score DECIMAL(5,2) NULL,
            comment TEXT,
            is_draft TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lesson_plan_id) REFERENCES lesson_plans(id) ON DELETE CASCADE,
            FOREIGN KEY (criteria_item_id) REFERENCES lp_criteria_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 13. Create notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Database and tables created successfully!\n";

    // Insert Default admin user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES ('admin', :password, 'System Admin', 'admin')");
        $insertStmt->execute(['password' => $hashed_password]);
        echo "Default admin user created (username: admin, password: 123456)\n";
    }

    // Insert Default LP Criteria if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM lp_criteria_categories");
    if ($stmt->fetchColumn() == 0) {
        // Category 1
        $pdo->exec("INSERT INTO lp_criteria_categories (id, title, order_idx) VALUES (1, 'ด้านโครงสร้างและองค์ประกอบของแผนการสอน', 1)");
        $pdo->exec("INSERT INTO lp_criteria_items (category_id, description, max_score, order_idx) VALUES (1, 'สาระสำคัญ/ความคิดรวบยอด สอดคล้องกับจุดประสงค์การเรียนรู้', 5, 1)");
        $pdo->exec("INSERT INTO lp_criteria_items (category_id, description, max_score, order_idx) VALUES (1, 'จุดประสงค์การเรียนรู้ครอบคลุม K P A และชัดเจนสามารถวัดและประเมินผลได้', 5, 2)");
        
        // Category 2
        $pdo->exec("INSERT INTO lp_criteria_categories (id, title, order_idx) VALUES (2, 'ด้านกิจกรรมการเรียนรู้', 2)");
        $pdo->exec("INSERT INTO lp_criteria_items (category_id, description, max_score, order_idx) VALUES (2, 'กิจกรรมการเรียนรู้เน้นผู้เรียนเป็นสำคัญ (Active Learning)', 5, 1)");
        $pdo->exec("INSERT INTO lp_criteria_items (category_id, description, max_score, order_idx) VALUES (2, 'มีการบูรณาการทักษะชีวิต ทักษะอาชีพ หรือคุณลักษณะอันพึงประสงค์', 5, 2)");

        // Category 3
        $pdo->exec("INSERT INTO lp_criteria_categories (id, title, order_idx) VALUES (3, 'ด้านสื่อและแหล่งเรียนรู้', 3)");
        $pdo->exec("INSERT INTO lp_criteria_items (category_id, description, max_score, order_idx) VALUES (3, 'เลือกใช้สื่อที่สอดคล้องกับกิจกรรมและวัยของผู้เรียนอย่างเหมาะสม', 5, 1)");
    }

} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
?>

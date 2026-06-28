<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch Categories and their Items
$stmt = $pdo->query("SELECT * FROM criteria_categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

$items_by_cat = [];
$stmt = $pdo->query("SELECT * FROM criteria_items ORDER BY id ASC");
while ($row = $stmt->fetch()) {
    $items_by_cat[$row['category_id']][] = $row;
}
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-list-check"></i> จัดการหัวข้อการประเมิน</h2>
        <p class="text-muted">กำหนดหัวข้อและเกณฑ์การให้คะแนน (5 ระดับ)</p>
    </div>
    <button class="btn-gradient" onclick="openCatModal()"><i class="fas fa-plus"></i> เพิ่มหัวข้อหลัก</button>
</div>

<?php foreach ($categories as $cat): ?>
<div class="content-card" style="margin-bottom: 30px; border-top: 4px solid var(--text-main);">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
        <h3 style="margin: 0; color: #333;"><?php echo htmlspecialchars($cat['title']); ?> <small style="color: #666; font-size: 14px;">(น้ำหนัก <?php echo $cat['weight']; ?>)</small></h3>
        <div>
            <button onclick="openItemModal(<?php echo $cat['id']; ?>)" style="background: #198754; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-right: 5px;"><i class="fas fa-plus"></i> เพิ่มข้อย่อย</button>
            <button onclick='openEditCatModal(<?php echo json_encode($cat); ?>)' style="background: #0d6efd; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-right: 5px;"><i class="fas fa-edit"></i></button>
            <button onclick="deleteCategory(<?php echo $cat['id']; ?>)" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;"><i class="fas fa-trash-alt"></i></button>
        </div>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 70%;">รายละเอียดการประเมิน</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 15%;">คะแนนเต็ม</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 15%;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($items_by_cat[$cat['id']])): ?>
                <?php foreach ($items_by_cat[$cat['id']] as $idx => $item): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?php echo ($idx + 1) . '. ' . htmlspecialchars($item['description']); ?></td>
                    <td style="padding: 10px; text-align: center;"><span style="background: #e9ecef; padding: 2px 8px; border-radius: 10px; font-weight: bold;"><?php echo $item['max_score']; ?></span></td>
                    <td style="padding: 10px; text-align: center;">
                        <button onclick='openEditItemModal(<?php echo json_encode($item); ?>)' style="background: none; border: none; color: #0d6efd; cursor: pointer; margin-right: 10px;"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteItem(<?php echo $item['id']; ?>)" style="background: none; border: none; color: #dc3545; cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="padding: 15px; text-align: center; color: #888;">ยังไม่มีหัวข้อย่อย</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php if (empty($categories)): ?>
    <div class="content-card" style="text-align: center; padding: 40px;">
        <i class="fas fa-folder-open" style="font-size: 40px; color: #ccc; margin-bottom: 15px;"></i>
        <h3 style="color: #666;">ยังไม่มีหัวข้อการประเมิน</h3>
        <p style="color: #888;">คลิกปุ่ม "เพิ่มหัวข้อหลัก" ด้านบนเพื่อเริ่มต้น</p>
    </div>
<?php endif; ?>

<!-- Category Modal -->
<div id="catModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content" style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 id="catModalTitle" style="margin-top: 0; margin-bottom: 20px;">เพิ่มหัวข้อหลัก</h3>
        <form id="catForm">
            <input type="hidden" name="action" id="cat_action" value="create_cat">
            <input type="hidden" name="cat_id" id="cat_id">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ชื่อหัวข้อหลัก <span style="color:red">*</span></label>
                <input type="text" id="cat_title" name="title" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">น้ำหนักคะแนน (รวมทุกหมวดควรได้ 100)</label>
                <input type="number" step="0.01" id="cat_weight" name="weight" value="0" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeCatModal()" style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Item Modal -->
<div id="itemModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content" style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 id="itemModalTitle" style="margin-top: 0; margin-bottom: 20px;">เพิ่มหัวข้อย่อย</h3>
        <form id="itemForm">
            <input type="hidden" name="action" id="item_action" value="create_item">
            <input type="hidden" name="item_id" id="item_id">
            <input type="hidden" name="category_id" id="item_category_id">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">รายละเอียดการประเมิน <span style="color:red">*</span></label>
                <textarea id="item_desc" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">คะแนนเต็ม <span style="color:red">*</span></label>
                <select id="item_max_score" name="max_score" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                    <option value="5">5 (ระดับ 5 เลเวล)</option>
                    <option value="10">10</option>
                </select>
                <small style="color:#666;">* ระบบถูกออกแบบมาให้รองรับการประเมินผล 5 ระดับ (5=ดีมาก ... 1=ปรับปรุง)</small>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeItemModal()" style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
// Category Modal Functions
function openCatModal() {
    document.getElementById('catForm').reset();
    document.getElementById('cat_action').value = 'create_cat';
    document.getElementById('catModalTitle').innerText = 'เพิ่มหัวข้อหลัก';
    document.getElementById('catModal').style.display = 'flex';
}

function openEditCatModal(cat) {
    document.getElementById('catForm').reset();
    document.getElementById('cat_action').value = 'update_cat';
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_title').value = cat.title;
    document.getElementById('cat_weight').value = cat.weight;
    document.getElementById('catModalTitle').innerText = 'แก้ไขหัวข้อหลัก';
    document.getElementById('catModal').style.display = 'flex';
}

function closeCatModal() { document.getElementById('catModal').style.display = 'none'; }

// Item Modal Functions
function openItemModal(catId) {
    document.getElementById('itemForm').reset();
    document.getElementById('item_action').value = 'create_item';
    document.getElementById('item_category_id').value = catId;
    document.getElementById('itemModalTitle').innerText = 'เพิ่มหัวข้อย่อยการประเมิน';
    document.getElementById('itemModal').style.display = 'flex';
}

function openEditItemModal(item) {
    document.getElementById('itemForm').reset();
    document.getElementById('item_action').value = 'update_item';
    document.getElementById('item_id').value = item.id;
    document.getElementById('item_category_id').value = item.category_id;
    document.getElementById('item_desc').value = item.description;
    document.getElementById('item_max_score').value = item.max_score;
    document.getElementById('itemModalTitle').innerText = 'แก้ไขหัวข้อย่อยการประเมิน';
    document.getElementById('itemModal').style.display = 'flex';
}

function closeItemModal() { document.getElementById('itemModal').style.display = 'none'; }

// Submit Handlers
function handleFormSubmit(formId, endpoint) {
    document.getElementById(formId).addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(endpoint, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({icon: 'success', title: 'สำเร็จ!', timer: 1500, showConfirmButton: false}).then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        });
    });
}

handleFormSubmit('catForm', 'criteria_action.php');
handleFormSubmit('itemForm', 'criteria_action.php');

// Delete Handlers
function deleteRecord(action, id, title) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบ " + title + " ใช่หรือไม่ ข้อมูลที่เกี่ยวข้องจะถูกลบไปด้วยทั้งหมด",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', id);
            
            fetch('criteria_action.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

function deleteCategory(id) { deleteRecord('delete_cat', id, 'หมวดหมู่หลักนี้'); }
function deleteItem(id) { deleteRecord('delete_item', id, 'หัวข้อย่อยนี้'); }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

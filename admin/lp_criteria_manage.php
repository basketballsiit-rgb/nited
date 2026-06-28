<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch Categories with their Items
$categories = [];
$stmt = $pdo->query("SELECT * FROM lp_criteria_categories ORDER BY order_idx ASC, id ASC");
while ($row = $stmt->fetch()) {
    $categories[$row['id']] = $row;
    $categories[$row['id']]['items'] = [];
}

$stmt = $pdo->query("SELECT * FROM lp_criteria_items ORDER BY order_idx ASC, id ASC");
while ($row = $stmt->fetch()) {
    if (isset($categories[$row['category_id']])) {
        $categories[$row['category_id']]['items'][] = $row;
    }
}
?>

<div class="content-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2><i class="fas fa-clipboard-check"></i> จัดการฟอร์มตรวจแผนการจัดการเรียนรู้เต็มเล่ม</h2>
        <p class="text-muted">กำหนดหัวข้อและเกณฑ์การให้คะแนนสำหรับ "การตรวจแผนการสอน" โดยเฉพาะ (แยกจากการไปนิเทศ)</p>
    </div>
    <button onclick="openAddCatModal()" class="btn-gradient"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่ใหม่</button>
</div>

<?php foreach ($categories as $cat): ?>
    <div class="content-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #E94057;">
                <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($cat['title']); ?> 
                <span style="font-size: 14px; color: #888; font-weight: normal;">(ลำดับ: <?php echo $cat['order_idx']; ?>)</span>
            </h3>
            <div>
                <button onclick="openEditCatModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['title'])); ?>', <?php echo $cat['order_idx']; ?>)" 
                        style="background: none; border: 1px solid #17a2b8; color: #17a2b8; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-right: 5px;">
                    <i class="fas fa-edit"></i> แก้ไขหมวด
                </button>
                <button onclick="deleteCategory(<?php echo $cat['id']; ?>)" 
                        style="background: none; border: 1px solid #dc3545; color: #dc3545; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-right: 15px;">
                    <i class="fas fa-trash"></i> ลบหมวด
                </button>
                <button onclick="openAddItemModal(<?php echo $cat['id']; ?>)" class="btn-gradient" style="padding: 5px 15px; font-size: 13px;">
                    <i class="fas fa-plus"></i> เพิ่มข้อย่อย
                </button>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 10%;">ลำดับ</th>
                    <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 30%;">หัวข้อ</th>
                    <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 30%;">ตัวชี้วัด/รายการที่ต้องมี</th>
                    <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 15%;">คะแนนเต็ม</th>
                    <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 15%;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cat['items'] as $idx => $item): ?>
                    <?php
                    $bg_color = $item['is_header'] ? '#e9ecef' : 'transparent';
                    $font_weight = $item['is_header'] ? 'bold' : 'normal';
                    $desc_text = htmlspecialchars($item['description']);
                    if ($item['is_optional']) {
                        $desc_text .= ' <span style="font-size: 12px; color: #dc3545; font-weight: normal;">(มีตัวเลือก มี/ไม่มี)</span>';
                    }
                    ?>
                    <tr style="border-bottom: 1px solid #eee; background-color: <?php echo $bg_color; ?>;">
                        <td style="padding: 10px; font-weight: <?php echo $font_weight; ?>;"><?php echo $item['order_idx']; ?></td>
                        <td style="padding: 10px; font-weight: <?php echo $font_weight; ?>;"><?php echo $desc_text; ?></td>
                        <td style="padding: 10px; color: #555;"><?php echo $item['is_header'] ? '' : htmlspecialchars($item['indicator'] ?? '-'); ?></td>
                        <td style="padding: 10px; text-align: center; font-weight: bold; color: #4f46e5;"><?php echo $item['is_header'] ? '-' : $item['max_score']; ?></td>
                        <td style="padding: 10px; text-align: center;">
                            <button onclick="openEditItemModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['description'])); ?>', '<?php echo htmlspecialchars(addslashes($item['indicator'] ?? '')); ?>', <?php echo $item['max_score']; ?>, <?php echo $item['order_idx']; ?>, <?php echo $item['is_header'] ? 'true' : 'false'; ?>, <?php echo $item['is_optional'] ? 'true' : 'false'; ?>)" 
                                    style="background: none; border: none; color: #17a2b8; cursor: pointer; margin-right: 10px;"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteItem(<?php echo $item['id']; ?>)" 
                                    style="background: none; border: none; color: #dc3545; cursor: pointer;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($cat['items'])): ?>
                    <tr><td colspan="5" style="padding: 15px; text-align: center; color: #aaa;">ยังไม่มีข้อย่อยประเมินในหมวดหมู่นี้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
<?php if (empty($categories)): ?>
    <div class="content-card" style="text-align: center; padding: 40px; color: #888;">
        ยังไม่มีข้อมูลฟอร์มการประเมิน กรุณากดปุ่ม "เพิ่มหมวดหมู่ใหม่"
    </div>
<?php endif; ?>

<!-- Modals -->
<!-- Add/Edit Category Modal -->
<div id="catModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content" style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 id="catModalTitle" style="margin-top: 0;">จัดการหมวดหมู่</h3>
        <form id="catForm">
            <input type="hidden" name="action" id="catAction" value="add_cat">
            <input type="hidden" name="id" id="catId" value="0">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ชื่อหมวดหมู่</label>
                <input type="text" name="title" id="catTitle" class="form-control" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ลำดับการแสดงผล</label>
                <input type="number" name="order_idx" id="catOrder" class="form-control" value="0" required>
            </div>
            
            <div style="text-align: right;">
                <button type="button" onclick="closeModal('catModal')" style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div id="itemModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content" style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 id="itemModalTitle" style="margin-top: 0;">จัดการข้อย่อยประเมิน</h3>
        <form id="itemForm">
            <input type="hidden" name="action" id="itemAction" value="add_item">
            <input type="hidden" name="id" id="itemId" value="0">
            <input type="hidden" name="category_id" id="itemCatId" value="0">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="is_header" id="itemIsHeader" value="1" onchange="toggleItemFields()"> 
                    ตั้งเป็น <strong>"หัวข้อย่อยแบบกลุ่ม" (Sub-header)</strong> (ไม่มีการให้คะแนนในข้อนี้)
                </label>
                <label id="itemOptionalContainer" style="display: none; margin-top: 10px; margin-left: 20px; color: #dc3545;">
                    <input type="checkbox" name="is_optional" id="itemIsOptional" value="1"> 
                    ให้เลือก <strong>มี / ไม่มี</strong> ได้ (หากเลือกไม่มี จะไม่นำคะแนนข้อย่อยไปคิดรวม)
                </label>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;" id="lblDescription">หัวข้อ</label>
                <input type="text" name="description" id="itemDesc" class="form-control" required>
            </div>
            
            <div id="itemDetailFields">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">ตัวชี้วัด/รายการที่ต้องมี (ถ้ามี)</label>
                    <textarea name="indicator" id="itemIndicator" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px;">คะแนนเต็ม</label>
                        <input type="number" name="max_score" id="itemMaxScore" class="form-control" value="5" min="1" max="100">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px;">ลำดับ</label>
                        <input type="number" name="order_idx" id="itemOrder" class="form-control" value="0" required>
                    </div>
                </div>
            </div>
            <div id="itemOrderFieldOnly" style="display: none; margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ลำดับ</label>
                <input type="number" id="itemOrderHeader" class="form-control" value="0">
            </div>
            
            <div style="text-align: right;">
                <button type="button" onclick="closeModal('itemModal')" style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Category Handlers
function openAddCatModal() {
    document.getElementById('catModalTitle').innerText = 'เพิ่มหมวดหมู่ใหม่';
    document.getElementById('catAction').value = 'add_cat';
    document.getElementById('catForm').reset();
    document.getElementById('catModal').style.display = 'flex';
}
function openEditCatModal(id, title, order) {
    document.getElementById('catModalTitle').innerText = 'แก้ไขหมวดหมู่';
    document.getElementById('catAction').value = 'edit_cat';
    document.getElementById('catId').value = id;
    document.getElementById('catTitle').value = title;
    document.getElementById('catOrder').value = order;
    document.getElementById('catModal').style.display = 'flex';
}
function deleteCategory(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?', text: "ข้อย่อยทั้งหมดในหมวดหมู่นี้จะถูกลบไปด้วย!", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'ลบเลย'
    }).then((result) => {
        if (result.isConfirmed) submitAction({action: 'delete_cat', id: id});
    });
}

// Item Handlers
function toggleItemFields() {
    const isHeader = document.getElementById('itemIsHeader').checked;
    document.getElementById('itemDetailFields').style.display = isHeader ? 'none' : 'block';
    document.getElementById('itemOrderFieldOnly').style.display = isHeader ? 'block' : 'none';
    document.getElementById('lblDescription').innerText = isHeader ? 'ชื่อกลุ่มหัวข้อย่อย' : 'หัวข้อ';
    document.getElementById('itemOptionalContainer').style.display = isHeader ? 'block' : 'none';
    if (!isHeader) document.getElementById('itemIsOptional').checked = false;
    
    // Manage required attributes
    if (isHeader) {
        document.getElementById('itemMaxScore').removeAttribute('required');
        document.getElementById('itemOrder').removeAttribute('required');
        document.getElementById('itemOrder').name = '';
        document.getElementById('itemOrderHeader').name = 'order_idx';
        document.getElementById('itemOrderHeader').setAttribute('required', 'required');
    } else {
        document.getElementById('itemMaxScore').setAttribute('required', 'required');
        document.getElementById('itemOrder').setAttribute('required', 'required');
        document.getElementById('itemOrder').name = 'order_idx';
        document.getElementById('itemOrderHeader').name = '';
        document.getElementById('itemOrderHeader').removeAttribute('required');
    }
}

function openAddItemModal(catId) {
    document.getElementById('itemModalTitle').innerText = 'เพิ่มข้อประเมินย่อย';
    document.getElementById('itemAction').value = 'add_item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemIsHeader').checked = false;
    document.getElementById('itemIsOptional').checked = false;
    document.getElementById('itemCatId').value = catId;
    toggleItemFields();
    document.getElementById('itemModal').style.display = 'flex';
}
function openEditItemModal(id, desc, indicator, maxScore, order, isHeader, isOptional) {
    document.getElementById('itemModalTitle').innerText = 'แก้ไขข้อประเมินย่อย';
    document.getElementById('itemAction').value = 'edit_item';
    document.getElementById('itemId').value = id;
    document.getElementById('itemIsHeader').checked = isHeader;
    document.getElementById('itemIsOptional').checked = isOptional;
    document.getElementById('itemDesc').value = desc;
    document.getElementById('itemIndicator').value = indicator;
    document.getElementById('itemMaxScore').value = maxScore;
    document.getElementById('itemOrder').value = order;
    document.getElementById('itemOrderHeader').value = order;
    toggleItemFields();
    document.getElementById('itemModal').style.display = 'flex';
}
function deleteItem(id) {
    Swal.fire({
        title: 'ยืนยันการลบข้อประเมินนี้?', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'ลบ'
    }).then((result) => {
        if (result.isConfirmed) submitAction({action: 'delete_item', id: id});
    });
}

// Form Submission Hook
document.getElementById('catForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitAction(Object.fromEntries(new FormData(this)));
});
document.getElementById('itemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitAction(Object.fromEntries(new FormData(this)));
});

// AJAX Sender
function submitAction(dataObj) {
    const formData = new FormData();
    for (const key in dataObj) { formData.append(key, dataObj[key]); }

    fetch('lp_criteria_action.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1000, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            Swal.fire('ข้อผิดพลาด', data.message, 'error');
        }
    }).catch(e => Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../db.php';
admin_required();
$pdo = db(); ensure_room_public_ids($pdo); $error = ''; $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? 'save';
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'delete') {
            $paths = room_image_paths($pdo, $id);
            $pdo->prepare('DELETE FROM rooms WHERE id=?')->execute([$id]);
            foreach ($paths as $path) delete_uploaded_image($path);
            $message = 'Room and its photos deleted.';
        } elseif ($action === 'delete_cover') {
            $stmt = $pdo->prepare('SELECT cover_path FROM rooms WHERE id=?'); $stmt->execute([$id]); $path = $stmt->fetchColumn();
            $pdo->prepare('UPDATE rooms SET cover_path=NULL WHERE id=?')->execute([$id]);
            delete_uploaded_image($path ?: null); $message = 'Cover photo deleted.';
        } elseif ($action === 'delete_gallery_image') {
            $imageId = (int)($_POST['image_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_path FROM room_images WHERE id=? AND room_id=?'); $stmt->execute([$imageId, $id]); $path = $stmt->fetchColumn();
            if (!$path) throw new RuntimeException('That gallery photo could not be found.');
            $pdo->prepare('DELETE FROM room_images WHERE id=? AND room_id=?')->execute([$imageId, $id]);
            delete_uploaded_image($path); $message = 'Gallery photo deleted.';
        } elseif ($action === 'replace_gallery_image') {
            $imageId = (int)($_POST['image_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_path FROM room_images WHERE id=? AND room_id=?'); $stmt->execute([$imageId, $id]); $oldPath = $stmt->fetchColumn();
            if (!$oldPath) throw new RuntimeException('That gallery photo could not be found.');
            $newPath = upload_image($_FILES['replacement'] ?? [], 'rooms');
            if (!$newPath) throw new RuntimeException('Choose a replacement image first.');
            $pdo->prepare('UPDATE room_images SET image_path=? WHERE id=? AND room_id=?')->execute([$newPath, $imageId, $id]);
            delete_uploaded_image($oldPath); $message = 'Gallery photo replaced. The new image is compressed as WebP.';
        } else {
            $categoryName = trim($_POST['category_name'] ?? '');
            $stmt = $pdo->prepare('SELECT id FROM room_categories WHERE title=? OR slug=? LIMIT 1'); $stmt->execute([$categoryName, $categoryName]); $category = (int)($stmt->fetchColumn() ?: 0);
            $hotelName = trim($_POST['hotel_name'] ?? ''); $hotelId = null;
            if ($hotelName !== '') { $stmt = $pdo->prepare('SELECT id FROM hotels WHERE name=? LIMIT 1'); $stmt->execute([$hotelName]); $hotelId = (int)($stmt->fetchColumn() ?: 0); if (!$hotelId) throw new RuntimeException('Choose a partner hotel from the suggestions or leave it blank.'); }
            $name = trim($_POST['name'] ?? ''); $location = trim($_POST['location'] ?? ''); $description = trim($_POST['description'] ?? ''); $price = (float)($_POST['price_from'] ?? 0);
            if (!$category || $name === '' || $location === '' || $description === '' || $price <= 0) throw new RuntimeException('Category, room name, location, description, and starting price are required.');
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_categories WHERE id=?'); $stmt->execute([$category]); if (!$stmt->fetchColumn()) throw new RuntimeException('Choose an existing category.');
            $newCover = upload_image($_FILES['cover'] ?? [], 'rooms');
            $oldCover = null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT cover_path FROM rooms WHERE id=?'); $stmt->execute([$id]); $oldCover = $stmt->fetchColumn() ?: null;
                $sql = 'UPDATE rooms SET category_id=?,hotel_id=?,name=?,location=?,description=?,price_from=?,guest_capacity=?,room_size=?,amenities=?,is_available=?'.($newCover ? ',cover_path=?' : '').' WHERE id=?';
                $args = [$category,$hotelId,$name,$location,$description,$price,trim($_POST['guest_capacity']??''),trim($_POST['room_size']??''),trim($_POST['amenities']??''),isset($_POST['is_available'])?1:0];
                if ($newCover) $args[] = $newCover; $args[] = $id; $pdo->prepare($sql)->execute($args);
            } else {
                if (!$newCover) throw new RuntimeException('Upload a main room photo.');
                $publicId = generate_room_public_id($pdo);
                $pdo->prepare('INSERT INTO rooms(public_id,category_id,hotel_id,name,location,description,price_from,guest_capacity,room_size,amenities,cover_path,is_available) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$publicId,$category,$hotelId,$name,$location,$description,$price,trim($_POST['guest_capacity']??''),trim($_POST['room_size']??''),trim($_POST['amenities']??''),$newCover,isset($_POST['is_available'])?1:0]);
                $id = (int)$pdo->lastInsertId();
            }
            if ($newCover && $oldCover) delete_uploaded_image($oldCover);
            $files = $_FILES['gallery'] ?? [];
            foreach (($files['name'] ?? []) as $index => $original) {
                if (!$original) continue;
                $one = []; foreach (['name','type','tmp_name','error','size'] as $key) $one[$key] = $files[$key][$index] ?? null;
                $path = upload_image($one, 'rooms');
                if ($path) $pdo->prepare('INSERT INTO room_images(room_id,image_path,sort_order) VALUES(?,?,?)')->execute([$id,$path,$index]);
            }
            $message = 'Room saved. Uploaded photos are compressed as WebP.';
        }
    } catch (Throwable $e) { $error = $e instanceof PDOException ? 'Could not save the room. Check the category, hotel, and image files.' : $e->getMessage(); }
}

$edit = null; $editImages = [];
if (isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM rooms WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch(PDO::FETCH_ASSOC) ?: null; if ($edit) { $stmt=$pdo->prepare('SELECT * FROM room_images WHERE room_id=? ORDER BY sort_order,id'); $stmt->execute([$edit['id']]); $editImages=$stmt->fetchAll(PDO::FETCH_ASSOC); } }
$categories=$pdo->query('SELECT id,title FROM room_categories ORDER BY sort_order,title')->fetchAll(PDO::FETCH_ASSOC);
$hotels=$pdo->query('SELECT id,name FROM hotels ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$list=$pdo->query('SELECT r.*,c.title AS category_title FROM rooms r JOIN room_categories c ON c.id=r.category_id ORDER BY r.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
admin_header('Rooms');
if ($message): ?><div class="admin-alert ok"><?=e($message)?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert"><?=e($error)?></div><?php endif; ?>
<section class="admin-card"><h2><?=$edit?'Edit':'Add'?> room</h2>
<?php if (!$categories): ?><p>Create a room category before adding rooms.</p><a class="admin-button" href="/admin/categories.php">Create category</a><?php else: ?>
<form class="admin-form" method="post" enctype="multipart/form-data"><?=csrf_field()?>
<input type="hidden" name="id" value="<?=e($edit['id']??'')?>">
<div class="admin-form-grid">
<label>Room category<input required name="category_name" list="room-category-options" value="<?php foreach($categories as $c){if(($edit['category_id']??0)==$c['id'])echo e($c['title']);}?>" placeholder="Type or choose a category"><datalist id="room-category-options"><?php foreach($categories as $c):?><option value="<?=e($c['title'])?>"><?php endforeach;?></datalist></label>
<label>Partner hotel (admin only)<input name="hotel_name" list="hotel-options" value="<?php foreach($hotels as $h){if(($edit['hotel_id']??0)==$h['id'])echo e($h['name']);}?>" placeholder="Optional hotel name"><datalist id="hotel-options"><?php foreach($hotels as $h):?><option value="<?=e($h['name'])?>"><?php endforeach;?></datalist></label>
<label>Room name<input required name="name" value="<?=e($edit['name']??'')?>"></label><label>Location<input required name="location" value="<?=e($edit['location']??'')?>"></label><label>Starting price per night (৳)<input required type="number" min="1" step="1" name="price_from" value="<?=e($edit['price_from']??'')?>"></label><label>Room size<input name="room_size" value="<?=e($edit['room_size']??'')?>" placeholder="420 sq ft"></label><label>Guest capacity<input name="guest_capacity" value="<?=e($edit['guest_capacity']??'')?>" placeholder="2 guests"></label><label class="full">Amenities (one per line)<textarea name="amenities"><?=e($edit['amenities']??'')?></textarea></label><label class="full">Room details<textarea required name="description"><?=e($edit['description']??'')?></textarea></label>
<label class="full">Cover photo (choose a new file to replace the current cover; saved as WebP)<input type="file" name="cover" accept="image/*" <?=$edit?'':'required'?>></label>
<label class="full">Additional photos (select multiple; saved as WebP)<input type="file" name="gallery[]" accept="image/*" multiple></label>
<label class="check-line"><input type="checkbox" name="is_available" <?=!isset($edit['is_available'])||$edit['is_available']?'checked':''?>>Available to display</label></div>
<p><button class="admin-button">Save room</button></p></form>
<?php if ($edit): ?><div class="admin-image-manager"><h3>Room photos</h3><?php if (!empty($edit['cover_path'])): ?><div class="managed-image"><img class="admin-thumb" src="/<?=e($edit['cover_path'])?>" alt="Room cover"><span>Cover photo</span><form method="post" onsubmit="return confirm('Delete this cover photo from the server?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_cover"><input type="hidden" name="id" value="<?=$edit['id']?>"><button class="admin-button danger">Delete image</button></form></div><?php endif; ?><?php foreach ($editImages as $im): ?><div class="managed-image"><img class="admin-thumb" src="/<?=e($im['image_path'])?>" alt="Room photo"><span>Gallery photo</span><form class="replace-image-form" method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="replace_gallery_image"><input type="hidden" name="id" value="<?=$edit['id']?>"><input type="hidden" name="image_id" value="<?=$im['id']?>"><input required type="file" name="replacement" accept="image/*" aria-label="Choose replacement photo"><button class="admin-button">Replace image</button></form><form method="post" onsubmit="return confirm('Delete this photo from the server?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_gallery_image"><input type="hidden" name="id" value="<?=$edit['id']?>"><input type="hidden" name="image_id" value="<?=$im['id']?>"><button class="admin-button danger">Delete image</button></form></div><?php endforeach; ?></div><?php endif; ?><?php endif; ?></section>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Photo</th><th>Room</th><th>Category</th><th>Location</th><th>Price/night</th><th>Actions</th></tr></thead><tbody><?php foreach($list as $r):?><tr><td><?php if($r['cover_path']):?><img class="admin-thumb" src="/<?=e($r['cover_path'])?>" alt=""><?php endif;?></td><td><?=e($r['name'])?></td><td><?=e($r['category_title'])?></td><td><?=e($r['location'])?></td><td>৳ <?=e(number_format((float)$r['price_from']))?></td><td><div class="admin-actions"><a class="admin-button" href="?edit=<?=$r['id']?>">Edit</a><form method="post" onsubmit="return confirm('Delete this room and all of its photos from the server?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="admin-button danger">Delete</button></form></div></td></tr><?php endforeach;?><?php if(!$list):?><tr><td colspan="6">No rooms yet.</td></tr><?php endif;?></tbody></table></div><?php admin_footer();

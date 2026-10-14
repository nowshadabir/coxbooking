<?php
require_once __DIR__ . '/../db.php'; admin_required(); $pdo=db(); $error=''; $message='';
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); try {
    $action=$_POST['action']??'save'; $id=(int)($_POST['id']??0);
    if ($action==='delete' || $action==='delete_logo') {
        $stmt=$pdo->prepare('SELECT logo_path FROM hotels WHERE id=?'); $stmt->execute([$id]); $path=$stmt->fetchColumn()?:null;
        if ($action==='delete') { $pdo->prepare('DELETE FROM hotels WHERE id=?')->execute([$id]); $message='Partner hotel and its logo deleted.'; }
        else { $pdo->prepare('UPDATE hotels SET logo_path=NULL WHERE id=?')->execute([$id]); $message='Hotel logo deleted.'; }
        delete_uploaded_image($path);
    } else {
        $name=trim($_POST['name']??''); $location=trim($_POST['location']??'');
        if ($name===''||$location==='') throw new RuntimeException('Hotel name and location are required.');
        $logo=upload_image($_FILES['logo']??[],'hotels'); $oldLogo=null;
        if ($id) { $stmt=$pdo->prepare('SELECT logo_path FROM hotels WHERE id=?'); $stmt->execute([$id]); $oldLogo=$stmt->fetchColumn()?:null;
            if ($logo) $pdo->prepare('UPDATE hotels SET name=?,location=?,logo_path=? WHERE id=?')->execute([$name,$location,$logo,$id]);
            else $pdo->prepare('UPDATE hotels SET name=?,location=? WHERE id=?')->execute([$name,$location,$id]);
        } else $pdo->prepare('INSERT INTO hotels(name,location,logo_path) VALUES(?,?,?)')->execute([$name,$location,$logo]);
        if ($logo&&$oldLogo) delete_uploaded_image($oldLogo);
        $message='Partner hotel saved. Uploaded logos are compressed as WebP.';
    }
} catch(Throwable $e) { $error=$e->getMessage(); } }
$edit=null; if(isset($_GET['edit'])){$stmt=$pdo->prepare('SELECT * FROM hotels WHERE id=?');$stmt->execute([(int)$_GET['edit']]);$edit=$stmt->fetch(PDO::FETCH_ASSOC)?:null;}
$hotels=$pdo->query('SELECT * FROM hotels ORDER BY name')->fetchAll(PDO::FETCH_ASSOC); admin_header('Partner hotels');
if($message):?><div class="admin-alert ok"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="admin-alert"><?=e($error)?></div><?php endif;?>
<section class="admin-card"><h2><?=$edit?'Edit':'Add'?> partner hotel</h2><?php if(!empty($edit['logo_path'])):?><div class="managed-image"><img class="admin-thumb" src="/<?=e($edit['logo_path'])?>" alt="Current hotel logo"><form method="post" onsubmit="return confirm('Delete this logo from the server?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_logo"><input type="hidden" name="id" value="<?=$edit['id']?>"><button class="admin-button danger">Delete logo</button></form></div><?php endif;?><form class="admin-form" method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="id" value="<?=e($edit['id']??'')?>"><div class="admin-form-grid"><label>Hotel name<input required name="name" value="<?=e($edit['name']??'')?>"></label><label>Location<input required name="location" value="<?=e($edit['location']??'')?>"></label><label class="full">Logo (max 8 MB; supported images are compressed to WebP)<input type="file" name="logo" accept="image/*"></label></div><p><button class="admin-button">Save hotel</button></p></form></section>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Logo</th><th>Hotel</th><th>Location</th><th>Actions</th></tr></thead><tbody><?php foreach($hotels as $h):?><tr><td><?php if($h['logo_path']):?><img class="admin-thumb" src="/<?=e($h['logo_path'])?>" alt=""><?php endif;?></td><td><?=e($h['name'])?></td><td><?=e($h['location'])?></td><td><div class="admin-actions"><a class="admin-button" href="?edit=<?=$h['id']?>">Edit</a><form method="post" onsubmit="return confirm('Delete this hotel and its logo?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$h['id']?>"><button class="admin-button danger">Delete</button></form></div></td></tr><?php endforeach;?><?php if(!$hotels):?><tr><td colspan="4">No hotels yet.</td></tr><?php endif;?></tbody></table></div><?php admin_footer();

<?php
require_once __DIR__ . '/../db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_id'])) { require __DIR__ . '/login.php'; exit; }
admin_required();
$pdo=db();
$counts=[];
foreach(['requests'=>'Requests','hotels'=>'Partner hotels','room_categories'=>'Room categories','rooms'=>'Rooms'] as $table=>$label) $counts[$label]=(int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
$newRequests=(int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status='new'")->fetchColumn();
$recent=$pdo->query('SELECT id,request_type,guest_name,email,room_name,property_name,status,created_at FROM requests ORDER BY created_at DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
admin_header('Overview');
?>
<p class="dashboard-intro">A quick view of your listings and guest enquiries.</p>
<div class="stat-grid">
<?php foreach($counts as $label=>$count): $href=['Requests'=>'/admin/requests.php','Partner hotels'=>'/admin/hotels.php','Room categories'=>'/admin/categories.php','Rooms'=>'/admin/rooms.php'][$label]; ?>
<a class="stat-box" href="<?=$href?>"><b><?=$count?></b><span><?=e($label)?></span></a>
<?php endforeach; ?>
</div>
<section class="admin-card"><h2>Quick actions</h2><div class="dashboard-actions">
<a class="dashboard-action" href="/admin/requests.php"><span><b>Review guest requests</b><small><?=$newRequests?> new <?= $newRequests===1?'request':'requests' ?> to follow up</small></span><span aria-hidden="true">↗</span></a>
<a class="dashboard-action" href="/admin/hotels.php"><span><b>Add a partner hotel</b><small>Manage hotel name, location and logo</small></span><span aria-hidden="true">＋</span></a>
<a class="dashboard-action" href="/admin/categories.php"><span><b>Create a room category</b><small>Set homepage title, location and price</small></span><span aria-hidden="true">＋</span></a>
<a class="dashboard-action" href="/admin/rooms.php"><span><b>Add a room</b><small>Upload room photos and details</small></span><span aria-hidden="true">＋</span></a>
</div></section>
<section class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Type</th><th>Guest/contact</th><th>Room/property</th><th>Status</th></tr></thead><tbody>
<?php foreach($recent as $r): ?><tr><td><?=e($r['created_at'])?></td><td><?=e($r['request_type'])?></td><td><?=e($r['guest_name'])?><br><?=e($r['email'])?></td><td><?=e($r['room_name']?:$r['property_name'])?></td><td><span class="admin-status"><?=e($r['status'])?></span></td></tr><?php endforeach; ?>
<?php if(!$recent): ?><tr><td colspan="5">No requests yet. New guest and partner enquiries will appear here.</td></tr><?php endif; ?>
</tbody></table></section>
<?php admin_footer();

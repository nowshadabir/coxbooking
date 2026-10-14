<?php
require_once __DIR__ . '/db.php';
$pdo = db();
ensure_room_public_ids($pdo);
$roomCategories = [];
foreach ($pdo->query('SELECT * FROM room_categories ORDER BY sort_order,id')->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
    $roomCategories[$categoryRow['slug']] = ['id'=>(int)$categoryRow['id'],'title'=>$categoryRow['title'],'cover'=>$categoryRow['cover_path'],'location'=>$categoryRow['location']??'Cox’s Bazar','price_from'=>$categoryRow['price_from'],'price_to'=>$categoryRow['price_to'],'is_featured'=>(bool)$categoryRow['is_featured'],'description'=>$categoryRow['description']];
}
$rooms = [];
$roomRows = $pdo->query('SELECT r.*,c.slug AS category_slug,c.title AS category_title FROM rooms r JOIN room_categories c ON c.id=r.category_id WHERE r.is_available=1 ORDER BY r.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$imageStmt = $pdo->prepare('SELECT image_path FROM room_images WHERE room_id=? ORDER BY sort_order,id');
foreach ($roomRows as $row) {
    $imageStmt->execute([$row['id']]);
    $images = array_column($imageStmt->fetchAll(PDO::FETCH_ASSOC), 'image_path');
    if ($row['cover_path']) array_unshift($images, $row['cover_path']);
    $amenities = preg_split('/\r?\n|,/', (string)$row['amenities'], -1, PREG_SPLIT_NO_EMPTY);
    $rooms[] = ['id'=>(int)$row['id'],'public_id'=>$row['public_id'],'category'=>$row['category_slug'],'category_id'=>(int)$row['category_id'],'category_title'=>$row['category_title'],'name'=>$row['name'],'location'=>$row['location'],'description'=>$row['description'],'price'=>'৳ ' . number_format((float)$row['price_from']),'price_value'=>$row['price_from'],'size'=>$row['room_size']??'','guests'=>$row['guest_capacity']??'','amenities'=>array_map('trim',$amenities?:[]),'images'=>$images];
}
function roomPhoto(string $path, int $width=1000): string {
    if (preg_match('~^https?://~i', $path)) return $path;
    return '/' . ltrim($path, '/');
}

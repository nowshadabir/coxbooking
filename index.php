<?php
require __DIR__ . '/room-data.php';
$featuredCategories = [];
$partnerHotels = $pdo->query('SELECT name,location,logo_path FROM hotels ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
foreach ($roomCategories as $categoryId => $category) {
    if (empty($category['is_featured']))
        continue;
    $matchingRooms = array_values(array_filter($rooms, fn($room) => $room['category'] === $categoryId));
    $firstRoom = $matchingRooms[0] ?? null;
    if ($firstRoom)
        $featuredCategories[] = ['id' => $categoryId, 'category' => $category, 'room' => $firstRoom];
}
?><!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#f8f6f0">
    <title>Cox Booking — A slower kind of escape</title>
    <meta name="description"
        content="Thoughtful stays along the coast. Discover handpicked hotels in Cox’s Bazar with Cox Booking.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bengali-ui.css">
    <link rel="stylesheet" href="responsive.css">
</head>

<body>
    <header class="topbar"><a class="brand" href="index.php" aria-label="Cox Booking home"><span
                class="brand-mark">c.</span><span>cox<span class="brand-light">booking</span></span></a>
        <nav class="desktop-nav" id="primary-navigation"><a class="active" href="index.php#room-categories">Stays</a><a
                href="about.php">Our story</a><a href="partner.php">For hoteliers</a></nav><a class="nav-cta"
            href="request.php">Plan your stay <span>↗</span></a><button class="menu-toggle" type="button"
            aria-label="Open navigation" aria-controls="primary-navigation" aria-expanded="false"><span
                aria-hidden="true">☰</span></button>
    </header>
    <main>
        <section class="hero"><img class="hero-image" src="https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=2200&q=85"
                alt="Soft waves rolling onto the Cox’s Bazar shoreline">
            <div class="hero-shade"></div>
            <div class="hero-content">
                <div class="eyebrow light"><span></span> A little closer to the sea</div>
                <h1>Let the coast<br><em>come to you.</em></h1>
                <p>Considered stays. A quieter kind of escape.<br>Find your place by the Bay of Bengal.</p><a
                    class="button button-cream" href="request.php">Find your stay <span>↗</span></a>
            </div>
            <div class="hero-note"><span>21° 25′ N &nbsp; 91° 58′ E</span><span>COX’S BAZAR, BANGLADESH</span></div>
            <div class="hero-counter">01 <i></i> 03</div>
        </section>
        <section class="intro wrap">
            <div class="eyebrow"><span></span> A stay that feels like yours</div>
            <div class="intro-row">
                <h2>More than a place<br>to <em>stay awhile.</em></h2>
                <div class="intro-copy">
                    <p>We know the coast, and the people who make it special. Our handpicked stays bring you closer to
                        both — with thoughtful details and a little local insight along the way.</p><a class="text-link"
                        href="about.php">A little about us <b>↗</b></a>
                </div>
            </div>
            <div class="trust-line"><span>Thoughtfully chosen, never endless</span><span
                    class="trust-rule"></span><span>Local people. Real recommendations.</span></div>
        </section>
        <section class="partners wrap" id="partners">
            <div class="section-head">
                <div>
                    <div class="eyebrow"><span></span> Good people, lovely places</div>
                    <h2>Our <em>partners</em></h2>
                </div>
                <p>Independent places we know and trust, chosen for the welcome as much as the view.</p>
            </div>
            <div class="partner-strip"><?php foreach ($partnerHotels as $hotel): ?>
                    <div class="partner-item"><?php if ($hotel['logo_path']): ?><img class="partner-logo"
                                src="<?= e(roomPhoto($hotel['logo_path'])) ?>"
                                alt="<?= e($hotel['name']) ?> logo"><?php else: ?><span
                                class="partner-symbol"><?= e(preg_match('/^./us', $hotel['name'], $m) ? $m[0] : '⌂') ?></span><?php endif; ?><span><b><?= e($hotel['name']) ?></b><small><?= e($hotel['location']) ?></small></span>
                    </div><?php endforeach; ?><?php if (!$partnerHotels): ?>
                    <p class="partner-empty">Our local hotel partners will be featured here.</p><?php endif; ?>
            </div>
        </section>
        <section class="featured" id="room-categories">
            <div class="wrap">
                <div class="section-head featured-head">
                    <div>
                        <div class="eyebrow"><span></span> Find your kind of stay</div>
                        <h2>Room <em>Categories</em></h2>
                        <p class="category-instruction">Select a category to browse the rooms available.</p>
                    </div>
                </div>
                <div class="stay-grid"><?php foreach ($featuredCategories as $item):
                    $category = $item['category']; ?><a
                            class="stay-card category-card" href="rooms.php?category=<?= rawurlencode($item['id']) ?>">
                            <div class="stay-photo category-cover"><span class="category-cover-index">COX BOOKING <i>·</i>
                                    ROOM COLLECTION</span><span class="category-cover-mark" aria-hidden="true">c.</span>
                                <div class="category-cover-copy"><span>ROOM
                                        CATEGORY</span><strong><?= e($category['title']) ?></strong><small><?= e($category['location']) ?></small>
                                </div><span class="category-cover-orbit" aria-hidden="true"></span>
                            </div>
                            <div class="stay-bottom category-card-bottom"><span class="stay-price">From <b>৳
                                        <?= e(number_format((float) $category['price_from'])) ?>    <?= !empty($category['price_to']) ? ' – ' . e(number_format((float) $category['price_to'])) : '' ?></b>
                                    <small>/ night</small></span><span class="category-card-arrow"
                                    aria-hidden="true">↗</span></div>
                        </a><?php endforeach; ?></div>
            </div>
        </section>
        <section class="note-section wrap">
            <div class="note-image"><img src="https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&w=1100&q=85"
                    alt="A quiet stretch of Cox’s Bazar coast at golden hour"><span class="image-caption">A slower
                    afternoon, somewhere along the coast.</span></div>
            <div class="note-copy">
                <div class="eyebrow"><span></span> A note from the coast</div>
                <h2>Some places<br>stay with <em>you.</em></h2>
                <p>It’s the first breeze through an open window. The tea that arrives without asking. The long walk when
                    the shore seems to go on forever.</p>
                <p>We started Cox Booking to help you find a little more of that — with people who care about your time
                    here.</p><a class="text-link" href="about.php">Meet Cox Booking <b>↗</b></a>
                <div class="signature">See you by the sea <span>— The Cox Booking team</span></div>
            </div>
        </section>
        <section class="host-cta">
            <div class="wrap host-row">
                <div>
                    <div class="eyebrow light"><span></span> For the people who make it special</div>
                    <h2>Have a place<br>by the <em>shore?</em></h2>
                </div>
                <div class="host-copy">
                    <p>We partner with hotels that care about a good stay. Let’s help more guests find their way to you.
                    </p><a class="button button-outline" href="partner.php">Let’s partner up <span>↗</span></a>
                </div>
                <div class="host-orb"></div>
            </div>
        </section>
    </main>
    <footer class="footer">
        <div class="wrap footer-top"><a class="brand" href="index.php"><span class="brand-mark">c.</span><span>cox<span
                        class="brand-light">booking</span></span></a>
            <div class="footer-note">Thoughtful stays by the Bay of Bengal.<br>Made with care, in Cox’s Bazar.</div>
            <div class="footer-links"><a href="about.php">Our story</a><a href="partner.php">Partner with us</a><a
                    href="request.php">Request a stay</a></div>
        </div>
        <div class="wrap footer-bottom"><span>© <?= date('Y') ?> Cox Booking</span><span>Managed by <a
                    href="https://vivagotechnologies.com" target="_blank" rel="noopener">Vivago
                    Technologies</a></span><span>COX’S BAZAR · BANGLADESH</span></div>
    </footer>
    <script src="app.js?v=3"></script>
</body>

</html>
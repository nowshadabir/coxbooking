<?php
require_once __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$sent = false;
$formError = '';
$insertedId = 0;
$submissionData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $pdo = db();
        ensure_saint_martin_table($pdo);

        $name = trim($_POST['name'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $members = trim($_POST['members'] ?? '');
        $serviceChoice = trim($_POST['service_choice'] ?? 'whole_package');
        
        $needShip = (!empty($_POST['need_ship']) || $serviceChoice === 'whole_package' || $serviceChoice === 'ship_ticket') ? 1 : 0;
        $needHotel = (!empty($_POST['need_hotel']) || $serviceChoice === 'whole_package' || $serviceChoice === 'hotel') ? 1 : 0;
        
        // If whole package is selected, both are needed
        if ($serviceChoice === 'whole_package') {
            $needShip = 1;
            $needHotel = 1;
            $serviceLabel = 'Whole Package (Ship + Hotel + Support)';
        } elseif ($serviceChoice === 'ship_ticket') {
            $serviceLabel = 'Ship Ticket Only';
        } elseif ($serviceChoice === 'hotel') {
            $serviceLabel = 'Hotel / Resort Only';
        } else {
            $parts = [];
            if ($needShip) $parts[] = 'Ship Ticket';
            if ($needHotel) $parts[] = 'Hotel / Resort';
            $serviceLabel = $parts ? implode(' + ', $parts) : 'Custom Request';
        }

        $travelDate = trim($_POST['travel_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $extraRequests = trim($_POST['extra_requests'] ?? '');

        if ($name === '' || $whatsapp === '' || $email === '' || $members === '' || $travelDate === '') {
            throw new RuntimeException('Please fill in your name, WhatsApp number, email, member count, and approximate travel date.');
        }

        $stmt = $pdo->prepare('INSERT INTO saint_martin_requests (name, whatsapp, email, members_count, service_needed, need_ship_ticket, need_hotel, approx_travel_date, notes, extra_requests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $name,
            $whatsapp,
            $email,
            $members,
            $serviceLabel,
            $needShip,
            $needHotel,
            $travelDate,
            $notes,
            $extraRequests
        ]);

        $insertedId = (int)$pdo->lastInsertId();
        $sent = true;
        $submissionData = [
            'id' => $insertedId,
            'name' => $name,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'members' => $members,
            'service' => $serviceLabel,
            'travel_date' => $travelDate,
            'notes' => $notes,
            'extra_requests' => $extraRequests,
        ];
    } catch (Throwable $e) {
        $formError = $e->getMessage() ?: 'We could not submit your request just now. Please try again in a moment.';
    }
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Free Booking For Saint Martin — Cox Booking</title>
    <meta name="description" content="Free pre-booking for Saint Martin Island. Reserve ship tickets, beachfront hotels and eco-cottages, or full packages. Tourism resumes Nov 1. 0 advance payment.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bengali-ui.css">
    <link rel="stylesheet" href="pages.css">
    <link rel="stylesheet" href="forms.css">
    <link rel="stylesheet" href="saint-martin.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>
<?php include 'partials.php'; ?>

<main>
    <!-- Hero Section -->
    <section class="sm-page-hero">
        <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2200&q=85" alt="Saint Martin Island clear blue ocean and coral beach">
        <div class="sm-hero-overlay"></div>
        <div class="sm-hero-content">
            <div class="sm-free-badge">
                <span class="pulse-dot"></span> 100% Free Pre-Booking · 0 Advance
            </div>
            <div class="eyebrow light"><span></span> Saint Martin Island · Season 2026–27</div>
            <h1>Free Booking For<br><em>Saint Martin Island.</em></h1>
            <p>The blue coral paradise awaits. Reserve your ship tickets, verified beach cottages, or full holiday package ahead of time with zero payment needed.</p>
            <div class="sm-hero-pills">
                <span class="sm-hero-pill">🚢 Ship Cruise Priority</span>
                <span class="sm-hero-pill">🌴 Beachfront Resorts</span>
                <span class="sm-hero-pill">৳0 Zero Advance to Book</span>
                <span class="sm-hero-pill">📍 Teknaf & Cox Concierge</span>
            </div>
        </div>
    </section>

    <div class="wrap">
        <!-- Season Notice -->
        <div class="sm-season-notice" role="alert">
            <span class="sm-notice-icon">📢</span>
            <div>
                <b>Important Season Notice: Saint Martin starts from November 1st</b>
                <p>Ship services (Keari Sindbad, Bay One, Karnafully Express, Baro Awlia) and tourism operations to Saint Martin officially commence from <strong>November 1st</strong>. When submitting your request below, please choose your approximate travel date on or after November 1st.</p>
            </div>
        </div>
    </div>

    <!-- Booking Form & Info Section -->
    <div class="form-layout wrap" style="padding-top: 10px; padding-bottom: 90px;">
        <aside class="form-aside">
            <div class="eyebrow"><span></span> Island Journey Planning</div>
            <h2 style="font: 400 44px/1.12 var(--serif); margin: 20px 0 16px; letter-spacing: -1px;">
                Lock in your spot<br><em>before seats fill.</em>
            </h2>
            <p>Every year, thousands head to Saint Martin in November and December. Ships and seaside rooms sell out weeks in advance.</p>
            <p>Our pre-booking service is completely free: we reserve your ship seats and room options so you don't face last-minute blackouts or inflated jetty prices.</p>

            <div class="response-card" style="margin-top: 32px;">
                <span class="response-icon">💬</span>
                <div>
                    <b>Direct WhatsApp Confirmation</b>
                    <small>Our Saint Martin team will reach out directly on WhatsApp with ship timetables, seat tiers, and resort photos.</small>
                </div>
            </div>

            <div class="response-card" style="margin-top: 14px; background: #eaf1ed;">
                <span class="response-icon" style="background: #29483d; color: #fff;">✓</span>
                <div>
                    <b>No Payment Needed Now</b>
                    <small>Zero obligation. You only confirm and pay when you are completely satisfied with the itinerary.</small>
                </div>
            </div>

            <div class="aside-footnote" style="margin-top: 45px;">
                Need urgent advice or customized corporate/group tours? You can also message our Cox & Saint Martin desk directly on WhatsApp.
            </div>
        </aside>

        <!-- Form Card -->
        <section class="form-card" id="booking-form-section">
            <div class="form-card-head">
                <span>SAINT MARTIN PRE-BOOKING</span>
                <span>FREE RESERVATION · 2026–2027</span>
            </div>

            <?php if ($sent): ?>
                <div class="success">
                    <span>✓</span>
                    <h2>You’re on the priority list!</h2>
                    <p>Thank you, <strong><?= e($submissionData['name']) ?></strong>. We have received your Saint Martin pre-booking request.</p>

                    <div class="sm-success-details">
                        <div class="sm-success-row">
                            <span>Booking Reference:</span>
                            <span>#SM-<?= str_pad((string)$submissionData['id'], 5, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="sm-success-row">
                            <span>WhatsApp:</span>
                            <span><?= e($submissionData['whatsapp']) ?></span>
                        </div>
                        <div class="sm-success-row">
                            <span>Email:</span>
                            <span><?= e($submissionData['email']) ?></span>
                        </div>
                        <div class="sm-success-row">
                            <span>Travelers:</span>
                            <span><?= e($submissionData['members']) ?></span>
                        </div>
                        <div class="sm-success-row">
                            <span>Service Needed:</span>
                            <span><?= e($submissionData['service']) ?></span>
                        </div>
                        <div class="sm-success-row">
                            <span>Approx. Travel Date:</span>
                            <span><?= e($submissionData['travel_date']) ?></span>
                        </div>
                        <?php if (!empty($submissionData['notes'])): ?>
                        <div class="sm-success-row">
                            <span>Preferences:</span>
                            <span><?= e($submissionData['notes']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($submissionData['extra_requests'])): ?>
                        <div class="sm-success-row">
                            <span>Extra Request:</span>
                            <span><?= e($submissionData['extra_requests']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <p style="font-size: 12px; color: #65705c; margin-top: 15px;">
                        Our Saint Martin travel concierge will send the ship cruise schedule and resort details to your WhatsApp within a few hours.
                    </p>

                    <?php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $submissionData['whatsapp']);
                    $waText = urlencode("Hello Cox Booking team! I just submitted a Saint Martin free booking (#SM-" . $submissionData['id'] . ") for " . $submissionData['members'] . " on " . $submissionData['travel_date'] . ". Looking forward to ship & stay details.");
                    ?>
                    <a class="sm-whatsapp-btn" href="https://wa.me/8801700000000?text=<?= $waText ?>" target="_blank" rel="noopener">
                        <span>💬 Message Concierge on WhatsApp</span>
                    </a>

                    <div style="margin-top: 24px;">
                        <a class="text-link" href="saint-martin.php">Submit another request <b>↗</b></a>
                        <span style="margin: 0 10px; color: #ccc;">|</span>
                        <a class="text-link" href="index.php">Back to home <b>↗</b></a>
                    </div>
                </div>
            <?php else: ?>
                <h2>Free Booking For Saint Martin</h2>
                <p style="font-size: 12px; color: #77796f; margin-top: -15px; margin-bottom: 24px;">
                    Fill out the quick form below. No payment required — our local concierge will coordinate your cruise tickets and resort rooms.
                </p>

                <?php if ($formError): ?>
                    <div class="admin-alert" role="alert"><?= e($formError) ?></div>
                <?php endif; ?>

                <form method="post" action="saint-martin.php#booking-form-section" novalidate>
                    <?= csrf_field() ?>

                    <!-- Personal Information -->
                    <div class="field-row">
                        <label>
                            Your full name <span style="color:#b96e53">*</span>
                            <input required name="name" id="sm-name" autocomplete="name" placeholder="e.g. Tanvir Ahmed" value="<?= e($_POST['name'] ?? '') ?>">
                        </label>
                        <label>
                            WhatsApp number <span style="color:#b96e53">*</span>
                            <input required type="tel" name="whatsapp" id="sm-whatsapp" autocomplete="tel" placeholder="+880 17XX XXXXXX" value="<?= e($_POST['whatsapp'] ?? '') ?>">
                            <small style="font-size: 9px; color: #8a8d83;">We will send ship seat availability and hotel catalog to WhatsApp</small>
                        </label>
                    </div>

                    <div class="field-row">
                        <label>
                            Email address <span style="color:#b96e53">*</span>
                            <input required type="email" name="email" id="sm-email" autocomplete="email" placeholder="tanvir@example.com" value="<?= e($_POST['email'] ?? '') ?>">
                        </label>
                        <label>
                            How many members? <span style="color:#b96e53">*</span>
                            <input required type="text" name="members" id="sm-members" placeholder="e.g. 4 persons (2 adults, 2 kids)" value="<?= e($_POST['members'] ?? '') ?>">
                            <div class="sm-member-chips">
                                <span class="sm-member-chip" onclick="setMembers('1 Person')">1 Person</span>
                                <span class="sm-member-chip" onclick="setMembers('2 Persons (Couple)')">2 (Couple)</span>
                                <span class="sm-member-chip" onclick="setMembers('4 Persons (Family)')">4 Persons</span>
                                <span class="sm-member-chip" onclick="setMembers('6–8 Persons (Group)')">6–8 Group</span>
                                <span class="sm-member-chip" onclick="setMembers('10+ Group Tour')">10+ Group</span>
                            </div>
                        </label>
                    </div>

                    <!-- Service Selection -->
                    <div style="margin-top: 10px;">
                        <label style="font-weight: 600; color: #263a32; font-size: 11px;">
                            What do you need for your trip? <span style="color:#b96e53">*</span>
                        </label>
                        <div class="sm-service-grid">
                            <label class="sm-service-card <?= (!isset($_POST['service_choice']) || ($_POST['service_choice'] ?? '') === 'whole_package') ? 'selected' : '' ?>" id="card-whole-package">
                                <input type="radio" name="service_choice" value="whole_package" <?= (!isset($_POST['service_choice']) || ($_POST['service_choice'] ?? '') === 'whole_package') ? 'checked' : '' ?> onchange="updateServiceSelection()">
                                <div class="sm-card-body">
                                    <span class="sm-card-tag">⭐ RECOMMENDED</span>
                                    <b>Whole Package</b>
                                    <small>Roundtrip ship tickets + Beachfront resort/cottage + Local jetty assistance.</small>
                                </div>
                            </label>

                            <label class="sm-service-card <?= (($_POST['service_choice'] ?? '') === 'ship_ticket') ? 'selected' : '' ?>" id="card-ship-ticket">
                                <input type="radio" name="service_choice" value="ship_ticket" <?= (($_POST['service_choice'] ?? '') === 'ship_ticket') ? 'checked' : '' ?> onchange="updateServiceSelection()">
                                <div class="sm-card-body">
                                    <span class="sm-card-tag">CRUISE PASSAGE</span>
                                    <b>Ship Ticket Only</b>
                                    <small>Confirmed seats on Keari Sindbad, Bay One, Karnafully Express, or Baro Awlia.</small>
                                </div>
                            </label>

                            <label class="sm-service-card <?= (($_POST['service_choice'] ?? '') === 'hotel') ? 'selected' : '' ?>" id="card-hotel">
                                <input type="radio" name="service_choice" value="hotel" <?= (($_POST['service_choice'] ?? '') === 'hotel') ? 'checked' : '' ?> onchange="updateServiceSelection()">
                                <div class="sm-card-body">
                                    <span class="sm-card-tag">ISLAND STAY</span>
                                    <b>Hotel / Resort Only</b>
                                    <small>Verified eco-resort, beachfront cottage, or AC deluxe room on Saint Martin.</small>
                                </div>
                            </label>
                        </div>

                        <!-- Checkboxes for granular customization -->
                        <div class="sm-checkbox-row">
                            <span style="font-size: 10px; color: #767d73; font-weight: 600; text-transform: uppercase;">Selected Components:</span>
                            <label class="sm-check-item">
                                <input type="checkbox" name="need_ship" id="need_ship" value="1" <?= (!isset($_POST['need_ship']) || !empty($_POST['need_ship']) || ($_POST['service_choice'] ?? 'whole_package') === 'whole_package' || ($_POST['service_choice'] ?? '') === 'ship_ticket') ? 'checked' : '' ?>>
                                <span>Ship Ticket Needed</span>
                            </label>
                            <label class="sm-check-item">
                                <input type="checkbox" name="need_hotel" id="need_hotel" value="1" <?= (!isset($_POST['need_hotel']) || !empty($_POST['need_hotel']) || ($_POST['service_choice'] ?? 'whole_package') === 'whole_package' || ($_POST['service_choice'] ?? '') === 'hotel') ? 'checked' : '' ?>>
                                <span>Hotel / Resort Needed</span>
                            </label>
                        </div>
                    </div>

                    <!-- Approximate Travel Date with Nov 1 Note -->
                    <div style="margin-top: 14px;">
                        <label>
                            Approximate travel date <span style="color:#b96e53">*</span>
                            <span style="font-size: 10px; color: #9c603a; font-weight: 500;">(Note: Saint Martin tourism starts from November 1st)</span>
                            <input required type="date" name="travel_date" id="sm-travel-date" min="2026-11-01" value="<?= e($_POST['travel_date'] ?? '2026-11-01') ?>">
                        </label>
                        <div class="sm-date-chips">
                            <span style="font-size: 10px; color: #888d82; align-self: center;">Quick pick:</span>
                            <button type="button" class="sm-date-chip" onclick="setDate('2026-11-01')">Nov 1 (Opening Day)</button>
                            <button type="button" class="sm-date-chip" onclick="setDate('2026-11-06')">Nov 6 (First Weekend)</button>
                            <button type="button" class="sm-date-chip" onclick="setDate('2026-11-15')">Mid November</button>
                            <button type="button" class="sm-date-chip" onclick="setDate('2026-11-27')">Late November</button>
                            <button type="button" class="sm-date-chip" onclick="setDate('2026-12-18')">Mid December</button>
                            <button type="button" class="sm-date-chip" onclick="setDate('2026-12-25')">Dec Holidays</button>
                        </div>
                    </div>

                    <!-- Trip Preferences -->
                    <label class="full-field" style="margin-top: 12px;">
                        Preferred ship class or room style <span class="optional">OPTIONAL</span>
                        <input type="text" name="notes" placeholder="e.g. Open Deck seating, AC cabin, beachfront cottage, couple bed, eco-cottage…" value="<?= e($_POST['notes'] ?? '') ?>">
                    </label>

                    <!-- Extra Requests / Notes (Write Anything You Want) -->
                    <label class="full-field" style="margin-top: 14px;">
                        Special requests & notes <span class="optional" style="color: #b96e53; font-weight: 600;">OPTIONAL · WRITE ANYTHING YOU WANT</span>
                        <textarea name="extra_requests" rows="3" placeholder="Write anything you want — have an extra request, special arrangement, wheelchair/elderly assistance, anniversary surprise, child food, flexible dates, or questions for our local team?"><?= e($_POST['extra_requests'] ?? '') ?></textarea>
                    </label>

                    <div class="form-submit" style="margin-top: 8px;">
                        <button class="button submit-button" type="submit" style="padding: 0 26px; height: 48px; font-size: 13px; font-weight: 600;">
                            Submit Free Booking <span>↗</span>
                        </button>
                        <small>
                            100% Free · No credit card or advance required.<br>
                            Personalized response within 6 hours.
                        </small>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <!-- Island Highlights & Trust Section -->
    <section class="sm-features-strip">
        <div class="wrap">
            <div class="eyebrow"><span></span> Why Book Saint Martin With Us</div>
            <div class="section-head" style="align-items: flex-end;">
                <h2 style="font: 400 38px/1.15 var(--serif); margin-top: 14px;">
                    Handcrafted journeys to<br>Bangladesh’s <em>coral jewel.</em>
                </h2>
                <p>We are based on the coast. That means real local relationships with ship operators and resort owners.</p>
            </div>

            <div class="sm-feature-grid">
                <article class="sm-feature-box">
                    <span class="sm-feature-num">01</span>
                    <h3>Zero Advance Payment</h3>
                    <p>Pre-booking is 100% free. You only review and pay once your preferred ship seats and rooms are locked in.</p>
                </article>

                <article class="sm-feature-box">
                    <span class="sm-feature-num">02</span>
                    <h3>Guaranteed Cruise Tickets</h3>
                    <p>Avoid sold-out dates. We coordinate directly with Keari, Bay One, and express vessels before public counters close.</p>
                </article>

                <article class="sm-feature-box">
                    <span class="sm-feature-num">03</span>
                    <h3>Verified Island Resorts</h3>
                    <p>Only hand-inspected beach cottages and resorts with reliable solar/generator electricity, fresh water, and beach views.</p>
                </article>

                <article class="sm-feature-box">
                    <span class="sm-feature-num">04</span>
                    <h3>Teknaf & Cox Jetty Care</h3>
                    <p>Our ground team assists you during boarding, jetty transfers, luggage management, and local island advice.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Bottom CTA -->
    <section class="page-bottom-cta">
        <h2>Ready for the crystal waters of<br><em>Saint Martin Island?</em></h2>
        <a class="button button-cream" href="#booking-form-section">
            Reserve Your Free Booking Now <span>↗</span>
        </a>
    </section>
</main>

<?php include 'footer.php'; ?>

<script>
function setMembers(val) {
    const input = document.getElementById('sm-members');
    if (input) {
        input.value = val;
        input.focus();
    }
}

function setDate(dateStr) {
    const input = document.getElementById('sm-travel-date');
    if (input) {
        input.value = dateStr;
        input.focus();
    }
}

function updateServiceSelection() {
    const wholePackage = document.querySelector('input[value="whole_package"]')?.checked;
    const shipTicket = document.querySelector('input[value="ship_ticket"]')?.checked;
    const hotel = document.querySelector('input[value="hotel"]')?.checked;
    
    const cardWhole = document.getElementById('card-whole-package');
    const cardShip = document.getElementById('card-ship-ticket');
    const cardHotel = document.getElementById('card-hotel');

    if (cardWhole) cardWhole.classList.toggle('selected', !!wholePackage);
    if (cardShip) cardShip.classList.toggle('selected', !!shipTicket);
    if (cardHotel) cardHotel.classList.toggle('selected', !!hotel);

    const shipBox = document.getElementById('need_ship');
    const hotelBox = document.getElementById('need_hotel');

    if (wholePackage) {
        if (shipBox) shipBox.checked = true;
        if (hotelBox) hotelBox.checked = true;
    } else if (shipTicket) {
        if (shipBox) shipBox.checked = true;
        if (hotelBox) hotelBox.checked = false;
    } else if (hotel) {
        if (shipBox) shipBox.checked = false;
        if (hotelBox) hotelBox.checked = true;
    }
}

// Ensure cards update style when clicked
document.querySelectorAll('.sm-service-card').forEach(card => {
    card.addEventListener('click', () => {
        setTimeout(updateServiceSelection, 10);
    });
});
</script>
</body>
</html>

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
        
        if ($serviceChoice === 'ship_ticket') {
            $needShip = 1;
            $needHotel = 0;
            $serviceLabel = 'শুধু জাহাজের টিকিট';
        } elseif ($serviceChoice === 'hotel') {
            $needShip = 0;
            $needHotel = 1;
            $serviceLabel = 'শুধু হোটেল / রিসোর্ট';
        } else {
            $needShip = 1;
            $needHotel = 1;
            $serviceLabel = 'সম্পূর্ণ প্যাকেজ (জাহাজ + রিসোর্ট + লোকাল সাপোর্ট)';
        }

        $travelDate = trim($_POST['travel_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $extraRequests = trim($_POST['extra_requests'] ?? '');

        if ($name === '' || $whatsapp === '' || $members === '' || $travelDate === '') {
            throw new RuntimeException('অনুগ্রহ করে নাম, হোয়াটসঅ্যাপ নম্বর, সদস্য সংখ্যা এবং আনুমানিক যাত্রার তারিখ পূরণ করুন।');
        }

        $stmt = $pdo->prepare('INSERT INTO saint_martin_requests (name, whatsapp, email, members_count, service_needed, need_ship_ticket, need_hotel, approx_travel_date, notes, extra_requests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $name,
            $whatsapp,
            $email !== '' ? $email : null,
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
        $formError = $e->getMessage() ?: 'দুঃখিত, এই মুহূর্তে রিকোয়েস্টটি পাঠানো সম্ভব হয়নি। অনুগ্রহ করে একটু পরে আবার চেষ্টা করুন।';
    }
}
?><!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>সেন্ট মার্টিন ফ্রি বুকিং — Cox Booking</title>
    <meta name="description" content="সেন্ট মার্টিন দ্বীপের ফ্রি প্রি-বুকিং। কোনো অগ্রিম টাকা ছাড়াই জাহাজের টিকিট, বিচ রিসোর্ট বা ফুল প্যাকেজ বুক করুন। পর্যটন শুরু ১ নভেম্বর থেকে।">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bengali-ui.css">
    <link rel="stylesheet" href="saint-martin.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>
<?php include 'partials.php'; ?>

<main class="sm-form-page">
    <div class="sm-form-container">
        <!-- Form Header Area -->
        <header class="sm-form-header">
            <div class="sm-badge-tag">
                <span class="pulse-dot"></span> ১০০% ফ্রি বুকিং · কোনো অগ্রিম পেমেন্ট নেই
            </div>
            <h1 class="sm-page-title">সেন্ট মার্টিন ফ্রি বুকিং</h1>
            <p class="sm-page-subtitle">
                ১ নভেম্বর থেকে সেন্ট মার্টিনের জাহাজ চলাচল ও পর্যটন শুরু হচ্ছে। আগে থেকেই কোনো অগ্রিম টাকা ছাড়াই আপনার জাহাজের সিট ও সেরা বিচ রিসোর্ট নিশ্চিত করুন।
            </p>
        </header>

        <!-- Season Notice Alert -->
        <div class="sm-season-notice" role="alert">
            <span class="sm-notice-icon">📢</span>
            <div>
                <b>জরুরি নোটিশ: সেন্ট মার্টিন জাহাজ চলাচল শুরু ১ নভেম্বর থেকে</b>
                <p>সরকারি নির্দেশনা অনুযায়ী আগামী <strong>১ নভেম্বর</strong> থেকে সেন্ট মার্টিনের সকল ক্রুজ জাহাজ (কেয়ারী, বে ওয়ান, কর্ণফুলী ইত্যাদি) চলাচল শুরু করবে। অনুগ্রহ করে আপনার ভ্রমণের আনুমানিক তারিখ ১ নভেম্বর বা তার পরবর্তী যেকোনো দিন নির্বাচন করুন।</p>
            </div>
        </div>

        <!-- Form Card Container -->
        <div class="sm-main-card">
            <?php if ($sent): ?>
                <!-- Success Confirmation State in Bengali -->
                <div class="sm-success-card">
                    <div class="sm-success-icon">✓</div>
                    <h2>আপনার বুকিং রিকোয়েস্ট সফলভাবে জমা হয়েছে!</h2>
                    <p>ধন্যবাদ, <strong><?= e($submissionData['name']) ?></strong>। সেন্ট মার্টিন ভ্রমণের জন্য আপনার ফ্রি বুকিং রিকোয়েস্টটি আমরা পেয়েছি।</p>

                    <div class="sm-summary-box">
                        <div class="sm-summary-row">
                            <span>বুকিং রেফারেন্স নম্বর:</span>
                            <span>#SM-<?= str_pad((string)$submissionData['id'], 5, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="sm-summary-row">
                            <span>হোয়াটসঅ্যাপ নম্বর:</span>
                            <span><?= e($submissionData['whatsapp']) ?></span>
                        </div>
                        <?php if (!empty($submissionData['email'])): ?>
                        <div class="sm-summary-row">
                            <span>ইমেইল অ্যাড্রেস:</span>
                            <span><?= e($submissionData['email']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="sm-summary-row">
                            <span>ভ্রমণকারী সদস্য:</span>
                            <span><?= e($submissionData['members']) ?></span>
                        </div>
                        <div class="sm-summary-row">
                            <span>প্রয়োজনীয় সেবা:</span>
                            <span><?= e($submissionData['service']) ?></span>
                        </div>
                        <div class="sm-summary-row">
                            <span>আনুমানিক যাত্রার তারিখ:</span>
                            <span><?= e($submissionData['travel_date']) ?></span>
                        </div>
                        <?php if (!empty($submissionData['notes'])): ?>
                        <div class="sm-summary-row">
                            <span>পছন্দ / প্রেফারেন্স:</span>
                            <span><?= e($submissionData['notes']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($submissionData['extra_requests'])): ?>
                        <div class="sm-summary-row">
                            <span>বিশেষ অনুরোধ:</span>
                            <span><?= e($submissionData['extra_requests']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <p style="font-size: 13px; color: #315448; margin-top: 10px;">
                        আমাদের লোকাল ট্রাভেল টিম দ্রুত আপনার হোয়াটসঅ্যাপে জাহাজের টিকিট ও সেরা বিচ রিসোর্টের বিস্তারিত তথ্য পাঠাবে।
                    </p>

                    <?php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $submissionData['whatsapp']);
                    $waText = urlencode("হ্যালো কক্স বুকিং টিম! আমি সেন্ট মার্টিন ফ্রি বুকিং (#SM-" . $submissionData['id'] . ") জমা দিয়েছি। সদস্য: " . $submissionData['members'] . ", তারিখ: " . $submissionData['travel_date'] . "। জাহাজের টিকিট ও রিসোর্ট ডিটেইলস জানতে চাচ্ছি।");
                    ?>
                    <a class="sm-wa-cta-btn" href="https://wa.me/8801700000000?text=<?= $waText ?>" target="_blank" rel="noopener">
                        <span>💬 হোয়াটসঅ্যাপে সরাসরি কথা বলুন</span>
                    </a>

                    <div style="margin-top: 25px; font-size: 13px;">
                        <a href="saint-martin.php" style="color: #315448; text-decoration: underline; font-weight: 600;">আরেকটি রিকোয়েস্ট পাঠান ↗</a>
                        <span style="margin: 0 10px; color: #ccc;">|</span>
                        <a href="index.php" style="color: #646f68; text-decoration: underline;">হোমপেজে ফিরে যান ↗</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- The Bengali Booking Form -->
                <?php if ($formError): ?>
                    <div class="admin-alert" role="alert" style="margin-bottom: 20px; font-family: 'Hind Siliguri', sans-serif;">
                        <?= e($formError) ?>
                    </div>
                <?php endif; ?>

                <form class="sm-form" method="post" action="saint-martin.php" novalidate>
                    <?= csrf_field() ?>

                    <!-- Row 1: Name and WhatsApp (2 Columns on Desktop, 1 on Mobile) -->
                    <div class="sm-row-2">
                        <div class="sm-field">
                            <label class="sm-label" for="sm-name">
                                <span>আপনার পুরো নাম <span class="sm-label-req">*</span></span>
                            </label>
                            <input required type="text" name="name" id="sm-name" class="sm-input" autocomplete="name" placeholder="যেমন: তানভীর আহমেদ" value="<?= e($_POST['name'] ?? '') ?>">
                        </div>

                        <div class="sm-field">
                            <label class="sm-label" for="sm-whatsapp">
                                <span>হোয়াটসঅ্যাপ নম্বর <span class="sm-label-req">*</span></span>
                            </label>
                            <input required type="tel" name="whatsapp" id="sm-whatsapp" class="sm-input" autocomplete="tel" placeholder="017XXXXXXXX বা +880 17XX XXXXXX" value="<?= e($_POST['whatsapp'] ?? '') ?>">
                            <span class="sm-field-hint">এই নম্বরে সরাসরি জাহাজের সিট অপশন ও রিসোর্টের ছবি পাঠানো হবে</span>
                        </div>
                    </div>

                    <!-- Row 2: Email and Members (2 Columns on Desktop, 1 on Mobile) -->
                    <div class="sm-row-2">
                        <div class="sm-field">
                            <label class="sm-label" for="sm-email">
                                <span>ইমেইল অ্যাড্রেস</span>
                                <span class="sm-label-opt">(ঐচ্ছিক)</span>
                            </label>
                            <input type="email" name="email" id="sm-email" class="sm-input" autocomplete="email" placeholder="ইমেইল না থাকলে খালি রাখুন (ঐচ্ছিক)" value="<?= e($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="sm-field">
                            <label class="sm-label" for="sm-members">
                                <span>কতজন সদস্য / ভ্রমণকারী? <span class="sm-label-req">*</span></span>
                            </label>
                            <input required type="text" name="members" id="sm-members" class="sm-input" placeholder="যেমন: ৪ জন (২ জন বড়, ২ জন শিশু)" value="<?= e($_POST['members'] ?? '') ?>">
                            <div class="sm-chip-group">
                                <span class="sm-chip" onclick="setMembers('১ জন')">১ জন</span>
                                <span class="sm-chip" onclick="setMembers('২ জন (কাপল)')">২ জন (কাপল)</span>
                                <span class="sm-chip" onclick="setMembers('৪ জন (ফ্যামিলি)')">৪ জন (ফ্যামিলি)</span>
                                <span class="sm-chip" onclick="setMembers('৬–৮ জন (গ্রুপ)')">৬–৮ জন</span>
                                <span class="sm-chip" onclick="setMembers('১০+ জন (ট্যুর গ্রুপ)')">১০+ জন</span>
                            </div>
                        </div>
                    </div>

                    <!-- Service Selection Section (3 Columns on Desktop, 1 on Mobile) -->
                    <div class="sm-field sm-field-full" style="margin-top: 4px;">
                        <label class="sm-label">
                            <span>ভ্রমণের জন্য আপনার কী কী প্রয়োজন? <span class="sm-label-req">*</span></span>
                        </label>

                        <div class="sm-service-select-grid">
                            <!-- Card 1: Whole Package -->
                            <label class="sm-service-card is-selected" id="card-whole">
                                <input type="radio" class="sm-native-hidden" name="service_choice" value="whole_package" <?= (!isset($_POST['service_choice']) || ($_POST['service_choice'] ?? '') === 'whole_package') ? 'checked' : '' ?> onchange="onServiceCardChange()">
                                <div>
                                    <div class="sm-card-top">
                                        <span class="sm-card-badge">⭐ সবচেয়ে জনপ্রিয়</span>
                                        <span class="sm-custom-radio"></span>
                                    </div>
                                    <strong class="sm-card-title">সম্পূর্ণ প্যাকেজ (হোল প্যাকেজ)</strong>
                                    <span class="sm-card-desc">জাহাজের রাউন্ড-ট্রিপ টিকিট + বিচ কটেজ/রিসোর্ট + টেকনাফ ঘাট ও লোকাল সহায়তা</span>
                                </div>
                            </label>

                            <!-- Card 2: Ship Ticket Only -->
                            <label class="sm-service-card" id="card-ship">
                                <input type="radio" class="sm-native-hidden" name="service_choice" value="ship_ticket" <?= (($_POST['service_choice'] ?? '') === 'ship_ticket') ? 'checked' : '' ?> onchange="onServiceCardChange()">
                                <div>
                                    <div class="sm-card-top">
                                        <span class="sm-card-badge" style="color: #215c82;">🚢 ক্রুজ টিকিট</span>
                                        <span class="sm-custom-radio"></span>
                                    </div>
                                    <strong class="sm-card-title">শুধু জাহাজের টিকিট</strong>
                                    <span class="sm-card-desc">কেয়ারী সিন্দাবাদ, বে ওয়ান, কর্ণফুলী বা বারো আউলিয়া জাহাজের নিশ্চিত টিকিট</span>
                                </div>
                            </label>

                            <!-- Card 3: Hotel Only -->
                            <label class="sm-service-card" id="card-hotel">
                                <input type="radio" class="sm-native-hidden" name="service_choice" value="hotel" <?= (($_POST['service_choice'] ?? '') === 'hotel') ? 'checked' : '' ?> onchange="onServiceCardChange()">
                                <div>
                                    <div class="sm-card-top">
                                        <span class="sm-card-badge" style="color: #2a5d35;">🏨 আইল্যান্ড স্টে</span>
                                        <span class="sm-custom-radio"></span>
                                    </div>
                                    <strong class="sm-card-title">শুধু হোটেল / রিসোর্ট</strong>
                                    <span class="sm-card-desc">সেন্ট মার্টিনের সি-ভিউ ইকো রিসোর্ট বা বিচ কটেজে নিশ্চিত রুম</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Row 3: Approximate Travel Date -->
                    <div class="sm-field sm-field-full">
                        <label class="sm-label" for="sm-travel-date">
                            <span>আনুমানিক যাত্রার তারিখ <span class="sm-label-req">*</span></span>
                            <span class="sm-label-opt" style="color: #bc694e;">(সেন্ট মার্টিন ভ্রমণ শুরু ১ নভেম্বর থেকে)</span>
                        </label>
                        <input required type="date" name="travel_date" id="sm-travel-date" class="sm-input" min="2026-11-01" value="<?= e($_POST['travel_date'] ?? '2026-11-01') ?>">
                        <div class="sm-chip-group">
                            <span class="sm-chip" onclick="setDate('2026-11-01')">১ নভেম্বর (উদ্বোধনী দিন)</span>
                            <span class="sm-chip" onclick="setDate('2026-11-06')">৬ নভেম্বর (১ম উইকেন্ড)</span>
                            <span class="sm-chip" onclick="setDate('2026-11-15')">নভেম্বরের মাঝামাঝি</span>
                            <span class="sm-chip" onclick="setDate('2026-11-27')">নভেম্বরের শেষ সপ্তাহ</span>
                            <span class="sm-chip" onclick="setDate('2026-12-18')">ডিসেম্বরের ছুটি</span>
                        </div>
                    </div>

                    <!-- Row 4: Trip Preferences (Optional) -->
                    <div class="sm-field sm-field-full">
                        <label class="sm-label" for="sm-notes">
                            <span>জাহাজের সিট বা রুমের কোনো বিশেষ পছন্দ?</span>
                            <span class="sm-label-opt">ঐচ্ছিক</span>
                        </label>
                        <input type="text" name="notes" id="sm-notes" class="sm-input" placeholder="যেমন: জাহাজের ওপেন ডেক, এসি কেবিন, বিচের পাশের কটেজ, কাপল বেড…" value="<?= e($_POST['notes'] ?? '') ?>">
                    </div>

                    <!-- Row 5: Extra Requests (Optional · Write Anything) -->
                    <div class="sm-field sm-field-full">
                        <label class="sm-label" for="sm-extra">
                            <span>অতিরিক্ত কোনো চাওয়া বা বিশেষ অনুরোধ?</span>
                            <span class="sm-label-opt" style="color: #bc694e; font-weight: 700;">ঐচ্ছিক · যা খুশি লিখতে পারেন</span>
                        </label>
                        <textarea name="extra_requests" id="sm-extra" class="sm-textarea" rows="3" placeholder="এখানে আপনি যেকোনো কিছু লিখতে পারেন — যেমন: বিশেষ কোনো আয়োজন, বয়স্ক সদস্যদের সুবিধা, হানিমুন সারপ্রাইজ, ছেঁড়া দ্বীপ ট্রিপ, বাজেট সংক্রান্ত প্রশ্ন বা অন্য কোনো তথ্য..."><?= e($_POST['extra_requests'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit Button Row -->
                    <div class="sm-submit-row">
                        <button class="sm-submit-btn" type="submit">
                            <span>ফ্রি বুকিং রিকোয়েস্ট পাঠান</span>
                            <span aria-hidden="true">↗</span>
                        </button>
                        <div class="sm-submit-hint">
                            ১০০% ফ্রি · কোনো ক্রেডিট কার্ড বা অগ্রিম টাকা লাগবে না<br>
                            ৬ ঘণ্টার মধ্যে হোয়াটসঅ্যাপে রিপ্লাই পাবেন
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
function setMembers(val) {
    const el = document.getElementById('sm-members');
    if (el) {
        el.value = val;
        el.focus();
    }
}

function setDate(d) {
    const el = document.getElementById('sm-travel-date');
    if (el) {
        el.value = d;
        el.focus();
    }
}

function onServiceCardChange() {
    const whole = document.querySelector('input[name="service_choice"][value="whole_package"]')?.checked;
    const ship = document.querySelector('input[name="service_choice"][value="ship_ticket"]')?.checked;
    const hotel = document.querySelector('input[name="service_choice"][value="hotel"]')?.checked;

    document.getElementById('card-whole')?.classList.toggle('is-selected', !!whole);
    document.getElementById('card-ship')?.classList.toggle('is-selected', !!ship);
    document.getElementById('card-hotel')?.classList.toggle('is-selected', !!hotel);
}

// Card Click Event Listeners
document.querySelectorAll('.sm-service-card').forEach(card => {
    card.addEventListener('click', function(e) {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            onServiceCardChange();
        }
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    onServiceCardChange();
});
</script>
</body>
</html>

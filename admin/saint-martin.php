<?php
require_once __DIR__ . '/../db.php';
admin_required();

$pdo = db();
ensure_saint_martin_table($pdo);

$message = '';
$messageType = 'ok';
$allowedStatuses = ['new', 'contacted', 'confirmed', 'cancelled'];

// Handle Status & Note Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'update';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'update' && $id > 0) {
        $status = (string)($_POST['status'] ?? 'new');
        $adminNotes = trim((string)($_POST['admin_notes'] ?? ''));

        if (in_array($status, $allowedStatuses, true)) {
            $stmt = $pdo->prepare('UPDATE saint_martin_requests SET status = ?, admin_notes = ? WHERE id = ?');
            $stmt->execute([$status, $adminNotes, $id]);
            $message = 'Saint Martin request #' . $id . ' updated successfully.';

            if (!empty($_POST['is_ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'id' => $id,
                    'status' => $status,
                    'admin_notes' => $adminNotes
                ]);
                exit;
            }
        }
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM saint_martin_requests WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Request #' . $id . ' deleted successfully.';
    }
}

// Filters & Search
$statusFilter = trim((string)($_GET['status'] ?? ''));
$searchQuery = trim((string)($_GET['q'] ?? ''));

$sql = 'SELECT * FROM saint_martin_requests WHERE 1=1';
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
}

if ($searchQuery !== '') {
    $sql .= ' AND (name LIKE ? OR whatsapp LIKE ? OR email LIKE ? OR notes LIKE ? OR extra_requests LIKE ? OR admin_notes LIKE ?)';
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for Stat Badges
$totalCount = (int)$pdo->query('SELECT COUNT(*) FROM saint_martin_requests')->fetchColumn();
$newCount = (int)$pdo->query("SELECT COUNT(*) FROM saint_martin_requests WHERE status='new'")->fetchColumn();
$contactedCount = (int)$pdo->query("SELECT COUNT(*) FROM saint_martin_requests WHERE status='contacted'")->fetchColumn();
$confirmedCount = (int)$pdo->query("SELECT COUNT(*) FROM saint_martin_requests WHERE status='confirmed'")->fetchColumn();
$packageCount = (int)$pdo->query("SELECT COUNT(*) FROM saint_martin_requests WHERE need_ship_ticket = 1 AND need_hotel = 1")->fetchColumn();

admin_header('Saint Martin Pre-Bookings');
?>

<?php if ($message): ?>
    <div class="admin-alert <?= $messageType === 'ok' ? 'ok' : '' ?>"><?= e($message) ?></div>
<?php endif; ?>

<p class="dashboard-intro">
    Manage incoming <strong>Free Booking requests for Saint Martin Island</strong>. Official tourism resumes November 1st. Update status and notes directly in the table below.
</p>

<!-- Stats Grid -->
<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom: 24px;">
    <a class="stat-box" href="saint-martin.php">
        <b><?= $totalCount ?></b>
        <span>Total SM Requests</span>
    </a>
    <a class="stat-box" href="saint-martin.php?status=new" style="border-top-color: #a0562e;">
        <b style="color: #a0562e;"><?= $newCount ?></b>
        <span>New / Uncontacted</span>
    </a>
    <a class="stat-box" href="saint-martin.php?status=contacted" style="border-top-color: #276088;">
        <b style="color: #276088;"><?= $contactedCount ?></b>
        <span>Contacted</span>
    </a>
    <a class="stat-box" href="saint-martin.php?status=confirmed" style="border-top-color: #1d6a3f;">
        <b style="color: #1d6a3f;"><?= $confirmedCount ?></b>
        <span>Confirmed Bookings</span>
    </a>
    <div class="stat-box" style="border-top-color: #65705c;">
        <b><?= $packageCount ?></b>
        <span>Full Packages (Ship + Hotel)</span>
    </div>
</div>

<!-- Filter Tabs & Search Bar -->
<div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
    <div class="admin-filter-tabs" style="margin-bottom: 0;">
        <a class="admin-filter-tab <?= $statusFilter === '' ? 'active' : '' ?>" href="saint-martin.php<?= $searchQuery ? '?q=' . urlencode($searchQuery) : '' ?>">
            All <span class="badge"><?= $totalCount ?></span>
        </a>
        <a class="admin-filter-tab <?= $statusFilter === 'new' ? 'active' : '' ?>" href="saint-martin.php?status=new<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>">
            New <span class="badge"><?= $newCount ?></span>
        </a>
        <a class="admin-filter-tab <?= $statusFilter === 'contacted' ? 'active' : '' ?>" href="saint-martin.php?status=contacted<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>">
            Contacted <span class="badge"><?= $contactedCount ?></span>
        </a>
        <a class="admin-filter-tab <?= $statusFilter === 'confirmed' ? 'active' : '' ?>" href="saint-martin.php?status=confirmed<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>">
            Confirmed <span class="badge"><?= $confirmedCount ?></span>
        </a>
        <a class="admin-filter-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>" href="saint-martin.php?status=cancelled<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>">
            Cancelled
        </a>
    </div>

    <!-- Search Form -->
    <form method="get" action="saint-martin.php" style="display: flex; gap: 6px; margin: 0;">
        <?php if ($statusFilter): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Search name, phone, email, notes…" style="padding: 7px 12px; font-size: 12px; border: 1px solid var(--line); border-radius: 2px; width: 230px;">
        <button type="submit" class="admin-button" style="min-height: 34px; padding: 0 12px; font-size: 12px;">Search</button>
        <?php if ($searchQuery || $statusFilter): ?>
            <a href="saint-martin.php" class="admin-button" style="min-height: 34px; padding: 0 10px; font-size: 12px; background: #888d84;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Requests Table -->
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 70px;">ID & Date</th>
                <th>Guest & Contact</th>
                <th style="width: 100px;">Members</th>
                <th>Service Needed</th>
                <th>Travel Date</th>
                <th style="min-width: 180px;">Guest Requests & Notes</th>
                <!-- Changeable Status Column -->
                <th style="min-width: 140px;">Status <span style="font-size: 9px; color: #b96e53;">(Changeable)</span></th>
                <!-- Changeable Note Column -->
                <th style="min-width: 250px;">Admin Note <span style="font-size: 9px; color: #b96e53;">(Changeable)</span></th>
                <th style="width: 90px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): 
                $cleanPhone = preg_replace('/[^0-9]/', '', $r['whatsapp']);
                $waText = urlencode("Hello " . $r['name'] . "! This is the Cox Booking & Saint Martin team. We received your free pre-booking request for Saint Martin on " . $r['approx_travel_date'] . " (" . $r['members_count'] . "). We are ready to assist with ship tickets and hotel rooms.");
                $serviceLower = strtolower($r['service_needed']);
                $badgeClass = 'service-custom';
                if (str_contains($serviceLower, 'whole') || str_contains($serviceLower, 'package')) {
                    $badgeClass = 'service-package';
                } elseif (str_contains($serviceLower, 'ship')) {
                    $badgeClass = 'service-ship';
                } elseif (str_contains($serviceLower, 'hotel') || str_contains($serviceLower, 'resort')) {
                    $badgeClass = 'service-hotel';
                }
            ?>
            <tr class="sm-request-row" id="row-<?= $r['id'] ?>" data-request='<?= e(json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'>
                <td>
                    <b style="font-family: var(--serif); color: #29483d;">#SM-<?= $r['id'] ?></b>
                    <br>
                    <small style="color: #7b8379; font-size: 10px;"><?= date('M d, Y', strtotime($r['created_at'])) ?></small>
                </td>
                <td>
                    <strong style="font-size: 13px; color: #233b32;"><?= e($r['name']) ?></strong>
                    <br>
                    <a href="mailto:<?= e($r['email']) ?>" style="color: #55645c; font-size: 11px; text-decoration: underline;"><?= e($r['email']) ?></a>
                    <br>
                    <a class="admin-wa-pill" href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waText ?>" target="_blank" rel="noopener" title="Message on WhatsApp">
                        <span>💬 <?= e($r['whatsapp']) ?></span>
                    </a>
                </td>
                <td>
                    <span style="font-weight: 600; color: #35473e;"><?= e($r['members_count']) ?></span>
                </td>
                <td>
                    <span class="service-badge <?= $badgeClass ?>"><?= e($r['service_needed']) ?></span>
                    <?php if ($r['need_ship_ticket'] && $r['need_hotel']): ?>
                        <div style="font-size: 9px; color: #7a8277; margin-top: 3px;">🚢 Ship + 🏨 Hotel</div>
                    <?php elseif ($r['need_ship_ticket']): ?>
                        <div style="font-size: 9px; color: #1b5278; margin-top: 3px;">🚢 Ship Ticket</div>
                    <?php elseif ($r['need_hotel']): ?>
                        <div style="font-size: 9px; color: #2a5d35; margin-top: 3px;">🏨 Hotel / Cottage</div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="color: #a0562e; font-size: 12px;"><?= e($r['approx_travel_date']) ?></strong>
                    <div style="font-size: 9px; color: #888d82;">From Nov 1 season</div>
                </td>
                <td>
                    <?php if (!empty($r['extra_requests'])): ?>
                        <div style="font-size: 11px; font-weight: 600; color: #233b32; margin-bottom: 4px; max-width: 210px;" title="<?= e($r['extra_requests']) ?>">
                            <span style="font-size: 8px; background: #ebdcc9; color: #7a461e; padding: 1px 5px; border-radius: 2px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Request</span>
                            <?= e($r['extra_requests']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($r['notes'])): ?>
                        <div style="font-size: 11px; color: #667065; max-width: 210px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;" title="<?= e($r['notes']) ?>">
                            <span style="font-size: 9px; color: #889185;">Pref:</span> <?= e($r['notes']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($r['extra_requests']) && empty($r['notes'])): ?>
                        <span style="color: #999d94; font-size: 10px;">—</span>
                    <?php endif; ?>
                </td>

                <!-- CHANGEABLE STATUS COLUMN -->
                <td>
                    <form class="inline-form row-update-form" method="post" action="saint-martin.php" data-id="<?= $r['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="admin_notes" class="synced-admin-note" value="<?= e($r['admin_notes'] ?? '') ?>">
                        <select name="status" class="inline-status-select status-dropdown status-<?= e($r['status']) ?>" onchange="quickUpdateStatus(this)">
                            <?php foreach ($allowedStatuses as $st): ?>
                                <option value="<?= $st ?>" <?= $r['status'] === $st ? 'selected' : '' ?>>
                                    <?= ucfirst($st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>

                <!-- CHANGEABLE NOTE COLUMN -->
                <td>
                    <form class="inline-form row-note-form" method="post" action="saint-martin.php" data-id="<?= $r['id'] ?>" onsubmit="submitInlineForm(event, this)">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="status" class="synced-status" value="<?= e($r['status']) ?>">
                        <input type="text" name="admin_notes" class="inline-note-input" value="<?= e($r['admin_notes'] ?? '') ?>" placeholder="Add note (e.g. called guest, wants Keari cruise)…" oninput="syncAdminNote(this)">
                        <button type="submit" class="inline-save-btn" title="Save status & note">Save</button>
                    </form>
                </td>

                <!-- ROW ACTIONS -->
                <td style="text-align: right;">
                    <button type="button" class="admin-button" style="min-height: 32px; padding: 0 9px; font-size: 11px;" onclick="openDetails(<?= $r['id'] ?>)">
                        View
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (!$rows): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px 20px; color: #767d74;">
                        No Saint Martin booking requests found matching your filter.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Detail Modal -->
<div class="modal" id="sm-detail-modal" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="sm-modal-title">
        <button class="modal-close" type="button" aria-label="Close" onclick="closeDetails()">×</button>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <p class="admin-kicker">SAINT MARTIN BOOKING</p>
                <h2 id="sm-modal-title" style="margin: 0;">Request Details</h2>
            </div>
            <span id="modal-status-pill" class="admin-status">New</span>
        </div>

        <dl class="detail-grid" id="sm-detail-grid"></dl>

        <!-- Changeable Status & Note inside Modal -->
        <form class="admin-form" method="post" action="saint-martin.php" style="margin-top: 24px; padding: 20px; background: #fbf9f2; border: 1px solid var(--line);">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="modal-id">
            
            <h3 style="margin: 0 0 14px; font-size: 14px; color: #233b32;">Update Status & Admin Note</h3>
            
            <div class="admin-form-grid" style="grid-template-columns: 180px 1fr; gap: 14px;">
                <label>
                    Status
                    <select name="status" id="modal-status" class="inline-status-select" style="width: 100% !important; min-height: 44px;">
                        <?php foreach ($allowedStatuses as $st): ?>
                            <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Admin Internal Note
                    <textarea name="admin_notes" id="modal-admin-notes" rows="2" placeholder="Write internal updates (e.g. Sent room options, waiting for passenger NID/details, confirmed booking)…" style="min-height: 70px;"></textarea>
                </label>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                <button class="admin-button" type="submit">Save Status & Note</button>
                <a id="modal-wa-link" href="#" target="_blank" rel="noopener" class="sm-whatsapp-btn" style="padding: 9px 16px; margin: 0; font-size: 11px;">
                    Chat on WhatsApp
                </a>
            </div>
        </form>

        <!-- Delete Option -->
        <div style="margin-top: 15px; text-align: right;">
            <form method="post" action="saint-martin.php" onsubmit="return confirm('Are you sure you want to delete this Saint Martin request?');" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="modal-delete-id">
                <button type="submit" class="admin-button danger" style="min-height: 28px; padding: 0 10px; font-size: 10px;">
                    Delete Request
                </button>
            </form>
        </div>
    </section>
</div>

<!-- Toast element -->
<div id="toast-container"></div>

<script>
// Sync input between inline forms in the same row
function syncAdminNote(input) {
    const row = input.closest('tr');
    const syncedInput = row.querySelector('.synced-admin-note');
    if (syncedInput) {
        syncedInput.value = input.value;
    }
}

// Quick status dropdown changer
function quickUpdateStatus(select) {
    const form = select.closest('form');
    const row = select.closest('tr');
    const noteForm = row.querySelector('.row-note-form');
    const syncedStatus = noteForm.querySelector('.synced-status');
    if (syncedStatus) {
        syncedStatus.value = select.value;
    }

    submitInlineForm(null, form);
}

// Inline Form submission with AJAX fallback
function submitInlineForm(event, form) {
    if (event) event.preventDefault();

    const formData = new FormData(form);
    formData.append('is_ajax', '1');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
        submitBtn.textContent = 'Saving…';
        submitBtn.disabled = true;
    }

    fetch('saint-martin.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('✓ Request #' + data.id + ' updated successfully!');
            // Update row dataset
            const row = document.getElementById('row-' + data.id);
            if (row) {
                const reqData = JSON.parse(row.dataset.request);
                reqData.status = data.status;
                reqData.admin_notes = data.admin_notes;
                row.dataset.request = JSON.stringify(reqData);

                // Update select background class
                const select = row.querySelector('.status-dropdown');
                if (select) {
                    select.className = 'inline-status-select status-dropdown status-' + data.status;
                }
            }
        } else {
            showToast('Could not update request.');
        }
    })
    .catch(err => {
        // Fallback to standard form submit
        form.submit();
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.textContent = originalText || 'Save';
            submitBtn.disabled = false;
        }
    });
}

// Modal Details Handler
const modal = document.getElementById('sm-detail-modal');
const detailGrid = document.getElementById('sm-detail-grid');
const modalId = document.getElementById('modal-id');
const modalDeleteId = document.getElementById('modal-delete-id');
const modalStatus = document.getElementById('modal-status');
const modalAdminNotes = document.getElementById('modal-admin-notes');
const modalStatusPill = document.getElementById('modal-status-pill');
const modalWaLink = document.getElementById('modal-wa-link');

const fieldLabels = {
    id: 'Request ID',
    created_at: 'Date Received',
    status: 'Current Status',
    name: 'Guest Full Name',
    whatsapp: 'WhatsApp Phone',
    email: 'Email Address',
    members_count: 'Members / Guests',
    service_needed: 'Service Needed',
    approx_travel_date: 'Approx. Travel Date',
    notes: 'Ship & Room Preferences',
    extra_requests: 'Special Requests & Extra Notes',
    admin_notes: 'Internal Admin Note'
};

function openDetails(id) {
    const row = document.getElementById('row-' + id);
    if (!row) return;

    const data = JSON.parse(row.dataset.request);
    detailGrid.replaceChildren();

    for (const [key, label] of Object.entries(fieldLabels)) {
        if (data[key] === undefined || data[key] === null || data[key] === '') continue;
        const dt = document.createElement('dt');
        const dd = document.createElement('dd');
        dt.textContent = label;
        dd.textContent = data[key];
        detailGrid.append(dt, dd);
    }

    modalId.value = data.id;
    modalDeleteId.value = data.id;
    modalStatus.value = data.status;
    modalAdminNotes.value = data.admin_notes || '';
    
    modalStatusPill.textContent = data.status.toUpperCase();
    modalStatusPill.className = 'admin-status status-' + data.status;

    const cleanPhone = (data.whatsapp || '').replace(/[^0-9]/g, '');
    const waText = encodeURIComponent("Hello " + data.name + "! This is Cox Booking regarding your Saint Martin reservation (#SM-" + data.id + ") for " + data.approx_travel_date + ".");
    modalWaLink.href = 'https://wa.me/' + cleanPhone + '?text=' + waText;

    modal.hidden = false;
}

function closeDetails() {
    modal.hidden = true;
}

modal.addEventListener('click', e => {
    if (e.target === modal) closeDetails();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !modal.hidden) closeDetails();
});

// Toast notification function
function showToast(text) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'flash-toast';
    toast.textContent = text;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(12px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2800);
}
</script>

<?php admin_footer(); ?>

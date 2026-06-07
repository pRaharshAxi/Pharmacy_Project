<?php
session_start();
require_once '../config/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$f_name = $_SESSION['f_name'] ?? 'Admin';

// Fixed query - using correct column name "Price" not "PRICE"
$medicines = $conn->query("SELECT MEDICINE_ID, NAME, CATEGORY, Price, STOCK_QUANTITY, DOSAGE FROM medicine ORDER BY NAME ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Medicines - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background: #f5f5f5;
        display: flex;
        height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        width: 240px;
        background: linear-gradient(180deg, #1F2937 0%, #111827 100%);
        color: white;
        padding: 30px 0;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        box-shadow: 4px 0 12px rgba(0,0,0,0.1);
    }

    .sidebar-logo {
        padding: 0 24px;
        margin-bottom: 32px;
        font-size: 1.4rem;
        font-weight: 700;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        padding: 12px 24px;
        text-decoration: none;
        color: #9CA3AF;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .sidebar-nav a:hover {
        background: rgba(255,255,255,0.05);
        color: white;
    }

    .sidebar-nav a.active {
        background: rgba(59,130,246,0.1);
        color: #3B82F6;
        border-left: 3px solid #3B82F6;
        padding-left: 21px;
    }

    /* Main Content */
    .main-content {
        margin-left: 240px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .header-bar {
        background: white;
        padding: 20px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-bottom: 1px solid #e5e7eb;
    }

    .header-bar h1 {
        font-size: 1.6rem;
        color: #111827;
    }

    .header-user {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-user a {
        padding: 8px 16px;
        background: #3B82F6;
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: 0.2s;
    }

    .header-user a:hover {
        background: #2563EB;
    }

    /* Content Area */
    .content {
        flex: 1;
        overflow-y: auto;
        padding: 32px 40px;
    }

    /* Alert Messages */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }

    .alert-close {
        cursor: pointer;
        font-weight: bold;
        opacity: 0.7;
    }

    .alert-close:hover {
        opacity: 1;
    }

    /* Search and Filters */
    .search-filters {
        background: white;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .search-box {
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.95rem;
        width: 300px;
    }

    .add-medicine-btn {
        padding: 10px 20px;
        background: #3B82F6;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: 0.2s;
    }

    .add-medicine-btn:hover {
        background: #2563EB;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    /* Medicines Grid */
    .medicines-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 24px;
    }

    .medicine-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        transition: all 0.3s;
    }

    .medicine-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #3B82F6;
        transform: translateY(-2px);
    }

    .medicine-image {
        width: 100%;
        height: 180px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        font-weight: bold;
        position: relative;
        overflow: hidden;
    }

    .medicine-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #10B981;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .medicine-badge.low-stock {
        background: #EF4444;
    }

    .medicine-details {
        padding: 20px;
    }

    .medicine-name {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }

    .medicine-category {
        font-size: 0.8rem;
        color: #9CA3AF;
        margin-bottom: 12px;
        text-transform: capitalize;
    }

    .medicine-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 0.9rem;
    }

    .medicine-info span {
        color: #374151;
    }

    .medicine-info strong {
        color: #111827;
        font-weight: 600;
    }

    .medicine-stock {
        padding: 8px;
        border-radius: 4px;
        font-size: 0.85rem;
        text-align: center;
        margin-bottom: 12px;
    }

    .medicine-stock.in-stock {
        background: #DCFCE7;
        color: #166534;
        font-weight: 600;
    }

    .medicine-stock.low-stock {
        background: #FEE2E2;
        color: #991B1B;
        font-weight: 600;
    }

    .medicine-actions {
        display: flex;
        gap: 8px;
    }

    .medicine-actions a,
    .medicine-actions button {
        flex: 1;
        padding: 8px;
        border: none;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: 0.2s;
    }

    .edit-btn {
        background: #3B82F6;
        color: white;
    }

    .edit-btn:hover {
        background: #2563EB;
    }

    .delete-btn {
        background: #EF4444;
        color: white;
    }

    .delete-btn:hover {
        background: #DC2626;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6B7280;
    }

    .empty-state h2 {
        color: #374151;
        margin-bottom: 8px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
        }

        .main-content {
            margin-left: 200px;
        }

        .content {
            padding: 20px;
        }

        .medicines-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }

        .search-filters {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box {
            width: 100%;
        }
    }

    @media (max-width: 640px) {
        .sidebar {
            display: none;
        }

        .main-content {
            margin-left: 0;
        }

        .medicines-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 0 24px; margin-bottom: 32px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_admin.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Dashboard</a>
            <a href="manage_users.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Users</a>
            <a href="manage_medicines.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Medicines</a>
        </nav>
    </div>
    <div style="padding: 0 24px 24px 24px;">
        <div style="display: flex; align-items: center; gap: 10px; background: #23263a; border-radius: 10px; padding: 10px 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #374151; display: flex; align-items: center; justify-content: center; font-size: 1.1em; font-weight: 700; color: #fff;">A</div>
            <div style="flex:1; min-width:0;">
                <div style="font-size: 1em; font-weight: 600; color: #fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($_SESSION['f_name'] . ' ' . ($_SESSION['l_name'] ?? '')); ?>
                </div>
                <div style="font-size: 0.85em; color: #cbd5e1;">Administrator</div>
            </div>
            <a href="logout.php" style="color: #cbd5e1; font-size: 1.2em; text-decoration: none; margin-left: 8px;">⎋</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" style="margin-left: 290px;">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>Medicines</h1>
        <a href="logout.php" style="display: flex; align-items: center; gap: 8px; color: white; background: #3B82F6; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 6px; transition: all 0.3s; font-size: 0.9rem;" onmouseover="this.style.background='#2563EB';" onmouseout="this.style.background='#3B82F6';">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>

    <!-- Content Area -->
    <div class="content">

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            <span class="alert-close" onclick="this.parentElement.style.display='none';">×</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            <span class="alert-close" onclick="this.parentElement.style.display='none';">×</span>
        </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="search-filters">
            <input type="text" class="search-box" id="searchInput" placeholder="🔍 Search medicines by name">
            <a href="add_medicine.php" class="add-medicine-btn">+ Add Medicine</a>
        </div>

        <!-- Medicines Grid -->
        <div class="medicines-grid" id="medicinesGrid">
            <?php
            if ($medicines && $medicines->num_rows > 0) {
                while ($med = $medicines->fetch_assoc()) {
                    $stock = $med['STOCK_QUANTITY'];
                    $stockClass = $stock < 10 ? 'low-stock' : 'in-stock';
                    $stockText = $stock < 10 ? "⚠️ Low Stock ($stock units)" : "✓ In Stock ($stock units)";
                    $badgeClass = $stock < 10 ? 'low-stock' : '';
                    
                    echo "
                    <div class='medicine-card' data-name='" . htmlspecialchars(strtolower($med['NAME'])) . "'>
                        <div class='medicine-image'>
                            <span style='font-size: 4rem;'>💊</span>
                            <span class='medicine-badge $badgeClass'>" . ($stock < 10 ? 'LOW' : 'STOCK') . "</span>
                        </div>
                        <div class='medicine-details'>
                            <div class='medicine-name'>" . htmlspecialchars($med['NAME']) . "</div>
                            <div class='medicine-category'>" . htmlspecialchars($med['CATEGORY']) . "</div>
                            <div class='medicine-info'>
                                <span>Price</span>
                                <strong>Rs. " . number_format($med['Price'], 2) . "</strong>
                            </div>
                            <div class='medicine-info'>
                                <span>Stock</span>
                                <strong>" . $med['STOCK_QUANTITY'] . " units</strong>
                            </div>
                            <div class='medicine-info'>
                                <span>Dosage</span>
                                <strong>" . htmlspecialchars($med['DOSAGE']) . "</strong>
                            </div>
                            <div class='medicine-stock $stockClass'>$stockText</div>
                            <div class='medicine-actions'>
                                <a href='edit_medicine.php?id=" . urlencode($med['MEDICINE_ID']) . "' class='edit-btn'>✏️ Edit</a>
                                <a href='delete_medicine.php?id=" . urlencode($med['MEDICINE_ID']) . "' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete " . htmlspecialchars($med['NAME']) . "?\")'>🗑️ Delete</a>
                            </div>
                        </div>
                    </div>
                    ";
                }
            } else {
                echo "
                <div class='empty-state' style='grid-column: 1 / -1;'>
                    <h2>📦 No medicines found</h2>
                    <p>Start by <a href='add_medicine.php' style='color: #3B82F6; font-weight: 600;'>adding a new medicine</a></p>
                </div>
                ";
            }
            ?>
        </div>

    </div>

</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const medicineCards = document.querySelectorAll('.medicine-card');
    const medicinesGrid = document.getElementById('medicinesGrid');

    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        medicineCards.forEach(card => {
            const name = card.dataset.name;

            if (name.includes(searchTerm)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Remove any existing empty state
        const existingEmptyState = medicinesGrid.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.remove();
        }

        // Show empty state if no results
        if (visibleCount === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.style.gridColumn = '1 / -1';
            emptyState.innerHTML = '<h2>🔍 No medicines found</h2><p>Try a different search term.</p>';
            medicinesGrid.appendChild(emptyState);
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

</body>
</html>

<?php $conn->close(); ?>
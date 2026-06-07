<?php
session_start();
require_once '../config/db_connect.php';

// Admin login check
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

// Fetch users
$users = $conn->query("SELECT USER_ID, F_NAME, L_NAME, EMAIL, ROLE FROM users ORDER BY ROLE, F_NAME");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - MedCare</title>
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

    .header-user a {
        padding: 8px 16px;
        background: #3B82F6;
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9rem;
    }

    /* Content Area */
    .content {
        flex: 1;
        overflow-y: auto;
        padding: 32px 40px;
    }

    /* Search and Filters */
    .search-filters {
        background: white;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .search-box-wrapper {
        margin-bottom: 20px;
    }

    .search-box {
        width: 100%;
        max-width: 400px;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .filter-tabs {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-tab {
        padding: 8px 16px;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s;
        color: #6B7280;
        font-weight: 500;
    }

    .filter-tab:hover {
        background: #f9fafb;
    }

    .filter-tab.active {
        background: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }

    /* User Cards Grid */
    .users-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }

    .user-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        transition: all 0.3s;
        text-align: center;
    }

    .user-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #3B82F6;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .user-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }

    .user-email {
        font-size: 0.85rem;
        color: #6B7280;
        margin-bottom: 12px;
    }

    .user-role {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .role-admin {
        background: #FEE2E2;
        color: #991B1B;
    }

    .role-pharmacist {
        background: #D1FAE5;
        color: #065F46;
    }

    .role-customer {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .user-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
        margin-top: 12px;
    }

    .tag {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 12px;
        background: #f0f4f8;
        color: #4B5563;
        border: 1px solid #e0e0e0;
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

        .users-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }

        .header-bar {
            padding: 15px 20px;
        }
    }

    @media (max-width: 640px) {
        .sidebar {
            display: none;
        }

        .main-content {
            margin-left: 0;
        }

        .users-grid {
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
            <a href="manage_users.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Users</a>
            <a href="manage_medicines.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Medicines</a>
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
        <h1>Users</h1>
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

        <!-- Search and Filters -->
        <div class="search-filters">
            <div class="search-box-wrapper">
                <input type="text" class="search-box" placeholder="🔍 Search users" id="searchInput">
            </div>
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="admin">Admin</button>
                <button class="filter-tab" data-filter="pharmacist">Pharmacist</button>
                <button class="filter-tab" data-filter="customer">Customer</button>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="users-grid" id="usersGrid">
            <?php
            if ($users && $users->num_rows > 0) {
                while ($user = $users->fetch_assoc()) {
                    $initials = substr($user['F_NAME'], 0, 1) . substr($user['L_NAME'], 0, 1);
                    $role_lower = strtolower($user['ROLE']);
                    $role_class = 'role-' . $role_lower;
                    $tags = $role_lower === 'admin' ? ['Manager', 'System'] : ($role_lower === 'pharmacist' ? ['Pharmacy', 'Staff'] : ['Customer', 'User']);
                    
                    echo "
                    <div class='user-card' data-role='" . $role_lower . "'>
                        <div class='user-avatar'>$initials</div>
                        <div class='user-name'>" . htmlspecialchars($user['F_NAME'] . ' ' . $user['L_NAME']) . "</div>
                        <div class='user-email'>" . htmlspecialchars($user['EMAIL']) . "</div>
                        <div class='user-role $role_class'>" . htmlspecialchars($user['ROLE']) . "</div>
                        <div class='user-tags'>
                            " . implode('', array_map(fn($tag) => "<span class='tag'>$tag</span>", $tags)) . "
                        </div>";
                    if ($role_lower === 'pharmacist' || $role_lower === 'customer') {
                        echo "<form method='get' action='delete_user.php' onsubmit=\"return confirm('Are you sure you want to delete this user?');\" style='margin-top:10px;'><input type='hidden' name='id' value='" . htmlspecialchars($user['USER_ID']) . "'><input type='hidden' name='delete' value='1'><button type='submit' style='background:#ef4444;color:#fff;border:none;padding:7px 16px;border-radius:6px;cursor:pointer;font-weight:600;'>Delete</button></form>";
                    }
                    echo "</div>";
                }
            } else {
                echo "
                <div class='empty-state' style='grid-column: 1 / -1;'>
                    <h2>No users found</h2>
                    <p>There are currently no users in the system.</p>
                </div>
                ";
            }
            ?>
        </div>

        <script>
            const filterTabs = document.querySelectorAll('.filter-tab');
            const searchInput = document.getElementById('searchInput');
            const usersGrid = document.getElementById('usersGrid');

            // Get all user cards (excluding empty state)
            function getUserCards() {
                return Array.from(document.querySelectorAll('.user-card'));
            }

            // Filter by role
            filterTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    filterTabs.forEach(t => t.classList.remove('active'));
                    // Add active class to clicked tab
                    tab.classList.add('active');

                    const filter = tab.dataset.filter;
                    const searchTerm = searchInput.value.toLowerCase().trim();

                    filterUsers(filter, searchTerm);
                });
            });

            // Search functionality
            searchInput.addEventListener('keyup', () => {
                const activeTab = document.querySelector('.filter-tab.active');
                const filter = activeTab.dataset.filter;
                const searchTerm = searchInput.value.toLowerCase().trim();

                filterUsers(filter, searchTerm);
            });

            function filterUsers(filter, searchTerm) {
                const userCards = getUserCards();
                let visibleCount = 0;

                userCards.forEach(card => {
                    const role = card.dataset.role || '';
                    
                    // Get the actual text content from the card
                    const nameElement = card.querySelector('.user-name');
                    const emailElement = card.querySelector('.user-email');
                    
                    const name = nameElement ? nameElement.textContent.toLowerCase() : '';
                    const email = emailElement ? emailElement.textContent.toLowerCase() : '';

                    // Check role filter
                    const roleMatch = filter === 'all' || role === filter;

                    // Check search filter - if search term is empty, show all matching role
                    const searchMatch = searchTerm === '' || name.includes(searchTerm) || email.includes(searchTerm);

                    // Show or hide card
                    if (roleMatch && searchMatch) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Show empty state if no users match
                let emptyState = document.querySelector('.empty-state');
                if (visibleCount === 0) {
                    if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.className = 'empty-state';
                        emptyState.style.gridColumn = '1 / -1';
                        emptyState.innerHTML = '<h2>No users found</h2><p>Try adjusting your search or filters.</p>';
                        usersGrid.appendChild(emptyState);
                    }
                } else {
                    if (emptyState) emptyState.remove();
                }
            }
        </script>

    </div>

</div>

</body>
</html>

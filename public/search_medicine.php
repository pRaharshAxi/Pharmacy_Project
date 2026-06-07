<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$f_name = $_SESSION['f_name'] ?? 'Customer';
$l_name = $_SESSION['l_name'] ?? '';

$search_query = '';
$results = null;

// Handle search
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $search_query = trim($_GET['query']);
    
    // Search in name and category with proper column names
    $sql = "SELECT * FROM medicine 
            WHERE (NAME LIKE ? OR CATEGORY LIKE ?) 
            AND STOCK_QUANTITY > 0 
            ORDER BY NAME ASC";
    
    $stmt = $conn->prepare($sql);
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Medicines - MedCare</title>
    <link rel="stylesheet" href="../css/dashboard.css">
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

        .main-content {
            margin-left: 290px;
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
            color: #111827;
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }

        .content {
            flex: 1;
            overflow-y: auto;
            padding: 32px 40px;
            display: flex;
            flex-direction: column;
        }

        /* Page wrapper */
        .search-container {
            max-width: 100%;
            margin: 0 0 2rem 0;
            padding: 0;
        }

        /* Search box with glassmorphism */
        .search-form {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 2rem 3rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-form h2 {
            margin-bottom: 1rem;
            color: white;
            font-size: 2rem;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        /* Search input */
        .search-input-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-input-group input {
            flex: 1;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .search-input-group input::placeholder {
            color: rgba(100, 100, 100, 0.6);
        }

        .search-input-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            background: white;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }

        .search-input-group button {
            padding: 1rem 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .search-input-group button:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* Filters - Category Buttons */
        .filter-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .filter-label {
            display: none;
        }

        .filter-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 1.2rem 1.5rem;
            background: rgba(255, 255, 255, 0.85);
            color: #1d3557;
            border-radius: 15px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            min-height: 60px;
            backdrop-filter: blur(10px);
        }

        .filter-btn svg {
            stroke: #1d3557;
            flex-shrink: 0;
        }

        .filter-btn:hover {
            background: white;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Results */
        .results-section {
            margin-top: 0;
        }

        .results-header {
            margin-bottom: 1.5rem;
        }

        .results-header h3 {
            color: #111827;
            font-size: 1.3rem;
            font-weight: 500;
        }

        /* Grid */
        .medicines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        /* Card */
        .medicine-card {
            background: white;
            padding: 1.2rem;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .medicine-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .medicine-card h3 {
            margin-bottom: 0.8rem;
            color: #2d3748;
            font-size: 1.1rem;
        }

        /* Semantic class names for medicine details */
        .medicine-category,
        .medicine-price,
        .medicine-stock,
        .medicine-dosage {
            margin: 0.4rem 0;
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .medicine-price {
            color: #38a169;
            font-weight: 600;
            font-size: 1rem;
        }

        .medicine-stock {
            color: #718096;
        }

        /* Button */
        .view-btn {
            display: inline-block;
            margin-top: 0.8rem;
            padding: 0.5rem 1rem;
            background: #48bb78;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .view-btn:hover {
            background: #38a169;
        }

        /* No results */
        .no-results {
            background: #f7fafc;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            color: #555;
        }

        .no-results p {
            margin: 0.5rem 0;
        }

        .no-results ul {
            list-style: none;
            padding: 0;
            margin-top: 1rem;
        }

        .no-results ul li {
            margin: 0.3rem 0;
            color: #666;
        }

        .no-results a {
            margin-top: 1rem;
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .no-results a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .filter-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .medicines-grid {
                grid-template-columns: 1fr;
            }

            .search-input-group {
                flex-direction: column;
            }

            .search-input-group button {
                width: 100%;
            }

            .filter-buttons {
                grid-template-columns: 1fr;
            }

            .search-form {
                padding: 1.5rem;
            }

            .search-form h2 {
                font-size: 1.5rem;
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
            <a href="dashboard_customer.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Dashboard</a>
            <a href="view_medicines.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">View Medicines</a>
            <a href="search_medicine.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Search</a>
            <a href="my_order.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">My Orders</a>
            <div style="height:1px; background:#ef4444; width:80%; margin:12px auto; border-radius:1px;"></div>
            <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #ef4444; font-weight: 600;">Logout</a>
        </nav>
    </div>
    <div style="padding: 0 24px 24px 24px;">
        <div style="display: flex; align-items: center; gap: 10px; background: #23263a; border-radius: 10px; padding: 10px 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #374151; display: flex; align-items: center; justify-content: center; font-size: 1.1em; font-weight: 700; color: #fff;">C</div>
            <div style="flex:1; min-width:0;">
                <div style="font-size: 1em; font-weight: 600; color: #fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($f_name . ' ' . $l_name); ?></div>
                <div style="font-size: 0.85em; color: #cbd5e1;">Customer</div>
            </div>
            <a href="logout.php" style="color: #cbd5e1; font-size: 1.2em; text-decoration: none; margin-left: 8px;">⎋</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>Search Medicines</h1>
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
        <div class="search-container">
            <form method="GET" action="search_medicine.php">
                <div class="search-input-group">
                    <input 
                        type="text" 
                        name="query" 
                        placeholder="Search by medicine name or category..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                        required
                    >
                    <button type="submit">Search</button>
                </div>
            </form>
            
            <div class="filter-buttons">
                <a href="search_medicine.php?query=Pain and Fever" class="filter-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="6" rx="4" ry="6"></ellipse>
                        <ellipse cx="12" cy="18" rx="4" ry="6"></ellipse>
                        <line x1="16" y1="12" x2="8" y2="12"></line>
                    </svg>
                    Pain & Fever
                </a>
                <a href="search_medicine.php?query=Antibiotics" class="filter-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 3v18"></path>
                        <path d="M15 3v18"></path>
                        <circle cx="12" cy="9" r="3"></circle>
                        <circle cx="12" cy="15" r="3"></circle>
                    </svg>
                    Antibiotics
                </a>
                <a href="search_medicine.php?query=Chronic Care" class="filter-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    Chronic Care
                </a>
                <a href="search_medicine.php?query=General wellness" class="filter-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    Wellness
                </a>
            </div>
        </div>
        
        <?php if ($results !== null): ?>
            <div class="results-section">
                <div class="results-header">
                    <h3>
                        <?php 
                        if ($results->num_rows > 0) {
                            echo "Found " . $results->num_rows . " result(s) for \"" . htmlspecialchars($search_query) . "\"";
                        } else {
                            echo "No results found for \"" . htmlspecialchars($search_query) . "\"";
                        }
                        ?>
                    </h3>
                </div>
            
            <?php if ($results->num_rows > 0): ?>
                <div class="medicines-grid">
                    <?php while($medicine = $results->fetch_assoc()): ?>
                        <div class="medicine-card">
                            <h3><?php echo htmlspecialchars($medicine['NAME']); ?></h3>
                            
                            <div class="medicine-category">
                                <strong>Category:</strong> <?php echo htmlspecialchars($medicine['CATEGORY']); ?>
                            </div>
                            
                            <div class="medicine-price">
                                Rs. <?php echo number_format($medicine['Price'], 2); ?>
                            </div>
                            
                            <div class="medicine-stock">
                                <strong>Stock:</strong> <?php echo $medicine['STOCK_QUANTITY']; ?> units
                            </div>
                            
                            <div class="medicine-dosage">
                                <strong>Dosage:</strong> <?php echo htmlspecialchars($medicine['DOSAGE']); ?>
                            </div>
                            
                            <a href="medicine_details.php?id=<?php echo urlencode($medicine['MEDICINE_ID']); ?>" class="view-btn">
                                View Details
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>😔 No medicines found matching your search.</p>
                    <p>Try searching for:</p>
                    <ul>
                        <li>Medicine names (e.g., "Paracetamol", "Ibuprofen")</li>
                        <li>Categories (e.g., "Antibiotics", "Pain and Fever")</li>
                    </ul>
                    <a href="view_medicines.php">
                        View all medicines →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
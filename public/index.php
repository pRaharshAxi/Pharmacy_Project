<?php
session_start();

/* Redirect logged-in users based on role */
if (!empty($_SESSION['role'])) {
    switch (strtoupper($_SESSION['role'])) {
        case 'ADMIN':
            header("Location: dashboard_admin.php");
            exit();
        case 'PHARMACIST':
            header("Location: dashboard_pharmacist.php");
            exit();
        case 'CUSTOMER':
            header("Location: dashboard_customer.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MedCare | Pharmacy Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>


<header class="modern-header">
  <div class="header-inner">
    <div class="header-logo">
      <span class="logo-text">MedCare</span>
    </div>
    <nav class="header-nav">
      <a href="#features">Features</a>
      <a href="#about">About</a>
      <a href="login.php">Login</a>
    </nav>
    <div class="header-cta">
      <a href="register.php" class="btn-header-cta">Get Started</a>
    </div>
  </div>
</header>

<main>

<!-- Hero -->
<section class="hero">
    <div class="hero-grid">
        <div class="hero-left">
            <span class="eyebrow">Medicine & Pharmacy</span>
            <h1>Smart Pharmacy Management Made Simple</h1>
            <p class="hero-lead">Manage medicines, customers, suppliers, orders, and invoices — all in one secure and efficient system designed for modern pharmacies.</p>

            <div class="hero-ctas">
                <a href="register.php" class="btn btn-primary">Get Started</a>
            </div>

            <div class="hero-pills">
                <div class="pill"><img src="../assets/medicine-pain-fever.svg" alt=""> Pain & Fever</div>
                <div class="pill"><img src="../assets/medicine-antibiotics.svg" alt=""> Antibiotics</div>
                <div class="pill"><img src="../assets/medicine-chronic.svg" alt=""> Chronic Care</div>
            </div>

            <div style="display: flex; align-items: center; gap: 16px; margin-top: 24px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; border: 3px solid white; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">U</div>
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 50%; border: 3px solid white; margin-left: -12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">S</div>
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #4facfe, #00f2fe); border-radius: 50%; border: 3px solid white; margin-left: -12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">E</div>
                </div>
                <div>
                    <div style="font-size: 1.3rem; font-weight: 700; color: #111827;">1000+</div>
                    <div style="font-size: 0.9rem; color: #666;">active users</div>
                </div>
            </div>
        </div>

        <div class="hero-right">
            <div class="hero-image">
              <img src="../assets/4447.jpg?v=<?php echo time(); ?>" alt="Pharmacist">
              <div class="floating-circle">+</div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features" id="features">
  <div class="bento-grid">

    <article class="bento-card equal green">
      <div class="card-content">
        <div class="card-text">
          <div class="card-header">
            <div class="card-icon"><img src="../assets/inventory.svg" alt="Inventory"></div>
            <h3>Smart Inventory</h3>
          </div>
          <p>Track stock levels, low-stock alerts and automated purchase requests to keep your pharmacy running smoothly.</p>
        </div>
        <div class="card-image"><img src="../assets/smart inventory.jpg" alt="Inventory"></div>
      </div>
    </article>

    <article class="bento-card equal gray">
      <div class="card-content">
        <div class="card-text">
          <div class="card-header">
            <div class="card-icon"><img src="../assets/customers.svg" alt="Customers"></div>
            <h3>Customer Profiles</h3>
          </div>
          <p>Maintain customer records, contact info, and order history for fast service and repeat business.</p>
        </div>
        <div class="card-image"><img src="../assets/customer profiles.jpg" alt="Customers"></div>
      </div>
    </article>

    <article class="bento-card equal blue">
      <div class="card-content">
        <div class="card-text">
          <div class="card-header">
            <div class="card-icon"><img src="../assets/sales.svg" alt="Sales"></div>
            <h3>Sales & Billing</h3>
          </div>
          <p>Generate invoices, manage payments and view sales reports easily with our integrated billing tools.</p>
        </div>
        <div class="card-image"><img src="../assets/sales and billing.jpg" alt="Sales"></div>
      </div>
    </article>

    <article class="bento-card equal pink">
      <div class="card-content">
        <div class="card-text">
          <div class="card-header">
            <div class="card-icon"><img src="../assets/prescription.svg" alt="Prescription"></div>
            <h3>Prescription Management</h3>
          </div>
          <p>Store and attach prescriptions to orders so you can validate and dispense medication safely.</p>
        </div>
        <div class="card-image"><img src="../assets/prescription management.jpg" alt="Prescription"></div>
      </div>
    </article>

    <article class="bento-card equal yellow">
      <div class="card-content">
        <div class="card-text">
          <div class="card-header">
            <div class="card-icon"><img src="../assets/supplier.svg" alt="Suppliers"></div>
            <h3>Supplier & Purchases</h3>
          </div>
          <p>Manage suppliers, purchase orders and track incoming stock all from one screen.</p>
        </div>
        <div class="card-image"><img src="../assets/supplier.jpg" alt="Suppliers"></div>
      </div>
    </article>

    <article class="bento-card equal purple-light">
      <div class="card-content">
        <div class="card-text">
          <div class="card-header">
            <div class="card-icon"><img src="../assets/sales.svg" alt="Reports"></div>
            <h3>Reports & Analytics</h3>
          </div>
          <p>Gain insight into sales trends, inventory turnover, and purchase history with detailed reports.</p>
        </div>
        <div class="card-image"><img src="../assets/smart.jpg" alt="Reports"></div>
      </div>
    </article>

  </div>
</section>

<!-- About -->
<section class="about" id="about">
    <div class="about-wrapper">
        <div class="about-right">
            <div class="about-image-container">
                <img src="../assets/about.jpeg" alt="About MedCare" class="about-image">
                <div class="about-text-overlay">
                    <h2>About Us</h2>
                    <p>
                        MedCare is a modern pharmacy management system designed to simplify
                        inventory, billing, and customer care. We build secure, fast, and
                        user-friendly tools to help pharmacies manage stock, sales, and
                        customer records with confidence.
                    </p>
                </div>
                <div class="about-decorative-spheres" aria-hidden="true">
                    <div class="sphere sphere-1"></div>
                    <div class="sphere sphere-2"></div>
                    <div class="sphere sphere-3"></div>
                    <div class="sphere sphere-4"></div>
                    <div class="sphere sphere-5"></div>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

<footer class="modern-footer">
  <div class="footer-content">
    <div class="footer-top">
      <div class="footer-brand">
        <span class="footer-logo">MedCare</span>
        <span class="footer-tagline">Smart pharmacy management that works.</span>
      </div>
      <div class="footer-social">
        <a href="#" aria-label="LinkedIn"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.469v6.766z"/></svg></a>
        <a href="#" aria-label="Instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.057-1.645.069-4.849.069-3.204 0-3.584-.012-4.849-.069-3.259-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1 1 12.324 0 6.162 6.162 0 0 1-12.324 0zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm4.965-10.322a1.44 1.44 0 1 1 2.881.001 1.44 1.44 0 0 1-2.881-.001z"/></svg></a>
        <a href="#" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
        <a href="#" aria-label="YouTube"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
      </div>
    </div>

    <div class="footer-divider"></div>

    <div class="footer-middle">
      <div class="footer-column">
        <h4>Company</h4>
        <ul>
          <li><a href="#about">About Us</a></li>
          <li><a href="#features">Services</a></li>
          <li><a href="#about">Features</a></li>
          <li><a href="#">Testimonials</a></li>
          <li><a href="#">Contact</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h4>Navigation</h4>
        <ul>
          <li><a href="#features">Main Benefits</a></li>
          <li><a href="#features">Our Services</a></li>
          <li><a href="#">Why Choose Us</a></li>
          <li><a href="#">Success Stories</a></li>
          <li><a href="#">Blog</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h4>Contact</h4>
        <div class="contact-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
          <a href="mailto:support@medcare.com">support@medcare.com</a>
        </div>
        <div class="contact-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          <a href="tel:+1234567890">+94 71 3835341</a>
        </div>
        <div class="contact-item">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          <span>Colombo, Sri Lanka</span>
        </div>
      </div>
    </div>

    <div class="footer-divider"></div>

    <div class="footer-bottom">
      <p>&copy; 2025 MedCare. All rights reserved.</p>
      <div class="footer-legal">
        <a href="#">Terms & Conditions</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Cookies</a>
      </div>
    </div>
  </div>
</footer>

</body>
</html>

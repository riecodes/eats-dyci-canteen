# Canteen Management System

## Overview

A robust, multi-role (Admin, Seller, Buyer) canteen management and food ordering system built with PHP and MySQL. It features real-time order management, product and stall management, announcements, sales analytics, and a flexible auto-voiding system for unclaimed orders.

---

## Features

### User Roles

- **Admin**
  - Manage all users, stalls, products, and orders
  - View, edit, and delete any order
  - Access sales reports and analytics (with bar chart)
  - Manage announcements
  - Backup and download the database
  - Toggle the 3PM auto-voiding of orders on/off

- **Seller**
  - Manage products (CRUD, image upload, stock)
  - View and process orders for their stalls
  - Upload and display payment QR codes
  - View sales and revenue (including voided orders)
  - Receive notifications for new orders

- **Buyer**
  - Register and log in
  - Browse canteens, stalls, and products
  - Place orders (max 5 items per order)
  - Upload payment receipts (portrait validation)
  - Track order status and view announcements

---

## Key Business Rules

- **Order Statuses:** `queue`, `processing`, `processed`, `done`, `void`
- **Voiding System:**
  - Orders are auto-voided at 3:00 PM on the day they are placed (if not picked up)
  - Orders placed after 3:00 PM are voided immediately
  - Admin can enable/disable this auto-voiding via a dashboard switch
- **Order Placement Restriction:** Orders cannot be placed after 2:45 PM
- **Cart Limit:** Buyers can order a maximum of 5 items per order
- **Sales/Revenue:** Voided orders are included in sales and revenue calculations

---

## UI/UX Highlights

- Consistent status badges and color coding
- Compact, readable order item lists (comma-separated, not bulleted)
- Responsive tables and modals with improved padding and whitespace
- Announcement cards with full-width images (1:1 aspect ratio)
- Seller and buyer announcement pages use unified card design
- Admin and seller dashboards are visually consistent

---

## Database

- **MySQL** (see `database/canteen.sql` for schema)
- **Key Tables:** `users`, `stalls`, `products`, `orders`, `order_items`, `announcements`, `settings`
- **Settings Table:** Used for global toggles (e.g., `auto_void_enabled`)

```sql
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    value VARCHAR(255) NOT NULL
);

INSERT INTO settings (name, value) VALUES ('auto_void_enabled', '1');
```

---

## Installation

1. **Clone the repository**
2. **Import the database**
   - Use `database/canteen.sql` (or your backup) to set up the schema
3. **Configure database connection**
   - Edit `/includes/db.php` with your MySQL credentials
4. **Set up file permissions**
   - Ensure `/assets/imgs/` and `/database/` are writable for uploads and backups
5. **Access the system**
   - Open in your browser via XAMPP or your preferred PHP server

---

## Admin Controls

- **3PM Auto-Void Switch:**  
  Admins can enable/disable the auto-voiding of orders at 3PM from the admin orders page. This affects both admin and seller order management.
- **Database Backup:**  
  Admins can create and download SQL backups from the `/database` folder. Existing backups are listed with filename, size, and download link.
- **Sales Report:**  
  View product sales, seller, stall, canteen, quantity sold, and total sales, with a bar chart visualization.

---

## Seller Controls

- **Product Management:**  
  Add, edit, and delete products, including image and stock management.
- **Order Management:**  
  View and process orders, update status, and see voiding status.
- **QR Code Upload:**  
  Upload a payment QR code for buyers to scan.

---

## Buyer Experience

- **Order Placement:**  
  Add up to 5 items per order, upload payment receipt (portrait only), and track order status.
- **Announcements:**  
  View announcements with images in a unified card design.

---

## Customization & Extensibility

- All business rules (voiding, order limits, etc.) are enforced server-side.
- UI is built with Bootstrap for easy customization.
- Settings table allows for easy addition of new global toggles.

---

## Security

- All user input is validated and sanitized.
- File uploads are restricted by type and size.
- Session management and role-based access control are enforced.

---

## License

MIT License

---

**For more details, see the code comments and inline documentation.**

## Cartoon Canteen System (2024 Redesign)

This project introduces a vibrant, cartoon-style UI/UX for the canteen management system, inspired by the plan in REVISION.md. All new features are additive and do not disrupt legacy code.

### New Files
- `css/cartoon-style.css`: Base cartoon styles, color palette, utility classes
- `css/cartoon-animations.css`: Cartoon-specific animations and accessibility toggle
- `css/variables.css`: Color variables and font imports
- `js/cartoon-effects.js`: Micro-interactions (e.g., confetti), animation toggles
- `php/cartoon_nav.php`: Cartoon-style navigation bar include
- `php/food_court_section.php`: Cartoon-style food court section include

### How to Use
- **CSS**: Link the new CSS files in your HTML/PHP templates (after legacy styles for override).
- **JS**: Link `cartoon-effects.js` for micro-interactions and accessibility toggles.
- **PHP**: Use `include 'php/cartoon_nav.php';` and `include 'php/food_court_section.php';` in your pages to add new cartoon components.
- **Accessibility**: Use `toggleCartoonAnimations(false)` in JS to disable all cartoon animations for accessibility.

### Migration
- You can gradually migrate legacy sections to the new cartoon style by replacing or wrapping them with the new components and classes.

See REVISION.md for the full design vision and roadmap. 
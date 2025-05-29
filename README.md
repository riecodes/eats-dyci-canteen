# Canteen Management System – Backend Documentation

## Table of Contents

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [User Roles & Permissions](#user-roles--permissions)
- [Database Structure](#database-structure)
- [API Endpoints](#api-endpoints)
- [Authentication & Security](#authentication--security)
- [Order & Payment Flow](#order--payment-flow)
- [Admin Features](#admin-features)
- [Seller Features](#seller-features)
- [Buyer Features](#buyer-features)
- [Announcement System](#announcement-system)
- [Backup & Restore](#backup--restore)
- [Session & State Management](#session--state-management)
- [Frontend Structure](#frontend-structure)
- [Extending or Porting the System](#extending-or-porting-the-system)
- [Development & Deployment](#development--deployment)
- [Conventions & Guidelines](#conventions--guidelines)

---

## Overview

This project is a robust backend for a multi-canteen food ordering and management system. It supports multiple user roles (admin, seller, buyer), order and payment workflows, product and stall management, announcements, and more. The backend is built with Node.js, Express, MySQL, and JWT-based authentication.

---

## System Architecture

- **Backend:** Node.js + Express.js
- **Database:** MySQL (see `database/canteen.sql` for schema and seed data)
- **Frontend:** Static HTML/CSS/JS (see `public/`)
- **Session:** Express Session (for legacy, JWT for API)
- **Authentication:** JWT (see `middleware/authMiddleware.js`)
- **File Uploads:** Multer (for images, QR codes)
- **Password Hashing:** bcryptjs

---

## User Roles & Permissions

### Admin
- Can create/manage seller and buyer accounts (sellers cannot self-register)
- Can create/manage stalls and assign sellers to stalls
- Can view, edit, and delete any user
- Can manage system-wide settings (TBD)
- Can perform database backup/restore

### Seller
- Created by admin only
- Can manage products (add/edit/delete, upload images, set prices)
- Can view and process orders, update order status (Queue → Processing → Done)
- Can approve/decline orders based on payment verification
- Can void orders (auto-cancel unclaimed orders)
- Can manage QR code-based payment methods

### Buyer
- Can self-register
- Can browse canteens, stalls, and products
- Can place orders (max 5 items per order)
- Can choose payment method and scan QR code
- Waits for seller's payment verification

---

## Database Structure

See `database/canteen.sql` for full schema and seed data.

**Key Tables:**
- `users` (id, name, email, password, role, created_at)
- `stalls` (id, name, description, owner_id)
- `categories` (id, name, description, image, stall_id)
- `foods` (id, name, description, price, image, category_id, stall_id)
- `orders` (orderRef, user_id, total_price, created_at, status)
- `order_items` (id, order_id, food_id, quantity)
- `reviews` (id, food_id, user_id, parent_id, comment, rating, created_at)
- `announcements` (id, title, message, type, seller_id, stall_id, created_at)
- `announcement_images` (id, announcement_id, image_url)
- `user_announcement_views` (id, user_id, announcement_id, viewed_at)

---

## API Endpoints

### Authentication
- `POST /api/auth/login` – Login (returns JWT)
- `POST /api/auth/register` – Register (buyer only)

### Users (Admin only)
- `GET /api/users/all` – List all users
- `POST /api/users/create` – Add user (admin, seller, buyer)
- `PUT /api/users/:id/role` – Update user role
- `DELETE /api/users/:id` – Delete user

### Stalls
- `GET /api/stalls` – List all stalls
- `POST /api/stalls` – Add stall (admin only)
- `PUT /api/stalls/:id` – Edit stall
- `DELETE /api/stalls/:id` – Delete stall

### Foods
- `GET /api/foods` – List all foods
- `POST /api/foods` – Add food (seller only)
- `PUT /api/foods/:id` – Edit food
- `DELETE /api/foods/:id` – Delete food

### Orders
- `POST /api/orders` – Place order (buyer)
- `GET /api/orders/user` – Get user orders (buyer)
- `GET /api/orders/recent` – Get recent orders (admin/seller)
- `PUT /api/orders/:orderRef/status` – Update order status (seller/admin)

### Reviews
- `POST /api/reviews` – Add review
- `GET /api/reviews/:food_id` – Get reviews for food
- `DELETE /api/reviews/:id` – Delete review (owner/admin)

### Announcements
- `GET /api/announcements` – List announcements
- `POST /api/announcements` – Create announcement (admin/seller)
- `POST /api/announcements/viewed` – Mark as viewed

### Payments
- `POST /api/payments` – Initiate payment (QR code, GCash, etc.)
- `GET /api/payments/status` – Check payment status

---

## Authentication & Security

- **JWT-based authentication** for all API endpoints (see `middleware/authMiddleware.js`)
- **Role-based access control** (admin, seller, buyer)
- **Password hashing** with bcryptjs
- **Session management** for legacy routes
- **CORS** enabled for all routes

---

## Order & Payment Flow

1. Buyer selects "Order Now"
2. System displays canteen locations, stalls, and items
3. Buyer places order (max 5 items)
4. Buyer selects payment method, system generates QR code
5. Buyer scans QR code and pays
6. Seller verifies payment and approves/declines order
7. If approved, order is processed and buyer picks up
8. If declined, order is cancelled and buyer is notified
9. Orders auto-void if not claimed in time

---

## Admin Features

- User management (add/edit/delete, assign roles)
- Stall management (add/edit/delete, assign sellers)
- System configuration (TBD)
- Database backup/restore (manual via SQL)
- View analytics and reports

---

## Seller Features

- Product management (CRUD, image upload)
- Order management (view, process, update status)
- Payment verification (QR code, GCash, Paymongo, Xendit)
- Order voiding (auto-cancel unclaimed orders)
- View sales analytics

---

## Buyer Features

- Self-registration
- Browse canteens, stalls, and products
- Place orders (max 5 items)
- Choose payment method, scan QR code
- Track order status
- Leave reviews

---

## Announcement System

- Admins and sellers can create announcements (with images)
- Buyers can view announcements and mark as read
- Announcement images are stored in `announcement_images`
- User views tracked in `user_announcement_views`

---

## Backup & Restore

- **Backup:** Export MySQL database using `mysqldump` or phpMyAdmin
- **Restore:** Import `canteen.sql` into MySQL
- **Data integrity:** Foreign keys and constraints enforced in schema

---

## Session & State Management

- **JWT** for API authentication
- **Express Session** for legacy session support
- **User info** (role, id) stored in JWT and available in `req.user`

---

## Frontend Structure

- **Static HTML/CSS/JS** in `public/`
- **Admin panel:** `public/admin/`
- **Seller pages:** `public/seller-pages/`
- **Assets:** `public/assets/` (images, styles, etc.)
- **JS:** `public/js/` (client-side logic)
- **Responsive design** with Bootstrap

---

## Extending or Porting the System

- **All business logic is in controllers and routes** – easy to port to PHP or other backend
- **Database schema is normalized and well-documented** – can be recreated in MySQL, MariaDB, or other RDBMS
- **Frontend is decoupled** – can be replaced with vanilla HTML/CSS/JS or any framework
- **API endpoints are RESTful** – can be mapped to PHP endpoints
- **Authentication and authorization logic** is modular (see `middleware/`)

---

## Development & Deployment

- **Install dependencies:** `npm install`
- **Configure environment:** `.env` file (see example in README)
- **Import database:** Use `database/canteen.sql`
- **Run server:** `node server.js`
- **Access API docs:** `/api-docs` (if enabled)

---

## Conventions & Guidelines

- **Always handle errors gracefully** (see controllers for patterns)
- **Use async/await for all DB operations**
- **Enforce role-based access in all admin/seller endpoints**
- **Document all changes and fixes in `public/assets/memory.txt`**
- **If an error occurs (missing file/module), fix it and note in memory.txt**

---

## Migration Notes

- **To port to PHP:**  
  - Recreate the database schema in MySQL/MariaDB  
  - Implement endpoints in PHP using the same RESTful structure  
  - Use PHP sessions or JWT for authentication  
  - Map controllers to PHP classes or scripts  
  - Use vanilla JS for frontend logic, AJAX for API calls

---

**This README is designed to serve as a blueprint for re-implementing or extending the system in any stack.**  
If you need a more detailed breakdown of any module, just ask! 
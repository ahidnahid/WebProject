# 🚌 BusBD - Bangladesh Bus Ticket Booking Platform

A web-based bus ticket booking system designed for Bangladesh, allowing users to search routes, book tickets, track buses, and get AI-powered travel recommendations.


---

## ✨ Features

- 🎟️ **Ticket Booking** — Search routes and book bus tickets online
- 📍 **Bus Tracking** — Real-time bus location tracking
- 💰 **Dynamic Pricing** — View fare details for different routes
- 🧭 **Route Recommendations** — Suggested routes based on destination
- 📱 **Responsive Design** — Mobile-friendly interface

---

## 🛠️ Tech Stack

| Layer     | Technology          |
|-----------|---------------------|
| Frontend  | HTML5, CSS3, JavaScript |
| Backend   | PHP                 |
| Database  | MySQL (SQL)         |

---

## 🚀 Getting Started

### Prerequisites
- PHP 7.4+
- MySQL
- A local server (XAMPP / WAMP / Laragon)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/ahidnahid/BusBD.git

# 2. Move to your server's htdocs or www folder
# e.g., C:/xampp/htdocs/BusBD

# 3. Import the database
# Open phpMyAdmin → Create a new database → Import database.sql

# 4. Configure DB connection
# Edit database.php and update your credentials:
```

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "busbd";
```

```bash
# 5. Open in browser
http://localhost/BusBD/index.html
```

---

## 📁 Project Structure

```
BusBD/
├── index.html           # Home page
├── booking.html         # Ticket booking page
├── tracking.html        # Bus tracking page
├── pricing.html         # Fare/pricing page
├── recommendations.html # Route recommendations
├── style.css            # Main stylesheet
├── main.js              # Core JavaScript logic
├── booking.js           # Booking functionality
├── tracking.js          # Tracking functionality
├── pricing.js           # Pricing logic
├── recommendations.js   # Route suggestion logic
├── pricing.php          # Backend: pricing API
├── tracking.php         # Backend: tracking API
├── recommendations.php  # Backend: route API
├── database.php         # DB connection config
└── database.sql         # Database schema & seed data
```

---

## 👤 Author

**Md. Ahidul Islam**
- GitHub: [@ahidnahid](https://github.com/ahidnahid)
- LinkedIn: [md-ahidul-islam](https://linkedin.com/in/md-ahidul-islam-41aa913bb)
- Email: mdahidulislam5113@gmail.com

<div align="center">
  <h1>🎬 CineBook</h1>
  <p><strong>A Modern Online Movie Ticket Booking Platform</strong></p>
  
  <p>
    <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Badge"/>
    <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL Badge"/>
    <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5 Badge"/>
    <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3 Badge"/>
    <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JS Badge"/>
  </p>
</div>

---

## 📝 Project Description

**CineBook** is a comprehensive, web-based movie ticket reservation system designed for a single theater or cinema chain. It digitizes the traditional ticketing process, allowing customers to browse current and upcoming movies, view trailers, select specific seats via an interactive layout, order food and beverages, and simulate a secure checkout process. It also features a dedicated Admin Panel for full management of theater operations.

---

## ✨ Features

### 👤 User Features
- **Authentication**: Secure Registration and Login (Password Hashing via PHP).
- **Movie Catalog**: Browse "Now Showing" and "Coming Soon" movies.
- **Search**: Find specific movies via a dedicated search bar.
- **Interactive Seat Selection**: Visual mapping of theater seats (Vacant/Occupied states).
- **Food & Beverages**: Add snacks to the cart during the booking flow.
- **Checkout**: Simulated payment gateway calculating precise totals.
- **Booking History**: View and cancel past or upcoming bookings.
- **Reviews**: Leave ratings and text reviews for watched movies.

### 🛡️ Admin Features
- **Secure Dashboard**: Protected admin route and session handling.
- **Movie Management**: CRUD operations for movies, cast, crew, and trailers.
- **Showtime Scheduling**: Assign movies to specific screens, dates, and times.
- **User Management**: View registered users and export user data.

---

## 🏗️ Architecture Overview

CineBook utilizes a classic **Three-Tier Client-Server Architecture** running on a **LAMP/WAMP** stack:
1. **Presentation Layer**: HTML5, CSS3, and Vanilla JavaScript handling the UI and interactive seat toggling.
2. **Application Logic Layer**: Apache Web Server running **PHP 8+** for server-side logic, session management, and routing (file-based).
3. **Data Access Layer**: **MySQL** Relational Database maintaining data integrity through foreign keys.

---

## 🛠️ Technology Stack

| Category | Technology |
|---|---|
| **Frontend** | HTML5, CSS3, Vanilla JavaScript |
| **Backend** | PHP (Procedural MySQLi) |
| **Database** | MySQL |
| **Server** | Apache (XAMPP / WAMP) |
| **Auth Provider** | Native PHP Sessions & `password_hash()` |
| **Package Manager**| *Not detected* (No dependencies) |
| **State Management**| PHP `$_SESSION` |
| **Routing** | Native File-based Routing |

---

## 📂 Project Structure

```text
CineBook/
├── admin/                 # Administrator Dashboard & Modules
│   ├── add_movie.php      # Form to add new movies
│   ├── scheduling.php     # Manage theater showtimes
│   ├── user.php           # User management
│   └── uploads/           # Stored movie poster images
├── assets/                # CSS, JS, and static assets
├── book_tickets.php       # Initialize booking flow
├── dbconnect.php          # Database connection & config
├── index.php              # Application entry point / Homepage
├── food_selection.php     # Snack ordering interface
├── movie_details.php      # Trailers, synopsis, cast & crew
├── payment.php            # Simulated checkout
├── seat_selection.php     # Interactive JS seat map
└── success.php            # Booking confirmation
```

---

## ⚙️ Prerequisites

To run this project locally, you must have:
- **XAMPP**, **WAMP**, or **LAMP** stack installed.
- **PHP 8.0+**
- **MySQL 5.7+** or MariaDB.

---

## 🚀 Installation & Setup

1. **Clone the Repository** (or extract the project folder):
   ```bash
   git clone <repository-url>
   ```
2. **Move to Server Directory**:
   Move the `CineBook` folder into your local server's root directory:
   - For XAMPP: `C:\xampp\htdocs\CineBook`
   - For WAMP: `C:\wamp64\www\CineBook`
   - For Linux/LAMP: `/var/www/html/CineBook`

3. **Start Server**:
   Open your XAMPP/WAMP Control Panel and start **Apache** and **MySQL**.

---

## 🗄️ Database Setup

The project requires a MySQL database named `cinebook_db`. 

1. Open **phpMyAdmin** (usually `http://localhost/phpmyadmin`).
2. Create a new database named `cinebook_db`.
3. *(If a `.sql` dump file is provided in the future, import it here)*. Otherwise, the system expects the following normalized tables:
   - `users`, `movies`, `showtimes`, `bookings`, `booking_food`, `movie_cast`, `movie_crew`, `movie_trailers`, `movie_reviews`.

---

## 🔐 Environment Variables

The project does not use a package manager or a `.env` parser like `vlucas/phpdotenv`. Database credentials are hardcoded in `dbconnect.php`. 

For best practices, if you were to extract these, your `.env.example` would look like this:

```env
DB_HOST=localhost
DB_NAME=cinebook_db
DB_USER=root
DB_PASSWORD=
TIMEZONE=Asia/Kolkata
```
*(Configure these directly inside `dbconnect.php` if your local MySQL setup differs from the XAMPP default).*

---

## 🏃 Running the Application

Once the files are in `htdocs` and the database is configured:
1. Open your web browser.
2. Navigate to: 
   ```text
   http://localhost/CineBook/
   ```
3. To access the Admin Panel, navigate to:
   ```text
   http://localhost/CineBook/admin/
   ```

---

## 📦 Missing / Not Detected Configurations

Based on the repository analysis, the following standard setups are **Not detected** (as this is a native PHP project):
- **Available Scripts (npm/yarn)**: *Not detected*
- **Production Build Steps**: *Not detected* (Deploy files directly to a standard PHP/cPanel web host).
- **API Integrations**: *Not detected* (No external REST/GraphQL APIs used).
- **Cloud Storage**: *Not detected* (Images are stored locally in `/admin/uploads/`).
- **Testing Frameworks**: *Not detected*
- **Linting / Formatting Tools**: *Not detected*
- **Docker / CI/CD**: *Not detected*

---

## 📸 Screenshots

*(Replace placeholder URLs with actual screenshot paths when available)*

| Homepage | Seat Selection | Admin Dashboard |
|:---:|:---:|:---:|
| ![Homepage](https://via.placeholder.com/400x250?text=Homepage+Screenshot) | ![Seats](https://via.placeholder.com/400x250?text=Seat+Selection) | ![Admin](https://via.placeholder.com/400x250?text=Admin+Dashboard) |

---

## 🐛 Troubleshooting

- **Database Connection Failed**: Ensure MySQL is running in XAMPP and the database name in `dbconnect.php` exactly matches the one you created in phpMyAdmin.
- **Images Not Uploading**: Ensure the `admin/uploads/` directory has write permissions (chmod 777 on Linux/Mac).
- **Blank Screen on Checkout**: Check your PHP error logs. Ensure PHP Sessions are enabled and `session_start()` is not blocked by whitespace before the `<?php` tag.

---

## 🤝 Contributing

Contributions are welcome! Please fork the repository and submit a pull request with your enhancements. 

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is open-source and available for educational and non-commercial use. 

<div align="center">
  <i>Developed with ❤️ for movie lovers.</i>
</div>

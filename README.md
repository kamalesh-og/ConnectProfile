# GUVI Project - `ConnectProfile` 🚀

A modern, full-stack user authentication and profile management system built with **HTML**, **CSS (Bootstrap)**, **JavaScript (jQuery AJAX)**, **PHP**, **MySQL**, **MongoDB**, and **Redis**. This project demonstrates a robust, scalable, and secure approach to user registration, login, and profile management, following best practices and separation of concerns.

---

## 📝 Problem Statement

Create a signup page where a user can register and a login page to log in with the details provided during registration. Successful login should redirect to a profile page which contains additional details such as age, date of birth, contact, etc. The user can update these details.

**Flow:**
```
Register ➡️ Login ➡️ Profile
```

---

## 🧭 README Contents

✨ **Features**  |  🛠️ **Tech Stack**  |  📁 **Project Structure**  |  🗄️ **Database Design**  |  🔐 **Session Management**  |  🧩 **How It Works**  |  🏗️ **Setup**  |  🖼️ **Screenshots**  |  🏅 **Best Practices**

---

## ✨ Features
- Responsive UI with Bootstrap 5
- User registration with validation (username, email, password)
- Secure password hashing
- Login with JWT-like session token (stored in browser localStorage)
- Profile page with additional details (age, dob, contact)
- Profile update functionality
- MySQL for authentication data
- MongoDB for user profile data
- Redis for session management
- AJAX-based communication (no form submissions)
- Clean separation of frontend and backend code

---

## 🛠️ Tech Stack
- **Frontend:**
  - HTML5, CSS3 (Bootstrap 5), JavaScript (jQuery)
- **Backend:**
  - PHP (with PDO for MySQL, MongoDB PHP Library, Redis PHP Extension)
- **Databases:**
  - MySQL (user authentication)
  - MongoDB (user profile details)
  - Redis (session storage)

---

## 📁 Project Structure
```
Guvi_Project/
│
├── index.html           # Landing page
├── register.html        # Registration page
├── login.html           # Login page
├── profile.html         # Profile page
│
├── css/
│   └── styles.css       # Custom & Bootstrap overrides
│
├── js/
│   ├── register.js      # Registration AJAX logic
│   ├── login.js         # Login AJAX logic
│   └── profile.js       # Profile AJAX logic
│
├── php/
│   ├── config.php       # DB & Redis config, session helpers
│   ├── register.php     # Registration endpoint
│   ├── login.php        # Login endpoint
│   └── profile.php      # Profile CRUD endpoint
│
├── database_setup.sql   # MySQL DB schema
└── README.md            # 📖 Project documentation
```

---

## 🗄️ Database Design

### 1. MySQL (user_auth)
- Stores registered users (username, email, hashed password, timestamps)
- Uses **prepared statements** for all queries (prevents SQL injection)
- Table: `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. MongoDB (user_profiles)
- Stores user profile details (age, dob, contact, etc.)
- Collection: `profiles`
- Linked to MySQL user by `user_id`

### 3. Redis
- Stores session tokens as `session:<token> => user_id`
- Used for fast, stateless session validation
- Sessions expire after 24 hours

---

## 🔐 Session Management
- **No PHP sessions used!**
- On login, a secure random token is generated and stored in Redis with the user's ID.
- The token is sent to the frontend and stored in `localStorage`.
- All authenticated requests send the token in the `Authorization` header.
- Backend validates the token with Redis for every request.
- Logout deletes the session from Redis and clears localStorage.

---

## 🧩 How It Works

```mermaid
flowchart TD
    A[User Registers] -->|AJAX POST| B[PHP Backend Validates & Stores in MySQL]
    B --> C[Profile Created in MongoDB]
    C --> D[Registration Success]
    D --> E[User Logs In]
    E -->|AJAX POST| F[PHP Backend Validates Credentials]
    F --> G[Session Token Generated & Stored in Redis]
    G --> H[Token Sent to Frontend & Saved in localStorage]
    H --> I[User Accesses Profile Page]
    I -->|AJAX GET with Token| J[PHP Backend Validates Token]
    J --> K[Fetches Data from MySQL & MongoDB]
    K --> L[Profile Data Returned as JSON]
    L --> M[User Can Update Profile (AJAX POST)]
    M --> K
    I --> N[User Logs Out]
    N --> O[Session Deleted from Redis & localStorage]
```

- **All communication is via AJAX.**
- **No PHP sessions are used; only browser localStorage and Redis for session state.**
- **Profile updates are instant and seamless.**

---

## 🏗️ Setup Instructions

1. **Clone the repository**
2. **Install dependencies:**
   - PHP 8.2+ with PDO, MongoDB, and Redis extensions
   - MySQL, MongoDB, and Redis servers running locally
   - Composer (for MongoDB PHP library)
3. **Import MySQL schema:**
   - Run `database_setup.sql` in your MySQL server
4. **Configure PHP:**
   - Update DB credentials in `php/config.php` if needed
5. **Start your local server:**
   - Use XAMPP, WAMP, or similar
6. **Access the app:**
   - Open `http://localhost/Guvi_Project/` in your browser

---

## 🖼️ Screenshots

> _Add screenshots of Register, Login, and Profile pages here for a visual overview._

---

## 🏅 Best Practices
- ✅ **Separation of Concerns:** HTML, CSS, JS, and PHP are in separate files for maintainability.
- ✅ **AJAX-Only Communication:** All backend interaction is via jQuery AJAX, ensuring a smooth user experience.
- ✅ **Responsive Design:** Built with Bootstrap for mobile and desktop compatibility.
- ✅ **Security:**
  - Passwords are securely hashed.
  - SQL injection is prevented using prepared statements.
  - Sessions are stateless and securely managed in Redis.
- ✅ **Scalability:**
  - Authentication and profile data are decoupled (MySQL & MongoDB).
  - Redis enables fast, scalable session management.
- ✅ **No PHP Sessions:** Only browser localStorage and Redis are used for session state, never PHP sessions.
- ✅ **Modern Stack:** Uses PDO, MongoDB PHP Library, and Redis PHP Extension for robust backend integration.

---

**Secure Access... Simplified Profiles.❤️**
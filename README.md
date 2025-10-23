# ModernShop - PHP E-commerce Platform

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen?style=flat-square)

A complete e-commerce website built with PHP, MySQL, and modern web technologies. This is a PHP conversion of the original React-based ModernShop, maintaining the same design and functionality.

## 🎯 Demo

> **Note**: This is a demonstration project showcasing a full-featured e-commerce platform built with PHP and MySQL.

### Live Demo Features
- Complete user authentication system
- Product catalog with search and filtering
- Shopping cart functionality
- Admin panel for product management
- Responsive design with glassmorphism UI
- RESTful API endpoints

## 🚀 Features

### Core Functionality
- **User Authentication** - Registration, login, logout with session management
- **Product Management** - Browse, search, filter, and view detailed product information
- **Shopping Cart** - Add/remove items, update quantities, persistent cart storage
- **Admin Panel** - Complete product CRUD operations for administrators
- **Responsive Design** - Mobile-first design with glassmorphism effects
- **Search & Filtering** - Advanced product search and category filtering

### Technical Features
- **Modern PHP** - Object-oriented PHP with PDO for database operations
- **Secure Authentication** - Password hashing, session management, CSRF protection
- **RESTful API** - JSON API endpoints for cart operations
- **Accessibility** - WCAG compliant with ARIA labels and keyboard navigation
- **Performance** - Optimized queries, lazy loading, and efficient caching

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Styling**: Custom CSS with CSS Grid and Flexbox
- **Icons**: Lucide React (SVG icons)
- **Fonts**: Inter & Space Grotesk from Google Fonts

## 📋 Prerequisites

- **XAMPP** (or similar local server environment)
- **PHP 7.4** or higher
- **MySQL 8.0** or higher
- **Web browser** with modern JavaScript support

## 🔧 Installation

### 1. Clone/Download the Project
```bash
# Place the project in your XAMPP htdocs directory
C:\xampp\htdocs\m-ecommerce-php\
```

### 2. Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the database schema:
   ```sql
   # Run the SQL commands from config/setup.sql
   # This will create the database and all necessary tables
   ```

### 3. Configuration
1. Update database credentials in `config/database.php` if needed:
   ```php
   private $host = 'localhost';
   private $db_name = 'm_ecommerce';
   private $username = 'root';
   private $password = '';
   ```

### 4. Access the Application
- **Main Site**: http://localhost/m-ecommerce-php/
- **Admin Panel**: http://localhost/m-ecommerce-php/admin/products.php

## 👤 Default Accounts

### Admin Account
- **Email**: admin@modernshop.com
- **Password**: admin123

### Regular User Account
- **Email**: user@example.com  
- **Password**: user123

*Note: Any email containing "admin" will automatically get admin privileges*

## 📁 Project Structure

```
m-ecommerce-php/
├── admin/                  # Admin panel pages
│   └── products.php       # Product management
├── api/                   # API endpoints
│   └── cart.php          # Cart operations API
├── components/            # Reusable PHP components
│   ├── header.php        # Site header
│   └── footer.php        # Site footer
├── config/               # Configuration files
│   ├── database.php      # Database connection
│   └── setup.sql         # Database schema
├── crm/                  # Customer Relationship Management
├── css/                  # Stylesheets
│   └── style.css         # Main stylesheet
├── includes/             # Core PHP classes
│   ├── auth.php          # Authentication system
│   └── functions.php     # Helper functions & classes
├── mobile-app/           # React Native mobile application
│   ├── src/              # Mobile app source code
│   ├── package.json      # Mobile app dependencies
│   └── README.md         # Mobile app setup guide
├── pages/                # Main application pages
│   ├── login.php         # User login
│   ├── register.php      # User registration
│   ├── products.php      # Product listing
│   ├── product-detail.php # Product details
│   ├── cart.php          # Shopping cart
│   ├── contact.php       # Contact form
│   └── logout.php        # Logout handler
└── index.php             # Homepage
```

### Mobile App Setup
The `mobile-app/` directory contains a React Native application. To set it up:

```bash
cd mobile-app
npm install
# For iOS
npx react-native run-ios
# For Android  
npx react-native run-android
```

> **Note**: Node modules are excluded from this repository. Run `npm install` to install dependencies.

## 🔐 Security Features

- **Password Hashing** - bcrypt for secure password storage
- **SQL Injection Prevention** - PDO prepared statements
- **XSS Protection** - Input sanitization and output escaping
- **Session Security** - Secure session management
- **Admin Protection** - Role-based access control

## 🎨 Design Features

- **Glassmorphism UI** - Modern glass-effect design
- **Gradient Backgrounds** - Dynamic gradient overlays
- **Responsive Layout** - Mobile-first responsive design
- **Smooth Animations** - CSS transitions and hover effects
- **Accessibility** - WCAG 2.1 AA compliant

## 📱 API Endpoints

### Cart API (`/api/cart.php`)
- **POST** - Add item to cart
- **PUT** - Update item quantity
- **DELETE** - Remove item from cart
- **GET** - Get cart contents

Example usage:
```javascript
// Add to cart
fetch('/m-ecommerce-php/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        product_id: 1,
        quantity: 2
    })
});
```

## 🚀 Deployment

### Local Development
1. Ensure XAMPP is running
2. Access via http://localhost/m-ecommerce-php/

### Production Deployment
1. Upload files to web server
2. Create MySQL database and import schema
3. Update `config/database.php` with production credentials
4. Set appropriate file permissions
5. Configure SSL certificate for HTTPS

## 🔧 Customization

### Adding New Products
1. Login as admin
2. Go to Admin Panel → Product Management
3. Click "Add New Product"
4. Fill in product details and save

### Modifying Design
- Edit `css/style.css` for styling changes
- Modify component files in `components/` for layout changes
- Update color scheme in CSS custom properties

### Adding New Features
1. Create new PHP files in appropriate directories
2. Add database tables if needed
3. Update navigation in `components/header.php`
4. Add API endpoints in `api/` if required

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check XAMPP MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database exists and tables are created

**Permission Denied**
- Check file permissions on web server
- Ensure PHP has write access to session directory

**Cart Not Working**
- Check browser console for JavaScript errors
- Verify user is logged in
- Check API endpoint responses

**Admin Panel Access Denied**
- Ensure user email contains "admin"
- Check `is_admin` field in database
- Verify session is active

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- **Email**: support@modernshop.com
- **Issues**: Create an issue on the repository
- **Documentation**: Check this README and code comments

## 🔄 Version History

- **v1.0.0** - Initial PHP conversion with full feature parity
- **v1.1.0** - Enhanced security and performance optimizations
- **v1.2.0** - Mobile responsiveness improvements

---

**ModernShop PHP** - A modern, secure, and feature-rich e-commerce platform built with PHP and MySQL.

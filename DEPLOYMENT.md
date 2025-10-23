# ModernShop - Live Hosting Deployment Guide

## 🚀 Pre-Deployment Checklist

### 1. Database Configuration
- Copy `.env.example` to `.env` and update with your live database credentials
- Or manually update `config/database.php` with your live database details

### 2. File Upload
Upload all files to your web hosting server:
- If hosting in root directory: Upload all files to `public_html/` or `www/`
- If hosting in subdirectory: Upload to `public_html/your-folder/`

### 3. Database Setup
Choose one of these methods:

#### Method A: Automatic Setup (Recommended)
1. Navigate to `https://yourdomain.com/auto_setup.php`
2. The script will automatically create database and admin user
3. Delete `auto_setup.php` after successful setup for security

#### Method B: Manual Setup
1. Create a MySQL database on your hosting panel
2. Import `config/setup.sql` via phpMyAdmin or database management tool
3. Update database credentials in `config/database.php`

### 4. File Permissions
Set appropriate file permissions:
```bash
# For most shared hosting
chmod 755 pages/ components/ includes/ config/ css/
chmod 644 *.php
chmod 644 css/*.css
```

### 5. Security Considerations
- Delete setup files after deployment: `auto_setup.php`, `test_login.php`, `setup_admin.php`
- Change default admin password after first login
- Ensure `.env` file is not publicly accessible (add to .htaccess if needed)

## 🔧 Configuration Updates

### Database Settings
The system auto-detects environment:
- **Local**: Uses localhost with root/empty password
- **Live**: Uses environment variables or manual configuration

### Base URL Configuration
The `getBaseUrl()` function automatically detects:
- **Local**: Returns `/m-ecommerce-php`
- **Live**: Returns empty string (for root directory) or custom path

### Currency
- All prices are now in Bangladeshi Taka (৳)
- Sample products have been updated with BDT pricing

## 📱 Testing After Deployment

1. **Homepage**: Verify site loads correctly
2. **Login**: Test with admin@modernshop.com / admin123
3. **Registration**: Create a test user account
4. **Products**: Check product listings and details
5. **Cart**: Test add to cart functionality
6. **Admin Panel**: Verify admin features work

## 🔐 Default Credentials

### Admin Account
- **Email**: admin@modernshop.com
- **Password**: admin123
- **Change this password immediately after first login!**

## 🐛 Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Verify database exists and user has proper permissions

2. **404 Errors on Pages**
   - Check if all files uploaded correctly
   - Verify file permissions

3. **CSS/Styling Issues**
   - Ensure `css/style.css` is uploaded
   - Check file permissions on CSS directory

4. **Session Issues**
   - Verify PHP sessions are enabled on hosting
   - Check if session directory is writable

### Support
- Check error logs in your hosting control panel
- Enable PHP error reporting for debugging (disable in production)

## 📁 File Structure
```
your-domain.com/
├── config/
│   ├── database.php (Updated for live hosting)
│   └── setup.sql
├── includes/
│   ├── auth.php (Fixed hardcoded URLs)
│   └── functions.php (Updated currency & base URL)
├── pages/
│   ├── login.php
│   ├── register.php (Centered alignment)
│   ├── account.php (New)
│   └── ...
├── css/
│   └── style.css (Added auth styles)
├── components/
├── admin/
├── api/
└── index.php
```

## ✅ Deployment Complete!

Your ModernShop e-commerce site is now ready for live hosting with:
- ✅ Auto-environment detection
- ✅ Bangladeshi Taka currency
- ✅ Centered register form
- ✅ Fixed hardcoded URLs
- ✅ Comprehensive account management
- ✅ Mobile-responsive design
- ✅ Security best practices

# M-Ecommerce Mobile App

A modern React Native mobile application for the M-Ecommerce platform, built with Expo for easy deployment to both Android and iOS app stores. Fully localized for Bangladesh market with Taka (৳) currency and 15% VAT.

## Features

- **User Authentication**: Login/Register with secure session management
- **Product Catalog**: Browse products by categories with search functionality
- **Shopping Cart**: Add/remove items, quantity management
- **Order Management**: Place orders and track order history
- **Support System**: Create and manage support tickets
- **Responsive Design**: Beautiful UI with modern design patterns

## Tech Stack

- **React Native** with **Expo** for cross-platform development
- **React Navigation** for navigation
- **Axios** for API communication
- **Expo SecureStore** for secure token storage
- **React Native Paper** for UI components
- **Expo Linear Gradient** for beautiful gradients

## Prerequisites

1. **Node.js** (v16 or higher)
2. **npm** or **yarn**
3. **Expo CLI**: `npm install -g @expo/cli`
4. **EAS CLI** (for building): `npm install -g eas-cli`

## Setup Instructions

### 1. Install Dependencies

```bash
cd mobile-app
npm install
```

### 2. Configure Backend URL

Edit `src/services/api.js` and update the `BASE_URL`:

```javascript
// For local development (emulator)
const BASE_URL = 'http://localhost/m-ecommerce-php';

// For physical device testing (replace with your computer's IP)
const BASE_URL = 'http://192.168.1.100/m-ecommerce-php';

// For production
const BASE_URL = 'https://yourdomain.com/m-ecommerce-php';
```

### 3. Start Development Server

```bash
npm start
```

This will open Expo DevTools in your browser. You can then:
- Scan QR code with Expo Go app (iOS/Android)
- Press 'a' to open Android emulator
- Press 'i' to open iOS simulator

## Building for Production

### 1. Setup EAS (Expo Application Services)

```bash
eas login
eas build:configure
```

### 2. Build APK for Testing

```bash
eas build --platform android --profile preview
```

### 3. Build for App Stores

```bash
# Android (AAB for Google Play Store)
eas build --platform android --profile production

# iOS (for App Store)
eas build --platform ios --profile production
```

### 4. Submit to App Stores

```bash
# Submit to Google Play Store
eas submit --platform android

# Submit to Apple App Store
eas submit --platform ios
```

## App Configuration

### App Icon & Splash Screen

Replace the following files with your custom assets:
- `assets/icon.png` (1024x1024)
- `assets/adaptive-icon.png` (1024x1024)
- `assets/splash.png` (1284x2778)
- `assets/favicon.png` (48x48)

### App Metadata

Update `app.json` with your app details:

```json
{
  "expo": {
    "name": "Your App Name",
    "slug": "your-app-slug",
    "version": "1.0.0",
    "ios": {
      "bundleIdentifier": "com.yourcompany.yourapp"
    },
    "android": {
      "package": "com.yourcompany.yourapp"
    }
  }
}
```

## API Endpoints

The mobile app communicates with these PHP API endpoints:

- `POST /api/auth.php` - Authentication (login/register/logout)
- `GET /api/products.php` - Product catalog and categories
- `GET/POST /api/cart.php` - Shopping cart management
- `GET/POST /api/orders.php` - Order management
- `GET/POST /api/support.php` - Support ticket system

## Testing

### On Physical Device

1. Install **Expo Go** app from App Store/Play Store
2. Make sure your phone and computer are on the same WiFi network
3. Update the `BASE_URL` in `api.js` to use your computer's IP address
4. Scan the QR code from Expo DevTools

### On Emulator

1. **Android**: Install Android Studio and setup AVD
2. **iOS**: Install Xcode (macOS only)
3. Run `npm start` and press 'a' for Android or 'i' for iOS

## Deployment Checklist

### Before Building for Production:

- [ ] Update app name, version, and metadata in `app.json`
- [ ] Replace default icons and splash screen
- [ ] Configure production API URL
- [ ] Test all features thoroughly
- [ ] Setup app store developer accounts
- [ ] Prepare app store listings (descriptions, screenshots, etc.)

### App Store Requirements:

**Google Play Store:**
- Developer account ($25 one-time fee)
- App bundle (AAB) file
- Privacy policy URL
- App screenshots and descriptions

**Apple App Store:**
- Apple Developer account ($99/year)
- IPA file built on macOS
- App Store Connect setup
- App review process (can take 1-7 days)

## Troubleshooting

### Common Issues:

1. **Network Error**: Check if backend server is running and accessible
2. **Build Errors**: Ensure all dependencies are installed correctly
3. **API Issues**: Verify API endpoints are working with tools like Postman
4. **Device Testing**: Make sure phone and computer are on same network

### Getting Help:

- Check Expo documentation: https://docs.expo.dev/
- React Native documentation: https://reactnative.dev/
- Stack Overflow for specific issues

## License

This project is licensed under the MIT License.

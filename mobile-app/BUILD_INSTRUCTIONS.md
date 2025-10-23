# Mobile App Build Instructions

## Quick Start Guide

### 1. Prerequisites Installation

```bash
# Install Node.js (if not already installed)
# Download from: https://nodejs.org/

# Install Expo CLI globally
npm install -g @expo/cli

# Install EAS CLI for building
npm install -g eas-cli
```

### 2. Setup Project

```bash
# Navigate to mobile app directory
cd C:\xampp\xampp\htdocs\m-ecommerce-php\mobile-app

# Install dependencies
npm install
```

### 3. Configure for Your Environment

Edit `src/services/api.js` and update the BASE_URL:

```javascript
// For testing on physical device, replace with your computer's IP
const BASE_URL = 'http://192.168.1.100/m-ecommerce-php';
```

To find your IP address:
- Windows: Open Command Prompt and run `ipconfig`
- Look for "IPv4 Address" under your WiFi adapter

### 4. Test the App

```bash
# Start development server
npm start

# This will open Expo DevTools in your browser
# Scan QR code with Expo Go app on your phone
# Or press 'a' for Android emulator, 'i' for iOS simulator
```

### 5. Build APK for Testing

```bash
# Login to Expo account (create free account at expo.dev)
eas login

# Configure build
eas build:configure

# Build APK for testing
eas build --platform android --profile preview
```

The build process will:
1. Upload your code to Expo servers
2. Build the APK in the cloud
3. Provide download link when complete (usually 5-15 minutes)

### 6. Install APK on Android Device

1. Download the APK from the link provided by EAS
2. Transfer to your Android device
3. Enable "Install from Unknown Sources" in Android settings
4. Install the APK

## Building for App Stores

### Google Play Store (Android)

```bash
# Build production AAB
eas build --platform android --profile production

# Submit to Google Play Store
eas submit --platform android
```

### Apple App Store (iOS)

```bash
# Build production IPA (requires macOS)
eas build --platform ios --profile production

# Submit to App Store
eas submit --platform ios
```

## Customization Before Building

### 1. App Identity

Update `app.json`:
```json
{
  "expo": {
    "name": "Your Store Name",
    "slug": "your-store-app",
    "version": "1.0.0",
    "android": {
      "package": "com.yourcompany.yourstore"
    },
    "ios": {
      "bundleIdentifier": "com.yourcompany.yourstore"
    }
  }
}
```

### 2. App Icon

Replace `assets/icon.png` with your 1024x1024 app icon

### 3. Splash Screen

Replace `assets/splash.png` with your splash screen image

### 4. Colors and Branding

Edit the color scheme in your screen files to match your brand colors.

## Troubleshooting

### Common Issues:

1. **"Metro bundler error"**: Delete `node_modules` and run `npm install` again
2. **"Network request failed"**: Check if your PHP server is running and accessible
3. **"Build failed"**: Check the build logs in EAS dashboard for specific errors

### Testing Checklist:

- [ ] App starts without crashes
- [ ] Login/Register works
- [ ] Products load correctly
- [ ] Cart functionality works
- [ ] Orders can be placed
- [ ] Support tickets can be created
- [ ] All navigation works smoothly

## Support

If you encounter issues:
1. Check the Expo documentation: https://docs.expo.dev/
2. Check build logs in EAS dashboard
3. Ensure your PHP backend APIs are working correctly

## Estimated Timeline

- **Setup & Testing**: 30 minutes
- **First APK Build**: 15-30 minutes (cloud build time)
- **App Store Submission**: 1-2 hours (preparation)
- **App Store Review**: 1-7 days (varies by platform)

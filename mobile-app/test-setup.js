#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🚀 M-Ecommerce Mobile App Setup Test\n');

// Check if required files exist
const requiredFiles = [
  'package.json',
  'app.json',
  'App.js',
  'src/services/api.js',
  'src/context/AuthContext.js',
  'src/screens/LoginScreen.js',
  'src/screens/HomeScreen.js',
  'src/screens/ProductsScreen.js',
  'src/screens/CartScreen.js',
  'src/screens/ProfileScreen.js'
];

let allFilesExist = true;

console.log('📁 Checking required files...');
requiredFiles.forEach(file => {
  const filePath = path.join(__dirname, file);
  if (fs.existsSync(filePath)) {
    console.log(`✅ ${file}`);
  } else {
    console.log(`❌ ${file} - MISSING`);
    allFilesExist = false;
  }
});

// Check package.json dependencies
console.log('\n📦 Checking package.json...');
try {
  const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
  console.log(`✅ App Name: ${packageJson.name}`);
  console.log(`✅ Version: ${packageJson.version}`);
  
  const requiredDeps = ['expo', 'react', 'react-native', '@react-navigation/native'];
  const missingDeps = requiredDeps.filter(dep => !packageJson.dependencies[dep]);
  
  if (missingDeps.length === 0) {
    console.log('✅ All required dependencies present');
  } else {
    console.log(`❌ Missing dependencies: ${missingDeps.join(', ')}`);
    allFilesExist = false;
  }
} catch (error) {
  console.log('❌ Error reading package.json');
  allFilesExist = false;
}

// Check app.json
console.log('\n⚙️ Checking app.json...');
try {
  const appJson = JSON.parse(fs.readFileSync('app.json', 'utf8'));
  console.log(`✅ App Name: ${appJson.expo.name}`);
  console.log(`✅ Slug: ${appJson.expo.slug}`);
  console.log(`✅ Version: ${appJson.expo.version}`);
} catch (error) {
  console.log('❌ Error reading app.json');
  allFilesExist = false;
}

// Final result
console.log('\n' + '='.repeat(50));
if (allFilesExist) {
  console.log('🎉 Setup Complete! Your mobile app is ready.');
  console.log('\nNext steps:');
  console.log('1. Run: npm install');
  console.log('2. Run: npm start');
  console.log('3. Scan QR code with Expo Go app');
  console.log('\nTo build APK:');
  console.log('1. Install EAS CLI: npm install -g eas-cli');
  console.log('2. Run: eas build --platform android --profile preview');
} else {
  console.log('❌ Setup incomplete. Please check missing files above.');
}
console.log('='.repeat(50));

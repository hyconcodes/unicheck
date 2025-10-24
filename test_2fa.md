# Testing Mandatory 2FA Implementation

## Test Users Available:
- Students: Use format `lastname.matric_no@bouesti.edu.ng` with password `password123`
- Lecturers: Use format `name@bouesti.edu.ng` with password `password123`
- Superadmin: Check DatabaseSeeder for admin user

## Test Scenarios:

### 1. New User Login (Without 2FA)
- Login with any user who doesn't have 2FA enabled
- Should be redirected to `/settings/two-factor` page
- Should see 2FA setup modal automatically opened
- Must complete 2FA setup before accessing dashboard

### 2. Existing User with 2FA
- Login with user who has 2FA enabled
- Should be redirected to 2FA challenge page
- Must enter 6-digit code to complete login

### 3. 2FA Setup Flow
- User without 2FA logs in
- Gets redirected to 2FA setup
- Scans QR code or enters manual key
- Enters verification code
- Gets redirected to dashboard after successful setup

## Expected Behavior:
- All users MUST set up 2FA before accessing the application
- No way to bypass 2FA requirement
- Smooth redirect flow from login → 2FA setup → dashboard
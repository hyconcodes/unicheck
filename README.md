# Smart Geolocation-Based Attendance and Verification System

A secure, web-based platform developed with Laravel that eliminates impersonation and remote attendance fraud among students through real-time geolocation validation and two-factor authentication.

## 🎯 Project Overview

The Smart Geolocation-Based Attendance and Verification System is designed to revolutionize attendance management in educational institutions. Unlike conventional systems that rely on manual entry or facial recognition alone, this platform integrates cutting-edge geolocation technology with robust security measures to ensure authentic, location-verified attendance tracking.

## 🔑 Key Features

### Core Functionality
- **Real-time Geolocation Validation**: Uses HTML5 Geolocation API to capture precise student coordinates
- **Distance-based Verification**: Validates attendance using the Haversine formula against predefined classroom locations
- **Radius-based Access Control**: Attendance only allowed within approved distance (e.g., 30 meters) from lecture venues
- **Two-Factor Authentication (2FA)**: Additional security layer using OTP verification via email or authenticator apps
- **Tamper-proof Records**: Comprehensive logging of timestamp, GPS coordinates, device information, and verification status

### Administrative Features
- **Real-time Dashboard**: Live attendance monitoring and analytics
- **Location Management**: Easy setup and management of classroom coordinates
- **User Role Management**: Granular permission system for administrators, lecturers, and students
- **Attendance Analytics**: Detailed reports and insights on attendance patterns
- **Map Visualization**: Interactive maps showing student locations and attendance zones

## 🛠 Technology Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Livewire, Alpine.js, Tailwind CSS
- **UI Components**: Flux UI Library
- **Authentication**: Laravel Fortify with 2FA support
- **Database**: MySQL/PostgreSQL
- **Permissions**: Spatie Laravel Permission
- **Geolocation**: HTML5 Geolocation API
- **Maps**: Integration-ready for Google Maps or Leaflet.js

## 📋 Current Implementation Status

### ✅ Completed Features

#### 1. User Authentication & Authorization
- Multi-role authentication system (Superadmin, Lecturer, Student)
- Laravel Fortify integration with 2FA support
- Comprehensive permission-based access control
- Role-specific dashboards and navigation

#### 2. Location Management System
- **Location Capture**: Real-time GPS coordinate capture using HTML5 Geolocation API
- **Location Storage**: Secure database storage of classroom coordinates with metadata
- **Permission-based Access**: Granular permissions for viewing, creating, editing, and deleting locations
- **User-friendly Interface**: Intuitive location management dashboard with Flux UI components
- **Error Handling**: Comprehensive error handling for geolocation failures and permission issues

#### 3. Database Architecture
- User management with role assignments
- Location storage with creator tracking and timestamps
- Permission system with role-based access control
- Proper indexing and relationships for optimal performance

#### 4. Security Implementation
- Permission middleware for route protection
- Method-level security checks in Livewire components
- UI-level permission controls with graceful degradation
- Secure coordinate validation and storage

#### 5. User Interface
- Responsive design with dark mode support
- Real-time feedback and toast notifications
- Interactive location capture with visual feedback
- Permission-aware UI that adapts based on user roles
- Accessibility features and proper ARIA labels

### 🚧 In Development
- Distance calculation using Haversine formula
- Attendance marking with geolocation validation
- OTP-based two-factor authentication for attendance
- Real-time attendance dashboard
- Map visualization for location and attendance data
- Attendance analytics and reporting

### 📅 Planned Features
- Mobile app integration
- Offline attendance capability with sync
- Advanced analytics and insights
- Integration with existing LMS platforms
- Automated attendance reports
- Student self-service portal

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL or PostgreSQL database

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd unicheck
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database Setup**
   - Configure your database credentials in `.env`
   - Run migrations and seeders:
   ```bash
   php artisan migrate
   php artisan db:seed --class=RolePermissionSeeder
   ```

6. **Build Assets**
   ```bash
   npm run build
   ```

7. **Start Development Server**
   ```bash
   php artisan serve
   ```

## 👥 User Roles & Permissions

### Superadmin
- Full system access and configuration
- User and role management
- System-wide location management
- Access to all analytics and reports

### Lecturer
- Classroom location setup and management
- Attendance monitoring for their classes
- Student attendance reports
- Location capture and verification

### Student
- View assigned classroom locations
- Mark attendance (when implemented)
- View personal attendance history
- Profile management

## 🔒 Security Features

- **Multi-layer Authentication**: Role-based access with 2FA support
- **Permission-based Authorization**: Granular control over system features
- **Secure Location Handling**: Encrypted coordinate storage and validation
- **Input Validation**: Comprehensive server-side validation for all inputs
- **CSRF Protection**: Built-in Laravel CSRF protection
- **SQL Injection Prevention**: Eloquent ORM with prepared statements

## 🌟 Benefits

- **Fraud Prevention**: Eliminates proxy attendance and location spoofing
- **Improved Accuracy**: Real-time, location-verified attendance records
- **Administrative Efficiency**: Automated attendance tracking reduces manual work
- **Transparency**: Comprehensive audit trails and tamper-proof records
- **Modern Approach**: Contactless, digital solution aligned with post-pandemic needs
- **Institutional Integrity**: Maintains academic standards through secure verification

## 📱 Browser Compatibility

- Chrome 50+
- Firefox 55+
- Safari 10+
- Edge 79+
- Mobile browsers with geolocation support

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions, please contact the development team or create an issue in the repository.

---

**Note**: This system is designed for educational institutions seeking to implement secure, location-based attendance tracking. The geolocation features require HTTPS in production environments and user permission for location access.
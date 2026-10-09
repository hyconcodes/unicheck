# CHAPTER FOUR

## SYSTEM IMPLEMENTATION AND RESULT

### 4.0 Introduction

This chapter presents the implementation of UniCheck, a geolocation based attendance and verification system for educational institutions. It describes how the application provides role specific functions for students, lecturers, and a superadministrator, and how attendance records are associated with classes and student accounts.

UniCheck is presented in this report as a native mobile application built with Laravel using NativePHP for Mobile. The mobile application is the user interface for students, lecturers, and the superadministrator. Laravel provides the application logic and data management behind the mobile interface. The chapter therefore focuses on the native app screens, role based workflows, backend, database, security controls, and implementation results.

The chapter discusses the implemented application components, backend and database integration, security controls, main user interfaces, implementation results, and limitations. Figure captions are provided as insertion points for original screenshots of the running UniCheck application. These captions are not substitutes for screenshots: each should be replaced with a genuine capture from the relevant project screen before the report is submitted.

### 4.1 System Implementation

UniCheck is implemented as a native mobile application built with Laravel using NativePHP for Mobile, supported by Laravel application logic and a shared persistence layer. The system manages users, departments, classes, locations, attendance, complaints, and access permissions. Students, lecturers, and the superadministrator use the mobile application according to their roles.

#### 4.1.1 Native Mobile Application

The native mobile application is the user facing interface to UniCheck. It is built with Laravel using NativePHP for Mobile and provides role specific functions for the intended mobile users. Students can view their dashboard and classes, open the attendance workflow, submit complaints, and access permitted account functions. Lecturers and superadministrators use the functions made available to their roles for class, attendance, student, department, location, and complaint management.

In the student attendance workflow, the mobile application obtains a device location for submission to the Laravel application. UniCheck calculates the distance between the submitted coordinates and the coordinates stored for the class using the Haversine formula. A class has a configured attendance radius, which defaults to 30 metres. The attendance interface communicates whether the captured position is within the permitted radius.

The attendance workflow also uses WebAuthn. An enrolled credential is associated with the student's account, and the application verifies an authentication assertion using a challenge and the stored public key before allowing the attendance action to complete. The resulting attendance record stores the class, student, name and matriculation number snapshot, captured coordinates, calculated distance, time marked, and whether the record was entered by a lecturer. The NativePHP app's location permission behavior, supported device types, and authenticator interaction should be evidenced by testing the running mobile build.

#### 4.1.2 Lecturer and Superadministrator Functions

The mobile application provides lecturers with functions for managing classes, viewing student lists, reviewing attendance, and entering attendance manually when needed. Class records identify the lecturer, department, level, coordinates, permitted radius, schedule, status, and whether attendance is open. The lecturer can manually enter a student's matriculation number for a class within the lecturer's access and the system records that the attendance was lecturer marked.

The superadministrator functions in the mobile application provide management for accounts, departments, locations, classes, student level promotion, complaints, and roles and permissions. Complaint records contain a status and priority, and can store an administrator response and the identity of the responding user. A lecturer can also generate an attendance report as a PDF from the class management functions.

Access to protected functions is controlled by authentication, role middleware, and permission middleware in the Laravel application. The screens and workflows shown in this chapter should be captured from the running NativePHP mobile application and should reflect the functions available to each user role.

#### 4.1.3 Backend and Database Integration

The project uses Laravel 12 and PHP 8.2 or later. Laravel supplies the application logic and data management used by the NativePHP mobile application. Eloquent models represent the main domain records, including users, departments, classes, attendance records, locations, complaints, and WebAuthn credentials.

The database configuration is environment driven. SQLite is the configured default, while the Laravel configuration also defines MySQL, MariaDB, PostgreSQL, and SQL Server connections. This report therefore does not claim that a particular database engine is used in production.

The principal database entities and their functions are as follows:

1. Users store account, profile, matriculation, department, level, and two factor authentication data.
2. Departments organize users and classes.
3. Classes store lecturer and department references, class level, schedule, location coordinates, radius, status, and attendance availability.
4. Class attendances connect a class with a student and store the attendance details, captured position, distance, and marking time. A unique constraint prevents a second attendance record for the same student and class.
5. Locations store reusable location coordinates, descriptive information, type, and the user who created the location.
6. Complaints store the submitting student, subject, message, priority, status, and any response.
7. WebAuthn credentials store the credential identifier, public key, authenticator metadata, signature counter, and last use time for a user.
8. Roles and permissions, together with their pivot tables, store role based authorization assignments.

The database also contains framework managed tables for password reset tokens, sessions, cache, queue jobs, failed jobs, and migration tracking. These support Laravel infrastructure and are distinct from UniCheck's attendance domain records.

#### 4.1.4 Security Features

The system applies multiple security controls:

1. Authentication is provided through the Laravel application and Fortify integration.
2. Fortify two factor authentication is enabled, with confirmation and password confirmation options configured.
3. Route middleware restricts protected functions by authentication status, role, and permission.
4. User passwords are stored using Laravel's hashed password cast rather than as plain text.
5. WebAuthn registration and authentication use a challenge based verification flow and store public credential material rather than biometric fingerprint images.
6. Attendance logic checks the student's identity fields, validates required coordinates, checks the location radius, and rejects duplicate attendance for a class and student.
7. Attendance and biometric outcomes are written to an application log channel.
8. Laravel request middleware provides standard protections, including CSRF protection where applicable.

Geolocation and WebAuthn improve attendance verification, but they do not establish that all forms of GPS manipulation are impossible. Location accuracy depends on the device, permission settings, and surrounding conditions. The implementation should therefore be described as location and authenticator based verification, not as a guarantee that attendance fraud has been eliminated.

### 4.2 System Modules and Interfaces

#### 4.2.1 Student Modules

Authentication and Dashboard Module

Students use the mobile application's authentication screens to sign in. Once authenticated, the student dashboard and navigation provide access to functions permitted by the account's role and permissions.

Class Module

The class interface enables students to view classes relevant to their academic context and open a class attendance screen. Class records include a department, level, lecturer, schedule, status, coordinates, radius, and attendance open state.

Geolocation and Attendance Module

The attendance interface obtains the device location and calculates the distance from the class coordinates. If the student is within the configured radius, the interface can proceed to biometric verification and attendance submission. The application records the submitted coordinates and calculated distance with the attendance time. The database prevents duplicate attendance for one student in one class.

Biometric Credential Module

Students can register and use WebAuthn passkeys through a supported device authenticator. Registration requests a discoverable credential so a student can start passkey sign-in from the login page without entering an email or password. Passkeys are held by the authenticator or passkey provider; a credential available on one device is not automatically available on another unless the provider syncs it. After signing in with a password on an unsynced device, a student can register an additional passkey from the attendance biometric step. The login assertion identifies the account through the credential returned by the authenticator, and the server verifies its challenge, signature, user handle, and user verification before signing in. Credentials registered before discoverable credentials were required may need to be registered again as passkeys for identifier-free sign-in. The application stores credential data, not a raw fingerprint image.

Complaint and Profile Modules

Students can submit and view complaints subject to their permissions. The profile and settings screens provide the account functions available to the user, including profile, password, appearance, and two factor authentication settings where enabled.

#### 4.2.2 Lecturer and Superadministrator Modules

Class and Location Management Module

Authorized users can manage classes and location records through the relevant administration screens. Class location coordinates and radius support the distance check used in student attendance. The separate location records include coordinates, building or block name, location type, description, and creator.

Lecturer Attendance Module

Lecturers can review class attendance and use manual attendance entry for a student identified by matriculation number. Manually entered records are flagged as lecturer marked. The implementation stores zero coordinate and distance values for manual attendance; those values should not be interpreted as a GPS verified position.

Student and Department Management Module

The lecturer interface includes student list and class related student views according to permissions. The superadministrator tools support account and department administration, as well as student level promotion.

Complaints Module

Students submit complaints and authorized superadministrators manage them. The stored status and priority support the complaint workflow, while response details record the administrative reply and responder where present.

Role and Permission Module

The system uses Spatie Laravel Permission to associate users with roles and permissions. The seeded roles are superadmin, lecturer, and student. Permissions are assigned to these roles and are used by protected routes to limit access.

Attendance Reporting Module

The lecturer class management interface can generate a PDF attendance report for an authorized class. The report is produced from class and attendance data through the installed DomPDF integration.

#### 4.2.3 User Interfaces

This section documents only the native mobile application interface, built with Laravel using NativePHP for Mobile. All figures in this chapter should be screenshots captured from the installed mobile application running on its supported device or emulator.

The following original screenshots are recommended for this chapter. Replace each insertion note with a genuine screenshot captured from the running project. Screenshots should use test accounts and non sensitive sample information. Do not include real student names, matriculation numbers, or precise personal location data in a report shared beyond its intended audience.

1. **Figure 4.1: UniCheck Native Mobile Login Screen.** Capture the login screen in the installed app. If showing two factor authentication, use a separate capture of the challenge screen and do not expose a real one time code.
2. **Figure 4.2: Native Mobile Student Dashboard.** Capture the student dashboard with sample or anonymized information.
3. **Figure 4.3: Native Mobile Class List and Class Details.** Capture the class list or a class details screen using test data.
4. **Figure 4.4: Native Mobile Attendance and Location Verification.** Capture the attendance screen with its location status visible. Use an authorized test location and avoid exposing a real student's coordinates.
5. **Figure 4.5: Native Mobile WebAuthn Verification and Attendance Confirmation.** Capture the app's verification or confirmation state. Do not include device secrets or authenticator enrollment material.
6. **Figure 4.6: Native Mobile Lecturer Class and Manual Attendance.** Capture a lecturer's class view or manual attendance screen using a test student record.
7. **Figure 4.7: Native Mobile Superadministrator Management.** Capture a representative administration screen, such as department, account, or role management, if that screen is available in the mobile app.
8. **Figure 4.8: Native Mobile Attendance Report.** Capture the attendance report or PDF viewing and export flow available in the mobile app, using anonymized or test attendance data.

### 4.3 Results of the Implementation

The implementation provides a running NativePHP mobile application for UniCheck, supported by Laravel application logic and database records. The mobile application is the sole user interface documented in this chapter. Its workflows cover role protected access, student classes and attendance, lecturer attendance management, superadministrator functions, complaints, and attendance reporting, as available to each role in the running build.

The student attendance path captures device coordinates, calculates distance from the class location, requires successful WebAuthn verification in the attendance flow, and stores an attendance record with the measured location data. The lecturer path supports manual attendance entry and labels the record accordingly. The class and attendance schema constrains each student to one attendance record per class.

The superadministrator functions provide account, department, class, location, permission, student level, and complaint management screens. The lecturer functions provide class management, student list access, manual attendance, and attendance reporting. Student and class records are connected through database relationships, allowing the application to retrieve attendance by class or student.

Automated tests are present in the repository, primarily for authentication, account settings, and dashboard access. The available test set does not establish measured location accuracy, fraud reduction, production scale performance, or user satisfaction. Native mobile operation should be supported in the final report with test evidence from the running NativePHP build. No numerical improvement, deployment outcome, or user survey result is claimed in this chapter unless supported by separately collected project evidence.

The Laravel application logic and database provide the foundation for the running NativePHP mobile application. Screenshots of the installed app should be inserted to provide visual evidence of its actual interface. Claims about mobile device behavior should be supported by testing the installed build.

### 4.4 Discussion of Findings

The implemented functions address several weaknesses associated with paper based or manually consolidated attendance records. Student submitted attendance can be associated with a particular class, time, location measurement, and WebAuthn verification. Role and permission controls separate student tasks from lecturer and superadministrator tasks. Manual lecturer attendance remains available as an operational alternative and is distinguishable in the attendance record.

#### 4.4.1 Alignment with Project Objectives

The implementation can be related to the following project objectives:

1. To provide role specific access for students, lecturers, and superadministrators. The application implements role based dashboards and route permissions for the three roles.
2. To support class and location management. The application stores class schedules, locations, coordinates, and attendance radii, and provides management screens for relevant authorized users.
3. To support location based student attendance. The mobile attendance interface obtains device location and the application computes distance from the class position before accepting attendance.
4. To strengthen identity verification during attendance. The student attendance workflow uses WebAuthn credential registration and authentication.
5. To provide traceable attendance and administrative records. Attendance data is linked to a student and class, and the system includes attendance logging, complaint response fields, and PDF attendance reporting.

These points describe implemented features visible in the current repository. They should be checked against the formally approved objectives in the earlier chapters of the student's project report before final submission.

#### 4.4.2 Comparison with the Manual Method

A paper based attendance process requires records to be collected and reviewed manually. It may be difficult to retrieve class history consistently, and the record itself does not inherently capture the location and verification context of a student's attendance.

UniCheck provides electronic attendance records linked to students and classes. For the student workflow, location coordinates and a calculated distance are stored with a timestamp, and WebAuthn verification is part of the attendance process. Lecturer entered attendance is also recorded, but flagged separately and assigned placeholder location values. These differences improve the traceability of records, but the repository does not contain a controlled field study or before and after measurements. Claims about the amount of time saved or fraud prevented therefore require separate evaluation.

#### 4.4.3 Limitations Observed

The following limitations should be considered when interpreting the implementation:

1. The system is documented here through its running NativePHP mobile application. The mobile build's supported platform coverage and available functions should be documented using its release configuration and test results.
2. Mobile attendance depends on location permission, an available location signal, and network access to the Laravel application. Permission behavior should be verified on each target operating system.
3. Device geolocation can vary in accuracy and may be affected by device settings or location spoofing. A radius check alone cannot prove a device's physical position.
4. WebAuthn requires a compatible device authenticator and a completed credential ceremony on the user's device.
5. Lecturer entered attendance stores zero values for coordinates and distance. Reports should use the lecturer marked flag to distinguish this data from a position checked through the student workflow.
6. The database engine is selected by environment configuration. The source configuration defaults to SQLite and also defines other supported Laravel drivers. A production database choice and operational deployment configuration cannot be inferred from source alone.
7. The repository does not provide field study results, load test measurements, GPS accuracy measurements, or survey evidence for user satisfaction.
8. The current implementation does not establish offline attendance synchronization or an integrated map visualization feature.

#### 4.4.4 Implications of the Findings

The system demonstrates how location checks and WebAuthn can be combined with role based attendance management in a Laravel application. The stored class and attendance relationships provide a foundation for reviewing participation by class and student. Logging, complaints, and report generation provide supporting administrative functions.

For institutional use, the system should be deployed over HTTPS, configured with an appropriate production database and mail transport, and tested with representative devices and locations. Students should be informed about the collection and use of location data. Attendance policies should also define how lecturers handle legitimate exceptions, device incompatibility, inaccurate location readings, and manual attendance.

The NativePHP mobile application should be tested across its intended operating systems, device types, permission states, and network conditions. The report should include reproducible build and installation details and screenshots from the running application. Offline attendance or map visualization should be treated as future enhancements unless separately implemented and tested.

### 4.5 Summary

This chapter described UniCheck through its native mobile application, built with Laravel and NativePHP for Mobile. Student workflows include classes, location checked attendance, WebAuthn verification, complaints, and account settings. Lecturer workflows include class management, student views, manual attendance, and attendance reports. Superadministrator workflows include account, department, location, class, role, permission, complaint, and level management.

The system stores attendance with its class and student references, time, location coordinates, distance, and marking method. Its security includes authentication, two factor authentication support, role and permission middleware, hashed passwords, WebAuthn verification, and attendance event logging. The chapter has identified screenshot insertion points for the native mobile interface. Performance measurements and field evaluation remain necessary to establish operational outcomes.

# CHAPTER FIVE

## SUMMARY, CONCLUSION AND RECOMMENDATIONS

### 5.1 Summary of the Study

This project focused on UniCheck, a geolocation based attendance and verification system for educational use. The application was developed to support attendance workflows for students and the administrative tasks performed by lecturers and superadministrators.

The implemented application uses Laravel 12 with PHP 8.2 or later and NativePHP for Mobile for its native mobile interface. Laravel Fortify provides authentication and two factor authentication support, while Spatie Laravel Permission provides role and permission management. WebAuthn is used for credential based identity verification in the student attendance workflow.

The application supports class and department records, class location and radius settings, student attendance, lecturer entered attendance, complaint handling, location management, and attendance PDF export. The relational schema stores the users, departments, classes, attendance records, locations, complaints, WebAuthn credentials, roles, and permissions needed for these functions. The database driver is determined by environment configuration, with SQLite as the default configuration and other database drivers also available.

The review of the implementation confirms that the principal workflows are represented in the source code and database migrations. The repository's automated tests cover areas such as authentication, settings, and dashboard access, but do not provide empirical results for attendance accuracy, fraud reduction, user satisfaction, or large scale performance. The application should therefore be evaluated in its target institutional environment before such outcomes are claimed.

UniCheck is presented as a native mobile application built with Laravel using NativePHP for Mobile. The Laravel application provides the business logic and data management, while screenshots and test records from the running mobile app document its runtime behavior.

### 5.2 Recommendations

Based on the implementation and its limitations, the following recommendations are made:

1. **Conduct functional and field testing.** Test student and lecturer workflows on representative mobile devices and class locations. Record actual results for location accuracy, WebAuthn completion, attendance submission, and manual attendance handling.
2. **Evaluate location verification controls.** Test how device accuracy, weak signals, and location spoofing affect attendance decisions. Define appropriate operational checks and lecturer review procedures.
3. **Protect location data.** Provide a clear privacy notice, restrict access to attendance coordinates, retain the data only for an approved period, and avoid publishing personally identifiable attendance information in reports.
4. **Complete production configuration.** Select and document a production database, configure HTTPS, backups, mail delivery, queue processing, logging retention, and access to operational secrets before deployment.
5. **Expand automated testing.** Add tests for the core attendance flows, distance boundary conditions, duplicate attendance, lecturer marked attendance, complaint responses, WebAuthn failures, and role and permission enforcement.
6. **Perform usability evaluation.** Gather structured feedback from students, lecturers, and administrators using the running system. Report participant numbers, methods, and findings rather than making unsupported claims about satisfaction or efficiency.
7. **Improve reporting and exception handling.** Ensure PDF reports distinguish GPS verified attendance from lecturer entered records, and document the process for resolving rejected or inaccurate location checks.
8. **Document and test the NativePHP mobile application.** Record the supported platforms, build and installation procedures, permissions, device integrations, and tested workflows. Include screenshots and test results from the running application.
9. **Review mobile accessibility and usability.** Evaluate application navigation, text scaling, assistive technology behavior, and platform conventions on the supported mobile devices.

### 5.3 Conclusion

UniCheck is presented as a native mobile application built with Laravel using NativePHP for Mobile. The system combines role based access, class and location records, geolocation, distance calculation, WebAuthn credential verification, manual lecturer attendance, complaints, and attendance reporting. These functions provide a structured electronic alternative to attendance records maintained only through paper or informal manual processes.

The implementation provides a foundation for institutional attendance management, but source code alone does not establish a successful field deployment or quantify reductions in fraud, processing time, or administrative workload. Such results require functional testing, deployment evidence, and evaluation with the intended users.

The current product should be presented through its NativePHP mobile application, with Laravel providing the application logic and data management. Further platform testing, deployment hardening, and user evaluation will help establish its reliability and suitability for institutional use.

# APPENDICES

## APPENDIX A: SELECTED SOURCE CODE

This appendix presents three short excerpts from the Laravel application logic that supports the UniCheck mobile application. The excerpts are taken from the current project source and illustrate attendance validation, WebAuthn verification, and role and permission based access control.

### A.1 Location Based Attendance Validation

The attendance module calculates the distance between the student's submitted coordinates and the class location. It rejects attempts outside the allowed radius and prevents duplicate attendance for the same student and class.

**Source:** `app/Models/ClassAttendance.php`

```php
$distance = ClassModel::calculateDistance(
    $class->latitude,
    $class->longitude,
    $latitude,
    $longitude
);

if ($distance > $class->radius) {
    throw new \Exception('You are too far from the class location.');
}

$existingAttendance = self::where('class_id', $class->id)
    ->where('student_id', $student->id)
    ->first();

if ($existingAttendance) {
    throw new \Exception('You have already marked attendance for this class.');
}
```

### A.2 WebAuthn Credential Verification

The WebAuthn module verifies the authenticator response against the user's registered public key and the challenge stored for the authentication request.

**Source:** `app/Services/WebauthnService.php`

```php
$this->webAuthn->processGet(
    $clientDataJSON,
    $authenticatorData,
    $signature,
    $credential->public_key,
    $challengeBinary,
    $credential->signature_count,
    true,
    true
);

$signatureCounter = $this->webAuthn->getSignatureCounter();
$credential->markUsed($signatureCounter);
```

Students whose passkey is unavailable on their current device can request a short-lived verification code at the login screen. The code is emailed to any valid address entered, regardless of whether an account is found; passkey enrollment still requires an existing student account. A student can use the code to verify an unverified email on their account before enrolling. Student accounts may use either the existing institution email format or Gmail; Gmail registrants enter their matric number separately. The email code authorizes enrollment only; subsequent sign-ins use the passkey, without email/password fallback. Enrollment codes are hashed in the session, expire after ten minutes, and are protected by per-email, per-IP, and verification-attempt rate limits. The application blocks log-only and in-memory mail transports for these codes; a real SMTP or API mail transport must be configured in the deployment environment for delivery.

### A.3 Role and Permission Based Access

The route definitions restrict lecturer class management to authenticated users with the lecturer role and the required class viewing or management permission.

**Source:** `routes/web.php`

```php
Route::middleware(['role:lecturer'])->group(function () {
    Volt::route('lecturer/classes', 'lecturer.class-manager')
        ->middleware('permission:can.view.classes|can.manage.classes')
        ->name('lecturer.classes');
});
```

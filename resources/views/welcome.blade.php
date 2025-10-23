<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} - Smart Geolocation Attendance System</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Tailwind CSS -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Custom Styles -->
        <style>
            * {
                font-family: 'Inter', sans-serif;
            }
            
            .gradient-bg {
                background: linear-gradient(135deg, #FFD700 0%, #10B981 100%);
            }
            
            .gradient-text {
                background: linear-gradient(135deg, #FFD700 0%, #10B981 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .hero-bg {
                background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            }
            
            .card-hover {
                transition: all 0.3s ease;
            }
            
            .card-hover:hover {
                transform: translateY(-8px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #FFD700 0%, #10B981 100%);
                transition: all 0.3s ease;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            
            .animate-fade-up {
                animation: fadeUp 0.8s ease-out;
            }
            
            @keyframes fadeUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </head>
    <body class="bg-white">
        <!-- Header / Navbar -->
        <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg gradient-bg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text">{{ config('app.name') }}</span>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#home" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Home</a>
                        <a href="#features" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Features</a>
                        <a href="#how-it-works" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">How It Works</a>
                        <a href="#about" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">About</a>
                        
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Login</a>
                            <a href="{{ route('register') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Register</a>
                        @endauth
                        
                        <button class="btn-primary text-white px-6 py-2 rounded-xl font-semibold">
                            Mark Attendance
                        </button>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button class="text-gray-700 hover:text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="home" class="hero-bg py-20 lg:py-32">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="animate-fade-up">
                        <h1 class="text-4xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                            <span class="gradient-text">Verify. Locate. Attend</span><br>
                            — Smarter Attendance Starts Here.
                        </h1>
                        <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                            Track and verify student presence in real time using GPS and secure 2FA authentication. 
                            Say goodbye to proxy attendance and hello to reliable verification.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg">
                                Get Started
                            </button>
                            <button class="border-2 border-blue-600 text-blue-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-blue-50 transition-colors">
                                Learn More
                            </button>
                        </div>
                    </div>
                    
                    <!-- Hero Illustration -->
                    <div class="relative">
                        <div class="bg-gradient-to-br from-blue-100 to-green-100 rounded-3xl p-8 relative overflow-hidden">
                            <svg class="w-full h-80" viewBox="0 0 400 300" fill="none">
                                <!-- Background Map Pattern -->
                                <defs>
                                    <pattern id="map-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                        <circle cx="20" cy="20" r="1" fill="#E5E7EB" opacity="0.5"/>
                                    </pattern>
                                </defs>
                                <rect width="400" height="300" fill="url(#map-pattern)"/>
                                
                                <!-- Location Radar -->
                                <circle cx="200" cy="150" r="80" fill="none" stroke="#10B981" stroke-width="2" opacity="0.3"/>
                                <circle cx="200" cy="150" r="60" fill="none" stroke="#10B981" stroke-width="2" opacity="0.5"/>
                                <circle cx="200" cy="150" r="40" fill="none" stroke="#10B981" stroke-width="2" opacity="0.7"/>
                                
                                <!-- Student with Phone -->
                                <g transform="translate(170, 120)">
                                    <!-- Phone -->
                                    <rect x="20" y="10" width="20" height="35" rx="4" fill="#FFD700"/>
                                    <rect x="22" y="12" width="16" height="25" rx="2" fill="#60A5FA"/>
                                    <circle cx="30" cy="40" r="2" fill="#FFD700"/>
                                    
                                    <!-- Person -->
                                    <circle cx="15" cy="15" r="8" fill="#F3F4F6"/>
                                    <rect x="7" y="23" width="16" height="20" rx="8" fill="#F3F4F6"/>
                                </g>
                                
                                <!-- Location Pin -->
                                <g transform="translate(190, 130)">
                                    <path d="M10 0C4.48 0 0 4.48 0 10C0 17.5 10 30 10 30S20 17.5 20 10C20 4.48 15.52 0 10 0Z" fill="#EF4444"/>
                                    <circle cx="10" cy="10" r="4" fill="white"/>
                                </g>
                                
                                <!-- Security Shield -->
                                <g transform="translate(220, 100)">
                                    <path d="M10 0L18 4V12C18 16 14 20 10 20C6 20 2 16 2 12V4L10 0Z" fill="#10B981"/>
                                    <path d="M7 10L9 12L13 8" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Highlights Section -->
        <section id="features" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                        Why Choose <span class="gradient-text">{{ config('app.name') }}</span>?
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        Advanced technology meets academic integrity with our comprehensive attendance verification system.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-2xl p-8 card-hover">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">📍 Geolocation Verification</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Ensures students are within the classroom area using precise GPS coordinates and geofencing technology.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white rounded-2xl p-8 card-hover">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">🔐 2FA Security Layer</h3>
                        <p class="text-gray-600 leading-relaxed">
                            OTP or Authenticator-based verification for identity protection, preventing unauthorized access and proxy attendance.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white rounded-2xl p-8 card-hover">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">🧭 Real-time Attendance Logs</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Admin dashboard showing student presence and map view with comprehensive analytics and reporting features.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                        How It <span class="gradient-text">Works</span>
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        Simple, secure, and reliable attendance marking in just three easy steps.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="text-2xl font-bold text-white">1</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">🔑 Login Securely</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Access your student account with secure authentication and two-factor verification enabled.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="text-2xl font-bold text-white">2</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">📍 Verify Location</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Allow GPS location access to verify your presence within the designated classroom area.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="text-2xl font-bold text-white">3</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">✅ Confirm Attendance</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Complete attendance marking via OTP or Authenticator app for final verification.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6">
                            Preventing <span class="gradient-text">Proxy Attendance</span>
                        </h2>
                        <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                            Our platform revolutionizes academic integrity by combining cutting-edge geolocation technology 
                            with robust security measures. We eliminate proxy attendance and remote spoofing through 
                            multi-layered verification systems.
                        </p>
                        <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                            With real-time GPS tracking, two-factor authentication, and intelligent fraud detection, 
                            {{ config('app.name') }} ensures that only physically present students can mark their attendance.
                        </p>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">99.9% Accuracy Rate</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Real-time Verification</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- University Class Illustration -->
                    <div class="relative">
                        <div class="bg-gradient-to-br from-blue-50 to-green-50 rounded-3xl p-8 relative overflow-hidden">
                            <svg class="w-full h-80" viewBox="0 0 400 300" fill="none">
                                <!-- Classroom Background -->
                                <rect width="400" height="300" fill="#F8FAFC"/>
                                
                                <!-- Desks -->
                                <rect x="50" y="180" width="60" height="40" rx="4" fill="#E2E8F0"/>
                                <rect x="130" y="180" width="60" height="40" rx="4" fill="#E2E8F0"/>
                                <rect x="210" y="180" width="60" height="40" rx="4" fill="#E2E8F0"/>
                                <rect x="290" y="180" width="60" height="40" rx="4" fill="#E2E8F0"/>
                                
                                <!-- Students -->
                                <g transform="translate(70, 160)">
                                    <circle cx="10" cy="10" r="8" fill="#F3F4F6"/>
                                    <rect x="2" y="18" width="16" height="20" rx="8" fill="#F3F4F6"/>
                                </g>
                                <g transform="translate(150, 160)">
                                    <circle cx="10" cy="10" r="8" fill="#F3F4F6"/>
                                    <rect x="2" y="18" width="16" height="20" rx="8" fill="#F3F4F6"/>
                                </g>
                                <g transform="translate(230, 160)">
                                    <circle cx="10" cy="10" r="8" fill="#F3F4F6"/>
                                    <rect x="2" y="18" width="16" height="20" rx="8" fill="#F3F4F6"/>
                                </g>
                                
                                <!-- Location Overlay -->
                                <circle cx="200" cy="150" r="100" fill="none" stroke="#10B981" stroke-width="3" opacity="0.3" stroke-dasharray="10,5"/>
                                
                                <!-- Location Pin -->
                                <g transform="translate(190, 130)">
                                    <path d="M10 0C4.48 0 0 4.48 0 10C0 17.5 10 30 10 30S20 17.5 20 10C20 4.48 15.52 0 10 0Z" fill="#10B981"/>
                                    <circle cx="10" cy="10" r="4" fill="white"/>
                                </g>
                                
                                <!-- Checkmarks -->
                                <g transform="translate(85, 145)">
                                    <circle cx="0" cy="0" r="8" fill="#10B981"/>
                                    <path d="M-3 0L-1 2L3 -2" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                                <g transform="translate(165, 145)">
                                    <circle cx="0" cy="0" r="8" fill="#10B981"/>
                                    <path d="M-3 0L-1 2L3 -2" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                                <g transform="translate(245, 145)">
                                    <circle cx="0" cy="0" r="8" fill="#10B981"/>
                                    <path d="M-3 0L-1 2L3 -2" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8">
                    <!-- Logo and Description -->
                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg gradient-bg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                </svg>
                            </div>
                            <span class="text-xl font-bold">{{ config('app.name') }}</span>
                        </div>
                        <p class="text-gray-400 leading-relaxed max-w-md">
                            Smart Geolocation Attendance System - Revolutionizing academic integrity through advanced 
                            technology and secure verification methods.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="#home" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                            <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                            <li><a href="#how-it-works" class="text-gray-400 hover:text-white transition-colors">How It Works</a></li>
                            <li><a href="#about" class="text-gray-400 hover:text-white transition-colors">About</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h3 class="font-semibold mb-4">Legal</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Use</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                    <p class="text-gray-400">
                        Copyright © 2025 – {{ config('app.name') }} Smart Geolocation Attendance System. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

        <!-- Smooth Scrolling Script -->
        <script>
            // Smooth scrolling for navigation links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add animation on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-up');
                    }
                });
            }, observerOptions);

            // Observe all sections
            document.querySelectorAll('section').forEach(section => {
                observer.observe(section);
            });
        </script>
    </body>
</html>

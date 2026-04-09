<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div style="display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh;">
            <!-- Left Side - Branding -->
            <div style="display: none; background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #312e81 100%); flex-direction: column; justify-content: center; align-items: center; padding: 2rem; position: relative; overflow: hidden;" class="hidden lg:flex">
                <!-- Decorative elements -->
                <div style="position: absolute; top: -200px; right: -200px; width: 400px; height: 400px; background: #3b82f6; border-radius: 50%; opacity: 0.1;"></div>
                <div style="position: absolute; bottom: -200px; left: -200px; width: 400px; height: 400px; background: #6366f1; border-radius: 50%; opacity: 0.1;"></div>
                
                <!-- Content -->
                < style="position: relative; z-index: 10; text-align: center; max-width: 400px;">
                    <!-- ASEAN Image -->
                    <img src="/img/ASEAN.png" alt="ASEAN" style="width: 200px; height: auto; margin-bottom: 2rem; display: block; margin-left: auto; margin-right: auto;">
                    
                    <h1 style="font-size: 1.875rem; font-weight: bold; color: white; margin-bottom: 1.5rem; line-height: 1.3;">
                        Open Data
                    </h1>
                    <h1 style="font-size: 1.875rem; font-weight: bold; color: white; margin-bottom: 1.5rem; line-height: 1.3;">
                        Open Data Self-Assessment
                    </h1>
                    
                    <p style="color: #d1d5db; font-size: 1rem; line-height: 1.6;">
                        A dedicated platform for working groups to conduct the Open Data Self-Assessment and compute Open Data Scores across Coverage and Openness
                    </p>
                </div>
            </div>

            <!-- Right Side - Auth Content -->
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 3rem 1.5rem; background: white;">
                <div style="width: 100%; max-width: 28rem;">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden" style="display: flex; justify-content: center; margin-bottom: 2rem;">
                        <a href="/">
                            <x-application-logo style="width: 4rem; height: 4rem; color: #2563eb;" class="fill-current" />
                        </a>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        <style>
            @media (max-width: 1024px) {
                [style*="display: grid"][style*="grid-template-columns: 1fr 1fr"] {
                    grid-template-columns: 1fr !important;
                }
                [style*="display: none"][class*="hidden lg:flex"] {
                    display: none !important;
                }
            }
        </style>
    </body>
</html>

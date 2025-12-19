<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Tech - Widget Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navbar Simulation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="font-bold text-xl text-[#0056b3]">VISION TECH</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="#" class="border-[#0056b3] text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Home</a>
                        <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Services</a>
                        <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Pricing</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section Simulation -->
    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-[#0056b3] font-semibold tracking-wide uppercase">Test Environment</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Simulate Visitor Experience
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    Use this page to test the chat widget as a visitor. Open this is in a private/incognito window to generate a new visitor session.
                </p>
            </div>
        </div>
    </div>

    <!-- Content Actions -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Simulate Navigation</h3>
                    <div class="mt-5 space-x-2">
                        <button onclick="changePage('/services')" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Visit Services
                        </button>
                        <button onclick="changePage('/pricing')" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Visit Pricing
                        </button>
                        <button onclick="changePage('/checkout')" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Visit Checkout
                        </button>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">
                        This updates the URL in the address bar (via History API) which works with the widget's tracker.
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                 <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Reset Session</h3>
                    <div class="mt-5">
                       <button onclick="resetSession()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">
                            Clear Cookies & Reload
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changePage(path) {
            history.pushState({}, '', path);
            // Manually trigger a popstate or custom event if the widget listens to it, 
            // but normally the widget might poll or listen to location changes.
            // Dispatch a popstate event to be safe if the widget listens to it
            window.dispatchEvent(new Event('popstate'));
            console.log('Simulated navigation to ' + path);
        }

        function resetSession() {
            // Clear all cookies
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            location.reload();
        }
    </script>
    
    <!-- Embed the Widget -->
    <script src="/widget.js"></script>
</body>
</html>

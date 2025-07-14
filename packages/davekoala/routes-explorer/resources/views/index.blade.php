<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Routes Explorer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="gradient-bg text-white py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold text-center mb-4">🔍 Laravel Routes Explorer</h1>
            <p class="text-xl text-center opacity-90">Explore your Laravel routes and their complete dependency chains</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Search/Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Find Routes</h2>
            
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search by Name or URI</label>
                    <input type="text" id="routeSearch" placeholder="Search routes..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Method</label>
                    <select id="methodFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Methods</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                        <option value="PATCH">PATCH</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Routes Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold">Available Routes ({{ count($routes) }})</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full" id="routesTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URI</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($routes as $route)
                            <tr class="hover:bg-gray-50 route-row" 
                                data-name="{{ $route['name'] ?? '' }}" 
                                data-uri="{{ $route['uri'] }}"
                                data-methods="{{ implode(',', $route['methods']) }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @foreach($route['methods'] as $method)
                                        @if($method !== 'HEAD')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($method === 'GET') bg-green-100 text-green-800
                                                @elseif($method === 'POST') bg-blue-100 text-blue-800
                                                @elseif($method === 'PUT') bg-yellow-100 text-yellow-800
                                                @elseif($method === 'DELETE') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif
                                                mr-1 mb-1">
                                                {{ $method }}
                                            </span>
                                        @endif
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $route['uri'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $route['name'] ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                    {{ $route['action'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if($route['name'])
                                        <a href="{{ route('routes-explorer.explore', $route['name']) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            🔍 Explore
                                        </a>
                                    @endif
                                    <button onclick="exploreRoute('{{ $route['name'] ?? $route['uri'] }}')" 
                                            class="text-blue-600 hover:text-blue-900">
                                        📊 Quick View
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Analysis Modal -->
    <div id="analysisModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-96 overflow-y-auto">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Route Analysis</h3>
                    <button onclick="closeModal()" class="float-right text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div id="analysisContent" class="px-6 py-4">
                    <!-- Analysis content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        document.getElementById('routeSearch').addEventListener('input', filterRoutes);
        document.getElementById('methodFilter').addEventListener('change', filterRoutes);

        function filterRoutes() {
            const search = document.getElementById('routeSearch').value.toLowerCase();
            const methodFilter = document.getElementById('methodFilter').value;
            const rows = document.querySelectorAll('.route-row');

            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                const uri = row.dataset.uri.toLowerCase();
                const methods = row.dataset.methods;
                
                const matchesSearch = name.includes(search) || uri.includes(search);
                const matchesMethod = !methodFilter || methods.includes(methodFilter);
                
                row.style.display = matchesSearch && matchesMethod ? '' : 'none';
            });
        }

        function exploreRoute(routeIdentifier) {
            document.getElementById('analysisModal').classList.remove('hidden');
            document.getElementById('analysisContent').innerHTML = '<div class="text-center py-8">Loading...</div>';

            fetch('{{ route("routes-explorer.api.explore") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ route: routeIdentifier, depth: 2 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAnalysis(data.data);
                } else {
                    document.getElementById('analysisContent').innerHTML = 
                        '<div class="text-red-600">Error: ' + data.error + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('analysisContent').innerHTML = 
                    '<div class="text-red-600">Error loading analysis</div>';
            });
        }

        function displayAnalysis(analysis) {
            let html = '<h4 class="font-semibold mb-2">Route Information:</h4>';
            html += '<div class="bg-gray-50 p-3 rounded mb-4">';
            html += '<p><strong>Name:</strong> ' + (analysis.route_info.name || 'N/A') + '</p>';
            html += '<p><strong>URI:</strong> ' + analysis.route_info.uri + '</p>';
            html += '<p><strong>Methods:</strong> ' + analysis.route_info.methods.join(', ') + '</p>';
            html += '<p><strong>Action:</strong> ' + analysis.route_info.action + '</p>';
            html += '</div>';

            if (Object.keys(analysis.relationships).length > 0) {
                html += '<h4 class="font-semibold mb-2">Related Classes:</h4>';
                html += '<div class="space-y-2">';
                
                Object.entries(analysis.relationships).forEach(([className, info]) => {
                    html += '<div class="border-l-4 border-blue-500 pl-3">';
                    html += '<p class="font-medium">' + className + '</p>';
                    html += '<p class="text-sm text-gray-600">' + (info.type || 'Unknown') + '</p>';
                    html += '</div>';
                });
                
                html += '</div>';
            }

            document.getElementById('analysisContent').innerHTML = html;
        }

        function closeModal() {
            document.getElementById('analysisModal').classList.add('hidden');
        }
    </script>
</body>
</html>
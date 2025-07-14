<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HJS: Route Archaeologist</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .method-GET { background-color: #28a745; }
        .method-POST { background-color: #007bff; }
        .method-PUT { background-color: #ffc107; color: #212529; }
        .method-DELETE { background-color: #dc3545; }
        .method-PATCH { background-color: #6c757d; }
        .middleware {
            font-size: 12px;
            color: #666;
        }
        .route-count {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>HJS: Route Archaeologist</h1>
        
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search routes..." value="{{ request('search') }}">
        </form>
        
        <div class="route-count">
            Total routes: {{ $routes->count() }}
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th>URI</th>
                    <th>Name</th>
                    <th>Action</th>
                    <th>Middleware</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routes as $route)
                <tr>
                    <td>
                        @foreach(explode('|', $route['method']) as $method)
                            @if($method !== 'HEAD')
                                <span class="method method-{{ $method }}">{{ $method }}</span>
                            @endif
                        @endforeach
                    </td>
                    <td><code>{{ $route['uri'] }}</code></td>
                    <td>{{ $route['name'] ?? '-' }}</td>
                    <td>{{ $route['action'] }}</td>
                    <td>
                        @if($route['middleware'])
                            <div class="middleware">
                                {{ implode(', ', $route['middleware']) }}
                            </div>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
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

        .test-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .test-btn:hover {
            background-color: #0056b3;
        }

        .row {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            width: 100%;
            height: 100vh;
        }

        .column {
            display: flex;
            flex-direction: column;
            flex-basis: 100%;
            flex: 1;
        }

        .left-column {
            /* background-color: blue; */
            max-height: 100vh;
            overflow-y: auto;
            padding: 0.5em;
            margin: 0.5em;
        }

        .right-column {
            /* background-color: green; */
            padding: 20px;
            padding: 0.5em;
            margin: 0.5em;
        }

        .right-column h3 {
            margin-top: 0;
            color: #333;
        }

        .right-column pre {
            background-color: #f8f9fa;
            color: #212529;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
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

        <div class='some-page-wrapper'>
            <div class='row'>
                <div class='column'>
                    <div class='left-column'>
                        <table>
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>URI</th>
                                    <th>Name</th>
                                    <th>Action</th>
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
                                    <td>
                                        <button onclick="testRoute('{{ $route['method'] }}', '{{ $route['uri'] }}')" 
                                                class="test-btn">
                                            Test Route
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='column'>
                    <div class='right-column'>
                        Lorem ipsum, dolor sit amet consectetur adipisicing elit. Id dicta architecto placeat maxime officia iusto porro sed necessitatibus corporis, ipsa, eaque ex. Dolores ipsum quam, cupiditate laboriosam libero ex nam.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function testRoute(method, route) {
            // Create the URL with query parameters
            const url = new URL(window.location.origin + '/dev/routes-explorer/explore');
            url.searchParams.set('verb', method);
            url.searchParams.set('route', route);
            
            // Show loading message in right column
            const rightColumn = document.querySelector('.right-column');
            rightColumn.innerHTML = '<h3>Loading...</h3>';
            
            // Make the GET request
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Display the response in the right column
                    rightColumn.innerHTML = `
                        <h3>Route Test Result</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    rightColumn.innerHTML = `
                        <h3>Error</h3>
                        <p style="color: red;">An error occurred: ${error.message}</p>
                    `;
                });
        }
    </script>
</body>
</html>
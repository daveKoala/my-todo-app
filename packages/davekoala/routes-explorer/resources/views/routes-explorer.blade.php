<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HJS: Route Archaeologist</title>
@include('routes-explorer::partials.styles')
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
                        @include('routes-explorer::routetable', ['routes' => $routes])
                    </div>
                </div>
                <div class='column'>
                    <div class='right-column'>
                        @include('routes-explorer::results')
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
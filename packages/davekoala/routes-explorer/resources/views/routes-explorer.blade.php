<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DaveKoala: Route Archaeologist</title>
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
@include('routes-explorer::partials.styles')
</head>
<body>
    <div class="container">
        <h1>DaveKoala: Route Archaeologist</h1>
        
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
        let currentData = null;
        let currentView = 'network';

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
                    currentData = data;
                    displayResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    rightColumn.innerHTML = `
                        <h3>Error</h3>
                        <p style="color: red;">An error occurred: ${error.message}</p>
                    `;
                });
        }
        
        function displayResults(data) {
            const rightColumn = document.querySelector('.right-column');
            
            rightColumn.innerHTML = `
                <h3>Route Analysis Results</h3>
                <div class="view-toggle">
                    <button onclick="switchView('network')" class="${currentView === 'network' ? 'active' : ''}">Network</button>
                    <button onclick="switchView('json')" class="${currentView === 'json' ? 'active' : ''}">JSON</button>
                </div>
                <div id="content-area"></div>
            `;
            
            if (currentView === 'network') {
                displayNetworkGraph(data.analysis.relationships);
            } else {
                displayJson(data);
            }
        }
        
        function switchView(view) {
            currentView = view;
            if (currentData) {
                displayResults(currentData);
            }
        }
        
        function displayNetworkGraph(relationships) {
            const contentArea = document.getElementById('content-area');
            contentArea.innerHTML = '<div id="network-container"></div>';
            
            // Convert relationships to Vis.js format
            const nodes = [];
            const edges = [];
            
            // Create nodes
            Object.values(relationships).forEach(rel => {
                nodes.push({
                    id: rel.name,
                    label: rel.name.split('\\').pop(), // Short name
                    title: rel.name, // Full name on hover
                    color: getColorForType(rel.type),
                    shape: getShapeForType(rel.type),
                    font: { size: 12 }
                });
            });
            
            // Create edges
            Object.values(relationships).forEach(rel => {
                // Inheritance edges
                if (rel.extends) {
                    edges.push({
                        from: rel.name,
                        to: rel.extends,
                        label: 'extends',
                        color: { color: '#ff9500' },
                        arrows: 'to'
                    });
                }
                
                // Dependency edges
                if (rel.dependencies) {
                    rel.dependencies.forEach(dep => {
                        edges.push({
                            from: rel.name,
                            to: dep.class,
                            label: 'uses',
                            color: { color: '#007bff' },
                            arrows: 'to',
                            dashes: true
                        });
                    });
                }
                
                // Interface edges
                if (rel.implements) {
                    rel.implements.forEach(iface => {
                        edges.push({
                            from: rel.name,
                            to: iface,
                            label: 'implements',
                            color: { color: '#28a745' },
                            arrows: 'to',
                            dashes: [5, 5]
                        });
                    });
                }
                
                // Trait edges
                if (rel.traits) {
                    rel.traits.forEach(trait => {
                        edges.push({
                            from: rel.name,
                            to: trait,
                            label: 'uses',
                            color: { color: '#6c757d' },
                            arrows: 'to',
                            dashes: [2, 2]
                        });
                    });
                }
            });
            
            // Create the network
            const container = document.getElementById('network-container');
            const data = { nodes: nodes, edges: edges };
            const options = {
                physics: {
                    enabled: true,
                    stabilization: { iterations: 100 }
                },
                layout: {
                    hierarchical: {
                        enabled: true,
                        direction: 'UD',
                        sortMethod: 'directed'
                    }
                },
                nodes: {
                    borderWidth: 2,
                    size: 20,
                    font: { size: 12, face: 'Arial' }
                },
                edges: {
                    width: 2,
                    font: { size: 10 }
                }
            };
            
            new vis.Network(container, data, options);
        }
        
        function displayJson(data) {
            const contentArea = document.getElementById('content-area');
            contentArea.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        }
        
        function getColorForType(type) {
            const colors = {
                'Controller': '#007bff',
                'Eloquent Model': '#28a745',
                'Service': '#ffc107',
                'Interface': '#6c757d',
                'Trait': '#dc3545',
                'Abstract Class': '#fd7e14',
                'Class': '#6f42c1'
            };
            return colors[type] || '#6c757d';
        }
        
        function getShapeForType(type) {
            const shapes = {
                'Controller': 'box',
                'Eloquent Model': 'database',
                'Service': 'diamond',
                'Interface': 'ellipse',
                'Trait': 'triangle',
                'Abstract Class': 'star',
                'Class': 'dot'
            };
            return shapes[type] || 'dot';
        }
    </script>
</body>
</html>
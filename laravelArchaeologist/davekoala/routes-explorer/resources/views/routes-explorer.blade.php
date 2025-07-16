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

            const { methods, uri } = data.route;

            const title = `${methods.join('|') || ''} ${uri || ''}`;
            
            rightColumn.innerHTML = `
                <h3>Route Analysis Results:${ title }</h3>
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
                const nodeIds = new Set();

                // Create nodes for all classes in relationships
                Object.values(relationships).forEach(rel => {
                    if (!nodeIds.has(rel.name)) {
                        nodes.push({
                            id: rel.name,
                            label: rel.name.split('\\').pop(), // Short name
                            title: rel.name, // Full name on hover
                            color: getColorForType(rel.type),
                            shape: getShapeForType(rel.type),
                            font: { size: 12 }
                        });
                        nodeIds.add(rel.name);
                    }

                    // Add nodes for parent classes
                    if (rel.extends && !nodeIds.has(rel.extends)) {
                        nodes.push({
                            id: rel.extends,
                            label: rel.extends.split('\\').pop(),
                            title: rel.extends,
                            color: getColorForType('Class'), // Default color for parent
                            shape: 'box',
                            font: { size: 11 }
                        });
                        nodeIds.add(rel.extends);
                    }

                    // Add nodes for traits (only show key ones to avoid clutter)
                    if (rel.traits) {
                        rel.traits.forEach(trait => {
                            const traitName = trait.split('\\').pop();
                            // Only show important Laravel traits
                            if (['SoftDeletes', 'HasFactory', 'Notifiable'].includes(traitName)) {
                                if (!nodeIds.has(trait)) {
                                    nodes.push({
                                        id: trait,
                                        label: traitName,
                                        title: trait,
                                        color: '#e74c3c',
                                        shape: 'triangle',
                                        font: { size: 10 }
                                    });
                                    nodeIds.add(trait);
                                }
                            }
                        });
                    }
                });

                // Create edges for relationships
                Object.values(relationships).forEach(rel => {
                    // Inheritance edges
                    if (rel.extends) {
                        edges.push({
                            from: rel.name,
                            to: rel.extends,
                            label: 'extends',
                            color: { color: '#ff9500' },
                            arrows: 'to',
                            width: 2
                        });
                    }

                    // Dependency edges
                    if (rel.dependencies) {
                        rel.dependencies.forEach(dep => {
                            // Choose edge style based on dependency usage type
                            let edgeColor = '#007bff';  // default
                            let edgeLabel = 'uses';

                            // Customize based on the usage type from your patterns
                            if (dep.context && dep.context.includes('event_dispatch')) {
                                edgeColor = '#e83e8c';
                                edgeLabel = 'dispatches';
                            } else if (dep.context && dep.context.includes('job_dispatch')) {
                                edgeColor = '#6610f2';
                                edgeLabel = 'queues';
                            } else if (dep.context && dep.context.includes('notification')) {
                                edgeColor = '#20c997';
                                edgeLabel = 'notifies';
                            } else if (dep.context && dep.context.includes('Middleware')) {
                                edgeColor = '#17a2b8';
                                edgeLabel = 'middleware';
                            }

                            edges.push({
                                from: rel.name,
                                to: dep.class,
                                label: edgeLabel,
                                color: { color: edgeColor },
                                arrows: 'to',
                                dashes: true,
                                width: 2
                            });
                        });
                    }

                    // Trait edges (only for the ones we included as nodes)
                    if (rel.traits) {
                        rel.traits.forEach(trait => {
                            const traitName = trait.split('\\').pop();
                            if (['SoftDeletes', 'HasFactory', 'Notifiable'].includes(traitName)) {
                                edges.push({
                                    from: rel.name,
                                    to: trait,
                                    label: 'uses',
                                    color: { color: '#9b59b6' },
                                    arrows: 'to',
                                    dashes: [3, 3],
                                    width: 1
                                });
                            }
                        });
                    }
                });

                // Create the network
                const container = document.getElementById('network-container');
                const data = { nodes: nodes, edges: edges };
                const options = {
                    physics: {
                        enabled: false
                    },
                    layout: {
                        hierarchical: {
                            enabled: true,
                            direction: 'UD',
                            sortMethod: 'directed',
                            nodeSpacing: 150,
                            levelSeparation: 100
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
                    'Middleware': '#17a2b8',     // Add these new types
                    'Event': '#e83e8c',
                    'Job': '#6610f2',
                    'Notification': '#20c997',
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
                    'Middleware': 'hexagon',      // Add these new types
                    'Event': 'dot',
                    'Job': 'square',
                    'Notification': 'triangleDown',
                    'Class': 'dot'
                };
                return shapes[type] || 'dot';
            }
    </script>
</body>
</html>
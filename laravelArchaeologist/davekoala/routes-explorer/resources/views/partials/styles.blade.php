    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1800px;
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
            thead th {
  position: sticky;
  top: 0;
  background-color: #fff;
  z-index: 1;

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
            flex-wrap: nowrap;  /* Changed from wrap to nowrap */
            width: 100%;
            height: 100vh;
        }

        .column {
            display: flex;
            flex-direction: column;
            flex-basis: 50%;  /* Changed from 100% to 50% */
            flex: 1;
            min-width: 400px;  /* Add minimum width */
        }

        .left-column {
            /* background-color: blue; */
            max-height: 100vh;
            overflow-y: auto;
            padding: 0 0.5em;
            margin: 0 0.5em;
        }

        .right-column {
            /* background-color: green; */
            padding: 20px;
            padding: 0 0.5em;
            margin: 0 0.5em;
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
        
        #network-container {
            width: 100%;
            /* height: 400px; */
            height: 90vh;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .view-toggle {
            margin-bottom: 10px;
        }
        
        .view-toggle button {
            margin-right: 10px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 3px;
        }
        
        .view-toggle button.active {
            background: #007bff;
            color: white;
        }
    </style>
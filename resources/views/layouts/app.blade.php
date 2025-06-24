<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Keep Clone') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:300,400,500,700&display=swap" rel="stylesheet">

    <!-- Styles -->

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #fafafa;
        }

        .note-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .note-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .note-title {
            font-weight: 500;
            font-size: 16px;
            margin-bottom: 8px;
            color: #202124;
        }

        .note-content {
            color: #5f6368;
            font-size: 14px;
            line-height: 1.4;
            white-space: pre-wrap;
        }

        .note-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .note-card:hover .note-toolbar {
            opacity: 1;
        }

        .toolbar-btn {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            color: #5f6368;
            transition: background-color 0.2s ease;
        }

        .toolbar-btn:hover {
            background-color: #f1f3f4;
        }

        .new-note-form {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-expanded {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 64px;
            width: 280px;
            height: calc(100vh - 64px);
            background: white;
            border-right: 1px solid #e0e0e0;
            padding: 8px 0;
            overflow-y: auto;
        }

        .main-content {
            margin-left: 280px;
            padding: 32px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
        }

        .logo {
            font-size: 22px;
            font-weight: 400;
            color: #5f6368;
            margin-left: 16px;
        }

        .search-box {
            flex: 1;
            max-width: 720px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: #f1f3f4;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
        }

        .search-input:focus {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .sidebar-item:hover {
            background-color: #f1f3f4;
            color: #202124;
        }

        .sidebar-item.active {
            background-color: #feefc3;
            color: #202124;
            border-right: 3px solid #fbbc04;
        }

        .sidebar-icon {
            width: 24px;
            height: 24px;
            margin-right: 24px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <button class="toolbar-btn" onclick="toggleSidebar()">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z" />
            </svg>
        </button>

        <div class="logo">
            Keep Clone
        </div>

        <div class="search-box">
            <form action="{{ route('notes.search') }}" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Search your notes..."
                    value="{{ request('q') }}">
            </form>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <a href="{{ route('notes.index') }}"
            class="sidebar-item {{ request()->routeIs('notes.index') ? 'active' : '' }}">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M9 21c0 .5.4 1 1 1h4c.6 0 1-.5 1-1v-1H9v1zm3-19C8.1 2 5 5.1 5 9c0 2.4 1.2 4.5 3 5.7V17c0 .5.4 1 1 1h6c.6 0 1-.5 1-1v-2.3c1.8-1.3 3-3.4 3-5.7 0-3.9-3.1-7-7-7z" />
            </svg>
            Notes
        </a>

        <a href="{{ route('notes.archived') }}"
            class="sidebar-item {{ request()->routeIs('notes.archived') ? 'active' : '' }}">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z" />
            </svg>
            Archive
        </a>

        <a href="{{ route('notes.trash') }}"
            class="sidebar-item {{ request()->routeIs('notes.trash') ? 'active' : '' }}">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
            </svg>
            Trash
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');

            if (sidebar.style.display === 'none') {
                sidebar.style.display = 'block';
                mainContent.style.marginLeft = '280px';
            } else {
                sidebar.style.display = 'none';
                mainContent.style.marginLeft = '0';
            }
        }

        // Mobile responsiveness
        if (window.innerWidth < 768) {
            document.getElementById('sidebar').style.display = 'none';
            document.querySelector('.main-content').style.marginLeft = '0';
        }
    </script>
</body>

</html>
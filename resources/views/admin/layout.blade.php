<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Планировщик звонков') — Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f1117;
            --bg2: #1a1d27;
            --card: #20243a;
            --border: #2d3150;
            --accent: #4f7eff;
            --accent-h: #3a65e8;
            --green: #22c55e;
            --red: #ef4444;
            --yellow: #f59e0b;
            --text: #e2e8f0;
            --muted: #8892a8;
            --radius: 10px;
        }
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }

        /* Nav */
        nav { background: var(--bg2); border-bottom: 1px solid var(--border); padding: 0 2rem; display: flex; align-items: center; gap: 2rem; height: 60px; }
        nav .logo { font-weight: 700; font-size: 1.1rem; color: var(--accent); text-decoration: none; }
        nav a { color: var(--muted); text-decoration: none; font-size: .9rem; transition: color .2s; padding: .25rem .5rem; border-radius: 6px; }
        nav a:hover, nav a.active { color: var(--text); background: var(--card); }

        /* Layout */
        main { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* Cards */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-title { font-size: 1rem; font-weight: 600; color: var(--text); margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem; }

        /* Stats row */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--accent); }
        .stat-label { font-size: .8rem; color: var(--muted); margin-top: .25rem; }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th { text-align: left; color: var(--muted); font-weight: 500; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; padding: .5rem 1rem; border-bottom: 1px solid var(--border); }
        td { padding: .75rem 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }

        /* Badges */
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 99px; font-size: .75rem; font-weight: 600; }
        .badge-green { background: rgba(34,197,94,.15); color: var(--green); }
        .badge-red { background: rgba(239,68,68,.15); color: var(--red); }
        .badge-yellow { background: rgba(245,158,11,.15); color: var(--yellow); }
        .badge-blue { background: rgba(79,126,255,.15); color: var(--accent); }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem 1rem; border-radius: 7px; font-size: .85rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all .18s; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-h); }
        .btn-danger { background: rgba(239,68,68,.15); color: var(--red); border: 1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background: rgba(239,68,68,.3); }
        .btn-ghost { background: var(--bg2); color: var(--muted); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text); border-color: var(--accent); }
        .btn-sm { padding: .3rem .75rem; font-size: .78rem; }

        /* Forms */
        input, select, textarea { background: var(--bg2); border: 1px solid var(--border); color: var(--text); border-radius: 7px; padding: .5rem .75rem; font-size: .875rem; width: 100%; outline: none; transition: border-color .2s; }
        input:focus, select:focus { border-color: var(--accent); }
        input[type=date] { color-scheme: dark; }
        label { font-size: .8rem; color: var(--muted); display: block; margin-bottom: .3rem; }
        .form-row { display: flex; gap: .75rem; align-items: flex-end; }
        .form-row .form-group { flex: 1; }
        .form-group { margin-bottom: .75rem; }

        /* Alerts */
        .alert { padding: .8rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .875rem; }
        .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: var(--green); }

        /* Empty state */
        .empty { text-align: center; padding: 3rem; color: var(--muted); font-size: .9rem; }

        /* Dot indicators */
        .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: .4rem; }
        .dot-green { background: var(--green); }
        .dot-red { background: var(--red); }
        .dot-yellow { background: var(--yellow); }

        .text-muted { color: var(--muted); font-size: .825rem; }
        .section-heading { font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text); }
    </style>
</head>
<body>
<nav>
    <a href="{{ route('admin.dashboard', [], false) }}" class="logo">📞 МитингБот</a>
    <a href="{{ route('admin.dashboard', [], false) }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Дашборд</a>
    <a href="{{ route('admin.slots', [], false) }}" class="{{ request()->routeIs('admin.slots') ? 'active' : '' }}">Слоты</a>
    <a href="{{ route('admin.bookings', [], false) }}" class="{{ request()->routeIs('admin.bookings') ? 'active' : '' }}">Записи</a>
</nav>
<main>
    @if(session('success'))
        <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    @yield('content')
</main>
</body>
</html>

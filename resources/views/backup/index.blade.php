<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Advanced Backup Manager</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Pagination Style */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .pagination li {
            list-style: none;
        }

        .pagination li a,
        .pagination li span {
            display: block;
            padding: 10px 16px;
            border-radius: 10px;
            background: #0f172a;
            border: 1px solid #334155;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .pagination li a:hover {
            background: #2563eb;
        }

        .pagination .active span {
            background: #2563eb;
            border-color: #2563eb;
        }

        .pagination .disabled span {
            opacity: 0.5;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-950 text-white p-6">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-4xl font-bold mb-2">🚀 Advanced Backup Dashboard</h1>
            <p class="text-slate-400">Laravel 12 Backup & Restore Management System</p>
        </div>

        <!-- Nav Tabs -->
        <div class="flex gap-3 mb-8 flex-wrap justify-center">
            <a href="{{ route('backup.index') }}" class="bg-blue-600 px-4 py-2 rounded-lg text-sm">🏠 Dashboard</a>
            <a href="{{ route('backup.settings') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">⚙️ Settings</a>
            <a href="{{ route('backup.cloud') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">☁️ Cloud Config</a>
            <a href="{{ route('backup.validate') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">🧪 Validator</a>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-green-500/20 border border-green-500 text-green-300 p-4 rounded-xl mb-5">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/20 border border-red-500 text-red-300 p-4 rounded-xl mb-5">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats -->
        <div class="grid md:grid-cols-2 gap-5 mb-8">

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg">

                <h2 class="text-slate-400 text-sm">
                    Total Backups
                </h2>

                <p class="text-4xl font-bold mt-2">
                    {{ $totalBackups }}
                </p>

            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg">

                <h2 class="text-slate-400 text-sm">
                    Total Storage
                </h2>

                <p class="text-4xl font-bold mt-2">
                    {{ $totalStorage }} MB
                </p>

            </div>

        </div>

        <!-- Search -->
        <form method="GET" class="mb-6">

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="🔍 Search backups..."
                class="w-full bg-slate-900 border border-slate-700 p-4 rounded-xl focus:outline-none focus:border-blue-500">

        </form>

        <!-- Create Backup -->
        <form action="{{ route('backup.create') }}"
              method="POST"
              class="mb-8">

            @csrf

            <button
                class="w-full bg-blue-600 hover:bg-blue-700 transition-all duration-300 p-4 rounded-xl font-semibold shadow-lg">

                ➕ Create New Backup

            </button>

        </form>

        <!-- Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-slate-800">

                        <tr>

                            <th class="p-4 text-left">
                                File Name
                            </th>

                            <th class="p-4 text-left">
                                Size
                            </th>

                            <th class="p-4 text-left">
                                Created Date
                            </th>

                            <th class="p-4 text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($backups as $backup)

                            <tr class="border-t border-slate-800 hover:bg-slate-800/40 transition">

                                <td class="p-4">
                                    {{ $backup['name'] }}
                                </td>

                                <td class="p-4">
                                    {{ $backup['size'] }} KB
                                </td>

                                <td class="p-4">
                                    {{ $backup['date'] }}
                                </td>

                                <td class="p-4">

                                    <div class="flex justify-center gap-3">

                                        <!-- Download -->
                                        <a href="{{ route('backup.download', $backup['name']) }}"
                                           class="bg-green-600 hover:bg-green-700 transition px-4 py-2 rounded-lg text-sm">

                                            ⬇ Download

                                        </a>

                                        <!-- Delete -->
                                        <form action="{{ route('backup.delete', $backup['name']) }}"
                                              method="POST">

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Delete this backup?')"
                                                class="bg-red-600 hover:bg-red-700 transition px-4 py-2 rounded-lg text-sm">

                                                🗑 Delete

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4"
                                    class="text-center p-10 text-slate-400">

                                    No Backups Found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-slate-800">

                {{ $backups->links() }}

            </div>

        </div>

        <!-- Restore Section -->
        <div class="mt-8 bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg">

            <h2 class="text-2xl font-bold mb-5">
                ♻ Restore Backup
            </h2>

            <form action="{{ route('backup.restore') }}"
                  method="POST">

                @csrf

                <select
                    name="backup_file"
                    required
                    class="w-full bg-slate-800 border border-slate-700 p-4 rounded-xl mb-5 focus:outline-none focus:border-orange-500">

                    <option value="">
                        Select Backup File
                    </option>

                    @foreach($backups as $backup)

                        <option value="{{ $backup['name'] }}">
                            {{ $backup['name'] }}
                        </option>

                    @endforeach

                </select>

                <button
                    type="submit"
                    onclick="return confirm('Restore database from this backup?')"
                    class="w-full bg-orange-600 hover:bg-orange-700 transition-all duration-300 p-4 rounded-xl font-semibold shadow-lg">

                    ♻ Restore Database

                </button>

            </form>

        </div>

    </div>

</body>

</html>
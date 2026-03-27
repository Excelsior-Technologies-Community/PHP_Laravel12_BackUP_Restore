<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Laravel Backup & Restore</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center p-6">

    <div class="w-full max-w-4xl backdrop-blur-lg bg-white/10 border border-white/20 rounded-2xl shadow-2xl p-8 text-white">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold">🚀 Database Backup & Restore</h1>
            <p class="text-sm text-gray-200 mt-1">Securely manage your database backups</p>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-green-500/20 border border-green-400 text-green-200 p-3 mb-4 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/20 border border-red-400 text-red-200 p-3 mb-4 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Create Backup -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">📦 Create Backup</h2>
            <form action="{{ route('backup.create') }}" method="POST">
                @csrf
                <button class="w-full bg-blue-600 hover:bg-blue-700 transition-all duration-300 px-4 py-3 rounded-lg font-medium shadow-lg">
                    ➕ Create New Backup
                </button>
            </form>
        </div>

        <!-- Existing Backups -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">📂 Existing Backups</h2>

            @if(count($backups) > 0)
                <div class="space-y-3 max-h-48 overflow-y-auto pr-2">
                    @foreach($backups as $backup)
                        <div class="flex justify-between items-center bg-white/10 border border-white/20 p-3 rounded-lg">
                            <span class="text-sm">{{ $backup }}</span>

                            <a href="{{ route('backup.download', $backup) }}"
                               class="bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-sm transition">
                                ⬇ Download
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-300 text-sm">No backups found.</p>
            @endif
        </div>

        <!-- Restore Backup -->
        <div>
            <h2 class="text-xl font-semibold mb-3">♻ Restore Backup</h2>

            <form action="{{ route('backup.restore') }}" method="POST" class="space-y-4">
                @csrf

                <select name="backup_file"
                    class="w-full bg-white/10 border border-white/20 text-white p-3 rounded-lg focus:outline-none">
                    <option value="" class="text-black">Select backup file</option>
                    @foreach($backups as $backup)
                        <option value="{{ $backup }}" class="text-black">{{ $backup }}</option>
                    @endforeach
                </select>

                <button class="w-full bg-red-500 hover:bg-red-600 transition-all duration-300 px-4 py-3 rounded-lg font-medium shadow-lg">
                    ♻ Restore Selected Backup
                </button>
            </form>
        </div>

    </div>

</body>

</html>
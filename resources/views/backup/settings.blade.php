<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-950 text-white p-6">
<div class="max-w-4xl mx-auto">

    <!-- Nav -->
    <div class="flex gap-3 mb-8 flex-wrap">
        <a href="{{ route('backup.index') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">🏠 Dashboard</a>
        <a href="{{ route('backup.settings') }}" class="bg-blue-600 px-4 py-2 rounded-lg text-sm">⚙️ Settings</a>
        <a href="{{ route('backup.cloud') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">☁️ Cloud Config</a>
        <a href="{{ route('backup.validate') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">🧪 Validator</a>
    </div>

    <h1 class="text-3xl font-bold mb-2">⚙️ Backup Schema Settings</h1>
    <p class="text-slate-400 mb-8">Configure what gets backed up, compression & encryption options.</p>

    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500 text-green-300 p-4 rounded-xl mb-6">{{ session('success') }}</div>
    @endif

    <form action="{{ route('backup.settings.save') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Structure & Data Toggles -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h2 class="text-lg font-semibold mb-4">📋 Backup Content</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <label class="flex items-center gap-3 bg-slate-800 p-4 rounded-xl cursor-pointer hover:bg-slate-700 transition">
                    <input type="checkbox" name="include_structure" value="1" {{ $settings->include_structure ? 'checked' : '' }}
                           class="w-5 h-5 accent-blue-500">
                    <div>
                        <p class="font-medium">Include Structure</p>
                        <p class="text-slate-400 text-xs">CREATE TABLE statements</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 bg-slate-800 p-4 rounded-xl cursor-pointer hover:bg-slate-700 transition">
                    <input type="checkbox" name="include_data" value="1" {{ $settings->include_data ? 'checked' : '' }}
                           class="w-5 h-5 accent-blue-500">
                    <div>
                        <p class="font-medium">Include Data</p>
                        <p class="text-slate-400 text-xs">INSERT INTO rows</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Table Selection -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h2 class="text-lg font-semibold mb-4">🗂️ Table Selection</h2>
            <p class="text-slate-400 text-sm mb-4">Check tables to <span class="text-green-400 font-medium">include</span> (leave all unchecked = backup all). Excluded tables override included.</p>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                @foreach($tables as $table)
                    <label class="flex items-center gap-2 bg-slate-800 p-3 rounded-lg cursor-pointer hover:bg-slate-700 transition text-sm">
                        <input type="checkbox" name="included_tables[]" value="{{ $table }}"
                               {{ in_array($table, $settings->included_tables ?? []) ? 'checked' : '' }}
                               class="accent-green-500">
                        <span class="text-green-300">✅ {{ $table }}</span>
                    </label>
                @endforeach
            </div>

            <p class="text-slate-400 text-sm mb-3">Check tables to <span class="text-red-400 font-medium">exclude</span> from backup:</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($tables as $table)
                    <label class="flex items-center gap-2 bg-slate-800 p-3 rounded-lg cursor-pointer hover:bg-slate-700 transition text-sm">
                        <input type="checkbox" name="excluded_tables[]" value="{{ $table }}"
                               {{ in_array($table, $settings->excluded_tables ?? []) ? 'checked' : '' }}
                               class="accent-red-500">
                        <span class="text-red-300">🚫 {{ $table }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Compression -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h2 class="text-lg font-semibold mb-4">📦 Compression</h2>
            <label class="flex items-center gap-3 mb-4 cursor-pointer">
                <input type="checkbox" name="compress" value="1" id="compressToggle"
                       {{ $settings->compress ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
                <span>Enable ZIP Compression</span>
            </label>
            <div class="mt-2">
                <label class="text-slate-400 text-sm block mb-2">Compression Level (1=fast, 9=max)</label>
                <input type="range" name="compression_level" min="1" max="9"
                       value="{{ $settings->compression_level ?? 6 }}"
                       class="w-full accent-blue-500"
                       oninput="document.getElementById('clevel').textContent = this.value">
                <p class="text-blue-400 text-sm mt-1">Level: <span id="clevel">{{ $settings->compression_level ?? 6 }}</span></p>
            </div>
        </div>

        <!-- Encryption -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h2 class="text-lg font-semibold mb-4">🔐 Encryption (AES-256-CBC)</h2>
            <label class="flex items-center gap-3 mb-4 cursor-pointer">
                <input type="checkbox" name="encrypt" value="1"
                       {{ $settings->encrypt ? 'checked' : '' }} class="w-5 h-5 accent-purple-500">
                <span>Enable Encryption</span>
            </label>
            <input type="text" name="encrypt_key" value="{{ $settings->encrypt_key }}"
                   placeholder="Enter encryption key (min 8 chars)"
                   class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-purple-500 text-sm">
            <p class="text-slate-500 text-xs mt-2">⚠️ Keep this key safe. Without it, encrypted backups cannot be restored.</p>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 transition p-4 rounded-xl font-semibold shadow-lg">
            💾 Save Settings
        </button>
    </form>
</div>
</body>
</html>

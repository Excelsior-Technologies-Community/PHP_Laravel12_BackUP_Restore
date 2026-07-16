<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Validator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-950 text-white p-6">
<div class="max-w-4xl mx-auto">

    <!-- Nav -->
    <div class="flex gap-3 mb-8 flex-wrap">
        <a href="{{ route('backup.index') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">🏠 Dashboard</a>
        <a href="{{ route('backup.settings') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">⚙️ Settings</a>
        <a href="{{ route('backup.cloud') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">☁️ Cloud Config</a>
        <a href="{{ route('backup.validate') }}" class="bg-blue-600 px-4 py-2 rounded-lg text-sm">🧪 Validator</a>
    </div>

    <h1 class="text-3xl font-bold mb-2">🧪 Recovery Validation Tester</h1>
    <p class="text-slate-400 mb-8">Sandbox scan your backup file before restoring — verify integrity, structure & data.</p>

    @if(session('error'))
        <div class="bg-red-500/20 border border-red-500 text-red-300 p-4 rounded-xl mb-6">{{ session('error') }}</div>
    @endif

    <!-- Run Validation Form -->
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl mb-8">
        <h2 class="text-lg font-semibold mb-4">▶️ Run Validation Test</h2>
        <form action="{{ route('backup.validate.run') }}" method="POST" class="flex gap-4 flex-wrap">
            @csrf
            <select name="backup_file" required
                    class="flex-1 bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 min-w-48">
                <option value="">Select .sql backup file</option>
                @foreach($backups as $backup)
                    <option value="{{ $backup }}">{{ $backup }}</option>
                @endforeach
            </select>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 transition px-6 py-3 rounded-xl font-semibold shadow-lg whitespace-nowrap">
                🔍 Run Validation
            </button>
        </form>
        @if(count($backups) === 0)
            <p class="text-slate-500 text-sm mt-3">No .sql backup files found. Create a backup first.</p>
        @endif
    </div>

    <!-- Latest Result (if just ran) -->
    @if(session('validation_result'))
        @php $latest = \App\Models\BackupValidationLog::find(session('validation_result')); @endphp
        @if($latest)
        <div class="bg-slate-900 border border-{{ $latest->status === 'passed' ? 'green' : ($latest->status === 'failed' ? 'red' : 'yellow') }}-500 p-6 rounded-2xl mb-8">
            <div class="flex items-center gap-3 mb-5">
                <span class="text-3xl">{{ $latest->status === 'passed' ? '✅' : ($latest->status === 'failed' ? '❌' : '⚠️') }}</span>
                <div>
                    <h2 class="text-xl font-bold">Validation {{ ucfirst($latest->status) }}</h2>
                    <p class="text-slate-400 text-sm">{{ $latest->backup_file }}</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-5">
                <div class="bg-slate-800 p-4 rounded-xl text-center">
                    <p class="text-2xl font-bold text-blue-400">{{ $latest->tables_found }}</p>
                    <p class="text-slate-400 text-xs mt-1">Tables Found</p>
                </div>
                <div class="bg-slate-800 p-4 rounded-xl text-center">
                    <p class="text-2xl font-bold text-green-400">{{ $latest->tables_verified }}</p>
                    <p class="text-slate-400 text-xs mt-1">Tables Verified</p>
                </div>
                <div class="bg-slate-800 p-4 rounded-xl text-center">
                    <p class="text-2xl font-bold text-purple-400">{{ $latest->rows_checked }}</p>
                    <p class="text-slate-400 text-xs mt-1">INSERT Statements</p>
                </div>
            </div>

            <!-- Check Report Cards -->
            <h3 class="font-semibold mb-3">📋 Check Report Cards</h3>
            <div class="grid md:grid-cols-2 gap-3">
                @php
                    $checkLabels = [
                        'file_not_empty'   => '📄 File Not Empty',
                        'has_create_table' => '🏗️ Has CREATE TABLE',
                        'has_insert_data'  => '📥 Has INSERT Data',
                        'no_error_markers' => '🚫 No Error Markers',
                        'valid_sql_header' => '🔖 Valid SQL Header',
                    ];
                @endphp
                @foreach($latest->checks as $key => $result)
                <div class="flex items-center justify-between bg-slate-800 p-3 rounded-xl">
                    <span class="text-sm">{{ $checkLabels[$key] ?? $key }}</span>
                    @if($result === 'pass')
                        <span class="bg-green-500/20 text-green-400 text-xs px-3 py-1 rounded-full font-medium">✅ PASS</span>
                    @elseif($result === 'warn')
                        <span class="bg-yellow-500/20 text-yellow-400 text-xs px-3 py-1 rounded-full font-medium">⚠️ WARN</span>
                    @else
                        <span class="bg-red-500/20 text-red-400 text-xs px-3 py-1 rounded-full font-medium">❌ FAIL</span>
                    @endif
                </div>
                @endforeach
            </div>

            <p class="text-slate-500 text-xs mt-4">{{ $latest->details }}</p>
        </div>
        @endif
    @endif

    <!-- Validation History -->
    @if($logs->count() > 0)
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <h2 class="text-lg font-semibold mb-4">📜 Validation History</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800">
                    <tr>
                        <th class="p-3 text-left">File</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Tables</th>
                        <th class="p-3 text-center">Inserts</th>
                        <th class="p-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr class="border-t border-slate-800 hover:bg-slate-800/40 transition">
                        <td class="p-3 text-slate-300">{{ $log->backup_file }}</td>
                        <td class="p-3 text-center">
                            @if($log->status === 'passed')
                                <span class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded-full">✅ Passed</span>
                            @elseif($log->status === 'failed')
                                <span class="bg-red-500/20 text-red-400 text-xs px-2 py-1 rounded-full">❌ Failed</span>
                            @else
                                <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2 py-1 rounded-full">⚠️ Partial</span>
                            @endif
                        </td>
                        <td class="p-3 text-center text-blue-400">{{ $log->tables_found }}</td>
                        <td class="p-3 text-center text-purple-400">{{ $log->rows_checked }}</td>
                        <td class="p-3 text-slate-400">{{ $log->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
</body>
</html>

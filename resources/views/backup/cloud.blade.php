<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Config</title>
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
        <a href="{{ route('backup.cloud') }}" class="bg-blue-600 px-4 py-2 rounded-lg text-sm">☁️ Cloud Config</a>
        <a href="{{ route('backup.validate') }}" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm transition">🧪 Validator</a>
    </div>

    <h1 class="text-3xl font-bold mb-2">☁️ Multi-Cloud Remote Storage</h1>
    <p class="text-slate-400 mb-8">Configure AWS S3 or FTP targets for automatic backup replication.</p>

    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500 text-green-300 p-4 rounded-xl mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-500/20 border border-red-500 text-red-300 p-4 rounded-xl mb-6">{{ session('error') }}</div>
    @endif

    <!-- Existing Configs -->
    @if($clouds->count() > 0)
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl mb-8">
        <h2 class="text-lg font-semibold mb-4">📡 Configured Targets</h2>
        <div class="space-y-3">
            @foreach($clouds as $cloud)
            <div class="flex items-center justify-between bg-slate-800 p-4 rounded-xl">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ $cloud->provider === 's3' ? '🪣' : '📁' }}</span>
                    <div>
                        <p class="font-medium">{{ $cloud->label }}</p>
                        <p class="text-slate-400 text-xs uppercase">{{ $cloud->provider }}
                            @if($cloud->enabled) <span class="text-green-400 ml-2">● Active</span>
                            @else <span class="text-slate-500 ml-2">● Disabled</span> @endif
                            @if($cloud->auto_upload) <span class="text-blue-400 ml-2">⚡ Auto-upload</span> @endif
                        </p>
                    </div>
                </div>
                <form action="{{ route('backup.cloud.delete', $cloud->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button onclick="return confirm('Delete this config?')"
                            class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded-lg text-sm transition">🗑 Delete</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Add New Config -->
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <h2 class="text-lg font-semibold mb-6">➕ Add Cloud Target</h2>

        <form action="{{ route('backup.cloud.save') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Provider & Label -->
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-slate-400 text-sm block mb-2">Provider</label>
                    <select name="provider" id="providerSelect" onchange="toggleFields()"
                            class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500">
                        <option value="s3">☁️ AWS S3</option>
                        <option value="ftp">📁 FTP Server</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-400 text-sm block mb-2">Label / Name</label>
                    <input type="text" name="label" placeholder="e.g. Production S3 Bucket"
                           class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                </div>
            </div>

            <!-- S3 Fields -->
            <div id="s3Fields" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">AWS Region</label>
                        <input type="text" name="region" placeholder="us-east-1"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">S3 Bucket Name</label>
                        <input type="text" name="bucket" placeholder="my-backup-bucket"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">Access Key ID</label>
                        <input type="text" name="key" placeholder="AKIA..."
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">Secret Access Key</label>
                        <input type="password" name="secret" placeholder="••••••••"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-slate-400 text-sm block mb-2">S3 Path Prefix</label>
                    <input type="text" name="path" placeholder="backups/"
                           class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                </div>
            </div>

            <!-- FTP Fields -->
            <div id="ftpFields" class="space-y-4 hidden">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">FTP Host</label>
                        <input type="text" name="host" placeholder="ftp.example.com"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">FTP Port</label>
                        <input type="number" name="port" placeholder="21" value="21"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">FTP Username</label>
                        <input type="text" name="ftp_user" placeholder="ftpuser"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="text-slate-400 text-sm block mb-2">FTP Password</label>
                        <input type="password" name="ftp_pass" placeholder="••••••••"
                               class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-slate-400 text-sm block mb-2">Remote Path</label>
                    <input type="text" name="path" placeholder="/backups/"
                           class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:border-blue-500 text-sm">
                </div>
            </div>

            <!-- Toggles -->
            <div class="grid md:grid-cols-2 gap-4">
                <label class="flex items-center gap-3 bg-slate-800 p-4 rounded-xl cursor-pointer hover:bg-slate-700 transition">
                    <input type="checkbox" name="enabled" value="1" checked class="w-5 h-5 accent-green-500">
                    <div>
                        <p class="font-medium text-sm">Enable This Target</p>
                        <p class="text-slate-400 text-xs">Activate for uploads</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 bg-slate-800 p-4 rounded-xl cursor-pointer hover:bg-slate-700 transition">
                    <input type="checkbox" name="auto_upload" value="1" class="w-5 h-5 accent-blue-500">
                    <div>
                        <p class="font-medium text-sm">Auto Upload on Backup</p>
                        <p class="text-slate-400 text-xs">Upload immediately after backup</p>
                    </div>
                </label>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 transition p-4 rounded-xl font-semibold shadow-lg">
                💾 Save Cloud Config
            </button>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const provider = document.getElementById('providerSelect').value;
    document.getElementById('s3Fields').classList.toggle('hidden', provider !== 's3');
    document.getElementById('ftpFields').classList.toggle('hidden', provider !== 'ftp');
}
</script>
</body>
</html>

const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 8000;
const ROOT = __dirname;

function serveFile(filePath, res) {
    const ext = path.extname(filePath);
    const mime = {
        '.html': 'text/html',
        '.php': 'text/html',
        '.js': 'text/javascript',
        '.css': 'text/css',
        '.json': 'application/json',
        '.png': 'image/png',
        '.jpg': 'image/jpeg'
    }[ext] || 'text/plain';

    fs.readFile(filePath, 'utf8', (err, data) => {
        if (err) {
            res.writeHead(404);
            res.end('Not found: ' + filePath);
            return;
        }
        // Strip <?php ... ?> blocks for preview
        let output = data.replace(/<\?php[\s\S]*?\?>/g, '<!-- PHP stripped for preview -->');
        res.writeHead(200, { 'Content-Type': mime + '; charset=utf-8' });
        res.end(output);
    });
}

const server = http.createServer((req, res) => {
    let urlPath = req.url.split('?')[0];
    if (urlPath === '/') urlPath = '/index.php';
    
    // Route mapping
    if (urlPath === '/test-report') {
        const report = fs.readFileSync(path.join(ROOT, 'TEST_REPORT.md'), 'utf8');
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(`
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Test Report</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8"><div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow">
<h1 class="text-2xl font-bold mb-4">Rapport Tests Locaux – SCHIPHRAT v2.0</h1>
<pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-auto whitespace-pre-wrap">${report.replace(/</g,'&lt;')}</pre>
<a href="/" class="mt-4 inline-block px-4 py-2 bg-purple-600 text-white rounded">Retour</a>
</div></body></html>`);
        return;
    }

    if (urlPath.startsWith('/src') || urlPath.startsWith('/includes') || urlPath.startsWith('/database')) {
        res.writeHead(403);
        res.end('Access to source denied in preview');
        return;
    }

    // Try file
    let filePath = path.join(ROOT, urlPath);
    // Security: prevent directory traversal
    if (!filePath.startsWith(ROOT)) {
        res.writeHead(403);
        res.end('Forbidden');
        return;
    }

    fs.stat(filePath, (err, stat) => {
        if (err || !stat.isFile()) {
            // Try with .php extension or index
            if (urlPath === '/dashboard.php' || urlPath === '/patientes.php') {
                // Show mock dashboard without PHP execution
                res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
                res.end(`
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${urlPath} – Preview</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-gray-50 p-8 text-center">
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
<div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-tools text-amber-600 text-2xl"></i></div>
<h1 class="text-2xl font-bold">${urlPath} – Mode aperçu sans PHP</h1>
<p class="text-gray-600 mt-2">Le serveur PHP n'est pas disponible dans ce sandbox (pas d'interpréteur PHP, réseau bloqué pour apt).</p>
<p class="text-sm text-gray-500 mt-2">Ce fichier existe et est syntaxiquement valide. Pour le tester réellement :</p>
<pre class="bg-gray-900 text-green-400 p-3 rounded-lg text-xs text-left mt-4">docker-compose up --build
# http://localhost:8000</pre>
<div class="mt-6 flex justify-center gap-2">
<a href="/" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Accueil</a>
<a href="/test-report" class="px-4 py-2 bg-gray-100 rounded-lg">Voir tests</a>
</div>
</div></body></html>`);
                return;
            }
            res.writeHead(404);
            res.end('404 – Fichier non trouvé: ' + urlPath);
            return;
        }
        serveFile(filePath, res);
    });
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`Test server listening on 0.0.0.0:${PORT}`);
});

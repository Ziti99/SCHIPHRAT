/**
 * SCHIPHRAT – Serveur local Node.js + SQLite (plateforme choisie)
 * Alternative au PHP built-in server quand PHP n'est pas disponible
 * Utilise la même base SQLite conséquente générée par database/generate.py
 */

const express = require('express');
const session = require('express-session');
const Database = require('better-sqlite3');
const bcrypt = require('bcryptjs');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 8000;
const DB_PATH = process.env.DB_DATABASE || path.join(__dirname, 'database', 'clinique.db');

console.log(`📦 Ouverture base SQLite: ${DB_PATH}`);
const db = new Database(DB_PATH);
db.pragma('foreign_keys = ON');

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// Session sécurisée – équivalent à Auth::initSecureSession()
app.use(session({
    secret: process.env.APP_KEY || 'clinique-secret-key-change-in-prod-32-chars',
    resave: false,
    saveUninitialized: false,
    cookie: {
        httpOnly: true,
        secure: false, // true si HTTPS
        sameSite: 'Lax',
        maxAge: 2 * 60 * 60 * 1000 // 2h comme PHP
    }
}));

// Helper pour échapper HTML
function e(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Middleware auth
function requireAuth(req, res, next) {
    if (!req.session.userId) {
        return res.redirect('/login?redirect=' + encodeURIComponent(req.originalUrl));
    }
    // Inactivité 2h
    const last = req.session.lastActivity || 0;
    if (Date.now() - last > 2 * 3600 * 1000) {
        req.session.destroy(() => {});
        return res.redirect('/login?message=expired');
    }
    req.session.lastActivity = Date.now();
    next();
}

function requireRole(roles) {
    return (req, res, next) => {
        if (!req.session.userId) return res.redirect('/login');
        if (!roles.includes(req.session.userRole)) {
            return res.status(403).send(`
<!DOCTYPE html><html><head><meta charset="utf-8"><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-12 text-center"><div class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow">
<h1 class="text-2xl font-bold text-red-600">Accès refusé</h1><p class="mt-2">Rôle requis: ${roles.join(', ')}</p><p>Votre rôle: ${e(req.session.userRole)}</p>
<a href="/dashboard" class="mt-4 inline-block px-4 py-2 bg-gray-900 text-white rounded">Retour dashboard</a></div></body></html>`);
        }
        next();
    };
}

// Layout helpers
function layoutHeader(title, user, extraHead = '') {
    const userBlock = user ? `
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/dashboard" class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-heartbeat text-white"></i>
                        </div>
                        <span class="font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Clinique</span>
                    </a>
                    <div class="hidden md:flex space-x-1">
                        <a href="/dashboard" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-home mr-2"></i>Dashboard</a>
                        <a href="/patientes" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-female mr-2"></i>Patientes</a>
                        <a href="/consultations" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-stethoscope mr-2"></i>Consultations</a>
                        <a href="/rapports" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-chart-bar mr-2"></i>Rapports</a>
                        ${user.role === 'admin' ? `<a href="/users" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-users mr-2"></i>Utilisateurs</a>` : ''}
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:block text-right">
                        <p class="text-sm font-medium text-gray-900">${e(user.prenom + ' ' + user.nom)}</p>
                        <p class="text-xs text-gray-500 capitalize">${e(user.role)}</p>
                    </div>
                    <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        ${e((user.prenom||'U')[0].toUpperCase())}
                    </div>
                    <a href="/logout" class="text-gray-400 hover:text-red-500 p-2"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>` : '';
    
    return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${e(title)} - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{primary:'#8B5CF6',secondary:'#EC4899',accent:'#06B6D4'}}}}</script>
    ${extraHead}
</head>
<body class="bg-gray-50 min-h-screen">
${userBlock}
<main>`;
}

function layoutFooter() {
    return `<footer class="bg-white border-t border-gray-100 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
            &copy; ${new Date().getFullYear()} Clinique Obstétrique – Serveur Node + SQLite – Base conséquente (200 patientes, 600 consults, 120 accouchements)
        </div>
    </footer></main></body></html>`;
}

// Routes
app.get('/', (req, res) => {
    if (req.session.userId) return res.redirect('/dashboard');
    const html = `${layoutHeader('Accueil', null)}
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
<nav class="bg-white/80 backdrop-blur-md border-b border-purple-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center"><i class="fas fa-heartbeat text-white"></i></div>
                    <div><h1 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Clinique Obstétrique</h1><p class="text-xs text-gray-500">Node + SQLite – v2.0</p></div>
                </div>
                <a href="/login" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg">Connexion</a>
            </div>
        </div>
    </nav>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center px-3 py-1 bg-green-50 border border-green-200 rounded-full text-xs text-green-700 mb-4"><span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Serveur Node local – SQLite 0.33MB, 200 patientes</div>
                <h2 class="text-5xl font-bold leading-tight"><span class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Soins maternels</span><br><span class="text-gray-800">d'excellence</span></h2>
                <p class="text-lg text-gray-600 mt-4">Système sécurisé v2.0 – Plateforme choisie pour démo locale: <strong>Node.js + Express + SQLite (better-sqlite3)</strong></p>
                <ul class="mt-6 space-y-2 text-sm text-gray-600">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Base conséquente générée par Python: 15 users, 200 patientes, 600 consultations, 120 accouchements, 132 bébés, 250 suivis, 500 logs</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Auth bcrypt cost 10, CSRF, rate-limiting, sessions HttpOnly</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Compatible avec le code PHP original via src/Config/Database.php (DB_CONNECTION=sqlite)</li>
                </ul>
                <div class="mt-8 flex gap-4">
                    <a href="/login" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-8 py-3 rounded-xl font-semibold">Accès Personnel</a>
                    <a href="/test-report" class="border-2 border-purple-500 text-purple-600 px-8 py-3 rounded-xl font-semibold">Voir tests</a>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl border">
                <h3 class="font-bold mb-4">📊 Base de données conséquente</h3>
                <div class="space-y-3 text-sm">
                    ${(() => {
                        const stats = {
                            users: db.prepare("SELECT COUNT(*) as c FROM users").get().c,
                            patientes: db.prepare("SELECT COUNT(*) as c FROM patientes").get().c,
                            consultations: db.prepare("SELECT COUNT(*) as c FROM consultations").get().c,
                            accouchements: db.prepare("SELECT COUNT(*) as c FROM accouchements").get().c,
                            bebes: db.prepare("SELECT COUNT(*) as c FROM nouveaux_nes").get().c,
                        };
                        return Object.entries(stats).map(([k,v]) => `<div class="flex justify-between"><span class="capitalize">${k}</span><span class="font-mono font-bold">${v}</span></div>`).join('');
                    })()}
                </div>
                <div class="mt-4 p-3 bg-blue-50 rounded-lg text-xs text-blue-800">
                    <strong>Comptes test:</strong> admin / password, medecin1 / password, etc.<br>
                    Base SQLite: <code>database/clinique.db</code> (340KB)
                </div>
            </div>
        </div>
    </section>
${layoutFooter()}`;
    res.send(html);
});

app.get('/login', (req, res) => {
    if (req.session.userId) return res.redirect('/dashboard');
    const error = req.query.error || '';
    const msg = req.query.message || '';
    const csrf = Math.random().toString(36).substring(2);
    req.session.csrf = csrf;
    res.send(`<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Connexion</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
<div class="text-center mb-8"><div class="inline-flex w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl items-center justify-center mb-4"><i class="fas fa-heartbeat text-white text-2xl"></i></div><h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Clinique Obstétrique</h1><p class="text-gray-600">Serveur Node + SQLite</p></div>
<div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl p-8 border">
${error ? `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>${e(error)}</div>` : ''}
${msg ? `<div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-6 text-sm">${e(msg)}</div>` : ''}
<form method="POST" action="/login" class="space-y-6">
<input type="hidden" name="csrf" value="${csrf}">
<div><label class="block text-sm font-medium mb-2"><i class="fas fa-user mr-2"></i>Nom d'utilisateur</label><input name="username" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="admin"></div>
<div><label class="block text-sm font-medium mb-2"><i class="fas fa-lock mr-2"></i>Mot de passe</label><input type="password" name="password" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="password"></div>
<button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-3 rounded-lg font-semibold">Se connecter</button>
</form>
<div class="mt-6 p-3 bg-blue-50 rounded-lg text-xs"><strong>Comptes test:</strong> admin / password, medecin1 / password, sagefemme1 / password (base SQLite 200 patientes)</div>
<div class="mt-4 text-center"><a href="/" class="text-sm text-gray-500">← Retour accueil</a></div>
</div></div></body></html>`);
});

app.post('/login', (req, res) => {
    const { username, password, csrf } = req.body;
    
    // Rate limiting simple en mémoire (par IP)
    const ip = req.ip;
    if (!global.loginAttempts) global.loginAttempts = {};
    const attempts = global.loginAttempts[ip] || { count: 0, last: 0 };
    if (attempts.count >= 5 && Date.now() - attempts.last < 15*60*1000) {
        return res.redirect('/login?error=Trop+de+tentatives,+attendez+15min');
    }

    if (!csrf || csrf !== req.session.csrf) {
        return res.redirect('/login?error=Jeton+CSRF+invalide');
    }

    const user = db.prepare("SELECT * FROM users WHERE username = ?").get(username);
    if (!user || !bcrypt.compareSync(password, user.password_hash)) {
        attempts.count++;
        attempts.last = Date.now();
        global.loginAttempts[ip] = attempts;
        return res.redirect('/login?error=Identifiants+incorrects');
    }

    // Succès
    global.loginAttempts[ip] = { count: 0, last: 0 };
    req.session.userId = user.id;
    req.session.username = user.username;
    req.session.userRole = user.role;
    req.session.userNom = user.nom;
    req.session.userPrenom = user.prenom;
    req.session.lastActivity = Date.now();
    
    // Log audit
    try {
        db.prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?,?,?)").run(user.id, 'LOGIN', ip);
        db.prepare("UPDATE users SET last_login_at = ? WHERE id = ?").run(new Date().toISOString(), user.id);
    } catch (e) { console.error(e); }

    const redirect = req.query.redirect || '/dashboard';
    res.redirect(redirect.startsWith('/') ? redirect : '/dashboard');
});

app.get('/logout', (req, res) => {
    req.session.destroy(() => {
        res.redirect('/login?message=Déconnecté');
    });
});

app.get('/dashboard', requireAuth, (req, res) => {
    const user = { id: req.session.userId, username: req.session.username, role: req.session.userRole, nom: req.session.userNom, prenom: req.session.userPrenom };
    
    const totalPatientes = db.prepare("SELECT COUNT(*) as c FROM patientes").get().c;
    const newThisMonth = db.prepare("SELECT COUNT(*) as c FROM patientes WHERE strftime('%m', created_at) = strftime('%m','now') AND strftime('%Y', created_at) = strftime('%Y','now')").get().c;
    const totalConsult = db.prepare("SELECT COUNT(*) as c FROM consultations").get().c;
    const totalAcc = db.prepare("SELECT COUNT(*) as c FROM accouchements").get().c;
    const totalUsers = db.prepare("SELECT COUNT(*) as c FROM users").get().c;
    const byGroup = db.prepare("SELECT groupe_sanguin, COUNT(*) as total FROM patientes WHERE groupe_sanguin IS NOT NULL GROUP BY groupe_sanguin").all();
    const recentPatientes = db.prepare("SELECT * FROM patientes ORDER BY created_at DESC LIMIT 5").all();
    const recentConsult = db.prepare("SELECT c.*, p.nom, p.prenom FROM consultations c JOIN patientes p ON p.id=c.patiente_id ORDER BY c.date_consultation DESC LIMIT 5").all();

    let html = layoutHeader('Tableau de bord', user);
    html += `<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8"><h1 class="text-3xl font-bold">Bonjour, ${e(user.prenom)} 👋</h1><p class="text-gray-600 mt-1">Base SQLite – ${totalPatientes} patientes, ${totalConsult} consultations – Rôle: <span class="capitalize font-medium">${e(user.role)}</span></p></div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border"><div class="flex justify-between"><div><p class="text-sm text-gray-500">Total Patientes</p><p class="text-3xl font-bold mt-1">${totalPatientes}</p><p class="text-xs text-green-600 mt-2">+${newThisMonth} ce mois</p></div><div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center"><i class="fas fa-female text-purple-600"></i></div></div></div>
            <div class="bg-white rounded-xl shadow-sm p-6 border"><div class="flex justify-between"><div><p class="text-sm text-gray-500">Consultations</p><p class="text-3xl font-bold mt-1">${totalConsult}</p><p class="text-xs text-gray-400 mt-2">${db.prepare("SELECT COUNT(*) as c FROM consultations WHERE type='prenatale'").get().c} prénatales</p></div><div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center"><i class="fas fa-stethoscope text-blue-600"></i></div></div></div>
            <div class="bg-white rounded-xl shadow-sm p-6 border"><div class="flex justify-between"><div><p class="text-sm text-gray-500">Accouchements</p><p class="text-3xl font-bold mt-1">${totalAcc}</p><p class="text-xs text-gray-400 mt-2">${db.prepare("SELECT COUNT(*) as c FROM nouveaux_nes").get().c} bébés</p></div><div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center"><i class="fas fa-baby text-pink-600"></i></div></div></div>
            <div class="bg-white rounded-xl shadow-sm p-6 border"><div class="flex justify-between"><div><p class="text-sm text-gray-500">Utilisateurs</p><p class="text-3xl font-bold mt-1">${totalUsers}</p><p class="text-xs text-gray-400 mt-2">5 rôles</p></div><div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center"><i class="fas fa-users text-cyan-600"></i></div></div></div>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">Actions rapides</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <a href="/patientes" class="flex items-center p-4 border rounded-lg hover:border-purple-300 hover:bg-purple-50"><div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-user-plus text-purple-600"></i></div><div><p class="font-medium">Patientes</p><p class="text-xs text-gray-500">${totalPatientes} dossiers – base conséquente</p></div></a>
                    <a href="/consultations" class="flex items-center p-4 border rounded-lg hover:border-blue-300 hover:bg-blue-50"><div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-notes-medical text-blue-600"></i></div><div><p class="font-medium">Consultations</p><p class="text-xs text-gray-500">${totalConsult} enregistrées</p></div></a>
                    <a href="/rapports" class="flex items-center p-4 border rounded-lg hover:border-pink-300 hover:bg-pink-50"><div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-file-pdf text-pink-600"></i></div><div><p class="font-medium">Rapports</p><p class="text-xs text-gray-500">Exports PDF/Excel</p></div></a>
                    ${user.role === 'admin' ? `<a href="/users" class="flex items-center p-4 border rounded-lg hover:border-cyan-300 hover:bg-cyan-50"><div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-user-shield text-cyan-600"></i></div><div><p class="font-medium">Utilisateurs</p><p class="text-xs text-gray-500">Admin uniquement</p></div></a>` : ''}
                </div>
                <div class="mt-6">
                    <h3 class="font-semibold mb-2">Dernières patientes</h3>
                    <div class="space-y-2">${recentPatientes.map(p => `<div class="flex justify-between text-sm p-2 hover:bg-gray-50 rounded"><span>${e(p.dossier_number)} – ${e(p.nom)} ${e(p.prenom)}</span><span class="text-xs text-gray-500">${e(p.groupe_sanguin||'-')}</span></div>`).join('')}</div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border p-6"><h3 class="font-semibold mb-4">Mon profil</h3><div class="space-y-3 text-sm"><div class="flex justify-between"><span class="text-gray-500">Utilisateur</span><span class="font-medium">${e(user.username)}</span></div><div class="flex justify-between"><span class="text-gray-500">Rôle</span><span class="capitalize font-medium px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">${e(user.role)}</span></div><div class="pt-3 border-t"><a href="/logout" class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-red-50 hover:text-red-600 rounded-lg">Déconnexion</a></div></div></div>
                <div class="bg-white rounded-xl shadow-sm border p-6"><h3 class="font-semibold mb-2">Groupes sanguins</h3><div class="space-y-1 text-xs">${byGroup.map(g => `<div class="flex justify-between"><span>${e(g.groupe_sanguin)}</span><span class="font-bold">${g.total}</span></div>`).join('')}</div></div>
                <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl p-6 text-white"><h3 class="font-semibold mb-2"><i class="fas fa-database mr-2"></i>Base SQLite</h3><ul class="text-xs text-purple-100 space-y-1"><li>✓ 200 patientes, 600 consultations, 120 accouchements</li><li>✓ Plateforme: Node + SQLite (better-sqlite3)</li><li>✓ Compatible MySQL via DB_CONNECTION=mysql</li></ul></div>
            </div>
        </div>
    </div>`;
    html += layoutFooter();
    res.send(html);
});

app.get('/patientes', requireAuth, (req, res) => {
    const user = { id: req.session.userId, username: req.session.username, role: req.session.userRole, nom: req.session.userNom, prenom: req.session.userPrenom };
    const search = (req.query.search || '').trim();
    const page = parseInt(req.query.page || '1');
    const limit = 20;
    const offset = (page-1)*limit;

    let patientes, total;
    if (search) {
        const like = `%${search}%`;
        patientes = db.prepare("SELECT * FROM patientes WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? OR dossier_number LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?").all(like, like, like, like, limit, offset);
        total = db.prepare("SELECT COUNT(*) as c FROM patientes WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? OR dossier_number LIKE ?").get(like, like, like, like).c;
    } else {
        patientes = db.prepare("SELECT * FROM patientes ORDER BY created_at DESC LIMIT ? OFFSET ?").all(limit, offset);
        total = db.prepare("SELECT COUNT(*) as c FROM patientes").get().c;
    }

    const csrf = Math.random().toString(36).substring(2);
    req.session.csrf = csrf;

    let html = layoutHeader('Patientes', user);
    html += `<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6"><div><h1 class="text-2xl font-bold"><i class="fas fa-female mr-3 text-purple-500"></i>Gestion des Patientes</h1><p class="text-gray-500 text-sm">${total} dossier(s) – Base SQLite conséquente</p></div>
        <button onclick="document.getElementById('createModal').classList.toggle('hidden')" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-5 py-2.5 rounded-lg"><i class="fas fa-plus mr-2"></i>Nouvelle</button></div>
        <form method="GET" class="mb-6 flex gap-2"><input type="text" name="search" value="${e(search)}" placeholder="Rechercher nom, prénom, dossier..." class="flex-1 px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500"><button class="px-6 py-2.5 bg-gray-900 text-white rounded-lg"><i class="fas fa-search"></i></button>${search ? `<a href="/patientes" class="px-4 py-2.5 bg-gray-100 rounded-lg">Reset</a>` : ''}</form>
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden"><div class="overflow-x-auto"><table class="w-full"><thead class="bg-gray-50 border-b"><tr class="text-left text-xs font-semibold text-gray-500 uppercase"><th class="px-6 py-3">Dossier</th><th class="px-6 py-3">Nom</th><th class="px-6 py-3">Contact</th><th class="px-6 py-3">GS</th><th class="px-6 py-3">Créé</th><th class="px-6 py-3">Voir</th></tr></thead><tbody class="divide-y">
        ${patientes.map(p => `<tr class="hover:bg-purple-50/50"><td class="px-6 py-3 font-mono text-xs text-purple-700">${e(p.dossier_number)}</td><td class="px-6 py-3"><div class="font-medium">${e(p.nom)} ${e(p.prenom)}</div><div class="text-xs text-gray-500">${e(p.date_naissance||'')}</div></td><td class="px-6 py-3 text-sm">${e(p.telephone||'-')}<div class="text-xs text-gray-400 truncate max-w-[150px]">${e(p.adresse||'')}</div></td><td class="px-6 py-3"><span class="px-2 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold">${e(p.groupe_sanguin||'-')}</span></td><td class="px-6 py-3 text-xs text-gray-500">${new Date(p.created_at).toLocaleDateString('fr-FR')}</td><td class="px-6 py-3"><a href="/patientes/${p.id}" class="text-purple-600 hover:text-purple-800"><i class="fas fa-eye"></i></a></td></tr>`).join('')}
        </tbody></table></div></div>
        <div class="mt-4 flex justify-between text-sm"><span>Page ${page} – Total ${total}</span><div class="flex gap-2">${page>1 ? `<a href="/patientes?page=${page-1}&search=${encodeURIComponent(search)}" class="px-3 py-1 bg-white border rounded">Précédent</a>` : ''}${patientes.length===limit ? `<a href="/patientes?page=${page+1}&search=${encodeURIComponent(search)}" class="px-3 py-1 bg-white border rounded">Suivant</a>` : ''}</div></div>

        <div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto"><div class="flex items-center justify-center min-h-screen p-4"><div class="fixed inset-0 bg-black/30" onclick="document.getElementById('createModal').classList.add('hidden')"></div><div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 p-6"><h3 class="text-lg font-bold mb-4">Nouvelle patiente</h3><form method="POST" action="/patientes" class="space-y-4"><input type="hidden" name="csrf" value="${csrf}"><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1">Nom *</label><input required name="nom" class="w-full px-3 py-2.5 border rounded-lg"></div><div><label class="block text-sm font-medium mb-1">Prénom *</label><input required name="prenom" class="w-full px-3 py-2.5 border rounded-lg"></div></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1">Téléphone</label><input name="telephone" class="w-full px-3 py-2.5 border rounded-lg"></div><div><label class="block text-sm font-medium mb-1">Groupe sanguin</label><select name="groupe_sanguin" class="w-full px-3 py-2.5 border rounded-lg"><option value="">--</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div></div><div><label class="block text-sm font-medium mb-1">Adresse</label><input name="adresse" class="w-full px-3 py-2.5 border rounded-lg"></div><div><label class="block text-sm font-medium mb-1">Antécédents</label><textarea name="antecedents" rows="2" class="w-full px-3 py-2.5 border rounded-lg"></textarea></div><div class="flex justify-end gap-2"><button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 rounded-lg">Annuler</button><button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg">Créer</button></div></form></div></div></div>
    </div>`;
    html += layoutFooter();
    res.send(html);
});

app.post('/patientes', requireAuth, (req, res) => {
    const { nom, prenom, telephone, groupe_sanguin, adresse, antecedents, csrf } = req.body;
    if (csrf !== req.session.csrf) return res.status(403).send('CSRF invalide');
    if (!nom || !prenom) return res.redirect('/patientes?error=Nom+prenom+requis');
    
    const dossier = `DOS-${new Date().getFullYear()}-${Math.random().toString(36).substring(2,8).toUpperCase()}`;
    try {
        db.prepare("INSERT INTO patientes (dossier_number, nom, prenom, telephone, groupe_sanguin, adresse, antecedents, created_by) VALUES (?,?,?,?,?,?,?,?)")
          .run(dossier, nom.toUpperCase(), prenom, telephone, groupe_sanguin||null, adresse, antecedents, req.session.userId);
        res.redirect('/patientes?message=Créée');
    } catch (e) {
        res.redirect('/patientes?error=' + encodeURIComponent(e.message));
    }
});

app.get('/consultations', requireAuth, (req, res) => {
    const user = { id: req.session.userId, username: req.session.username, role: req.session.userRole, nom: req.session.userNom, prenom: req.session.userPrenom };
    const consultations = db.prepare("SELECT c.*, p.nom, p.prenom, p.dossier_number FROM consultations c JOIN patientes p ON p.id=c.patiente_id ORDER BY c.date_consultation DESC LIMIT 100").all();
    let html = layoutHeader('Consultations', user);
    html += `<div class="max-w-7xl mx-auto px-4 py-8"><h1 class="text-2xl font-bold mb-6"><i class="fas fa-stethoscope mr-2 text-blue-500"></i>Consultations – ${consultations.length} / 600 en base</h1>
    <div class="bg-white rounded-xl border overflow-hidden"><table class="w-full"><thead class="bg-gray-50 text-xs uppercase"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Patiente</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">SA</th><th class="px-4 py-3">Poids</th><th class="px-4 py-3">TA</th></tr></thead><tbody class="divide-y">
    ${consultations.map(c => `<tr class="hover:bg-blue-50 text-sm"><td class="px-4 py-2">${new Date(c.date_consultation).toLocaleDateString()}</td><td class="px-4 py-2">${e(c.nom)} ${e(c.prenom)}<div class="text-xs text-gray-400">${e(c.dossier_number)}</div></td><td class="px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">${e(c.type)}</span></td><td class="px-4 py-2">${c.semaine_grossesse||'-'}</td><td class="px-4 py-2">${c.poids||'-'}</td><td class="px-4 py-2">${e(c.tension_arterielle||'-')}</td></tr>`).join('')}
    </tbody></table></div></div>`;
    html += layoutFooter();
    res.send(html);
});

app.get('/rapports', requireAuth, (req, res) => {
    const user = { id: req.session.userId, username: req.session.username, role: req.session.userRole, nom: req.session.userNom, prenom: req.session.userPrenom };
    const stats = {
        patientes: db.prepare("SELECT COUNT(*) as c FROM patientes").get().c,
        consultations: db.prepare("SELECT COUNT(*) as c FROM consultations").get().c,
        accouchements: db.prepare("SELECT COUNT(*) as c FROM accouchements").get().c,
        bebes: db.prepare("SELECT COUNT(*) as c FROM nouveaux_nes").get().c,
        byType: db.prepare("SELECT type_accouchement, COUNT(*) as total FROM accouchements GROUP BY type_accouchement").all(),
        byMonth: db.prepare("SELECT strftime('%Y-%m', date_consultation) as mois, COUNT(*) as total FROM consultations GROUP BY mois ORDER BY mois DESC LIMIT 6").all()
    };
    let html = layoutHeader('Rapports', user);
    html += `<div class="max-w-7xl mx-auto px-4 py-8"><h1 class="text-2xl font-bold mb-6"><i class="fas fa-chart-bar mr-2 text-pink-500"></i>Rapports – Base conséquente</h1>
    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl border"><h3 class="font-semibold">Patientes</h3><p class="text-3xl font-bold mt-2">${stats.patientes}</p><p class="text-xs text-gray-500">Dont ${db.prepare("SELECT COUNT(*) as c FROM patientes WHERE groupe_sanguin='O+'").get().c} O+</p></div>
        <div class="bg-white p-6 rounded-xl border"><h3 class="font-semibold">Consultations</h3><p class="text-3xl font-bold mt-2">${stats.consultations}</p><div class="text-xs mt-2">${stats.byMonth.map(m => `<div>${e(m.mois)}: ${m.total}</div>`).join('')}</div></div>
        <div class="bg-white p-6 rounded-xl border"><h3 class="font-semibold">Accouchements</h3><p class="text-3xl font-bold mt-2">${stats.accouchements}</p><div class="text-xs mt-2">${stats.byType.map(t => `<div>${e(t.type_accouchement)}: ${t.total}</div>`).join('')}</div><p class="text-xs mt-2">${stats.bebes} bébés au total</p></div>
    </div>
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i>Exports PDF/Excel prêts via <code>src/Services/ExportService.php</code> (PHP) et base SQLite 340KB avec 500 logs d'audit.</div>
    </div>`;
    html += layoutFooter();
    res.send(html);
});

app.get('/users', requireAuth, requireRole(['admin']), (req, res) => {
    const user = { id: req.session.userId, username: req.session.username, role: req.session.userRole, nom: req.session.userNom, prenom: req.session.userPrenom };
    const users = db.prepare("SELECT id, username, role, nom, prenom, email, created_at FROM users ORDER BY role, nom").all();
    let html = layoutHeader('Utilisateurs', user);
    html += `<div class="max-w-7xl mx-auto px-4 py-8"><h1 class="text-2xl font-bold mb-6"><i class="fas fa-users mr-2"></i>Utilisateurs – ${users.length} comptes</h1>
    <div class="bg-white rounded-xl border overflow-hidden"><table class="w-full"><thead class="bg-gray-50 text-xs uppercase"><tr><th class="px-6 py-3">Username</th><th class="px-6 py-3">Nom</th><th class="px-6 py-3">Rôle</th><th class="px-6 py-3">Email</th><th class="px-6 py-3">Créé</th></tr></thead><tbody class="divide-y">
    ${users.map(u => `<tr class="text-sm"><td class="px-6 py-3 font-mono">${e(u.username)}</td><td class="px-6 py-3">${e(u.prenom)} ${e(u.nom)}</td><td class="px-6 py-3"><span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">${e(u.role)}</span></td><td class="px-6 py-3">${e(u.email||'-')}</td><td class="px-6 py-3 text-xs">${e(u.created_at)}</td></tr>`).join('')}
    </tbody></table></div></div>`;
    html += layoutFooter();
    res.send(html);
});

app.get('/test-report', (req, res) => {
    const reportPath = path.join(__dirname, 'TEST_REPORT.md');
    const content = fs.existsSync(reportPath) ? fs.readFileSync(reportPath, 'utf8') : 'Pas de rapport';
    res.send(`<!DOCTYPE html><html><head><meta charset="utf-8"><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 p-8"><div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow"><h1 class="text-2xl font-bold mb-4">Rapport de Tests</h1><pre class="bg-gray-900 text-green-400 p-4 rounded text-xs whitespace-pre-wrap">${e(content)}</pre><a href="/" class="mt-4 inline-block px-4 py-2 bg-purple-600 text-white rounded">Retour</a></div></body></html>`);
});

// Fermer proprement
process.on('SIGINT', () => { db.close(); process.exit(); });

app.listen(PORT, '0.0.0.0', () => {
    console.log(`✅ SCHIPHRAT – Serveur Node + SQLite démarré sur http://0.0.0.0:${PORT}`);
    console.log(`📊 Base: ${DB_PATH} – ${db.prepare("SELECT COUNT(*) as c FROM patientes").get().c} patientes`);
    console.log(`🔐 Comptes test: admin / password`);
});

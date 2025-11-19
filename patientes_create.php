<?php
// Démarrer la session en premier, avant tout autre code
session_start();

// Point d'entrée public pour la création de patientes
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Récupérer les infos utilisateur depuis la session
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? '',
];

$db = new Database();

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $date_naissance = $_POST['date_naissance'] ?? '';
        $adresse = $_POST['adresse'] ?? '';
        $telephone = $_POST['telephone'] ?? '';
        $nationalite = $_POST['nationalite'] ?? '';
        $antecedents_medicaux = $_POST['antecedents_medicaux'] ?? '';
        
        // Validation des champs obligatoires
        if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($telephone)) {
            $error = "Veuillez remplir tous les champs obligatoires.";
        } else {
            // Calculer l'âge
            $age = date_diff(date_create($date_naissance), date_create('today'))->y;
            
            // Insérer la patiente
            $sql = "INSERT INTO patientes (nom, prenom, date_naissance, age, adresse, telephone, nationalite, antecedents_medicaux) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $db->query($sql, [
                $nom,
                $prenom,
                $date_naissance,
                $age,
                $adresse,
                $telephone,
                $nationalite,
                $antecedents_medicaux
            ]);
            
            $message = "Patiente créée avec succès !";
            
            // Récupérer l'ID créé pour proposer une consultation directe
            $nouvelle_patiente_id = $db->lastInsertId();
            
            // Rediriger vers la liste des patientes avec un CTA "Nouvelle consultation"
            header('Location: patientes.php?message=' . urlencode($message) . '&new_patiente_id=' . urlencode($nouvelle_patiente_id));
            exit();
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la création : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Patiente - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        .voice-indicator {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
        }
        .voice-btn i {
            font-size: 1rem; /* taille cohérente avec l’icône calendrier natif */
            line-height: 1;
        }
        /* Assure que les listes déroulantes (Tom Select) passent au-dessus des zones de texte en dessous */
        .ts-dropdown {
            z-index: 2147483647 !important;
            position: fixed !important;
            background: #ffffff !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15), 0 8px 10px rgba(0,0,0,0.1) !important;
            border: 1px solid #E5E7EB !important;
        }
        /* Ajuste légèrement le micro du champ date vers le bas */
        .align-date-micro {
            transform: translateY(calc(-50% + 3px)) !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="patientes.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-user-plus text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Nouvelle Patiente</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </span>
                            <a href="logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Messages -->
                <?php if ($error): ?>
                    <div id="message-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        <button onclick="closeMessage('message-error')" class="absolute top-0 right-0 mt-2 mr-2 text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Nouvelle Patiente</h1>
                    <p class="text-gray-600">Ajoutez une nouvelle patiente au système</p>
                </div>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Informations de base -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nom" name="nom" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="prenom" class="block text-sm font-medium text-gray-700 mb-2">
                                    Prénom <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="prenom" name="prenom" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative">
                                <label for="date_naissance" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date de naissance <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="date_naissance" name="date_naissance" required
                                       class="w-full px-3 pr-12 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <button type="button" class="voice-btn align-date-micro absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-purple-600 hover:text-purple-800 z-10" data-target="date_naissance" aria-label="Saisie vocale date de naissance">
                                    <i class="fas fa-microphone"></i>
                                </button>
                            </div>
                            
                            <div class="relative">
                                <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Téléphone <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="telephone" name="telephone" required
                                       class="w-full px-3 pr-12 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <button type="button" class="voice-btn absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-purple-600 hover:text-purple-800 z-10" data-target="telephone" aria-label="Saisie vocale téléphone">
                                    <i class="fas fa-microphone"></i>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative">
                                <label for="nationalite" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nationalité
                                </label>
                                <select id="nationalite" name="nationalite"
                                        class="w-full px-3 pr-12 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner une nationalité</option>
                                    <optgroup label="Afrique">
                                        <option value="Gabon" selected>Gabon</option>
                                        <option value="Algérie">Algérie</option>
                                        <option value="Angola">Angola</option>
                                        <option value="Bénin">Bénin</option>
                                        <option value="Botswana">Botswana</option>
                                        <option value="Burkina Faso">Burkina Faso</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="Cameroun">Cameroun</option>
                                        <option value="Cap-Vert">Cap-Vert</option>
                                        <option value="Comores">Comores</option>
                                        <option value="Congo">Congo</option>
                                        <option value="Côte d'Ivoire">Côte d'Ivoire</option>
                                        <option value="Djibouti">Djibouti</option>
                                        <option value="Égypte">Égypte</option>
                                        <option value="Érythrée">Érythrée</option>
                                        <option value="Eswatini">Eswatini</option>
                                        <option value="Éthiopie">Éthiopie</option>
                                        <option value="Gambie">Gambie</option>
                                        <option value="Ghana">Ghana</option>
                                        <option value="Guinée">Guinée</option>
                                        <option value="Guinée-Bissau">Guinée-Bissau</option>
                                        <option value="Guinée équatoriale">Guinée équatoriale</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Lesotho">Lesotho</option>
                                        <option value="Libéria">Libéria</option>
                                        <option value="Libye">Libye</option>
                                        <option value="Madagascar">Madagascar</option>
                                        <option value="Malawi">Malawi</option>
                                        <option value="Mali">Mali</option>
                                        <option value="Maroc">Maroc</option>
                                        <option value="Maurice">Maurice</option>
                                        <option value="Mauritanie">Mauritanie</option>
                                        <option value="Mozambique">Mozambique</option>
                                        <option value="Namibie">Namibie</option>
                                        <option value="Niger">Niger</option>
                                        <option value="Nigéria">Nigéria</option>
                                        <option value="Ouganda">Ouganda</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
                                        <option value="Sénégal">Sénégal</option>
                                        <option value="Seychelles">Seychelles</option>
                                        <option value="Sierra Leone">Sierra Leone</option>
                                        <option value="Somalie">Somalie</option>
                                        <option value="Soudan">Soudan</option>
                                        <option value="Soudan du Sud">Soudan du Sud</option>
                                        <option value="Tanzanie">Tanzanie</option>
                                        <option value="Tchad">Tchad</option>
                                        <option value="Togo">Togo</option>
                                        <option value="Tunisie">Tunisie</option>
                                        <option value="Zambie">Zambie</option>
                                        <option value="Zimbabwe">Zimbabwe</option>
                                    </optgroup>
                                    <optgroup label="Autres pays">
                                        <option value="France">France</option>
                                        <option value="Belgique">Belgique</option>
                                        <option value="Canada">Canada</option>
                                        <option value="États-Unis">États-Unis</option>
                                        <option value="Chine">Chine</option>
                                        <option value="Inde">Inde</option>
                                        <option value="Brésil">Brésil</option>
                                        <option value="Allemagne">Allemagne</option>
                                        <option value="Italie">Italie</option>
                                        <option value="Espagne">Espagne</option>
                                        <option value="Portugal">Portugal</option>
                                        <option value="Suisse">Suisse</option>
                                        <option value="Luxembourg">Luxembourg</option>
                                        <option value="Pays-Bas">Pays-Bas</option>
                                        <option value="Royaume-Uni">Royaume-Uni</option>
                                        <option value="Japon">Japon</option>
                                        <option value="Corée du Sud">Corée du Sud</option>
                                        <option value="Australie">Australie</option>
                                        <option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
                                    </optgroup>
                                </select>
                                <button type="button" class="voice-btn absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-purple-600 hover:text-purple-800 z-10" data-target="nationalite" aria-label="Saisie vocale nationalité">
                                    <i class="fas fa-microphone"></i>
                                </button>
                            </div>
                        </div>

                        <div class="relative">
                            <label for="adresse" class="block text-sm font-medium text-gray-700 mb-2">
                                Adresse
                            </label>
                            <textarea id="adresse" name="adresse" rows="3"
                                      class="w-full px-3 pr-12 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                      placeholder="Adresse de la patiente (optionnel)"></textarea>
                            <button type="button" class="voice-btn absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-purple-600 hover:text-purple-800 z-10" data-target="adresse" aria-label="Saisie vocale adresse">
                                <i class="fas fa-microphone"></i>
                            </button>
                        </div>

                        <!-- Informations médicales -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations médicales</h3>
                            
                            <div class="relative">
                                <label for="antecedents_medicaux" class="block text-sm font-medium text-gray-700 mb-2">
                                    Antécédents médicaux
                                </label>
                                <textarea id="antecedents_medicaux" name="antecedents_medicaux" rows="4"
                                          class="w-full px-3 pr-12 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                          placeholder="Décrivez les antécédents médicaux de la patiente..."></textarea>
                                <button type="button" class="voice-btn absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-purple-600 hover:text-purple-800 z-10" data-target="antecedents_medicaux" aria-label="Saisie vocale antécédents médicaux">
                                    <i class="fas fa-microphone"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="patientes.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tom Select Nationalité avec dropdown détaché et positionnement contrôlé
            const natSelect = new TomSelect("#nationalite", {
                create: false,
                dropdownParent: 'body',
                sortField: { field: "text", direction: "asc" }
            });
            
            // Définir Gabon comme valeur par défaut
            if (natSelect.getValue() === '') {
                natSelect.setValue('Gabon', true);
            }
            function positionNatDropdown() {
                try {
                    const wrapper = natSelect.wrapper;
                    const dropdown = document.querySelector('.ts-dropdown');
                    if (!wrapper || !dropdown || dropdown.style.display === 'none') return;
                    const rect = wrapper.getBoundingClientRect();
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                    const margin = 12;
                    dropdown.style.left = rect.left + 'px';
                    dropdown.style.top = (rect.bottom + 6) + 'px';
                    dropdown.style.width = rect.width + 'px';
                    const maxH = Math.max(160, viewportHeight - rect.bottom - margin);
                    dropdown.style.maxHeight = maxH + 'px';
                    dropdown.style.overflowY = 'auto';
                } catch (e) {}
            }
            natSelect.on('dropdown_open', positionNatDropdown);
            window.addEventListener('scroll', positionNatDropdown, true);
            window.addEventListener('resize', positionNatDropdown);

            // Saisie vocale (Web Speech API) - sans bloquer la saisie manuelle
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const speechSupported = !!SpeechRecognition;

            function normalizePhone(raw) {
                const digits = (raw || '').replace(/\D+/g, '').slice(0, 15);
                return digits.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
            }

            function parseFrenchDateToISO(raw) {
                if (!raw) return '';
                const mapMonths = {
                    'janvier': 1, 'fevrier': 2, 'février': 2, 'mars': 3, 'avril': 4, 'mai': 5, 'juin': 6,
                    'juillet': 7, 'aout': 8, 'août': 8, 'septembre': 9, 'octobre': 10, 'novembre': 11, 'decembre': 12, 'décembre': 12
                };
                let s = raw.toLowerCase().trim();
                // ex: 15/03/92 or 15-03-1992
                const m1 = s.match(/(\d{1,2})[\/\-\. ](\d{1,2})[\/\-\. ](\d{2,4})/);
                if (m1) {
                    let d = parseInt(m1[1], 10);
                    let mo = parseInt(m1[2], 10);
                    let y = parseInt(m1[3], 10);
                    if (y < 100) y += 1900; // heuristique
                    return `${y.toString().padStart(4,'0')}-${mo.toString().padStart(2,'0')}-${d.toString().padStart(2,'0')}`;
                }
                // ex: 15 mars 1992
                const m2 = s.match(/(\d{1,2})\s+([a-zéûôîàèù]+)\s+(\d{2,4})/i);
                if (m2) {
                    let d = parseInt(m2[1], 10);
                    let mo = mapMonths[m2[2]] || 0;
                    let y = parseInt(m2[3], 10);
                    if (y < 100) y += 1900;
                    if (mo) return `${y.toString().padStart(4,'0')}-${mo.toString().padStart(2,'0')}-${d.toString().padStart(2,'0')}`;
                }
                // fallback: essayer Date.parse
                const dd = new Date(raw);
                if (!isNaN(dd.getTime())) {
                    const y = dd.getFullYear();
                    const mo = dd.getMonth() + 1;
                    const d = dd.getDate();
                    return `${y.toString().padStart(4,'0')}-${mo.toString().padStart(2,'0')}-${d.toString().padStart(2,'0')}`;
                }
                return '';
            }

            function pickNationaliteFromTranscript(text) {
                if (!natSelect) return;
                const val = (text || '').toLowerCase().trim();
                let best = '';
                let bestScore = 0;
                let bestText = '';
                
                // Récupérer toutes les options depuis le select HTML original
                const selectElement = document.querySelector('#nationalite');
                if (!selectElement) return;
                
                // Normaliser le texte (enlever accents, espaces multiples, etc.)
                function normalize(str) {
                    return str.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // Enlever accents
                        .replace(/\s+/g, ' ')
                        .trim();
                }
                
                const normalizedVal = normalize(val);
                console.log('[VOICE] Recherche pour:', normalizedVal);
                
                // Liste des candidats avec leurs scores
                const candidates = [];
                
                // Parcourir toutes les options du select
                Array.from(selectElement.options).forEach(opt => {
                    if (!opt.value) return; // Ignorer l'option vide
                    const optText = normalize(opt.text || '');
                    const optValue = opt.value;
                    let score = 0;
                    let reason = '';
                    
                    // 1. Correspondance exacte (score le plus élevé)
                    if (optText === normalizedVal) {
                        score = 1000;
                        reason = 'exact';
                    }
                    // 2. Commence par (très bon score) - ex: "mali" match "Mali"
                    else if (optText.startsWith(normalizedVal)) {
                        score = 500;
                        reason = 'starts';
                    }
                    // 3. Se termine par (bon score)
                    else if (optText.endsWith(normalizedVal)) {
                        score = 400;
                        reason = 'ends';
                    }
                    // 4. Correspondance exacte d'un mot (bon score)
                    else {
                        const optWords = optText.split(/\s+/);
                        const valWords = normalizedVal.split(/\s+/);
                        
                        // Vérifier si tous les mots de val sont dans optText
                        let allWordsMatch = true;
                        valWords.forEach(valWord => {
                            if (valWord.length > 2 && !optText.includes(valWord)) {
                                allWordsMatch = false;
                            }
                        });
                        
                        if (allWordsMatch && valWords.length > 0) {
                            // Vérifier si c'est un mot complet (pas juste une partie)
                            const isCompleteWord = optWords.some(optWord => {
                                return valWords.some(valWord => {
                                    return optWord === valWord || optWord.startsWith(valWord + ' ') || optWord.endsWith(' ' + valWord);
                                });
                            });
                            
                            if (isCompleteWord) {
                                score = 300;
                                reason = 'word';
                            } else {
                                // Vérifier si c'est au milieu d'un autre mot
                                // Ex: "mali" dans "Somalie" = REJETÉ
                                const index = optText.indexOf(normalizedVal);
                                const isInMiddle = index > 0 && 
                                                  index < optText.length - normalizedVal.length &&
                                                  optText[index - 1] !== ' ' &&
                                                  optText[index + normalizedVal.length] !== ' ';
                                
                                if (!isInMiddle) {
                                    score = 200;
                                    reason = 'partial';
                                } else {
                                    // Correspondance au milieu = REJETÉ
                                    score = 0;
                                    reason = 'middle-rejected';
                                }
                            }
                        }
                    }
                    
                    if (score > 0) {
                        candidates.push({
                            value: optValue,
                            text: optText,
                            score: score,
                            reason: reason,
                            length: optText.length
                        });
                    }
                });
                
                // Trier par score décroissant, puis par longueur croissante
                candidates.sort((a, b) => {
                    if (b.score !== a.score) return b.score - a.score;
                    return a.length - b.length;
                });
                
                console.log('[VOICE] Candidats trouvés:', candidates);
                
                // Prendre le meilleur candidat avec score >= 200
                const winner = candidates.find(c => c.score >= 200);
                
                if (winner) {
                    console.log('[VOICE] Sélectionné:', winner.text, 'score:', winner.score, 'raison:', winner.reason);
                    natSelect.setValue(winner.value, true);
                } else if (candidates.length > 0) {
                    console.log('[VOICE] Aucun candidat fiable (score < 200), meilleur:', candidates[0]);
                } else {
                    console.log('[VOICE] Aucun candidat trouvé');
                }
            }

            // Petit indicateur global d'état d'écoute
            let indicator = document.getElementById('voiceIndicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'voiceIndicator';
                indicator.className = 'voice-indicator hidden bg-purple-600 text-white text-sm px-3 py-2 rounded-lg shadow-lg z-50';
                indicator.setAttribute('role', 'status');
                indicator.setAttribute('aria-live', 'polite');
                indicator.textContent = '🎤 Écoute en cours… Parlez';
                document.body.appendChild(indicator);
            }

            function showIndicator() { indicator.classList.remove('hidden'); }
            function hideIndicator() { indicator.classList.add('hidden'); }

            function startDictation(targetId, btnEl) {
                const el = document.getElementById(targetId);
                if (!el || !speechSupported) return;
                const rec = new SpeechRecognition();
                rec.lang = 'fr-FR';
                rec.interimResults = false;
                rec.maxAlternatives = 1;
                rec.onstart = () => {
                    showIndicator();
                    if (btnEl) {
                        btnEl.classList.add('animate-pulse');
                    }
                };
                rec.onresult = (ev) => {
                    const transcript = ev.results[0][0].transcript || '';
                    if (targetId === 'date_naissance') {
                        const iso = parseFrenchDateToISO(transcript);
                        if (iso) el.value = iso;
                    } else if (targetId === 'telephone') {
                        el.value = normalizePhone(transcript);
                    } else if (targetId === 'nationalite') {
                        pickNationaliteFromTranscript(transcript);
                        return;
                    } else {
                        el.value = transcript;
                    }
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                };
                rec.onend = () => {
                    hideIndicator();
                    if (btnEl) {
                        btnEl.classList.remove('animate-pulse');
                    }
                };
                rec.onerror = () => {
                    hideIndicator();
                    if (btnEl) {
                        btnEl.classList.remove('animate-pulse');
                    }
                };
                rec.start();
            }

            // brancher les boutons micro
            document.querySelectorAll('.voice-btn').forEach(btn => {
                if (!speechSupported) {
                    btn.disabled = true;
                    btn.title = 'Saisie vocale non disponible sur cet appareil';
                } else {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-target');
                        startDictation(id, btn);
                    });
                }
            });

            // Logs et réalignement défensif (diagnostic)
            function logAndAlign(btn) {
                try {
                    const targetId = btn.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    if (!input) return;
                    const r = input.getBoundingClientRect();
                    const rb = btn.getBoundingClientRect();
                    console.log('[VOICE-ALIGN]', targetId, {
                        input: { top: r.top, height: r.height, center: r.top + r.height / 2 },
                        button: { top: rb.top, height: rb.height, center: rb.top + rb.height / 2 }
                    });
                    // Force position centrée si décalage > 2px
                    const parent = btn.parentElement;
                    const pr = parent.getBoundingClientRect();
                    const expectedTop = (r.top - pr.top) + r.height / 2;
                    const btnCenter = rb.top - pr.top + rb.height / 2;
                    if (Math.abs(btnCenter - expectedTop) > 2) {
                        btn.style.top = '50%';
                        btn.style.transform = 'translateY(-50%)';
                    }
                } catch (e) {}
            }
            const voiceButtons = document.querySelectorAll('.voice-btn');
            voiceButtons.forEach(logAndAlign);
            window.addEventListener('resize', () => voiceButtons.forEach(logAndAlign));
            window.addEventListener('orientationchange', () => voiceButtons.forEach(logAndAlign));
        });
        
        // Fonction pour fermer les messages
        function closeMessage(messageId) {
            const message = document.getElementById(messageId);
            if (message) {
                message.style.display = 'none';
            }
        }
    </script>
</body>
</html> 
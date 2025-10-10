<?php
session_start();

// Debug: Afficher les informations de session
error_log("Session debug - user_id: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("Session debug - username: " . ($_SESSION['username'] ?? 'non défini'));
error_log("Session debug - user_role: " . ($_SESSION['user_role'] ?? 'non défini'));

if (!isset($_SESSION['user_id'])) {
    error_log("Redirection vers login.php - user_id non défini");
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = new Database();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données - Convertir les chaînes vides en NULL pour les champs INT
        $patiente_id = $_POST['patiente_id'] ?? '';
        $medecin_id = !empty($_POST['medecin_id']) ? $_POST['medecin_id'] : null;
        $sagefemme_id = !empty($_POST['sagefemme_id']) ? $_POST['sagefemme_id'] : null;
        $date_consultation = $_POST['date_consultation'] ?? '';
        $tension_arterielle = $_POST['tension_arterielle'] ?? null;
        $poids = !empty($_POST['poids']) ? $_POST['poids'] : null;
        $hauteur_uterine = !empty($_POST['hauteur_uterine']) ? $_POST['hauteur_uterine'] : null;
        $position_foetus = $_POST['position_foetus'] ?? null;
        $frequence_cardiaque_foetale = !empty($_POST['frequence_cardiaque_foetale']) ? $_POST['frequence_cardiaque_foetale'] : null;
        $observations = $_POST['observations'] ?? null;
        $recommandations = $_POST['recommandations'] ?? null;

        if (empty($patiente_id) || empty($date_consultation)) {
            throw new Exception('Les champs obligatoires doivent être remplis');
        }

        // Vérifier qu'un médecin OU une sage-femme est sélectionné (pas les deux)
        if ($medecin_id === null && $sagefemme_id === null) {
            throw new Exception('Veuillez sélectionner un médecin OU une sage-femme');
        }

        if ($medecin_id !== null && $sagefemme_id !== null) {
            throw new Exception('Veuillez sélectionner soit un médecin soit une sage-femme, pas les deux');
        }

        // Déterminer qui est le praticien (médecin ou sage-femme)
        $praticien_id = $medecin_id !== null ? $medecin_id : $sagefemme_id;
        $praticien_type = $medecin_id !== null ? 'medecin' : 'sagefemme';

        // Insertion de la consultation
        $db->query("
            INSERT INTO consultations_prenatales (
                patiente_id, medecin_id, sagefemme_id, date_consultation, tension_arterielle, 
                poids, hauteur_uterine, position_foetus, frequence_cardiaque_foetale,
                observations, recommandations, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $patiente_id, $medecin_id, $sagefemme_id, $date_consultation, $tension_arterielle,
            $poids, $hauteur_uterine, $position_foetus, $frequence_cardiaque_foetale,
            $observations, $recommandations
        ]);
        
        $consultation_id = $db->lastInsertId();
        error_log("Consultation créée avec succès, ID: " . $consultation_id);
        
        // Enregistrer les actes médicaux sélectionnés
        $montant_total_actes = 0;
        if (isset($_POST['actes']) && is_array($_POST['actes'])) {
            foreach ($_POST['actes'] as $acte_id) {
                // Récupérer le montant de l'acte
                $acte = $db->fetch("SELECT montant FROM actes_poses WHERE id = ?", [$acte_id]);
                if ($acte) {
                    $db->query("
                        INSERT INTO consultation_actes (consultation_id, acte_id, quantite, montant)
                        VALUES (?, ?, 1, ?)
                    ", [$consultation_id, $acte_id, $acte['montant']]);
                    $montant_total_actes += $acte['montant'];
                    error_log("Acte médical $acte_id enregistré pour la consultation $consultation_id");
                }
            }
        }
        
        // Créer automatiquement une entrée de paiement pour la caisse
        try {
            // Vérifier si la table paiements existe
            $db->query("SELECT 1 FROM paiements LIMIT 1");
            
            // Créer le paiement en statut "en_attente"
            $db->query("
                INSERT INTO paiements (
                    consultation_id, 
                    patiente_id, 
                    montant_total, 
                    montant_paye, 
                    montant_restant, 
                    statut,
                    created_at
                ) VALUES (?, ?, ?, 0, ?, 'en_attente', NOW())
            ", [
                $consultation_id,
                $patiente_id,
                $montant_total_actes,
                $montant_total_actes
            ]);
            error_log("Paiement créé automatiquement pour consultation $consultation_id - Montant: $montant_total_actes FCFA");
        } catch (PDOException $e) {
            // Table paiements n'existe pas encore, on ignore silencieusement
            error_log("Table paiements non disponible - Paiement non créé pour consultation $consultation_id");
        }
        
        // Redirection vers la page de détails
        header('Location: voir.php?id=' . $consultation_id . '&success=1');
        exit;

    } catch (Exception $e) {
        error_log("Erreur lors de la création de la consultation: " . $e->getMessage());
        $error_message = $e->getMessage();
    }
}

// Récupérer la liste des patientes
$patientes = $db->fetchAll("
    SELECT id, nom, prenom, date_naissance, telephone
    FROM patientes 
    ORDER BY nom, prenom
");

// Récupérer la liste des médecins
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'medecin'
    ORDER BY nom, prenom
");

// Récupérer la liste des sages-femmes
$sagefemmes = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'sagefemme'
    ORDER BY nom, prenom
");

// Récupérer la liste des actes médicaux actifs
$actes = $db->fetchAll("
    SELECT id, nom_acte, montant, description
    FROM actes_poses 
    WHERE is_active = 1
    ORDER BY nom_acte
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Consultation - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="../consultations.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-plus-circle text-2xl text-blue-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Nouvelle Consultation</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="../logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Nouvelle Consultation Prénatale</h2>
                    <p class="text-gray-600">Ajouter une nouvelle consultation pour une patiente</p>
                </div>

                <!-- Message d'erreur -->
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle mr-2 mt-1"></i>
                            <div>
                                <strong>Erreur !</strong> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulaire -->
                <form method="POST" class="bg-white rounded-xl shadow-lg p-6">
                    <!-- Informations de base -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-info-circle mr-2 text-blue-600"></i>Informations de Base
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="patiente_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Patiente <span class="text-red-500">*</span>
                                </label>
                                <select id="patiente_id" name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner une patiente</option>
                                    <?php foreach ($patientes as $patiente): ?>
                                        <option value="<?php echo $patiente['id']; ?>">
                                            <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                            (<?php echo date('d/m/Y', strtotime($patiente['date_naissance'])); ?>)
                                            <?php if ($patiente['telephone']): ?>
                                                - <?php echo htmlspecialchars($patiente['telephone']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Médecin
                                </label>
                                <select id="medecin_id" name="medecin_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner un médecin</option>
                                    <?php foreach ($medecins as $medecin): ?>
                                        <option value="<?php echo $medecin['id']; ?>">
                                            Dr. <?php echo htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']); ?>
                                            <?php if ($medecin['specialite']): ?>
                                                (<?php echo htmlspecialchars($medecin['specialite']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="sagefemme_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sage-femme
                                </label>
                                <select id="sagefemme_id" name="sagefemme_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner une sage-femme</option>
                                    <?php foreach ($sagefemmes as $sagefemme): ?>
                                        <option value="<?php echo $sagefemme['id']; ?>">
                                            <?php echo htmlspecialchars($sagefemme['prenom'] . ' ' . $sagefemme['nom']); ?>
                                            <?php if ($sagefemme['specialite']): ?>
                                                (<?php echo htmlspecialchars($sagefemme['specialite']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="date_consultation" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date et heure de consultation <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" id="date_consultation" name="date_consultation" required 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Signes vitaux -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-heartbeat mr-2 text-red-600"></i>Signes Vitaux
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="tension_arterielle" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tension artérielle
                                </label>
                                <input type="text" id="tension_arterielle" name="tension_arterielle" 
                                       placeholder="ex: 120/80 mmHg"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="poids" class="block text-sm font-medium text-gray-700 mb-2">
                                    Poids (kg)
                                </label>
                                <input type="number" id="poids" name="poids" step="0.1" min="0"
                                       placeholder="ex: 65.5"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Examen obstétrical -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-baby mr-2 text-pink-600"></i>Examen Obstétrical
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="hauteur_uterine" class="block text-sm font-medium text-gray-700 mb-2">
                                    Hauteur utérine (cm)
                                </label>
                                <input type="number" id="hauteur_uterine" name="hauteur_uterine" step="0.1" min="0"
                                       placeholder="ex: 28.5"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="position_foetus" class="block text-sm font-medium text-gray-700 mb-2">
                                    Position du fœtus
                                </label>
                                <select id="position_foetus" name="position_foetus" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner</option>
                                    <option value="céphalique">Céphalique</option>
                                    <option value="siège">Siège</option>
                                    <option value="transverse">Transverse</option>
                                    <option value="oblique">Oblique</option>
                                </select>
                            </div>
                            <div>
                                <label for="frequence_cardiaque_foetale" class="block text-sm font-medium text-gray-700 mb-2">
                                    Fréquence cardiaque fœtale (bpm)
                                </label>
                                <input type="number" id="frequence_cardiaque_foetale" name="frequence_cardiaque_foetale" 
                                       min="0" max="300"
                                       placeholder="ex: 140"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Actes médicaux -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-stethoscope mr-2 text-purple-600"></i>Actes Médicaux Posés
                        </h3>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                            <p class="text-sm text-purple-700 mb-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                Sélectionnez les actes médicaux effectués durant cette consultation
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php if (empty($actes)): ?>
                                    <p class="text-sm text-gray-600 col-span-2">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Aucun acte disponible. 
                                        <a href="../actes.php" class="text-purple-600 underline">Configurer les actes</a>
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($actes as $acte): ?>
                                        <label class="flex items-start p-3 bg-white border border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 hover:shadow-md transition-all">
                                            <input type="checkbox" 
                                                   name="actes[]" 
                                                   value="<?php echo $acte['id']; ?>" 
                                                   data-montant="<?php echo $acte['montant']; ?>"
                                                   data-nom="<?php echo htmlspecialchars($acte['nom_acte']); ?>"
                                                   class="mt-1 mr-3 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($acte['nom_acte']); ?></div>
                                                <div class="text-sm text-purple-600 font-semibold">
                                                    <?php echo number_format($acte['montant'], 0, ',', ' '); ?> FCFA
                                                </div>
                                                <?php if ($acte['description']): ?>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <?php echo htmlspecialchars($acte['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Résumé des actes sélectionnés -->
                            <div id="actes-summary" class="mt-4 p-3 bg-white rounded-lg border border-purple-300 hidden">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-900">
                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                        Actes sélectionnés:
                                    </span>
                                    <span id="total-actes" class="text-lg font-bold text-purple-600">0 FCFA</span>
                                </div>
                                <div id="actes-list" class="text-sm text-gray-700"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Observations et recommandations -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-notes-medical mr-2 text-green-600"></i>Observations et Recommandations
                        </h3>
                        <div class="space-y-6">
                            <div>
                                <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                                    Observations
                                </label>
                                <textarea id="observations" name="observations" rows="4"
                                          placeholder="Décrivez les observations cliniques..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            <div>
                                <label for="recommandations" class="block text-sm font-medium text-gray-700 mb-2">
                                    Recommandations
                                </label>
                                <textarea id="recommandations" name="recommandations" rows="4"
                                          placeholder="Indiquez les recommandations pour la patiente..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <a href="../consultations.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Enregistrer la Consultation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Validation côté client
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const patienteSelect = document.getElementById('patiente_id');
            const medecinSelect = document.getElementById('medecin_id');
            const sagefemmeSelect = document.getElementById('sagefemme_id');
            const dateInput = document.getElementById('date_consultation');

            // Fonction pour gérer la sélection exclusive médecin/sage-femme
            function handlePractitionerSelection(changedSelect, otherSelect) {
                if (changedSelect.value) {
                    otherSelect.value = '';
                    otherSelect.disabled = true;
                } else {
                    otherSelect.disabled = false;
                }
            }

            // Écouter les changements sur les sélections
            medecinSelect.addEventListener('change', function() {
                handlePractitionerSelection(this, sagefemmeSelect);
            });

            sagefemmeSelect.addEventListener('change', function() {
                handlePractitionerSelection(this, medecinSelect);
            });

            form.addEventListener('submit', function(e) {
                let isValid = true;
                let errorMessage = '';

                // Validation des champs obligatoires
                if (!patienteSelect.value) {
                    errorMessage += 'Veuillez sélectionner une patiente.\n';
                    isValid = false;
                }

                if (!medecinSelect.value && !sagefemmeSelect.value) {
                    errorMessage += 'Veuillez sélectionner un médecin OU une sage-femme.\n';
                    isValid = false;
                }

                if (medecinSelect.value && sagefemmeSelect.value) {
                    errorMessage += 'Veuillez sélectionner soit un médecin soit une sage-femme, pas les deux.\n';
                    isValid = false;
                }

                if (!dateInput.value) {
                    errorMessage += 'Veuillez saisir la date de consultation.\n';
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    alert('Erreurs de validation:\n' + errorMessage);
                }
            });

            // Validation de la date (ne pas permettre les dates futures)
            dateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const now = new Date();
                
                if (selectedDate > now) {
                    alert('La date de consultation ne peut pas être dans le futur.');
                    this.value = '';
                }
            });
            
            // Gestion de la sélection des actes et calcul du total
            const actesCheckboxes = document.querySelectorAll('input[name="actes[]"]');
            const actesSummary = document.getElementById('actes-summary');
            const totalActesSpan = document.getElementById('total-actes');
            const actesListDiv = document.getElementById('actes-list');
            
            function updateActesTotal() {
                let total = 0;
                let selectedActes = [];
                
                actesCheckboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        const montant = parseFloat(checkbox.dataset.montant);
                        const nom = checkbox.dataset.nom;
                        total += montant;
                        selectedActes.push({
                            nom: nom,
                            montant: montant
                        });
                    }
                });
                
                if (selectedActes.length > 0) {
                    actesSummary.classList.remove('hidden');
                    totalActesSpan.textContent = total.toLocaleString('fr-FR') + ' FCFA';
                    
                    let html = '<ul class="space-y-1">';
                    selectedActes.forEach(function(acte) {
                        html += '<li class="flex justify-between items-center">';
                        html += '<span>• ' + acte.nom + '</span>';
                        html += '<span class="font-semibold">' + acte.montant.toLocaleString('fr-FR') + ' FCFA</span>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    actesListDiv.innerHTML = html;
                } else {
                    actesSummary.classList.add('hidden');
                }
            }
            
            actesCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateActesTotal);
            });
        });
    </script>
</body>
</html> 
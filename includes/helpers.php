<?php
/**
 * Fichier de fonctions helper pour l'application
 * Compatible PHP 8.1+
 */

/**
 * Version sécurisée de htmlspecialchars qui gère les valeurs null
 * Compatible avec PHP 8.1+ qui n'accepte plus null dans htmlspecialchars
 * 
 * @param mixed $string La chaîne à échapper (peut être null)
 * @param int $flags Les flags htmlspecialchars
 * @param string $encoding L'encodage
 * @param bool $double_encode Double encodage
 * @return string La chaîne échappée ou une chaîne vide si null
 */
function h($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true) {
    if ($string === null || $string === '') {
        return '';
    }
    return htmlspecialchars((string)$string, $flags, $encoding, $double_encode);
}

/**
 * Échappe et affiche une valeur (version courte)
 * 
 * @param mixed $string La chaîne à échapper et afficher
 */
function eh($string) {
    echo h($string);
}

/**
 * Récupère une valeur d'un tableau avec une valeur par défaut
 * 
 * @param array $array Le tableau
 * @param string|int $key La clé
 * @param mixed $default Valeur par défaut
 * @return mixed
 */
function array_get($array, $key, $default = '') {
    return $array[$key] ?? $default;
}

/**
 * Formate une date au format français
 * 
 * @param string|null $date La date à formater
 * @param string $format Le format de sortie
 * @return string
 */
function format_date($date, $format = 'd/m/Y') {
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    
    try {
        $datetime = new DateTime($date);
        return $datetime->format($format);
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Formate une date et heure au format français
 * 
 * @param string|null $datetime La date/heure à formater
 * @return string
 */
function format_datetime($datetime) {
    return format_date($datetime, 'd/m/Y H:i');
}

/**
 * Sécurise une valeur numérique
 * 
 * @param mixed $value La valeur
 * @param int|float $default Valeur par défaut
 * @return int|float
 */
function safe_number($value, $default = 0) {
    if ($value === null || $value === '') {
        return $default;
    }
    return is_numeric($value) ? $value : $default;
}

/**
 * Génère un badge de rôle coloré
 * 
 * @param string|null $role Le rôle
 * @return string HTML du badge
 */
function role_badge($role) {
    $colors = [
        'admin' => 'bg-purple-100 text-purple-800',
        'medecin' => 'bg-blue-100 text-blue-800',
        'sage_femme' => 'bg-green-100 text-green-800',
        'secretaire' => 'bg-yellow-100 text-yellow-800',
        'caissiere' => 'bg-pink-100 text-pink-800'
    ];
    
    $role = $role ?? 'inconnu';
    $color = $colors[$role] ?? 'bg-gray-100 text-gray-800';
    $label = ucfirst(str_replace('_', ' ', $role));
    
    return '<span class="px-2 py-1 text-xs rounded-full ' . $color . '">' . h($label) . '</span>';
}

/**
 * Génère un badge de statut coloré
 * 
 * @param string|null $status Le statut
 * @return string HTML du badge
 */
function status_badge($status) {
    $colors = [
        'en_cours' => 'bg-blue-100 text-blue-800',
        'terminee' => 'bg-green-100 text-green-800',
        'interrompue' => 'bg-red-100 text-red-800',
        'en_attente' => 'bg-yellow-100 text-yellow-800',
        'valide' => 'bg-green-100 text-green-800',
        'rejete' => 'bg-red-100 text-red-800',
        'vivant' => 'bg-green-100 text-green-800',
        'mort_ne' => 'bg-red-100 text-red-800',
        'decede' => 'bg-gray-100 text-gray-800'
    ];
    
    $status = $status ?? 'inconnu';
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    $label = ucfirst(str_replace('_', ' ', $status));
    
    return '<span class="px-2 py-1 text-xs rounded-full ' . $color . '">' . h($label) . '</span>';
}

/**
 * Tronque un texte à une longueur donnée
 * 
 * @param string|null $text Le texte
 * @param int $length Longueur max
 * @param string $suffix Suffixe (...)
 * @return string
 */
function truncate($text, $length = 50, $suffix = '...') {
    if (!$text) return '';
    
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * 
 * @param string|array $roles Le(s) rôle(s) à vérifier
 * @return bool
 */
function has_role($roles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    
    return $_SESSION['user_role'] === $roles;
}

/**
 * Redirige si l'utilisateur n'a pas le bon rôle
 * 
 * @param string|array $required_roles Rôle(s) requis
 * @param string $redirect_url URL de redirection
 */
function require_role($required_roles, $redirect_url = '/dashboard.php') {
    if (!has_role($required_roles)) {
        header('Location: ' . $redirect_url);
        exit;
    }
}


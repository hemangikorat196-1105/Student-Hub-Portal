<?php
// ============================================================================
// Student Hub Portal - FAQs JSON API Endpoint
// Practical 6, 13
// ============================================================================

require_once __DIR__ . '/../includes/api_bootstrap.php';
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../includes/response.php';

$jsonPath = __DIR__ . '/../../data/faqs.json';
if (!file_exists($jsonPath)) {
    $jsonPath = __DIR__ . '/../../faqs.json';
}

if (!file_exists($jsonPath)) {
    json_response(500, false, "FAQs data source not found.");
}

$raw = file_get_contents($jsonPath);
$faqs = json_decode($raw, true);

if (!is_array($faqs)) {
    json_response(500, false, "Corrupted FAQs data.");
}

$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search'] ?? $_GET['q'] ?? '');

if ($category !== '') {
    $faqs = array_values(array_filter($faqs, function($f) use ($category) {
        return strcasecmp($f['category'] ?? '', $category) === 0;
    }));
}

if ($search !== '') {
    $searchLower = strtolower($search);
    $faqs = array_values(array_filter($faqs, function($f) use ($searchLower) {
        $q = strtolower($f['question'] ?? '');
        $a = strtolower($f['answer'] ?? '');
        return (strpos($q, $searchLower) !== false) || (strpos($a, $searchLower) !== false);
    }));
}

json_response(200, true, "FAQs retrieved successfully", $faqs, ['count' => count($faqs)]);

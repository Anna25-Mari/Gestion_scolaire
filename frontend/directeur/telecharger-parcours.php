<?php
require_once __DIR__ . '/../../backend/includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/PdfWriter.php';

$pdo = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: eleves.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT e.*, c.nom AS classe_nom, c.niveau AS classe_niveau, cy.nom AS cycle_nom
    FROM eleves e
    LEFT JOIN classes c ON e.classe_id = c.id
    LEFT JOIN cycles cy ON c.cycle_id = cy.id
    WHERE e.id = ?
');
$stmt->execute([$id]);
$eleve = $stmt->fetch();

if (!$eleve) {
    header('Location: eleves.php');
    exit;
}

$dateNaissance = $eleve['date_naissance'] ? strtotime($eleve['date_naissance']) : null;
$dateInscription = strtotime($eleve['date_inscription']);
$ancienneteAnnees = floor((time() - $dateInscription) / (365.25 * 86400));
$ancienneteMois = floor(((time() - $dateInscription) / (30.44 * 86400)) % 12);

$pdf = new PdfWriter();
$pdf->setFooter('Complexe Scolaire ANNA — Document généré le ' . date('d/m/Y à H:i'));

$pdf->headerBand('COMPLEXE SCOLAIRE ANNA', "PARCOURS DE L'ÉLÈVE DANS L'ÉTABLISSEMENT");

$pdf->sectionTitle("IDENTITÉ DE L'ÉLÈVE");
$pdf->keyValue('Nom', $eleve['nom']);
$pdf->keyValue('Prénom', $eleve['prenom']);
$pdf->keyValue('Sexe', $eleve['sexe'] === 'M' ? 'Masculin (Garçon)' : 'Féminin (Fille)');
$pdf->keyValue('Date de naissance', $dateNaissance ? date('d/m/Y', $dateNaissance) : 'Non renseignée');
$pdf->keyValue('Adresse', $eleve['adresse'] ?: 'Non renseignée');

$pdf->spacer(6);
$pdf->sectionTitle('PARCOURS SCOLAIRE');
$pdf->keyValue("Date d'inscription", date('d/m/Y', $dateInscription));
$pdf->keyValue('Ancienneté', trim(($ancienneteAnnees > 0 ? $ancienneteAnnees . ' an(s) ' : '') . ($ancienneteMois > 0 ? $ancienneteMois . ' mois' : '')) ?: "Moins d'un mois");
$pdf->keyValue('Salle / Classe actuelle', $eleve['classe_nom'] ?: 'Non assigné');
$pdf->keyValue('Cycle', $eleve['cycle_nom'] ?: '-');
$pdf->keyValue('Niveau', $eleve['classe_niveau'] ?: '-');

$pdf->spacer(6);
$pdf->sectionTitle('TUTEUR / PARENT');
$pdf->keyValue('Nom du tuteur', $eleve['parent_nom'] ?: 'Non renseigné');
$pdf->keyValue('Téléphone du tuteur', $eleve['parent_tel'] ?: 'Non renseigné');

$pdf->note('');
$pdf->note("Ce document a été généré automatiquement par le système de gestion scolaire.");

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="parcours_eleve_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($eleve['nom'] . '_' . $eleve['prenom'])) . '.pdf"');
header('Cache-Control: no-store');

$tmp = tempnam(sys_get_temp_dir(), 'pdf_');
$pdf->save($tmp);
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
@unlink($tmp);
exit;

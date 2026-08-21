<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/XlsxWriter.php';

requireAdmin();

$writer = new XlsxWriter();

$writer->addSheet('Cycles', [
    ['Nom', 'Code', 'Description'],
    ['Maternelle', 'MAT', 'Cycle maternelle'],
    ['Primaire', 'PRI', 'Cycle primaire'],
    ['Secondaire', 'SEC', 'Cycle secondaire']
]);

$writer->addSheet('Classes', [
    ['Nom', 'Cycle', 'Niveau', 'Capacite', 'Description'],
    ['6ème A', 'Secondaire', '6ème', 40, ''],
    ['CM2 B', 'Primaire', 'CM2', 35, '']
]);

$writer->addSheet('Eleves', [
    ['Nom', 'Prenom', 'Date naissance', 'Sexe', 'Classe', 'Parent nom', 'Parent tel', 'Adresse'],
    ['MBALLA', 'Jean', '15/03/2013', 'M', '6ème A', 'MBALLA Pierre', '690000001', 'Yaoundé'],
    ['NGONO', 'Marie', '22/07/2014', 'F', 'CM2 B', 'NGONO Alice', '691000002', '']
]);

$writer->addSheet('Enseignants', [
    ['Nom', 'Prenom', 'Email', 'Telephone', 'Specialite', 'Date embauche', 'Classes'],
    ['KAMGA', 'Paul', 'p.kamga@anna.com', '677000003', 'Mathematiques', '01/09/2022', '6ème A, CM2 B'],
    ['FOTSO', 'Alice', '', '699000004', 'Francais', '', '6ème A']
]);

$tmpFile = tempnam(sys_get_temp_dir(), 'modele_');
$writer->save($tmpFile . '.xlsx');
unlink($tmpFile);
$xlsxFile = $tmpFile . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="modele_import_anna.xlsx"');
header('Content-Length: ' . filesize($xlsxFile));
header('Cache-Control: no-store');

readfile($xlsxFile);
@unlink($xlsxFile);
exit;

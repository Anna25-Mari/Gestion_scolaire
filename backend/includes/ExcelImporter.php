<?php
require_once __DIR__ . '/XlsxReader.php';

/**
 * Import des cycles, classes, élèves et enseignants depuis un fichier .xlsx.
 *
 * Structure attendue du classeur (4 onglets, en-têtes sur la ligne 1) :
 *  - Onglet "Cycles"      : Nom | Code | Description
 *  - Onglet "Classes"     : Nom | Cycle | Niveau | Capacité | Description
 *  - Onglet "Eleves"      : Nom | Prenom | Date naissance | Sexe | Classe | Parent nom | Parent tel | Adresse
 *  - Onglet "Enseignants" : Nom | Prenom | Email | Telephone | Specialite | Date embauche | Classes
 */
class ExcelImporter
{
    public static function import($xlsxPath, $pdo)
    {
        $reader = new XlsxReader($xlsxPath);

        $report = [
            'cycles' => self::emptyCounters(),
            'classes' => self::emptyCounters(),
            'eleves' => self::emptyCounters(),
            'enseignants' => self::emptyCounters()
        ];

        $pdo->beginTransaction();

        try {
            self::importCycles($reader, $pdo, $report['cycles']);
            self::importClasses($reader, $pdo, $report['classes']);
            self::importEleves($reader, $pdo, $report['eleves']);
            self::importEnseignants($reader, $pdo, $report['enseignants']);
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return $report;
    }

    private static function emptyCounters()
    {
        return ['inserted' => 0, 'updated' => 0, 'ignored' => 0, 'errors' => []];
    }

    // ------------------------------------------------------------------
    // Cycles : Nom | Code | Description
    // ------------------------------------------------------------------
    private static function importCycles($reader, $pdo, &$counters)
    {
        $rows = $reader->getRows('Cycles');
        if ($rows === null) {
            return;
        }

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;
            $nom = self::clean($row[0] ?? '');
            if ($nom === '') {
                if (self::rowIsEmpty($row)) { $counters['ignored']++; continue; }
                $counters['errors'][] = ['ligne' => $line, 'message' => 'Nom du cycle manquant.'];
                continue;
            }

            $code = self::clean($row[1] ?? '');
            $description = self::clean($row[2] ?? '');

            $stmt = $pdo->prepare('SELECT id FROM cycles WHERE LOWER(nom) = LOWER(?)');
            $stmt->execute([$nom]);
            $existing = $stmt->fetch();

            if ($existing) {
                $update = $pdo->prepare('UPDATE cycles SET code = ?, description = ? WHERE id = ?');
                $update->execute([$code ?: null, $description ?: null, $existing['id']]);
                $counters['updated']++;
            } else {
                $insert = $pdo->prepare('INSERT INTO cycles (nom, code, description) VALUES (?, ?, ?)');
                $insert->execute([$nom, $code ?: null, $description ?: null]);
                $counters['inserted']++;
            }
        }
    }

    // ------------------------------------------------------------------
    // Classes : Nom | Cycle | Niveau | Capacité | Description
    // ------------------------------------------------------------------
    private static function importClasses($reader, $pdo, &$counters)
    {
        $rows = $reader->getRows('Classes');
        if ($rows === null) {
            return;
        }

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;
            $nom = self::clean($row[0] ?? '');
            if ($nom === '') {
                if (self::rowIsEmpty($row)) { $counters['ignored']++; continue; }
                $counters['errors'][] = ['ligne' => $line, 'message' => 'Nom de la classe manquant.'];
                continue;
            }

            $cycleNom = self::clean($row[1] ?? '');
            $niveau = self::clean($row[2] ?? '');
            $capacite = (int)($row[3] ?? 0);
            $description = self::clean($row[4] ?? '');

            try {
                $cycleId = null;
                if ($cycleNom !== '') {
                    $cycleId = self::resolveCycleId($pdo, $cycleNom);
                }

                $stmt = $pdo->prepare('SELECT id FROM classes WHERE LOWER(nom) = LOWER(?)');
                $stmt->execute([$nom]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $update = $pdo->prepare('UPDATE classes SET niveau = ?, cycle_id = ?, capacite = ?, description = ? WHERE id = ?');
                    $update->execute([
                        $niveau ?: null,
                        $cycleId,
                        $capacite > 0 ? $capacite : 40,
                        $description ?: null,
                        $existing['id']
                    ]);
                    $counters['updated']++;
                } else {
                    $insert = $pdo->prepare('INSERT INTO classes (nom, niveau, cycle_id, capacite, description) VALUES (?, ?, ?, ?, ?)');
                    $insert->execute([
                        $nom,
                        $niveau ?: null,
                        $cycleId,
                        $capacite > 0 ? $capacite : 40,
                        $description ?: null
                    ]);
                    $counters['inserted']++;
                }
            } catch (Exception $e) {
                $counters['errors'][] = ['ligne' => $line, 'message' => $e->getMessage()];
            }
        }
    }

    // ------------------------------------------------------------------
    // Elèves : Nom | Prenom | Date naissance | Sexe | Classe | Parent nom | Parent tel | Adresse
    // ------------------------------------------------------------------
    private static function importEleves($reader, $pdo, &$counters)
    {
        $rows = $reader->getRows('Eleves');
        if ($rows === null) {
            return;
        }

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;
            $nom = self::clean($row[0] ?? '');
            $prenom = self::clean($row[1] ?? '');

            if ($nom === '' && $prenom === '') {
                if (self::rowIsEmpty($row)) { $counters['ignored']++; continue; }
                $counters['errors'][] = ['ligne' => $line, 'message' => 'Nom et prénom manquants.'];
                continue;
            }
            if ($nom === '' || $prenom === '') {
                $counters['errors'][] = ['ligne' => $line, 'message' => 'Nom ou prénom manquant.'];
                continue;
            }

            $dateNaissance = self::parseDate($row[2] ?? '');
            $sexe = self::parseSexe($row[3] ?? '');
            $classeNom = self::clean($row[4] ?? '');
            $parentNom = self::clean($row[5] ?? '');
            $parentTel = self::clean($row[6] ?? '');
            $adresse = self::clean($row[7] ?? '');

            try {
                $classeId = null;
                if ($classeNom !== '') {
                    $classeId = self::resolveClasseId($pdo, $classeNom);
                    if ($classeId === null) {
                        throw new Exception("Classe \"$classeNom\" introuvable (créez-la dans l'onglet Classes).");
                    }
                }

                $stmt = $pdo->prepare('SELECT id FROM eleves WHERE LOWER(nom) = LOWER(?) AND LOWER(prenom) = LOWER(?)');
                $stmt->execute([$nom, $prenom]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $update = $pdo->prepare('UPDATE eleves SET date_naissance = ?, sexe = ?, parent_nom = ?, parent_tel = ?, adresse = ?, classe_id = ? WHERE id = ?');
                    $update->execute([$dateNaissance, $sexe, $parentNom ?: null, $parentTel ?: null, $adresse ?: null, $classeId, $existing['id']]);
                    $counters['updated']++;
                } else {
                    $insert = $pdo->prepare('INSERT INTO eleves (nom, prenom, date_naissance, sexe, parent_nom, parent_tel, adresse, classe_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $insert->execute([$nom, $prenom, $dateNaissance, $sexe, $parentNom ?: null, $parentTel ?: null, $adresse ?: null, $classeId]);
                    $counters['inserted']++;
                }
            } catch (Exception $e) {
                $counters['errors'][] = ['ligne' => $line, 'message' => $e->getMessage()];
            }
        }
    }

    // ------------------------------------------------------------------
    // Enseignants : Nom | Prenom | Email | Telephone | Specialite | Date embauche | Classes
    // ------------------------------------------------------------------
    private static function importEnseignants($reader, $pdo, &$counters)
    {
        $rows = $reader->getRows('Enseignants');
        if ($rows === null) {
            return;
        }

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;
            $nom = self::clean($row[0] ?? '');
            $prenom = self::clean($row[1] ?? '');

            if ($nom === '' && $prenom === '') {
                if (self::rowIsEmpty($row)) { $counters['ignored']++; continue; }
                $counters['errors'][] = ['ligne' => $line, 'message' => 'Nom et prénom manquants.'];
                continue;
            }
            if ($nom === '' || $prenom === '') {
                $counters['errors'][] = ['ligne' => $line, 'message' => 'Nom ou prénom manquant.'];
                continue;
            }

            $email = self::clean($row[2] ?? '');
            $telephone = self::clean($row[3] ?? '');
            $specialite = self::clean($row[4] ?? '');
            $dateEmbauche = self::parseDate($row[5] ?? '');
            $classesNoms = self::splitNames($row[6] ?? '');

            try {
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Adresse email invalide.');
                }

                if ($email !== '') {
                    $checkEmail = $pdo->prepare('SELECT id FROM enseignants WHERE email = ? AND NOT (LOWER(nom) = LOWER(?) AND LOWER(prenom) = LOWER(?))');
                    $checkEmail->execute([$email, $nom, $prenom]);
                    if ($checkEmail->fetch()) {
                        throw new Exception("L'email $email est déjà utilisé par un autre enseignant.");
                    }
                }

                $stmt = $pdo->prepare('SELECT id FROM enseignants WHERE LOWER(nom) = LOWER(?) AND LOWER(prenom) = LOWER(?)');
                $stmt->execute([$nom, $prenom]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $update = $pdo->prepare('UPDATE enseignants SET email = ?, telephone = ?, specialite = ?, date_embauche = ? WHERE id = ?');
                    $update->execute([$email ?: null, $telephone ?: null, $specialite ?: null, $dateEmbauche, $existing['id']]);
                    $enseignantId = $existing['id'];
                    $counters['updated']++;
                } else {
                    $insert = $pdo->prepare('INSERT INTO enseignants (nom, prenom, email, telephone, specialite, date_embauche) VALUES (?, ?, ?, ?, ?, ?)');
                    $insert->execute([$nom, $prenom, $email ?: null, $telephone ?: null, $specialite ?: null, $dateEmbauche]);
                    $enseignantId = $pdo->lastInsertId();
                    $counters['inserted']++;
                }

                // Réassignation des classes
                $deleteLinks = $pdo->prepare('DELETE FROM enseignants_classes WHERE enseignant_id = ?');
                $deleteLinks->execute([$enseignantId]);

                foreach ($classesNoms as $classeNom) {
                    $classeId = self::resolveClasseId($pdo, $classeNom);
                    if ($classeId === null) {
                        $counters['errors'][] = ['ligne' => $line, 'message' => "Classe \"$classeNom\" introuvable pour l'enseignant $prenom $nom (affectation ignorée)."];
                        continue;
                    }
                    $link = $pdo->prepare('INSERT IGNORE INTO enseignants_classes (enseignant_id, classe_id) VALUES (?, ?)');
                    $link->execute([$enseignantId, $classeId]);
                }
            } catch (Exception $e) {
                $counters['errors'][] = ['ligne' => $line, 'message' => $e->getMessage()];
            }
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private static function resolveCycleId($pdo, $nom)
    {
        $stmt = $pdo->prepare('SELECT id FROM cycles WHERE LOWER(nom) = LOWER(?)');
        $stmt->execute([trim($nom)]);
        $cycle = $stmt->fetch();
        if ($cycle) {
            return $cycle['id'];
        }
        $insert = $pdo->prepare('INSERT INTO cycles (nom) VALUES (?)');
        $insert->execute([trim($nom)]);
        return $pdo->lastInsertId();
    }

    private static function resolveClasseId($pdo, $nom)
    {
        $stmt = $pdo->prepare('SELECT id FROM classes WHERE LOWER(nom) = LOWER(?)');
        $stmt->execute([trim($nom)]);
        $classe = $stmt->fetch();
        return $classe ? $classe['id'] : null;
    }

    private static function clean($value)
    {
        return trim(preg_replace('/\s+/u', ' ', (string)$value));
    }

    private static function splitNames($value)
    {
        $parts = preg_split('/[,;\/|]+/u', (string)$value);
        $result = [];
        foreach ($parts as $part) {
            $part = self::clean($part);
            if ($part !== '') {
                $result[] = $part;
            }
        }
        return $result;
    }

    private static function parseSexe($value)
    {
        $v = self::normalize(mb_strtolower(trim((string)$value)));
        if (in_array($v, ['f', 'fille', 'feminin', 'feminine'], true)) {
            return 'F';
        }
        return 'M';
    }

    private static function parseDate($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        // Format ISO déjà converti par le lecteur (cellules au format date Excel)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 10);
        }
        // Formats français : jj/mm/aaaa ou jj-mm-aaaa
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $m)) {
            $date = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            if (checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
                return $date;
            }
            return null;
        }
        return null;
    }

    private static function normalize($text)
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        return strtolower(trim($text));
    }

    private static function rowIsEmpty(array $row)
    {
        foreach ($row as $cell) {
            if (self::clean($cell) !== '') {
                return false;
            }
        }
        return true;
    }
}

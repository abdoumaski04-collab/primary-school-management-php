<?php
$files = [
    'dashboard_directeur.php',
    'dashboard_enseignant.php',
    'dashboard_parent.php',
    'dashboard_surveillant.php',
    'header.php',
    'index.php',
    'inscription.php',
    'logout.php',
    'session_check.php',
    'eleves/fiche.php'
];

foreach($files as $f) {
    if(file_exists($f)) {
        $c = file_get_contents($f);
        $c_fixed = utf8_decode($c);
        file_put_contents($f, $c_fixed);
        echo "Fixed $f\n";
    }
}
?>

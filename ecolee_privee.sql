-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 15 avr. 2026 à 03:33
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecolee_privee`
--

-- --------------------------------------------------------

--
-- Structure de la table `absences`
--

CREATE TABLE `absences` (
  `id` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `date` date NOT NULL,
  `demi_journee` enum('matin','apres_midi') NOT NULL,
  `motif` text DEFAULT NULL,
  `statut` enum('signalee','justifiee','non_justifiee','archivee') NOT NULL,
  `date_signalement` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

CREATE TABLE `alertes` (
  `id` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `date_generation` date NOT NULL,
  `nb_absences_nj` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bulletins`
--

CREATE TABLE `bulletins` (
  `id` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `trimestre` int(11) NOT NULL,
  `moyenne_generale` float DEFAULT NULL,
  `appreciation` text DEFAULT NULL,
  `statut` enum('en_cours','genere','publie','consulte') NOT NULL,
  `date_generation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `niveau` varchar(50) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `effectif_max` int(11) DEFAULT 30,
  `annee_scolaire` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `niveau`, `nom`, `effectif_max`, `annee_scolaire`) VALUES
(1, 'cp', 'a', 30, '2026'),
(2, 'CE1', 'a', 30, '2026-2027');

-- --------------------------------------------------------

--
-- Structure de la table `creneaux`
--

CREATE TABLE `creneaux` (
  `id` int(11) NOT NULL,
  `id_edt` int(11) NOT NULL,
  `jour` varchar(20) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_enseignant` int(11) NOT NULL,
  `salle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dossiers_financiers`
--

CREATE TABLE `dossiers_financiers` (
  `id` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `solde` double NOT NULL,
  `statut` varchar(50) NOT NULL,
  `date_creation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dossiers_financiers`
--

INSERT INTO `dossiers_financiers` (`id`, `id_eleve`, `solde`, `statut`, `date_creation`) VALUES
(1, 1, 0, 'actif', '2026-04-14'),
(2, 2, 0, 'actif', '2026-04-15');

-- --------------------------------------------------------

--
-- Structure de la table `dossiers_inscription`
--

CREATE TABLE `dossiers_inscription` (
  `id` int(11) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `statut` enum('soumis','verifie','accepte','refuse') NOT NULL,
  `pieces_jointes` text DEFAULT NULL,
  `date_soumission` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dossiers_inscription`
--

INSERT INTO `dossiers_inscription` (`id`, `id_parent`, `statut`, `pieces_jointes`, `date_soumission`) VALUES
(1, 5, 'accepte', 'hdhsjhdjkd', '2026-04-14'),
(2, 5, 'accepte', 'please accept my request', '2026-04-14'),
(3, 6, 'refuse', 'sndnd', '2026-04-15');

-- --------------------------------------------------------

--
-- Structure de la table `eleves`
--

CREATE TABLE `eleves` (
  `id` int(11) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `statut` enum('preInscrit','inscrit','suspendu','radie','diplome') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `eleves`
--

INSERT INTO `eleves` (`id`, `id_classe`, `id_parent`, `nom`, `prenom`, `date_naissance`, `statut`) VALUES
(1, 1, 5, 'maski', 'ilyass', '2026-04-13', 'preInscrit'),
(2, 2, 5, 'faris', 'sakid', '2026-04-03', 'preInscrit');

-- --------------------------------------------------------

--
-- Structure de la table `emplois_du_temps`
--

CREATE TABLE `emplois_du_temps` (
  `id` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `annee_scolaire` varchar(20) NOT NULL,
  `statut` enum('brouillon','publie') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `emplois_du_temps`
--

INSERT INTO `emplois_du_temps` (`id`, `id_classe`, `annee_scolaire`, `statut`) VALUES
(1, 1, '2026', 'brouillon');

-- --------------------------------------------------------

--
-- Structure de la table `enseignant_classe`
--

CREATE TABLE `enseignant_classe` (
  `id_enseignant` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `formulaires`
--

CREATE TABLE `formulaires` (
  `id` int(11) NOT NULL,
  `id_dossier` int(11) NOT NULL,
  `donnees_parent` text DEFAULT NULL,
  `est_valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formulaires`
--

INSERT INTO `formulaires` (`id`, `id_dossier`, `donnees_parent`, `est_valide`) VALUES
(1, 1, '{\"nom\":\"maski\",\"prenom\":\"ilyass\",\"date_naissance\":\"2026-04-13\"}', 0),
(2, 2, '{\"nom\":\"faris\",\"prenom\":\"sakid\",\"date_naissance\":\"2026-04-03\"}', 0),
(3, 3, '{\"nom\":\"wdi\",\"prenom\":\"fils\",\"date_naissance\":\"2026-03-30\"}', 0);

-- --------------------------------------------------------

--
-- Structure de la table `journal_audit`
--

CREATE TABLE `journal_audit` (
  `id` int(11) NOT NULL,
  `id_note` int(11) NOT NULL,
  `ancienne_valeur` float DEFAULT NULL,
  `nouvelle_valeur` float DEFAULT NULL,
  `auteur` varchar(100) NOT NULL,
  `date_modification` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `justifications`
--

CREATE TABLE `justifications` (
  `id` int(11) NOT NULL,
  `id_absence` int(11) NOT NULL,
  `date_presence_parent` date NOT NULL,
  `motif` text NOT NULL,
  `document_joint` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

CREATE TABLE `matieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `coefficient` float NOT NULL,
  `id_enseignant` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `id_expediteur` int(11) NOT NULL,
  `destinataire_role` varchar(50) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` datetime NOT NULL,
  `statut` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `id_expediteur`, `destinataire_role`, `contenu`, `date_envoi`, `statut`) VALUES
(1, 3, 'parent', 'bonsoir', '2026-04-14 21:20:33', 'envoye');

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `trimestre` int(11) NOT NULL,
  `valeur` float NOT NULL CHECK (`valeur` between 0 and 20),
  `date_evaluation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `date_creation` datetime NOT NULL,
  `est_lue` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `id_utilisateur`, `type`, `contenu`, `date_creation`, `est_lue`) VALUES
(1, 3, 'Inscription', 'Un nouveau dossier d\'inscription a été soumis par maski yahya', '2026-04-14 20:58:28', 0),
(2, 5, 'Inscription', 'Le dossier d\'inscription de ilyass a été vérifié et transmis à la direction pour décision finale.', '2026-04-14 20:59:09', 0),
(3, 5, 'Inscription', 'Félicitations ! Le dossier d\'inscription de ilyass a été accepté. L\'enfant est désormais pré-inscrit.', '2026-04-14 21:00:00', 0),
(4, 3, 'Inscription', 'Un nouveau dossier d\'inscription a été soumis par maski yahya', '2026-04-14 21:14:26', 0),
(5, 5, 'Inscription', 'Le dossier d\'inscription de sakid a été vérifié et transmis à la direction pour décision finale.', '2026-04-14 21:18:21', 0),
(6, 4, 'Nouveau Message', 'Un nouveau message vous a été envoyé par Jean Surv.', '2026-04-14 21:20:33', 0),
(7, 5, 'Nouveau Message', 'Un nouveau message vous a été envoyé par Jean Surv.', '2026-04-14 21:20:33', 0),
(8, 3, 'Inscription', 'Un nouveau dossier d\'inscription a été soumis par maski siham', '2026-04-15 01:54:04', 0),
(9, 6, 'Inscription', 'Le dossier d\'inscription de fils a été refusé. Veuillez contacter l\'administration.', '2026-04-15 01:57:44', 0),
(10, 5, 'Inscription', 'Félicitations ! Le dossier d\'inscription de sakid a été accepté. L\'enfant est désormais pré-inscrit.', '2026-04-15 02:01:31', 0);

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(11) NOT NULL,
  `id_dossier` int(11) NOT NULL,
  `montant` double NOT NULL,
  `mode` enum('carte','cash') NOT NULL,
  `date_transaction` date NOT NULL,
  `statut` enum('en_attente','valide','refuse') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id_session` varchar(128) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_debut` datetime NOT NULL,
  `derniere_activite` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sessions`
--

INSERT INTO `sessions` (`id_session`, `id_utilisateur`, `date_debut`, `derniere_activite`) VALUES
('rf24jinnvid2cspkj4kdh9umuc', 7, '2026-04-15 02:31:39', '2026-04-15 02:32:08');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('directeur','enseignant','surveillant','parent','eleve') NOT NULL,
  `est_connecte` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `est_connecte`) VALUES
(1, 'Boss', 'Directeur', 'directeur@ecole.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'directeur', 0),
(2, 'Prof', 'Maths', 'enseignant@ecole.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant', 0),
(3, 'Surv', 'Jean', 'surveillant@ecole.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'surveillant', 0),
(4, 'Parent', 'Paul', 'parent@ecole.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 0),
(5, 'yahya', 'maski', 'yahyamaski@gmail.com', '$2y$10$SDWZAxM7GLzqLwUjWZPWdeP9X91Nx.9F34q7tsNcLrtqVHaEqn0/6', 'parent', 0),
(6, 'siham', 'maski', 'sihammaski@gmail.com', '$2y$10$HVNzqDPhnYxc/BsObscM9es2OHcPOXPmeAkI.7jv2MxX8GQWAWUIS', 'parent', 0),
(7, 'acher', 'hatimi', 'acherhatimi@gmail.com', '$2y$10$lUZp.rJsB7dOOX1Hm8dPNuUAv2A/P4TEvfp6nRzppeI9NNTCfQpWC', 'eleve', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `absences`
--
ALTER TABLE `absences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `bulletins`
--
ALTER TABLE `bulletins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `creneaux`
--
ALTER TABLE `creneaux`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_edt` (`id_edt`),
  ADD KEY `id_matiere` (`id_matiere`),
  ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Index pour la table `dossiers_financiers`
--
ALTER TABLE `dossiers_financiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `dossiers_inscription`
--
ALTER TABLE `dossiers_inscription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_parent` (`id_parent`);

--
-- Index pour la table `eleves`
--
ALTER TABLE `eleves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_classe` (`id_classe`),
  ADD KEY `id_parent` (`id_parent`);

--
-- Index pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_classe` (`id_classe`);

--
-- Index pour la table `enseignant_classe`
--
ALTER TABLE `enseignant_classe`
  ADD PRIMARY KEY (`id_enseignant`,`id_classe`),
  ADD KEY `id_classe` (`id_classe`);

--
-- Index pour la table `formulaires`
--
ALTER TABLE `formulaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_dossier` (`id_dossier`);

--
-- Index pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_note` (`id_note`);

--
-- Index pour la table `justifications`
--
ALTER TABLE `justifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_absence` (`id_absence`);

--
-- Index pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_expediteur` (`id_expediteur`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_eleve` (`id_eleve`),
  ADD KEY `id_matiere` (`id_matiere`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_dossier` (`id_dossier`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id_session`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `absences`
--
ALTER TABLE `absences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `alertes`
--
ALTER TABLE `alertes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bulletins`
--
ALTER TABLE `bulletins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `creneaux`
--
ALTER TABLE `creneaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `dossiers_financiers`
--
ALTER TABLE `dossiers_financiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `dossiers_inscription`
--
ALTER TABLE `dossiers_inscription`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `eleves`
--
ALTER TABLE `eleves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `formulaires`
--
ALTER TABLE `formulaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `justifications`
--
ALTER TABLE `justifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matieres`
--
ALTER TABLE `matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absences`
--
ALTER TABLE `absences`
  ADD CONSTRAINT `absences_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleves` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleves` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bulletins`
--
ALTER TABLE `bulletins`
  ADD CONSTRAINT `bulletins_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleves` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `creneaux`
--
ALTER TABLE `creneaux`
  ADD CONSTRAINT `creneaux_ibfk_1` FOREIGN KEY (`id_edt`) REFERENCES `emplois_du_temps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `creneaux_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `creneaux_ibfk_3` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dossiers_financiers`
--
ALTER TABLE `dossiers_financiers`
  ADD CONSTRAINT `dossiers_financiers_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleves` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dossiers_inscription`
--
ALTER TABLE `dossiers_inscription`
  ADD CONSTRAINT `dossiers_inscription_ibfk_1` FOREIGN KEY (`id_parent`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `eleves`
--
ALTER TABLE `eleves`
  ADD CONSTRAINT `eleves_ibfk_1` FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eleves_ibfk_2` FOREIGN KEY (`id_parent`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD CONSTRAINT `emplois_du_temps_ibfk_1` FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `enseignant_classe`
--
ALTER TABLE `enseignant_classe`
  ADD CONSTRAINT `enseignant_classe_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enseignant_classe_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `formulaires`
--
ALTER TABLE `formulaires`
  ADD CONSTRAINT `formulaires_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossiers_inscription` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD CONSTRAINT `journal_audit_ibfk_1` FOREIGN KEY (`id_note`) REFERENCES `notes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `justifications`
--
ALTER TABLE `justifications`
  ADD CONSTRAINT `justifications_ibfk_1` FOREIGN KEY (`id_absence`) REFERENCES `absences` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD CONSTRAINT `matieres_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`id_eleve`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossiers_financiers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

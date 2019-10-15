-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 15. Okt 2019 um 23:08
-- Server-Version: 10.1.38-MariaDB-0+deb9u1
-- PHP-Version: 7.0.33-0+deb9u3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `miner`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `clients`
--

CREATE TABLE `clients` (
  `clientId` int(11) NOT NULL,
  `token` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `jobs`
--

CREATE TABLE `jobs` (
  `jobId` int(11) NOT NULL,
  `clientId` int(11) NOT NULL,
  `puzzleId` int(11) NOT NULL,
  `startNonce` int(10) UNSIGNED NOT NULL,
  `endNonce` int(10) UNSIGNED NOT NULL,
  `finished` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `puzzles`
--

CREATE TABLE `puzzles` (
  `puzzleId` int(11) NOT NULL,
  `bitcoinBlockId` int(11) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `prevBlockHash` varchar(256) NOT NULL,
  `merkleRoot` varchar(256) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `nbits` int(10) UNSIGNED NOT NULL,
  `difficultyTarget` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `solutions`
--

CREATE TABLE `solutions` (
  `id` int(11) NOT NULL,
  `clientId` int(11) NOT NULL,
  `jobId` int(11) NOT NULL,
  `puzzleId` int(11) NOT NULL,
  `nonce` int(10) UNSIGNED NOT NULL,
  `blockHash` varchar(256) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`clientId`);

--
-- Indizes für die Tabelle `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`jobId`),
  ADD KEY `puzzleId` (`puzzleId`),
  ADD KEY `clientId` (`clientId`);

--
-- Indizes für die Tabelle `puzzles`
--
ALTER TABLE `puzzles`
  ADD PRIMARY KEY (`puzzleId`);

--
-- Indizes für die Tabelle `solutions`
--
ALTER TABLE `solutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientId` (`clientId`),
  ADD KEY `jobId` (`jobId`),
  ADD KEY `puzzleId` (`puzzleId`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `clients`
--
ALTER TABLE `clients`
  MODIFY `clientId` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `jobs`
--
ALTER TABLE `jobs`
  MODIFY `jobId` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `puzzles`
--
ALTER TABLE `puzzles`
  MODIFY `puzzleId` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `solutions`
--
ALTER TABLE `solutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`puzzleId`) REFERENCES `puzzles` (`puzzleId`),
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`clientId`) REFERENCES `clients` (`clientId`);

--
-- Constraints der Tabelle `solutions`
--
ALTER TABLE `solutions`
  ADD CONSTRAINT `solutions_ibfk_1` FOREIGN KEY (`clientId`) REFERENCES `clients` (`clientId`),
  ADD CONSTRAINT `solutions_ibfk_2` FOREIGN KEY (`jobId`) REFERENCES `jobs` (`jobId`),
  ADD CONSTRAINT `solutions_ibfk_3` FOREIGN KEY (`puzzleId`) REFERENCES `puzzles` (`puzzleId`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

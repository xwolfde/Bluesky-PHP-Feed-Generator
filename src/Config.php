<?php

/*
 * Copyright (C) 2024 xwolf
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bluesky;

class Config {
    // Array mit Konfigurationswerten
    private $config = [];
    
    /**
     * Config-Konstruktor
     * Lädt die Konfigurationswerte aus der JSON-Datei
     *
     * @param string $filePath Der Pfad zur JSON-Datei
     * @throws \Exception Wenn die Datei nicht gelesen oder die JSON-Daten ungültig sind
     */
    public function __construct($filePath = __DIR__ . '/../config.json')
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Konfigurationsdatei nicht gefunden: $filePath");
        }

        $jsonData = file_get_contents($filePath);
        $this->config = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Fehler beim Parsen der JSON-Datei: " . json_last_error_msg());
        }
    }
    /**
     * Abrufen eines Konfigurationswertes
     *
     * @param string $key Der Schlüssel des Konfigurationswertes
     * @return mixed|null Der Wert oder null, wenn der Schlüssel nicht existiert
     */
    public function get($key) {
        return $this->config[$key] ?? null;
    }

    /**
     * Alle Konfigurationswerte abrufen
     *
     * @return array Das gesamte Konfigurationsarray
     */
    public function getAll() {
        return $this->config;
    }
}


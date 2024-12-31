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

/**
 * Getting and displaying status infos
 *
 * @author xwolf
 */
require __DIR__ . '/vendor/autoload.php';

use Bluesky\FeedGenerator;
use Bluesky\Config;
use Bluesky\API;

// Loading config
$config = new Config();


$arguments = $_SERVER['argv'] ?? [];
array_shift($arguments); // Erster Eintrag ist der Skriptname, entfernen
$options = [];

// Optionen analysieren (z.B. --help oder --v)
foreach ($arguments as $arg) {
    if (str_starts_with($arg, '--')) {
        $options[] = ltrim($arg, '-');
    } else {
        $options[] = $arg;
    }
}




foreach ($options as $option) {
        match ($option) {
            'help' => print("Hilfe: Verfügbare Optionen sind:\n--help\n--v\n--config\n\n"),
            'v' => print("Version: 1.0.0\n"),
            'config' => show_config($config),
            'timeline' => get_timeline($config),
            'autorfeed' => get_authorfeed($confg),
            default => show_help()
        };
    }
if (empty($options)) {
      show_help();
}
exit;


function show_config(Config $config) {
    echo "Config:\n";
    var_dump($config->getAll());
}
/*
 * Ausgabe der Hilfetexte
 */
function show_help() {
    echo "Hilfe: Verfügbare Optionen sind:\n";
    echo "timeline: Zeige Public Timeline\n";
    echo "autorfeed Zeige Feed eines über die Config gegebenen Autors\n";
    echo "createfeed: Erstelle XRPC feed\n";
    echo "\t--config: Zeige Config\n";
    echo "\t--help: Diese Hilfe\n";
    echo "\t--v: Version\n";
}



/*
 * Erstelle Feed und gib diesen zurück
 */
function createFeed(Config $config) {
    // Routing basierend auf der URL
    $requestUri = $config->get('xrpc-endpoint'); // $_SERVER['REQUEST_URI'];


    if ($requestUri === '/') {
        echo json_encode(['status' => 'Bluesky Feed Generator is running!']);
    } elseif ($requestUri === $config->get('xrpc-endpoint')) {
        $feedGenerator = new FeedGenerator();
      //  header('Content-Type: application/json');
        echo json_encode($feedGenerator->getFeedSkeleton());
    } else {
      //  http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }

    echo "\n";
}


/*
 * Gebe Public Timeline zurück
 */
function get_timeline(Config $config) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login fehlgeschlagen. Überprüfe deinen Benutzernamen und dein Passwort.");
        }
        echo "Access Token erfolgreich abgerufen: {$token}\n";
        $timeline = $blueskyAPI->getPublicTimeline();
        echo "Öffentliche Timeline:\n";
        print_r($timeline);

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}

/*
 * Hole den Feed eines Autors
 */
function get_authorfeed(Config $config) {
     if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login fehlgeschlagen. Überprüfe deinen Benutzernamen und dein Passwort.");
        }
        echo "Access Token erfolgreich abgerufen: {$token}\n";

        if (!empty($config->get("timeline-did"))) {
            echo "Timeline of did ".$config->get("timeline-did").":\n";
            $didtimeline = $blueskyAPI->getAuthorFeed($config->get("timeline-did"));
            print_r($didtimeline);
        }
    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}



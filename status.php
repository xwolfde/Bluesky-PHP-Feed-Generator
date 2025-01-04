<?php

/*
 * Copyright (C) 2025 xwolf
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
use Bluesky\Post;
use Bluesky\Debugging;
use Bluesky\Utils;

// Loading config
$config = new Config();


// Konfiguration der Kurz- und Langoptionen
$shortopts = "hvcu:q:"; // -h, -v, -c <file>
$longopts = ["help", "version", "config", "uri:", "limit:", "tag:", "lang:", "q:"]; 

// Optionen parsen
$options = getopt($shortopts, $longopts);
$arguments = array_slice($_SERVER['argv'], count($options) + 1); // ZusÃ¤tzliche Argumente nach Optionen

// PrimÃ¤re Aktion als erstes Argument erwarten
$action = $arguments[0] ?? null;

// Verarbeitungslogik
if (isset($options['h']) || isset($options['help'])) {
    show_help();
    exit(0);
}

if (isset($options['v']) || isset($options['version'])) {
    echo "Version: 1.0.0\n";
    exit(0);
}
if (isset($options['c']) || isset($options['config'])) {
    show_config($config);
    exit(0);
}



match ($action) {
    'timeline' => get_timeline($config),
    'autorfeed' => get_authorfeed($config),
    'createFeed'    => createFeed($config),
    'getPost'   => get_post($config, $options),
    'searchPosts'    => get_searchPosts($config, $options),
    'search'    => get_searchPosts($config, $options),
    default => show_help()
};


exit;


function show_config(Config $config) {
    echo "Config:\n";
    echo Debugging::get_dump_debug($config->getAll(), false, true);
}
/*
 * Ausgabe der Hilfetexte
 */
function show_help() {
    echo "Hilfe: VerfÃ¼gbare Kommandos sind:\n";
    echo "\ttimeline: Zeige Public Timeline\n";
    echo "\tautorfeed: Zeige Feed eines Ã¼ber die Config gegebenen Autors\n";
    echo "\tcreatefeed: Erstelle XRPC feed\n";
    echo "\tgetPost: Rufe einen einzelnen Post ab und zeigt alle zugehÃ¶rigen Daten an.\n";
    echo "\t         BenÃ¶tigt die Angabe der URI mit --uri=AT-URI\n";
    echo "\tsearchPosts: Suche nach Posts\n";
    echo "\t         BenÃ¶tigt die Angabe eines Suchstrings mit --q=Suchstring\n";
    
    
    echo "\nParameter:\n";
    echo "\t--config: Zeige Config\n";
    echo "\t--help: Diese Hilfe\n";
    echo "\t--uri: AT-URI\n";
    echo "\t--v: Version\n";
    
}

/*
 * Sucht einen einzelnenm Post anhand einer URI und gibt dessen Rohdaten zurÃ¼ck
 */
function get_post(Config $config, array $options) {
    if ((!isset($options['u'])) && (!isset($options['uri']))) {
        echo "Please enter a URI for a post to look at with --uri=URI\n";
        exit;
    } 
    
    if (isset($options['u'])) {
        $uri = $options['u'];
    } else {
        $uri = $options['uri'];
    }

     if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            echo "Login fehlgeschlagen. ÃœberprÃ¼fe deinen Benutzernamen und dein Passwort.";
            return false;
        }


        $postdata = $blueskyAPI->getPosts($uri);
       
        echo Debugging::get_dump_debug($postdata, false, true);
        
        return true;

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
        return false;
    }
    
    
}

/*
 * Suche Posts
 */
function get_searchPosts(Config $config, array $options) {
    if (!isset($options['q'])) {
        echo "Please enter a URI for a post to look at with --uri=URI\n";
        exit;
    } 
    
    $search = [];
    $search['q'] = $options['q'];
    if (isset($options['limit'])) {
        $search['limit'] = $options['limit'];
    }
    if (isset($options['lang'])) {
        $search['lang'] = $options['lang'];
    }
    if (isset($options['tag'])) {
        $search['tag'] = $options['tag'];
    }

    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            echo "Login fehlgeschlagen. ÃœberprÃ¼fe deinen Benutzernamen und dein Passwort.";
            return false;
        }  


        $searchdata = $blueskyAPI->searchPosts($search);
        
        
      
        if ($searchdata['hitsTotal'] > 0) {
            foreach ($searchdata['posts'] as $post) {
                
                echo "Text       : ".$post->text."\n";
                echo "Autor      : ".$post->getAutorHandle()."\n";
                echo "Erstellt am: ".$post->createdAt."\n";
                echo "Bluesky URL: ".Utils::getBlueskyURL($post->uri, $post->getAutorHandle())."\n";
                echo "XRPC URL   : ".Utils::getBlueSkyXRPURL($post->uri)."\n";
                echo "\n";
           }
             
        } else {
            echo "Kein Treffer\n";
              echo Debugging::get_dump_debug($searchdata, false, true);
        }

        return true;
        

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
        return false;
    }

}
/*
 * Erstelle Feed und gib diesen zurÃ¼ck
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
 * Gebe Public Timeline zurÃ¼ck
 */
function get_timeline(Config $config) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login fehlgeschlagen. ÃœberprÃ¼fe deinen Benutzernamen und dein Passwort.");
        }
        $timeline = $blueskyAPI->getPublicTimeline();
        echo "Ã–ffentliche Timeline:\n";
        echo get_timeline_output($timeline, $config);

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
            throw new Exception("Login fehlgeschlagen. ÃœberprÃ¼fe deinen Benutzernamen und dein Passwort.");
        }

        if (!empty($config->get("timeline-did"))) {
            echo "Timeline of did ".$config->get("timeline-did").":\n";
            $didtimeline = $blueskyAPI->getAuthorFeed($config->get("timeline-did"));
            echo get_timeline_output($didtimeline, $config);
        }
    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}


function get_timeline_output(array $timeline, Config $config): string {
    $output = "";
    $nr = 0;
    foreach ($timeline as $entry => $feedobject) {    
        if ($entry == 'feed') {
            foreach ($feedobject as $content) {
            
                foreach ($content as $feedtype => $feeddata) {
                    if ($feedtype == 'post') {
                         $post = new Post($feeddata, $config);
                        // FÃ¼ge die Ausgabe des Posts mit getListView() zum Gesamtausgabe-String hinzu
                        $output .= $post->getListView($config) . PHP_EOL;
                        $nr += 1;
                    } else {
             //           echo "feed type $feedtype:\n";
             //           var_dump($feeddata);
                    }
                }
            
            }
        } elseif ($entry == 'cursor') {
            error_log("Cursor for feed: $feedobject " . date('Y-m-d H:i:s'));
        } else {
            error_log("Unknown schema object \"$entry\" in response timeline " . date('Y-m-d H:i:s'));
        }


    }

    return $output;
    
}


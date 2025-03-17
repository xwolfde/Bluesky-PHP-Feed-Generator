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


require __DIR__ . '/vendor/autoload.php';

use Bluesky\FeedGenerator;
use Bluesky\Config;
use Bluesky\API;
use Bluesky\Post;
use Bluesky\Debugging;
use Bluesky\Profil;
use Bluesky\Lists;
use Bluesky\StarterPack;

// Loading config
$config = new Config();


// Konfiguration der Kurz- und Langoptionen
$shortopts = "hvcu:q:"; // -h, -v, -c <file>
$longopts = ["help", "version", "config", "uri:", "limit:", "tag:", "lang:", "q:", "did:", "rawdata", "filter:"];

// Bekannte Aktionen definieren
$validActions = [
    'timeline', 'getFeed', 'autorfeed', 'getPost', 'searchPosts', 'search', 'list',
    'listindex', 'getProfil', 'getActorStarterPacks', 'getStarterPacks', 'getByLabel'
];

// Alle Argumente abrufen (außer dem Skriptnamen selbst)
$args = array_slice($_SERVER['argv'], 1);

$action = null;
$options = [];
$remainingArgs = [];

// Manuelles Parsen aller Argumente
for ($i = 0; $i < count($args); $i++) {
    $arg = $args[$i];

    // Falls das Argument eine bekannte Aktion ist und noch keine Aktion gesetzt wurde
    if (in_array($arg, $validActions, true) && !$action) {
        $action = $arg;
        continue;
    }

    // Falls das Argument eine lange Option ist (--option oder --option=value)
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', $arg, 2);
        $key = ltrim($parts[0], '-');
        
        // Option ohne Wert (z. B. --config)
        if (count($parts) === 1) {
            // Falls das nächste Argument KEIN neues `--option`, dann als Wert speichern
            if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')) {
                $options[$key] = $args[++$i]; // Wert aus dem nächsten Argument holen
            } else {
                $options[$key] = true; // Kein Wert → Option ist ein Schalter (z. B. --config)
            }
        } else {
            // Option mit `=` → Wert direkt setzen
            $options[$key] = $parts[1];
        }
        continue;
    }

    // Falls das Argument eine kurze Option ist (-o Wert oder -o)
    if (str_starts_with($arg, '-')) {
        $key = ltrim($arg, '-');

        // Falls es sich um eine Option mit Wert handelt (-q suchbegriff)
        if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '-')) {
            $options[$key] = $args[++$i];
        } else {
            $options[$key] = true; // Falls keine Werte vorhanden sind (z. B. -c)
        }
        continue;
    }

    // Falls es kein Argument ist, bleibt es als zusätzliches Argument erhalten
    $remainingArgs[] = $arg;

}


// Standardoptionen ausführen
if (isset($options['h']) || isset($options['help'])) {
    show_help();
    exit(0);
}

if (isset($options['v']) || isset($options['version'])) {
    $packageFile = 'package.json';
    $version = "unknown";

    if (file_exists($packageFile)) {
        $packageContent = file_get_contents($packageFile);
        $packageJson = json_decode($packageContent, true);

        if (isset($packageJson['version'])) {
            $version = $packageJson['version'];
        }
    }

    echo "Version: " . $version . "\n";
    exit(0);
}

if (isset($options['c']) || isset($options['config'])) {
    show_config($config);
    exit(0);
}
// Falls keine Aktion gefunden wurde, Hilfe anzeigen
if (!$action) {
    show_help();
    exit(0);
}

// Aktion ausführen
match ($action) {
    'timeline'      => get_timeline($config, $options),
    'autorfeed'     => get_feed($config, $options),
    'getFeed'       => get_feed($config, $options),
    'getPost'       => get_post($config, $options),
    'searchPosts'   => get_searchPosts($config, $options),
    'getByLabel'    => get_ByLabel($config, $options),
    'search'        => get_searchPosts($config, $options),
    'list'          => get_list($config, $options),
    'listindex'     => get_listindex($config, $options),
    'getProfil'     => get_profil($config, $options),
    'getActorStarterPacks' => get_ActorStarterPacks($config, $options),
    'getStarterPacks'  => get_StarterPacks($config, $options),
    default         => show_help()
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
    echo "Hilfe: Commands are:\n";
    echo "\ttimeline   : Display public timeline\n";
    echo "\tgetFeed    : Display feed of the given author\n";
    echo "\t             Needs either --did=DID or handle or timeline-did in config.json\n";
  //  echo "\tcreatefeed: Erstelle XRPC feed\n";
    echo "\tgetPost    : Loads a defined post.\n";
    echo "\t             Needs --uri=AT-URI\n";
    echo "\tsearchPosts: Search for posts.\n";
    echo "\t             Needs --q=Searchstring\n";
    echo "\tlistindex  : Returns the index of lists\n";
    echo "\t             Needs --did=AT Identifier\n";
    echo "\tlist       : Returns a given list\n";
    echo "\t             Needs --did=AT URI\n";
    echo "\tgetProfil  : Returns profil information of an account\n";
    echo "\t             Needs --did=DID URI or handle\n";
    echo "\tgetByLabel  : Returns posts by a given label\n";
    echo "\tgetActorStarterPacks  : Returns a Starterpack by an handle of an actor\n";
    echo "\t             Needs --uri=DID URI or handle\n";
    echo "\tgetStarterPacks  : Returns a Starterpack by an uri\n";
    echo "\t             Needs --uri=DID URI or handle\n";
    
    echo "\nParameter:\n";
    echo "\t--config: Display current config\n";
    echo "\t--help: This help\n";
    echo "\t--uri: AT-URI\n";
    echo "\t--v: Version\n";
    echo "\t--rawdata:  Displays Output as raw data (not all actions yet).\n";
}


/*
 * get_profil
 */
function get_profil(Config $config, array $options) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
    
        if (!isset($options['did'])) {
            echo "Please enter a identifier (Handle or DID of account to fetch of)  with --did=AT URI\n";
            return false;
        } 
        
        echo "Looking up Profil for DID or Handle: ".$options['did'].":\n\n";

        $search = [];
        $search['actor'] = $options['did'];

        $profil = $blueskyAPI->getProfile($search);
        $profil->setConfig($config);  
        echo $profil->getProfilView() . PHP_EOL;
    
   //     echo Debugging::get_dump_debug($profil->getRawData());

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
    
}


/*
 * search for a single post by its did uri
 * example uri: at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27
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
            echo "Login failed. Please check login and passwort in your config file.";
            return false;
        }
        
        $post = $blueskyAPI->getPosts($uri);
        if ($post === null) {
            return false;
        }
        $post->setConfig($config);
        
        if (isset($options['rawdata'])) {
            echo Debugging::get_dump_debug($post->toArray());
        } else {
            echo $post->getPostView();
        }
         
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
        echo "Please enter a search string --q=Search\n";
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
            echo "Login failed. Please check login and passwort in your config file.";
            return false;
        }  


        $searchdata = $blueskyAPI->searchPosts($search);
        
        
      
        if ($searchdata['hitsTotal'] > 0) {
            echo "Found: ".$searchdata['hitsTotal']. " hits\n";
            foreach ($searchdata['posts'] as $post) {          
                $post->setConfig($config);
                if (isset($options['rawdata'])) {
                    echo Debugging::get_dump_debug($post->toArray());
                } else {
                    echo $post->getPostView()."\n";
                }
           }
             
        } else {
            echo "Nothing found\n";
              echo Debugging::get_dump_debug($searchdata, false, true);
        }

        return true;
        

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
        return false;
    }

}

function get_ByLabel(Config $config, array $options) {
    if (!isset($options['q'])) {
        echo "Please enter a label search string --q=Label\n";
        exit;
    } 
    
    $search = [];
    $search['q'] = '#'.$options['q'];
    $search['label'] = $options['q']; // Statt 'q' wird hier gezielt nach Labels gesucht
    if (isset($options['limit'])) {
        $search['limit'] = $options['limit'];
    }
    
    // Bluesky-API Login
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            echo "Login failed. Please check login and password in your config file.";
            return false;
        }  

        // API-Abfrage für Posts mit einem bestimmten Label
        $searchdata = $blueskyAPI->searchPosts($search);
        
        if (!empty($options['filter'])) {
            $searchdata = filter_posts($searchdata, $options['filter'], $config);
        }
        
        if ($searchdata['hitsTotal'] > 0) {
            
            foreach ($searchdata['posts'] as $post) {          
                $post->setConfig($config);
                if (isset($options['rawdata'])) {
                    echo Debugging::get_dump_debug($post->toArray());
                } else {
                    echo $post->getPostView()."\n";
                }
            }
            echo "Tatal hits for search: ".$searchdata['hitsTotal']." hits\n";
            if (isset($searchdata['blocked'])) {
                echo "Blocked: ".$searchdata['blocked']."\n";
            }
        } else {
            echo "Nothing found\n";
            echo Debugging::get_dump_debug($searchdata, false, true);
        }

        return true;
    } else {
        echo "No Bluesky account in config.json, therefore stopping\n";
        return false;
    }
}

/*
 * Nehme eine Liste an Post und filtere ssie gegen eine Liste an 
 * Filtereigenschaften aus einer Datei
 */
function filter_posts(array $data, string $filePath, Config $config) {
      if (!file_exists($filePath)) {
            echo "No file for Filter args found or invalid filename\n";
            return $data;
        }

        $jsonData = file_get_contents($filePath);
        $filterargs = json_decode($jsonData, true);
        $result = $data;
        $result['posts'] = [];
        $blockeduser = 0;
        $blocked = 0;
        echo Debugging::get_dump_debug($filterargs);
        
        
        // First filter for accounts
        if (!empty($filterargs['block-user'])) {
            $busers = $filterargs['block-user'];
            $result['posts'] = [];
            foreach ($data['posts'] as $post) {
               //  $post->setConfig($config);
                $profil = $post->autor;
                
                
                if (in_array($profil->handle, $busers)) {
                    echo "Post from user ".$profil->handle." blocked.\n";
                    $blockeduser++;
                    $blocked++;
                } else {
                    $result['posts'][] = $post;
                }
            }
            if ($blockeduser>0) {
                $data['posts'] = $result['posts'];
                $data['blocked'] = $blocked;
                $data['blockeduserposts'] = $blockeduser;
            }
        }
        
        // then filter for special patterns
        if (!empty($filterargs['block-patterns'])) {
            $blockedpatterns = 0;
            $bpatterns = $filterargs['block-patterns'];
            $result['posts'] = [];
            foreach ($data['posts'] as $post) {
               //  $post->setConfig($config);
                $text = $post->text;
                
                if (checkPatterns($text, $bpatterns)) {
                    echo "Post blocked cause of pattern.\n";
                    $blockedpatterns++;
                    $blocked++;
                } else {
                    $result['posts'][] = $post;
                }
            }
            if ($blockedpatterns>0) {
                $data['posts'] = $result['posts'];
                $data['blocked'] = $blocked;
                $data['blockedpatterns'] = $blockedpatterns;
            }
        }
        // filter posts that only consists out of hashtags + url
        if ((!empty($filterargs['rules'])) && ($filterargs['rules']['hashtagcloud'] == true)) {
            $blockedhashcloud = 0;
            $bpatterns = $filterargs['block-patterns'];
            $result['posts'] = [];
            foreach ($data['posts'] as $post) {
               
                $text = $post->text;
                
                if (isOnlyHashtagsAndURL($text)) {
                    echo "Post blocked cause of tagcloud.\n";
                    $blockedhashcloud++;
                    $blocked++;
                } else {
                    $result['posts'][] = $post;
                }
            }
            if ($blockedhashcloud>0) {
                $data['posts'] = $result['posts'];
                $data['blockedhashcloud'] = $blockedhashcloud;
                $data['blocked'] = $blocked;
            }
        }
        return $data;
}

/*
 * Prüfung nach POsts, die nur aus Hashtags und einer URL bestehen
 */
function isOnlyHashtagsAndURL(string $text): bool {
    return preg_match('/^\s*(?:#\w+\s*)*(https?:\/\/\S+)?(?:\s*#\w+)*\s*$/', trim($text));    
}
/**
 * Funktion prüft, ob ein Begriff oder Regex aus $patterns im $text vorkommt.
 */
function checkPatterns(string $text, array $patterns): bool {
    foreach ($patterns as $pattern) {
        // Prüfen, ob es sich um einen regulären Ausdruck handelt (Start/Ende mit `/` oder `#`)
        if (@preg_match($pattern, '') !== false) { 
            if (preg_match($pattern, $text)) {
                return true;
            }
        } else {
            // Falls es kein regulärer Ausdruck ist, prüfe normalen String
            if (str_contains($text, $pattern)) {
                return true;
            }
        }
    }
    return false;
}

/*
 * Erstelle Feed und gib diesen zurÃƒÂ¼ck
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
 * Gebe Public Timeline zurÃƒÂ¼ck
 */
function get_timeline(Config $config, array $options) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
        $timeline = $blueskyAPI->getPublicTimeline();
        echo "Timeline:\n";
        echo get_timeline_output($timeline, $config);
        

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}

/*
 * Hole den Feed eines Autors
 */
function get_feed(Config $config, array $options) {
     if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
       
        if (!empty($options['did'])) {
            echo "Timeline of did ".$options['did'].":\n";
            $didtimeline = $blueskyAPI->getAuthorFeed($options['did']);
            echo get_timeline_output($didtimeline, $config);
        } elseif (!empty($config->get("timeline-did"))) {
            echo "Timeline of did ".$config->get("timeline-did").":\n";
            $didtimeline = $blueskyAPI->getAuthorFeed($config->get("timeline-did"));
            echo get_timeline_output($didtimeline, $config);
        } else {
            echo "No DID for timeline in params (--did=DID) or in config.json\nPlease enter a value like: timeline-did=DID\n";
        }
    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}


function get_timeline_output(array $timeline, Config $config): string {
    $output = "";
    foreach ($timeline as $entry => $feedobject) {    
        if ($entry == 'feed') {
            foreach ($feedobject as $content) {
            
                foreach ($content as $feedtype => $feeddata) {
                    if ($feedtype == 'post') {
                        $post = new Post($feeddata);
                        $post->setConfig($config);
                        $output .= $post->getPostView() . PHP_EOL;
                    } else {
                 //       echo "feed type $feedtype:\n";
                 //       echo Debugging::get_dump_debug($feeddata, false, true);
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
/*
 * Get a StarterPack by a given uri
 */
function get_StarterPacks(Config $config, array $options) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
      
        if (isset($options['u'])) {
            $uri = $options['u'];
        } else {
            $uri = $options['uri'];
        }
         if (!isset($uri)) {
                echo "Please enter Starterpack identifier (at-uri)  with --uri=AT Identifier\n";
                return false;
        } 
        
        
        $starterpacks = $blueskyAPI->getStarterPacks($uri);
        if (empty($starterpacks)) {
            echo "No StarterPack by the uri found.\n";
            return;
        }
        
        foreach ($starterpacks as $starterpack) {
            echo $starterpack->getStarterPackView() . PHP_EOL;
        }

    
    } else {
       echo "No bluesky account in config.json, therfor stopping\n";
    }
}


/*
 * Get StarterPacks by a User Handle
 */
function get_ActorStarterPacks(Config $config, array $options) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
    
        if (!isset($options['uri'])) {
            echo "Please enter a identifier (Handle or DID of account to fetch of)  with --uri=AT URI\n";
            return false;
        } 

        $search = [];
        $search['actor'] = $options['uri'];

        $starterpackcontainer = $blueskyAPI->getActorStarterPacks($search);

        if (empty($starterpackcontainer)) {
            echo "No StarterPack by the uri found.\n";
            return;
        }
        
        foreach ($starterpackcontainer['starterpacks'] as $num => $starterpack) {
   //         echo Debugging::get_dump_debug($starterpack);
            
            echo $starterpack->getStarterPackView() . PHP_EOL;
        }
        
    // echo Debugging::get_dump_debug($profil);

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
    
}


/*
 * Get a index of lists
 */
function get_listindex(Config $config, array $options) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
    
        if (!isset($options['did'])) {
            echo "Please enter list identifier (at-uri)  with --did=AT Identifier\n";
            return false;
        } 

        $search = [];
        $search['actor'] = $options['did'];
        if (isset($options['limit'])) {
            $search['limit'] = $options['limit'];
        }
        $listdata = $blueskyAPI->getLists($search);
        if (empty($listdata)) {
            echo "No list followed by actor found.\n";
            return;
        }
        
        foreach ($listdata as $list) {
            echo $list->getListsView() . PHP_EOL;
        }
                
        
       

       
    
    } else {
       echo "No bluesky account in config.json, therfor stopping\n";
    }
}
/*
 * Get a list
 */
function get_list(Config $config, array $options) {
    if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
        }
    
        if (!isset($options['did'])) {
            echo "Please enter list identifier (at-uri)  with --did=AT URI\n";
            return false;
        } 

        $search = [];
        $search['list'] = $options['did'];
        if (isset($options['limit'])) {
            $search['limit'] = $options['limit'];
        }
        if (isset($options['cursor'])) {
            $search['cursor'] = $options['cursor'];
        }
        $listdata = $blueskyAPI->getList($search);
        if (!$listdata) {
            echo "List not found\n";
            return false;
        }
        
        echo $listdata['list']->getListsView() . PHP_EOL;
        if (count($listdata['items']) > 0) {
            // Array with all list items
            $itemlist = $listdata['items'];

            if ($listdata['cursor']) {
                // list not complete yet. have to make requests until cursor is empty
                $cursor = $listdata['cursor'];
                while(!empty($cursor)) {
                    echo "New request with cursor = {$cursor}\n";
                    $search['cursor'] = $cursor;
                    $newListdata = $blueskyAPI->getList($search);

                    // Wenn keine neuen Daten oder ungÃ¼ltige Antwort, abbrechen
                    if (!$newListdata) {
                        echo "No further data received.\n";
                        break;
                    }

                    // Cursor aktualisieren
                    $cursor = $newListdata['cursor'];

                    // Neu geladene Items an $itemlist anhÃ¤ngen
                    $itemlist = array_merge($itemlist, $newListdata['items']);
                }
            }
            // Jetzt sind alle Eintraege in $itemlist
            echo "Total items in list: " . count($itemlist) . "\n";

            // Beispielhafter Durchlauf aller Items
            foreach ($itemlist as $item => $user) {
               echo $item.". ".$user['subject']->handle." - ".$user['subject']->displayName." ( {$user['uri']} )\n";
            }
        }
        
     //   echo Debugging::get_dump_debug($listdata['items'], false, true);
    

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}

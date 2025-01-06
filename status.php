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

// Loading config
$config = new Config();


// Konfiguration der Kurz- und Langoptionen
$shortopts = "hvcu:q:"; // -h, -v, -c <file>
$longopts = ["help", "version", "config", "uri:", "limit:", "tag:", "lang:", "q:","did:"]; 

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



match ($action) {
    'timeline'      => get_timeline($config),
    'autorfeed'     => get_authorfeed($config),
 //   'createFeed'    => createFeed($config),
    'getPost'       => get_post($config, $options),
    'searchPosts'   => get_searchPosts($config, $options),
    'search'        => get_searchPosts($config, $options),
    'list'          => get_list($config, $options),
    'listindex'     => get_listindex($config, $options),
    'getProfil'     => get_profil($config, $options),
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
    echo "\tautorfeed  : Display feed of the given author\n";
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
    
    echo "\nParameter:\n";
    echo "\t--config: Display current config\n";
    echo "\t--help: This help\n";
    echo "\t--uri: AT-URI\n";
    echo "\t--v: Version\n";
    
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

        $search = [];
        $search['actor'] = $options['did'];

        $profil = $blueskyAPI->getProfile($search);
        $profil->setConfig($config);  
        echo $profil->getProfilView() . PHP_EOL;
    

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
        $post->setConfig($config);
        echo $post->getPostView();

         
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
            echo "Login failed. Please check login and passwort in your config file.";
            return false;
        }  


        $searchdata = $blueskyAPI->searchPosts($search);
        
        
      
        if ($searchdata['hitsTotal'] > 0) {
            echo "Found: ".$searchdata['hitsTotal']. " hits\n";
            foreach ($searchdata['posts'] as $post) {          
                $post->setConfig($config);
                echo $post->getPostView()."\n";
                
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
function get_authorfeed(Config $config) {
     if ((!empty($config->get('bluesky_username'))) && (!empty($config->get('bluesky_password')))) {
        $blueskyAPI = new API($config);
        $token = $blueskyAPI->getAccessToken($config->get('bluesky_username'), $config->get('bluesky_password'));
        if (!$token) {
            throw new Exception("Login failed. Please check login and passwort in your config file.");
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

        echo Debugging::get_dump_debug($listdata, false, true);
    
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
        $listdata = $blueskyAPI->getList($search);

        echo Debugging::get_dump_debug($listdata, false, true);
    

    } else {
        echo "No bluesky account in config.json, therfor stopping\n";
    }
}

<?php

namespace Bluesky;

class API {
    private string $baseUrl = "https://bsky.social/xrpc";
    private ?string $token = null;
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        if (!empty($this->config->get('service_baseurl'))) {
            $this->baseUrl = $this->config->get('service_baseurl');
        }
    }
    /**
     * Loggt sich ein und speichert das Access-Token.
     *
     * @param string $username Bluesky-Benutzername.
     * @param string $password Bluesky-Passwort.
     * @return string|null Das Access-Token oder null, falls der Login fehlschlägt.
     */
    public function getAccessToken(string $username, string $password): ?string {
        $url = "{$this->baseUrl}/com.atproto.server.createSession";

        $data = [
            "identifier" => $username,
            "password" => $password,
        ];

        $response = $this->makeRequest($url, "POST", $data);

        if (isset($response['accessJwt'])) {
            $this->token = $response['accessJwt'];
            return $this->token;
        }

        return null;
    }

    /**
     * Ruft die Beiträge eines bestimmten Benutzers ab.
     *
     * @param string $did Die DID des Benutzers (z. B. "did:plc:12345").
     * @return array|null Die Beiträge des Benutzers oder null bei Fehlern.
     */
    public function getAuthorFeed(string $did): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        $params = '';
        if (!empty($this->config->get('query_getAuthorFeed'))) {
            if (isset($this->config->get('query_getAuthorFeed')['filter'])) {
                $params .= '&filter='.$this->config->get('query_getAuthorFeed')['filter'];
            }
            if (isset($this->config->get('query_getAuthorFeed')['limit'])) {
                $params .= '&limit='.$this->config->get('query_getAuthorFeed')['limit'];
            }
        }
        
       
        $url = "{$this->baseUrl}/app.bsky.feed.getAuthorFeed?actor={$did}";
        if (!empty($params)) {
            $url .= $params;
        }

        return $this->makeRequest($url, "GET");
    }

     /**
     * Ruft einen bestimmten Post auf.
     *
     * @param string $uri . Bspw: at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27
     * @return array|null . Die Daten des Posts, wenn gefunden
     */
    public function getPosts(string $uri): ?Post {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        $data = [
            'uris' => [$uri], // AT URI(s) as input
        ];
        $url = "{$this->baseUrl}/app.bsky.feed.getPosts";
        
        // API-Aufruf über die makeRequest-Methode
         $response = $this->makeRequest($url, "GET", $data);
         
        if (!$response || empty($response['posts'])) {
              error_log("No post found for: $uri");
              return null;
        }

        // Den ersten Post im Array nehmen (da wir nur einen URI übergeben haben)
        $postData = $response['posts'][0];

        // Rückgabe des Posts als Post-Objekt
        return new Post($postData);
    }
    
    
    /**
     * Ruft die öffentliche Timeline ab.
     *
     * @return array|null Die öffentliche Timeline oder null bei Fehlern.
     */
    public function getPublicTimeline(): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }

        $url = "{$this->baseUrl}/app.bsky.feed.getTimeline";
        
        // Werte aus der Config laden
        $configParams = $this->config->get('query_timeline') ?? [];
        
        $search = [];
        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
        return $this->makeRequest($url, "GET", $search);
    }
    
    /**
    * Search for a list
    * @param array search, with (at-identifier) actor as required, (int) limit optional, (strng) cursor optional
    * @return array|null List or null on not found
    */
    public function getLists(array $search): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['actor'])) {
            throw new \InvalidArgumentException('Required field actor (of type at-identifier) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getLists";
        
        // Werte aus der Config laden
        $configParams = $this->config->get('query_getlists') ?? [];
        
        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
         $response = $this->makeRequest($url, "GET", $search);

        // Falls keine sinnvolle Antwort vorliegt oder 'lists' nicht vorhanden ist, null zurückgeben
        if (!$response || !isset($response['lists']) || !is_array($response['lists'])) {
            return null;
        }

        // Jedes Element in 'lists' in ein Objekt der Klasse Lists umwandeln
        $listsObjects = [];
        foreach ($response['lists'] as $num => $listData) {
            
           $listsObjects[] = new Lists($listData, $this->config);
        }

        return $listsObjects;
    }
    
     /**
     * Search for a list
     * @param array search, with (at-uri) list as required, (int) limit optional, (strng) cursor optional
     * @return array|null List or null on not found
     */
    public function getList(array $search): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['list'])) {
            throw new \InvalidArgumentException('Required field list (of type at-uri) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getList";
        
        // Werte aus der Config laden
        $configParams = $this->config->get('query_getlist') ?? [];
        
        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
         // API-Anfrage
        $response = $this->makeRequest($url, "GET", $search);

        // Falls keine gültige Antwort vorliegt, Abbruch mit null
        if (!$response) {
            return null;
        }

        // Erwartete Felder prüfen
        if (!isset($response['list'], $response['items'])) {
            // Wenn Felder fehlen, kann man hier entweder null oder eine Exception werfen
            return null;
        }

        // Umwandeln des Feldes 'list' in ein Listen-Objekt (Class Lists)
        $listsObject = new Lists($response['list'], $this->config);
        $cursor = isset($response['cursor']) ? (string) $response['cursor'] : '';
        
        $items = [];
        if (isset($response['items']) && is_array($response['items'])) {
            foreach ($response['items'] as $item) {
                // uri als string
                $uri = $item['uri'] ?? '';

                // subject als Profil-Objekt
                $profilObj = null;
                if (isset($item['subject']) && is_array($item['subject'])) {
                    $profilObj = new Profil($item['subject'], $this->config);
                }

                $items[] = [
                    'uri'     => $uri,
                    'subject' => $profilObj
                ];
            }
        }
        
        // Rückgabe als assoziatives Array
        return [
            'cursor' => $cursor,
            'list'   => $listsObject,
            'items'  => $items
        ];
    }
    
    
    
    /**
    * Get StarterPacks by Actor URI
    */
    public function getActorStarterPacks(array $search): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['actor'])) {
            throw new \InvalidArgumentException('Required field list (of type at-uri) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getActorStarterPacks";
        
        // Werte aus der Config laden
        $configParams = $this->config->get('query_getActorStarterPacks') ?? [];
        
        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
         // API-Anfrage
        $response = $this->makeRequest($url, "GET", $search);

        // Falls keine gültige Antwort vorliegt, Abbruch mit null
        if (!$response) {
            return null;
        }

        // Erwartete Felder prüfen
        if (!isset($response['starterPacks'])) {
            // Wenn Felder fehlen, kann man hier entweder null oder eine Exception werfen
            return null;
        }

        // Umwandeln des Feldes 'starterPacks' in ein Starterpack-Objekt (Class Lists)
        
        $listsObjects = [];
        foreach ($response['starterPacks'] as $num => $listData) {
           $listsObjects[] = new StarterPack($listData, $this->config);
        }
        $cursor = isset($response['cursor']) ? (string) $response['cursor'] : '';
        
        // Rückgabe als assoziatives Array
        return [
            'cursor' => $cursor,
            'starterpacks'   => $listsObjects,
        ];
    }
    /**
    * Get StarterPacks by URIS
    */
    public function getStarterPacks(string $uris): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        if (empty($uris)) {
            throw new \InvalidArgumentException('Required field actor (of type at-identifier) missing.');
        }
        $data = [
            'uris' => [$uris], // AT URI(s) as input
        ];
        $url = "{$this->baseUrl}/app.bsky.graph.getStarterPacks";

        $response = $this->makeRequest($url, "GET", $data);
        

        // Falls keine sinnvolle Antwort vorliegt, null zurückgeben
        if (!$response || !isset($response['starterPacks']) || !is_array($response['starterPacks'])) {
            return null;
        }

        // Jedes Element in 'lists' in ein Objekt der Klasse Lists umwandeln
        $listsObjects = [];
        foreach ($response['starterPacks'] as $num => $listData) {
      //      echo Debugging::get_var_dump($listData);
           $listsObjects[] = new StarterPack($listData, $this->config);
        }

        return $listsObjects;
    }
    
     /**
     * Search for a list
     * @param array search, with (at-uri) list as required, (int) limit optional, (strng) cursor optional
     * @return array|null List or null on not found
     */
    public function getStarterPack(array $search): ?array {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['starterPack'])) {
            throw new \InvalidArgumentException('Required field list (of type at-uri) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.graph.getStarterPack";
        
        // Werte aus der Config laden
        $configParams = $this->config->get('query_getlist') ?? [];
        
        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
         // API-Anfrage
        $response = $this->makeRequest($url, "GET", $search);

        // Falls keine gültige Antwort vorliegt, Abbruch mit null
        if (!$response) {
            return null;
        }

        // Erwartete Felder prüfen
        if (!isset($response['list'], $response['items'])) {
            // Wenn Felder fehlen, kann man hier entweder null oder eine Exception werfen
            return null;
        }

        // Umwandeln des Feldes 'list' in ein Listen-Objekt (Class Lists)
        $listsObject = new StarterPack($response['list'], $this->config);
        

        
        // Rückgabe als assoziatives Array
        return [
            'StarterPack'   => $listsObject,
        ];
    }
    
    
    /**
     * Get an account
     * @param actor
     * @return array|null List or null on not found
     */
    public function getProfile(array $search): ?Profil {
        if (!$this->token) {
            throw new \Exception("Access token is required. Call getAccessToken() first.");
        }
        if (empty($search['actor'])) {
            throw new \InvalidArgumentException('Required field actor (Handle or DID of account to fetch profile of.) missing.');
        }
        $url = "{$this->baseUrl}/app.bsky.actor.getProfile";
        
        $response = $this->makeRequest($url, "GET", $search);

        if (!$response) {
            error_log("Keine Antwort vom Server.");
            return null;
        }

        // Wandelt das Array in ein Profil-Objekt um
        return new Profil($response, $this->config);
    }
   
    
    
    /*
     * Suchanfrage
     */
    public function searchPosts(array $search): ?array {
        // Basis-URL des Endpoints
        $endpoint = "{$this->baseUrl}/app.bsky.feed.searchPosts";

          // Sicherstellen, dass das Feld 'q' vorhanden ist
        if (empty($search['q'])) {
            throw new \InvalidArgumentException('Required field q for the search string is missing.');
        }

        // Werte aus der Config laden
        $configParams = $this->config->get('query_searchPosts') ?? [];

        // Fehlende Werte aus der Config ergänzen
        foreach ($configParams as $key => $value) {
            if (!array_key_exists($key, $search) || $search[$key] === null || $search[$key] === '') {
                $search[$key] = $value;
            }
        }
    
        // GET-Anfrage mit den Suchparametern
        $response = $this->makeRequest($endpoint, 'GET', $search);

        if (!$response) {
            error_log('No results found.');
            return null;
        }
        // Validieren der API-Antwort
        if (!isset($response['posts'])) {
            throw new \RuntimeException('Invalid api response');
        }

        // Umwandeln der Post-Daten in Post-Objekte
        $posts = [];
        foreach ($response['posts'] as $postData) {
            $posts[] = new Post($postData);
        }

        return [
            'cursor' => $response['cursor'] ?? '',
            'hitsTotal' => (int) ($response['hitsTotal'] ?? count($posts)),
            'posts' => $posts
        ];
        return $response;
    }


    /**
     * Führt eine HTTP-Anfrage aus.
     *
     * @param string $url Die URL für die Anfrage.
     * @param string $method Die HTTP-Methode ("GET" oder "POST").
     * @param array|null $data Optional: Daten für POST-Anfragen.
     * @return array|null Die JSON-Antwort als Array oder null bei Fehlern.
     */
    private function makeRequest(string $url, string $method, ?array $data = null): ?array {
        
        if ($method === "GET" && $data) {
            // Daten als URL-Parameter kodieren und an die URL anhängen
            $queryString = http_build_query($data);
            $url .= '?' . $queryString;
        }

        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // TODO: Change this for productive use:
        if (($this->config->get('SSL_VERIFYPEER') !== null) && ($this->config->get('SSL_VERIFYPEER') == 'false')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $headers = ["Content-Type: application/json"];
        if ($this->token) {
            $headers[] = "Authorization: Bearer {$this->token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === "POST" && $data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log("cURL error: " . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON decode error: ' . json_last_error_msg());
        }

        return $decoded;
    }
}

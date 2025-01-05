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
              error_log("Keine Posts für URI gefunden: $uri");
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
        return $this->makeRequest($url, "GET", $search);
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
        return $this->makeRequest($url, "GET", $search);
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

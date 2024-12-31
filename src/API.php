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

        $url = "{$this->baseUrl}/app.bsky.feed.getAuthorFeed?actor={$did}";

        return $this->makeRequest($url, "GET");
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

        return $this->makeRequest($url, "GET");
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

        return json_decode($response, true);
    }
}

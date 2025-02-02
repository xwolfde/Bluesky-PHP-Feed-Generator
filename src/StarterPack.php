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


namespace Bluesky;

class StarterPack {
    public string $uri;
    public string $cid;
    public array $record;    
    public Profil $creator;
    public ?Lists $list;
    public ?array $listItemsSample;
    public ?array $feeds;
    public ?int $joinedWeekCount;
    public ?int $joinedAllTimeCount;
    public ?array $labels;
    public ?string $indexedAt;
    private ?array $rawdata;
    private ?Config $config;
    
    public function __construct(array $data, ?Config $config = null) {
        $this->uri = $data['uri'] ?? '';
        $this->cid = $data['cid'] ?? '';
        $this->creator = new Profil($data['creator']);
        $this->record = $data['record'] ?? [];
        $this->indexedAt = $data['indexedAt'] ?? '';
        $this->labels = $data['labels'] ?? [];
        if (isset($data['list'])) {
            $this->list = new Lists($data['list']);
        }
        $this->listItemsSample = $data['listItemsSample'] ?? [];
        $this->feeds = $data['feeds'] ?? [];
        $this->joinedWeekCount = (int) ($data['joinedWeekCount'] ?? 0);   
        $this->joinedAllTimeCount = (int) ($data['joinedAllTimeCount'] ?? 0);   

        $this->config = $config ?? null;
        
        // Everything else move in rawdata       
        $usedKeys = [
            'uri',
            'cid',
            'creator',
            'record',
            'list',
            'listItemsSample',
            'feeds',
            'joinedWeekCount',
            'joinedAllTimeCount',
            'indexedAt',
            'labels'
        
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
    }
    
    public function setConfig(Config $config) {
        return $this->config = $config;
    }
       
    
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    public function getStarterPackView(?string $template = null): string {
        if (empty($template)) {
            $template = '';
            $template .= "Name         : #recordname#".PHP_EOL;
            $template .= "Description  : #recorddescription#".PHP_EOL;
            $template .= "Created At   : #recordcreatedAt#".PHP_EOL;
            $template .= "Updated At   : #recordupdatedAt#".PHP_EOL;
            $template .= "List AT URI  : #recordlist#".PHP_EOL;
            
            $template .= "Creator      : #creator#".PHP_EOL;
            $template .= "URI          : #uri#".PHP_EOL;
            $template .= "CID          : #cid#".PHP_EOL;
            $template .= "Bluesky URL  : #blueskyurl#".PHP_EOL;
            $template .= "XRPC URL     : #xrpcurl#".PHP_EOL;
        }
        
         
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#creator#' => $this->getCreatorHandle(),
            '#uri#' => $this->uri ?? 'N/A',
            '#cid#' => $this->cid ?? 'N/A',
            '#recordname#' => $this->record['name'] ?? 'N/A',
            '#recorddescription#' => $this->record['description'] ?? 'N/A',
            '#recordcreatedAt#' => $this->record['createdAt'] ?? 'N/A',
            '#recordupdatedAt#' => $this->record['updatedAt'] ?? 'N/A',
            '#recordlist#' => $this->record['list'] ?? 'N/A',
            '#indexedat#' => $this->indexedAt ?? 'N/A',
            '#blueskyurl#'  => $this->getBlueskyURL(),
            '#xrpcurl#' => $this->getBlueSkyXRPCURL()
        ];

                
        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
    
    /*
     * get handle of creator
     */
    public function getCreatorHandle(): ?string {
        return $this->creator->handle ?? null;
    }
    
    /**
     * gets public Bluesky-URL for list
     */
    public function getBlueskyURL(): string {
        // Überprüfen, ob die URI das richtige Format hat
        if (preg_match('/^at:\/\/did:plc:([a-z0-9]+)\/app\.bsky\.graph\.getStarterPacks\/([a-z0-9]+)$/', $this->uri, $matches)) {
            $postId = $matches[2];
        } elseif (preg_match('/^at:\/\/did:plc:([a-z0-9]+)\/app\.bsky\.graph\.starterpack\/([a-z0-9]+)$/', $this->uri, $matches)) {
            $postId = $matches[2];
        } else {
            $postId = '';
        }
        if ($this->getCreatorHandle()) {
            $profileId = $this->getCreatorHandle();
        } else {
            $profileId = $matches[0];
        }
        $postId = $matches[2];

        return "https://bsky.app/starter-pack/{$profileId}/{$postId}";
    }
     /**
     * get XRPC URL for profile
     */
    public function getBlueSkyXRPCURL(): string {
        return "https://public.api.bsky.app/xrpc/app.bsky.graph.getStarterPacks?uris=" . urlencode($this->uri);
    }
}

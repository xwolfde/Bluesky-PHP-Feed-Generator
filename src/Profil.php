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

class Profil {
    public string $did;
    public string $handle;
    public ?string $createdAt;
    public ?string $indexedAt;
    public ?string $displayName;
    public ?string $description;
    public ?string $avatar;
    public ?string $banner;
    public int $followersCount;
    public int $followsCount;
    public int $postsCount;
    private ?Config $config;
    private array $rawdata;
    
    public function __construct(array $data, ?Config $config = null) {
        $this->did = $data['did'] ?? '';
        $this->handle = $data['handle'] ?? '';
        $this->displayName = $data['displayName'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->avatar = $data['avatar'] ?? '';
        $this->banner = $data['banner'] ?? '';
        $this->createdAt = $data['createdAt'] ?? '';
        $this->indexedAt = $data['indexedAt'] ?? '';
        
        
        $this->followersCount = (int) ($data['followersCount'] ?? 0);
        $this->followsCount= (int) ($data['followsCount'] ?? 0);
        $this->postsCount= (int) ($data['postsCount'] ?? 0);
        $this->config = $config ?? null;
        
        // Alle Keys, die wir bereits zugewiesen haben
        $usedKeys = [
            'did',
            'handle',
            'displayName',
            'description',
            'avatar',
            'banner',
            'createdAt',
            'indexedAt',
            'followersCount',
            'followsCount',
            'postsCount'
        ];

        // Nur jene Keys in rawdata speichern, 
        // die nicht bereits zugewiesen wurden
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
    }
    
    public function setConfig(Config $config) {
        return $this->config = $config;
    }
       
    
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    public function getProfilView(?string $template = null): string {
        if (empty($template)) {
            $template = '';
            $template .= "Text (ex.) : #textexcerpt#".PHP_EOL;
            $template .= "Tags       : #tags#".PHP_EOL;
            $template .= "Author     : #autor#".PHP_EOL;
            $template .= "Created at : #created#".PHP_EOL;
            $template .= "Stats      : #likes# Likes #reposts# Reposts, #replys# Replys".PHP_EOL;
            $template .= "Bluesky URL: #blueskyurl#".PHP_EOL;
            $template .= "XRPC URL   : #xrpcurl#".PHP_EOL;
        }
        
        $limit = 80;
        if ($this->config) {
            $limit = $this->config->get('exerpt-length');
        }
        $textExcerpt = $this->text ? substr(str_replace(["\r", "\n"], ' ', $this->text), 0, $limit)
        : 'N/A';
         
         
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#autor#' => $this->getAutorHandle() ?? 'N/A',
            '#text#' => $this->text ?? 'N/A',
            '#textexcerpt#' => $textExcerpt,
            '#created#' => $this->createdAt ?? 'N/A',
            '#id#' => $this->uri ?? 'N/A',
            '#tags#' => implode(', ', $this->getTags() ?? []),
            '#reposts#' => $this->repostCount ?? '0',
            '#replys#' => $this->replyCount ?? '0',
            '#quotes#' => $this->quoteCount ?? '0',
            '#likes#' => $this->likeCount ?? '0',
            '#blueskyurl#'  => Utils::getBlueskyURL($this->uri, $this->getAutorHandle()) ?? '',
            '#xrpcurl#' => Utils::getBlueSkyXRPURL($this->uri) ?? ''
        ];

                
        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
}

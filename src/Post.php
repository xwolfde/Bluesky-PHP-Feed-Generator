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

class Post {
    public string $text;
    public string $uri;
    public string $cid;
    public string $createdAt;
    public string $indexedAt;
    public ?array $autor;
    public ?array $embeds;
    public ?array $facets;
    public ?array $langs;
    public ?array $config;
    public int $likeCount;
    public int $repostCount;
    public int $replyCount;
    public int $quoteCount;
    
    
    public function __construct(array $data, ?Config $config = null) {
        $this->text = $data['record']['text'] ?? '';
        $this->uri = $data['uri'] ?? '';
        $this->cid = $data['cid'] ?? '';
        $this->createdAt = $data['record']['createdAt'] ?? '';
        $this->indexedAt = $data['record']['indexedAt'] ?? '';
        $this->likeCount = (int) ($data['record']['likeCount'] ?? 0);
        $this->repostCount= (int) ($data['record']['repostCount'] ?? 0);
        $this->replyCount= (int) ($data['record']['replyCount'] ?? 0);
        $this->quoteCount= (int) ($data['record']['quoteCount'] ?? 0);       
        
        $this->autor = $data['author'] ?? null;
        $this->embeds = $data['record']['embed'] ?? null;
        $this->facets = $data['record']['facets'] ?? null;
        $this->langs = $data['record']['langs'] ?? null;
        $this->config = $config ?? null;
    }
    
    public function setConfig(Config $config) {
        return $this->config = $config;
    }
    public function getAutorHandle(): ?string {
        return $this->autor['handle'] ?? null;
    }

  
 
    public function getTags(): ?array {
        $taglist = [];

        foreach ($this->facets as $num) {
            if (isset($num['features'][0]['tag'])) {
                $taglist[] = '#'.$num['features'][0]['tag'];
            }
        }
  
        
        return $taglist;
    }
    
    
    
    public function getRawData(): array {
        return $this->post;
    }
    
    public function getListView(?string $template = null): string {
        $template ??= '#autor#: "#textexcerpt#..."  #tags#  (#likes# Likes #reposts# Reposts, #replys# Replys) '
                . PHP_EOL.'             #created# URI: #id#';
        
        
        $limit = $post->config->get('exerpt-length') ?? 80;
                
        $textExcerpt = $this->getText() ? substr(str_replace(["\r", "\n"], ' ', $this->text), 0, $limit)
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
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
}

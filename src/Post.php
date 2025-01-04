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


namespace Bluesky;

class Post {
    public string $text;
    public string $createdAt;
    public ?array $facets;
    public ?array $langs;
    public ?array $tags;
    public array $raw;
    public ?array $config;
    
    public function __construct(array $data, ?Config $config = null) {
        $this->text = $data['text'] ?? '';
        $this->createdAt = $data['createdAt'] ?? '';
        $this->facets = $data['facets'] ?? null;
        $this->langs = $data['langs'] ?? null;
        $this->tags = $data['tags'] ?? null;
        $this->raw = $data;
        $this->config = $config ?? null;
    }
    
    public function setConfig(Config $config) {
        return $this->config = $config;
    }
    public function getAuthor(): ?string {
        return $this->post['author']['handle'] ?? null;
    }

    public function getText(): ?string {
        return $this->post['record']['text'] ?? null;
    }

    public function getCreatedAt(): ?string {
        return $this->post['record']['createdAt'] ?? null;
    }

    public function getLinks(): ?array {
        return $this->post['record']['links'] ?? null;
    }

    public function getLikes(): ?int {
        return $this->post['likeCount'] ?? null;
    }

    public function getReposts(): ?int {
        return $this->post['repostCount'] ?? null;
    }

    public function getReplys(): ?int {
        return $this->post['replyCount'] ?? null;
    }
    public function getQuotes(): ?int {
        return $this->post['quoteCount'] ?? null;
    }
    public function getLang(): ?string {
        return $this->post['record']['langs'][0] ?? null;
    }
    public function getTags(): ?array {
        $taglist = [];
        if (isset($this->post['record']['facets'])) {
            foreach ($this->post['record']['facets'] as $num) {
                if (isset($num['features'][0]['tag'])) {
                    $taglist[] = '#'.$num['features'][0]['tag'];
                }
            }
        }
        
        return $taglist;
    }
    
    
    
    public function getId(): ?string {
        return $this->post['uri'] ?? null;
    }

    public function getRawData(): array {
        return $this->post;
    }
    
    public function getListView(?string $template = null): string {
        $template ??= '#author#: "#textexcerpt#..."  #tags#  (#likes# Likes #reposts# Reposts, #replys# Replys) '
                . PHP_EOL.'             #created# URI: #id#';
        
        
        $limit = $post->config->get('exerpt-length') ?? 80;
                
        $textExcerpt = $this->getText() ? substr(str_replace(["\r", "\n"], ' ', $this->getText()), 0, $limit)
        : 'N/A';
         
         
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#author#' => $this->getAuthor() ?? 'N/A',
            '#text#' => $this->getText() ?? 'N/A',
            '#textexcerpt#' => $textExcerpt,
            '#created#' => $this->getCreatedAt() ?? 'N/A',
            '#id#' => $this->getId() ?? 'N/A',
            '#links#' => implode(', ', $this->getLinks() ?? []),
            '#tags#' => implode(', ', $this->getTags() ?? []),
            '#reposts#' => $this->getReposts() !== null ? (string)$this->getReposts() : '0',
            '#replys#' => $this->getReplys() !== null ? (string)$this->getReplys() : '0',
            '#quotes#' => $this->getQuotes() !== null ? (string)$this->getQuotes() : '0',
            '#likes#' => $this->getLikes() !== null ? (string)$this->getLikes() : '0',
            '#reposts#' => $this->getReposts() !== null ? (string)$this->getReposts() : '0',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
}

<?php

/*
 * Copyright (C) 2024 Anwender
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
    private Config $config;

    public function __construct(private array $post, Config $config) {
        $this->config = $config;
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
        return $this->post['stats']['likes'] ?? null;
    }

    public function getReposts(): ?int {
        return $this->post['stats']['reposts'] ?? null;
    }

    public function getId(): ?string {
        return $this->post['uri'] ?? null;
    }

    public function getRawData(): array {
        return $this->post;
    }
    
    public function getListView(?string $template = null): string {
        $template ??= '#author#  "#textexcerpt#..."  #created# (#likes# Likes #reposts# Reposts, Links: #links#)';
        
        $limit = $this->config->get('exerpt-length') ?? 80;
                
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
            '#likes#' => $this->getLikes() !== null ? (string)$this->getLikes() : '0',
            '#reposts#' => $this->getReposts() !== null ? (string)$this->getReposts() : '0',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
}

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

/**
 * Description of Feed
 *
 * @author Anwender
 */
class Feed {
    public array $posts;
    public array $reply;
    public array $reason;
    public string $feedContext;
    
    public function __construct(array $posts, array $reply, array $reason, string $feedContext) {
        $this->posts = $posts;
        $this->reply = $reply;
        $this->reason = $reason;
        $this->feedContext = $feedContext;
    }
}

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

/**
 * Description of Utils
 *
 * @author Anwender
 */
class Utils {
    /**
     * Konvertiert eine Bluesky-URI in die öffentliche Bluesky-URL.
     * 
     * @param string $uri Die Eingabe-URI im Format at://did:plc:PROFILEID/app.bsky.feed.post/POSTID.
     * @return string Die generierte öffentliche URL.
     * @throws InvalidArgumentException Wenn die URI nicht korrekt formatiert ist.
     */
    public static function getBlueskyURL(string $uri, string $handle): string {
        // Überprüfen, ob die URI das richtige Format hat
        if (!preg_match('/^at:\/\/did:plc:([a-z0-9]+)\/app\.bsky\.feed\.post\/([a-z0-9]+)$/', $uri, $matches)) {
            throw new \InvalidArgumentException("Ungültige URI: $uri");
        }

        $profileId = $handle;
        $postId = $matches[2];

        return "https://bsky.app/profile/{$profileId}/post/{$postId}";
    }

    /**
     * Konvertiert eine Bluesky-URI in eine XRPC-URL.
     * 
     * @param string $uri Die Eingabe-URI im Format at://did:plc:PROFILEID/app.bsky.feed.post/POSTID.
     * @return string Die generierte XRPC-URL.
     */
    public static function getBlueSkyXRPURL(string $uri): string {
        return "https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?uris=" . urlencode($uri);
    }
}

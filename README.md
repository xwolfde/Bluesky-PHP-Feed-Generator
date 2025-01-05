# Bluesky PHP Feed-Generator and Queries

[![Release Version](https://img.shields.io/github/v/release/xwolfde/Bluesky-PHP-Feed-Generator?label=Release+Version)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/releases/) [![GitHub License](https://img.shields.io/github/license/xwolfde/Bluesky-PHP-Feed-Generator?label=Lizenz)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/blob/main/LICENSE) [![GitHub issues](https://img.shields.io/github/issues/xwolfde/Bluesky-PHP-Feed-Generator)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/issues)

Functions to access Bluesky and generate feeds with PHP

## Version

Version: 1.0.10

## Autor 

xwolf, 
- Website: https://xwolf.de
- Bluesky: https://bsky.app/profile/xwolf.de

## Copyright

GNU General Public License (GPL) Version 3


## Feedback

Please use the Issues:
 https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/issues


## Installation

Notice: You need PHP 8.3 or later to use this skripts.

To start, copy the `config.sample.json` to `config.json` and insert your login-credentials for bluesky into the fields
    "bluesky_username" : "",
    "bluesky_password" : "",



## Example Usage

### getPost

`php status.php --uri=at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27 getPost`

returns status informations of the post with the at-uri.



### Test and Debug-Cases

* Bluesky API Reference: https://docs.bsky.app/docs/category/http-reference
* Public XRPC API XCall for a Post: https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?uris=at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27
* Same Post on Bluesky Webapp: 

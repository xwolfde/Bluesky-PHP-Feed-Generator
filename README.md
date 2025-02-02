# Bluesky PHP Feed-Generator and Queries

[![Aktuelle Version](https://img.shields.io/github/package-json/v/xwolfde/Bluesky-PHP-Feed-Generator/main?label=Version)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator) [![Release Version](https://img.shields.io/github/v/release/xwolfde/Bluesky-PHP-Feed-Generator?label=Release+Version)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/releases/) [![GitHub License](https://img.shields.io/github/license/xwolfde/Bluesky-PHP-Feed-Generator?label=Lizenz)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/blob/main/LICENSE) [![GitHub issues](https://img.shields.io/github/issues/xwolfde/Bluesky-PHP-Feed-Generator)](https://github.com/xwolfde/Bluesky-PHP-Feed-Generator/issues)

Functions to access Bluesky and generate feeds with PHP

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

Note: Set SSL_VERIFYPEER to false if your curl-installation isnt capable of using SSL.


## Example Usage

### getPost

`php status.php --uri=at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.feed.post/3lemy4yerrk27 getPost`

returns status informations of the post with the at-uri.

### getProfil

`php status.php --did=xwolf.de getProfil`

returns the proil informations of the user with the given handle

### listindex

`php status.php --did=did:plc:wyxbu4v7nqt6up3l3camwtnu listindex` 

returns the index of a lists of a actor with the given did

### list

Normal list:
`php status.php --did=at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.graph.list/3kfdow6lmdr27 list`

List from a starterpack
`php status.php --did=at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.graph.list/3lbmyecry7422 list` 

returns a single list with its members


### getActorStarterPacks

`php status.php --uri=xwolf.de getActorStarterPacks`

gets the starter pack of a defined uri/bluesky Handle


### getStarterPacks

`php status.php --uri=at://did:plc:wyxbu4v7nqt6up3l3camwtnu/app.bsky.graph.getStarterPacks/3lbmyed23tr2q getStarterPacks`

returns StarterPack by uri.



## Documentation

* Bluesky API Reference: https://docs.bsky.app/docs/category/http-reference


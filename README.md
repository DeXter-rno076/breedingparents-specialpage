# breedingchains-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

The data sets are created via the program lying in breedingparents-data in the same GitHub user aka me (DeXter-rno076).

The name of this specialpage was changed to BreedingChains / Zuchtketten at the end to prevent misunderstandings about its usage, but this repo is still named breedingparents because messing with Git later on without exactly knownig what you're doing is always a bad idea.

# next steps
* adjust needed php version
* create fancy docs of the general algorithms and ideas
* selectSuccessors call warnings stuff
* 
* maybe a data object that stores the data which learnability types are possible for the root
* add connection text attribute to BreedingTreeNode that has the connection from the node to its predecessor (always 0-1 lines -> one string is enough) -> easier way to handle evo connection and special cases (at least to handle it outside the already quite full SVGPkmn class)
* performance problems -> Glurak Konter g8
* renamed move names in text input
* learnset subpage links for special forms (links must go to normal form pages)
* lowercase names in text suggestions are odd
* somehow remove legend (a bit chaos with infomessages, legend and breeding tree map)

## visual stuff
* colors for old gen and event are todo
* better zoom limits (maybe dependent on the svg size)

## needs data stuff
* add property that has the correct name casing
* some moves are blocked by Nachahmer
* Kugelblitz and Volttackle
* one general JSON file that has all pkmn names, move names and some config data
* female only pkmn -> are limited to their line

## time plan
* names
  * lowercase names in text suggestions -> changing names to lowercase at the end and only in final datasets
  * subpage links to correctly capitalised normal forms
  * renamed moves in text input
* nachahmer
* bug: nur weibliche pokémon (Arbok Giftzahn G8)
* config file
  * check typos in move names
  * outsource some constants (games to sk, games to gen, renamed moves)

# bugs
* Arbok Giftzahn G8: pkmn occur multiple times

# quality of life todos
* button to get to the middle
* mouse wheel: switch between scrolling and zooming
* old gen -> say which specific game
* remove leaflet link

# after 'release' (aka. never)
* solve redundancies (e. g. check each tree section for equality => do this bottom up => much more efficient, Glumanda Konter Gen8 is one of the extreme cases)
* set single successors to same y coord as the predecessor
* move single successors closer to their neighbours => less whitespace
* maybe put tutor learnsets into a separate list and handle it like event or old gen

# notes
* if you're working on something with ordering or whatever and wonder why the order is upside down: leaflet uses weird coordinate systems with non-standard origins -> this causes the breedind tree to be vertically mirrored (could be handled by the JS though)
* learns by old gen purposely only works with direct learnsets. Otherwise you would have to *start* to create a completely independet breeding tree for each Pokémon in order to know whether it can successfully inherit the move. But this is not impossible. You'd just probably have to cache every breeding tree for performance reasons, but the benefit additional effort ratio is by far too bad for v1.0 (right now there is no caching because caching itself probably is slower than creating the breeding trees)
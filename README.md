# breedingchains-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

The data sets are created via the program lying in breedingparents-data in the same GitHub user aka me (DeXter-rno076).

The name of this specialpage was changed to BreedingChains / Zuchtketten at the end to prevent misunderstandings about its usage, but this repo is still named breedingparents because messing with Git later on without exactly knownig what you're doing is always a bad idea.

# testing
* Shardrago (LP)

# next steps
* text input
  * better suggestions (really allow shortcuts?)
*
* adjust needed php version
* create fancy docs of the general algorithms and ideas
*
* performance problems -> Glurak Konter g8 (still relevant for separated games?)
* somehow remove legend (a bit chaos with infomessages, legend and breeding tree map)

## visual stuff
* colors for old gen and event are todo

## needs data stuff

## time plan

# bugs

# quality of life todos
* mouse wheel: switch between scrolling and zooming
* old gen -> say which specific game

# after 'release' (aka. never)
* set single successors to same y coord as the predecessor
* move single successors closer to their neighbours => less whitespace
* maybe put tutor learnsets into a separate list and handle it like event or old gen

# notes
* why external data pages in the wiki and json files? The external pages are meant for things that can change at any time (i. e. some error is found and wants to be corrected), the json files are meant for stuff that only changes with new releases
* if you're working on something with ordering or whatever and wonder why the order is upside down: leaflet uses weird coordinate systems with non-standard origins -> this causes the breedind tree to be vertically mirrored (could be handled by the JS though)
* learns by old gen purposely only works with direct learnsets. Otherwise you would have to *start* to create a completely independet breeding tree for each Pokémon in order to know whether it can successfully inherit the move. But this is not impossible. You'd just probably have to cache every breeding tree for performance reasons, but the benefit additional effort ratio is by far too bad for v1.0 (right now there is no caching because caching itself probably is slower than creating the breeding trees)
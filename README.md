# breedingchains-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

The data sets are created via the program lying in breedingparents-data in the same GitHub user aka me (DeXter-rno076).

The name of this specialpage was changed to BreedingChains / Zuchtketten at the end to prevent misunderstandings about its usage, but this repo is still named breedingparents because messing with Git later on without exactly knownig what you're doing is always a bad idea.


# next steps
* adjust needed php version
* create fancy docs of the general algorithms and ideas

# bugs
* SuccessorFilter: The male-only and unknown-only requirements probably have to check the basis Pokémon too when checking for male-only/unknown-only evo line
* evolution connections can also appear in the middle of a path: Baby-Pokémon can receive a move, evolve and then pass it on to others

# quality of life todos
* mouse wheel: switch between scrolling and zooming
* old gen -> say which specific game

# after 'release' (aka. never)
* move single successors closer to their neighbours => less whitespace
* maybe put tutor learnsets into a separate list and handle it like event or old gen

# notes
* why external data pages in the wiki and json files? The external pages are meant for things that can change at any time (i. e. some error is found and wants to be corrected), the json files are meant for stuff that only changes with new releases
* if you're working on something with ordering or whatever and wonder why the order is upside down: leaflet uses weird coordinate systems with non-standard origins -> this causes the breedind tree to be vertically mirrored (could be handled by the JS though)
* learns by old gen purposely only works with direct learnsets. Otherwise you would have to *start* to create a completely independet breeding tree for each Pokémon in order to know whether it can successfully inherit the move. But this is not impossible. You'd just probably have to cache every breeding tree for performance reasons, but the benefit additional effort ratio is by far too bad for v1.0 (right now there is no caching because caching itself probably is slower than creating the breeding trees)
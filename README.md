# breedingchains-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

The data sets are created via the program lying in breedingparents-data in the same GitHub user aka me (DeXter-rno076).

The name of this specialpage was changed to BreedingChains / Zuchtketten at the end to prevent misunderstandings about its usage, but this repo is still named breedingparents because messing with Git later on without exactly knownig what you're doing is always a bad idea.

# next steps
* adjust needed php version
* create fancy docs of the general algorithms and ideas
* selectSuccessors call warnings stuff

# bugs
* for evo connections the lowest evo appears twice in the tree
* evo connection arrows somehow is messed up

# other stuff

# after 'release' (aka. never)
* solve redundancies (e. g. check each tree section for equality => do this bottom up => much more efficient, Glumanda Konter Gen8 is one of the extreme cases)
* set single successors to same y coord as the predecessor
* move single successors closer to their neighbours => less whitespace
* maybe put tutor learnsets into a separate list and handle it like event or old gen

# notes
* learns by old gen purposely only works with direct learnsets. Otherwise you would have to *start* to create a completely independet breeding tree for each Pokémon in order to know whether it can successfully inherit the move. But this is not impossible. You'd just probably have to cache every breeding tree for performance reasons, but the benefit additional effort ratio is by far too bad for v1.0 (right now there is no caching because caching itself probably is slower than creating the breeding trees)
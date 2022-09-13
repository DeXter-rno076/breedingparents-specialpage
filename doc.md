# intro
The BreedingChains specialpage has the goal to display the possible breeding chains for a Pokémon to get a certain move in the most practical way. This also means that the built breeding trees are explicitly *NOT* complete. Often you can have massive chains of breeding, sometimes even infinitely long ones when you manage to build closed loops. For smaller grafics and faster computing this specialpage uses some rules to ignore unnecessarily long breeding paths.

# context
In most Pokémon main games two Pokémon can be put in the Pokémon Nursery and - if some factors apply - get children

These factors are:
* both Pokémon have at least one matching egg group
* one is female, the other one is male
Both Pokémon explicitly do not have to be of the same species.

The children are always of the species of the mother. 

The children can inherit moves from their parents if following factors apply:
* the move is in the breeding learnsets of the child
* one of the parents has the move

This means that often you can get some moves on a Pokémon far earlier, than using ordinary leveling. However, the fact that Pokémon can have up to two egg groups creates another field of possible learnabilities: The so called chain breeding, where you kind of drag moves over multiple Pokémon species to get some possibly exotic moves on a wanted Pokémon.

For example, when you want to get Skull Bash on Bulbasaur in Pokémon Sword. One possible way is: Getting a male Sandaconda that learns Skull Bash on level 1. Pair that with a female Rhyhorn (or one of its evolutions) to get a male Rhyhorn with Skull Bash (a male one is needed for the next step). Pair that with a female Bulbasaur (or one of its evolutions) to get a Bulbasaur with Skull Bush.

The breeding chain with the egg group connections would be:

    Sandaconda =Field=> Rhyhorn =Monster=> Bulbasaur

---

Really understanding the role of the amount of egg groups a Pokémon has, plays a major role in one of the building steps.

A Pokémon that has only one egg group, either learns it via some other way than breeding or is the targeted Pokémon (Bulbasaur in the last example) and only has the purpose to receive the move. That is, because when having to inherit the move to get it, this egg group is already used for getting the move and it would be repetitive to pair with one Pokémon of the same egg group again (this would also add the problem of infinite loops).

Examples:

Litwick has one egg group: Amorphous.

An efficient breeding chain could look like this:

    A =Amorphous=> Litwick

But not like this:

    A =Amorphous=> Litwick =...=> B

Because Litwick lacks the egg group for the connection to B.

Why not the following one?

    A =Amorphous=> Litwick =Amorphous=> C

Because this is unnecessarily long and just skipping Litwick is faster and more practicable:

    A =Amorphous=> C


Conclusion: Pokémon with one egg group are beneficial only at the beginning or the and of a breeding chain. Middle parts of a chain need Pokémon with two egg groups to be able to have two connections (in and out).

## edge cases
### Ditto
When pairing a Pokémon with Ditto, the gender of the Pokémon is irrelevant. This enables male-only and gender-unknown Pokémon to have children.

### egg group unknown
Pokémon in the egg group Unknown (mostly legendaries) can't get children and therefore are irrelevant for this topic.

# terms/names
In the source code the name `successor` appears quite often. This is meant in the context of trees, *NOT* Pokémon breeding. The trees are built with the start on the left side, because then path lengths are aligned in the usual left to right order going from shortest to longest, so a tree successor means a Pokémon parent/predecessor.

`unpairable` means the Pokémon can't be paired with another one and therefore can't get children. Mostly legendaries and baby Pokémon.

`unbreedable` means the Pokémon can't hatch from an egg. Mostly Pokémon that are evolutions.

# tree building steps
The breeding trees are created in three steps.
First the raw structure is calculated. At this point, the possible paths are set.

Then this structure is translated into one that has everything you need for general visualization, i. e. every node has its coordinates and other data (e. g. icon links).

In the last step the visual structure is translated into a concrete and small exchangable format. Currently JSON is used, but in the beginning it was SVG, which should be easy to switch back to again.

---

(little side note about the move from SVG to JSON: at first the grafics were built and displayed as SVGs, but building zooming and moving myself was difficulter than expected and then I heard of leaflet that basically did all of that far better and I switched to it; at first I just used the SVG grafic as a background in leaflet and added some popups and so on via JS, but this was quite tricky because of imo. unintuitive coordinate systems (yes, multiple different ones) of leaflet; so instead of a background I rebuilt the entire grafic in leaflet in JS with the SVG as the basis that was display:noned, but this was quite slow, some extreme cases took about 2s on my PC, so I separated general visualization logic from SVG logic (this would have been generally a todo) and added a JSON translation, changing the exchange format massively boostet performance (these extreme cases were pushed to about 200ms on my PC) and shrinked the data size a lot)

## creating the raw breeding tree (includes/tree_creation)
In this building step we look for all shortest successful breeding paths. That is done by trying all possibilities and adding them on success.

First some general thougts:
Just blindly trying everything, will result in infinite recursion, so we need some restricting rules. We want only short paths, so every egg group should only appear once or never per single path, because if it would appear multiple times, we could just remove the connections in between these appearances and still have a working but far shorter path.

We want short paths, so being able to learn the wanted move directly (level up, TMTR or Move Tutors) should end the path, even if the Pokémon could inherit it from others. Breeding aside, we also have learning in an old game and event distributions. The thing with these two is that it can be very hard or even impossible to use them. So I came up with the following system:
* if the Pokémon can learn the move directly, stop and finish this path as a success
* mark if the Pokémon can learn the move in an old gen, but don't do anything more on this learning type
* check if the Pokémon can inherit the move and do this procedure for every possible successor
  *  if at least one path is successful, stop and finish this path as a success
* if the Pokémon can learn the move in an old gen, stop and finish this path as a success
* if the Pokémon can have the move from an event distribution, stop and finish as a success
* reaching this point, means the Pokémon can't learn the move, stop and finish as a failure
This system is not set in stone. Especially the handliung of old gen and event learnability can be discussed. But that's the current state.

This already describes the core recursive algorithm for building the breeding tree.
Here is a pseudocode version:
```
    if learns directly:
        mark learns directly
        return success
    
    if learns in old gen:
        mark learns in old gen
    
    if can inherit:
        mark could inherit
        successors = empty list
        for every possible parent of the current pkmn:
            result = do this procedure with parent
            if result = success
                add parent to successors
        
        if successors has entries:
            mark can inherit
            return success
    
    if learns in old gen:
        return success
    
    if learns in event:
        mark learns in event
        return success
    
    return failure
```

### classes
#### `BreedingSubtree`
Resembles a subtree with one or more roots and 0 or more successors. This is the most abstract building block of the raw breeding tree structure. Everything from the entire tree to the single path ends is a BreedingSubtree.

But it's only a structural entity. The concrete nodes are BreedingTreeNode instances. BreedingSubtree instances basically enclose them and bring the individual nodes into a tree structure.

BreedingSubtrees however are created by BreedingTreeNodes. It's like the nodes calculate the breeding chains, throw their successors in their BreedingSubtree and then jump themselves into it, forgetting the connections and existing alone until the next building step.

#### `BreedingRootSubtree`
A BreedingSubtree version that is meant for the wanted Pokémon/the root of roots, so the most outer layer of Subtrees. It has exactly one root and 0 or more successors.

#### `BreedingTreeNode`
The concrete nodes that resemble entities in the breeding structure. Almost in all cases a Pokémon, currently also the item Light Ball for the Volt Tackle special case.

These instances execute the recursive breeding chains creation process and then create BreedingSubtrees and move themselves into there.

#### `MiscTreeNode`
Subclass of BreedingTreeNode for non-Pokémon entities.

#### `PkmnTreeNode`
Subclass of BreedingTreeNode for Pokémon entities.

#### `PkmnTreeRoot`
Subclass of PkmnTreeNode for the top level root.
This has little changes in the main breeding chains calculation process:
* not one but up to two egg groups are selected for possible successors (this is the only node that only receives but does not pass on the wanted move)
* tries to get the move via its lowest evolution if it's an evolved form
  * for this another PkmnTreeRoot instance is created for the lowest evolution, so in that case you technically have two top level roots

#### `LearnabilityStatus`
Class for setting and getting learnability info with very simple logic for e. g. building a learnability code, where each type is resembled by one char.

#### `SuccessorFilter`
The filter that throws out every unwanted successor in one breeding chains calculation step.
Additionally this is the place where almost all special cases are handled, that can't be abstracted away in the dataset creation (i. e. this took quite a bit of debugging and tears).

It uses the following order of procedures:
* removes Pokémon that don't exist in the selected game
* removes Pokémon that are unpairable (we want suitable parents, so Pokémon that can't be parents are unwanted)
* todo

#### `SuccessorMixer`
A mixer that directly inserts tree entities by adding an entity as a successful successor. This is currently only used for the Volt Tackle special case, where a Light Orb node is inserted.

## creating a structure for visualization (includes/visual_creation)
## translating it into usable data exchange format (includes/visual_cretion)

# frontend
## form
## leaflet
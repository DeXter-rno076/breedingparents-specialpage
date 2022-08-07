$( function () {
    const gameToSk = mw.config.get('breedingchains-game-to-sk');
    const gameNames = Object.keys(gameToSk);
    const moveSuggestions = mw.config.get('breedingchains-move-suggestions');
    const pkmnNames = Object.keys(moveSuggestions);

    let currentMoveSuggestions = [];

    const qsParams = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });


    const gameInput = new OO.ui.ComboBoxInputWidget({
        placeholder: mw.config.get('breedingchains-game-input-placeholder'),
        value: qsParams.targetGame || undefined,
        options: arrayToOptionsArray(gameNames)
    });
    const gameInputField = new OO.ui.FieldLayout(gameInput);


    const pkmnInput = new OO.ui.ComboBoxInputWidget({
        placeholder: mw.config.get('breedingchains-pkmn-input-placeholder'),
        value: qsParams.targetPkmn || undefined,
        options: arrayToOptionsArray(pkmnNames)
    });
    const pkmnInputField = new OO.ui.FieldLayout(pkmnInput);


    const moveInput = new OO.ui.ComboBoxInputWidget({
        placeholder: mw.config.get('breedingchains-move-input-placeholder'),
        value: qsParams.targetMove || undefined,
        options: []
    });
    const moveInputField = new OO.ui.FieldLayout(moveInput);


    const displayDebugLogs = new OO.ui.CheckboxInputWidget({
        selected: qsParams.displayDebugLogs || false
    });
    const displayDebugLogsField = new OO.ui.FieldLayout(
        displayDebugLogs, {label: 'display debug logs', align: 'inline'});


    const displayStatusLogs = new OO.ui.CheckboxInputWidget({
        selected: qsParams.displayStatusLogs || false
    });
    const displayStatusLogsField = new OO.ui.FieldLayout(
        displayStatusLogs, {label: 'display status logs', align: 'inline'});


    const createDetailedSuccessorFilterLogs = new OO.ui.CheckboxInputWidget({
        selected: qsParams.createDetailedSuccessorFilterLogs || false
    });
    const createDetailedSuccessorFilterLogsField = new OO.ui.FieldLayout(
        createDetailedSuccessorFilterLogs, {label: 'print detailed logs', align: 'inline'});


    const submitButton = new OO.ui.ButtonInputWidget({
        label: mw.config.get('breedingchains-submit-text'),
        icon: 'check',
        flags: 'progressive',
        type: 'submit'
    });
    const submitButtonField = new OO.ui.FieldLayout(submitButton);


    addComponents();
    changeMoveSuggestions();

    submitButton.on('click', submitForm);
    gameInput.on('change', function () {
        clearErrorsAndWarningsIfNonEmpty(gameInput, gameInputField);

        if (!gameNames.includes(gameInput.getValue())) {
            gameInputField.setWarnings([
                mw.config.get('breedingchains-unknown-game').replace('$1', gameInput.getValue())
            ]);
        }

        changeMoveSuggestions();
    });
    pkmnInput.on('change', function () {
        clearErrorsAndWarningsIfNonEmpty(pkmnInput, pkmnInputField);

        if (!pkmnNames.includes(pkmnInput.getValue())) {
            pkmnInputField.setWarnings([
                mw.config.get('breedingchains-unknown-pkmn').replace('$1', pkmnInput.getValue())
            ]);
        }

        changeMoveSuggestions();
    });
    moveInput.on('change', function () {
        clearErrorsAndWarningsIfNonEmpty(moveInput, moveInputField);
        const moveInputStr = moveInput.getValue();

        if (moveInputStr !== '' && !currentMoveSuggestions.includes(moveInputStr)) {
            moveInputField.setWarnings([
                mw.config.get('breedingchains-move-not-suggested')
                                .replace('$1', moveInputStr)
                                .replace('$2', pkmnInput.getValue())
            ]);
        }
    });

    $('#specialBreedingChainsLoadingBar').remove();
    setInitialScrollState();

    function clearErrorsAndWarningsIfNonEmpty (input, field) {
        if (input.getValue() !== '') {
            field.setErrors([]).setWarnings([]);
        }
    }

    function arrayToOptionsArray (array) {
        const result = [];
        for (const item of array) {
            result.push({data: item});
        }
        return result;
    }

    function addComponents () {
        const wrapperId = 'specialBreedingChainsFormContainer';

        const outerFieldset = new OO.ui.FieldsetLayout({
            id: 'specialBreedingChainsForm'
        });
        const innerFieldSets = [];

        const textInputFieldset = new OO.ui.FieldsetLayout();
        textInputFieldset.addItems([
            gameInputField,
            pkmnInputField,
            moveInputField
        ]);
        innerFieldSets.push(textInputFieldset);

        if (mw.config.get('breedingchains-display-debug-checkboxes')) {
            const buttonFieldSet = new OO.ui.FieldsetLayout();
            buttonFieldSet.addItems([
                displayDebugLogsField,
                displayStatusLogsField,
                createDetailedSuccessorFilterLogsField
            ]);

            innerFieldSets.push(buttonFieldSet);
        }

        const submitFieldSet = new OO.ui.FieldsetLayout();
        submitFieldSet.addItems([
            submitButtonField
        ]);
        innerFieldSets.push(submitFieldSet);


        outerFieldset.addItems(innerFieldSets);

        $(`#${wrapperId}`).append(
            outerFieldset.$element
        );
    }

    function changeMoveSuggestions () {
        moveInput.setOptions([]);

        const pkmnName = pkmnInput.getValue();
        const suggestions = moveSuggestions[pkmnName];
        if (suggestions === undefined) {
            console.debug('unknown pkmn name ' + pkmnName);
            return;
        }
        const game = gameInput.getValue();
        const targetSuggestions = getMoveSuggestionsForGame(suggestions, game);
        console.debug('new moves:' + targetSuggestions);
        const targetSuggestionsAsOptionsArray = arrayToOptionsArray(targetSuggestions);

        moveInput.setOptions(targetSuggestionsAsOptionsArray);
        currentMoveSuggestions = targetSuggestions;
    }

    function getMoveSuggestionsForGame (suggestions, game) {
        if (!Array.isArray(suggestions)) {
            console.debug('invalid argument, suggestions must be an array');
            return [];
        }

        const gameSk = gameToSk[game];
        if (gameSk === undefined) {
            console.debug('unknown game ' + game);
        }

        const suggestionBlock = suggestions.find(function (item) {
            return item.games.includes(gameSk);
        });

        if (suggestionBlock !== undefined) {
            return suggestionBlock.moves;
        } else {
            return [];
        }
    }

    function submitForm () {
        const textInputErrorStatus = checkForInputErrors();
        if (textInputErrorStatus) {
            return;
        }

        const baseUrl = window.location.origin + window.location.pathname;

        const qs = new URLSearchParams({
            'targetGame': gameInput.getValue(),
            'targetPkmn': pkmnInput.getValue(),
            'targetMove': moveInput.getValue(),
        });

        if (displayDebugLogs.isSelected !== undefined && displayDebugLogs.isSelected()) {
            qs.append('displayDebugLogs', '1');
        }
        if (displayStatusLogs.isSelected !== undefined && displayStatusLogs.isSelected()) {
            qs.append('displayStatusLogs', '1');
        }
        if (createDetailedSuccessorFilterLogs.isSelected !== undefined
                && createDetailedSuccessorFilterLogs.isSelected()) {
            qs.append('createDetailedSuccessorFilterLogs', '1');
        }

        const targetUrl = baseUrl + '?' + qs.toString();

        open(targetUrl, '_self');
    }

    function checkForInputErrors () {
        let errorOccured = false;

        if (gameInput.getValue() === '') {
            gameInputField.setErrors([mw.config.get('breedingchains-game-required')]);
            errorOccured = true;
        }
        if (pkmnInput.getValue() === '') {
            pkmnInputField.setErrors([mw.config.get('breedingchains-pkmn-required')]);
            errorOccured = true;
        }
        if (moveInput.getValue() === '') {
            moveInputField.setErrors([mw.config.get('breedingchains-move-required')]);
            errorOccured = true;
        }

        return errorOccured;
    }

    function setInitialScrollState () {
        const msgBoxes = $('.breedingChainsMessageBox');
        if (msgBoxes.length) {
            msgBoxes[0].scrollIntoView({
                behavior: 'smooth'
            });
            return;
        }

        const breedingChainsSVGMap = $('#breedingChainsSVGMap');
        if (breedingChainsSVGMap.length) {
            breedingChainsSVGMap[0].scrollIntoView({
                behavior: 'smooth'
            });
        }

    }
});
$( function () {
    const visualStructure = mw.config.get('breedingchains-visual-structure');
    if (visualStructure !== null) {
        const container = document.getElementById('breedingChainsSVGMap');

        const CONTAINER_WIDTH = container.clientWidth;
        const VISUAL_STRUCTURE_VIEWBOX = visualStructure.viewbox;
        const VISUAL_STRUCTURE_VIEWBOX_VALUES = VISUAL_STRUCTURE_VIEWBOX.split(' ');
        const VISUAL_STRUCTURE_WIDTH = VISUAL_STRUCTURE_VIEWBOX_VALUES[2];
        const VISUAL_STRUCTURE_HEIGHT = VISUAL_STRUCTURE_VIEWBOX_VALUES[3];

        /*mouse wheel events are handled strangely across browsers.
        Some work as intended via the zoomDelta option, some not and for those
        the wheelPxPerZoomLevel option enables a zoom change per scrolled pixels.
        60px per mouse wheel click is an inaccurate estimate that just works*/
        const SCROLL_IN_PIXELS = 60;
        const WANTED_ZOOM_DELTA = 0.5;
        const ZOOM_DELTA_IN_PX_PERCENTAGE = SCROLL_IN_PIXELS / WANTED_ZOOM_DELTA;

        const map = L.map('breedingChainsSVGMap', {
            crs: L.CRS.Simple,
            center: calcCenterOffsets(),
            zoom: 0,
            minZoom: -5,
            maxZoom: 2,
            zoomSnap: 0,
            zoomDelta: WANTED_ZOOM_DELTA,
            wheelPxPerZoomLevel: ZOOM_DELTA_IN_PX_PERCENTAGE,
            attributionControl: false,
            zoomControl: false
        });

        const zoomControl = L.control.zoom({
            position: 'bottomleft'
        });
        map.addControl(zoomControl);

        const attributionControl = L.control.attribution({
            position: 'topright',
            prefix: ''
        }).addAttribution(mw.config.get('breedingchains-whatshappening'));
        map.addControl(attributionControl);

        main();

        function main () {
            console.time('creating leaflet map');
            addVisualElements(visualStructure.innerContent);
            //addHelpingLines();
            addResetButton();
            console.timeEnd('creating leaflet map');
        }

        function calcCenterOffsets () {
            const MOBILE_LAYOUT_CENTERING_OFFSETS = [VISUAL_STRUCTURE_HEIGHT / 2, CONTAINER_WIDTH / 2];
            const STANDARD_LAYOUT_CENTERING_OFFSETS = [VISUAL_STRUCTURE_HEIGHT / 2, VISUAL_STRUCTURE_WIDTH / 2];

            if (svgWidthExceedsContainerWidth()) {
                //console.debug('mobile layout');
                return MOBILE_LAYOUT_CENTERING_OFFSETS;
            } else {
                //console.debug('standard layout');
                return STANDARD_LAYOUT_CENTERING_OFFSETS;
            }
        }

        function svgWidthExceedsContainerWidth () {
            return CONTAINER_WIDTH < VISUAL_STRUCTURE_WIDTH;
        }

        function addVisualElements (visualElements) {
            addSVGBackgroundElements(visualElements);
            addSVGFrontElements(visualElements);
        }
        
        function addSVGBackgroundElements (visualElements) {
            const backgroundElements = [ 'line' ];
            addSVGElementsType(visualElements, backgroundElements);
        }
        
        function addSVGFrontElements (visualElements) {
            const frontElements = ['circle', 'image', 'text', 'a'];
            addSVGElementsType(visualElements, frontElements);
        }

        function addSVGElementsType (visualElements, includedElementTypes) {
            for (const el of visualElements) {
                if (includedElementTypes.includes(el.tag)) {
                    addSVGElement(el);
                }
            }
        }

        function addSVGElement (visualElement) {
            switch (visualElement.tag) {
                case 'circle':
                    addCircle(visualElement);
                    break;
                case 'a':
                    addLink(visualElement);
                    break;
                case 'line':
                    addLine(visualElement);
                    break;
                case 'text':
                    addText(visualElement);
                    break;
                case 'image':
                    addImage(visualElement);
                    break;
                default:
                    console.error('tried to add unexpected visual element of type ' + visualElement.tag);
                    console.error(visualElement);
            }
        }
        
        function addCircle (visualCircle) {
            const x = Number(visualCircle.cx);
            const y = Number(visualCircle.cy);
            const r = Number(visualCircle.r);
            const color = visualCircle.color;
        
            const leafletCircle = L.circle([y, x], {
                radius: r,
                color,
                weight: 4,
                className: 'breedingChainsLeafletCircle'
            });
        
            addPkmnPopup(visualCircle, leafletCircle);
        
            leafletCircle.addTo(map);
        }
        
        function addPkmnPopup (visualEl, leafletEl) {
            const groupId = visualEl.groupid;
            const learnability = visualEl.learnability;
        
            const pkmnLinks = findVisualElementsByGroupAndTag(groupId, 'a');
        
            if (pkmnLinks.length !== 1) {
                console.error('addImage: pkmnLinks array has unexpected length ' + pkmnLinks.length);
            } else {
                const text = document.createElement('div');
        
                const pkmnLink = createPkmnLinkTag(pkmnLinks);
                text.appendChild(pkmnLink);
                const learnabilityString = buildLearnabilityString(learnability);
                if (learnabilityString !== '') {
                    pkmnLink.classList.add('breedingchains-popup-non-single-link')
                    text.appendChild(learnabilityString);
                }
        
                leafletEl.bindPopup(text);
            }
        }
        
        //todo this only searches through top level tags (names implies a complete search)
        function findVisualElementsByGroupAndTag (groupId, tagType) {
            if (isNaN(groupId)) {
                console.error('findVisualElementsByGroupAndTag: param groupId of tag '
                    + tagType + ' is not a number: ' + groupId);
                return [];
            }
        
            const filteredArray = [];
        
            for (const visualChild of visualStructure.innerContent) {
                if (visualChild.groupid !== undefined
                        && visualChild.groupid === groupId
                        && (tagType === undefined || visualChild.tag === tagType)) {
                    filteredArray.push(visualChild);
                }
            }
        
            return filteredArray;
        }
        
        function createPkmnLinkTag (pkmnLinks) {
            const pkmnLink = pkmnLinks[0].href;
        
            const linkTag = document.createElement('a');
            linkTag.href = pkmnLink;
        
            let linkText = pkmnLinks[0]['pkmn-name'];
            const linkTextNode = document.createTextNode(linkText);
            linkTag.appendChild(linkTextNode);
        
            return linkTag;
        }
        
        function buildLearnabilityString (learnability) {
            if (learnability === '') {
                return '';
            }
            const textDiv = document.createElement('div');
            const headerText = document.createTextNode(mw.config.get('breedingchains-popup-header'));
            textDiv.appendChild(headerText);
            const list = document.createElement('ul');
            textDiv.appendChild(list);
            for (const char of learnability) {
                const listItem = document.createElement('li');
                const itemText = document.createTextNode(learnabilityCharToDescription(char));
                listItem.appendChild(itemText);
                list.appendChild(listItem);
            }

            return textDiv;
        }
        
        function learnabilityCharToDescription (learnabilityChar) {
            switch (learnabilityChar) {
                case 'd':
                    return mw.config.get('breedingchains-popup-learns-d');
                case 'b':
                    return mw.config.get('breedingchains-popup-learns-b');
                case 'o':
                    return mw.config.get('breedingchains-popup-learns-o');
                case 'e':
                    return mw.config.get('breedingchains-popup-learns-e');
                default:
                    return mw.config.get('breedingchains-popup-error').replace('$1', learnabilityChar);
            }
        }

        function addLink (svgLink) {
            addVisualElements(svgLink.innerContent);
        }
        
        function addLine (svgLine) {
            const x1 = Number(svgLine.x1);
            const x2 = Number(svgLine.x2);
            const y1 = Number(svgLine.y1);
            const y2 = Number(svgLine.y2);
        
            L.polyline([
                [y1, x1],
                [y2, x2]
            ], {
                className: 'breedingChainsLeafletLine'
            }).addTo(map);
        }
        
        function addText (visualText) {
            const text = visualText.text;
        
            const textMetrics = getTextMetrics(text, getCanvasFont());
        
            const x = getLeafletTextXCoordinate(visualText.groupid);
            const y = Number(visualText.y);
        
            L.marker([y + 2, x], {
                icon: L.divIcon({
                    html: text,
                    iconSize: [textMetrics.width, textMetrics.height],
                    className: 'breedingChainsLeafletText'
                })
            }).addTo(map);
        }
        
        function getLeafletTextXCoordinate (groupId) {
            const lines = findVisualElementsByGroupAndTag(groupId, 'line');
        
            if (lines.length === 0) {
                console.error('couldnt find line for text ' + svgText);
                return 0;
            }
        
            const x1 = Number(lines[0].x1);
            const x2 = Number(lines[0].x2);
        
            const lineWidth = Math.abs(x2 - x1);
            const xDiff = lineWidth / 2;
            const textX = x1 + xDiff;
        
            return textX;
        }
        
        function addImage (visualImage) {
            const x = Number(visualImage.x);
            const y = Number(visualImage.y);
            const width = Number(visualImage.width);
            const height = Number(visualImage.height);
            const href = visualImage['xlink:href'];
        
            const icon = L.icon({
                iconUrl: href,
                iconSize: [width, height],
                className: 'breedingChainsLeafletIcon'
            });
        
            const marker = L.marker([y + height/2, x + width/2], {
                icon
            })
        
            addPkmnPopup(visualImage, marker);
        
            marker.addTo(map);
        }
        
        function addHelpingLines () {
            const RADIUS = 5;
            const COLOR = 'black;'
            L.circle([0, 0], {
                radius: RADIUS,
                color: COLOR,
                className: 'breedingChainsLeafletCircle'
            }).addTo(map);
        
            L.polyline([
                [0, 0],
                [100, 0]
            ], {
                className: 'breedingChainsLeafletLine'
            }).addTo(map);
        
            L.polyline([
                [0, 0],
                [0, 100]
            ], {
                className: 'breedingChainsLeafletLine'
            }).addTo(map);
        }
        
        function addResetButton () {
            const button = document.createElement('img');
            button.id = 'breedingChainsMapResetButton';
            button.src = '../extensions/BreedingChains/img/Clockwise_Arrow.svg';
        
            button.addEventListener('click', resetMap);
        
            container.appendChild(button);
        }

        function resetMap () {
            map.setView(calcCenterOffsets(), 0);
        }
        
        /**
          * Uses canvas.measureText to compute and return the width of the given text of given font in pixels.
          * 
          * @param {String} text The text to be rendered.
          * @param {String} font The css font descriptor that text is to be rendered with (e.g. "bold 14px verdana").
          * 
          * @see https://stackoverflow.com/questions/118241/calculate-text-width-with-javascript/21015393#21015393 (adjusted it a bit myself)
          */
        function getTextMetrics(text, font) {
            // re-use canvas object for better performance
            const canvas = getTextMetrics.canvas || (getTextMetrics.canvas = document.createElement("canvas"));
            const context = canvas.getContext("2d");
            context.font = font;
            const metrics = context.measureText(text);
            return metrics;
        }
        
        function getCanvasFont(el = document.body) {
          const fontWeight = getCssStyle(el, 'font-weight') || 'normal';
          const fontSize = getCssStyle(el, 'font-size') || '16px';
          const fontFamily = getCssStyle(el, 'font-family') || 'Times New Roman';
          
          return `${fontWeight} ${fontSize} ${fontFamily}`;
        }
        
        function getCssStyle(element, prop) {
            return window.getComputedStyle(element, null).getPropertyValue(prop);
        }
    }
});
const svgTag = document.getElementById('breedingChainsSVG');
if (svgTag !== null) {


const svgChildren = svgTag.children;
const svgMap = document.getElementById('breedingChainsSVGMap');

const SVG_CONTAINER_WIDTH = svgMap.clientWidth;
const SVG_TAG_VIEWBOX = svgTag.attributes.viewBox.value;
const SVG_TAG_VIEWBOX_VALUES = SVG_TAG_VIEWBOX.split(' ');
const SVG_WIDTH = SVG_TAG_VIEWBOX_VALUES[2];
const SVG_HEIGHT = SVG_TAG_VIEWBOX_VALUES[3];

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
    addSVGElements(svgChildren);
    svgTag.style.display = 'none';
    //addHelpingLines();
    addResetButton();
    console.timeEnd('creating leaflet map');
}

function calcCenterOffsets () {
    const MOBILE_LAYOUT_CENTERING_OFFSETS = [SVG_HEIGHT / 2, SVG_CONTAINER_WIDTH / 2];
    const STANDARD_LAYOUT_CENTERING_OFFSETS = [SVG_HEIGHT / 2, SVG_WIDTH / 2];

    if (svgWidthExceedsContainerWidth()) {
        //console.debug('mobile layout');
        return MOBILE_LAYOUT_CENTERING_OFFSETS;
    } else {
        //console.debug('standard layout');
        return STANDARD_LAYOUT_CENTERING_OFFSETS;
    }
}

function svgWidthExceedsContainerWidth () {
    return SVG_CONTAINER_WIDTH < SVG_WIDTH;
}

function addSVGElements (svgElements) {
    addSVGBackgroundElements(svgElements);
    addSVGFrontElements(svgElements);
}

function addSVGBackgroundElements (svgElements) {
    const backgroundElements = [ 'line' ];
    addSVGElementsType(svgElements, backgroundElements);
}

function addSVGFrontElements (svgElements) {
    const frontElements = ['circle', 'image', 'text', 'a'];
    addSVGElementsType(svgElements, frontElements);
}

function addSVGElementsType (svgElements, includedElementTypes) {
    for (const el of svgElements) {
        if (includedElementTypes.includes(el.tagName)) {
            addSVGElement(el);
        }
    }
}

function addSVGElement (svgElement) {
    switch (svgElement.tagName) {
        case 'circle':
            addCircle(svgElement);
            break;
        case 'a':
            addLink(svgElement);
            break;
        case 'line':
            addLine(svgElement);
            break;
        case 'text':
            addText(svgElement);
            break;
        case 'image':
            addImage(svgElement);
            break;
        default:
            console.error('tried to add unexpected svgElement of type ' + svgElement.tagName);
            console.error(svgElement);
    }
}

function addCircle (svgCircle) {
    const x = Number(svgCircle.attributes.cx.value);
    const y = Number(svgCircle.attributes.cy.value);
    const r = Number(svgCircle.attributes.r.value);
    const color = svgCircle.attributes.color.value;

    const circle = L.circle([y, x], {
        radius: r,
        color,
        weight: 4,
        className: 'breedingChainsLeafletCircle'
    });

    addPkmnPopup(svgCircle, circle);

    circle.addTo(map);
}

function addPkmnPopup (svgEl, leafletEl) {
    const groupId = svgEl.attributes.groupid.value;
    const learnability = svgEl.attributes.learnability.value;

    const pkmnLinks = findSVGElements(groupId, 'a');

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

function findSVGElements (groupId, tagType) {
    if (isNaN(groupId)) {
        console.error('findSVGElements: param groupId is not a number: ' + groupId);
        return [];
    }

    const filteredArray = [];

    for (const svgChild of svgChildren) {
        if (svgChild.attributes.groupid.value !== undefined
                && svgChild.attributes.groupid.value === groupId
                && (tagType === undefined || svgChild.tagName === tagType)) {
            filteredArray.push(svgChild);
        }
    }

    return filteredArray;
}

function createPkmnLinkTag (pkmnLinks) {
    const pkmnLink = pkmnLinks[0].attributes.href.value;

    const linkTag = document.createElement('a');
    linkTag.href = pkmnLink;

    let linkText = pkmnLinks[0].attributes['pkmn-name'].value;
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
    addSVGElements(svgLink.children);
}

function addLine (svgLine) {
    const x1 = Number(svgLine.attributes.x1.value);
    const x2 = Number(svgLine.attributes.x2.value);
    const y1 = Number(svgLine.attributes.y1.value);
    const y2 = Number(svgLine.attributes.y2.value);

    L.polyline([
        [y1, x1],
        [y2, x2]
    ], {
        className: 'breedingChainsLeafletLine'
    }).addTo(map);
}

function addText (svgText) {
    const text = svgText.textContent;

    const bbox = svgText.getBBox();
    const width = bbox.width;
    const height = bbox.height;

    const x = getLeafletTextXCoordinate(svgText.attributes.groupid.value);
    const y = Number(svgText.attributes.y.value);

    L.marker([y - 2, x], {
        icon: L.divIcon({
            html: text,
            iconSize: [width, height],
            className: 'breedingChainsLeafletText'
        })
    }).addTo(map);
}

function getLeafletTextXCoordinate (groupId) {
    const lines = findSVGElements(groupId, 'line');

    if (lines.length === 0) {
        console.error('couldnt find line for text ' + svgText);
        return 0;
    }

    const x1 = Number(lines[0].attributes.x1.value);
    const x2 = Number(lines[0].attributes.x2.value);

    const lineWidth = Math.abs(x2 - x1);
    const xDiff = lineWidth / 2;
    const textX = x1 + xDiff;

    return textX;
}

function addImage (svgImage) {
    const x = Number(svgImage.attributes.x.value);
    const y = Number(svgImage.attributes.y.value);
    const width = Number(svgImage.attributes.width.value);
    const height = Number(svgImage.attributes.height.value);
    const href = svgImage.attributes['xlink:href'].value;

    const icon = L.icon({
        iconUrl: href,
        iconSize: [width, height],
        className: 'breedingChainsLeafletIcon'
    });

    const marker = L.marker([y + height/2, x + width/2], {
        icon
    })

    addPkmnPopup(svgImage, marker);

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

    svgMap.appendChild(button);
}

function resetMap () {
    map.setView(calcCenterOffsets(), 0);
}

}
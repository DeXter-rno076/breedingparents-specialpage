const svgTag = document.getElementById('breedingParentsSVG');
const svgChildren = svgTag.children;
const svgMap = document.getElementById('breedingParentsSVGMap');

const SVG_CONTAINER_WIDTH = svgMap.clientWidth;
const SVG_CONTAINER_HEIGHT = svgMap.clientHeight;
const SVG_WIDTH = svgTag.width.baseVal.value;
const SVG_HEIGHT = svgTag.height.baseVal.value;

/*mouse wheel events are handled strangely across browsers.
Some work as intended via the zoomDelta option, some not and for those
the wheelPxPerZoomLevel option enables a zoom change per scrolled pixels.
60px per mouse wheel click is an inaccurate estimate that just works*/
const SCROLL_IN_PIXELS = 60;
const WANTED_ZOOM_DELTA = 0.5;
const ZOOM_DELTA_IN_PX_PERCENTAGE = SCROLL_IN_PIXELS / WANTED_ZOOM_DELTA;

const MAX_BOUNDS_X_PADDING = 100;
const MAX_BOUNDS_Y_PADDING = 160;

const overlayBounds = [
	[0, 0],
	[SVG_HEIGHT, SVG_WIDTH]
];

const map = L.map('breedingParentsSVGMap', {
	crs: L.CRS.Simple,
	center: calcCenterOffsets(),
	zoom: 0,
	minZoom: -5,
	maxZoom: 4,
	zoomSnap: 0,
	zoomDelta: WANTED_ZOOM_DELTA,
	wheelPxPerZoomLevel: ZOOM_DELTA_IN_PX_PERCENTAGE,
	attributionControl: false
});

map.setMaxBounds(new L.LatLngBounds(
	map.unproject([
		-SVG_CONTAINER_WIDTH + MAX_BOUNDS_X_PADDING,
		SVG_CONTAINER_HEIGHT - MAX_BOUNDS_Y_PADDING
	], 0),
	map.unproject([
		SVG_WIDTH + SVG_CONTAINER_WIDTH - MAX_BOUNDS_X_PADDING,
		-SVG_HEIGHT - SVG_CONTAINER_HEIGHT + MAX_BOUNDS_Y_PADDING
	], 0)
));

const svgOverlay = L.svgOverlay(svgTag, overlayBounds).addTo(map);

addPkmnPopups();

function calcCenterOffsets () {
	const MOBILE_LAYOUT_CENTERING_OFFSETS = [SVG_HEIGHT / 2, SVG_CONTAINER_WIDTH / 2];
	const STANDARD_LAYOUT_CENTERING_OFFSETS = [SVG_HEIGHT / 2, SVG_WIDTH / 2];

	if (svgWidthExceedsContainerWidth()) {
		return MOBILE_LAYOUT_CENTERING_OFFSETS;
	} else {
		return STANDARD_LAYOUT_CENTERING_OFFSETS;
	}
}

function svgWidthExceedsContainerWidth () {
	return SVG_CONTAINER_WIDTH < SVG_WIDTH;
}

function addPkmnPopups () {
	for (const svgChild of svgChildren) {
		if (svgChild.tagName !== 'a') {
			continue;
		}
		addPkmnPopup(svgChild);
	}
}

function addPkmnPopup (pkmnTag) {
	const link = pkmnTag.href.baseVal;
	const pkmnName = link.split('/')[0];
	const imageTag = pkmnTag.children[0];

	const width = imageTag.width.baseVal.value;
	const height = imageTag.height.baseVal.value;

	const x = imageTag.x.baseVal.value;
	//leafletjs and svg use different coordinate systems
	const y = SVG_HEIGHT - height - imageTag.y.baseVal.value;

	const rectangle = createPkmnPopup(x, y, width, height);

	const popupLink = createPopupLinkTag(pkmnName, link);
	
	rectangle.bindPopup(popupLink);

	rectangle.addTo(map);
}

function createPkmnPopup (x, y, width, height) {
	const PADDING = 5;
	return L.rectangle(
		[
			[y - PADDING, x - PADDING],
			[y + height + PADDING, x + width + PADDING]
		], {
			opacity: 0,
			fillOpacity: 0
	});
}

function createPopupLinkTag (pkmnName, link) {
	const popupLink = document.createElement('a');
	popupLink.href = link;
	popupLink.target = '_blank';
	const popupLinkText = document.createTextNode(pkmnName);
	popupLink.appendChild(popupLinkText);
	return popupLink;
}
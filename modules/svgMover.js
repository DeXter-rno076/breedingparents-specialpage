'use strict';
const svgContainer = document.getElementById('breedingParentsSVGContainer');
const svgTag = document.getElementById('breedingParentsSVG');

//offsets of the svgTag when the user started to move it
let yOffset = 0;
let xOffset = 0;

//mouse/touch coordinates when the user started to move the svg
let cursorStartingX = 0;
let cursorStartingY = 0;

main();

function main () {
    initSVGInlineStyles();
    centerSVG();
    addListeners();
}

function centerSVG () {
    const svgWidth = svgTag.width.baseVal.value;
    const svgHeight = svgTag.height.baseVal.value;

    const containerWidth = svgContainer.clientWidth;
    const containerHeight = svgContainer.clientHeight;

    const xOffset = (containerWidth - svgWidth) / 2;
    const yOffset = (containerHeight - svgHeight) / 2;

    setOffset(xOffset, yOffset);
}

function initSVGInlineStyles () {
    svgTag.style.transform = 'scale(1.00)';
    svgTag.style.left = '0px';
    svgTag.style.top = '0px';
}

function addListeners () {
    svgContainer.addEventListener('mousedown', mouseStart);
    svgContainer.addEventListener('mouseup', mouseStop);
    svgContainer.addEventListener('touchstart', touchStart);
    svgContainer.addEventListener('touchmove', touchMove);
    svgContainer.addEventListener('wheel', zoomMouse);
}

function mouseStart (event) {
    startMoving('mouse', event.clientX, event.clientY);
}

function touchStart (event) {
    const firstTouch = event.targetTouches[0];
    startMoving('touch', firstTouch.clientX, firstTouch.clientY);
}

function startMoving (type, x, y) {
	cursorStartingX = x;
	cursorStartingY = y;
	xOffset = parseInt(svgTag.style.left);
	yOffset = parseInt(svgTag.style.top);

    if (type === 'mouse') {
	    svgContainer.addEventListener('mousemove', moveSVG);
    }
}

function mouseStop () {
	svgContainer.removeEventListener('mousemove', moveSVG);
}

function touchMove (event) {
    event.preventDefault();
    const firstTouch = event.targetTouches[0];
    moveSVG(firstTouch);
}

function moveSVG (event) {
	let x = event.clientX;
	let y = event.clientY;

	let dx = cursorStartingX - x;
	let dy = cursorStartingY - y;

	let newX = xOffset - dx;
	let newY = yOffset - dy;

	setOffset(newX, newY);
}

function zoomMouse (event) {
    event.preventDefault();
    const wheelUp = event.deltaY < 0;
    const currentTransform = svgTag.style.transform;
    if (!/scale\(.+?\)/.test(currentTransform)) {
        //user played around with the transform value
        svgTag.style.transform = 'scale(1)';
        return;
    }

    //this feels shady af, but scale is saved like that
    const currentZoom = parseFloat(currentTransform.replace('scale(', '').replace(')', ''));

    let newZoom = 1;
    const zoomChange = currentZoom * 0.05;
    if (wheelUp) {
        newZoom = currentZoom + zoomChange;
    } else {
        newZoom = currentZoom - zoomChange;
    }

    if (isNaN(newZoom)) {
        newZoom = 1;
    } else if (newZoom <= 0.1) {
        newZoom = 0.1;
    } else if (newZoom > 5) {
        newZoom = 5;
    }

    //todo
    // const currentXOffset = parseInt(svgTag.style.left);
    // const currentYOffset = parseInt(svgTag.style.top);

    // const plainMx = svgTag.width.baseVal.value / 2;
    // const plainMy = svgTag.height.baseVal.value / 2;

    // const realMx = plainMx + currentXOffset;
    // const realMy = plainMy + currentYOffset;


    //c means cursor
    // let cx = event.offsetX;
    // let cy = event.offsetY;

    // let xDiff = cx / prevX;
    // prevX = cx;
    // let zoomDiff = currentZoom / prevZoom;
    // prevZoom = currentZoom;

    // if (event.target.id === 'breedingParentsSVG') {
    //     cx = cx * currentZoom + currentXOffset;
    //     cy = cy * currentZoom + currentYOffset;
    // }

    // const cmx = realMx - cx;
    // const cmy = realMy - cy;

    // const xPush = cmx * (newZoom - 1);
    // const yPush = cmy * (newZoom - 1);

    global_currentZoom = newZoom;
    svgTag.style.transform = 'scale(' + newZoom + ')';
    //svgTag.style.left = currentXOffset + xPush + 'px';
    //svgTag.style.top = currentYOffset + yPush + 'px';
}

function setOffset (x, y) {
    svgTag.style.left = x + 'px';
    svgTag.style.top = y + 'px';
}
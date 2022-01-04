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
    console.log(svgContainer);
    const svgWidth = svgTag.width.baseVal.value;
    const svgHeight = svgTag.height.baseVal.value;

    const containerWidth = svgContainer.clientWidth;
    const containerHeight = svgContainer.clientHeight;

    console.log('svg: ' + svgWidth + ' ' + svgHeight);
    console.log('container: ' + containerWidth + ' ' + containerHeight);

    const xOffset = (containerWidth - svgWidth) / 2;
    const yOffset = (containerHeight - svgHeight) / 2;

    console.log(xOffset + ' ' + yOffset);

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

function setOffset (x, y) {
    svgTag.style.left = x + 'px';
    svgTag.style.top = y + 'px';
}
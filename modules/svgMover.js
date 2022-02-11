'use strict';

const svgContainer = document.getElementById('breedingParentsSVGContainer');
const svgTag = document.getElementById('breedingParentsSVG');

//change when screen orientation changes
let svgWidth = svgTag.width.baseVal.value;
let svgHeight = svgTag.height.baseVal.value;
let containerWidth = svgContainer.clientWidth;
let containerHeight = svgContainer.clientHeight;

const ZOOM_IN = true;
const ZOOM_OUT = false;
const TOUCHES_MAXIMUM = 2;

const TOUCH_MODE_MOVING = 1;
const TOUCH_MODE_ZOOMING = 2;

const debugDiv = document.getElementById('mw-debug-html');

let global_currentZoom = 1;

function debugOut (msg) {
	debugDiv.innerHTML = msg + '<br />' + debugDiv.innerHTML;
}

const touchPair = {
	touches: [],

	addTouch (touchEvent) {
		const eventTouches = touchEvent.touches;
		if (this.isFull()) {
			return;
		}

		if (this.isEmpty()) {
			const firstTouch = this.getFirstTouch(eventTouches);
			initTouchMoving(firstTouch);
			this.touches.push(firstTouch);
			return;
		}
		this.touches.push(this.getSecondTouch(eventTouches));
	},

	getFirstTouch (touches) {
		return this.getTouchConditionally(touches, function () {
			return true;
		});
	},

	getSecondTouch (touches) {
		if (this.isEmpty()) {
			throw 'touch pair got scrambled: Attempting to add a second touch to an empty touch pair list';
		}

		return this.getTouchConditionally(touches, function (touch) {
			return touchPair.touches[0].identifier !== touch.identifier;
		});
	},

	getTouchConditionally (touches, condition) {
		for (const touch of touches) {
			if (condition(touch)) {
				return touch;
			}
		}
		return null;
	},

	getMultipleTouchesConditionally (touches, condition) {
		const foundTouches = [];

		for (const touch of touches) {
			if (condition(touch)) {
				foundTouches.push(touch);
			}
		}

		return foundTouches;
	},


	removeTouch (touchEvent) {
		const eventTouches = touchEvent.touches;
		let idList = '';
		for (let i = 0; i < eventTouches.length; i++) {
			idList += eventTouches[i].identifier + ', ';
		}

		for (let i = 0; i < this.touches.length; i++) {
			if (!this.touchListIncludes(eventTouches, this.touches[i])) {
				this.touches.splice(i, 1);
				i--;
			}
		}
		if (this.isEmpty()) {
			document.removeEventListener('touchmove', touchMove);
		} else {
			const firstTouch = this.getFirstTouch(this.touches);
			initMovementVariables(firstTouch.clientX, firstTouch.clientY);
		}
	},

	touchListIncludes (touchList, targetTouch) {
		for (const touch of touchList) {
			if (this.isEqualTouches(touch, targetTouch)) {
				return true;
			}
		}
		return false;
	},

	getMovedTouchCoordinatesDifference (event) {
		const eventTouches = event.touches;

		const newTouchesStates = this.getMultipleTouchesConditionally(eventTouches, function (touch) {
			return touchPair.touchListIncludes(touchPair.touches, touch);
		});

		return this.getChangedTouch(newTouchesStates);
	},

	getChangedTouch (newTouchesStates) {
		for (const oldTouchState of this.touches) {
			const newTouchState = this.getTouchConditionally(newTouchesStates, function (newTouchState) {
				return touchPair.isEqualTouches(newTouchState, oldTouchState);
			});

			const otherTouch = this.getTouchConditionally(this.touches, function (touch) {
				return !touchPair.isEqualTouches(oldTouchState, touch);
			})

			const diff = this.calculateCoordinateSumDiff(otherTouch, oldTouchState, newTouchState);
			if (diff !== 0) {
				return diff;
			}
		}
		return 0;
	},

	calculateCoordinateSumDiff (otherTouch, oldTouchState, newTouchState) {
		//checking the increase or decrease of the x and y distances is enough to
		//determine zoom in or zoom out. Exact distance is not needed.

		const oldx = Math.abs(otherTouch.clientX - oldTouchState.clientX);
		const oldy = Math.abs(otherTouch.clientY - oldTouchState.clientY);

		const newx = Math.abs(otherTouch.clientX - newTouchState.clientX);
		const newy = Math.abs(otherTouch.clientY - newTouchState.clientY);

		const diffX = newx - oldx;
		const diffY = newy - oldy;

		return diffX + diffY;
	},

	isEqualTouches (a, b) {
		return a.identifier === b.identifier;
	},

	isFull () {
		return this.touches.length === TOUCHES_MAXIMUM;
	},

	isEmpty () {
		return this.touches.length === 0;
	},

	getCurrentMovementMode () {
		if (this.touches.length < 2) {
			return TOUCH_MODE_MOVING;
		}
		return TOUCH_MODE_ZOOMING;
	},

	updateTouches (touchEvent) {
		this.touches = [];
		const eventTouches = touchEvent.touches;
		
		for (let i = 0; i < TOUCHES_MAXIMUM && i < eventTouches.length; i++) {
			this.touches.push(eventTouches[i]);
		}
	}
};

main();

function main () {
    initSVGInlineStyles();
    centerSVG();
    addListeners();
}

function initSVGInlineStyles () {
    svgTag.style.transform = 'scale(1.00)';
    svgTag.style.left = '0px';
    svgTag.style.top = '0px';
}

function addListeners () {
    svgContainer.addEventListener('mousedown', initMouseMoving);
    svgContainer.addEventListener('mouseup', mouseStop);

    svgContainer.addEventListener('wheel', zoomMouse);

	svgContainer.addEventListener('touchstart', addTouch);
	svgContainer.addEventListener('touchend', removeTouch);

	//screen.orientation.addEventListener('change', alignElementsForChangedScreenOrientation);
}

function addTouch (event) {
	touchPair.addTouch(event);
}
function removeTouch (event) {
	touchPair.removeTouch(event);
}

function mouseStop () {
    svgContainer.removeEventListener('mousemove', moveSVG);
}

function alignElementsForChangedScreenOrientation (event) {
	//currently unused because it somehow doesnt exactly work as wanted
	//is supposed to update the offsets when screen orientation is switched so that the offset dimension proportions are kept
	const offset = getOffset();
	const originalXProportion = offset.x / containerWidth;
	const originalYProportion = offset.y / containerHeight;

	//debugOut(originalXProportion + ' ' + originalYProportion);

	const newXOffset = parseInt(containerHeight * originalXProportion);
	const newYOffset = parseInt(containerWidth * originalYProportion);

	const t = containerHeight;
	containerHeight = containerWidth;
	containerWidth = t;

	setOffset(newXOffset, newYOffset);
}
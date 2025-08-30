const store = function ( v, get = false ) {
    var storeValue;
    function setStore( v ) {
        this.storeValue = v;
    }
    function getStore() {
        return this.storeValue;
    }
    if( get ) {
        return getStore()
    } else {
        setStore( v );
    }
}
function draggable(e) {
    if( typeof e === "object" ) {

    } else {
        e = nj().els( "#" + e );
    }
    e.classList.add("draggable");
    let tmp = e.id.replace( "_box", "" );
    let t = nj().els( "#" + tmp + "_headline");
    //dialogTouchMove( t );
    t.className = "dragPoint"/*, initialDragPointStyling(t)*/;
    let n = e.firstChild;
    /*
    if ((null !== n ? e.insertBefore(t, n) : e.appendChild(t), e.classList.contains("resizable"))) {
        let n = e.parentElement;
        n.classList.contains("parentResize") && (resizePointsStyling(e, t), (e = n));
    }
    */
    t.onmousedown = function () {
        1 == event.which && trackMouseDragPlusAction({ action: "drag", param: [e] });
    };
    t.ontouchstart = function () {
        1 == event.which && trackMouseDragPlusAction({ action: "drag", param: [e] });
    };

}
function getDragNewPosition(e, t) {
    let n = getElementOffsetAndMeasures(e),
        i = { x: n.left - t.x, y: n.top - t.y };
    return preventDragOutsideScreen(n, i, { left: i.x, top: i.y, right: i.x + n.width, bottom: i.y + n.height });
}
function getElementOffsetAndMeasures(e) {
    return { left: e.offsetLeft, top: e.offsetTop, height: e.offsetHeight, width: e.offsetWidth };
}
function preventDragOutsideScreen(e, t, n) {
    let i = getDocumentBodyLimits();
    let borderLeftWidth = 2;
    return n.left < i.left + borderLeftWidth && (t.x = e.left), n.top < i.top && (t.y = e.top), n.right > i.right && (t.x = e.left), n.bottom > i.bottom && (t.y = e.top), t;
}
function dragAction(e, t) {
        let n = getDragNewPosition(e.param[0], t);
        store( n );
        (e.param[0].style.left = n.x + "px"), (e.param[0].style.top = n.y + "px");
}
function getItemCountToFitElementByWidth(e, t) {
    if (null != e) {
        let n = t.clientWidth,
            i = window.getComputedStyle(e),
            o = parseInt(i.getPropertyValue("width").slice(0, -2)),
            l = parseInt(i.getPropertyValue("margin-left").slice(0, -2)),
            a = parseInt(i.getPropertyValue("margin-right").slice(0, -2));
        return (elementWidth = o + l + a), Math.floor(n / elementWidth);
    }
}
function getButton(e, t) {
    let n;
    //console.log( e, t );
    for (let i = 0, o = (n = e.classList.contains("draggable") ? e.firstElementChild.firstElementChild.childNodes : e.firstElementChild.childNodes).length; i < o; i++) if (n[i].classList.contains(t)) return n[i];
}
function getElementSizeAndPosition(e) {
    let t = window.getComputedStyle(e);
    return {
        width: t.getPropertyValue("width"),
        height: t.getPropertyValue("height"),
        top: t.getPropertyValue("top"),
        left: t.getPropertyValue("left"),
        gridCol: t.getPropertyValue("grid-template-columns"),
        gridRow: t.getPropertyValue("grid-template-rows"),
    };
}
function getDocumentBodyLimits() {
//    return { left: 0, right: document.body.clientWidth, top: 0, bottom: document.body.clientHeight };
    return { left: 0, right: document.body.clientWidth, top: 0, bottom: window.innerHeight };
}
function trackMouseDragPlusAction(e) {
    let t = event.clientX,
        n = event.clientY;
    (document.onmouseup = dragMouseStop),
        (document.onmousemove = function () {
            let i = event.clientX,
                o = event.clientY;
            dragAction(e, { x: t - i, y: n - o }), (t = i), (n = o);
        });
    (document.ontouchend = dragMouseStop),
        (document.touchmove = function () {
            let i = event.clientX,
                o = event.clientY;
            dragAction(e, { x: t - i, y: n - o }), (t = i), (n = o);
        });
}
function dragMouseStop(e) {
    //console.log( store( null, true ) );
    (document.onmouseup = null), (document.onmousemove = null);
    (document.ontochend = null), (document.ontouchmove = null);
}
function resizeable(e, position = "both" ) {
    e = nj().els( "#" + e );
    console.log( getIdAndName( nj().els( e ).id ).name );
    nj( "#" + getIdAndName( nj().els( e ).id ).name ).sty( "width", "98%" );
    parent = re_getElementSizeAndPosition( nj( e ).p() );
    let correctObj = nj(  e.parentNode );
    e.classList.add("resizeable");
    e.classList.add("resizePoint");
    switch( position ) {
    case "both":
            nj( e ).aCl( "resBoth" );
            nj(e).sty({ left: parseInt( parent.width )  - 20 + "px", top: parseInt( parent.height ) + nj( e.previousElementSibling ).gRe().height + "px"} );  // + footer height
        break
    }
    e.onmousedown = function () {
        1 == event.which && re_trackMouseDragPlusAction({ action: "drag", param: [e] });
    };
}
function re_getDragNewPosition(e) {
    let n = re_getElementOffsetAndMeasures(e),
        i = { x: n.left - e.x, y: n.top - e.y - 20 };
        // TODO: preventDragOutsideScreen does not work properly
    return re_preventDragOutsideScreen(n, i, { left: i.x, top: i.y, right: i.x + n.width, bottom: i.y + n.height });
}
function re_getElementOffsetAndMeasures(e) {
    return { left: e.offsetLeft, top: e.offsetTop, height: e.offsetHeight, width: e.offsetWidth };
}
function re_preventDragOutsideScreen(e, t, n) {
    let i = re_getDocumentBodyLimits();
    return n.left < i.left && (t.x = e.left), n.top < i.top && (t.y = e.top), n.right > i.right && (t.x = e.left), n.bottom > i.bottom && (t.y = e.top), t;
}
function re_dragAction( e, t ) {
        let n = re_getDragNewPosition(e.param[0], t);
        let c = re_getElementSizeAndPosition( e.param[0].parentNode );
        let pos = store( null, true );
        nj( e.param[0] ).sty( { left: pos.mPosX - parseInt(  c.left ) + "px", top: pos.mPosY - parseInt( c.top ) + "px" } );
        let el = nj().els( "#" + e.param[0].id ).parentNode;
        if( ( parseInt( pos.mPosX ) - parseInt( c.left ) + 20 ) < 250 ) {
            //TODO: fit resizer position
            nj( "#" + getIdAndName( el.id ).name + "_dummyRes" ).sty( {left: "unset", top: "unset" } );
            return;
        } 
        if( ( parseInt( pos.mPosY ) - parseInt( c.top ) + 20 ) < 150 ) {
            //TODO: fit resizer position
            nj( "#" + getIdAndName( el.id ).name + "_dummyRes" ).sty( {left: "unset", top: "unset" } );
            return;
        } 
        nj( el ).sty({ width: parseInt( pos.mPosX ) - parseInt( c.left ) + 20 +"px", height: parseInt( pos.mPosY ) - parseInt( c.top ) + "px"});
        // TODO: fit dialog size
        // get footer height
        let resizer_pos = nj( nj().els( "#" + e.param[0].id ) ).gRe();
        let el_footer_height = parseInt( re_getElementSizeAndPosition( nj().els( "#" + e.param[0].id ).previousSibling ).height );
        let res_top = parseInt( resizer_pos.top );
        const dia_hl_height = 35 + 3 // hl + menu
        const dia_footer_height = nj( e.param[0].previousElementSibling ).gRe();
        let testvalue = res_top -  parseInt( c.top ) - dia_hl_height - el_footer_height;
        nj().els( "#" + e.param[0].previousSibling.previousSibling.id  ).style.height = testvalue + "px";
}
function re_getElementSizeAndPosition(e) {
    let t = window.getComputedStyle(e);
    return {
        width: t.getPropertyValue("width"),
        height: t.getPropertyValue("height"),
        top: t.getPropertyValue("top"),
        left: t.getPropertyValue("left"),
        gridCol: t.getPropertyValue("grid-template-columns"),
        gridRow: t.getPropertyValue("grid-template-rows"),
    };
}
function re_getDocumentBodyLimits() {
    return { left: 0, right: document.body.clientWidth, top: 0, bottom: document.body.clientHeight };
}
function re_trackMouseDragPlusAction(e) {
    let t = event.clientX,
        n = event.clientY;
    (document.onmouseup = re_dragMouseStop),
        (document.onmousemove = function () {
            let i = event.clientX,
                o = event.clientY;
                //console.log( e, t, n, i, o );                           // e ->{ event: "drag", param: [0: resize el]
                                                                        // t -> x offset of event in resize el
                                                                        // n -> y offset of event in resize el
                                                                        // i -> x coord of mousepointer
                                                                        // o -> y coord of mousepointer
            store( { mPosX: i, mPosY: o, elOffsetX: t, elOffsetY: n } );
            re_dragAction(e/*, { x: t - i, y: n - o }*/)/*, (t = i), (n = o)*/;
        });
}
function re_dragMouseStop(e) {
    // correct target position
    (document.onmouseup = null), (document.onmousemove = null);
    if( e.target.nextElementSibling !== null ) {
        nj().els( e.target.nextElementSibling ).style.top = parseInt( e.target.nextElementSibling.style.top ) - 10 + "px";
        nj().els( e.target.nextElementSibling ).style.left = parseInt( e.target.nextElementSibling.style.left ) + 10 + "px";
        nj().els( e.target.parentNode.parentNode ).style.height = parseInt( nj().els( e.target.parentNode.parentNode ).style.height ) + 14 + "px";
        console.log( e.target.parentNode.children[0] );
        nj().els( e.target.parentNode.children[0] ).style.position = "relative";
        nj().els( e.target.parentNode.children[0] ).style.left = "-20px";        
    }
    // end correct terget position
}
const dialogTouchMove = function ( el ) {
    var touchLocation, posBasis, diffX, diffY, pEl = nj( el ).p();
    nj( el ).on( "touchstart", function ( e ) {
        posBasis = nj( pEl ).gRe();
        diffX = posBasis.x - e.targetTouches[0].pageX;
        diffY = posBasis.y - e.targetTouches[0].pageY;
    } );
    nj( el ).on( "touchmove", function ( e ) {
        touchLocation = e.targetTouches[0];
        console.log( touchLocation, posBasis );
        nj( pEl ).sty( { left: touchLocation.pageX + diffX + "px", top: touchLocation.pageY + diffY + "px" } );
    } );
}

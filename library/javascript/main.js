//javascript
const PATH_TO_INFO = "info/";
const PATH_TO_HELP = "help/";
const PATH_TO_ICONS = "../library/icons/";
const PATH_TO_CSS = "library/css/";
const PATH_TO_JAVASCIPT = "library/javascript/";
const DEFAULT_CSS_FILE = "DialogNew.css";
const CLASS_DIALOG_MENU = "dialogMenu";
const CLASS_DIALOG_BOX = "dialogBox";
const CLASS_DIALOG_CONTENT = "dialogContent";
const CLASS_DIALOG_FOOTER = "dialogFooter";
const CLASS_DIALOG_RESIZER = "dialogResizer";
const CLASS_DIALOG_HELP = "dialogHelp";
const CLASS_DIALOG_INFO = "dialogInfo";
const CLASS_DIALOG_WRAPPER = "dialogWrapper";
var roleId;
var currentTrackId = 0;
var data = {};
var timer = 10000;
var timerCounter = 0;
var timerCounterMax;
if( typeof( timeout_time ) != "undefined" ) timerCounterMax = timeout_time / 10000;
var latitude, longitude;
(function(funcName, baseObj) {
    // The public function name defaults to window.docReady
    // but you can pass in your own object and own function name and those will be used
    // if you want to put them in a different namespace
    funcName = funcName || "docReady";
    baseObj = baseObj || window;
    var readyList = [];
    var readyFired = false;
    var readyEventHandlersInstalled = false;

    // call this when the document is ready
    // this function protects itself against being called more than once
    function ready() {
        if (!readyFired) {
            // this must be set to true before we start calling callbacks
            readyFired = true;
            for (var i = 0; i < readyList.length; i++) {
                // if a callback here happens to add new ready handlers,
                // the docReady() function will see that it already fired
                // and will schedule the callback to run right after
                // this event loop finishes so all handlers will still execute
                // in order and no new ones will be added to the readyList
                // while we are processing the list
                readyList[i].fn.call(window, readyList[i].ctx);
            }
            // allow any closures held by these functions to free
            readyList = [];
        }
    }

    function readyStateChange() {
        if ( document.readyState === "complete" ) {
            ready();
        }
    }

    // This is the one public interface
    // docReady(fn, context);
    // the context argument is optional - if present, it will be passed
    // as an argument to the callback
    baseObj[funcName] = function(callback, context) {
        if (typeof callback !== "function") {
            throw new TypeError("callback for docReady(fn) must be a function");
        }
        // if ready has already fired, then just schedule the callback
        // to fire asynchronously, but right away
        if (readyFired) {
            setTimeout(function() {callback(context);}, 1);
            return;
        } else {
            // add the function and context to the list
            readyList.push({fn: callback, ctx: context});
        }
        // if document already ready to go, schedule the ready function to run
        if (document.readyState === "complete") {
            setTimeout(ready, 1);
        } else if (!readyEventHandlersInstalled) {
            // otherwise if we don't have event handlers installed, install them
            if (document.addEventListener) {
                // first choice is DOMContentLoaded event
                document.addEventListener("DOMContentLoaded", ready, false);
                // backup is window load event
                window.addEventListener("load", ready, false);
            } else {
                // must be IE
                document.attachEvent("onreadystatechange", readyStateChange);
                window.attachEvent("onload", ready);
            }
            readyEventHandlersInstalled = true;
        }
    }
})("docReady", window);
var evaluateMain = function( data ) {
    console.log( data );
    let jsonobject;
    if( typeof data === "string" ) {
        jsonobject = JSON.parse( data );
    } else {
        jsonobject = data;
    }
    if( !nj().isJ( jsonobject ) ) {
        throw "kein JSON-Objekt übergeben";
    }
    console.log( jsonobject );
    switch( jsonobject.command ) {
        case "refresh_timeout":
            if( typeof( setValueCountMessagesNews ) == "function" ) {
                setValueCountMessagesNews( parseInt( jsonobject.countMessages ), parseInt( jsonobject.countNews ) );
            }
        break;
    }
}
/* evaluate tracking */
/*
var evaluateTracking = function( data ) {
    console.log( data );
    let jsonobject;
    if( typeof data === "string" ) {
        jsonobject = JSON.parse( data );
    } else {
        jsonobject = data;
    }
    if( !nj().isJ( jsonobject ) ) {
        throw "kein JSON-Objekt übergeben";
    }
    console.log( jsonobject );
    switch( jsonobject.command ) {
        case "track":
            currentTrackId = parseInt( jsonobject.id );
        break;
        case "setTrackAction":
            currentTrackId = parseInt( jsonobject.id );
        break;
    }
}
*/
/* end  evaluate tracking */
/* register resize/scroll */
var registerFunctionsResize = [];
var registerFunctionsScroll = [];
window.addEventListener( "resize", function(){
    let l = registerFunctionsResize.length;
    let i = 0;
    while( i < l ) {
        //console.log( registerFunctionsResize[i] )
        registerFunctionsResize[i]();
        i += 1;    
    }
});
window.addEventListener( "scroll", function(){
    let l = registerFunctionsScroll.length;
    let i = 0;
    while( i < l ) {
        registerFunctionsScroll[i]();
        i += 1;    
    }
});
var registerOnResize = function( args ) {
    registerFunctionsResize.push( args );
}
var registerOnScroll = function( args ) {
    registerFunctionsScroll.push( args );
}
var setWindowDocProperties = function( args ) {
    let x, y;
    if( window.innerWidth < window.screen.availWidth ) {
        x = window.innerWidth;
    } else {
        x = window.screen.availWidth;
    }
    if( window.innerHeight < window.screen.availHeight ) {
        y = window.innerHeight;
    } else {
        y = window.screen.availHeight;
    }

    document.documentElement.style.setProperty('--window-width', x);
    document.documentElement.style.setProperty('--window-height', y);
    if (window.innerWidth > document.body.clientWidth) {
        if( window.innerWidth < window.screen.availWidth ) {
            document.documentElement.style.setProperty('--scrollbar-width', window.innerWidth - document.body.clientWidth );
        } else {
            document.documentElement.style.setProperty('--scrollbar-width', 0 );   
        }
    } else {
        document.documentElement.style.setProperty('--scrollbar-width', 0 );    
    }   
    if (window.innerHeight > document.documentElement.clientHeight ) {
        if( window.innerHeight < window.screen.availHeight ) {
            document.documentElement.style.setProperty('--scrollbar-height', window.innerHeight - document.documentElement.clientHeight );
        } else {
            document.documentElement.style.setProperty('--scrollbar-height', 0 );   
        }
    } else {
        document.documentElement.style.setProperty('--scrollbar-height', 0 );    
    }   
}
var getDocumentHeight = function() {
    let height,
        body = document.body,
        html = document.documentElement;

    height = Math.max( body.scrollHeight, body.offsetHeight, 
                       html.clientHeight, html.scrollHeight, html.offsetHeight );
    if( height < window.innerHeight ) height = window.innerHeight;
    document.documentElement.style.setProperty('--document-height', height );    

}
var getDocumentWidth = function() {
    let width,
    body = document.body,
    html = document.documentElement;

    width = Math.max( body.scrollWidth, body.offsetWidth, 
                       html.clientWidth, html.scrollWidth, html.offsetWidth );
    if( width < window.innerWidth ) width = window.innerWidth;
    document.documentElement.style.setProperty('--document-width', width );
}
registerOnResize( getDocumentHeight );
registerOnResize( getDocumentWidth );

registerOnResize( setWindowDocProperties );


window.addEventListener("load", function() {
    window.dispatchEvent(new Event('resize'));
    window.dispatchEvent(new Event('scroll'));
})
/* end register resize/scroll */
/* pos nav */
var getPosNav = function() {
    let pos = nj( "nav" ).gRe();
    let navBottom = pos.y + pos.height;
    if( nj( "#header_big" ).gRe().height > navBottom ) {
        document.documentElement.style.setProperty('--nav-top', nj( "#header_big" ).gRe().height + "px");

    }else{
        document.documentElement.style.setProperty('--nav-top', navBottom + "px");

    }
    document.documentElement.style.setProperty('--nav-width', pos.width);
}
registerOnResize( getPosNav );
/* end pos nav */
/* dim wrapper */
var getDimWrapper = function() {
    let wW = +( document.documentElement.style.getPropertyValue('--document-width') ) - ( +( document.documentElement.style.getPropertyValue('--scrollbar-width') ) );
    document.documentElement.style.setProperty('--wrapper-width', wW + "px");
    let wH = +( document.documentElement.style.getPropertyValue('--document-height') ) - ( +( document.documentElement.style.getPropertyValue('--scrollbar-height') ) );
    var B = document.body,
    H = document.documentElement,
    height

    if (typeof document.height !== 'undefined') {
        height = document.height // For webkit browsers
    } else {
        height = Math.max( B.scrollHeight, B.offsetHeight,H.clientHeight, H.scrollHeight, H.offsetHeight );
    }

    document.documentElement.style.setProperty('--wrapper-height', height + "px");
}
registerOnResize( getDimWrapper );
/* end pos nav */
/* on content is full loaded */
let eventsOnLoad = [];
var registerOnLoad = function( cb ) {
    eventsOnLoad.push( cb );    
}
var correctFirefoxCss = function() {
    if( navigator.userAgent.indexOf( "Firefox" ) > -1 ) {
        nj( "#header_big" ).sty( {"top":"0px", "position": "fixed"} );
        nj( "#logo_description_big").sty( "font-size", "17px" );
    }    
}
registerOnLoad( correctFirefoxCss )
addEventListener('DOMContentLoaded', (event) => {
    let l = eventsOnLoad.length;
    let i = 0;
    while( i < l ) {
        eventsOnLoad[i]()
        i += 1;    
    }
});

    var B = document.body,
    H = document.documentElement,
    height

    if (typeof document.height !== 'undefined') {
        height = document.height // For webkit browsers
    } else {
        height = Math.max( B.scrollHeight, B.offsetHeight,H.clientHeight, H.scrollHeight, H.offsetHeight );
    }

    document.documentElement.style.setProperty('--wrapper-height', height + "px");


/* end on content is full loaded */

/* simulate doublecklick for phone */
var doubletapDeltaTime_ = 700;
var doubletap1Function_ = null;
var doubletap2Function_ = null;
var doubletapTimer = null;
var doubletapTimer_, doubletapTimeout_; 
function tap(singleTapFunc, doubleTapFunc) {
    if (doubletapTimer==null) {
    // First tap, we wait X ms to the second tap
        doubletapTimer_ = setTimeout(doubletapTimeout_, doubletapDeltaTime_);
        doubletap1Function_ = singleTapFunc;
        doubletap2Function_ = doubleTapFunc;
    } else {
    // Second tap
        clearTimeout(doubletapTimer);
        doubletapTimer_ = null;
        doubletap2Function_();
    }
}

function doubletapTimeout() {
// Wait for second tap timeout
    doubletap1Function_();
    doubleTapTimer_ = null;
}
/* usage
tap(singleClickEvent, doubleClickEvent )
<div id="divID" onclick="tap(tapOnce, tapTwice)" >
*/

/**/
var automaticLogout = function() {
    if( ++timerCounter >= timerCounterMax ) {
        location.href = "index.php?c=timeout";   
        } else {
    }
}
/**/
/* geo coords */
function getPosition() {
    // Simple wrapper
    return new Promise((res, rej) => {
        navigator.geolocation.getCurrentPosition(res, errorCallback);
    });
}

async function getGeoPositions() {
    try {
        var position = await getPosition();  // wait for getPosition to complete
        console.log(position);
        latitude = position.coords.latitude;
        longitude = position.coords.longitude;
        //actionAfterGetGeoCodes();
        if( typeof actionAfterGetGeoCodes  === "function" ) {
            actionAfterGetGeoCodes();    
        }

    } catch {
        console.log(position);
    }
}
    function errorCallback(error) {
        console.log( error );

        if(error.code == 1) {
            console.log( "You've decided not to share your position, but it's OK. We won't ask you again." );
            if( typeof( actionAfterErrorGeoCodes ) == "function" ) {
                actionAfterErrorGeoCodes();    
            } 
        } else if(error.code == 2) {
//            result.innerHTML = "The network is down or the positioning service can't be reached.";
        } else if(error.code == 3) {
//            result.innerHTML = "The attempt timed out before it could get the location data.";
        } else {
//            result.innerHTML = "Geolocation failed due to unknown error.";
        }
    }

/* end geo */
var automaticLogout = function() {
    if( ++timerCounter >= timerCounterMax ) {
        location.href = "index.php?c=timeout";   
    } else {
    }
}
document.addEventListener("mousemove", function( e ) {
    timerCounter = 0;
    if( typeof timeout_counter_mousemove === 'undefined' ) return;
    if( ++timeout_counter_mousemove > refresh_mousemove ) {
        timeout_counter_mousemove = 0;
        var data = {};
        data.command = "refresh_timeout";
        nj().post("library/php/ajax_request.php", data, evaluateMain);

    }
});
document.addEventListener( "keydown", function( e ) {
    timerCounter = 0;
    if( typeof timeout_counter_keydown === 'undefined' ) return;
    if( ++timeout_counter_keydown > refresh_keydown ) {
        timeout_counter_keydown = 0;
        var data = {};
        data.command = "refresh_timeout";
        nj().post("library/php/ajax_request.php", data, evaluateMain);

    }
});
document.addEventListener("touchstart", function(){
    var data = {};
    data.command = "refresh_timeout";
    nj().post("library/php/ajax_request.php", data, evaluateMain);
});

nj("#disconnect_button, #short_name").on("click", function() {
    if( nj( "#profile" ).sty( "display" ) == "none" ) {
        let left = nj("#disconnect_button").gRe().x;
        nj( "#profile" ).sty( {"display": "block", 
            "z-index": "4", 
            "position": "fixed",
            "left": left - 50 + "px" } );
    } else {
        console.log("hier")
        nj( "#profile" ).sty( "display", "none" );            
    }
});
nj("#disconnect_button_phone").on("click", function() {
    if( nj( "#profile_phone" ).sty( "display" ) == "none" ) {
        nj( "#profile_phone" ).sty( "display", "grid" );
    } else {
        nj( "#profile_phone" ).sty( "display", "none" );            
    }
});
nj("#profile_phone").on("click", function() {
    if( nj( "#profile_phone" ).sty( "display" ) == "none" ) {
        nj( "#profile_phone" ).sty( "display", "grid" );
    } else {
        nj( "#profile_phone" ).sty( {"display": "none" } );            
    }
});
nj("#profile").on("click", function() {
    if( nj( "#profile" ).sty( "display" ) == "none" ) {
        nj( "#profile" ).sty( "display", "block" );
    } else {
        nj( "#profile" ).sty( "display", "none" );
    }
});
nj( "#closeProfile").on("click", function() {
    nj( "#profile").rPr( "style" );
});

nj("#profile_show, #profile_show_phone").on("click", function() {
    location.href = "profile.php";
});
nj("#admin_show, #admin_show_phone").on("click", function() {
    location.href = "admin_portal.php";
});
nj("#profile_disconnect, #profile_disconnect_phone").on("click", function() {
    location.href = "index.php";
});
nj("#abstinencard_show, #abstinencecard_show_phone").on("click", function( e ) {
    location.href = "abstinenzcard.php";
});


window.onload = function() {
/*
    if( typeof isExtern === "undefined" ) {
        data.command = "track";
        data.type = "load";
        data.currentTrackId = currentTrackId;
        data.pathname = window.location.pathname;
        nj().post("library/php/ajax_request.php", data, evaluateTracking );
    }
*/
}
/*
window.onpagehide = function(e) {
    if( typeof isExtern === "undefined" ) {
        data.command = "track";
        data.type = "unload";
        data.currentTrackId = currentTrackId;
        data.pathname = window.location.pathname;
        nj().post("library/php/ajax_request.php", data, evaluateTracking );
    }
}
*/
window.onbeforeunload = function(e) {
    if( typeof isExtern === "undefined" ) {
        data.command = "track";
        data.type = "unload";
        data.currentTrackId = currentTrackId;
        data.pathname = window.location.pathname;
        nj().post("library/php/ajax_request.php", data, evaluateTracking );
    }
}
getPosNav();
nj( "#profile" ).sty( "display", "none" );
if( typeof evaluateData === "function" ) {
    data.command = "refresh_timeout";
    nj().post("library/php/ajax_request.php", data, evaluateMain);
}
nj( "input, textarea" ).on( "blur", function( e ) {
    if( nj().els( this ).type === "file") return;
    nj().els( this ).value = nj().els( this ).value.replaceAll( '"', "“" ).replaceAll( "'", "’" );
});
/* correct safari representation */ 
const correctSafariRepresentation = function() {
    if( nj().gBr() === "Safari" ) {
        loadCSS( "library/css/safari/default.css" );
        let tmp = splitUrl();
        if( tmp.fName === "") {
            loadCSS( "library/css/safari/index.css" );
        } else {
            loadCSS( "library/css/safari/" + splitUrl().fNameWithoutExt + ".css" );            
        }
    }
}
correctSafariRepresentation();
/* end correct safari representation */ 
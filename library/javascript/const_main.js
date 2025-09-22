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
var data = {};
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

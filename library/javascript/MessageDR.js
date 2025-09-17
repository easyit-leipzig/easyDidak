//javascript
const PATH_TO_MESSAGE_CSS = "library/css/MessageNew.css";
const MESSAGE_HTML = '<div><div class="[class]"></div><div><div>[content]</div></div></div>'
var dMNew;
class Message {
      constructor( param ) {
        this.opt = {
            id:                 "#mess", // necessary - id of dialog; if the element does not exists a new element will be created with this id
            dVar:               "", // necessary - must by the name of the object
            divMess:            undefined,
            title:              "Titel",  // necessary - string
            type:               true, // necessary - the type of message
            innerHTML:          "", // necessary - the content
            target:             document.body, // necessary - the target element
            classPraefix:       "mess_", // optional - praefix for all classes
            height:             undefined, // optional - can be a value for standard height or "dynamic" for dynamic according to the content size
            width:              undefined, // optional - can be a value for standard width
            modal:              true, // optional - true/false
            autoOpen:           false, // optional - true/false
            center:             true, // optional - true/false
            rootCenter:         true, // a temporary value for saving start center
            addClasses:         "diaMess", // optional - additional classes divide by " " - "diaMess" should be allways set for correct css
            hasIcon:            false, // optional - true/false
            hasMin:             false,
            hasMax:             false,
            hasSticky:          false,
            hasClose:           true,
            canMove:            true,
            canResize:          false,
            buttons:            [],// {title: "Okay", action: function(){ let df = getDVar( this ); window[ df ].hide() } ],
            variables:          undefined,
        }
        Object.assign( this.opt, param );
        loadCSS( PATH_TO_MESSAGE_CSS );
        this.opt.divMess = new DialogDR( { dVar: this.opt.dVar + ".opt.divMess", 
            id: this.opt.id, 
            title: this.opt.title,
            addClasses: this.opt.addClasses,
            height: this.opt.height,
            width: this.opt.width,
            modal: this.opt.modal,
            center: this.opt.center,
            canResize: this.opt.canResize,
            hasMin: true,
            //variables: this.opt.variables,
        } );
    }
    show = function( args ) {
        if( typeof args !== "undefined" ) {
            if( typeof args.buttons !== "undefined" ) {

            } else {
                if( this.opt.buttons.length == 0 ) {
                    b = {};
                    b.title = "Okay";
                    b.action = function( el ){
                        nj( this ).Dia().hide();
                    };
                    this.opt.buttons.push( b ); 
                args.buttons = this.opt.buttons;
                }
            }
            if( typeof args.variables !== "undefined" ) {
                this.opt.variables = args.variables;
            } 
            if( typeof args.type !== "undefined" ) {
                switch( args.type ) {
                    case true:
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messOkay" ) 
                    break;
                    case false:
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messError" ) 
                    break;
                    case "question":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messQuestion" ) 
                    break;
                    case "info":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messInfo" ) 
                    break;
                    case "warning":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messWarning" ) 
                    break;
                    case "attention":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messAttention" ) 
                    break;
                    case "finger":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messFinger" ) 
                    break;
                    case "key":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messKey" ) 
                    break;
                    case "wait":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messWait" ) 
                    break;
                    case "newfolder":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messNewFolder" ) 
                    break;
                    case "empty":
                        args.innerHTML = MESSAGE_HTML.replace( "[class]", "messEmpty" ) 
                    break;
                }
            } else {
                args.innerHTML = MESSAGE_HTML.replace( "[class]", "messUndefined" ) 
            }
            if( typeof args.text !== "undefined" ) {
                args.innerHTML = args.innerHTML.replace( "[content]", args.text ) 
            }
        }
        this.opt.divMess.show( args );
        nj( "#mess_footer>button:last-child").f();        
    }
    hide = function() {
        this.opt.divMess.hide();        
    }
}
dMNew = new Message( {dVar: "dMNew", modal: false, center: true, addClasses: "diaMess" } );
//javascript
const DIV_UPLOAD_HTML = `<div id="[dVar]_tmpUploadId" class="divUploadFile">
    <div id="[dVar]_tmpDivUploadFormErrorText" class="fileUploadErrorText">Datei auswählen und "Öffnen" wählen.</div>
    <div>
        <label id="[dVar]_tmpLabelUpload" class="fileUploadLabel" for="[dVar]_tFUFile">Hochladen</label>
    </div>
    <input type="file" id="[dVar]_tFUFile">
</div>`;
const DIV_LINK_HTML = `<input id="[dVar]_linkElId" type="text"><label>Link</label><input id="[dVar]_link" type="text">
<label>Ziel</label><select id="[dVar]_linkTarget">
    <option value="_blank" selected>neuer Tab</option>
    <option value="_self">selber Tab</option>
    <option value="_parent">Elternfenster</option>
    <option value="_top">ganzes Fenster</option>
</select>
<label>Link</label><input id="[dVar]_linkText" type="text">`;
const DIV_EDIT_SELECT = ``;
const DIV_EDIT_TEXTAREA = ``;
const optDate = '<option value="[field]>-1">alle</option><option value="[field]>=\'' + getLastWeek().from + '\' and [field]<=\'' + getLastWeek().to + '\'">letzteWoche</option><option value="[field]>=\'' + getCurrentWeek().from + '\' and [field]<=\'' + getCurrentWeek().to + '\'">aktuelle Woche</option><option value="[field]>=\'' + getNextWeek().from + '\' and [field]<=\'' + getNextWeek().to + '\'">nächste Woche</option><option value="[field]>=\'' + getLastMonth().from + '\' and [field]<=\'' + getLastMonth().to + '\'">letzter Monat</option><option value="[field]>=\'' + getCurrentMonth().from + '\' and [field]<=\'' + getCurrentMonth().to + '\'">aktueller Monat</option><option value="[field]>=\'' + getNextMonth().from + '\' and [field]<=\'' + getNextMonth().to + '\'">nächster Monat</option>';
const DIFF_PAGINATION = 2;
const MAX_FILE_UPLOADSIZE = 10000;
var args = {};
class DataForm {                    // class for DataForm2.0
      constructor( param ) {
        this.opt = {
            dVar:                               undefined,  // necessary - var of field object
            id:                                 undefined,  // id of field object; if not isset id is dVar
            target:                             document.body,  // id of target element; if not isset target is body
            fields:                             undefined,  // field list divided by ","; if not isset all fields
            title:                              undefined,  // title of dataform dialog
            hasHelp:                            false,
            hasInfo:                            false,
            canResize:                          false,
            fieldDefinitions:                   [],         // object array of field definitions
            primaryKey:                         [],         // object array of field definitions
            recordsets:                         [],         // object array of recordsets
            optionLists:                        [],         // object array of option lists for fields e.g. 
                                                            /*
                                                            [
                                                                {   
                                                                    field:"[selectfield1]", 
                                                                    options: '<value="1">[value 1]</option><value="2">[value 2]</option>..'
                                                                }, 
                                                                {   
                                                                    field:"[selectfield2]", 
                                                                    options: '<value="0">[value 0]</option><value="1">[value 1]</option>..'
                                                                }, 
                                                                ...
                                                            ]
                                                            */
            currentRecord:                      1,
            orderBy:                            "",
            whereClausel:                       "",
            currentPage:                        0,
            countPerPage:                       0,          // if is 0 all records
            hasPagination:                      false,
            orderArray:                         [],
            filter:                             "",         // if "undefined" no values will be required on init
            searchArray:                        [],
            ownArray:                           [],
            hasNew:                             true,
            boundForm:                          [],         // ["form1","form_n"...]
            boundFields:                        [],         // [{from:v,to:t1}...]
            boundFilter:                        "",         // additional filter after bound form
            addPraefix:                         "",
            addClassFiles:                      "DataForm20.css", // optional - additional class files divide by " "
            widthSave:                          true,
            widthDelete:                        true,
            dfHasLabel:                         true,
            validOnSave:                        false,
            baseClassRecordSet:                 "cRecordset",
            addRSClasses:                       "",
            baseClassField:                     "cField",
            addFieldClasses:                    "",
            classButtonSize:                    "",
            autoOpen:                           true,
            formType:                           "list",
            formWidth:                          280,
            formHeight:                         400,
            formModal:                          true,
            divForm:                            undefined,
            rootPath:                           "library",
            imagePath:                          "library/df/",
            onClose:                            undefined,
            onShow:                             undefined,
            afterDelete:                        undefined,
            afterNew:                           undefined,
            afterSuccessSave:                   undefined,
            afterbuild:                         function(){},
            onsave:                             function(){},
        }
        let tmpId = "",
            tmpClasses = "",
            tmp,
            tmpEl, 
            tmpEls,
            tmpSearchString = "",
            searchWasGreaterThanTwo;
        if( param.addClassFiles !== "" ) param.addClassFiles = this.opt.addClassFiles + " " + param.addClassFiles;
        Object.assign( this.opt, param );
        tmp = this.opt.addClassFiles.split(" ");
        //console.log( tmp );
        let l = tmp.length;
        let i = 0;
        while ( i < l ) {
            if( tmp[i] !== "undefined" ) loadCSS( PATH_TO_CSS + tmp[i] );
            i += 1;
        } 
        //this.opt.id = "#" + this.opt.addPraefix + this.opt.id.substring( 1 );
        if( this.opt.formType === "html") {            
            if( !nj( this.opt.id ).isE() ) {
                tmpEl = nj().cEl( "div" );
                tmpEl.id = this.opt.id.substring( 1 );
                nj( this.opt.target ).aCh( tmpEl );
            }
            
        } else {
            this.dDF = new DialogDR( {
                dVar: this.opt.dVar + ".dDF", 
                id: this.opt.id, 
                title: this.opt.title, 
                width: this.opt.formWidth, 
                height: this.opt.formHeight, 
                modal: this.opt.formModal,
                autoOpen: this.opt.autoOpen,
                hasHelp: this.opt.hasHelp,
                hasInfo: this.opt.hasInfo,
                canResize: this.opt.canResize,
                onClose: this.opt.onClose,
                onShow: this.opt.onShow,
            } );
        }
        nj( this.opt.id ).sDs( "dvar", this.opt.dVar );
        tmpEl = nj().cEl( "div" );
        tmpEl.id = this.opt.id.substring( 1 ) + "_head";
        nj( tmpEl ).aCl( "dataformHead" );
        nj( this.opt.id ).aCh( tmpEl );
        tmpEl = nj().cEl( "div" );
        tmpEl.id = this.opt.id.substring( 1 ) + "_head_headline";
        nj( "#" + this.opt.id.substring( 1 ) + "_head" ).aCh( tmpEl );
        nj( "#" + this.opt.id.substring( 1 ) + "_head_headline" ).aCl( "dataFormHeadHeadline" );
        tmpEl = nj().cEl( "div" );
        tmpEl.id = this.opt.id.substring( 1 ) + "_data";
        nj( tmpEl ).aCl( "dataformData" );
        nj( this.opt.id ).aCh( tmpEl );
        if( this.opt.hasPagination ) {
            tmpEl = nj().cEl( "div" );
            tmpEl.id = this.opt.id.substring( 1 ) + "_pag";
            nj( tmpEl ).aCl( "dataformPagination" );
            nj( tmpEl ).sDs( "dvar", this.opt.dVar );
            nj( this.opt.id ).aCh( tmpEl );            
        }
        this.showDfHeadline();
        if( this.opt.searchArray.length > 0 ) {
            this.showSearchHeadline();
        }
        if( this.opt.ownArray.length > 0 ) {
            //tmpEl = nj().cEl("div");
            //tmpEl.id = "ownArray";
            this.showOwnHeadline();

            //console.log( this.opt );
        }

        this.divUpload = new DialogDR( { 
            dVar: param.dVar + ".divUpload", 
            title: "Datei laden", 
            innerHTML: DIV_UPLOAD_HTML.replaceAll( "[dVar]", this.opt.dVar ),
            addClasses: "cDivUpload", 
        } );
        this.divEditLink = new DialogDR( { 
            dVar: param.dVar + ".divEditLink", 
            title: "Link bearbeiten", 
            innerHTML: DIV_LINK_HTML.replaceAll( "[dVar]", this.opt.dVar ),
            addClasses: "cDivEditLink",
            buttons: [
                {
                    title: "Speichern",
                    action: function() {
                        let tmpTargetId = nj(this).Dia().opt.variables.el.opt.id;
                        let tmpId ="#" + nj(this).Dia().opt.variables.df.opt.dVar + "_linkText";
                        let tmpVal = nj( tmpId ).v();
                        let tmpTarget = nj("#" + nj(this).Dia().opt.variables.df.opt.dVar + "_linkTarget" ).v();
                        let tmpUrl = nj("#" + nj(this).Dia().opt.variables.df.opt.dVar + "_link" ).v();
                        nj( tmpTargetId ).htm( tmpVal );
                        nj( tmpTargetId ).atr( "target", tmpTarget );
                        nj( tmpTargetId ).atr( "href", tmpUrl );
                        nj( this ).Dia().hide();
                    }
                }
            ],
            onShow: function() {
                nj( "#" + this.dVar.split( "." )[0] + "_link" ).v("");
                let tmp = "#" + this.dVar.split( "." )[0] + "_linkElId";
                let vText = nj( nj( tmp ).v() ).htm();
                let vLink = nj( nj( tmp ).v() ).atr( "href" );
                let vTarget = nj( nj( tmp ).v() ).atr( "target" );
                nj( "#" + this.dVar.split( "." )[0] + "_linkText" ).v( vText );
                nj( "#" + this.dVar.split( "." )[0] + "_link" ).v( vLink );
                nj( "#" + this.dVar.split( "." )[0] + "_linkTarget" ).v( vTarget );
                nj( "#" + this.dVar.split( "." )[0] + "_link" ).on( "blur", function() {
                    if( !validateURL( nj(this).v() ) && nj(this).v() !== "" ) {
                        dMNew.show( {title: "Fehler", type: false, text: "Der eingegebene Wert ist kein gültiger Link."})
                        nj(this).v("");
                    }
                });   
                nj( "#" + this.dVar.split( "." )[0] + "_linkText" ).on( "blur", function() {
                    if( nj(this).v() === "" ) {
                        dMNew.show( {title: "Fehler", type: false, text: "Der Linktext darf nicht leer sein."})
                        nj(this).v("Link");
                    }
                }); 
                this.opt.afterBuild()  
            } 
        } );
        this.divEditSelect = new DialogDR( {
                dVar: param.dVar + ".divEditSelect", 
                title: "Auswahl", 
                innerHTML: DIV_EDIT_SELECT.replaceAll( "[dVar]", this.opt.dVar ),
                addClasses: "cDivEditSelect",
                width: 280,
                height: 300,
                onShow: function(){let el = this; console.log( el, Object.keys(el),['variables'] )},
                buttons: [
                        {
                            title: "Übernehmen",
                            action: function( args ) {
                                let v = nj( "#" + nj(this).gRO().opt.addPraefix + "TmpSetSelect" ).gSV();
                                //console.log( v )
                                console.log( nj().els( nj(this).Dia().opt.variables.el.opt.id ) );
                                nj( nj(this).Dia().opt.variables.el.opt.id ).sSV( v, true );
                                //console.log( nj( nj(this).Dia().opt.variables.el.opt.id ).gSV()  );
                                nj(this).Dia().hide();
                                //nj( nj(this).Dia().opt.variables.el.opt.id ).tri( "change" );
                            }
                        },
                        {
                            title: "Abbrechen",
                            action: function( args ) {
                                nj(this).Dia().hide();
                            }
                        },
                    ]            
            }   
        );
        this.divEditTextarea = new DialogDR( {
                dVar: param.dVar + ".divEditTextarea", 
                title: "Texteingabe", 
                innerHTML: DIV_EDIT_TEXTAREA.replaceAll( "[dVar]", this.opt.dVar ),
                addClasses: "cDivEditTextarea",
                width: 280,
                height: 300,
                buttons: [
                        {
                            title: "Übernehmen",
                            action: function( args ) {
                                let v = nj( "#" + nj(this).gRO().opt.addPraefix + "TmpSetTextarea" ).v();
                                nj( nj(this).Dia().opt.variables.el.opt.id ).v( v );
                                nj(this).Dia().hide();
                                nj( nj(this).Dia().opt.variables.el.opt.id ).tri( "change" );
                            }
                        },
                        {
                            title: "Abbrechen",
                            action: function( args ) {
                                nj(this).Dia().hide();
                            }
                        },
                    ]            
            }   
        );
        nj( "#" + this.opt.dVar + "_tFUFile" ).on( "change", function( args ) {
            nj( this ).gRO().uploadFile( nj( this ).Dia() );    
        }  );
        this.getFieldDefinitions()
    }
    evaluateDF = function ( data ) {
        // content
        let jsonobject, l, i, m, j, tmp, decVal, strVal;
        if( typeof data === "string" ) {
            jsonobject = JSON.parse( data );
        } else {
            jsonobject = data;
        }
        if( !nj().isJ( jsonobject ) ) {
            throw "kein JSON-Objekt übergeben";
        }
        //console.log( jsonobject );
        var df = window[ jsonobject.dVar ];
        switch( jsonobject.command ) {
            case "getFieldDefinitions":
                l = df.opt.fieldDefinitions.length;
                i = 0;
                let field, options;
                while( i < l ) {
                    field = nj().fOA( jsonobject.fieldDefs, "Field", df.opt.fieldDefinitions[ i ].field )[0];
                    if( typeof field !== "undefined" ) {
                        Object.assign( df.opt.fieldDefinitions[ i ], df.buildLengthProps( field, df.opt.fieldDefinitions[ i ] ) );
                    }
                    options = nj().fOA( df.opt.optionLists, "field", df.opt.fieldDefinitions[ i ].field )[0];
                    if( typeof options !== "undefined" ) {
                        df.opt.fieldDefinitions[ i ].options = options.options;
                    }
                    i += 1;
                }
                df.opt.primaryKey = jsonobject.primaryKey;
                df.getSearchString();
            break;
            case "getRecords":
                df.opt.countRecords = jsonobject.countRecords;
                df.prepareRecords( jsonobject );
                if( df.opt.hasPagination ) df.initPagination();
                df.initRecordPointer();
                if( typeof df.opt.afterBuild === "function") df.opt.afterBuild();
            break;
            case "saveRecordset":
                if( jsonobject.success ) {
                    if( jsonobject.oldId === "new" && typeof df.opt.afterNew === "function" ) {
                        args.df = df;
                        args.jsonobject = jsonobject;
                        df.opt.afterNew();
                    }
                    if( jsonobject.oldId !== "new" && typeof df.opt.afterSuccessSave === "function" ) {
                        data.df = df;
                        data.jsonobject;
                        df.opt.afterSuccessSave();
                    }
                } else {
                    dMNew.show( {title: "Fehler", type: false, text: jsonobject.message } );
                }
                df.getSearchString();
            break;
            case "deleteRecordset":
                if( typeof df.opt.afterDelete === "function" ) {
                    df.opt.afterDelete( df, jsonobject );
                }
                df.getSearchString();
            break;
            default:
                // content
        
            break;
        }
        if( typeof df.opt.afterBuild === "function" ) df.opt.afterBuild();
    }
    buildLengthProps = function ( fieldProps, fieldDefs ) {
        // content
        let field = {}, decVal, strVal, i, l;
        if( typeof fieldDefs.Comment === undefined ) {
            field.title = ""    
        } else {
            field.title = fieldDefs.Comment;    
        }
        field.default = fieldProps.Default;
        fieldProps.Null === "NO" ? field.canBeNull = false: field.canBeNull = true;
        fieldProps.Key === "Uni" || fieldProps.Key === "Pri" ? field.isUnique = false: field.isUnique = true;
        fieldProps.Extra.indexOf( "auto_increment" ) > - 1 ? field.isAutoInc = true: field.isAutoInc = false;
        field.maxValue = "";
        field.minValue = "";
        let tmpType = fieldProps.Type.split( "(" )[0];
        let tmpLength = fieldProps.Type.split( "(" )[1];
        let tmpSize = undefined;
        let tmpMinVal = undefined;
        let tmpMaxVal = undefined;
        let tmpUnsigned = false;
        if( fieldProps.Type.split( "(" ).length === 2 ) {
            let tmp = fieldProps.Type.split( "(" )[1].split( ")" );
            tmp[1].indexOf( "unsigned" ) > -1 ? tmpUnsigned = true:  tmpUnsigned = false;
        }
        switch( tmpType ) {
            case "tinyint":
                if( tmpUnsigned ) {
                    field.maxValue = 511;
                    field.minValue = 0;
                    field.maxLength = 3;
                } else {
                    field.maxValue = 255;
                    field.minValue = -256;
                    field.maxLength = 4;
                }            
            break;
            case "mediumint":
                field.maxLength = 8;
                if( tmpUnsigned ) {
                    field.maxValue = 16777215;
                    field.minValue = 0;
                 } else {
                    field.maxValue = 8388607;
                    field.minValue = -8388608;
                }            
            break;
            case "int":
                field.maxLength = 11;
                if( tmpUnsigned ) {
                    field.maxValue = 24294967295;
                    field.minValue = 0;
                } else {
                    field.maxValue = 2147483647;
                    field.minValue = -2147483648;
                }
            break;
            case "bigint":
                field.maxLength = 20;
                if( tmpUnsigned ) {
                    field.maxValue = 18446744073709551615
                    field.minValue = 0;
                } else {
                    field.maxValue = 9223372036854775807;
                    field.minValue = -9223372036854775808;
                }           
            break;
            case "decimal":
                let decVal = tmpLength.substring(0, tmpLength.length - 1 ).split( "," );
                l = parseInt( decVal[0] );
                i = 0;
                let strVal = "";
                while( i < l ) {
                    strVal += "9";
                    i += 1;
                }
                strVal += ".";
                l = parseInt( decVal[1] );
                i = 0;
                while( i < l ) {
                    strVal += "9";
                    i += 1;
                }
                if( tmpUnsigned ) {
                    field.maxValue = parseFloat( strVal );
                    field.minValue = 0;
                    field.maxLength = parseInt( decVal[0]) + parseInt( decVal[1]) + 1;
                } else {
                    field.maxValue = parseFloat( strVal );
                    field.minValue = field.maxValue * - 1;
                    field.maxLength = parseInt( decVal[0]) + parseInt( decVal[1]) + 2;
                }
            break;
            case "float":
                if( tmpUnsigned ) {
                    field.maxValue = 3.402823466e38;
                    field.minValue = 1.175494351e-38 ;
                    field.maxLength = ( "" + tmpMaxVal ).length;
                } else {
                    field.maxValue = 3.402823466e+38;
                    field.maxValue = -1.175494351e-38;
                    field.maxValue = ( "" + tmpMinVal ).length;
                }     
            break;
            case "double":
                if( tmpUnsigned ) {
                    field.maxValue = Infinity;
                    field.minValue = 0;
                    field.maxLength = 16;
                } else {
                    field.maxValue = Infinity;
                    field.minValue = -Infinity;
                    field.maxLength = 17;
                }     
            break;
            case "date":
                field.maxLength = 10;
            break;
            case "datetime":
                field.maxLength = 19;
            break;
            case "time":
                field.maxLength = 8;
            break;
            case "tinytext":
                field.maxLength = 255;
            break;
            case "text":
            case "blob":
                field.maxLength = 655354;
            break;
            case "mediumtext":
            case "mediumblob":
                field.maxLength = 16777215;
            break;
            case "longtext":
            case "longblob":
                field.maxLength = 4294967295;
            break;
            case "json":
            case "longtext":
                field.maxLength = 294967295
            break;
            case "varchar":
            case "bit":
                field.maxLength = parseInt( tmpLength );
            break;
            default:
            
            break;
        }
        if( typeof fieldDefs.minValue !== "undefined" ) field.minValue = fieldDefs.minValue;
        if( typeof fieldDefs.maxValue !== "undefined" ) field.maxValue = fieldDefs.maxValue;
        if( typeof fieldDefs.maxLength !== "undefined" ) field.maxLength = fieldDefs.maxLength;
        if( typeof fieldDefs.default !== "undefined" ) field.default = fieldDefs.default;
        return field;

    }   
    resolveFileUpload = async function( file, id, attr, targetPath, /*cb = this.afterSuccessFileUpload(  ),*/ path = "library/php/upload_dataform20.php" ) {
        console.log( this, file, id );
        let formData = new FormData();
        formData.append("file", file[0] );
        formData.append("idTargetElement", id );
        formData.append("targetElementAttr", attr );
        formData.append("targetPath", targetPath );
        await fetch( path , {
          method: "POST", 
          body: formData
        })
      .then( data => { 
        console.log( data, id, attr, targetPath, file[0].name );
        this.afterSuccessFileUpload( data, targetPath, file[0].name, this, id, attr );
        //cb;
      } )
      .catch( data => {
        dMNew.hide(); 
        console.log(data);
      })   
    }
    /**
     * { function_description }
     *
     * @param      {<type>}  data                  The data
     * @param      {string}  targetPath            The target path
     * @param      {string}  targetFileName        The target file name
     * @param      {<type>}  df                    { parameter_description }
     * @param      {string}  id                    The identifier
     * @param      {<type>}  tFUTargetElementAttr  The t fu target element attribute
     */
    afterSuccessFileUpload = function( data, targetPath, targetFileName, df, id, tFUTargetElementAttr ) {
        dMNew.show( {title: "Dateiupload", type: true, text: "Die Datei wurde erfolgreich übertragen.", variables: { dataform: df }, buttons:[{title:"Schliessen", action: function() {
        console.log( /*data, targetPath, targetFileName, nj( this ).Dia(), nj( this ).Dia().opt.variables.dataform,*/ id );
            nj( this ).Dia().opt.variables.dataform.divUpload.hide();
            let opts = nj( this ).Dia().opt.variables.dataform.opt;
            console.log( targetPath + targetFileName );
            dMNew.hide();
            switch( tFUTargetElementAttr ) {
                case "value":
                    nj( "#" + id ).v( targetPath + targetFileName );
                break;
                case "src":
                    let img = new Image(), imSize, w, h, diff, el, res = "#" + id;
                    img.src = targetPath + targetFileName;
                    el = nj().bDV( nj( "#" + id ).ds( "dvar" ) ).opt.imageSize;
                    img.onload = function( e ) {
                        let c = this.width;
                        console.log( nj().bDV( nj( res ).ds( "dvar" ) ).opt.imageSize );
                        imSize = nj().bDV( nj( res ).ds( "dvar" ) ).opt.imageSize
                        if( img.width / img.height >= 1 ) {
                            w = imSize;
                            h = "auto";
                            diff = ( imSize - img.height / img.width * imSize ) / 2;
                            console.log( diff );
                            nj( res ).sty("margin-left","unset");
                            nj( res ).sty("margin-right","unset");
                            nj( res ).sty("margin-top",diff+"px");
                            nj( res ).sty("margin-bottom",diff+"px");
                        } else {
                            w = "auto";
                            h = imSize;
                            diff = ( imSize - img.width / img.height * imSize ) / 2;
                            console.log( diff, nj().els( res ) );
                            nj( res ).sty("margin-top","unset");
                            nj( res ).sty("margin-bottom","unset");
                            nj( res ).sty("margin-left",diff+"px");
                            nj( res ).sty("margin-right",diff+"px");
                        }                    
                        nj( res ).atr( "height", h );
                        nj( res ).atr( "width", w );
                    }
                    nj( "#" + id ).atr( "src", targetPath + targetFileName );
                break;
            case "bckg":
                    nj( "#" + id ).sty( "background-image", "url(" + targetPath + targetFileName + ")" );
                break;
            case "href":
                    nj( "#" + id ).atr( "href", targetPath + targetFileName );
                    nj( "#" + id ).atr( "target", "_blank" );
                    nj( "#" + id ).htm( targetFileName );
                break;
            }
        }}] } );
        nj( "#" + df.opt.dVar + "_tFUFile" ).v( null );   
    }
    uploadFile = function( dUpload ) {
        dMNew.show( { title: "Dateiupload", type: "wait", text: "Datei wird geladen" } );
        this.resolveFileUpload( nj().els( "#" + this.opt.dVar + "_tFUFile").files, dUpload.opt.variables.id, dUpload.opt.variables.attr, dUpload.opt.variables.uploadPath );
    }
    /**
     * Shows the df headline.
     */
    showDfHeadline = function() {
        if( this.opt.dfHasLabel ) {
            let el;
            let l = this.opt.fieldDefinitions.length;
            let i = 0;
            while ( i < l ) {
                el = nj().cEl( "div" );
                el.id = this.opt.addPraefix + "hl_" + this.opt.fieldDefinitions[i].field;
                if( typeof this.opt.fieldDefinitions[i].label === "undefined" || this.opt.fieldDefinitions[i].label === "" ) {
                    nj( el ).htm( "&nbsp;" );
                } else {
                    if( this.opt.orderArray.indexOf( this.opt.fieldDefinitions[i].field ) > -1) {
                        nj( el ).htm( this.opt.fieldDefinitions[i].label + "&nbsp;♦" );
                    } else {
                        nj( el ).htm( this.opt.fieldDefinitions[i].label );    
                    }                    
                }

                nj( el ).sDs( "field", this.opt.fieldDefinitions[i].field );
                nj( this.opt.id + "_head_headline" ).aCl( "DataFormHeadHeadline" );
                nj( this.opt.id + "_head_headline" ).aCh( el );
                if( this.opt.orderArray.indexOf( this.opt.fieldDefinitions[i].field ) > -1 ) {
                nj( "#" + el.id ).on( "click", function( args ) {
                    console.log( nj(this).htm(), this );
                    if( nj(this).htm().slice( - 1 ) == "▼" ) {
                        nj("#" + this.id ).htm( nj(this).htm().substring( 0, nj(this).htm().length - 1 ) + "▲" );
                        console.log( nj( this ).gRO().opt.orderBy );
                        nj( this ).gRO().opt.orderBy = nj( this ).gRO().opt.orderBy.replace( " " + nj(this).ds("field") + " ASC,", "" );
                        nj( this ).gRO().opt.orderBy += " " + nj(this).ds("field") + " DESC,"
                        nj( this ).gRO().getRecords();
                        return;
                    }
                    if( nj(this).htm().slice( - 1 ) == "▲" ) {        
                        nj(this).htm( nj(this).htm().substring( 0, nj(this).htm().length - 1 ) + "♦" );
                        nj( this ).gRO().opt.orderBy = nj( this ).gRO().opt.orderBy.replace( " " + nj(this).ds("field") + " DESC,", "" );
                        nj( this ).gRO().getRecords();
                        return;
                    }
                    if( nj(this).htm().slice( - 1 ) == "♦" ) {        
                        nj(this).htm( nj(this).htm().substring( 0, nj(this).htm().length - 1 ) + "▼" );
                        console.log( " " + nj(this).ds("field") + ' ASC,' );
                        nj( this ).gRO().opt.orderBy += " " + nj(this).ds("field") + (" ASC,");
                        nj( this ).gRO().getRecords();
                        return;
                    }
                });
                }                   
                i += 1;
            }
        }        
    }
    /**
     * Shows the search headline.
     */
    showSearchHeadline = function() {
        let el, field;
        el = nj().cEl( "div" );
        el.id = this.opt.id.substring( 1 ) + "_searchline";
        nj( el ).sDs( "dvar", this.opt.dVar );
        nj( this.opt.id + "_head" ).aCh( el );
        let l = this.opt.searchArray.length;
        let i = 0;
        while( i < l ) {
            field = new Field( {
                id: "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field,
                type: this.opt.searchArray[i].type,
                addAtr: this.opt.searchArray[i].addAtr + " data-field='" + this.opt.searchArray[i].field + "'",
                options: this.opt.searchArray[i].options,
                title: this.opt.searchArray[i].title,
                dVar: this.opt.dVar,    
            } );
            nj( this.opt.id + "_searchline" ).aCh( field.getField()[0] );
            nj( field.opt.id ).v( this.opt.searchArray[i].value );
            if( this.opt.searchArray[i].type === "select" ) {
                nj( field.opt.id ).on( "change", function( args ) {
                    nj( this ).Dia().getSearchString();   
                } );    
            }
            if( this.opt.searchArray[i].type === "input_text" ) {
                nj( field.opt.id ).on( "keyup", function() {
                    if( nj( this ).v().length < 3 && this.searchWasGreaterThanTwo ) {
                        nj( this ).v("");
                        this.searchWasGreaterThanTwo = false;    
                        nj( this ).Dia().getSearchString();
                    } else {
                        if( nj( this ).v().length > 2 ) {
                            this.searchWasGreaterThanTwo = true;
                            nj( this ).Dia().getSearchString();
                        }
                    }

                } );    
            }
            if( this.opt.searchArray[i].type === "input_date" ) {
                nj( field.opt.id ).on( "change", function() {
                        nj( this ).Dia().getSearchString();
                } );    
            }
            
            if( this.opt.searchArray[i].type === "select"&& typeof this.opt.searchArray[i].addAtr !== "undefined" && this.opt.searchArray[i].addAtr.indexOf("multiple") > -1  && this.opt.searchArray[i].addAtr.indexOf("data-clickable") > -1 ) {
                nj( field.opt.id ).on( "click", function() {
                    let elId, el, tmp;
                                                    event.preventDefault();
                                                    elId = nj( this ).Dia().opt.id;
                                                    el = nj().cEl( "select" );
                                                    //el.id = "tmpSetSelect";
                                                    tmp = nj().els( this ).outerHTML;
                                                    tmp = htmlToElement( tmp )
                                                    tmp.id = nj( this ).gRO().opt.addPraefix + "TmpSetSelect" ;
                                                    nj( "#" + nj( this ).gRO().opt.dVar + ".divEditSelect" ).htm("");
                                                    nj( "#" + nj( this ).gRO().opt.dVar + ".divEditSelect" ).aCh( tmp );
                                                    tmp = {opt:{
                                                        id: "#" + this.id
                                                    }}
                                                    nj( this ).gRO().divEditSelect.show({variables: {df: nj(this).gRO(), el: tmp } } );                                                    
                    return;
                } );    
            }
            
            i += 1;
        }
    }
    showOwnHeadline = function() {
        let tmpEl = nj().els( "#" + this.opt.id + "_head" );
        let e = new Field( {id: this.opt.id + "_head_ownHeadline", type: "div"} );
        nj( this.opt.id + "_head" ).aCh( e.getField()[0] );
        let f = new Field({id:"test", type:"input_text"});
        nj(this.opt.id + "_head_ownHeadline").aCh( f.getField()[0] );    
    }
    getSearchString = function() {
        if( typeof this.opt.filter === "undefined" ) {
            nj( this.opt.id + "_pag" ).htm( "" );
            return;   
        }
        let l = this.opt.searchArray.length;
        let i = 0;
        let searchString = "where ";
        if( this.opt.filter !== "" ) {
            searchString += this.opt.filter + " AND ";
        }
        while ( i < l ) {
            if( this.opt.searchArray[i].type === "input_text" ) {
                if( nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).v().length > 1 ) {
                    searchString += this.opt.searchArray[i].field + " like '" + nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).v() + "%' AND ";    
                } else {
                    searchString += "";
                }
            }
            if( this.opt.searchArray[i].type === "input_date" ) {
                if( nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).v().length > 1 ) {
                    searchString += this.opt.searchArray[i].field + " like '" + nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).v() + "%' AND ";    
                } else {
                    searchString += "";
                }
            }
            if( this.opt.searchArray[i].type === "select" ) {
                if( nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).gSV().join( "," ) !== ">-1" ) {
                    
                    if( this.opt.searchArray[i].sel === "value" ) {
                        searchString += this.opt.searchArray[i].field + " = '" + nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).gSV().join( "," ) + "' AND ";
                    } else {
                        // is area for e.g. date areas (date >= value and date <= [value])
                        let s = nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).gSV().join(",");
                        console.log( s, s.substring(s.length-3, s.length) );
                        if( s.substring(s.length-3, s.length)  !== ">-1" ) {
                            searchString += nj( "#" + this.opt.addPraefix + "search_" + this.opt.searchArray[i].field ).gSV().join( "," ) + " AND ";
                        }
                    }
                } else {
                    searchString += "";
                }
             }
            i += 1;
        }
        if( searchString.substring( searchString.length - 6, searchString.length - 1 ) === "  AND") {
            searchString = searchString.substring( 0, searchString.length - 5 )
        }
        this.opt.whereClausel = searchString.substring( 0, searchString.length - 5 );
        this.opt.currentPage = 0;
        this.getRecords();
    }
    getFieldDefinitions = function ( callback ) {
        // content
        data = {};
        data.command = "getFieldDefinitions";
        data.dVar = this.opt.dVar;
        data.table = this.opt.table;
        data.fields = this.opt.fields;
        data.fieldDefinitions = this.opt.fieldDefinitions;
        nj().fetchPostNew("library/php/ajax_dataform20.php", data, this.evaluateDF);
        if( typeof callback === "function" ) callback();
    }
    getRecords = function ( args ) {
        // content
        let orderBy = "";
        data.command = "getRecords";
        data.dVar = this.opt.dVar;
        data.table = this.opt.table;
        data.primaryKey = this.opt.primaryKey;
        data.fields = this.opt.fields;
        data.fieldDefinitions = [];
        if( this.opt.orderBy !== "" ) {
            orderBy = this.opt.orderBy.substring( 0, this.opt.orderBy.length - 1 );    
        }
        data.orderBy = orderBy;
        data.whereClausel = this.opt.whereClausel;
        if( this.opt.countPerPage !== 0 ) {
            data.limit = " LIMIT " + this.opt.currentPage * this.opt.countPerPage + ", " + this.opt.countPerPage;
        } else {
            data.limit = "";
        }
        data.hasNew = this.opt.hasNew;
        data.primaryKey = this.opt.primaryKey;
        nj().fetchPostNew("library/php/ajax_dataform20.php", data, this.evaluateDF);
    }
    prepareRecords = function ( data ) {
        // content
        this.opt.recordsets = [];
        let i, j, l, m, field, tmpField = {}, primaryKeyValue, tmpFieldType;
        l = data.records.length;
        //this.opt.countRecords = l;
        i = 0;
        while( i < l ) {
            this.opt.recordsets.push( new RecordSet( {
                dVar: this.opt.dVar + ".opt.recordsets." + i,
                id: "#" + this.opt.addPraefix + this.opt.id.substring( 1 ) + "RS" + "_" + data.records[ i ].primaryKey, 
                target: this.opt.id + "_data", 
                table: this.opt.table,
                baseClass: this.opt.baseClassRecordSet,
                addClasses: this.opt.addRSClasses,
                baseClassField: this.opt.baseClassField,
                classButtonSize: this.opt.classButtonSize,
                afterShowRS: this.opt.afterBuild
            } ) );
            m = this.opt.fieldDefinitions.length;
            j = 0;
            while( j < m ) {
                field = this.opt.fieldDefinitions[j];
                field.id = "#" + this.opt.addPraefix + field.field + "_" + data.records[ i ].primaryKey;
                if( typeof data.records[i][this.opt.fieldDefinitions[j].field] !== "undefined" ) {
                    field.value = data.records[i][this.opt.fieldDefinitions[j].field];
                }
                field.tabIndex = j;
                field.table = this.opt.table;
                field.target = this.opt.recordsets[i].opt.id;
                field.dVar = this.opt.dVar + ".opt.recordsets." + i + ".opt.fields." + j;
                field.validOnSave = this.opt.validOnSave;
                field.classButtonSize = this.opt.classButtonSize;
                this.opt.recordsets[i].opt.fields.push( new Field( field ) );
                j += 1;
            }
            if( this.opt.widthSave ) {
                tmpField.id = this.opt.addPraefix + "RS_save_" + data.records[ i ].primaryKey;
                tmpField.value = 0;
                tmpField.label = "Datensatz speichern";
                tmpField.baseClass = "";
                tmpField.addClasses = "cSave";
                tmpField.classButtonSize = this.opt.classButtonSize;
                tmpField.onClick = function ( args ) {
                    nj( this ).Dia( "dvar", 4 ).saveRecordset( nj( this ).gRO(), nj( this ).Dia( "dvar", 5 ), this.id.split("_")[this.id.split("_").length - 1] );
                }
                tmpField.tabIndex = j;
                tmpFieldType = { type: "button" }
                tmpField.table = this.opt.table;
                tmpField.target = this.opt.recordsets[i].opt.id;
                tmpField.dVar = this.opt.dVar + ".opt.recordsets." + i + ".opt.fields." + j;
                tmpField.value = "&nbsp;";
                tmpField.title = "Datensatz speichern"
                Object.assign( tmpField, tmpFieldType );
                this.opt.recordsets[i].opt.fields.push( new Field( tmpField ) );
            }
            if( this.opt.widthDelete ) {
                tmpField.id = this.opt.addPraefix + "RS_delete_" + data.records[ i ].primaryKey;
                tmpField.value = 0;
                tmpField.label = "Datensatz löschen";
                tmpField.baseClass = "";
                tmpField.addClasses = "cDelete";
                tmpField.classButtonSize = this.opt.classButtonSize;
                tmpField.onClick = function ( args ) {
                    console.log( this );
                    nj( this ).Dia( "dvar", 4 ).deleteRecordset( nj( this ).gRO(), nj( this ).Dia( "dvar", 5 ), this.id.split("_")[this.id.split("_").length - 1] );
                }
                tmpField.tabIndex = j;
                tmpFieldType = { type: "button" }
                //console.log( tmpField );
                tmpField.table = this.opt.table;
                tmpField.target = this.opt.recordsets[i].opt.id;
                tmpField.dVar = this.opt.dVar + ".opt.recordsets." + i + ".opt.fields." + ( j + 1 );
                tmpField.value = "&nbsp;";
                tmpField.title = "Datensatz löschen"
                Object.assign( tmpField, tmpFieldType );
                this.opt.recordsets[i].opt.fields.push( new Field( tmpField ) );
            }
            i += 1;
        }
        

        if( this.opt.hasNew && ( this.opt.recordsets.length < this.opt.countPerPage || this.opt.countPerPage === 0 ) ) {
            this.opt.recordsets.push( new RecordSet( {
                dVar: this.opt.dVar + ".opt.recordsets." + i, 
                id: "#" + this.opt.addPraefix + this.opt.id.substring( 1 ) + "RS" + "_new", 
                target: this.opt.id + "_data", 
                table: this.opt.table,
                baseClass: this.opt.baseClassRecordSet,
                addClasses: this.opt.addRSClasses,
                baseClassField: this.opt.baseClassField,
                classButtonSize: this.opt.classButtonSize,
                afterShowRS: this.opt.afterBuild,
            } ) );
            m = this.opt.fieldDefinitions.length;
            j = 0;
            while ( j < m ) {
                field = this.opt.fieldDefinitions[j];
                field.id = "#" + this.opt.addPraefix + field.field + "_new";
                if( typeof this.opt.fieldDefinitions[j].default !== "undefined" && this.opt.fieldDefinitions[j].default != null ) {
                    field.value = this.opt.fieldDefinitions[j].default;
                } else {
                    field.value = "";
                }

                field.tabIndex = j;
                field.table = this.opt.table;
                field.target = this.opt.recordsets[i].opt.id;
                field.dVar = this.opt.dVar + ".opt.recordsets." + i + ".opt.fields." + j;
                field.validOnSave = this.opt.validOnSave;
                field.classButtonSize = this.opt.classButtonSize;
                this.opt.recordsets[i].opt.fields.push( new Field( field ) );
                j += 1;
            }
            if( this.opt.widthSave ) {
                tmpField.id = this.opt.addPraefix + "RS_save_new";
                tmpField.value = 0;
                tmpField.label = "Datensatz speichern";
                tmpField.baseClass = "";
                tmpField.addClasses = "cSave";
                tmpField.classButtonSize = this.opt.classButtonSize;
                tmpField.onClick = function ( args ) {
                    nj( this ).Dia( "dvar", 4 ).saveRecordset( nj( this ).gRO(), nj( this ).Dia( "dvar", 5 ), this.id.split("_")[this.id.split("_").length - 1] );
                }
                tmpField.tabIndex = j;
                tmpFieldType = { type: "button" }
                //console.log( tmpField );
                tmpField.table = this.opt.table;
                tmpField.target = this.opt.recordsets[i].opt.id;
                tmpField.dVar = this.opt.dVar + ".opt.recordsets." + i + ".opt.fields." + j;
                tmpField.value = "&nbsp;";
                tmpField.title = "Datensatz speichern"
                Object.assign( tmpField, tmpFieldType );
                this.opt.recordsets[i].opt.fields.push( new Field( tmpField ) );
            }
        }
        this.showRecordSets();
    }
    buildNewRecord = function () {
        let l = this.opt.fieldDefinitions.length;
        let i = 0;
        while( i < l ) {
            i += 1;
        }
    }
    showRecordSets = function () {
        nj( this.opt.id + "_data" ).htm( "" );
        let l = this.opt.recordsets.length;
        let i = 0;
        while( i < l ) {
            this.opt.recordsets[i].getRecord();
            i += 1;
        }
        if( typeof this.opt.afterbuild === "function" ) this.opt.afterbuild()
    }
    initPagination = function() {
        this.opt.countRecords = parseInt( this.opt.countRecords )
        if( this.opt.hasNew ) this.opt.countRecords += 1;
        let countPages;
        if( Number.isInteger( this.opt.countRecords / this.opt.countPerPage ) ) {
            countPages = this.opt.countRecords / this.opt.countPerPage;
        } else {
            countPages = parseInt( this.opt.countRecords / this.opt.countPerPage ) + 1;
        }
        let cPag = DIFF_PAGINATION * 2 + 1, htm = "<a href='#'  id='" + this.opt.addPraefix + "_pag_firstPage'>«</a><a href='#'  id='" + this.opt.addPraefix + "_pag_prevPage'>‹</a><span>...</span>";
        if( countPages < cPag ) {
            let l = countPages;
            let i = 0;
            while ( i < l ) {
                if( this.opt.currentPage == i ) {
                    htm += '<a href="#" id="' + this.opt.addPraefix + 'pag_' + i + '" class="' + this.opt.addPraefix + '_activePage">' + ( i + 1 ) + '</a>';
                } else {
                    htm += '<a href="#" id="' + this.opt.addPraefix + 'pag_' + i + '">' + ( i + 1 ) + '</a>';                    
                }
                i += 1;
            }
        } else {
            let l = cPag;
            let i = this.opt.currentPage - DIFF_PAGINATION;
            if( this.opt.currentPage + DIFF_PAGINATION < countPages ) {
                i = this.opt.currentPage - DIFF_PAGINATION;
                l = i + cPag;
                if( i < 0 ) {
                    i = 0;
                    l = cPag;    
                }
            } else {
                i = countPages - cPag;
                l = countPages;
            }
            while ( i < l ) {
                if( i >= countPages ) break;
                if( this.opt.currentPage == i ) {
                    htm += '<a href="#" id="' + this.opt.addPraefix + '_pag_' + i + '" class="' + this.opt.classPraefix + '_activePage">' + ( i + 1 ) + '</a>';
                } else {
                    htm += '<a href="#" id="' + this.opt.addPraefix + '_pag_' + i + '">' + ( i + 1 ) + '</a>';                    
                }
                i += 1;
            }

    }
        htm += '<span>...</span><a href="#"  id="' + this.opt.addPraefix + '_pag_nextPage">›</a><a href="#"  id="' + this.opt.addPraefix + '_pag_lastPage">»</a>';
        nj( this.opt.id + "_pag" ).htm( htm );
        let els = document.querySelectorAll( this.opt.id + "_pag>a" );
        let l = els.length;
        let i = 0;
        let id, df;
        while ( i < l ) {
            id = getIdAndName( els[i].id ).Id;
            switch( id ) {
                case "firstPage":
                    nj( els[i] ).on( "click", function() {
                        nj(this).Dia().opt.currentPage = 0;
                        nj(this).Dia().getRecords()
                    });
                break;
                case "prevPage":
                    nj( els[i] ).on( "click", function() {
                        nj(this).Dia().opt.currentPage = parseInt( nj(this).Dia().opt.currentPage ) -1;
                        if( nj(this).Dia().opt.currentPage < 0 ) {
                            nj(this).Dia().opt.currentPage = 0;
                        }
                        nj(this).Dia().getRecords()
                    });
                break;
                case "nextPage":
                    nj( els[i] ).on( "click", function() {
                        nj(this).Dia().opt.currentPage = parseInt( nj(this).Dia().opt.currentPage ) + 1;
                        //console.log( df.opt.countPages, df.opt.currentPage );
                        if( nj(this).Dia().opt.currentPage == countPages ) {
                            nj(this).Dia().opt.currentPage = countPages - 1;
                        }
                        nj(this).Dia().getRecords();
                    });
                break;
                case "lastPage":
                    nj( els[i] ).on( "click", function() {
                        nj(this).Dia().opt.currentPage = countPages - 1;
                        nj(this).Dia().getRecords()
                    });
                break;
                default:
                    nj( els[i] ).on( "click", function( e ) {
                        nj(this).Dia().opt.currentPage = parseInt( getIdAndName( e.target.id ).Id );;
                        nj(this).Dia().getRecords()
                    });
                break;

            }
            i += 1;
        }
    }
    initRecordPointer = function() {
        nj( "button[id^='" + this.opt.addPraefix + "recordPointer_']:not(button[id*=_recordPointer_new])").on( "click", function( e ) {
            e.stopImmediatePropagation();
            nj( ".cRecordPointer" ).rCl( "cRecPointerSelected" );
            nj( this ).aCl( "cRecPointerSelected" );
            let df = nj(this).Dia("dvar", 1 );
            let cRec = getIdAndName( this.id ).Id;
            nj(this).gRO().opt.currentRecord = cRec;
            let l = df.opt.boundForm.length;
            console.log(df.opt.lastRecordPointer);
            let i = 0;
            let field;
            while ( i < l ) {
                field = nj().fOA( window[df.opt.boundForm[i]].opt.fieldDefinitions, "field", df.opt.boundFields[i].to)[0];
                field.default = nj( "#" + df.opt.addPraefix + df.opt.boundFields[i].from + "_" + cRec ).v();
                if( df.opt.boundFilter !== "" ) {
                    window[df.opt.boundForm[i]].opt.filter = df.opt.boundFilter + " and " + df.opt.boundFields[i].to + " = " + nj( "#" + df.opt.addPraefix + df.opt.boundFields[i].from + "_" + cRec ).v();
                } else {
                    window[df.opt.boundForm[i]].opt.filter = df.opt.boundFields[i].to + " = " + nj( "#" + df.opt.addPraefix + df.opt.boundFields[i].from + "_" + cRec ).v();
                }
                window[df.opt.boundForm[i]].getSearchString();
                if( window[df.opt.boundForm[i]].opt.formType !== "html" ) {
                    window[df.opt.boundForm[i]].dDF.show();
                }
                i += 1;
            }
        });
        nj( "button[id*=_recordPointer_new]").on( "click", function( e ) {
            e.stopImmediatePropagation();
            nj( ".cRecordPointer" ).rCl( "cRecPointerSelected" );
            let df = nj(this).Dia("dvar", 1 );
            let cRec = getIdAndName( this.id ).Id;
            let l = df.opt.boundForm.length;
            let i = 0;
            let field;
            while ( i < l ) {
                nj( "#" + window[df.opt.boundForm[i]].opt.dVar + "_data" ).htm( "" );
                i += 1;
            }
        });
        nj( "div:has(>div.cStars)" ).on( "click", function( e ) {
            e.stopImmediatePropagation(); 
            if( nj().els(this).children[0].children.length === 0 ) return; 
            var rect = nj().els(this).getBoundingClientRect(); 
            var x = event.clientX - rect.left; 
            var y = event.clientY - rect.top; 
            nj().els(this).children[0].children[1].setAttribute("width", (parseInt(x/20) + 1)*20 )
        })    
    }
    initBehavior = function() {
    }
    init = function () {
        // content
        if( this.opt.filter !== "" ) {
            this.opt.whereClausel = " where " + this.opt.filter;
        }
        this.initBehavior();
        this.getFieldDefinitions( this.opt.afterBuild );
        if( typeof this.opt.onShow === "function") this.opt.onShow();
    }
}

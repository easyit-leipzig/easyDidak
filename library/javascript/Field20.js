//javascript
class Field {                    // class for DataForm2.0
      constructor( param ) {
        this.opt = {
            id:                 undefined,  // necessary - id of field; fieldname in databasetable
            dVar:               undefined,  // necessary - var of field object
            tabIndex:           undefined,  // index in recordset 
            value:              undefined,  // value of field
            default:            undefined,  // default value of field
            isPrimaryKey:       false,      // is true if field is primary key
            isAutoInc:          false,      // is true if field is auto increment
            maxValue:           undefined,
            minValue:           undefined,
            maxLength:          undefined,
            label:              "", // label of field - is id if not set
            title:              "", // tooltip value
            table:              "", // nessecary - source table for field
            type:               "input_text", /* fieldtype: 
                                                    select
                                                    recordPointer
                                                    button
                                                    input_text
                                                    input_number
                                                    input_date
                                                    input_time
                                                    input_month
                                                    input_week
                                                    input_datetime
                                                    input_datetime-local
                                                    input_button
                                                    input_password
                                                    input_color
                                                    input_email
                                                    input_tel
                                                    input_url
                                                    input_range
                                                    radio
                                                    checkbox
                                                    div
                                                    label
                                                    textarea
                                                    stars
                                                    img
                                                    bckg
                                                */
            addPraefix:         "", // praefix for recordsets and fields; e.g. "Df_", "df"
            addAtr:            "", // additional attributes for html e.g.: 'target = "_blank" placeholder="[placeholder]"; ...' / combinitions are possible
            baseClass:          "cField",
            addClasses:         "", // additional classes for field; e.g. "cUsusal cLabel ..."
            classButtonSize:    "", // additional classes for button size; e.g. "cButtonMin", "cButtonSmall" .. 
            imageSize:          100,
            containerImgWidth:  undefined,
            containerImgHeight: undefined,
            starRatio:          1,
            easyLink:           false, // if is true a full link will (text, href. target) be created else where only the link will be safed
            valid:              [], // validity ["not empty", "not 0", "not null", "not undifined", "is email", "is postalcode", "is unique", "is in range", ...]; combinitions are possible
            validOnSave:        false, // checks validity on save else validation will be done if field onblur
            options:            undefined, // options for select field, options for input text datalist
                                /* <input list="ice-cream-flavors" id="ice-cream-choice" name="ice-cream-choice" />
                                        <datalist id="ice-cream-flavors">
                                            <option value="Chocolate"></option>
                                            <option value="Coconut"></option>
                                            <option value="Mint"></option>
                                            <option value="Strawberry"></option>
                                            <option value="Vanilla"></option>
                                        </datalist>
                                */
            widthLabel:          false,
            withDiv:            false,
            uploadPath:         "library/df/",
            onFocus:            function( args ) {
                if( nj().els( "button[id^=" + nj( this ).gRO().opt.addPraefix + "recordPointer_].cRecPointerSelected" ).length === 1 ) {
                    if( getIdAndName( this.id ).Id !== getIdAndName( nj().els( "button[id^=" + nj( this ).gRO().opt.addPraefix + "recordPointer_].cRecPointerSelected" )[0].id ).Id ) {
                        nj( "button[id^=" + nj( this ).gRO().opt.addPraefix + "recordPointer_].cRecPointerSelected" ).rCl("cRecPointerSelected");
                        let l = nj( this ).gRO().opt.boundForm.length;
                        let i = 0;
                        while ( i < l ) {
                            nj( window[nj( this ).gRO().opt.boundForm[i]].opt.id + "_data" ).htm( "" );
                            i += 1;
                        }
                    }
                }
            },
            onBlur:             function( args ) {
                if( !nj( this ).gRO().opt.validOnSave ) {
                    let res = nj( this ).Dia().checkValidity();
                    console.log( res );
                    if( !res.success ) {
                        dMNew.show( {title: "Fehler", type: false, text: res.message } );
                        return;
                    }
                }   
            },
            onChange:           function( event ) {                                    
                                        if( nj(this).Dia().opt.addAtr.indexOf( "undefined" === -1 ) ) {
                                            switch( nj(this).Dia().opt.type ) {
                                                case "number":
                                                    console.log( typeof nj(this).Dia().getValue())
                                                    let val = nj(this).Dia().getValue();
                                                    if( val < nj(this).Dia().opt.minValue ) {
                                                        nj( nj(this).Dia().opt.id ).v( nj(this).Dia().opt.minValue )
                                                    }
                                                    if( val > nj(this).Dia().opt.maxValue ) {
                                                        nj( nj(this).Dia().opt.id ).v( nj(this).Dia().opt.maxValue )
                                                    }
                                            break;
                                            }
                                        }                            
                                    },
            onClick:            function( event ) {
                                    console.log( this );

                                    let elId, el, tmp;
                                    if( nj(this).Dia().opt.addAtr.indexOf( "undefined" === -1 ) ) {
                                        switch( nj(this).Dia().opt.type ) {
                                            case "stars":
                                                var rect = nj().els(this).getBoundingClientRect(); 
                                                var x = event.clientX - rect.left; 
                                                var y = event.clientY - rect.top; 
                                                nj().els(this).children[1].setAttribute("width", (parseInt(x/20) + 1)*20 );
                                                //nj("div[class*=_val_stars_][id*=_new]").sty( "top", "unset");
                                            break;
                                            case "img":
                                                nj( "#" + nj( this ).gRO().opt.dVar + "_tFUFile" ).atr( "accept", ".png,.jpg");
                                                nj( this ).gRO().divUpload.show({variables: {df: nj(this).gRO(), id: this.id, attr: "src", uploadPath: nj( this ).Dia().opt.uploadPath, table: nj( this ).gRO().opt.table, field: nj( this ).Dia().opt.field } });
                                            break;
                                            case "file":
                                                event.preventDefault();
                                                nj( "#" + nj( this ).gRO().opt.dVar + "_tFUFile" ).rAt( "accept" );
                                                nj( this ).gRO().divUpload.show({variables: {df: nj(this).gRO(), id: this.id, attr: "href", uploadPath: nj( this ).Dia().opt.uploadPath, table: nj( this ).gRO().opt.table, field: nj( this ).Dia().opt.field } });
                                            break;
                                            case "link":
                                                event.preventDefault();
                                                elId = nj( this ).Dia().opt.id;
                                                console.log( elId );
                                                nj().els( "#" + nj( this ).gRO().opt.dVar + "_linkElId" ).value = elId;
                                                nj( this ).gRO().divEditLink.show({variables: {df: nj(this).gRO(), el: nj( this ).Dia() } } );
                                            break;
                                            case "select":
                                                if( nj( this ).hAt( "multiple" ) && nj( this ).hAt( "data-clickable" ) ) {
                                                    event.preventDefault();
                                                    elId = nj( this ).Dia().opt.id;
                                                    console.log(elId)
                                                    el = nj().cEl( "select" );
                                                    //el.id = "tmpSetSelect";
                                                    tmp = nj().els( this ).outerHTML;
                                                    tmp = htmlToElement( tmp )
                                                    tmp.id = nj( this ).gRO().opt.addPraefix + "TmpSetSelect" ;
                                                    nj( "#" + nj( this ).gRO().opt.dVar + ".divEditSelect" ).htm("");
                                                    nj( "#" + nj( this ).gRO().opt.dVar + ".divEditSelect" ).aCh( tmp );
                                                    nj( this ).gRO().divEditSelect.show({variables: {df: nj(this).gRO(), el: nj( this ).Dia() } } );                                                    
                                                }
                                            break;
                                            case "textarea":
                                                if( nj( this ).hAt( "data-clickable" ) ) {
                                                    event.preventDefault();
                                                    elId = nj( this ).Dia().opt.id;
                                                    el = nj().cEl( "select" );
                                                    //el.id = "tmpSetSelect";
                                                    tmp = nj().els( this ).outerHTML;
                                                    tmp = htmlToElement( tmp )
                                                    tmp.id = nj( this ).gRO().opt.addPraefix + "TmpSetTextarea" ;
                                                    nj( "#" + nj( this ).gRO().opt.dVar + ".divEditTextarea" ).htm("");
                                                    nj( "#" + nj( this ).gRO().opt.dVar + ".divEditTextarea" ).aCh( tmp );
                                                    nj( this ).gRO().divEditTextarea.show({variables: {df: nj(this).gRO(), el: nj( this ).Dia() } } );                                                    
                                                }
                                            break;
                                        }
                                    }
                                },
            onDblClick:         undefined,
            onMouseOver:        undefined,
        }
        let showOnInit = true,
            boxId = "",
            tmpId = "",
            tmpClasses = "",
            tmpEl = {}, 
            tmpEls;
        Object.assign( this.opt, param );
        if( typeof this.opt.default !== "undefined" && typeof this.opt.value === "undefined" ) {
            this.opt.value = this.opt.default;       
        }
        if( this.opt.type === "button" || this.opt.type === "input_button" ) {
            this.opt.addClasses = this.opt.baseClass + " " + this.opt.classButtonSize + " " + this.opt.addClasses;
        } else {
            this.opt.addClasses = this.opt.baseClass + " " + this.opt.addClasses;
        }
        if( this.opt.label === "" ) this.opt.label = this.opt.id;
        if( typeof this.opt.onFocus === "function" ) {
        }
    }
    buildFieldDefs( fieldDef ) {
        this.opt.id = fieldDef.id;
        return this;
    }
    getId = function( args ) {        
        if( typeof this.opt.index !== "undefined" ) {
            if( this.opt.addPraefix === "" ) {
                this.tmpId = this.opt.id + '_' + this.opt.index;    
            } else {
                this.tmpId = this.opt.addPraefix + "_" + this.opt.id + '_' + this.opt.index;
            }                    
        } else {
            if( this.opt.addPraefix === "" ) {
                this.tmpId = this.opt.id;    
            } else {
                this.tmpId = this.opt.addPraefix + "_" + this.opt.id;
            }
        }
        return this.tmpId;   
    }
    setRecordPointer = function( res ) {
        console.log( res );
    }    
    checkValidity = function() {
        let result = {success: true};
        let tmpValid = this.opt.valid;
        let v = this.getValue();
        let l = tmpValid.length;
        let i = 0;
        while ( i < l ) {
            console.log( v, tmpValid[ i ] );
            switch( tmpValid[ i ] ) {
                case "not -1":
                    if( v == -1 ) {
                        result.success = false;
                        result.message = "Du musst für das Feld '" + this.opt.label + "' eine Auswahl treffen.";    
                    }
                break;
                case "not 0":
                    if( v == 0 ) {
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' darf nicht 0 sein.";    
                    }
                break;
                case "not empty":
                    if( v === "" ) {
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' darf nicht leer sein.";    
                    }
                break;
                case "is email":
                    if( v !== "" && !validateEmail( this.getValue() ) ) {
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' muss eine E-Mail-Adresse sein.";    
                    }
                break;
                case "is url":
                    if( v !== "" && !validateURL( this.getValue() ) ) {
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' muss ein gültiger Link sein.";    
                    }
                break;
                case "is number":
                    if( Number( v ) === NaN ) {
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' muss eine Zahl sein.";    
                    }
                case "is integer":
                    if( Number( v ) !== NaN && Number.isInteger( Number( v ) ) ) {
                    } else {
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' muss eine Ganzzahl sein.";                       
                    }
                break;
                case "in range":
                    if( v < this.opt.minValue || v > this.opt.maxValue ) {
                        nj( this.opt.id ).v( this.opt.default );
                        result.success = false;
                        result.message = "Das Feld '" + this.opt.label + "' muss im Bereich von " + this.opt.minValue + " und " + this.opt.maxValue + " liegen.";                       
                    }
                break;
            }
            i += 1;
        }
        return result;
    }
    getValue = function( value ) {
        if( typeof this.opt.index !== "undefined" ) {
            if( this.opt.addPraefix === "" ) {
                if( this.opt.type === "checkbox" ) {
                    return nj( "#" + this.opt.id + '_' + this.opt.index ).chk();
                } else {
                    return nj( "#" + this.opt.id + '_' + this.opt.index ).v();
                }                    
            } else {
                console.log( this.opt.type );
                if( this.opt.type === "checkbox" ) {
                    return nj( "#" + this.opt.addPraefix + "_" + this.opt.id + '_' + this.opt.index ).chk();
                } else {
                    return nj( "#" + this.opt.addPraefix + "_" + this.opt.id + '_' + this.opt.index ).v();    
                }
            }
        } else {
            if( this.opt.addPraefix === "" ) {
                switch( this.opt.type ) {
                case "checkbox":
                    return nj( this.opt.id ).chk();
                    break;
                case "select":
                    return nj( this.opt.id ).gSV().join();
                    break;
                case "img":
                    return nj( this.opt.id ).atr( "src" );
                    break;
                case "stars":
                    return nj().els( this.opt.id ).children[1].getAttribute( "width" ) / 20;
                    break;
                case "link":
                case "file":
                    console.log( this.opt.easyLink );
                    //return nj( "#" + this.opt.addPraefix + "_" + this.opt.id ).atr( "href" );
                    if( this.opt.easyLink ) {
                        return nj( this.opt.id ).atr( "href" );
                    } else {
                        return nj( this.opt.id ).htm() + "|" + nj( this.opt.id ).atr( "target" ) + "|" + nj( this.opt.id ).atr( "href" );
                    }
                    break;
                case "div":
                    return nj( this.opt.id ).htm();
                    break;
                default:
                    return nj( this.opt.id ).v();    
                    break;
                }
            } else {
                switch( this.opt.type ) {
                case "checkbox":
                    return nj( "#" + this.opt.addPraefix + "_" + this.opt.id ).chk();
                    break;
                case "select":
                    return nj( "#" + this.opt.addPraefix + "_" + this.opt.id ).gSV().join();
                    break;
                case "stars":
                    return nj().els( "#" + this.opt.addPraefix + "_" + this.opt.id ).children[1].getAttribute( "width" ) / 20;
                    break;
                case "link":
                    console.log( this );
                    //return nj( "#" + this.opt.addPraefix + "_" + this.opt.id ).atr( "href" );
                    return "1";
                    break;
                
                default:
                    return nj( "#" + this.opt.addPraefix + "_" + this.opt.id ).v();    
                    break;
                }
            }            
        }
    }
    getField = function() {
        let fieldHTML = "", tmpValueArry = [], el, tmpId, i, l, fieldElements = [];
        if( this.opt.type.substring( 0, 6 ) === "input_" ) {
            this.opt.type = this.opt.type.split( "_" )[1]
        }
        if( this.opt.widthLabel ) {
            if( this.opt.addPraefix !== "" ) {
                el = this.opt.addPraefix + "_" + this.opt.id.substring( 1 );
            } else {
                el = this.opt.id.substring( 1 );
            }
            /*
            if( this.opt.index !== "" ) {
                el += "_" + this.opt.index;
            }
            */
            if( this.opt.addPraefix === "" ) {
                fieldElements.push( htmlToElement( "<label id='lab_" + el + "' for='" + el + "'>" + this.opt.label + "</label>" ) );
            } else {
                fieldElements.push( htmlToElement( "<label id='lab_" + el + "'  class='lab_" + this.opt.dVar + "' for='" + el + "'>" + this.opt.label + "</label>" ) );
            }
        }
        switch ( this.opt.type) {
            case "select":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<select id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ' + this.opt.addAtr + " ";    
                    } else {
                        fieldHTML += '<select id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ' + this.opt.addAtr + " ";
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<select id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ' + this.opt.addAtr + " ";
                    } else {
                        fieldHTML += '<select id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ' + this.opt.addAtr + " ";
                    }
                }
                fieldHTML += ' class="cSelect ' + this.opt.addClasses + '" ';
                if( typeof this.opt.title !== "undefined" ) fieldHTML += 'title="' + this.opt.title +'" ';
                fieldHTML += this.opt.addAtr + '>' + this.opt.options + '</select>';
                this.tmpEl = htmlToElement( fieldHTML );
                if( typeof this.opt.value !== "undefined" && this.opt.value != null ) {
                    this.tmpValueArry = ( "" + this.opt.value ).split( "," );
                    l = this.tmpEl.children.length;
                    i = 0;
                    while (i < l) {
                         if( this.tmpValueArry.includes(  this.tmpEl.children[i].value ) ) {
                            nj( this.tmpEl.children[i] ).atr( "selected", "" );
                        } 
                        i += 1;
                    }
                }
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            case "text":
            case "date":
            case "time":
            case "number":
            case "datetime-local":
            case "color":
            case "password":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<input id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" maxlength="' + this.opt.maxLength + '" ';    
                    } else {
                        fieldHTML += '<input id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" maxlength="' + this.opt.maxLength + '" ';
                    }
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<input id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" maxlength="' + this.opt.maxLength + '" ';    
                    } else {
                        fieldHTML += '<input id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + ' maxlength="' + this.opt.maxLength + '" ';
                    }
                }
                if( typeof this.opt.minValue !== "undefined" ) {
                    fieldHTML += ' min="' + this.opt.minValue + '"';
                }
                if( typeof this.opt.maxValue !== "undefined" ) {
                    fieldHTML += ' max="' + this.opt.maxValue + '"';
                }
                if( typeof this.opt.maxLength !== "undefined" ) {
                    fieldHTML += ' maxlength="' + this.opt.maxLength + '"';
                }
                if( typeof this.opt.title !== "undefined" ) {
                    fieldHTML += ' title="' + this.opt.title + '"';
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" type="' + this.opt.type + '" value="' + this.opt.value + '" title="' + this.opt.title + '">';
                this.tmpEl = htmlToElement( fieldHTML );
                if( typeof this.opt.options !== "undefined" && this.opt.type === "text" ) {
                    tmpId = "list_" + this.tmpEl.id;
                    this.tmpEl.setAttribute("list", tmpId );
                }
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  );
                if( typeof this.opt.options !== "undefined" && this.opt.type === "text" ) {
                    this.opt.options = this.opt.options.replaceAll( "value=", "");
                    el = nj().cEl( "datalist" );
                    el.id = "list_" + this.tmpEl.id;
                    nj( el ).htm( this.opt.options );
                    fieldElements.push( el  );
                }
            break;
            case "but":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<input id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '"';    
                    } else {
                        fieldHTML += '<input id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '"';
                    }
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<input id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"';    
                    } else {
                        fieldHTML += '<input id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" type="button" value="' + this.opt.value + '">';
                this.tmpEl = htmlToElement( fieldHTML );
                fieldElements.push( this.tmpEl  );
            break;
            case "link":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<a id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '"';    
                    } else {
                        fieldHTML += '<a id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '"';
                    }
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<a id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"';    
                    } else {
                        fieldHTML += '<a id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" ';
                tmpValueArry = this.opt.value.split( "|" );
                if( tmpValueArry.length === 3 ) {
                    fieldHTML += ' target="' + tmpValueArry[1] + '" href="' + tmpValueArry[2] + '">' + tmpValueArry[0] + '</a>';                    
                } else {
                    fieldHTML += ' target="_blank" href="' + this.opt.value + '">Link</a>';
                }
                this.tmpEl = htmlToElement( fieldHTML );
                fieldElements.push( this.tmpEl  )
            break;
            case "file":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<a id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '"';    
                    } else {
                        fieldHTML += '<a id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '"';
                    }
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<a id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"';    
                    } else {
                        fieldHTML += '<a id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" ';
                tmpValueArry = this.opt.value.split( "|" );
                if( tmpValueArry.length === 3 ) {
                    fieldHTML += ' target="' + tmpValueArry[1] + '" href="' + tmpValueArry[2] + '">' + tmpValueArry[0] + '</a>';                    
                } else {
                    fieldHTML += ' target="_blank" href="' + this.opt.value + '">Datei</a>';
                }
                this.tmpEl = htmlToElement( fieldHTML );
                fieldElements.push( this.tmpEl  )
            break;
            case "checkbox":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<input id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<input id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<input id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<input id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                fieldHTML += this.opt.addAtr;
                if( this.opt.value == true ) {
                    fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" type="checkbox" checked ' + 'title="' + this.opt.title + '">';
                } else {
                    fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" type="checkbox" ' + 'title="' + this.opt.title + '">';
                }
                this.tmpEl = htmlToElement( fieldHTML );
                fieldElements.push( this.tmpEl  )
            break;
            case "button":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<button id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" title="' + this.opt.title + '"';    
                    } else {
                        fieldHTML += '<button id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" title="' + this.opt.title + '"';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<button id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"  title="' + this.opt.title + '"';    
                    } else {
                        fieldHTML += '<button id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"  title="' + this.opt.title + '"';
                    }
                }
                if( typeof this.opt.value === "undefined" ) this.opt.value = "";
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '">' + this.opt.value + '</button>';
                this.tmpEl = htmlToElement( fieldHTML );
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            case "div":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<div id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" title="' + this.opt.title + '"';    
                    } else {
                        fieldHTML += '<div id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" title="' + this.opt.title + '"';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<div id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"  title="' + this.opt.title + '"';    
                    } else {
                        fieldHTML += '<div id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"  title="' + this.opt.title + '"';
                    }
                }
                if( typeof this.opt.value === "undefined" ) this.opt.value = "";
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' tabindex=0 class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '">' + this.opt.value + '</div>';
                this.tmpEl = htmlToElement( fieldHTML );
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            case "textarea":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<textarea id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" title="' + this.opt.title + '"';    
                    } else {
                        fieldHTML += '<textarea id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" title="' + this.opt.title + '"';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<textarea id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"  title="' + this.opt.title + '"';    
                    } else {
                        fieldHTML += '<textarea id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '"  title="' + this.opt.title + '"';
                    }
                }
                if( typeof this.opt.value === "undefined" ) this.opt.value = "";
                if( typeof this.opt.maxLength !== "undefined" ) {
                    fieldHTML += ' maxlength="' + this.opt.maxLength + '"';
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '">' + this.opt.value + '</textarea>';
                this.tmpEl = htmlToElement( fieldHTML );
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            case "img":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<img id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<img id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<img id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<img id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                let img = new Image(), w, h, res = this.opt.id, f;
                img.src = this.opt.value;
                if( this.opt.value === "" ) {
                        w = "width=" + this.opt.imageSize;
                        h = "height=" + this.opt.imageSize;
                } else {
                    img.onload = function( e ) {
                        let c = this.width, imSize, diff;

                        f = htmlToElement( fieldHTML );
                        console.log( f.id );
                        console.log( nj().bDV( nj( "#" + f.id ).ds( "dvar" ) ).opt.imageSize );
                        
                        imSize = nj().bDV( nj( "#" + f.id ).ds( "dvar" ) ).opt.imageSize
                        if( img.width / img.height >= 1 ) {
                            diff = ( imSize - img.height / img.width * imSize ) / 2;
                            console.log( diff );
                            nj( "#" + f.id ).sty("margin-left","unset");
                            nj( "#" + f.id ).sty("margin-right","unset");
                            nj( "#" + f.id ).sty({"margin-top":diff+"px","margin-bottom":diff+"px"})
                        } else {
                            diff = ( imSize - img.width / img.height * imSize ) / 2;
                            console.log( diff );
                            nj( "#" + f.id ).sty("margin-top","unset");
                            nj( "#" + f.id ).sty("margin-bottom","unset");
                            nj( "#" + f.id ).sty({"margin-left":diff+"px","margin-right":diff+"px"})
                        }                    
                    }
                    if( img.width / img.height >= 1 ) {
                        w = "width=" + this.opt.imageSize;
                        h = "height=auto";
                    } else {
                        w = "width=auto";
                        h = "height=" + this.opt.imageSize;
                    }
                }
                fieldHTML += " " + w + " " + h + " ";
                fieldHTML += " " + this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '" src="' + this.opt.value + '">';
                this.tmpEl = htmlToElement( fieldHTML );
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            case "recordPointer":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<button id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<button id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<button id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<button id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '">' + this.opt.value + '</button>';
                this.tmpEl = htmlToElement( fieldHTML );
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            case "stars":
                if( typeof this.opt.index !== "undefined" ) {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<div id="' + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<div id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '_' + this.opt.index + '" data-dvar="' + this.opt.dVar + '" ';
                    }                    
                } else {
                    if( this.opt.addPraefix === "" ) {
                        fieldHTML += '<div id="' + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';    
                    } else {
                        fieldHTML += '<div id="' + this.opt.addPraefix + "_" + this.opt.id.substring( 1 ) + '" data-dvar="' + this.opt.dVar + '" ';
                    }
                }
                fieldHTML += this.opt.addAtr;
                fieldHTML += ' class="c' + uppercaseWords( this.opt.type ) + ' ' + this.opt.addClasses + '">';
                fieldHTML += '<img src="library/css/icons/star_bar.png" width="100" style="position: relative;z-index: 1">';
                fieldHTML += '<img src="library/css/icons/background_yellow.png" width="' + parseFloat(this.opt.value) * 20 + '" height="20" style="position: relative; top: -25px; z-index: 0">';
                fieldHTML += '</div>';
                this.tmpEl = htmlToElement( fieldHTML );
                //this.setActions( this.tmpEl );
                fieldElements.push( this.tmpEl  )
            break;
            default:
                // statements_def
                break;
        }        
        if( this.opt.withDiv ) {
            el = nj().cEl( "div" );
            if( this.opt.addPraefix === "" ) {
                el.id = 'div_' + this.opt.id.substring( 1 );    
            } else {
                el.id = this.opt.addPraefix + '_div_' + this.opt.id.substring( 1 );    
            }
            nj( el ).aCl( "divField_" + this.opt.id.substring( 1 ) );
            nj( el ).aCl( "div" + uppercaseWords( this.opt.type ) );
            l = fieldElements.length;
            i = 0;
            while ( i < l ) {
                nj( el ).aCh( fieldElements[i] ); 
                i += 1;
            }
            fieldElements = [];
            fieldElements.push( el ); 
        }
        return fieldElements;
    }
}

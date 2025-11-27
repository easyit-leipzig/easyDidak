class RecordSet {                    // class for DataForm2.0
      constructor( param ) {
        this.opt = {
            id:                 undefined,  // necessary - id of field; fieldname in databasetable
            dVar:               undefined,  // necessary - var of field object
            target:             undefined,
            fields:             [],         // necessary - fielddefinitions 
            values:             [],
            table:              undefined,  // nessacary - tablename for Recordset
            variables:          {},         // optional - additional variables for Recordset
            baseClass:          param.addClassesRecordSet,
            baseClassField:     "",
            addClasses:         "",
            classButtonSize:    "",
            afterShowRS:        undefined,
            onRSFocus:          function() {},
            onRSBlur:           function() { console.log( "blur") },
        }
        let showOnInit = true, primaryKey, primaryKeyValue;
        Object.assign( this.opt, param );
    }
    evaluateRS = function ( data ) {
        let jsonobject, l, i, tmp;
        if( typeof data === "string" ) {
            jsonobject = JSON.parse( data );
        } else {
            jsonobject = data;
        }
        if( !nj().isJ( jsonobject ) ) {
            throw "kein JSON-Objekt übergeben";
        }
        var fr = window[ jsonobject.dVar ];
        switch( jsonobject.command ) {
            case "saveRecordset":
            break;
            default:
                // content
        
            break;
        }
    }
    getFields = function () {
        let els = nj().els( this.opt.id + " ." + this.opt.baseClassField );
    }
    prepareRecord = function ( args ) {

    }
    getRecord = function ( getFields = true ) {
        let i, j, l, m, elField;
        let el = nj().cEl( "div" );
        el.id = this.opt.id.substring( 1 );
        nj( el ).aCN( this.opt.baseClass + this.opt.addClasses );
        nj( el ).sDs( "dvar", this.opt.dVar );
        nj( el ).atr( "tabindex", "-1" );
        nj( el ).on( "focus", this.opt.onRSFocus );
        nj( this.opt.target ).aCh( el );
        if( getFields ) {
            l = this.opt.fields.length;
            i = 0;
            while( i < l ) {
                elField = this.opt.fields[ i ].getField()
                let m = elField.length;
                let j = 0;
                while( j < m ) {
                    nj( "#" + el.id ).aCh( elField[j] );
                    if( typeof this.opt.onRSFocus === "function" ) {
                       nj(elField[j]).on( "focus", function(){ nj( "#" + el.id ).tri("focus") } ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onFocus === "function" ) {
                       nj(elField[j]).on( "focus", nj(elField[j]).Dia().opt.onFocus ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onBlur === "function" ) {
                       nj(elField[j]).on( "blur", nj(elField[j]).Dia().opt.onBlur ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onChange === "function" ) {
                       nj(elField[j]).on( "change", nj(elField[j]).Dia().opt.onChange ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onClick === "function" ) {
                       nj(elField[j]).on( "click", nj(elField[j]).Dia().opt.onClick ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onDblClick === "function" ) {
                       nj(elField[j]).on( "dblclick", nj(elField[j]).Dia().opt.onDblClick ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onMouseOver === "function" ) {
                       nj(elField[j]).on( "mouseover", nj(elField[j]).Dia().opt.onMouseOver ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onMouseOut === "function" ) {
                       nj(elField[j]).on( "mouseout", nj(elField[j]).Dia().opt.onMouseOut ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onMouseDown === "function" ) {
                       nj(elField[j]).on( "mousedown", nj(elField[j]).Dia().opt.onMouseOver ); 
                    }
                    if( typeof nj(elField[j]).Dia().opt.onKeyDown === "function" ) {
                       nj(elField[j]).on( "keydown", nj(elField[j]).Dia().opt.onKeyDown ); 
                    }
                    j += 1;
                }
                i += 1;
            }
        }
        nj( ".divImg" ).on( "click", function( e ) {
            e.stopImmediatePropagation();
            let id = this.children[0].id;
            nj( "#" + nj( "#" + id ).gRO().opt.dVar + "_tFUFile" ).atr( "accept", ".png,.jpg");
            nj( "#" + id ).gRO().divUpload.show({variables: {df: nj("#" + id).gRO(), id: id, attr: "src", uploadPath: nj( "#" + id ).Dia().opt.uploadPath, table: nj( "#" + id ).gRO().opt.table, field: nj( "#" + id ).Dia().opt.field } });

        })
    }
    getRecordValues = function ( args ) {
        let els = nj().els( "div[id=" + this.opt.id.substring( 1 ) + "] .cField" );
    }
    saveRecordset = function( df, rs, primaryKey ) {
        if( df.opt.validOnSave ) {
            let l = rs.fields.length;
            let i = 0;
            let res = {};
            while ( i < l ) {
                res = rs.fields[i].checkValidity();
                if( !res.success ) {
                    dMNew.show( {title: "Fehler", type: false, text: res.message } );
                    return;
                }
                i += 1;
            }
        }
        let tabFields = df.opt.fields.split( "," );
        let l = tabFields.length;
        let i = 0;
        while ( i < l ) {
            tabFields[i].trim()
            i += 1;
        }
        l = rs.fields.length;
        i = 0;
        let field = {}
        let fieldArray = "[";
        while ( i < l ) {
            if( tabFields.indexOf( rs.fields[i].opt.field ) > -1 ) {
                field.field = rs.fields[i].opt.field;
                field.value = rs.fields[i].getValue();
                fieldArray += JSON.stringify( field ) + ", ";                                
            }
            i += 1;
        }
        fieldArray = fieldArray.substring( 0, fieldArray.length - 2 )
        fieldArray += "]";
        data = {};
        data.dVar = df.opt.dVar;
        data.command = "saveRecordset";
        data.table = df.opt.table;
        data.primaryKey = df.opt.primaryKey;
        data.primaryKeyValue = primaryKey;
        data.fields = fieldArray;
        nj().fetchPostNew("library/php/ajax_dataform20.php", data, df.evaluateDF)        
    }
    deleteRecordset = function( df, rs, primaryKey ) {
        let orphans = [], oValues = {};
        let l = df.opt.boundForm.length;
        let i = 0;
        while ( i < l ) {
            oValues.table = window[ df.opt.boundForm[i] ].opt.table;
            oValues.field = df.opt.boundFields[i].to;            
            oValues.value = nj( "#" + df.opt.addPraefix + df.opt.boundFields[i].from + "_" + primaryKey ).v();
            orphans.push( oValues );
            i += 1;
        }
        data = {};
        data.dVar = df.opt.dVar;
        data.command = "deleteRecordset";
        data.table = df.opt.table;
        data.primaryKey = df.opt.primaryKey;
        data.primaryKeyValue = primaryKey;
        data.orphans = JSON.stringify( orphans );
        nj().fetchPostNew("library/php/ajax_dataform20.php", data, df.evaluateDF )        
    }
    init = function ( fieldDefinitions ) {
        if( typeof fieldDefinitions === "undefined" ) {
            let data = {};
            data.dVar = this.opt.dVar;
            data.command = "getFielddefinitions";
            data.table = this.opt.table;
            nj().fetchPostNew("library/php/ajax_dataform20.php", data, this.evaluateRS)        
        } else {
            // false    
        }
    }
}

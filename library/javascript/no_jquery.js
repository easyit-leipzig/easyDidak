//javascript
var nj = function ( p ) {
    if( p == "" || typeof( p ) == "undefined" ) {
        this.e = null;
        return this;
    } else if ( Node.prototype.isPrototypeOf( p ) || NodeList.prototype.isPrototypeOf( p ) || typeof( p ) == "object" ) {
        this.e = p;
    } else if( p.substr( 0, 1 ) === "#" && p.indexOf( " " ) === - 1 && p.indexOf( "," ) === - 1 && p.indexOf( "[" ) === - 1 && p.indexOf( ":" ) === - 1) {
        this.e = document.getElementById( p.substr( 1, p.length - 1 ) );    
    } else if( p.substr( 0, 1 ) === "." && p.indexOf( " " ) === - 1 && p.indexOf( "," ) === - 1 && p.indexOf( "[" ) === - 1 && p.indexOf( ":" ) === - 1) {
        this.e = document.getElementsByClassName( p.substr( 1, p.length - 1 ) );    
    } else {
        this.e = document.querySelectorAll( p );
    }
    _els = function( p ) {
        if( p === "" || typeof( p ) === "undefined" ) {
            return null;
        } else if ( Node.prototype.isPrototypeOf( p ) || NodeList.prototype.isPrototypeOf( p ) ) {
            return p;
        } else if( p.substr( 0, 1 ) === "#" && p.indexOf( " " ) === - 1 && p.indexOf( "," ) === - 1 && p.indexOf( "[" ) === - 1 && p.indexOf( ":" ) === - 1) {
            return document.getElementById( p.substr( 1, p.length - 1 ) );    
        } else if( p.substr( 0, 1 ) === "." && p.indexOf( " " ) === - 1 && p.indexOf( "," ) === - 1 && p.indexOf( "[" ) === - 1 && p.indexOf( ":" ) === - 1) {
            return document.getElementsByClassName( p.substr( 1, p.length - 1 ) );    
        } else {
            return document.querySelectorAll( p );
        }
    }
    _lEl = function( e ) {
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            return this.e;
        } else {
            return this.e[ this.e.length - 1 ];
        }
    }
    _fEl = function( e ) {
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            return this.e;
        } else {
            return this.e[ 0 ];
        }
    }
    _nEl = function( e, n ) {
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            return this.e;
        } else {
            return this.e[ n - 1 ];
        }
    }
    _bEl = function( e ) {
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            return this.e.previousElementSibling;
        } else {
            return false;
        }
    }
    _cEl = function( t ) {
        return document.createElement( t );
    }
    _htm = function( v ) {
        if( this.e === null ) return false;
        if( NodeList.prototype.isPrototypeOf( this.e ) || HTMLCollection.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i].innerHTML = v;
                i += 1;    
            }
            return;
        }
        if( typeof( v ) == "undefined" ) {
            return this.e.innerHTML;
        } else {
            this.e.innerHTML = v;
        }
    }
    _txt = function( v ) {
        if( this.e === null ) return false;
        if( NodeList.prototype.isPrototypeOf( this.e ) || HTMLCollection.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i].textContent = v;
                i += 1;    
            }
            return;
        }
        if( typeof( v ) == "undefined" ) {
            return this.e.textContent;
        } else {
            this.e.textContent = v;
        }
    }
    _oHt = function( v ) {
        if( NodeList.prototype.isPrototypeOf( this.e ) || HTMLCollection.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i].outerHTML = v;
                i += 1;    
            }
            return;
        } else {
            this.e.outerHTML = v;
        }
    }
    _oTx = function( v ) {
        if( NodeList.prototype.isPrototypeOf( this.e ) || HTMLCollection.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i].outerText = v;
                i += 1;    
            }
            return;
        } else {
            this.e.outerText = v;
        }
    }
    _v = function( v ) {
        if( this.e == null ) return false;
        if( typeof( v ) == "undefined" ) {
            return this.e.value;
        } else {
            this.e.value = v;
        }
    }
    _b = function( v ) {
        if( this.e == null ) return false;
        this.e.before( v );
    }
    _a = function( v ) {
        if( this.e == null ) return false;
        this.e.after( v );
    }
    _p = function( v ) {
        if( this.e == null ) return false;
        return this.e.parentElement;
    }
    _m = function( target ) {
        if( this.e == null || typeof target === "undefined" ) return false;
        let fragment = document.createDocumentFragment();
        fragment.appendChild( this.e );
        nj().els( target ).appendChild(fragment);
    }
    _id = function() {
        if( this.e == null ) return false;
        return this.e.getAttribute( "id" );
    }
    _tag = function( v ) {

        if( NodeList.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                if( typeof v === "undefined" ) {

                } else {
                    this.e[i].tagName = v;
                    i += 1;    

                }
            }
            return;
        } else {
                if( typeof v === "undefined" ) {
                    return this.e.tagName;        

                } else {
                    this.e.tagName = v;
                }
        }
    }
    _ds = function( v ) {
        if( NodeList.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                    return this.e[i].dataset[v];
                    i += 1;
                }
        } else {
            return this.e.dataset[v];
        }
    }
    _sDs = function( ds, v ) {
        if( NodeList.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                    this.e[i].dataset[ds] = v;
                    i += 1;
                }
            return;
        } else {
            return this.e.dataset[ds] = v;
        }
    }
    //set selected values
    _sSV = function( v, clearField ) {
        if( this.e == null ) return false;
        if( clearField || clearField == undefined ) {
            this.e.value = null;
        }
        //console.log( this.e.options );
        let tmpId = "#" + this.e.id +  " option";
        let els = nj().els( tmpId );
        let l = els.length, j;
        for (var i = 0; i < v.length; i++) {
            j = 0;
            while( j < l ) {
                if( els[j].value == v[i] ) {
                    els[j].selected = true;        
                }
                j += 1;    
            }    
        }
    return;
    }
    /* chatchpt
    _sSV = function(v, clearField) {
    if (this.e == null) return false;
    // Werte aus dem String in ein Array aufteilen
    if( typeof v == "string" ) {
        const values = v.split(',').map(s => s.trim());
    }

    // Falls clearField true oder nicht gesetzt ist → Auswahl zurücksetzen
    if (clearField || clearField === undefined) {
        for (let option of this.e.options) {
            option.selected = false;
        }
    }
    if( typeof values === "undefined" || values.length===0 ) return
    console.log(v, values)

    // Jetzt durchgehen und passende Optionen auswählen
    for (let i = 0; i < this.e.options.length; i++) {
        let option = this.e.options[i];
        if (values.includes(option.value)) {
            option.selected = true;
        }
    }

    return;
}
*/
    _aCN = function ( v ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.classList = this.e.classList + " " + v;
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].classList = this.e[ i ].classList + " " + v;
                i += 1;    
            }
        }
        return this.e.classList;            
    }
    _rCN = function ( v ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.classList = this.e.classList.replace( v, "");
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].classList = this.e[ i ].classList.replace( v, "");
                i += 1;    
            }
        }
        return this.e.classList;            
    }
    _gSV = function() {
        if( this.e == null ) return false;
        let tmpId = "#" + this.e.id;
        let select = nj().els( tmpId );

        let result = [];
        let options = select && select.options;
        let opt;

        for (var i=0, iLen=options.length; i<iLen; i++) {
            opt = options[i];
            if (opt.selected) {
              result.push(opt.value || opt.text);
            }
        }
        return result;    
    }
    _f = function( v ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.focus();
        } else {
            this.e[0].focus()
        }
    }
    _s = function( v ) {
        if( this.e == null ) return false;
        this.e.select();
    }
    _chk = function( v ) {
        if( this.e == null ) return false;
        if( typeof( v ) !== "undefined" ) {
            this.e.checked = v;
        } else {
            return this.e.checked;
        }
    }
    _sPr = function( n, v ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e[n] = v; 
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i][n] = v;
                i += 1;    
            }
        }
    }
    _rPr = function( v ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.removeAttribute( v ); 
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i].removeAttribute( v );
                i += 1;    
            }
        }
    }
    // classes, styles
    _aCl = function( c ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            let tmpCl = c.split( " " );
            if( tmpCl.length > 0 ) {
                let l = tmpCl.length;
                let i = 0;
                while( i < l ) {
                    this.e.classList.add( tmpCl[i] );
                    i += 1;    
                }
            } else {
                this.e.classList.add( c );    
            }            
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].classList.add( c );
                i += 1;    
            }
        }
    }
    _hCl = function( c ) {
        if( this.e == null ) return false;
        if( this.e.length > 0 ) {
            return this.e[0].classList.contains( c );    
        }
        return this.e.classList.contains( c );
    }
    _rCl = function( c ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            let tmpCl = c.split( " " );
            if( tmpCl.length > 0 ) {
                let l = tmpCl.length;
                let i = 0;
                while( i < l ) {
                    this.e.classList.remove( tmpCl[i] );
                    i += 1;    
                }
            } else {
                this.e.classList.remove( c );    
            }            
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].classList.remove( c );
                i += 1;    
            }
        }
    }
    _tCl = function( c ) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.classList.toggle( c );            
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].classList.toggle( c );
                i += 1;    
            }
        }
        return this.e.classList;            
    }
    _clL = function() {
        if( this.e == null ) return false;
        return this.e.classList;            
    }
    _iCl = function( args ) {
        
    }
    _sty = function( s, v ) {
        if( this.e == null ) return false;
        if( NodeList.prototype.isPrototypeOf( this.e ) || HTMLCollection.prototype.isPrototypeOf( this.e ) ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                if( typeof( s ) == "object" ) {
                    for( let p in s ) {
                        this.e[i].style[p] = s[p];
                    }
                } else {
                    this.e[i].style[s] = v;
                }
                i += 1;    
            }
            return;
        }
        if( !this.e ) return;
        if( typeof( v ) == "undefined" && typeof( s ) != "object" ) {
            return this.e.style[s];
        } else {
            if( typeof( s ) == "object" ) {
                for( let p in s ) {
                    this.e.style[p] = s[p];
                }
            } else {
                this.e.style[s] = v;
            }   
        }
    }
    _sRP = function( p ) {
        // style remove property
        if( this.e == null ) return false;
        this.e.style.removeProperty( p );
    }
    _gCS = function( e, v ) {
        if( e == null ) return false;
        return window.getComputedStyle( this.e ).getPropertyValue( v );
    }
    // dom
    _rEl = function() {
        if( this.e == null ) return false;
        //this.parentElement.removeChild(this);
        this.e.outerHTML="";
        //this.e.remove();
    }
    _nEl = function() {
        return this.e.nextElementSibling;
    }
    _prE = function() {
        return this.e.previousElementSibling;
    }
    _aCh = function( el ){
        return this.e.appendChild( el );
    }
    _pCh = function( e ){
        return this.e.prepend( e );
    }
    _app = function( v ){
        return this.e.append( v );
    }
    _atr = function( a, v ) {
        if( typeof( v ) != "undefined" ) {
            if( Node.prototype.isPrototypeOf( this.e ) ) {
                return this.e.setAttribute( a, v )
            } else {
                let l = this.e.length;
                let i = 0;
                while( i < l ) {
                    this.e[i].setAttribute( a, v );
                    i += 1;    
                }
            }
        } else {
            if( NodeList.prototype.isPrototypeOf( this.e ) ) {
                return this.e[0].getAttribute( a );    
            } else {
                return this.e.getAttribute( a );
            }
        }
    }
    _hAt = function( a ){
        if( this.e.hasAttribute( a ) ) {
            return true;
        } else {
            return false;
        }
    }
    _rAt = function( a ){
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.removeAttribute( a )
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].removeAttribute( a )
                i += 1;    
            }
        }
    }
    _isE = function(){
        return !!this.e;
    }
    _gRe = function() {
        try {
            if( this.e.length == 1 ) {
                return this.e[0].getBoundingClientRect();    
            } else {
                return this.e.getBoundingClientRect();
            }
            
        } catch (e) {
            return e;
        }
    }  
    // events
    _on = function( ev, fun) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.addEventListener( ev, fun );
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].addEventListener( ev, fun );
                i += 1;    
            }
        }
    }
    _off = function( ev, fun) {
        if( this.e == null ) return false;
        if( Node.prototype.isPrototypeOf( this.e ) ) {
            this.e.removeEventListener( ev, fun );
        } else {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[ i ].removeEventListener( ev, fun );
                i += 1;    
            }
        }
    }
    _tri = function( ev ) {
        if( NodeList.prototype.isPrototypeOf( this.e ) && this.e.length > 0 ) {
            let l = this.e.length;
            let i = 0;
            while( i < l ) {
                this.e[i].dispatchEvent( new Event( ev ) );
                i += 1;    
            }
        } else {
            this.e.dispatchEvent( new Event( ev ) );    
        }
    }
    // cookies
    _gCV = function( a ) {  // get cookie value
        const b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
        return b ? b.pop() : '';
    }
    _sCV = function( name, value, days) {
        let expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }
    // AJAX
    _post = function( url, d, cb ) {
        let hr = new XMLHttpRequest();
        hr.onreadystatechange = function(){
                if (hr.readyState === XMLHttpRequest.DONE) {
                    if (hr.status === 200) {
                        cb( hr.responseText );
                    } else {
                        console.log('Auf den Server kann (derzeit) nicht zugegriffen werden.');
                    }
                }
        }
        hr.open('POST', url, true);
        let f = new FormData();
        for (let p in d) {
            f.append( p, d[p] )
        }
        hr.send(f);
    }
    /* beispiel


const url = 'https://randomuser.me/api';

let data = {
  name: 'Sara'
}

var request = new Request(url, {
    method: 'POST',
    body: data,
    headers: new Headers()
});

fetch(request)
.then(function() {
    // Handle response we get from the API
})




    */
    _fetchPost = function( url, d, cb ) {
        console.log( JSON.stringify( d ) );
        fetch( url, {
            method: 'POST',
            headers: {
            //'Accept': 'application/json, text/plain, */*',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify( d )
        })
        .then(res =>{ console.log( res );res.json()} )
        .then(res => {
                cb(res);
            })
        .catch(error => {
            console.log( error )
        });
    }
    _fetchPostNew = function( url, fetchData, callback ) {
        const requestOptions = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify( fetchData )
        };
        fetch(url, requestOptions)
            .then(response => response.json())
            .then(data => callback( data ) );
    }
    // others
    _ddS = function( k, v ) {
        document.documentElement.style.setProperty( k, v );
    }
    _ddG = function( k ) {
        return document.documentElement.style.getPropertyValue( k );
    }
    _gBr = function() {
        if( window.navigator.userAgent.indexOf("Edg/") > -1 && window.navigator.userAgent.indexOf("Edge/") === -1 ) return "ChromiumEdge"; // for new edge chromium
        if( window.navigator.userAgent.indexOf("Chrome") > -1 ) return "Chrome";
        if( window.navigator.userAgent.indexOf("MSIE ") > -1 || !!navigator.userAgent.match(/Trident.*rv\:11\./) ) return "MSIE";
        if( window.navigator.userAgent.indexOf("Edge/") > -1 ) return "Edge";
        if( window.navigator.userAgent.indexOf("Safari") > -1 && window.navigator.userAgent.indexOf('Chrome') == -1 ) return "Safari";
        if( window.navigator.userAgent.indexOf("Firefox") > -1 ) return "Firefox";
        //if( window.navigator.userAgent.indexOf("Edg/") > -1 && window.navigator.userAgent.indexOf("Edge/") === -1 ) return "ChromiumEdge"; // for new edge chromium
    }
    // check equal
    _cEq = function(a, b) {
           var aProps = Object.getOwnPropertyNames(a);
           var bProps = Object.getOwnPropertyNames(b);
           if (aProps.length != bProps.length) {
              return false;
           }
           for (var i = 0; i < aProps.length; i++) {
              var propName = aProps[i];
              if (a[propName] !== b[propName]) {
                 return false;
              }
           }
           return true;
        }

    _exC = function( code ) {
        const myFunction = new Function(code);
        myFunction();
    }    

    // objects
    _oEx = function(a, b) {
        for( let p in b ) {
            //console.log( p, object[p] );
            a[p] = b[p]
        }
        return a;
    }
    // JSON
    _isJ = function (item) {
        item = typeof item !== "string"
            ? JSON.stringify(item)
            : item;

        try {
            item = JSON.parse(item);
        } catch (e) {
            return false;
        }

        if (typeof item === "object" && item !== null) {
            return true;
        }

        return false;
    }
    // arrays
    _rAE = function( a, v ) { // remove array element
        let l = a.length;
        let i = 0;
        while( i < l ) {
            if( typeof( v ) == "object" ) {
                if( nj().cEq( a[i], v ) ) break;
            } else {
                if( a[i] == v ) break;
            }
            i += 1;    
        }        
        a.splice ( i, 1);        
        return a;
    }
    //iterating over an array
    _fEa = function( arr, cb ) {
        arr.forEach(
        function (elem, index) {  // (1)
            cb(index, elem);
        });    
    }
    // filter object arra
    _fOA = function ( arr, field, value ) {
        // content
        return arr.filter(fieldValue => {
                        return fieldValue[field] === value;
                    });
    }
    // elements
    // dialog
    _gDV = function( ds = "dvar" ) {
        let x = 1;
        while (x == 1 ) {
            if( this.e.dataset[ ds ] ) {
                x = 0;
                return this.e.dataset[ ds ];
            } else {
                this.e = this.e.parentNode;
            }
        }
        return false;
    }
    _Dia = function( ds = "dvar", deep ) {
        let dia = false, x = 1;
        while (x == 1 ) {
            if( this.e.dataset[ ds ] ) {
                x = 0;
                dia = this.e.dataset[ ds ];
            } else {
                this.e = this.e.parentNode;
            }
        }
        if( !dia ) {
            return false;    
        } else {
            let tmp = dia.split( "." );
            let m = tmp.length;
            if( typeof deep !== "undefined" ) m = deep;
            let j = 1;
            let tmpVar = window[ tmp[0] ];
            while( j < m ) {
                tmpVar = tmpVar[tmp[j]];
                j += 1;
            }
            return tmpVar;
        }        
    }
    _gRO = function( ds = "dvar" ) {
        let dia = false, x = 1;
        while (x == 1 ) {
            if( this.e.dataset[ ds ] ) {
                x = 0;
                dia = this.e.dataset[ ds ];
            } else {
                this.e = this.e.parentNode;
            }
        }
        return window[ dia.split(".")[0] ];
    }
    _bDV = function( dvar ) {
        let tmp = dvar.split( "." );
        let m = tmp.length;
        let j = 1;
        let tmpVar = window[ tmp[0] ];
        while( j < m ) {
            tmpVar = tmpVar[tmp[j]];
            j += 1;
        }
        return tmpVar;    
    }
    this.els = _els;
    this.lEl = _lEl;
    this.fEl = _fEl;
    this.nEl = _nEl;
    this.bEl = _bEl;
    this.cEl = _cEl;
    this.htm = _htm;
    this.oHt = _oHt;
    this.oTx = _oTx;
    this.txt = _txt;
    this.sSV = _sSV;
    this.aCN = _aCN;
    this.rCN = _rCN;
    this.gSV = _gSV;
    this.v = _v;
    this.s = _s;
    this.f = _f;
    this.p = _p;
    this.b = _b;
    this.a = _a;
    this.m = _m;
    this.id = _id;
    this.chk = _chk;
    this.sPr = _sPr;
    this.rPr = _rPr;
    this.tag = _tag;
    this.ds = _ds;
    this.sDs = _sDs;

    // classes
    this.aCl = _aCl;
    this.hCl = _hCl;
    this.rCl = _rCl;
    this.tCl = _tCl;
    this.clL = _clL;
    this.sty = _sty;
    this.sRP = _sRP;
    this.gCS = _gCS;
    this.gRe = _gRe;
    // dom
    this.rEl = _rEl;
    this.nEl = _nEl;
    this.prE = _prE;
//    this.pEl = _pEl;
    this.aCh = _aCh;
    this.pCh = _pCh;
    this.app = _app;
    this.atr = _atr;
    this.hAt = _hAt;
    this.rAt = _rAt;
    this.isE = _isE;
    // events
    this.on = _on;
    this.off = _off;
    this.tri = _tri;
    // cookies
    this.sCV = _sCV;
    this.gCV = _gCV;
    // AJAX
    this.post = _post;
    this.fetchPost = _fetchPost;
    this.fetchPostNew = _fetchPostNew;
    // others
    this.ddS = _ddS;
    this.ddG = _ddG;
    this.cEq = _cEq;
    this.gBr = _gBr;
    this.exC = _exC; 
    // objects
    this.oEx = _oEx;
    this.isJ = _isJ;
    // arrays
    this.rAE = _rAE;
    this.fEa = _fEa;
    this.fOA = _fOA;
    //
    // dialog
    this.gDV = _gDV;
    this.Dia = _Dia;
    this.gRO = _gRO;
    this.bDV = _bDV;
    return this
}
nj( document );
/* eslint-disable no-console */

var rebaseServer, docNamespaces, lastAuthorForDoc, artificialDelay,
	port = 8081,
	express = require( 'express' ),
	app = express(),
	url = require( 'url' ),
	http = require( 'http' ).Server( app ),
	io = require( 'socket.io' )( http ),
	ve = require( '../dist/ve-rebaser.js' );

function summarize( author, backtrack, change ) {
	var storeCount = 0,
		summary = [];
	summary.push( 'author=' + author );
	if ( backtrack ) {
		summary.push( 'backtrack ' + backtrack );
	}
	if ( change.getLength() ) {
		summary.push( change.getLength() + ' transactions (start ' + change.start + ')' );
	}
	console.log( 'change=', change );
	change.stores.forEach( function ( store ) {
		storeCount += store.getLength();
	} );
	if ( storeCount > 0 ) {
		summary.push( storeCount + ' stored values' );
	}
	Object.keys( change.selections ).forEach( function ( author ) {
		summary.push( 'selection ' + author + ':' + change.selections[ author ].getDescription() );
	} );
	return summary.join( ', ' );
}

rebaseServer = new ve.dm.RebaseServer();
docNamespaces = new Map();
lastAuthorForDoc = new Map();
artificialDelay = parseInt( process.argv[ 2 ] ) || 0;

function makeConnectionHandler( docName ) {
	return function handleConnection( socket ) {
		var history = rebaseServer.getDocState( docName ).history,
			author = 1 + ( lastAuthorForDoc.get( docName ) || 0 );
		lastAuthorForDoc.set( docName, author );
		console.log( 'new client ' + author + ' for ' + docName );
		socket.emit( 'registered', author );
		// HACK Catch the client up on the current state by sending it the entire history
		// Ideally we'd be able to initialize the client using HTML, but that's hard, see
		// comments in the /raw handler. Keeping an updated linmod on the server could be
		// feasible if TransactionProcessor was modified to have a "don't sync, just apply"
		// mode and ve.dm.Document was faked with { data: ..., metadata: ..., store: ... }
		console.log( 'Sending full history: ' + summarize( null, 0, history ) );
		socket.emit( 'newChange', history.serialize( true ) );
		socket.on( 'submitChange', setTimeout.bind( null, function ( data ) {
			var change, applied;
			try {
				change = ve.dm.Change.static.deserialize( data.change, null, true );
				console.log( 'receive ' + summarize( author, data.backtrack, change ) );
				applied = rebaseServer.applyChange( docName, author, data.backtrack, change );
				if ( !applied.isEmpty() ) {
					console.log( 'applied ' + summarize( author, 0, applied ) );
					docNamespaces.get( docName ).emit( 'newChange', applied.serialize( true ) );
				}
				if ( applied.getLength() < change.getLength() ) {
					console.log( author + ' rejected ' + ( applied.getLength() - change.getLength() ) );
				}
			} catch ( error ) {
				console.error( error.stack );
			}
		}, artificialDelay ) );
		socket.on( 'disconnect', function () {
			var change = rebaseServer.applyUnselect( docName, author );
			docNamespaces.get( docName ).emit( 'newChange', change.serialize( true ) );
			console.log( 'disconnect', author, docName );
		} );
	};
}

app.use( express.static( __dirname + '/..' ) );
app.set( 'view engine', 'ejs' );

app.get( '/', function ( req, res ) {
	res.render( 'index' );
} );

app.get( '/doc/edit/:docName', function ( req, res ) {
	var docName = req.params.docName;
	res.render( 'editor', { docName: docName } );
} );

app.get( '/doc/raw/:docName', function ( req, res ) {
	// TODO return real data
	// In order to provide HTML here, we'd need all of ve.dm (Document, Converter, all nodes)
	// and none of that code is likely to work in nodejs without some work because of how heavily
	// it uses the DOM.
	res.status( 401 ).send( 'DOM in nodejs is hard' );
} );

io.on( 'connection', function ( socket ) {
	var nsp,
		docName = url.parse( socket.handshake.url, true ).query.docName;

	if ( docName && !docNamespaces.has( docName ) ) {
		nsp = io.of( '/' + docName );
		docNamespaces.set( docName, nsp );
		nsp.on( 'connection', makeConnectionHandler( docName ) );
	}
} );

http.listen( port );
console.log( 'Listening on ' + port + ' (artificial delay ' + artificialDelay + ' ms)' );

/*!
 * VisualEditor ContentEditable MWInlineImageNode class.
 *
 * @copyright 2011-2017 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki image node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.MWImageNode
 *
 * @constructor
 * @param {ve.dm.MWInlineImageNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWInlineImageNode = function VeCeMWInlineImageNode() {
	var isError, $image;
	// Parent constructor
	ve.ce.MWInlineImageNode.super.apply( this, arguments );

	isError = this.model.getAttribute( 'isError' );

	if ( isError ) {
		this.$element = $( '<a>' )
			.addClass( 'new' )
			.text( this.model.getFilename() );
		$image = $( [] );
	} else {
		if ( this.model.getAttribute( 'isLinked' ) ) {
			this.$element = $( '<a>' ).addClass( 'image' );
			$image = $( '<img>' ).appendTo( this.$element );
		} else {
			this.$element = $image = $( '<img>' ).appendTo( this.$element );
		}
	}

	// Mixin constructors
	ve.ce.MWImageNode.call( this, this.$element, $image );

	$image
		.attr( 'src', this.getResolvedAttribute( 'src' ) )
		.attr( 'width', this.model.getAttribute( 'width' ) )
		.attr( 'height', this.model.getAttribute( 'height' ) );

	if ( this.$element.css( 'direction' ) === 'rtl' ) {
		this.showHandles( [ 'sw' ] );
	} else {
		this.showHandles( [ 'se' ] );
	}

	this.updateClasses();

	// DOM changes
	this.$element.addClass( 've-ce-mwInlineImageNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWInlineImageNode, ve.ce.LeafNode );

// Need to mixin base class as well (T92540)
OO.mixinClass( ve.ce.MWInlineImageNode, ve.ce.GeneratedContentNode );

OO.mixinClass( ve.ce.MWInlineImageNode, ve.ce.MWImageNode );

/* Static Properties */

ve.ce.MWInlineImageNode.static.name = 'mwInlineImage';

/* Methods */

/**
 * Update CSS classes based on current attributes
 *
 */
ve.ce.MWInlineImageNode.prototype.updateClasses = function () {
	var valign = this.model.getAttribute( 'valign' );

	// Border
	this.$element.toggleClass( 'mw-image-border', !!this.model.getAttribute( 'borderImage' ) );

	// default size
	this.$element.toggleClass( 'mw-default-size', !!this.model.getAttribute( 'defaultSize' ) );

	// valign
	if ( valign !== 'default' ) {
		this.$image.css( 'vertical-align', valign );
	}
};

/**
 * @inheritdoc
 */
ve.ce.MWInlineImageNode.prototype.onAttributeChange = function ( key, from, to ) {
	// Mixin method
	ve.ce.MWImageNode.prototype.onAttributeChange.apply( this, arguments );

	if ( key === 'height' || key === 'width' ) {
		to = parseInt( to, 10 );
	}

	if ( from !== to ) {
		switch ( key ) {
			// TODO: 'align', 'src', 'valign', 'border'
			case 'width':
				this.$image.css( 'width', to );
				break;
			case 'height':
				this.$image.css( 'height', to );
				break;
		}
		this.updateClasses();
	}
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWInlineImageNode );

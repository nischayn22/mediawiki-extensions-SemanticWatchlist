/**
 * JavaScript for Special:WatchlistConditions in the Semantic Watchlist extension.
 * @see http://www.mediawiki.org/wiki/Extension:Semantic_Watchlist
 *
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 */

(function( $, mw ){ $.fn.watchlistcondition = function( group, options ) {

	var self = this;
	this.group = group;

	this.buildHtml = function() {
		this.html( $( '<legend />' ).text( group.name ) );

		var table = $( '<table />' ).attr( { 'class': 'swltable' } );

		this.nameInput = $( '<input />' ).attr( {
			'type': 'text',
			'value': group.name,
			'size': 30
		} );

		this.nameInput.keyup( function() {
			self.find( 'legend' ).text( $( this ).val() );
			self.attr( 'groupname', $( this ).val() );
		} );

		var name = $( '<p />' ).text( mw.msg( 'swl-group-name' ) + ' ' ).append( this.nameInput );
		table.append( name );


		this.propertiesInput = $( '<input />' ).attr( {
			'type': 'text',
			'value': group.properties.join('|'),
			'size': 30
		} );

		this.propertiesInput.keyup( function() {
			self.attr( 'properties', $( this ).val() );
		} );

		var property = $( '<p />' ).text( mw.msg( 'swl-group-properties' ) + ' ' ).append( this.propertiesInput );
		table.append( property );

		var conditionValue, conditionType;

		switch ( true ) {
			case group.categories.length > 0:
				conditionValue = group.categories[0];
				conditionType = 'category';
				break;
			case group.namespaces.length > 0:
				conditionValue = group.namespaces[0];
				conditionType = 'namespace';
				break;
			case group.concepts.length > 0:
				conditionValue = group.concepts[0];
				conditionType = 'concept';
				break;
		}

		this.conditionTypeInput = $( '<select />' );
		var conditionTypes = [ 'category', 'namespace', 'concept' ];
		var conditionTypeGroups = [ 'categories', 'namespaces', 'concepts' ];

		for ( i in conditionTypes ) {
			var optionElement = $( '<option />' )
				.text( mw.msg( 'swl-group-' + conditionTypes[i] ) )
				.attr( { 'value': conditionTypes[i], 'type': conditionTypeGroups[i] } );

			if ( conditionType == conditionTypes[i] ) {
				optionElement.attr( 'selected', 'selected' );
			}

			this.conditionTypeInput.append( optionElement );
		}

		this.conditionNameInput = $( '<input />' ).attr( {
			'type': 'text',
			'value': conditionValue,
			'size': 30
		} );

		var condition = $( '<p />' ).text( mw.msg( 'swl-group-page-selection' ) + ' ' )
			.append( this.conditionTypeInput )
			.append( '&nbsp;' )
			.append( this.conditionNameInput )

		table.append( condition );
		table.append( $( '<div/>' ).attr({ 'class' : 'customTexts' }) );

		this.addCustomTextDiv = function( customText ) {
			var customTextFieldset = $( '<fieldset/>' ).attr({ 'class' : 'customText' });

			var propertyInput = '<input type="text" size:10 id="propertyInput" value="'+customText[0]+'" > </input>';
			var newValueInput = '<input type="text" size:10 id="customTextInput" value="'+customText[1]+'" > </input>';
			var customTextInput = '<input type="text" size:100 id="customTextInput" value="'+customText[2]+'" > </input>';

			var removeButton = $( '<input />' ).attr( {
				'type': 'button',
				'value': mw.msg( 'swl-custom-remove-property' )
			} ).click( function() {
				var container = $(this).closest( $( '.customText' ) );
				container.slideUp( 'fast', function() { container.remove(); } );
			} );

			var customTextTable = $( '<table/>' );
			customTextTable.append( $( '<tr/>' ).
				append( $( '<td/>' ).html( mw.msg( 'swl-custom-input', propertyInput, newValueInput, customTextInput ))).
				append( $( '<td/>' ).append( removeButton ) ));

			customTextFieldset.append( customTextTable );
			table.find('.customTexts').append( customTextFieldset );
		}

		for ( i in group.customTexts ) {
			self.addCustomTextDiv( group.customTexts[i].split( '~' ) );
		}

		var addCustomTextButton = $( '<input />' ).attr( {
			'type': 'button',
			'value': mw.msg( 'swl-custom-text-add' )
		} ).click( function() {
			self.addCustomTextDiv( new Array( '', '', '' ) )
		} );

		table.append( addCustomTextButton );
		this.append( table );

		this.append(
			$( '<input />' ).attr( {
				'type': 'button',
				'value': mw.msg( 'swl-group-delete' )
			} ).click( function() {
				if ( confirm( mw.msg( 'swl-group-confirmdelete', self.nameInput.val() ) ) ) {
					this.disabled = true;
					var button = this;

					self.doDelete( function( success ) {
						if ( success ) {
							self.slideUp( 'fast', function() { self.remove(); } );
						}
						else {
							alert( 'Could not delete the watchlist group.' );
							button.disabled = false;
						}
					} );
				}
			} )
		);
	};

	this.doSave = function( callback ) {
		var customTexts = new Array();
		self.find( '.customText' ).each( function( index, element ) {
			element = $( element );
			customTexts.push( element.find( '#propertyInput' ).val() + '~' + element.find( '#newValueInput' ).val() + '~' + element.find( '#customTextInput' ).val() );
		} );
		var args = {
			'action': ( this.group.id == '' ? 'addswlgroup' : 'editswlgroup' ),
			'format': 'json',
			'id': this.group.id,
			'name': self.attr( 'groupname' ),
			'properties': self.attr( 'properties' ),
			'customTexts' : customTexts.join( '|' )
		};
		this.conditionTypeInput = $( '<select />' );
		this.conditionNameInput = $( '<input />' );
		args[this.conditionTypeInput.find( 'option:selected' ).attr( 'type' )] = this.conditionNameInput.val();
	 	$.getJSON(
			wgScriptPath + '/api.php',
			args,
			function( data ) {
				callback( data.success );
			}
		);
	};

	this.doDelete = function( callback ) {
		$.getJSON(
			wgScriptPath + '/api.php',
			{
				'action': 'deleteswlgroup',
				'format': 'json',
				'ids': this.group.id
			},
			function( data ) {
				callback( data.success );
			}
		);
	};

	return this;

}; })( jQuery, mediaWiki );

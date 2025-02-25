( function () {
	'use strict';

	/**
	 * Fired after an edit was successfully saved.
	 *
	 * Does not fire for null edits.
	 *
	 * @event postEdit
	 * @member mw.hook
	 * @param {Object} [data] Optional data
	 * @param {string|jQuery|Array} [data.message] Message that listeners
	 *  should use when displaying notifications. String for plain text,
	 *  use array or jQuery object to pass actual nodes.
	 * @param {string|mw.user} [data.user=mw.user] User that made the edit.
	 * @param {boolean} [data.tempUserCreated] Whether a temporary user account
	 *  was created.
	 */

	/**
	 * After the listener for #postEdit removes the notification.
	 *
	 * @deprecated
	 * @event postEdit_afterRemoval
	 * @member mw.hook
	 */

	var postEdit = mw.config.get( 'wgPostEdit' );

	var config = require( './config.json' );
	var contLangMessages = require( './contLangMessages.json' );

	function showTempUserPopup() {
		var title = mw.message( 'postedit-temp-created-label' ).text();
		var $content = mw.message(
			'postedit-temp-created',
			mw.util.getUrl( 'Special:CreateAccount' ),
			contLangMessages[ 'tempuser-helppage' ]
		).parseDom();

		var $usernameLink = $( '.mw-userpage-tmp' );
		if ( $usernameLink.length ) {
			// If supported by the skin, display a popup anchored to the username
			var popup = new OO.ui.PopupWidget( {
				padded: true,
				head: true,
				label: title,
				$content: $content,
				$floatableContainer: $usernameLink,
				classes: [ 'postedit-tempuserpopup' ],
				// Work around T307062
				position: 'below',
				autoFlip: false
			} );
			$( document.body ).append( popup.$element );
			popup.toggle( true );
		} else {
			// Otherwise display a mw.notify message
			mw.notify( $content, {
				title: title,
				classes: [ 'postedit-tempuserpopup' ],
				autoHide: false
			} );
		}
	}

	function showConfirmation( data ) {
		var label;

		data = data || {};

		label = data.message || new OO.ui.HtmlSnippet( mw.message(
			config.EditSubmitButtonLabelPublish ?
				'postedit-confirmation-published' :
				'postedit-confirmation-saved',
			data.user || mw.user
		).escaped() );

		data.message = new OO.ui.MessageWidget( {
			type: 'success',
			inline: true,
			label: label
		} ).$element[ 0 ];

		mw.notify( data.message, {
			classes: [ 'postedit' ]
		} );

		// Deprecated - use the 'postEdit' hook, and an additional pause if required
		mw.hook( 'postEdit.afterRemoval' ).fire();

		if ( data.tempUserCreated ) {
			showTempUserPopup();
		}
	}

	// JS-only flag that allows another module providing a hook handler to suppress the default one.
	if ( !mw.config.get( 'wgPostEditConfirmationDisabled' ) ) {
		mw.hook( 'postEdit' ).add( showConfirmation );
	}

	if ( postEdit ) {
		var action = postEdit;
		var tempUserCreated = false;
		var plusPos = action.indexOf( '+' );
		if ( plusPos > -1 ) {
			action = action.slice( 0, plusPos );
			tempUserCreated = true;
		}
		if ( action === 'saved' && config.EditSubmitButtonLabelPublish ) {
			action = 'published';
		}
		mw.hook( 'postEdit' ).fire( {
			// The following messages can be used here:
			// * postedit-confirmation-published
			// * postedit-confirmation-saved
			// * postedit-confirmation-created
			// * postedit-confirmation-restored
			message: mw.msg(
				'postedit-confirmation-' + action,
				mw.user
			),
			tempUserCreated: tempUserCreated
		} );
	}

}() );

( function () {
	var blocksRegistry = window.wc && window.wc.wcBlocksRegistry;
	var wcSettings = window.wc && window.wc.wcSettings;
	var wpElement = window.wp && window.wp.element;
	var htmlEntities = window.wp && window.wp.htmlEntities;
	var i18n = window.wp && window.wp.i18n;

	if (
		! blocksRegistry ||
		! blocksRegistry.registerPaymentMethod ||
		! wcSettings ||
		! wcSettings.getPaymentMethodData ||
		! wpElement ||
		! wpElement.createElement
	) {
		return;
	}

	var settings = wcSettings.getPaymentMethodData( 'woo_mono_int', {} );
	var decodeEntities = htmlEntities && htmlEntities.decodeEntities ? htmlEntities.decodeEntities : function ( value ) {
		return value;
	};
	var defaultTitle = i18n && i18n.__ ? i18n.__( 'monobank', 'woo-mono-int' ) : 'monobank';
	var title = decodeEntities( settings.title || defaultTitle );
	var description = decodeEntities( settings.description || '' );
	var createElement = wpElement.createElement;

	var Label = function ( props ) {
		return createElement( props.components.PaymentMethodLabel, {
			text: title,
		} );
	};

	var Content = function () {
		if ( ! description ) {
			return null;
		}

		return createElement(
			'div',
			{
				className: 'woo-mono-int-blocks-description',
			},
			description
		);
	};

	blocksRegistry.registerPaymentMethod( {
		name: 'woo_mono_int',
		label: createElement( Label, null ),
		content: createElement( Content, null ),
		edit: createElement( Content, null ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: title,
		supports: {
			features: settings.supports || [ 'products' ],
		},
	} );
} )();

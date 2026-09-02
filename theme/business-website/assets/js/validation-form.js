( function () {
	var phoneInput = document.querySelector( '#contact_phone' );
	var nameInput = document.querySelector( '#contact_name' );
	var operatorCodes = [ '39', '50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99' ];

	if ( nameInput ) {
		nameInput.addEventListener( 'input', function () {
			this.value = this.value.replace( /[^\p{L}\s'-]/gu, '' );
		} );
	}

	if ( ! phoneInput ) {
		return;
	}

	var getLocalPhoneDigits = function ( value ) {
		var digits = value.replace( /\D/g, '' );

		if ( digits.indexOf( '380' ) === 0 ) {
			digits = digits.slice( 3 );
		} else if ( digits.indexOf( '80' ) === 0 ) {
			digits = digits.slice( 2 );
		} else if ( digits.indexOf( '0' ) === 0 ) {
			digits = digits.slice( 1 );
		}

		return digits.slice( 0, 9 );
	};

	var formatPhone = function ( value ) {
		var digits = getLocalPhoneDigits( value );

		var code = digits.slice( 0, 2 );
		var partOne = digits.slice( 2, 5 );
		var partTwo = digits.slice( 5, 7 );
		var partThree = digits.slice( 7, 9 );
		var result = '+380';

		if ( code ) {
			result += ' (' + code;
		}

		if ( code.length === 2 ) {
			result += ')';
		}

		if ( partOne ) {
			result += ' ' + partOne;
		}

		if ( partTwo ) {
			result += '-' + partTwo;
		}

		if ( partThree ) {
			result += '-' + partThree;
		}

		return result;
	};

	var countLocalDigitsBeforePosition = function ( value, position ) {
		return getLocalPhoneDigits( value.slice( 0, position ) ).length;
	};

	var findPositionAfterLocalDigits = function ( value, digitCount ) {
		var position = 0;
		var passed = 0;

		if ( digitCount <= 0 ) {
			return value.length;
		}

		while ( position < value.length ) {
			if ( /\d/.test( value.charAt( position ) ) ) {
				passed++;
			}

			position++;

			if ( passed >= digitCount + 3 ) {
				return position;
			}
		}

		return value.length;
	};

	var validatePhone = function () {
		var digits = phoneInput.value.replace( /\D/g, '' );
		var code = digits.indexOf( '380' ) === 0 ? digits.slice( 3, 5 ) : '';

		if ( phoneInput.value && ( digits.length !== 12 || operatorCodes.indexOf( code ) === -1 ) ) {
			phoneInput.setCustomValidity( 'Enter a valid Ukrainian mobile phone number.' );
			return;
		}

		phoneInput.setCustomValidity( '' );
	};

	phoneInput.addEventListener( 'keydown', function ( event ) {
		var start = this.selectionStart;
		var end = this.selectionEnd;
		var value = this.value;

		if ( event.key !== 'Backspace' || start !== end || start <= 0 ) {
			return;
		}

		if ( /\d/.test( value.charAt( start - 1 ) ) ) {
			return;
		}

		var digits = getLocalPhoneDigits( value );
		var digitsBeforeCaret = countLocalDigitsBeforePosition( value, start );
		var removeIndex = digitsBeforeCaret - 1;

		if ( removeIndex < 0 ) {
			return;
		}

		event.preventDefault();

		digits = digits.slice( 0, removeIndex ) + digits.slice( removeIndex + 1 );
		this.value = formatPhone( digits );
		this.setSelectionRange(
			findPositionAfterLocalDigits( this.value, removeIndex ),
			findPositionAfterLocalDigits( this.value, removeIndex )
		);
		validatePhone();
	} );

	phoneInput.addEventListener( 'input', function () {
		this.value = formatPhone( this.value );
		validatePhone();
	} );

	phoneInput.addEventListener( 'blur', validatePhone );
} )();

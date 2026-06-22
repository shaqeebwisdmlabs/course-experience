( function () {
	'use strict';

	var config = window.courseexpMoodleSession;
	if ( ! config || ! config.ajaxUrl || ! config.nonce ) {
		return;
	}

	var url = config.ajaxUrl +
		'?action=courseexp_warm_moodle_session&nonce=' +
		encodeURIComponent( config.nonce );

	fetch( url, { credentials: 'include' } ).catch( function () {} );
} )();

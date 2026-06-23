( function () {
	'use strict';

	function makeButton( href, label, modifier ) {
		var link = document.createElement( 'a' );
		link.href = href;
		link.className = 'courseexp-cta courseexp-cta--' + modifier;
		link.textContent = label;
		link.addEventListener( 'click', function ( event ) {
			event.stopPropagation();
		} );
		return link;
	}

	function enhanceCard( cardId, urls, i18n ) {
		var card = document.getElementById( cardId );
		if ( ! card ) {
			return;
		}

		var grid = card.querySelector( '.wdm-course-grid' );
		var caption = grid ? grid.querySelector( '.wdm-caption' ) : null;
		if ( ! caption || caption.querySelector( '.courseexp-cta-group' ) ) {
			return;
		}

		var wrapper = grid.querySelector( 'a.wdm-course-thumbnail' );
		if ( wrapper ) {
			wrapper.removeAttribute( 'href' );
			wrapper.classList.add( 'courseexp-is-inert' );
		}

		var group = document.createElement( 'div' );
		group.className = 'courseexp-cta-group';
		group.appendChild( makeButton( urls.student_url, i18n.student, 'student' ) );
		group.appendChild( makeButton( urls.instructor_url, i18n.instructor, 'instructor' ) );
		caption.appendChild( group );
		card.classList.add( 'courseexp-has-cta' );
	}

	function init() {
		var data = window.courseexpMyCourses;
		if ( ! data || ! data.cards || ! data.i18n ) {
			return;
		}

		Object.keys( data.cards ).forEach( function ( cardId ) {
			enhanceCard( cardId, data.cards[ cardId ], data.i18n );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

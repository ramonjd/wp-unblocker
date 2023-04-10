(function( $, win ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$( function() {

		const registerPlugin = wp.plugins.registerPlugin;
		const PluginSidebar = wp.editPost.PluginSidebar;
		const el = wp.element.createElement;
		const Text = wp.components.TextControl;
		const useSelect = wp.data.useSelect;

		const MetaBlockField = function () {
			const { status, depth, parent_block_name, previous_block_name, next_block_name } = useSelect( function ( select ) {
				const selectedBlock = select( 'core/block-editor' ).getSelectedBlock();
				const blockRootClientId = select( 'core/block-editor' ).getBlockRootClientId();
				const rootId = select( 'core/block-editor' ).getBlockHierarchyRootClientId();
				const blockIndex = select( 'core/block-editor' ).getBlockIndex;
				const previousBlockId = select( 'core/block-editor' ).getPreviousBlockClientId;
				const nextBlockId = select( 'core/block-editor' ).getNextBlockClientId;
				const getBlock = select( 'core/block-editor' ).getBlock;
				const parents = select( 'core/block-editor' ).getBlockParents;

				const result = {
					status: 0,
				};

				if ( selectedBlock && selectedBlock.clientId ) {
					const parentsList = parents( selectedBlock.clientId );
					console.log( 'selectedBlock', selectedBlock.name );
					result.depth = parentsList.length;
					result.parent_block_name = getBlock( parentsList[ parentsList.length ? parentsList.length - 1 : 0 ] )?.name || 'None';
					result.previous_block_name = getBlock( previousBlockId( selectedBlock.clientId ) )?.name || 'None';
					result.next_block_name = getBlock( nextBlockId( selectedBlock.clientId ) )?.name || 'None';
					result.status = 1;
				}
				return result;
			}, [] );

			const [ list, setList ] = wp.element.useState({});

			wp.element.useEffect( function () {
				if ( status === 1 ) {
					const queryParams = {
						depth,
						parent_block_name,
						previous_block_name,
						next_block_name,
					};

					wp.apiFetch( {
						path: wp.url.addQueryArgs( '/wp-unblocker/v1/unblock', queryParams ),
					} ).then( ( result ) => {
						console.log( result );

					} );
				}

			}, [ status, depth, parent_block_name, previous_block_name, next_block_name ] );









			return el( Text, {
				label: 'Meta Block Field',
				value: '',
				onChange: function ( content ) {
					console.log( 'content has changed to ', content );
				},
			} );
		};

		registerPlugin( 'my-plugin-sidebar', {
			render: function () {
				return el(
					PluginSidebar,
					{
						name: 'my-plugin-sidebar',
						icon: 'admin-post',
						title: 'My plugin sidebar',
					},
					el(
						'div',
						{ className: 'plugin-sidebar-content' },
						el( MetaBlockField )
					)
				);
			},
		} );

	} )

})( jQuery, window );

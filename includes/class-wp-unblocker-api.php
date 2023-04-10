<?php

/**
 * Custom API Endpoint Class
 */
class Wp_Unblocker_API extends WP_REST_Controller {
    protected $wp_train;

    public function __construct() {
        $this->wp_train = new Wp_Unblocker_Train();
        $this->wp_train->train();

    }

    /**
     * Register custom API endpoint
     */
    public function register_api()
    {
        add_action('rest_api_init', function () {
            register_rest_route('wp-unblocker/v1', '/unblock', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'unblock_callback' ),
                'args'                => array(
                    'depth'            => array(
                        'description' => __( 'How deep a block is in the tree', 'gutenberg' ),
                        'type'        => 'integer',
                    ),
                    'parent_block_name'       => array(
                        'description' => __( 'Parent block name', 'gutenberg' ),
                        'type'        => 'string',
                    ),
                    'previous_block_name'       => array(
                        'description' => __( 'Previous block name', 'gutenberg' ),
                        'type'        => 'string',
                    ),
                    'next_block_name'       => array(
                        'description' => __( 'Next block name', 'gutenberg' ),
                        'type'        => 'string',
                    ),
                ),
            ));
        });
    }

    /**
     * Custom endpoint callback function
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response $response
     */
    public function unblock_callback( WP_REST_Request $request ) {
        $response = array(
            'message' => 'Custom API endpoint GET request successful',
            'data' => array(),
        );
        $request['depth'] = $request['depth'] ? (int) $request['depth'] : 0;
        $request['parent_block_name'] = $request['parent_block_name'] ? $request['parent_block_name'] : 'None';
        $request['previous_block_name'] = $request['previous_block_name'] ? $request['previous_block_name'] : 'None';
        $request['next_block_name'] = $request['next_block_name'] ? $request['next_block_name'] : 'None';

        $response = $this->wp_train->predict(
            array(
                array( $request['depth'], $request['parent_block_name'], $request['previous_block_name'], $request['next_block_name'] ),

            )
        );

        return new WP_REST_Response( $response, 200) ;
    }
}


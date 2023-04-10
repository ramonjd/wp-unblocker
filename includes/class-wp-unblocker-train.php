<?php
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use Rubix\ML\Transformers\OneHotEncoder;
use Rubix\ML\Classifiers\ClassificationTree;
use Rubix\ML\Classifiers\RandomForest;

class Wp_Unblocker_Train {
    /**
     * An array of block patterns
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $patterns An array of block patterns
     */
    protected $patterns;
    protected $samples;
    protected $labels;
    protected $estimator;

    public function __construct() {}
    public function predict( $samples ) {
        $dataset = new Unlabeled( $samples );
        $predictions = $this->estimator->predict( $dataset );
        $probabilities = $this->estimator->proba( $dataset );
// @TODO create an endpoint for this so it's available in the editor
//        print_r( $probabilities ); // take the top 6 and return them
//        print_r( $predictions ); // this is the top prediction

        return array(
            'predictions'   => $predictions,
            'probabilities' => $probabilities
        );
    }
    public function train() {
        /*
         * Eventually we'll want to fetch this from http://api.wordpress.org/patterns/1.0/
         */

        // Get the path to your plugin directory
        $json_path = plugin_dir_path( dirname( __FILE__ ) ) . 'data/wordpress-patterns.json';

        // Read the contents of the JSON file
        $json = file_get_contents( $json_path );

        // Parse the JSON data into a PHP object or array
        $this->patterns = json_decode( $json );

        foreach ( $this->patterns as $pattern ) {
            $this->compile_dataset( $pattern );
        }

        $dataset = new Labeled( $this->samples, $this->labels );
        //$dataset->apply( new OneHotEncoder() );

        $testing = $dataset->randomize()->take(10 );

        //$this->estimator = new KNearestNeighbors(5 );
        //$this->estimator = new ClassificationTree(10, 5, 0.001, null, null);
        $this->estimator = new RandomForest( new ClassificationTree( 20 ), 500 );

        $this->estimator->train( $dataset );

        // Persis model and save in database? https://docs.rubixml.com/2.0/model-persistence.html

//        $predictions = $this->estimator->predict( $testing );
//         print_r( array_slice( $predictions, 0, 10 ) );
//        $metric = new Accuracy();
//        $score = $metric->score( $predictions, $testing->labels() );
//         print_r( 'Accuracy is ' . (string) ( $score * 100.0 ) . '%' );
    }

    private function compile_dataset( $pattern ) {
        $blocks = parse_blocks( $pattern->pattern_content );

        /*
            Objective: to offer a suggestion on which block to insert based on surrounding data
            The prediction is a WP block name

            Look at:
            - parent blocks, first block type, then attributes, then tag
            - depth
            - position in tree, e.g., top, middle, bottom = 0-1 where 0 is top and 1 is bottom
            - type of parent block, e.g., group, gallery
            - sibling blocks, e.g. paragraph, first block type, then attributes, then tag
            - frequency of blocks (most used)
            - order of blocks, where an image appears for example
            - language, e.g, what block might come after a paragraph that talks about [noun], or mentions a video or audio/music or youtube etc
            - if the block is a container, what blocks might be inside/outside/before
            -
            Fallbacks:

            $samples = [
                [ depth, frequency, index, text, root_tag, attributes, parent_0, sibling_prev, sibling_next, location, blockName ],
            ];
        */

        // Build dataset and labels for https://docs.rubixml.com/2.0/basic-introduction.html

        $this->create_dataset_from_blocks( $blocks, 0, 'None' );

    }

    private function create_dataset_from_blocks( $blocks, $depth = 0, $parent_block_name = 'None' ) {
        foreach ( $blocks as $index => $block ) {
            if ( ! isset( $block['blockName'] ) ) {
                continue;
            }
            // $previous_block_name
            $previous_block_name = 'None';
            $previous_index = $index - 1;
            if ( isset( $blocks[ $previous_index ] ) ) {
                $previous_block_name = $blocks[ $previous_index ]['blockName'] ? $blocks[ $previous_index ]['blockName'] : 'None';
            }

            // $next_block_name
            $next_block_name = 'None';
            $next_index = $index + 1;
            if ( isset( $blocks[ $next_index ] ) ) {
                $next_block_name = $blocks[ $next_index ]['blockName'] ? $blocks[ $next_index ]['blockName'] : 'None';
            }

            // This is what we need from the API
            // Turn into a constant, or create a class to generate the model.
            $this->samples[] = array(
                $depth,
                $parent_block_name,
                $previous_block_name,
                $next_block_name,
                //$block['blockName'],
            );

            $this->labels[] = $block['blockName'];

            // If the block has inner blocks, recursively call the function to find nested blocks
            if ( ! empty( $block['innerBlocks'] ) ) {
                $this->create_dataset_from_blocks( $block['innerBlocks'], $depth + 1, $block['blockName'] );
            }
        }
    }
}
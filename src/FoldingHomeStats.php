<?php


namespace PhoenixFAH;


use DateTime;

/**
 * Class Phoenix_Folding_Home_Stats
 */
class FoldingHomeStats
{
    /**
     *
     */
    public const SHORTCODE_NAME = 'phoenix_folding_stats';

    /**
     * @var string
     */
    public const VERSION = '2.0.0';

    /**
     * @var Stats[][]
     */
    private array $stats = [];

    /**
     * @var DateTime Exists so we don't repeatedly instantiate DateTime
     */
    private DateTime $currentDateTime;

    /**
     * @var string
     */
    private string $assetsURL;

    /**
     * Phoenix_Folding_Home_Stats constructor.
     */
    public function __construct()
    {
        $this->assetsURL = plugins_url( 'phoenix-folding-at-home-stats/assets/' );
        foreach ( [
                      'Stats',
                      'StatsTeam',
                      'StatsUser',
                      'Table',
                      'Widget'
                  ] as $file ) {
            include_once($file . '.php');
        }

        add_action( 'wp_enqueue_scripts', [$this, 'enqueue'], 100 );
        add_action( 'widgets_init', [$this, 'registerWidget'] );

        add_shortcode( self::SHORTCODE_NAME, [$this, 'shortcode'] );
        $this->currentDateTime = date_create(); //now

        add_action( 'save_post', [$this, 'updateForShortcode'], 10, 3 );
    }

    /**
     * @param        $id
     * @param string $type
     * @return Stats
     */
    public
    function statFactory($id, string $type = 'team'): Stats
    {
        if ( !empty( $this->stats[$type][$id] ) ) {
            return $this->stats[$type][$id];
        }

        switch( $type ) {
            case 'team':
                $stats = new StatsTeam();
                break;
            case 'user':
            case 'donor':
            default:
                $stats = new StatsUser();
        }
        if ( is_numeric( $id ) ) {
            $stats->setID( (int)$id );
        } else {
            $stats->setName( $id );
        }
        return $this->stats[$type][$id] = $stats
            ->setCurrentDateTime(
                $this->currentDateTime
            );
    }

    /**
     * @param        $id
     * @param string $type
     * @param array  $attributes
     * @return Table
     */
    public
    function tableFactory($id, string $type = 'team', array $attributes = []): Table
    {
        return (new Table())
            ->setAttributes( $attributes )
            ->setStats(
                $this->statFactory( $id, $type )
            )->setAssetsURL( $this->assetsURL );
    }

    /**
     *
     */
    public
    function enqueue(): void
    {
        //wp_enqueue_script('phoenix-main', PHOENIX_PLUGIN_URL . '/assets/js/main.min.js', array('jquery'), false, true);
        $min = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : 'min.';
        global $post;
        wp_register_style(
            'phoenix-folding', $this->assetsURL . 'css/phoenix-folding.' . $min . 'css',
            [],
            self::VERSION,
        );

        /**
         * If part of post content we enqueue otherwise we can enqueue in Widget class instance
         */
        if ( !empty( $post ) && has_shortcode( $post->post_content, self::SHORTCODE_NAME ) && (is_single() || is_page()) ) {
            wp_enqueue_style( 'phoenix-folding' );
        }
    }

    /**
     *
     */
    public function registerWidget(): void
    {
        if ( class_exists( \PhoenixFAH\Widget::class ) ) {
            register_widget( new Widget( $this ) );
        }
    }

    /**
     * @param        $attributes
     * @return string
     */
    public function shortcode($attributes): string
    {
        wp_enqueue_style( 'phoenix-folding' );
        $attributes = shortcode_atts(
            [
                'type' => 'team', //either team or user
                'id' => '1', //can be numerical for either or string for donors
                'class' => '',
                'show_donor_teams' => true,
                'show_team_donors' => true,
                'show_id' => false,
                'show_logo' => true,
                'show_rank' => true,
                'show_tagline' => true
            ], $attributes, self::SHORTCODE_NAME );
        return $this->tableFactory( $attributes['id'], $attributes['type'], $attributes )->render();
    }

    /**
     * Runs when post published to pre-query API for stats and save to DB
     *
     * @param $postID
     * @param $post
     * @param $update
     */
    public function updateForShortcode($postID, $post, $update): void
    {
        if ( 'auto-draft' === $post->post_status || 'revision' === $post->post_type ) {
            return;
        }
        $content = $post->post_content;
        if ( is_array( $content ) ) {
            $content = implode( ' ', $content );
        }
        if ( false === strpos( $content, '[' . self::SHORTCODE_NAME ) ) {
            return;
        }
        $regex = get_shortcode_regex( [self::SHORTCODE_NAME] );
        $count = preg_match_all( "/$regex/", $content, $matches );
        if ( !$count ) {
            return;
        }
        foreach ( $matches[3] as $attributes ) {
            $attributesArray = shortcode_parse_atts( $attributes );
            $this->statFactory(
                !empty( $attributesArray['id'] ) ? $attributesArray['id'] : '',
                !empty( $attributesArray['type'] ) ? $attributesArray['type'] : '',
            );
        }
    }
}
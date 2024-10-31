<?php


namespace PhoenixFAH;

/**
 * Class Table
 *
 * @author James Jones
 * @package PhoenixFAH
 *
 */
class Table
{
    /**
     * @var array Attributes from shortcode or widget
     */
    private array $attributes = [];

    /**
     * @var Stats
     */
    private Stats $stats;

    /**
     * @var int max number of donors to show for team, or teams for donor
     */
    public const MAX_ITEMS = 5;

    /**
     * @var string
     */
    private string $dateTimeFormat;

    /**
     * @var string
     */
    private string $assetsURL;

    /**
     * Table constructor.
     */
    public function __construct()
    {
        $this->dateTimeFormat = get_option( 'date_format', 'j F Y' ) . ', ' . get_option( 'time_format', 'g:i a' );
    }

    /**
     * @param array  $errorMessages
     * @param string $title
     * @return string
     */
    private function renderErrors(array $errorMessages = [], string $title = 'Errors'): string
    {
        if ( empty( $errorMessages ) ) {
            return '';
        }
        ob_start(); ?>
        <table class="fah-errors<?php echo !empty( $this->attributes['class'] ) ? ' ' . $this->attributes['class'] : ''; ?>">
            <thead>
            <tr>
                <th><?php echo $title; ?> - <small>Note: Errors are only visible to administrators</small></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ( $errorMessages as $errorMessage ) { ?>
                <tr>
                    <td><?php echo $errorMessage; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php return ob_get_clean();
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $stats = $this->stats->getStats();
        $type = $this->stats->getType();
        if ( $this->stats->getID() === null || empty( $this->stats->getName() ) ) {
            $missingData = true;
        }
        $isAdmin = is_super_admin();

        ob_start(); ?>
        <div class="phoenix-fah-stats">
            <?php
            if ( $isAdmin ) {
                echo $this->renderErrors( $this->stats->getPreviousErrors(), 'Previous Errors' );
                echo $this->renderErrors( $this->stats->getErrors() );
            }
            ?>
            <table class="fah-<?php echo $type . (!empty( $this->attributes['class'] ) ? ' ' . $this->attributes['class'] : ''); ?>">
                <thead>
                <tr>
                    <th colspan="2"><?php echo $this->getHeaderString(); ?>
                    </th>
                </tr>

                </thead>
                <tbody>
                <?php
                if ( !empty( $this->attributes['show_logo'] ) ) { ?>
                    <tr class="fah-logo">
                        <td colspan="2">
                            <a href="https://foldingathome.org/"><img src="<?php echo $this->assetsURL . 'images/fah-arrows.png'; ?>" alt="folding at home logo"></a>
                        </td>
                    </tr>
                <?php }
                if ( empty( $missingData ) ) {
                    if ( !empty( $this->attributes['show_id'] ) ) {
                        echo $this->renderRow(
                            ucfirst( $type ) . ' ID',
                            $stats->id,
                            ucfirst( $type ) . ' ID missing - Check in later',
                            'fah-id'
                        );
                    }

                    echo $this->renderRow(
                        'Grand score <small>(points)</small>',
                        !empty( $stats->score ) ? number_format( $stats->score ) : '',
                        'Grand Score missing - Check in later',
                        'fah-grand_score'
                    );
                    echo $this->renderRow(
                        'Work units completed',
                        !empty( $stats->wus ) ? number_format( $stats->wus ) : '',
                        'Work units missing - Check in later',
                        'fah-work_units'
                    );

                    if ( !empty( $this->attributes['show_rank'] ) ) {
                        // check for $rank before row.php because $rank is likely to be missing. Rank threshold to be reached before Folding provides rank through API.
                        echo $this->renderRow(
                            ucfirst( $type === 'user' ? 'donor' : $type ) . ' Ranking',
                            $this->getRankString( $stats ),
                            'Rank missing - Check in later',
                            'fah-rank'
                        );
                    }

                    if ( $type === 'user' ) {
                        echo $this->renderRow( //maybe impossible
                            'Last work unit completed',
                            isset( $stats->last ) ? $this->formatDate( $stats->last ) : '',
                            'Most recent work unit completion date missing - Check in later',
                            'fah-last_completion_date'
                        );
                    }
                    echo $this->renderRow(
                        'Report generated on',
                        isset( $stats->report_datetime ) ? $this->formatDate( $stats->report_datetime ) : '',
                        'Report date missing - Check in later',
                        'fah-report_date'
                    );
                } else {
                    echo $this->renderRow(
                        '',
                        '',
                        'Data currently unavailable - Check in later',
                        'fah-missing_data'
                    );
                    echo $this->renderRow(
                        'Report attempted on',
                        isset( $stats->attempt_datetime ) ? $this->formatDate( $stats->attempt_datetime ) : '',
                        'Report date missing - Check in later',
                        'fah-attempted_date'
                    );
                }

                if ( !empty( $this->attributes['show_donor_teams_team_donors'] ) ) {
                    echo $this->renderTeamDonors(); //Does donor teams too
                }
                ?>
                <tr class="fah-tagline">
                    <td colspan="2">
                        <div class="fah-tagline_logo">
                            <img width="50" height="50" src="<?php echo $this->assetsURL . 'images/fah-logo.png'; ?>" alt="folding at home logo">
                        </div>
                        <div class="fah-tagline_text">
                            <p>Folding@home allows anyone to assist with disease research by donating their unused
                                computer processing power. To join in, simply <a href="https://foldingathome.org/">download
                                    the F@H software</a>.
                            </p>
                        </div>
                    </td>
                </tr>
                </tbody>
                <?php echo ''; ?>
            </table>
        </div>
        <?php
        return apply_filters(
            'ph_folding_display_table',
            ob_get_clean(),
            $this
        );
    }

    /**
     * @return string
     */
    private function getNameWithLink(): string
    {
        $name = $this->stats->getName();
        if ( empty( $name ) ) {
            $name = $this->stats->getID();
            if ( $name === null ) {
                return '';
            }
            $name = 'ID: ' . $name;
        }
        $url = $this->stats->getURL();
        if ( empty( $url ) ) {
            return $name;
        }
        return '<a href=' . $url . '>' . $name . '</a>';
    }

    /**
     * @return string
     */
    private function getHeaderString(): string
    {
        $nameWithLink = $this->getNameWithLink();

        return sprintf(
            __( '<a href="https://foldingathome.org/">Folding@Home</a> contribution stats for %s %s', 'ph_folding' ),
            $this->stats->getType(),
            $nameWithLink
        );
    }

    /**
     * @param object $stats
     * @return string
     */
    public function getRankString(object $stats): string
    {
        if ( empty( $stats->rank ) ) {
            return '';
        }
        $type = $this->stats->getType();
        $totalKey = $type === 'team' ? 'teams' : 'users';
        $suffix = $type === 'team' ? 'teams' : 'donors';
        return number_format( $stats->rank )
            . (!empty( $stats->$totalKey ) ? ' of ' . number_format( $stats->$totalKey ) . ' ' . $suffix : '');
    }

    /**
     * @return string
     */
    public function renderTeamDonors(): string
    {
        $stats = $this->stats->getStats();
        $type = $this->stats->getType();
        $itemKey = $type === 'team' ? 'members' : 'teams';

        if ( empty( $stats->$itemKey ) ) {
            return '';
        }

        $items = $stats->$itemKey;
        if ( $type === 'team' ) {
            array_shift( $items );
        }
        $numberItems = count( $items );

        $maxItems = apply_filters( 'ph_folding_max_items', $this::MAX_ITEMS, $type, $numberItems );
        $itemType = $type === 'team' ? 'donor' : 'team';

        array_splice( $items, $maxItems );
        ob_start(); ?>
        <tr class="fah-team_donors_header">
        <th class="string" colspan="2"><?php
            $nameWithLink = $this->getNameWithLink();
            $headerString = (!empty( $nameWithLink ) ? $nameWithLink : $type)
                . ' '
                . $itemType
                . ($type === 'team' ? 's' : ' Contributions');
            echo $headerString;
            if ( !empty( $numberItems ) && !empty( $maxItems ) && $maxItems < $numberItems ) {
                printf( ' <small>(' . __( 'top %d %ss of %d', 'ph_folding' ) . ')<small>',
                    $maxItems,
                    $itemType,
                    $numberItems
                );
            }
            ?>
        </th>
        </tr><?php
        if ( $type === 'team' ) {
            foreach ( $items as $donor ) {
                // [0] => name [1] => id [2] => rank [3] => score [4] => wus
                $url = $this->stats::getDefaultURL( 'donor', $donor[1] );
                echo $this->renderRow(
                    !empty( $url ) ? '<a href="' . $url . '">' . $donor[0] . '</a>' : $donor[0],
                    number_format( (int)$donor[3] ), // credit
                    '',
                    'fah-team_donors'  /* old cell classes =// value name // value score */
                );
            }
        } else {
            foreach ( $items as $team ) {
                $url = $this->stats::getDefaultURL( 'team', $team->team );
                echo $this->renderRow(
                    !empty( $url ) ? '<a href="' . $url . '">' . $team->name . '</a>' : $team->name,
                    number_format( (int)$team->score ), // score
                    '',
                    'fah-donor_teams'  /* old cell classes =// value name // value score */
                );
            }
        }

        return ob_get_clean();
    }

    /**
     * @param string $string
     * @param string $val
     * @param string $missing_string
     * @param string $class
     * @return string
     */
    public function renderRow(string $string = '', string $val = '', string $missing_string = '', string $class = ''): string
    {
        ob_start(); ?>
        <tr<?php echo !empty( $class ) ? ' class="' . $class . '"' : ''; ?>>
            <?php if ( !empty( $val ) ) { ?>
                <th class="string"><?php _e( $string, 'ph_folding' ); ?></th>
                <td class="value"><?php echo $val; ?></td>
            <?php } else { ?>
                <td class="missing" colspan="2"><?php _e( $missing_string, 'ph_folding' ); ?></td>
            <?php } ?>
        </tr>
        <?php return ob_get_clean();
    }

    /**
     * @param string $date
     * @return string
     */
    public function formatDate(string $date): string
    {
        return date(
            $this->dateTimeFormat,
            strtotime( $date )
        );
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): Table
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @param Stats $stats
     * @return $this
     */
    public function setStats(Stats $stats): Table
    {
        $this->stats = $stats;
        return $this;
    }

    /**
     * @param string $assetsURL
     * @return $this
     */
    public function setAssetsURL(string $assetsURL): Table
    {
        $this->assetsURL = $assetsURL;
        return $this;
    }


}
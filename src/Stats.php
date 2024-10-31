<?php


namespace PhoenixFAH;

use DateTime;
use stdClass;
use WP_Error;

/**
 * Class Stats
 *
 * @author James Jones
 * @package PhoenixFAH
 *
 */
abstract class Stats
{
    /**
     *
     */
    public const TRANSIENT_NAME = 'phoenix_folding_stats';

    /**
     *
     */
    public const TRANSIENT_EXPIRATION = 31536000; /* 1 Year - 10800 - 3 Hours */

    /**
     *
     */
    public const DATE_FORMAT = 'Y-m-d H:i:s'; /* For DB transient and errors */

    /**
     * @var string
     */
    public const BASE_API_URL = 'https://api.foldingathome.org/';

    /**
     * @var string
     */
    protected string $type = '';

    /**
     * @var WP_Error
     */
    protected WP_Error $errors;

    /**
     * @var WP_Error
     */
    protected WP_Error $previousErrors;

    /**
     * @var int|null Team or donor numerical ID
     */
    protected ?int $id = null;

    /**
     * @var string Team or donor name
     */
    protected string $name = '';

    /**
     * @var stdClass|null|false
     */
    protected $stats = false;

    /**
     * @var DateTime Exists so we don't repeatedly instantiate DateTime
     */
    private DateTime $currentDateTime;

    /**
     * @var bool
     */
    private bool $isExpired;

    /**
     * Ph_Folding_Stats constructor.
     */
    public function __construct()
    {
        $this->errors = new WP_Error();
        $this->previousErrors = new WP_Error();
        add_action( 'shutdown', [$this, 'updateTransient'] );
    }

    /**
     * @return bool
     */
    public function updateTransient(): bool
    {
        if ( !$this->isStatsExpired() ) {
            return true;
        }
        $handle = $this->getHandle();

        $stats = $this->getStats();
        $stats->errors = [];

        if ( $this->errors->has_errors() ) {
            foreach ( $this->errors->get_error_messages() as $code => $errorMessage ) {
                $stats->errors[$code] = $errorMessage;
            }
        }
        $transient = get_transient( self::TRANSIENT_NAME );

        $transient[$this->type][$handle] = $stats;

        if ( is_int( $handle ) ) { //is an id
            $handle = $this->getHandle( true );
            unset( $transient[$this->type][$handle] );
        }
        if ( set_transient(
            self::TRANSIENT_NAME,
            $transient,
            self::TRANSIENT_EXPIRATION
        ) ) {
            $this->isExpired = false;
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getPreviousErrors(): array
    {
        $previousErrors = $this->getStatsProperty( 'errors' );
        if ( empty( $previousErrors ) ) {
            return [];
        }
        return $previousErrors;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors->get_error_messages();
    }

    /**
     * @param string $lastAttemptDateString
     * @return bool
     */
    public function isStatsExpired(string $lastAttemptDateString = ''): bool
    {
        if ( isset( $this->isExpired ) ) {
            return $this->isExpired;
        }
        if ( empty( $lastAttemptDateString ) ) {
            $lastAttemptDateString = (string)$this->getStatsProperty( 'attempt_datetime' );
            if ( empty( $lastAttemptDateString ) ) {
                return true;
            }
        }
        $difference = $this->currentDateTime->diff(
            date_create( $lastAttemptDateString )
        );
        $hoursDifference = ($difference->days * 24) + ($difference->h) + ($difference->i / 60);

        return $this->isExpired = ($hoursDifference > 3);
    }

    /**
     * @param string $key
     * @return string|mixed|stdClass|null
     */
    public function getStatsProperty(string $key)
    {
        $stats = $this->getStats();
        return !empty( $stats->$key ) ? $stats->$key : null;
    }

    /**
     * @param bool $nameOnly
     * @return int|string|null
     */
    public function getHandle(bool $nameOnly = false)
    {
        $id = $nameOnly ? null : $this->getID();
        if ( $id === null ) {
            $name = $this->getName();
            if ( empty( $name ) ) {
                return null;
            }
            return 'unknown_id-' . $name;
        }
        return $id;
    }

    /**
     * @return mixed|null
     */
    public function getStats()
    {
        if ( $this->stats !== false ) {
            return $this->stats;
        }

        $handle = $this->getHandle();
        if ( empty( $handle ) ) {
            $this->errors->add(
                'ph_folding_no-handle',
                'No ' . $this->type . ' ID or ' . $this->type . ' name provided.'
            );
            return null;
        }
        $transient = get_transient( self::TRANSIENT_NAME );

        if ( !empty( $transient[$this->type][$handle] ) ) {
            $statsFromDB = $transient[$this->type][$handle];
        }
        $this->isExpired = true;
        if ( empty( $statsFromDB->attempt_datetime ) ) {
            $this->isExpired = true;
        } elseif ( !$this->isStatsExpired( $statsFromDB->attempt_datetime ) ) { // Use stats from DB transient
            $stats = $statsFromDB;
            if ( isset( $stats->id ) && is_string( $handle ) /*name, not id*/ ) {
                $this->setID( $stats->id );
            }
            return $this->stats = $stats;
        }

        $currentDate = $this->currentDateTime->format( self::DATE_FORMAT );
        $stats = $this->getStatsFromAPI();
        if ( empty( $stats->id ) || empty( $stats->name ) ) { // API call failed
            if ( $stats !== null ) {    // API call returned bad data
                $this->errors->add(
                    'ph_folding_api-bad-data',
                    '<h5>Received Bad data from API :</h5> <ul><li><strong>Type</strong> &nbsp;-&nbsp; ' . $this->getType()
                    . '</li><li><strong>Handle</strong> &nbsp;-&nbsp; ' . $handle
                    . '</li><li><strong>Date Attempted</strong> &nbsp;-&nbsp; ' . $currentDate
                    . '</li><li><strong>Bad Data</strong> &nbsp;-&nbsp; ' . print_r( $stats, true )
                    . '</li></ul>'
                );
            }
            if ( !empty( $statsFromDB->report_datetime ) ) { // Use stats from DB transient as a backup
                $stats = $statsFromDB;
            } else { //API Call Failed and no transient
                $stats = new stdClass();
                if ( is_string( $handle ) ) {
                    $stats->name = $handle;
                } else {
                    $stats->id = $handle;
                }
            }
        } else { // API call succeeded
            $stats->report_datetime = $currentDate;
            if ( isset( $stats->id ) && is_string( $handle ) /*name, not id*/ ) {
                $this->setID( $stats->id );
            }
        }
        $stats->attempt_datetime = $currentDate;
        return $this->stats = $stats;
    }

    /**
     * @param string $type
     * @param        $id
     * @return string
     */
    public static function getDefaultURL(string $type, $id): string
    {
        if ( $id === null ) {
            return '';
        }
        if ( $type === 'user' ) {
            $type = 'donor';
        }
        return 'https://stats.foldingathome.org/' . $type . '/' . $id;
    }

    /**
     * @return mixed|stdClass|string
     */
    public function getURL()
    {
        $url = $this->getStatsProperty( 'url' );
        if ( !empty( $url ) ) {
            return $url;
        }
        return self::getDefaultURL( $this->getType(), $this->getID() );
    }

    /**
     * @return mixed
     */
    abstract public function getStatsFromAPI();

    /**
     * @param string $endpoint
     * @return mixed|null
     */
    public function getRemoteJSON(string $endpoint = '')
    {
        $url = self::BASE_API_URL . $endpoint;
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            $this->errors->merge_from( $response );
            return null;
        }

        $body = json_decode(
            wp_remote_retrieve_body( $response ),
            false,
            512
        );
        $code = wp_remote_retrieve_response_code( $response );

        if ( !empty( $body->error ) || $code !== 200 ) {
            $this->errors->add(
                'ph_folding_api-error',
                '<h5>API Error:</h5> <ul><li><strong>URL</strong>  &nbsp;-&nbsp;  ' . $url
                . '</li><li><strong>Date Attempted</strong> &nbsp;-&nbsp; ' . $this->currentDateTime->format( self::DATE_FORMAT )
                . '</li><li><strong>Code</strong> &nbsp;-&nbsp; ' . $code . '</li>'
                . (!empty( $body->status ) ? '<li><strong>Status</strong>  &nbsp;-&nbsp;  ' . $body->status . '</li>' : '')
                . (!empty( $body->error ) ? '<li><strong>Error</strong>  &nbsp;-&nbsp;  ' . $body->error . '</li>' : '')
                . '<li><strong>Response</strong> &nbsp;-&nbsp; ' . wp_remote_retrieve_response_message( $response ) . '</li></ul>'
            );
            return null;
        }
        return $body;
    }

    /**
     * @param int|null $id
     * @return Stats
     */
    public function setID(int $id = null): Stats
    {
        if ( $id !== null ) {
            $this->id = $id;
        }
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name = ''): Stats
    {
        if ( !empty( $name ) ) {
            $this->name = $name;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if ( !empty( $this->name ) ) {
            return $this->name;
        }
        $name = $this->getStatsProperty( 'name' );
        if ( is_string( $name ) ) {
            return $this->name = $name;
        }
        return '';
    }

    /**
     * @return int
     */
    public function getID(): ?int
    {
        if ( !empty( $this->id ) ) {
            return $this->id;
        }
        $name = $this->getName();
        if ( empty( $name ) ) {
            return null;
        }
        $transient = get_transient( self::TRANSIENT_NAME );
        if ( empty( $transient[$this->type] ) ) {
            return null;
        }
        foreach ( $transient[$this->type] as $record ) {
            if ( empty( $record->id ) || empty( $record->name ) ) {
                continue;
            }
            if ( $name === $record->name ) {
                $this->setID( $record->id );
                return $record->id;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param DateTime $dateTime
     * @return $this
     */
    public function setCurrentDateTime(DateTime $dateTime): Stats
    {
        $this->currentDateTime = $dateTime;
        return $this;
    }

}
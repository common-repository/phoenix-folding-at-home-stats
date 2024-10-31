<?php


namespace PhoenixFAH;


class StatsTeam extends Stats
{
    /**
     * @var string
     */
    protected string $type = 'team';

    /**
     * @return object|null
     * @throws \JsonException
     */
    public function getStatsFromAPI(): ?object
    {
        $stats = $this->getRemoteJSON( 'team/'
            . (!empty( $this->id ) ? $this->id : 'find?name=' . $this->name)
        );
        if ( empty( $stats ) ) {
            return null;
        }
        $count = $this->getRemoteJSON( 'team/count' );
        $stats->teams = $count;

        if ( !empty( $stats->id ) ) {
            $users = $this->getRemoteJSON( 'team/' . $stats->id . '/members' );
            $stats->members = $users;
        }
        return $stats;
    }
}
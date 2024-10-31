<?php


namespace PhoenixFAH;


class StatsUser extends Stats
{

    /**
     * @var string
     */
    protected string $type = 'user';

    /**
     * @return object|null
     * @throws \JsonException
     */
    public function getStatsFromAPI(): ?object
    {
        return $this->getRemoteJSON(
            !empty( $this->id ) ? 'uid/' . $this->id : 'user/' . $this->name
        );
    }
}
<?php

namespace Tienvx\Bundle\MbtBundle\Message;

class QueuedPathReducerMessage
{
    protected $reproducePathId;
    protected $length;
    protected $pair;
    protected $reducer;

    public function __construct(int $reproducePathId, int $length, array $pair, string $reducer)
    {
        $this->reproducePathId = $reproducePathId;
        $this->length          = $length;
        $this->pair            = $pair;
        $this->reducer         = $reducer;
    }

    public function getReproducePathId(): int
    {
        return $this->reproducePathId;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getPair(): array
    {
        return $this->pair;
    }

    public function getReducer(): string
    {
        return $this->reducer;
    }

    public function __toString()
    {
        return json_encode([
            'reproducePathId' => $this->reproducePathId,
            'length'          => $this->length,
            'pair'            => $this->pair,
            'reducer'         => $this->reducer,
        ]);
    }
}
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Carbon\Carbon;

#[ORM\Entity]
#[ORM\Table(name: 'currency_exchange_rate')]
class CurrencyExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CurrencyPair::class)]
    #[ORM\JoinColumn(name: 'pair_id', referencedColumnName: 'id', nullable: false)]
    private CurrencyPair $pair;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 10)]
    private float $rate;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    /**
     * Create a new currency exchange rate
     *
     * @param CurrencyPair $pair The currency pair
     * @param float $rate The exchange rate value
     * @param \DateTimeInterface|null $date The date of the exchange rate, defaults to now
     */
    public function __construct(CurrencyPair $pair, float $rate, \DateTimeInterface $date = null)
    {
        $this->pair = $pair;
        $this->rate = $rate;
        $this->date = $date ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPair(): CurrencyPair
    {
        return $this->pair;
    }

    public function setPair(CurrencyPair $pair): self
    {
        $this->pair = $pair;
        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
}

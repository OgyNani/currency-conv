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

    #[ORM\Column(type: 'json')]
    private array $exchangeRate = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    public function __construct()
    {
        $this->date = new \DateTime();
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

    public function getExchangeRate(): array
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(array $exchangeRate): self
    {
        $this->exchangeRate = $exchangeRate;
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

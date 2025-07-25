<?php   

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'currency_pair')]
class CurrencyPair
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CurrencyData::class)]
    #[ORM\JoinColumn(name: 'currency_from', referencedColumnName: 'id', nullable: false)]
    private CurrencyData $currencyFrom;

    #[ORM\ManyToOne(targetEntity: CurrencyData::class)]
    #[ORM\JoinColumn(name: 'currency_to', referencedColumnName: 'id', nullable: false)]
    private CurrencyData $currencyTo;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $observe = false;
    
    /**
     * Create a new currency pair
     *
     * @param CurrencyData $currencyFrom The source currency
     * @param CurrencyData $currencyTo The target currency
     * @param bool $observe Whether to observe this pair for rate updates
     */
    public function __construct(CurrencyData $currencyFrom, CurrencyData $currencyTo, bool $observe = false)
    {
        $this->currencyFrom = $currencyFrom;
        $this->currencyTo = $currencyTo;
        $this->observe = $observe;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrencyFrom(): CurrencyData
    {
        return $this->currencyFrom;
    }

    public function setCurrencyFrom(CurrencyData $currencyFrom): self
    {
        $this->currencyFrom = $currencyFrom;
        return $this;
    }

    public function getCurrencyTo(): CurrencyData
    {
        return $this->currencyTo;
    }

    public function setCurrencyTo(CurrencyData $currencyTo): self
    {
        $this->currencyTo = $currencyTo;
        return $this;
    }

    public function isObserve(): bool
    {
        return $this->observe;
    }

    public function setObserve(bool $observe): self
    {
        $this->observe = $observe;
        return $this;
    }
}
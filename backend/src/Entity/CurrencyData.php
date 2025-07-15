<?php   

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'currency_data')]
class CurrencyData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 3, unique: true)]
    private string $code;

    #[ORM\Column(length: 10)]
    private string $symbol;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(name: 'symbol_native', length: 20)]
    private string $symbolNative;

    #[ORM\Column(name: 'decimal_digits', type: 'smallint')]
    private int $decimalDigits;

    #[ORM\Column(type: 'float')]
    private float $rounding;

    #[ORM\Column(name: 'name_plural', length: 100)]
    private string $namePlural;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = null;
    
    /**
     * Create a new currency data entity
     *
     * @param string $code The currency code (e.g., USD)
     * @param string $symbol The currency symbol (e.g., $)
     * @param string $name The currency name (e.g., US Dollar)
     * @param string $symbolNative The native currency symbol
     * @param int $decimalDigits The number of decimal digits
     * @param float $rounding The rounding value
     * @param string $namePlural The plural name of the currency
     * @param string|null $type The currency type (e.g., fiat)
     */
    public function __construct(
        string $code,
        string $symbol,
        string $name,
        string $symbolNative,
        int $decimalDigits,
        float $rounding,
        string $namePlural,
        ?string $type = null
    ) {
        $this->code = $code;
        $this->symbol = $symbol;
        $this->name = $name;
        $this->symbolNative = $symbolNative;
        $this->decimalDigits = $decimalDigits;
        $this->rounding = $rounding;
        $this->namePlural = $namePlural;
        $this->type = $type;
    }

    public function getId(): ?int { 
        return $this->id; 
    }

    public function getCode(): string { 
        return $this->code; 
    }

    public function setCode(string $code): self { 
        $this->code = $code; 
        return $this; 
    }

    public function getSymbol(): string {
         return $this->symbol; 
    }

    public function setSymbol(string $symbol): self { 
        $this->symbol = $symbol; 
        return $this; 
    }

    public function getName(): string { 
        return $this->name; 
    }

    public function setName(string $name): self { 
        $this->name = $name; 
        return $this; 
    }

    public function getSymbolNative(): string { 
        return $this->symbolNative; 
    }

    public function setSymbolNative(string $symbolNative): self { 
        $this->symbolNative = $symbolNative; 
        return $this; 
    }

    public function getDecimalDigits(): int { 
        return $this->decimalDigits; 
    }

    public function setDecimalDigits(int $decimalDigits): self { 
        $this->decimalDigits = $decimalDigits; 
        return $this; 
    }

    public function getRounding(): float { 
        return $this->rounding; 
    }

    public function setRounding(float $rounding): self { 
        $this->rounding = $rounding; 
        return $this; 
    }

    public function getNamePlural(): string { 
        return $this->namePlural; 
    }

    public function setNamePlural(string $namePlural): self { 
        $this->namePlural = $namePlural; 
        return $this; 
    }

    public function getType(): ?string { 
        return $this->type; 
    }

    public function setType(?string $type): self { 
        $this->type = $type; 
        return $this; 
    }
}
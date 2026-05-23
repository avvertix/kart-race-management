<?php

declare(strict_types=1);

namespace App\Enums;

enum ItalianRegion: string
{
    case ABRUZZO = 'abruzzo';
    case BASILICATA = 'basilicata';
    case CALABRIA = 'calabria';
    case CAMPANIA = 'campania';
    case EMILIA_ROMAGNA = 'emilia_romagna';
    case FRIULI_VENEZIA_GIULIA = 'friuli_venezia_giulia';
    case LAZIO = 'lazio';
    case LIGURIA = 'liguria';
    case LOMBARDIA = 'lombardia';
    case MARCHE = 'marche';
    case MOLISE = 'molise';
    case PIEMONTE = 'piemonte';
    case PUGLIA = 'puglia';
    case SARDEGNA = 'sardegna';
    case SICILIA = 'sicilia';
    case TOSCANA = 'toscana';
    case TRENTINO_ALTO_ADIGE = 'trentino_alto_adige';
    case UMBRIA = 'umbria';
    case VALLE_D_AOSTA = 'valle_d_aosta';
    case VENETO = 'veneto';

    /**
     * Find the region that contains the given province code or name.
     */
    public static function fromProvince(string $province): ?self
    {
        foreach (self::cases() as $region) {
            if ($region->containsProvince($province)) {
                return $region;
            }
        }

        return null;
    }

    /**
     * Map a region name from the ISTAT postal-code dataset to an enum case.
     * Handles special names like "Trentino-Alto Adige/Südtirol" and "Valle d'Aosta/Vallée d'Aoste".
     */
    public static function fromDatasetRegionName(string $name): ?self
    {
        return match (mb_trim($name)) {
            'Abruzzo' => self::ABRUZZO,
            'Basilicata' => self::BASILICATA,
            'Calabria' => self::CALABRIA,
            'Campania' => self::CAMPANIA,
            'Emilia-Romagna' => self::EMILIA_ROMAGNA,
            'Friuli-Venezia Giulia' => self::FRIULI_VENEZIA_GIULIA,
            'Lazio' => self::LAZIO,
            'Liguria' => self::LIGURIA,
            'Lombardia' => self::LOMBARDIA,
            'Marche' => self::MARCHE,
            'Molise' => self::MOLISE,
            'Piemonte' => self::PIEMONTE,
            'Puglia' => self::PUGLIA,
            'Sardegna' => self::SARDEGNA,
            'Sicilia' => self::SICILIA,
            'Toscana' => self::TOSCANA,
            'Trentino-Alto Adige/Südtirol', 'Trentino-Alto Adige' => self::TRENTINO_ALTO_ADIGE,
            'Umbria' => self::UMBRIA,
            "Valle d'Aosta/Vallée d'Aoste", "Valle d'Aosta" => self::VALLE_D_AOSTA,
            'Veneto' => self::VENETO,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ABRUZZO => 'Abruzzo',
            self::BASILICATA => 'Basilicata',
            self::CALABRIA => 'Calabria',
            self::CAMPANIA => 'Campania',
            self::EMILIA_ROMAGNA => 'Emilia-Romagna',
            self::FRIULI_VENEZIA_GIULIA => 'Friuli-Venezia Giulia',
            self::LAZIO => 'Lazio',
            self::LIGURIA => 'Liguria',
            self::LOMBARDIA => 'Lombardia',
            self::MARCHE => 'Marche',
            self::MOLISE => 'Molise',
            self::PIEMONTE => 'Piemonte',
            self::PUGLIA => 'Puglia',
            self::SARDEGNA => 'Sardegna',
            self::SICILIA => 'Sicilia',
            self::TOSCANA => 'Toscana',
            self::TRENTINO_ALTO_ADIGE => 'Trentino-Alto Adige',
            self::UMBRIA => 'Umbria',
            self::VALLE_D_AOSTA => "Valle d'Aosta",
            self::VENETO => 'Veneto',
        };
    }

    /**
     * Returns all provinces in this region as ['code' => '..', 'name' => '..'] arrays.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function provinces(): array
    {
        return match ($this) {
            self::ABRUZZO => [
                ['code' => 'AQ', 'name' => "L'Aquila"],
                ['code' => 'CH', 'name' => 'Chieti'],
                ['code' => 'PE', 'name' => 'Pescara'],
                ['code' => 'TE', 'name' => 'Teramo'],
            ],
            self::BASILICATA => [
                ['code' => 'MT', 'name' => 'Matera'],
                ['code' => 'PZ', 'name' => 'Potenza'],
            ],
            self::CALABRIA => [
                ['code' => 'CS', 'name' => 'Cosenza'],
                ['code' => 'CZ', 'name' => 'Catanzaro'],
                ['code' => 'KR', 'name' => 'Crotone'],
                ['code' => 'RC', 'name' => 'Reggio Calabria'],
                ['code' => 'VV', 'name' => 'Vibo Valentia'],
            ],
            self::CAMPANIA => [
                ['code' => 'AV', 'name' => 'Avellino'],
                ['code' => 'BN', 'name' => 'Benevento'],
                ['code' => 'CE', 'name' => 'Caserta'],
                ['code' => 'NA', 'name' => 'Napoli'],
                ['code' => 'SA', 'name' => 'Salerno'],
            ],
            self::EMILIA_ROMAGNA => [
                ['code' => 'BO', 'name' => 'Bologna'],
                ['code' => 'FC', 'name' => 'Forlì-Cesena'],
                ['code' => 'FE', 'name' => 'Ferrara'],
                ['code' => 'MO', 'name' => 'Modena'],
                ['code' => 'PC', 'name' => 'Piacenza'],
                ['code' => 'PR', 'name' => 'Parma'],
                ['code' => 'RA', 'name' => 'Ravenna'],
                ['code' => 'RE', 'name' => 'Reggio Emilia'],
                ['code' => 'RN', 'name' => 'Rimini'],
            ],
            self::FRIULI_VENEZIA_GIULIA => [
                ['code' => 'GO', 'name' => 'Gorizia'],
                ['code' => 'PN', 'name' => 'Pordenone'],
                ['code' => 'TS', 'name' => 'Trieste'],
                ['code' => 'UD', 'name' => 'Udine'],
            ],
            self::LAZIO => [
                ['code' => 'FR', 'name' => 'Frosinone'],
                ['code' => 'LT', 'name' => 'Latina'],
                ['code' => 'RI', 'name' => 'Rieti'],
                ['code' => 'RM', 'name' => 'Roma'],
                ['code' => 'VT', 'name' => 'Viterbo'],
            ],
            self::LIGURIA => [
                ['code' => 'GE', 'name' => 'Genova'],
                ['code' => 'IM', 'name' => 'Imperia'],
                ['code' => 'SP', 'name' => 'La Spezia'],
                ['code' => 'SV', 'name' => 'Savona'],
            ],
            self::LOMBARDIA => [
                ['code' => 'BG', 'name' => 'Bergamo'],
                ['code' => 'BS', 'name' => 'Brescia'],
                ['code' => 'CO', 'name' => 'Como'],
                ['code' => 'CR', 'name' => 'Cremona'],
                ['code' => 'LC', 'name' => 'Lecco'],
                ['code' => 'LO', 'name' => 'Lodi'],
                ['code' => 'MB', 'name' => 'Monza e Brianza'],
                ['code' => 'MI', 'name' => 'Milano'],
                ['code' => 'MN', 'name' => 'Mantova'],
                ['code' => 'PV', 'name' => 'Pavia'],
                ['code' => 'SO', 'name' => 'Sondrio'],
                ['code' => 'VA', 'name' => 'Varese'],
            ],
            self::MARCHE => [
                ['code' => 'AN', 'name' => 'Ancona'],
                ['code' => 'AP', 'name' => 'Ascoli Piceno'],
                ['code' => 'FM', 'name' => 'Fermo'],
                ['code' => 'MC', 'name' => 'Macerata'],
                ['code' => 'PU', 'name' => 'Pesaro e Urbino'],
            ],
            self::MOLISE => [
                ['code' => 'CB', 'name' => 'Campobasso'],
                ['code' => 'IS', 'name' => 'Isernia'],
            ],
            self::PIEMONTE => [
                ['code' => 'AL', 'name' => 'Alessandria'],
                ['code' => 'AT', 'name' => 'Asti'],
                ['code' => 'BI', 'name' => 'Biella'],
                ['code' => 'CN', 'name' => 'Cuneo'],
                ['code' => 'NO', 'name' => 'Novara'],
                ['code' => 'TO', 'name' => 'Torino'],
                ['code' => 'VB', 'name' => 'Verbano-Cusio-Ossola'],
                ['code' => 'VC', 'name' => 'Vercelli'],
            ],
            self::PUGLIA => [
                ['code' => 'BA', 'name' => 'Bari'],
                ['code' => 'BR', 'name' => 'Brindisi'],
                ['code' => 'BT', 'name' => 'Barletta-Andria-Trani'],
                ['code' => 'FG', 'name' => 'Foggia'],
                ['code' => 'LE', 'name' => 'Lecce'],
                ['code' => 'TA', 'name' => 'Taranto'],
            ],
            self::SARDEGNA => [
                ['code' => 'CA', 'name' => 'Cagliari'],
                ['code' => 'NU', 'name' => 'Nuoro'],
                ['code' => 'OR', 'name' => 'Oristano'],
                ['code' => 'SS', 'name' => 'Sassari'],
                ['code' => 'SU', 'name' => 'Sud Sardegna'],
            ],
            self::SICILIA => [
                ['code' => 'AG', 'name' => 'Agrigento'],
                ['code' => 'CL', 'name' => 'Caltanissetta'],
                ['code' => 'CT', 'name' => 'Catania'],
                ['code' => 'EN', 'name' => 'Enna'],
                ['code' => 'ME', 'name' => 'Messina'],
                ['code' => 'PA', 'name' => 'Palermo'],
                ['code' => 'RG', 'name' => 'Ragusa'],
                ['code' => 'SR', 'name' => 'Siracusa'],
                ['code' => 'TP', 'name' => 'Trapani'],
            ],
            self::TOSCANA => [
                ['code' => 'AR', 'name' => 'Arezzo'],
                ['code' => 'FI', 'name' => 'Firenze'],
                ['code' => 'GR', 'name' => 'Grosseto'],
                ['code' => 'LI', 'name' => 'Livorno'],
                ['code' => 'LU', 'name' => 'Lucca'],
                ['code' => 'MS', 'name' => 'Massa-Carrara'],
                ['code' => 'PI', 'name' => 'Pisa'],
                ['code' => 'PO', 'name' => 'Prato'],
                ['code' => 'PT', 'name' => 'Pistoia'],
                ['code' => 'SI', 'name' => 'Siena'],
            ],
            self::TRENTINO_ALTO_ADIGE => [
                ['code' => 'BZ', 'name' => 'Bolzano'],
                ['code' => 'TN', 'name' => 'Trento'],
            ],
            self::UMBRIA => [
                ['code' => 'PG', 'name' => 'Perugia'],
                ['code' => 'TR', 'name' => 'Terni'],
            ],
            self::VALLE_D_AOSTA => [
                ['code' => 'AO', 'name' => 'Aosta'],
            ],
            self::VENETO => [
                ['code' => 'BL', 'name' => 'Belluno'],
                ['code' => 'PD', 'name' => 'Padova'],
                ['code' => 'RO', 'name' => 'Rovigo'],
                ['code' => 'TV', 'name' => 'Treviso'],
                ['code' => 'VE', 'name' => 'Venezia'],
                ['code' => 'VI', 'name' => 'Vicenza'],
                ['code' => 'VR', 'name' => 'Verona'],
            ],
        };
    }

    /**
     * Check whether this region contains the given province (by code or name).
     */
    public function containsProvince(string $province): bool
    {
        $normalized = mb_trim($province);

        foreach ($this->provinces() as $p) {
            if (strcasecmp($p['code'], $normalized) === 0 || strcasecmp($p['name'], $normalized) === 0) {
                return true;
            }
        }

        return false;
    }
}

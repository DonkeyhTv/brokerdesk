<?php

namespace Database\Factories\Support;

/**
 * Plausible Belgian identities and addresses for factories and demo data.
 * Communes and postal codes are real; people and companies are invented.
 */
final class BelgianData
{
    public const FRENCH_COMMUNES = [
        ['1000', 'Bruxelles'], ['1050', 'Ixelles'], ['1180', 'Uccle'], ['1200', 'Woluwe-Saint-Lambert'],
        ['1300', 'Wavre'], ['1348', 'Ottignies-Louvain-la-Neuve'], ['1400', 'Nivelles'], ['4000', 'Liège'],
        ['4020', 'Liège'], ['4800', 'Verviers'], ['5000', 'Namur'], ['5500', 'Dinant'],
        ['6000', 'Charleroi'], ['6700', 'Arlon'], ['7000', 'Mons'], ['7500', 'Tournai'],
    ];

    public const DUTCH_COMMUNES = [
        ['1500', 'Halle'], ['1800', 'Vilvoorde'], ['2000', 'Antwerpen'], ['2300', 'Turnhout'],
        ['2800', 'Mechelen'], ['3000', 'Leuven'], ['3500', 'Hasselt'], ['3800', 'Sint-Truiden'],
        ['8000', 'Brugge'], ['8400', 'Oostende'], ['8500', 'Kortrijk'], ['9000', 'Gent'],
        ['9100', 'Sint-Niklaas'], ['9300', 'Aalst'],
    ];

    public const FRENCH_STREETS = [
        'Rue de la Station', 'Rue du Moulin', 'Rue de l\'Église', 'Chaussée de Namur', 'Avenue des Tilleuls',
        'Rue des Écoles', 'Place du Marché', 'Rue de la Paix', 'Avenue de la Gare', 'Rue des Champs',
    ];

    public const DUTCH_STREETS = [
        'Stationsstraat', 'Kerkstraat', 'Molenstraat', 'Schoolstraat', 'Dorpsstraat',
        'Nieuwstraat', 'Kapelstraat', 'Veldstraat', 'Grote Markt', 'Lindenlaan',
    ];

    public const FIRST_NAMES = [
        'Emma', 'Louise', 'Camille', 'Julie', 'Sarah', 'Charlotte', 'Elise', 'Marie', 'Laura', 'Nora',
        'Lucas', 'Noah', 'Louis', 'Thomas', 'Maxime', 'Antoine', 'Arthur', 'Jules', 'Wout', 'Bram',
    ];

    public const LAST_NAMES = [
        'Peeters', 'Janssens', 'Maes', 'Jacobs', 'Mertens', 'Willems', 'Claes', 'Goossens', 'Wouters', 'De Smet',
        'Dubois', 'Lambert', 'Dupont', 'Martin', 'Simon', 'Laurent', 'Leclercq', 'Renard', 'Lejeune', 'Noël',
    ];

    public const FRENCH_BUSINESSES = ['Boulangerie', 'Garage', 'Menuiserie', 'Toiture', 'Imprimerie', 'Électricité'];

    public const DUTCH_BUSINESSES = ['Bakkerij', 'Garage', 'Schrijnwerkerij', 'Dakwerken', 'Drukkerij', 'Elektriciteit'];

    public const FRENCH_LEGAL_FORMS = ['SRL', 'SA', 'SC'];

    public const DUTCH_LEGAL_FORMS = ['BV', 'NV', 'CV'];

    public const INSURERS = [
        'Assurances des Ardennes', 'Mutuelle du Condroz', 'Hainaut Assurances',
        'Kempen Verzekeringen', 'Scheldeland Verzekeringen', 'Brabant Vie',
    ];

    public const CLAIM_DESCRIPTIONS = [
        'motor' => [
            'Collision sur un parking, pare-chocs arrière endommagé.',
            'Bris de pare-brise par un gravillon sur l\'autoroute.',
            'Accrochage à un carrefour, constat amiable signé par les deux parties.',
        ],
        'home' => [
            'Dégât des eaux dans la cuisine après la rupture d\'un flexible.',
            'Tuiles arrachées par la tempête, infiltration dans les combles.',
            'Vitre de la porte d\'entrée brisée lors d\'une tentative d\'effraction.',
        ],
        'liability' => [
            'Dommage causé au véhicule d\'un voisin par la chute d\'une branche.',
            'Enfant ayant brisé une vitre chez un tiers en jouant au ballon.',
        ],
        'life' => ['Demande de liquidation partielle à la suite d\'un événement familial.'],
        'health' => ['Remboursement de frais d\'hospitalisation à la suite d\'une opération.'],
        'legal' => ['Litige avec un entrepreneur concernant des travaux de rénovation.'],
        'business' => [
            'Vol de matériel dans l\'atelier pendant le week-end.',
            'Incendie limité dans la réserve, stock partiellement détruit.',
        ],
    ];

    /**
     * @return array{street: string, postal_code: string, city: string}
     */
    public static function address(): array
    {
        $isFrench = fake()->boolean(55);
        [$postalCode, $city] = fake()->randomElement($isFrench ? self::FRENCH_COMMUNES : self::DUTCH_COMMUNES);
        $street = fake()->randomElement($isFrench ? self::FRENCH_STREETS : self::DUTCH_STREETS);

        return [
            'street' => $street.' '.fake()->numberBetween(1, 180),
            'postal_code' => $postalCode,
            'city' => $city,
        ];
    }

    public static function companyName(): string
    {
        $isFrench = fake()->boolean(55);
        $business = fake()->randomElement($isFrench ? self::FRENCH_BUSINESSES : self::DUTCH_BUSINESSES);
        $legalForm = fake()->randomElement($isFrench ? self::FRENCH_LEGAL_FORMS : self::DUTCH_LEGAL_FORMS);

        return $business.' '.fake()->randomElement(self::LAST_NAMES).' '.$legalForm;
    }

    public static function phone(): string
    {
        return sprintf('+32 470 %02d %02d %02d', fake()->numberBetween(0, 99), fake()->numberBetween(0, 99), fake()->numberBetween(0, 99));
    }
}

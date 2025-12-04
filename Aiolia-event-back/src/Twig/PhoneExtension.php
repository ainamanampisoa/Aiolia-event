<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PhoneExtension extends AbstractExtension
{
    /**
     * Mapping des codes pays vers les codes de drapeau emoji et les indicatifs téléphoniques
     */
    private const COUNTRY_CODES = [
        'MG' => ['code' => '+261', 'flag' => '🇲🇬', 'name' => 'Madagascar'],
        'FR' => ['code' => '+33', 'flag' => '🇫🇷', 'name' => 'France'],
        'US' => ['code' => '+1', 'flag' => '🇺🇸', 'name' => 'États-Unis'],
        'GB' => ['code' => '+44', 'flag' => '🇬🇧', 'name' => 'Royaume-Uni'],
        'CA' => ['code' => '+1', 'flag' => '🇨🇦', 'name' => 'Canada'],
        'BE' => ['code' => '+32', 'flag' => '🇧🇪', 'name' => 'Belgique'],
        'CH' => ['code' => '+41', 'flag' => '🇨🇭', 'name' => 'Suisse'],
        'RE' => ['code' => '+262', 'flag' => '🇷🇪', 'name' => 'Réunion'],
        'MU' => ['code' => '+230', 'flag' => '🇲🇺', 'name' => 'Maurice'],
        // Ajoutez d'autres pays selon les besoins
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('phone_format', [$this, 'formatPhone'], ['is_safe' => ['html']]),
            new TwigFilter('phone_format_text', [$this, 'formatPhoneText'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Formate un numéro de téléphone avec drapeau et code pays
     * 
     * @param string|null $phone Le numéro de téléphone
     * @param string|null $countryCode Le code pays (ex: 'MG', 'FR')
     * @return string HTML formaté avec drapeau et code pays
     */
    public function formatPhone(?string $phone, ?string $countryCode = null): string
    {
        if (!$phone) {
            return '';
        }

        // Par défaut, utiliser Madagascar si aucun code pays n'est fourni
        $countryCode = $countryCode ? strtoupper($countryCode) : 'MG';
        
        // Vérifier si le code pays existe dans notre mapping
        if (!isset(self::COUNTRY_CODES[$countryCode])) {
            // Si le code pays n'est pas reconnu, essayer de deviner depuis le numéro
            $countryCode = $this->detectCountryFromPhone($phone) ?? 'MG';
        }

        $country = self::COUNTRY_CODES[$countryCode] ?? self::COUNTRY_CODES['MG'];
        
        // Si le numéro commence déjà par le code pays, ne pas le dupliquer
        $phoneNumber = $phone;
        if (strpos($phone, '+') === 0 || strpos($phone, '00') === 0) {
            // Le numéro a déjà un indicatif international
            $phoneNumber = preg_replace('/^(\+|00)/', '', $phone);
            // Retirer le code pays s'il est présent au début
            if (strpos($phoneNumber, ltrim($country['code'], '+')) === 0) {
                $phoneNumber = substr($phoneNumber, strlen(ltrim($country['code'], '+')));
            }
        }

        // Formater le numéro avec le drapeau et le code pays
        return sprintf(
            '<span class="phone-formatted"><span class="phone-flag">%s</span><span class="phone-country-code">%s</span><span class="phone-number">%s</span></span>',
            $country['flag'],
            $country['code'],
            $phoneNumber
        );
    }

    /**
     * Formate un numéro de téléphone avec code pays en texte simple (pour PDF)
     * 
     * @param string|null $phone Le numéro de téléphone
     * @param string|null $countryCode Le code pays (ex: 'MG', 'FR')
     * @return string Texte formaté avec code pays (sans emoji)
     */
    public function formatPhoneText(?string $phone, ?string $countryCode = null): string
    {
        if (!$phone) {
            return '';
        }

        // Par défaut, utiliser Madagascar si aucun code pays n'est fourni
        $countryCode = $countryCode ? strtoupper($countryCode) : 'MG';
        
        // Vérifier si le code pays existe dans notre mapping
        if (!isset(self::COUNTRY_CODES[$countryCode])) {
            // Si le code pays n'est pas reconnu, essayer de deviner depuis le numéro
            $countryCode = $this->detectCountryFromPhone($phone) ?? 'MG';
        }

        $country = self::COUNTRY_CODES[$countryCode] ?? self::COUNTRY_CODES['MG'];
        
        // Si le numéro commence déjà par le code pays, ne pas le dupliquer
        $phoneNumber = $phone;
        if (strpos($phone, '+') === 0 || strpos($phone, '00') === 0) {
            // Le numéro a déjà un indicatif international
            $phoneNumber = preg_replace('/^(\+|00)/', '', $phone);
            // Retirer le code pays s'il est présent au début
            if (strpos($phoneNumber, ltrim($country['code'], '+')) === 0) {
                $phoneNumber = substr($phoneNumber, strlen(ltrim($country['code'], '+')));
            }
        }

        // Formater le numéro avec le code pays en texte simple
        return sprintf(
            '%s %s',
            $country['code'],
            $phoneNumber
        );
    }

    /**
     * Essaie de détecter le code pays depuis le numéro de téléphone
     */
    private function detectCountryFromPhone(string $phone): ?string
    {
        $phone = preg_replace('/^(\+|00)/', '', $phone);
        
        // Madagascar: commence par 261
        if (strpos($phone, '261') === 0) {
            return 'MG';
        }
        
        // France: commence par 33
        if (strpos($phone, '33') === 0) {
            return 'FR';
        }
        
        return null;
    }
}


<?php
/**
 * Validator.php - Klasse für Input-Validierung
 */

namespace Alphaessen\Validation;

class Validator
{
    /**
     * Validiert eine E-Mail-Adresse
     * 
     * @param string $email Die zu validierende E-Mail-Adresse
     * @return bool True, wenn die E-Mail-Adresse gültig ist
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validiert einen Integer-Wert
     * 
     * @param mixed $value Der zu validierende Wert
     * @param int|null $min Minimalwert (optional)
     * @param int|null $max Maximalwert (optional)
     * @return bool True, wenn der Wert ein gültiger Integer ist
     */
    public static function validateInteger($value, ?int $min = null, ?int $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $intValue = (int)$value;

        if ($min !== null && $intValue < $min) {
            return false;
        }

        if ($max !== null && $intValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validiert einen String
     * 
     * @param string $value Der zu validierende String
     * @param int|null $minLength Minimale Länge (optional)
     * @param int|null $maxLength Maximale Länge (optional)
     * @return bool True, wenn der String gültig ist
     */
    public static function validateString(string $value, ?int $minLength = null, ?int $maxLength = null): bool
    {
        $length = strlen(trim($value));

        if ($minLength !== null && $length < $minLength) {
            return false;
        }

        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }

        return true;
    }

    /**
     * Validiert einen Essens-Typ
     * 
     * @param string $typ Der zu validierende Typ
     * @return bool True, wenn der Typ gültig ist
     */
    public static function validateEssenTyp(string $typ): bool
    {
        return in_array($typ, \Alphaessen\Models\Essen::ALLE_TYPEN, true);
    }

    /**
     * Validiert eine Woche (1-12)
     * 
     * @param mixed $woche Die zu validierende Woche
     * @return bool True, wenn die Woche gültig ist
     */
    public static function validateWoche($woche): bool
    {
        return self::validateInteger($woche, 1, 12);
    }

    /**
     * Validiert ein Jahr
     * 
     * @param mixed $jahr Das zu validierende Jahr
     * @param int|null $minJahr Minimales Jahr (optional, Standard: aktuelles Jahr - 1)
     * @param int|null $maxJahr Maximales Jahr (optional, Standard: aktuelles Jahr + 5)
     * @return bool True, wenn das Jahr gültig ist
     */
    public static function validateJahr($jahr, ?int $minJahr = null, ?int $maxJahr = null): bool
    {
        $currentYear = (int)date('Y');
        $minJahr = $minJahr ?? $currentYear - 1;
        $maxJahr = $maxJahr ?? $currentYear + 5;

        return self::validateInteger($jahr, $minJahr, $maxJahr);
    }

    /**
     * Bereinigt einen String (trim, htmlspecialchars)
     * 
     * @param string $value Der zu bereinigende String
     * @return string Der bereinigte String
     */
    public static function sanitizeString(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Bereinigt eine E-Mail-Adresse
     * 
     * @param string $email Die zu bereinigende E-Mail-Adresse
     * @return string Die bereinigte E-Mail-Adresse
     */
    public static function sanitizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Bereinigt einen Integer
     * 
     * @param mixed $value Der zu bereinigende Wert
     * @return int Der bereinigte Integer
     */
    public static function sanitizeInteger($value): int
    {
        return (int)$value;
    }

    /**
     * Prüft, ob ein Wert leer ist
     * 
     * @param mixed $value Der zu prüfende Wert
     * @return bool True, wenn der Wert leer ist
     */
    public static function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return empty($value);
        }

        return $value === '' || $value === 0 || $value === false;
    }

    /**
     * Prüft, ob ein Wert nicht leer ist
     * 
     * @param mixed $value Der zu prüfende Wert
     * @return bool True, wenn der Wert nicht leer ist
     */
    public static function isNotEmpty($value): bool
    {
        return !self::isEmpty($value);
    }
}

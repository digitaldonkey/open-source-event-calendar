<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\OsecBaseClass;

/**
 * This class renders the html for the event ticket.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Ticket
 */
class EventTicketView extends OsecBaseClass
{
    /**
     * Create readable content for buy tickets/register link
     *
     * @param  bool  $long  Set to false to use short message version
     *
     * @return string Message to be rendered on buy tickets link
     */
    public function get_tickets_url_label(Event $event, $long = true)
    {
        if ($event->is_free()) {
            return ($long)
                ? __('Register Now', 'open-source-event-calendar')
                : __('Register', 'open-source-event-calendar');
        }
        $output = '';
        if ($long) {
            /**
             * Alter buy tickes icon html
             *
             * @since 1.0
             *
             * @param  string  $html  Html do display a ticket icon.
             */
            $output = apply_filters('osec_buy_tickets_url_icon', '<i class="ai1ec-fa ai1ec-fa-shopping-cart"></i>');
            if ( ! empty($output)) {
                $output .= ' ';
            }
        }
        $output .= ($long)
            ? __('Buy Tickets', 'open-source-event-calendar')
            : __('Tickets', 'open-source-event-calendar');

        return $output;
    }


    /**
     * The first number in the cost, e.g. for schema.org/Offer price.
     *
     * A separator followed by one or two digits at its end is the decimal separator, any other
     * groups thousands: "12.50", "12,50 €", "€1.000", "1,000.50", "10 - 20 EUR" (10).
     */
    public function get_cost_value(Event $event): ?float
    {
        if (! preg_match('/\d[\d.,\s]*/u', (string)$event->get('cost'), $match)) {
            return null;
        }
        $number   = rtrim($match[0], "., \t\n\r");
        $decimals = '';
        if (preg_match('/[.,](\d{1,2})$/', $number, $fraction)) {
            $decimals = '.' . $fraction[1];
            $number   = substr($number, 0, -strlen($fraction[0]));
        }

        return (float)(preg_replace('/\D/', '', $number) . $decimals);
    }

    /**
     * Contact info as HTML
     */
    public function get_cost_iso_4217_currency(Event $event): ?string
    {
        $coast = $event->get('cost');

        if (empty($coast)) {
            return null;
        }

        /**
         *  UTF-8 Currency Symbol (Key) => ISO 4217 Code (Value)
         */
        $currencyMap = [
            '$'    => 'USD', // US Dollar (Shared with AUD, CAD, NZD, etc.)
            '€'    => 'EUR', // Euro
            '£'    => 'GBP', // British Pound
            '¥'    => 'JPY', // Japanese Yen / Chinese Yuan
            '₹'    => 'INR', // Indian Rupee
            '₽'    => 'RUB', // Russian Ruble
            '₿'    => 'BTC', // Bitcoin (Commonly used, though not a standard ISO currency)
            '₩'    => 'KRW', // South Korean Won
            '₪'    => 'ILS', // Israeli New Shekel
            '₫'    => 'VND', // Vietnamese Dong
            '₭'    => 'LAK', // Lao Kip
            '₮'    => 'MNT', // Mongolian Tugrik
            '₱'    => 'PHP', // Philippine Peso
            '฿'    => 'THB', // Thai Baht
            '₺'    => 'TRY', // Turkish Lira
            '₼'    => 'AZN', // Azerbaijani Manat
            '₴'    => 'UAH', // Ukrainian Hryvnia
            '₦'    => 'NGN', // Nigerian Naira
            '₡'    => 'CRC', // Costa Rican Colón
            '₵'    => 'GHS', // Ghanaian Cedi
            '₸'    => 'KZT', // Kazakhstani Tenge
            'SR'   => 'SAR', // Saudi Riyal
            'zł'   => 'PLN', // Polish Zloty
            'kr'   => 'SEK', // Swedish Krona (Shared with NOK, DKK)
            'Fr'   => 'CHF', // Swiss Franc
            'Ksh'  => 'KES', // Kenyan Shilling
            'Ar'   => 'MGA', // Malagasy Ariary
            'Rp'   => 'IDR', // Indonesian Rupiah
            'RM'   => 'MYR', // Malaysian Ringgit
            'Br'   => 'ETB', // Ethiopian Birr
            'L'    => 'HNL', // Honduran Lempira
            'Q'    => 'GTQ', // Guatemalan Quetzal
            'kn'   => 'HRK', // Croatian Kuna
            'm'    => 'TMT', // Turkmenistan Manat
            'R$'   => 'BRL',  // Brazilian Real
        ];
        /**
         * Add Currency symbols (UTF-8) => ISO 4217 Code
         *
         * <meta itemprop="priceCurrency" content="EUR">
         * @see https://schema.org/priceCurrency
         *
         * @since 1.1
         *
         * @param  array  $currencyMap
         * .
         * @return array
         */
        $currencyMap = apply_filters('osec_currency_to_iso4217_map', $currencyMap);

        /* @var String $letters get all unique lettses */
        $letters = implode(array_unique(str_split(implode(array_keys($currencyMap)))));

        $cost_currency = preg_replace(
            '/[^a-z' . $letters . ']/i',
            '',
            $coast
        );
        if (in_array($cost_currency, array_keys($currencyMap), true)) {
            $cost_currency = $currencyMap[$cost_currency];
        }

        /**
         * Allows you to alter or provide a valid priceCurrency
         *
         * @see https://schema.org/priceCurrency
         * @since 1.0
         *
         * @param  string  $cost_currency Assumed ISO 4217 value.
         * @param  string  $coast Raw field value.
         * @return string Must return ISO 4217 3 letter string as schema requires or null to hide.
         */
        $cost_currency = apply_filters('osec_currency_iso4217_value_alter', $cost_currency, $coast);

        return $cost_currency ?: null;
    }
}
